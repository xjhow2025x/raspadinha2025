<?php
@session_start();
require_once '../conexao.php';
header('Content-Type: application/json');

// Taxa de acerto desejada para usuários normais (ex: 0.01 = 1%)
$desiredWinRate = 0.08;

$userId       = $_SESSION['usuario_id'] ?? 0;
$raspadinhaId = (int)($_POST['raspadinha_id'] ?? 0);

if (!$userId || !$raspadinhaId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Requisição inválida']));
}

// Busca valor da raspadinha
$stmt = $pdo->prepare("SELECT valor FROM raspadinhas WHERE id = ?");
$stmt->execute([$raspadinhaId]);
$raspadinha = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$raspadinha) {
    http_response_code(404);
    exit(json_encode(['error' => 'Raspadinha não encontrada']));
}

// Busca saldo e status de influencer
$stmt = $pdo->prepare("SELECT saldo, influencer FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$usuario) {
    http_response_code(404);
    exit(json_encode(['error' => 'Usuário não encontrado']));
}
if ($usuario['saldo'] < $raspadinha['valor']) {
    http_response_code(403);
    exit(json_encode(['error' => 'Saldo insuficiente']));
}

// Deduz o valor
$pdo->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE id = ?")
    ->execute([$raspadinha['valor'], $userId]);

// Carrega os prêmios configurados
$stmt = $pdo->prepare("SELECT id, probabilidade, valor FROM raspadinha_premios WHERE raspadinha_id = ?");
$stmt->execute([$raspadinhaId]);
$premiosBrutos = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($premiosBrutos) === 0) {
    http_response_code(500);
    exit(json_encode(['error' => 'Nenhum prêmio configurado']));
}

$isInfluencer = (int)$usuario['influencer'] === 1;

// Se for influencer, mantém boost
if ($isInfluencer) {
    foreach ($premiosBrutos as &$p) {
        if ($p['valor'] > 50) {
            $p['probabilidade'] += 40;
        }
    }
    unset($p);
}
// Senão, normaliza para desiredWinRate
else {
    $winPrizes  = array_filter($premiosBrutos, fn($p) => $p['valor'] > 0);
    $losePrizes = array_filter($premiosBrutos, fn($p) => $p['valor'] == 0);

    $sumWin  = array_sum(array_column($winPrizes,  'probabilidade'));
    $sumLose = array_sum(array_column($losePrizes, 'probabilidade'));
    $totalOriginal = $sumWin + $sumLose;

    if ($totalOriginal > 0 && $sumWin > 0 && $sumLose > 0) {
        $scaleWin  = ($totalOriginal * $desiredWinRate)      / $sumWin;
        $scaleLose = ($totalOriginal * (1 - $desiredWinRate)) / $sumLose;
        foreach ($premiosBrutos as &$p) {
            $p['probabilidade'] *= ($p['valor'] > 0) ? $scaleWin : $scaleLose;
        }
        unset($p);
    }
}

/**
 * Sorteia um prêmio com base em probabilidades ajustadas.
 */
function sortearPremio(array $premios): int {
    $total = array_sum(array_column($premios, 'probabilidade'));
    if ($total <= 0) {
        return (int)$premios[0]['id'];
    }
    $rand = mt_rand(0, (int)($total * 100)) / 100;
    $acumulado = 0;
    foreach ($premios as $p) {
        $acumulado += $p['probabilidade'];
        if ($rand <= $acumulado) {
            return (int)$p['id'];
        }
    }
    return (int)$premios[array_key_last($premios)]['id'];
}

/**
 * Verifica se há 3 itens iguais na grade
 */
function temTresIguais(array $grid): bool {
    $counts = array_count_values($grid);
    foreach ($counts as $count) {
        if ($count >= 3) {
            return true;
        }
    }
    return false;
}

/**
 * Verifica se o prêmio é vencedor (valor > 0)
 */
function isPremioVencedor(array $premios, int $premioId): bool {
    foreach ($premios as $premio) {
        if ($premio['id'] == $premioId) {
            return $premio['valor'] > 0;
        }
    }
    return false;
}

/**
 * Gera a grade 3×3 garantindo que todos os prêmios sejam usados
 * e controlando a probabilidade de 3 itens iguais
 */
function gerarGrade(array $premios): array {
    $maxAttempts = 30;
    $attempts = 0;
    
    while ($attempts++ < $maxAttempts) {
        $grid = [];
        
        // Se temos 9 ou menos prêmios, garante que todos sejam usados
        if (count($premios) <= 9) {
            // Adiciona todos os prêmios primeiro
            foreach ($premios as $premio) {
                $grid[] = (int)$premio['id'];
            }
            
            // Se temos exatamente 9, embaralha e retorna
            if (count($grid) === 9) {
                shuffle($grid);
                return $grid;
            }
            
            // Se temos menos de 9, preenche o resto
            while (count($grid) < 9) {
                $premioId = sortearPremio($premios);
                $grid[] = $premioId;
            }
        } else {
            // Se temos mais de 9 prêmios, sorteia aleatoriamente
            for ($i = 0; $i < 9; $i++) {
                $premioId = sortearPremio($premios);
                $grid[] = $premioId;
            }
        }
        
        // Verifica se há 3 itens iguais
        if (temTresIguais($grid)) {
            // Se há 3 iguais, verifica se é um prêmio vencedor
            $counts = array_count_values($grid);
            $temTresVencedores = false;
            
            foreach ($counts as $premioId => $count) {
                if ($count >= 3 && isPremioVencedor($premios, $premioId)) {
                    $temTresVencedores = true;
                    break;
                }
            }
            
            // Se tem 3 vencedores, aceita a grade
            if ($temTresVencedores) {
                return $grid;
            }
            
            // Se tem 3 perdedores, rejeita e tenta novamente
            continue;
        }
        
        // Se não tem 3 iguais, aceita a grade
        return $grid;
    }
    
    // Se não conseguiu gerar uma grade válida, gera uma simples
    $grid = [];
    for ($i = 0; $i < 9; $i++) {
        $premioId = sortearPremio($premios);
        $grid[] = $premioId;
    }
    
    return $grid;
}

// Executa o sorteio e persiste no banco
try {
    $grid = gerarGrade($premiosBrutos);
    
    // Validação adicional
    if (!is_array($grid) || count($grid) !== 9) {
        throw new Exception('Grade inválida gerada: ' . json_encode($grid));
    }
    
    // Verifica se todos os IDs são válidos
    foreach ($grid as $premioId) {
        $premioValido = false;
        foreach ($premiosBrutos as $premio) {
            if ($premio['id'] == $premioId) {
                $premioValido = true;
                break;
            }
        }
        if (!$premioValido) {
            throw new Exception('ID de prêmio inválido encontrado: ' . $premioId);
        }
    }
    
} catch (Exception $e) {
    error_log('Erro no sorteio de raspadinha: ' . $e->getMessage());
    http_response_code(500);
    exit(json_encode([
        'error' => 'Erro interno no sorteio. Tente novamente.',
        'debug' => $e->getMessage()
    ]));
}

$stmt = $pdo->prepare("INSERT INTO orders (user_id, raspadinha_id, premios_json) VALUES (?, ?, ?)");
$stmt->execute([$userId, $raspadinhaId, json_encode($grid)]);
$orderId   = $pdo->lastInsertId();
$novoSaldo = $usuario['saldo'] - $raspadinha['valor'];

echo json_encode([
    'success'    => true,
    'order_id'   => $orderId,
    'grid'       => $grid,
    'saldo_novo' => $novoSaldo
]);

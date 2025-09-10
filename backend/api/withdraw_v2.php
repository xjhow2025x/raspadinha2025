<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

require_once __DIR__ . '/../conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Validar dados obrigatórios
$required_fields = ['amount', 'pixKey', 'pixType', 'beneficiaryName', 'beneficiaryDocument'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
        exit;
    }
}

$amount = (float) $data['amount'];
$pixKey = trim($data['pixKey']);
$pixType = strtoupper($data['pixType']);
$beneficiaryName = trim($data['beneficiaryName']);
$beneficiaryDocument = preg_replace('/[^0-9]/', '', $data['beneficiaryDocument']);

// Validar tipos de chave Pix
$valid_pix_types = ['CPF', 'E-MAIL', 'TELEFONE', 'ALEATORIA'];
if (!in_array($pixType, $valid_pix_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de chave Pix inválido']);
    exit;
}

// Validar CPF do beneficiário
if (strlen($beneficiaryDocument) !== 11) {
    echo json_encode(['success' => false, 'message' => 'CPF do beneficiário inválido']);
    exit;
}

// Validar chave Pix baseada no tipo
switch ($pixType) {
    case 'CPF':
        $cleanKey = preg_replace('/[^0-9]/', '', $pixKey);
        if (strlen($cleanKey) !== 11) {
            echo json_encode(['success' => false, 'message' => 'CPF inválido']);
            exit;
        }
        $pixKey = $cleanKey;
        break;
    case 'E-MAIL':
        if (!filter_var($pixKey, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
            exit;
        }
        break;
    case 'TELEFONE':
        $cleanKey = preg_replace('/[^0-9]/', '', $pixKey);
        if (strlen($cleanKey) < 10 || strlen($cleanKey) > 11) {
            echo json_encode(['success' => false, 'message' => 'Telefone inválido']);
            exit;
        }
        $pixKey = $cleanKey;
        break;
    case 'ALEATORIA':
        if (strlen($pixKey) < 32) {
            echo json_encode(['success' => false, 'message' => 'Chave aleatória inválida']);
            exit;
        }
        break;
}

try {
    $pdo->beginTransaction();

    // Verificar saldo do usuário
    $stmt = $pdo->prepare("SELECT saldo, nome, email, telefone FROM usuarios WHERE id = :id FOR UPDATE");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }

    // Verificar configurações de saque
    $stmt = $pdo->prepare("SELECT saque_min, saque_max_diario, taxa_saque FROM config LIMIT 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $saqueMin = $config['saque_min'] ?? 50;
    $saqueMaxDiario = $config['saque_max_diario'] ?? 2000;
    $taxaSaque = $config['taxa_saque'] ?? 0;

    if ($amount < $saqueMin) {
        throw new Exception("Valor mínimo para saque é R$ " . number_format($saqueMin, 2, ',', '.'));
    }

    if ($usuario['saldo'] < $amount) {
        throw new Exception('Saldo insuficiente para realizar o saque');
    }

    // Verificar se há saque pendente
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM saques WHERE user_id = :user_id AND status = 'PENDING'");
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $hasPending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

    if ($hasPending > 0) {
        throw new Exception('Você já possui um saque pendente. Aguarde a conclusão para solicitar outro.');
    }

    // Verificar limite diário de saque
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total_sacado_hoje 
        FROM saques 
        WHERE user_id = :user_id 
        AND DATE(created_at) = :hoje 
        AND status IN ('PENDING', 'PAID')
    ");
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();
    $totalSacadoHoje = $stmt->fetch(PDO::FETCH_ASSOC)['total_sacado_hoje'];

    if (($totalSacadoHoje + $amount) > $saqueMaxDiario) {
        $limiteRestante = $saqueMaxDiario - $totalSacadoHoje;
        if ($limiteRestante <= 0) {
            throw new Exception("Você já atingiu o limite diário de saque de R$ " . number_format($saqueMaxDiario, 2, ',', '.') . ". Tente novamente amanhã.");
        } else {
            throw new Exception("Este saque excede seu limite diário. Você ainda pode sacar R$ " . number_format($limiteRestante, 2, ',', '.') . " hoje. Limite diário: R$ " . number_format($saqueMaxDiario, 2, ',', '.'));
        }
    }

    // Debitar saldo do usuário
    $newBalance = $usuario['saldo'] - $amount;
    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = :saldo WHERE id = :id");
    $stmt->bindParam(':saldo', $newBalance);
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    // Criar registro de saque
    $transactionId = uniqid('WTH_');
    $stmt = $pdo->prepare("INSERT INTO saques (transactionId, user_id, nome, cpf, valor, pix_key, pix_type, beneficiary_name, beneficiary_document, status) 
                           VALUES (:transactionId, :user_id, :nome, :cpf, :valor, :pix_key, :pix_type, :beneficiary_name, :beneficiary_document, 'PENDING')");
    $stmt->bindParam(':transactionId', $transactionId);
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $usuario['nome']);
    $stmt->bindParam(':cpf', $beneficiaryDocument);
    $stmt->bindParam(':valor', $amount);
    $stmt->bindParam(':pix_key', $pixKey);
    $stmt->bindParam(':pix_type', $pixType);
    $stmt->bindParam(':beneficiary_name', $beneficiaryName);
    $stmt->bindParam(':beneficiary_document', $beneficiaryDocument);
    $stmt->execute();

    $saqueId = $pdo->lastInsertId();

    $pdo->commit();

    // Verificar se há taxa de saque configurada
    if ($taxaSaque > 0) {
        $valorTaxa = ($amount * $taxaSaque) / 100;
        
        // Aplicar taxa mínima de R$ 5,00
        $taxaMinima = 5.00;
        if ($valorTaxa < $taxaMinima) {
            $valorTaxa = $taxaMinima;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Saque solicitado com sucesso!',
            'transaction_id' => $transactionId,
            'saque_id' => $saqueId,
            'tem_taxa' => true,
            'taxa_info' => [
                'saque_id' => $saqueId,
                'valor_saque' => $amount,
                'percentual_taxa' => $taxaSaque,
                'valor_taxa' => $valorTaxa,
                'valor_liquido' => $amount - $valorTaxa,
                'cpf_usuario' => $beneficiaryDocument,
                'taxa_minima_aplicada' => $valorTaxa == $taxaMinima
            ],
            'limite_restante_hoje' => number_format($saqueMaxDiario - ($totalSacadoHoje + $amount), 2, ',', '.')
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Saque solicitado com sucesso! Aguarde a aprovação.',
            'transaction_id' => $transactionId,
            'saque_id' => $saqueId,
            'tem_taxa' => false,
            'limite_restante_hoje' => number_format($saqueMaxDiario - ($totalSacadoHoje + $amount), 2, ',', '.')
        ]);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
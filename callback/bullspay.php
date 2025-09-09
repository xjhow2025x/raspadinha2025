<?php

session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . "- PAYLOAD BULLSPAY: " . print_r($data, true) . PHP_EOL, FILE_APPEND);
file_put_contents('daanrox.txt', "----------------------------------------------------------" . PHP_EOL, FILE_APPEND);

if (!isset($data['paymentId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$body          = $data;
$paymentType   = $body['paymentMethod']   ?? '';
$status        = $body['status']        ?? '';
$paymentId     = $body['paymentId'] ?? ''; // ID da Bullspay
$externalId    = $body['externalId'] ?? ''; // ID do nosso sistema

if ($paymentType !== 'PIX' || $status !== 'APPROVED' || (empty($paymentId) && empty($externalId))) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados insuficientes ou transação não paga']);
    exit;
}

require_once __DIR__ . '/../conexao.php';

try {
    $pdo->beginTransaction();

    // Verificar se é uma taxa de saque - procurar por externalId ou paymentId
    $stmt = $pdo->prepare("SELECT id, user_id, saque_id, valor_saque, valor_taxa, status FROM taxas_saque WHERE transaction_id = :externalId OR transaction_id = :paymentId LIMIT 1 FOR UPDATE");
    $stmt->execute([':externalId' => $externalId, ':paymentId' => $paymentId]);
    $taxaSaque = $stmt->fetch();

    if ($taxaSaque) {
        // Processar taxa de saque
        if ($taxaSaque['status'] === 'PAID') {
            $pdo->commit();
            echo json_encode(['message' => 'Esta taxa já foi aprovada']);
            exit;
        }

        // Atualizar status da taxa
        $stmt = $pdo->prepare("UPDATE taxas_saque SET status = 'PAID', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $taxaSaque['id']]);

        // Atualizar status do saque para "EM PROCESSAMENTO" (aguardando aprovação do admin)
        $stmt = $pdo->prepare("UPDATE saques SET status = 'PROCESSING', updated_at = NOW() WHERE id = :saque_id");
        $stmt->execute([':saque_id' => $taxaSaque['saque_id']]);

        $pdo->commit();
        echo json_encode(['message' => 'Taxa de saque processada com sucesso']);
        exit;
    }

    // Verificar se é um depósito normal - procurar por externalId, paymentId ou gateway_transaction_id
    $stmt = $pdo->prepare("SELECT id, user_id, valor, status FROM depositos WHERE transactionId = :externalId OR transactionId = :paymentId OR gateway_transaction_id = :paymentId LIMIT 1 FOR UPDATE");
    $stmt->execute([':externalId' => $externalId, ':paymentId' => $paymentId]);
    $deposito = $stmt->fetch();

    if (!$deposito) {
        // Log para debug
        file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . "- TRANSAÇÃO NÃO ENCONTRADA - PaymentId: $paymentId, ExternalId: $externalId" . PHP_EOL, FILE_APPEND);
        
        $pdo->commit();
        http_response_code(404);
        echo json_encode(['error' => 'Transação não encontrada']);
        exit;
    }

    if ($deposito['status'] === 'PAID') {
        $pdo->commit();
        echo json_encode(['message' => 'Este pagamento já foi aprovado']);
        exit;
    }

    // Processar depósito normal
    $stmt = $pdo->prepare("UPDATE depositos SET status = 'PAID', updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $deposito['id']]);

    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :valor WHERE id = :uid");
    $stmt->execute([
        ':valor' => $deposito['valor'],
        ':uid'   => $deposito['user_id']
    ]);

    // Log de sucesso
    file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . "- DEPÓSITO APROVADO - ID: {$deposito['id']}, Valor: {$deposito['valor']}, User: {$deposito['user_id']}" . PHP_EOL, FILE_APPEND);

    // Processar comissão de afiliado
    $stmt = $pdo->prepare("SELECT indicacao FROM usuarios WHERE id = :uid");
    $stmt->execute([':uid' => $deposito['user_id']]);
    $usuario = $stmt->fetch();

    if ($usuario && !empty($usuario['indicacao'])) {
        $stmt = $pdo->prepare("SELECT id, comissao_cpa, banido FROM usuarios WHERE id = :afiliado_id");
        $stmt->execute([':afiliado_id' => $usuario['indicacao']]);
        $afiliado = $stmt->fetch();

        if ($afiliado && $afiliado['banido'] != 1 && !empty($afiliado['comissao_cpa'])) {
            $comissao = $afiliado['comissao_cpa'];
            
            $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :comissao WHERE id = :afiliado_id");
            $stmt->execute([
                ':comissao' => $comissao,
                ':afiliado_id' => $afiliado['id']
            ]);

            $stmt = $pdo->prepare("INSERT INTO transacoes_afiliados 
                                  (afiliado_id, usuario_id, deposito_id, valor, created_at) 
                                  VALUES (:afiliado_id, :usuario_id, :deposito_id, :valor, NOW())");
            $stmt->execute([
                ':afiliado_id' => $afiliado['id'],
                ':usuario_id' => $deposito['user_id'],
                ':deposito_id' => $deposito['id'],
                ':valor' => $comissao
            ]);

            // Log de comissão
            file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . "- COMISSÃO PAGA - Afiliado: {$afiliado['id']}, Valor: $comissao" . PHP_EOL, FILE_APPEND);
        }
    }

    $pdo->commit();
    echo json_encode(['message' => 'OK']);
} catch (Exception $e) {
    $pdo->rollBack();
    file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . "- ERRO NO CALLBACK: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
    exit;
}
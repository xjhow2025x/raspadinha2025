<?php
// Callback para BullsPay New API
// Verificar se a sessão já está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log de debug para webhook
$logFile = __DIR__ . '/bullspay_new_webhook.log';

function logWebhook($message, $data = null) {
    global $logFile;
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data,
        'headers' => getallheaders(),
        'raw_input' => file_get_contents('php://input')
    ];
    $logMessage = json_encode($logData, JSON_PRETTY_PRINT) . PHP_EOL . "---" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Log inicial do webhook
logWebhook('Webhook recebido', $_POST);

// Verificar o método da requisição
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Resposta para requisições GET (como preview do WhatsApp)
    logWebhook('Requisição GET recebida - respondendo com status OK');
    http_response_code(200);
    echo json_encode(['status' => 'webhook_active', 'message' => 'BullsPay New Webhook está ativo']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logWebhook('Método não permitido', $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Obter dados do webhook
$input = file_get_contents('php://input');
$webhookData = json_decode($input, true);

// Log dos dados recebidos
logWebhook('Dados do webhook decodificados', $webhookData);

// Verificar se os dados foram recebidos corretamente
if (!$webhookData) {
    logWebhook('Erro ao decodificar JSON', $input);
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

require_once __DIR__ . '/../conexao.php';

try {
    // Verificar se é um evento de transação (aceitar tanto 'transaction' quanto 'transaction_updated')
    $eventType = $webhookData['event_type'] ?? '';
    if (!in_array($eventType, ['transaction', 'transaction_updated'])) {
        logWebhook('Tipo de evento não suportado', $eventType);
        http_response_code(200);
        echo json_encode(['status' => 'ignored', 'message' => 'Evento não é de transação']);
        exit;
    }

    // Extrair dados da transação
    $transactionData = $webhookData['data'] ?? [];
    $transactionId = $transactionData['id'] ?? null;
    $status = $transactionData['status'] ?? null;
    
    // A BullsPay New usa 'total_value' em vez de 'amount'
    $amount = isset($transactionData['total_value']) ? $transactionData['total_value'] / 100 : 0; // Converter de centavos

    logWebhook('Dados extraídos', [
        'transaction_id' => $transactionId,
        'status' => $status,
        'amount' => $amount,
        'event_type' => $eventType
    ]);

    if (!$transactionId) {
        logWebhook('ID da transação não encontrado', $transactionData);
        http_response_code(400);
        echo json_encode(['error' => 'ID da transação não encontrado']);
        exit;
    }

    // Buscar a transação no banco de dados
    $stmt = $pdo->prepare("SELECT * FROM depositos WHERE transactionId = :transactionId LIMIT 1");
    $stmt->bindParam(':transactionId', $transactionId, PDO::PARAM_STR);
    $stmt->execute();
    $deposito = $stmt->fetch();

    if (!$deposito) {
        logWebhook('Transação não encontrada no banco', $transactionId);
        http_response_code(404);
        echo json_encode(['error' => 'Transação não encontrada']);
        exit;
    }

    logWebhook('Transação encontrada no banco', $deposito);

    // Verificar se a transação já foi processada
    if ($deposito['status'] === 'PAID') {
        logWebhook('Transação já processada', $transactionId);
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }

    // Processar diferentes status
    switch (strtoupper($status)) {
        case 'PAID':
        case 'APPROVED':
        case 'COMPLETED':
            // Atualizar status da transação
            $stmt = $pdo->prepare("UPDATE depositos SET status = 'PAID' WHERE transactionId = :transactionId");
            $stmt->bindParam(':transactionId', $transactionId, PDO::PARAM_STR);
            $stmt->execute();

            // Adicionar saldo ao usuário
            $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :valor WHERE id = :user_id");
            $stmt->bindParam(':valor', $deposito['valor'], PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $deposito['user_id'], PDO::PARAM_INT);
            $stmt->execute();

            logWebhook('Pagamento aprovado e saldo adicionado', [
                'transaction_id' => $transactionId,
                'user_id' => $deposito['user_id'],
                'valor' => $deposito['valor']
            ]);

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Pagamento processado']);
            break;

        case 'CANCELLED':
        case 'FAILED':
        case 'EXPIRED':
            // Atualizar status da transação para cancelada
            $stmt = $pdo->prepare("UPDATE depositos SET status = 'CANCELLED' WHERE transactionId = :transactionId");
            $stmt->bindParam(':transactionId', $transactionId, PDO::PARAM_STR);
            $stmt->execute();

            logWebhook('Pagamento cancelado/falhou', [
                'transaction_id' => $transactionId,
                'status' => $status
            ]);

            http_response_code(200);
            echo json_encode(['status' => 'cancelled', 'message' => 'Pagamento cancelado']);
            break;

        default:
            logWebhook('Status não reconhecido', $status);
            http_response_code(200);
            echo json_encode(['status' => 'ignored', 'message' => 'Status não reconhecido']);
            break;
    }

} catch (Exception $e) {
    logWebhook('Erro no processamento', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
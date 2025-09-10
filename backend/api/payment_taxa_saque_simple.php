<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Mapear os dados da taxa para o formato do payment.php
$amount = isset($_POST['valor_taxa']) ? floatval(str_replace(',', '.', $_POST['valor_taxa'])) : 0;
$cpf = isset($_POST['cpf_usuario']) ? preg_replace('/\D/', '', $_POST['cpf_usuario']) : '';

// Validar taxa mínima de R$ 5,00
$taxaMinima = 5.00;
if ($amount < $taxaMinima) {
    http_response_code(400);
    echo json_encode(['error' => 'Taxa mínima é de R$ ' . number_format($taxaMinima, 2, ',', '.')]);
    exit;
}

if ($amount <= 0 || strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

require_once __DIR__ . '/../conexao.php';

try {
    $stmt = $pdo->query("SELECT active FROM gateway LIMIT 1");
    $activeGateway = $stmt->fetchColumn();

    if ($activeGateway !== 'bullspay') {
        throw new Exception('Gateway não configurada ou não suportada.');
    }

    $stmt = $pdo->query("SELECT url, secret_key FROM bullspay LIMIT 1");
    $bullspay = $stmt->fetch();

    if (!$bullspay) {
        throw new Exception('Credenciais Bullspay não encontradas.');
    }

    $url = rtrim($bullspay['url'], '/');
    $secretKey = $bullspay['secret_key'];

    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não autenticado.');
    }

    $usuario_id = $_SESSION['usuario_id'];

    $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base = $protocol . $host;
    $postbackUrl = $base . '/callback/bullspay.php';

    $ch = curl_init("$url/webhook.getWebhooks");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: $secretKey",
            "Content-Type: application/json"
        ]
    ]);
    $webhookResponse = curl_exec($ch);
    curl_close($ch);
    $webhooks = json_decode($webhookResponse, true);

    $webhookExists = false;
    if (isset($webhooks['result']) && is_array($webhooks['result'])) {
        foreach ($webhooks['result'] as $wh) {
            if (strpos($wh['callbackUrl'], $host) !== false) {
                $webhookExists = true;
                break;
            }
        }
    }

    if (!$webhookExists) {
        $payloadWebhook = json_encode([
            "callbackUrl" => $postbackUrl,
            "name" => $postbackUrl,
            "onBuyApproved" => true,
            "onRefound" => false,
            "onChargeback" => false,
            "onPixCreated" => false
        ]);

        $ch = curl_init("$url/webhook.create");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payloadWebhook,
            CURLOPT_HTTPHEADER => [
                "Authorization: $secretKey",
                "Content-Type: application/json"
            ]
        ]);
        $createResponse = curl_exec($ch);
        curl_close($ch);
        $createResult = json_decode($createResponse, true);

        $ch = curl_init("$url/webhook.getWebhooks");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: $secretKey",
                "Content-Type: application/json"
            ]
        ]);
        $webhookResponse = curl_exec($ch);
        curl_close($ch);
        $webhooks = json_decode($webhookResponse, true);

        $webhookConfirmed = false;
        if (isset($webhooks['result']) && is_array($webhooks['result'])) {
            foreach ($webhooks['result'] as $wh) {
                if (strpos($wh['callbackUrl'], $host) !== false) {
                    $webhookConfirmed = true;
                    break;
                }
            }
        }

        if (!$webhookConfirmed) {
            throw new Exception('Falha ao registrar webhook.');
        }
    }

    $payload = [
        'name'=> $usuario['nome'],
        'email' => $usuario['email'],
        'cpf' => $cpf,
        'phone'=> '31992812273',
        'paymentMethod' => 'PIX',
        'cep' => '32060-000',
        'complement' => 'Casa',
        'number'=> '1',
        'street' => 'Rua Apio Cardoso',
        'district' => 'Barueri',
        'city' => 'São Paulo',
        'state' => 'SP',
        'utmQuery' => 'string',
        'checkoutUrl' => 'string',
        'referrerUrl' => 'string',
        'externalId' => uniqid(),
        'postbackUrl' => $postbackUrl,
        'fingerPrints' => [
            [
                'provider' => 'string',
                'value' => 'string'
            ]
        ],
        'amount' => number_format($amount, 2, '.', '') * 100,
        'traceable' => true,
        'items' => [
            [
                'unitPrice' => number_format($amount, 2, '.', '') * 100,
                'title' => 'Taxa de Saque',
                'quantity' => 1,
                'tangible' => false
            ]
        ]
    ];

    $ch = curl_init("$url/transaction.purchase");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: $secretKey",
            "Content-Type: application/json"
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $pixData = json_decode($response, true);

    if (!isset($pixData['id'], $pixData['pixCode'])) {
        throw new Exception('Falha ao gerar QR Code.');
    }

    // Salvar como depósito temporariamente para testar
    $stmt = $pdo->prepare("
        INSERT INTO depositos (transactionId, user_id, nome, cpf, valor, status, qrcode)
        VALUES (:transactionId, :user_id, :nome, :cpf, :valor, 'PENDING', :qrcode)
    ");
    $stmt->execute([
        ':transactionId' => $pixData['id'],
        ':user_id' => $usuario_id,
        ':nome' => $usuario['nome'],
        ':cpf' => $cpf,
        ':valor' => $amount,
        ':qrcode' => $pixData['pixCode'],
    ]);

    $_SESSION['transactionId'] = $pixData['id'];

    echo json_encode([
        'success' => true,
        'qrcode' => $pixData['pixCode'],
        'transaction_id' => $pixData['id']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
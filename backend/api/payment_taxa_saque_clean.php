<?php
// Iniciar buffer de saída para evitar problemas com headers
ob_start();

// Iniciar sessão antes de qualquer output
session_start();

// Limpar qualquer output anterior
ob_clean();

// Definir headers JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Função simples para retornar JSON
function returnJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnJson(['error' => 'Método não permitido'], 405);
}

// Validar dados de entrada
$amount = isset($_POST['valor_taxa']) ? floatval(str_replace(',', '.', $_POST['valor_taxa'])) : 0;
$cpf = isset($_POST['cpf_usuario']) ? preg_replace('/\D/', '', $_POST['cpf_usuario']) : '';

// Validar taxa mínima
if ($amount < 5.00) {
    returnJson(['error' => 'Taxa mínima é de R$ 5,00'], 400);
}

if ($amount <= 0 || strlen($cpf) !== 11) {
    returnJson(['error' => 'Dados inválidos'], 400);
}

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    returnJson(['error' => 'Usuário não autenticado'], 401);
}

try {
    require_once __DIR__ . '/../conexao.php';

    // Verificar gateway
    $stmt = $pdo->query("SELECT active FROM gateway LIMIT 1");
    $activeGateway = $stmt->fetchColumn();

    if ($activeGateway !== 'bullspay') {
        throw new Exception('Gateway não configurado');
    }

    // Buscar credenciais
    $stmt = $pdo->query("SELECT url, secret_key FROM bullspay LIMIT 1");
    $bullspay = $stmt->fetch();

    if (!$bullspay) {
        throw new Exception('Credenciais não encontradas');
    }

    // Buscar dados do usuário
    $usuario_id = $_SESSION['usuario_id'];
    $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }

    // Preparar payload
    $payload = [
        'name' => $usuario['nome'],
        'email' => $usuario['email'],
        'cpf' => $cpf,
        'phone' => '31992812273',
        'paymentMethod' => 'PIX',
        'cep' => '32060-000',
        'complement' => 'Casa',
        'number' => '1',
        'street' => 'Rua Apio Cardoso',
        'district' => 'Barueri',
        'city' => 'São Paulo',
        'state' => 'SP',
        'utmQuery' => 'string',
        'checkoutUrl' => 'string',
        'referrerUrl' => 'string',
        'externalId' => 'TAXA-' . uniqid(),
        'postbackUrl' => 'http://' . $_SERVER['HTTP_HOST'] . '/callback/bullspay.php',
        'fingerPrints' => [['provider' => 'string', 'value' => 'string']],
        'amount' => intval($amount * 100),
        'traceable' => true,
        'items' => [[
            'unitPrice' => intval($amount * 100),
            'title' => 'Taxa de Saque - R$ ' . number_format($amount, 2, ',', '.'),
            'quantity' => 1,
            'tangible' => false
        ]]
    ];

    // Fazer requisição para Bullspay
    $ch = curl_init(rtrim($bullspay['url'], '/') . '/transaction.purchase');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: {$bullspay['secret_key']}",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Erro cURL: $curlError");
    }

    if ($httpCode !== 200) {
        throw new Exception("Erro HTTP: $httpCode");
    }

    $pixData = json_decode($response, true);

    if (!isset($pixData['id'], $pixData['pixCode'])) {
        throw new Exception('Resposta inválida da API');
    }

    // Salvar transação
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
        ':qrcode' => $pixData['pixCode']
    ]);

    $_SESSION['transactionId'] = $pixData['id'];

    // Retornar sucesso
    returnJson([
        'success' => true,
        'qrcode' => $pixData['pixCode'],
        'transaction_id' => $pixData['id']
    ]);

} catch (Exception $e) {
    returnJson(['error' => $e->getMessage()], 500);
}
?>
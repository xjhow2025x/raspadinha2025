<?php
// Limpar qualquer output anterior
ob_clean();

session_start();

// Headers corretos para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Função para log detalhado
function logDebug($message, $data = null) {
    try {
        $logFile = __DIR__ . '/../payment_taxa_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message";
        if ($data !== null) {
            $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $logMessage .= "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // Ignorar erros de log para não afetar a resposta
    }
}

// Função para retornar resposta JSON garantida
function jsonResponse($data, $httpCode = 200) {
    try {
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        
        http_response_code($httpCode);
        
        // Garantir que os dados sejam válidos para JSON
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($jsonData === false) {
            // Se falhar ao codificar, retornar erro simples
            $jsonData = json_encode(['error' => 'Erro interno do servidor'], JSON_UNESCAPED_UNICODE);
        }
        
        echo $jsonData;
        exit;
    } catch (Exception $e) {
        // Último recurso - resposta de erro simples
        http_response_code(500);
        echo '{"error":"Erro interno do servidor"}';
        exit;
    }
}

try {
    logDebug("=== INÍCIO DA REQUISIÇÃO ===");
    logDebug("Método da requisição", $_SERVER['REQUEST_METHOD']);
    logDebug("Dados POST recebidos", $_POST);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logDebug("Erro: Método não permitido");
        jsonResponse(['error' => 'Método não permitido'], 405);
    }

    // Mapear os dados da taxa para o formato do payment.php
    $amount = isset($_POST['valor_taxa']) ? floatval(str_replace(',', '.', $_POST['valor_taxa'])) : 0;
    $cpf = isset($_POST['cpf_usuario']) ? preg_replace('/\D/', '', $_POST['cpf_usuario']) : '';

    logDebug("Valores processados", ['amount' => $amount, 'cpf' => $cpf]);

    // Validar taxa mínima de R$ 5,00
    $taxaMinima = 5.00;
    if ($amount < $taxaMinima) {
        logDebug("Erro: Taxa menor que a mínima", ['amount' => $amount, 'minima' => $taxaMinima]);
        jsonResponse(['error' => 'Taxa mínima é de R$ ' . number_format($taxaMinima, 2, ',', '.')], 400);
    }

    if ($amount <= 0 || strlen($cpf) !== 11) {
        logDebug("Erro: Dados inválidos", ['amount' => $amount, 'cpf_length' => strlen($cpf)]);
        jsonResponse(['error' => 'Dados inválidos'], 400);
    }

    require_once __DIR__ . '/../conexao.php';

    logDebug("Verificando gateway ativo");
    $stmt = $pdo->query("SELECT active FROM gateway LIMIT 1");
    $activeGateway = $stmt->fetchColumn();
    logDebug("Gateway ativo", $activeGateway);

    if ($activeGateway !== 'bullspay') {
        throw new Exception('Gateway não configurado ou não suportado.');
    }

    logDebug("Buscando credenciais Bullspay");
    $stmt = $pdo->query("SELECT url, secret_key FROM bullspay LIMIT 1");
    $bullspay = $stmt->fetch();
    logDebug("Credenciais encontradas", ['url' => $bullspay['url'] ?? 'N/A', 'secret_key_length' => strlen($bullspay['secret_key'] ?? '')]);

    if (!$bullspay) {
        throw new Exception('Credenciais Bullspay não encontradas.');
    }

    $url = rtrim($bullspay['url'], '/');
    $secretKey = $bullspay['secret_key'];

    if (!isset($_SESSION['usuario_id'])) {
        logDebug("Erro: Usuário não autenticado");
        throw new Exception('Usuário não autenticado.');
    }

    $usuario_id = $_SESSION['usuario_id'];
    logDebug("ID do usuário", $usuario_id);

    $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch();
    logDebug("Dados do usuário", $usuario);

    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base = $protocol . $host;
    $postbackUrl = $base . '/callback/bullspay.php';
    logDebug("URL de callback", $postbackUrl);

    // Preparar payload para o PIX
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
        'externalId' => 'TAXA-' . uniqid(),
        'postbackUrl' => $postbackUrl,
        'fingerPrints' => [
            [
                'provider' => 'string',
                'value' => 'string'
            ]
        ],
        'amount' => intval($amount * 100), // Converter para centavos
        'traceable' => true,
        'items' => [
            [
                'unitPrice' => intval($amount * 100),
                'title' => 'Taxa de Saque - R$ ' . number_format($amount, 2, ',', '.'),
                'quantity' => 1,
                'tangible' => false
            ]
        ]
    ];

    logDebug("Payload preparado", $payload);

    // Fazer requisição para gerar PIX
    $ch = curl_init("$url/transaction.purchase");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: $secretKey",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    logDebug("Fazendo requisição para", "$url/transaction.purchase");
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    logDebug("Resposta da API Bullspay", [
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'response' => substr($response, 0, 1000) // Limitar log para evitar problemas
    ]);

    if ($curlError) {
        throw new Exception("Erro na requisição cURL: $curlError");
    }

    if ($httpCode !== 200) {
        throw new Exception("Erro HTTP: $httpCode");
    }

    $pixData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logDebug("Erro ao decodificar JSON", ['error' => json_last_error_msg()]);
        throw new Exception('Erro ao processar resposta da API.');
    }

    logDebug("Dados do PIX decodificados", ['id' => $pixData['id'] ?? 'N/A', 'has_pixCode' => isset($pixData['pixCode'])]);

    if (!isset($pixData['id'], $pixData['pixCode'])) {
        logDebug("Erro: Resposta inválida da API", array_keys($pixData));
        throw new Exception('Falha ao gerar QR Code. Resposta inválida da API.');
    }

    // Salvar na tabela de depósitos temporariamente
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

    logDebug("Transação salva no banco", ['transaction_id' => $pixData['id']]);

    $_SESSION['transactionId'] = $pixData['id'];

    $result = [
        'success' => true,
        'qrcode' => $pixData['pixCode'],
        'transaction_id' => $pixData['id']
    ];

    logDebug("Sucesso! Retornando resultado", ['success' => true, 'has_qrcode' => !empty($pixData['pixCode'])]);
    jsonResponse($result);

} catch (Exception $e) {
    logDebug("ERRO CAPTURADO", ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    jsonResponse(['error' => $e->getMessage()], 500);
} catch (Error $e) {
    logDebug("ERRO FATAL CAPTURADO", ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    jsonResponse(['error' => 'Erro interno do servidor'], 500);
}

logDebug("=== FIM DA REQUISIÇÃO ===");
?>
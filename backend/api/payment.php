<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}
sleep(2);

require_once __DIR__ . '/../conexao.php';

function getActiveGatewayConfig($pdo) {
    try {
        $stmt = $pdo->query("SELECT active FROM gateway WHERE id = 2 LIMIT 1");
        $activeGateway = $stmt->fetchColumn();
        
        if (!$activeGateway) {
            $activeGateway = 'bullspay';
        }
        
        $config = ['gateway' => $activeGateway];
        
        switch ($activeGateway) {
            case 'bullspay':
                $stmt = $pdo->query("SELECT secret_key, url FROM bullspay WHERE id = 1");
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $config['secret_key'] = $data['secret_key'] ?? '';
                $config['url'] = $data['url'] ?? 'https://pay.bullspay.net/api/v1';
                break;
                
            case 'axisbanking':
                $stmt = $pdo->query("SELECT public_key, private_key, api_url FROM axisbanking WHERE id = 1");
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $config['public_key'] = $data['public_key'] ?? '';
                $config['private_key'] = $data['private_key'] ?? '';
                $config['url'] = $data['api_url'] ?? 'https://api.axisbanking.com/v1';
                break;
                
            case 'bullspay_new':
                $stmt = $pdo->query("SELECT public_key, private_key, api_url FROM bullspay_new WHERE id = 1");
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $config['public_key'] = $data['public_key'] ?? '';
                $config['private_key'] = $data['private_key'] ?? '';
                $config['url'] = $data['api_url'] ?? 'https://api.bullspay.com.br';
                break;
        }
        
        return $config;
    } catch (Exception $e) {
        return [
            'gateway' => 'bullspay',
            'secret_key' => ''
        ];
    }
}

// Função para obter configurações de split
function getSplitConfig($pdo) {
    try {
        // Assumindo que você tenha uma tabela 'splits_config' com as configurações
        // Se não tiver, você pode definir diretamente aqui
        $stmt = $pdo->query("SELECT email, type, value, active FROM splits_config WHERE active = 1");
        $splits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($splits)) {
            // Configuração padrão caso não tenha na base de dados
            return [
                [
                    'email' => 'suacasabet@gmail.com',
                    'type' => 'percentage',
                    'value' => 2
                ]
            ];
        }
        
        return $splits;
    } catch (Exception $e) {
        // Retorna configuração padrão em caso de erro
        return [
            [
                'email' => 'suacasabet@gmail.com',
                'type' => 'percentage', 
                'value' => 2
            ]
        ];
    }
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$gatewayConfig = getActiveGatewayConfig($pdo);
$selectedGateway = $gatewayConfig['gateway'];

$isTaxaSaque = isset($_POST['taxa_saque']) && $_POST['taxa_saque'] === 'true';

if ($isTaxaSaque) {
    $amount = isset($_POST['valor_taxa']) ? floatval(str_replace(',', '.', $_POST['valor_taxa'])) : 0;
    $cpf = isset($_POST['cpf_usuario']) ? preg_replace('/\D/', '', $_POST['cpf_usuario']) : '';
    
    $taxaMinima = 5.00;
    if ($amount < $taxaMinima) {
        http_response_code(400);
        echo json_encode(['error' => 'Taxa mínima é de R$ ' . number_format($taxaMinima, 2, ',', '.')]);
        exit;
    }
} else {
    $amount = isset($_POST['amount']) ? floatval(str_replace(',', '.', $_POST['amount'])) : 0;
    $cpf = isset($_POST['cpf']) ? preg_replace('/\D/', '', $_POST['cpf']) : '';
}

$errorLogFile = __DIR__ . '/payment_errors.log';

function logPaymentError($gateway, $message, $data = null) {
    global $errorLogFile;
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'gateway' => $gateway,
        'message' => $message,
        'data' => $data
    ];
    $logMessage = json_encode($logData) . PHP_EOL;
    file_put_contents($errorLogFile, $logMessage, FILE_APPEND);
}

if ($amount <= 0 || strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

try {
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

    switch ($selectedGateway) {
        case 'axisbanking':
            $axisApiUrl = rtrim($gatewayConfig['url'], '/') . '/transactions/v2/purchase';
            $axisApiKey = $gatewayConfig['private_key'];
            
            if (empty($gatewayConfig['public_key']) || empty($gatewayConfig['private_key'])) {
                throw new Exception('Credenciais do AxisBanking não configuradas no painel administrativo.');
            }
            
            $encodedApiKey = 'Basic ' . base64_encode('secret:' . $axisApiKey);
            
            $externalId = ($isTaxaSaque ? 'TAXA-' : 'S') . time() . rand(100000, 999999);
            
            $axisPostbackUrl = 'https://raspadinhasortudo.com/callback/axisbanking.php';
            
            $payload = [
                'currency' => 'BRL',
                'name' => $usuario['nome'],
                'email' => $usuario['email'],
                'cpf' => $cpf,
                'phone' => '91982375568',
                'amount' => $amount * 100,
                'description' => $isTaxaSaque ? 'Taxa de Saque - R$ ' . number_format($amount, 2, ',', '.') : 'Deposito Raspadinha',
                'responsibleDocument' => $cpf,
                'responsibleExternalId' => $externalId,
                'paymentMethod' => 'PIX',
                'postbackUrl' => $axisPostbackUrl
            ];

            logPaymentError('axisbanking', $isTaxaSaque ? 'Iniciando solicitação de PIX para taxa de saque' : 'Iniciando solicitação de PIX', [
                'url' => $axisApiUrl, 
                'payload' => $payload,
                'usuario_id' => $usuario_id,
                'is_taxa_saque' => $isTaxaSaque
            ]);

            $ch = curl_init($axisApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $encodedApiKey,
                    'Content-Type: application/json'
                ]
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            logPaymentError('axisbanking', 'Resposta da API', [
                'httpCode' => $httpCode,
                'response' => $response,
                'curlError' => $curlError
            ]);
            
            $axisData = json_decode($response, true);

            if (!isset($axisData['id'], $axisData['pixCode'])) {
                logPaymentError('axisbanking', 'Falha ao gerar QR Code', [
                    'httpCode' => $httpCode,
                    'response' => $axisData,
                    'error' => $curlError
                ]);
                throw new Exception('Falha ao gerar QR Code com API AxisBanking.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO depositos (transactionId, user_id, nome, cpf, valor, status, qrcode)
                VALUES (:transactionId, :user_id, :nome, :cpf, :valor, 'PENDING', :qrcode)
            ");
            
            $stmt->execute([
                ':transactionId' => $axisData['id'],
                ':user_id' => $usuario_id,
                ':nome' => $usuario['nome'],
                ':cpf' => $cpf,
                ':valor' => $amount,
                ':qrcode' => $axisData['pixCode'],
            ]);

            $_SESSION['transactionId'] = $axisData['id'];
            
            logPaymentError('axisbanking', $isTaxaSaque ? 'PIX para taxa de saque gerado com sucesso' : 'PIX gerado com sucesso', [
                'transactionId' => $axisData['id'],
                'usuario_id' => $usuario_id,
                'amount' => $amount,
                'is_taxa_saque' => $isTaxaSaque
            ]);

            echo json_encode([
                'qrcode' => $axisData['pixCode']
            ]);
            break;

        case 'bullspay_new':
            $bullspayNewApiUrl = rtrim($gatewayConfig['url'], '/') . '/api/transactions/create';
            
            if (empty($gatewayConfig['public_key']) || empty($gatewayConfig['private_key'])) {
                throw new Exception('Credenciais do BullsPay New não configuradas no painel administrativo.');
            }
            
            $ddd = rand(11, 99);
            $number = rand(10000000, 99999999);
            $randomPhone = "{$ddd}9{$number}";
            
            $externalId = ($isTaxaSaque ? 'TAXA-' : 'S') . time() . rand(100000, 999999);
            
            // Obter configurações de split
            $splitsConfig = getSplitConfig($pdo);
            
            $payload = [
                'amount' => $amount * 100,
                'buyer_infos' => [
                    'buyer_name' => $usuario['nome'],
                    'buyer_email' => $usuario['email'],
                    'buyer_document' => $cpf,
                    'buyer_phone' => $randomPhone
                ],
                'splits' => $splitsConfig,
                'external_id' => $externalId
            ];

            logPaymentError('bullspay_new', $isTaxaSaque ? 'Iniciando solicitação de PIX para taxa de saque' : 'Iniciando solicitação de PIX', [
                'url' => $bullspayNewApiUrl, 
                'payload' => $payload,
                'usuario_id' => $usuario_id,
                'is_taxa_saque' => $isTaxaSaque,
                'public_key' => $gatewayConfig['public_key'],
                'private_key_length' => strlen($gatewayConfig['private_key']),
                'splits' => $splitsConfig
            ]);

            $ch = curl_init($bullspayNewApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-Public-Key: ' . $gatewayConfig['public_key'],
                    'X-Private-Key: ' . $gatewayConfig['private_key']
                ]
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            logPaymentError('bullspay_new', 'Resposta da API', [
                'httpCode' => $httpCode,
                'response' => $response,
                'curlError' => $curlError
            ]);
            
            $bullspayNewData = json_decode($response, true);

            if (!isset($bullspayNewData['success']) || $bullspayNewData['success'] !== true || 
                !isset($bullspayNewData['data']['payment_data']['id']) || 
                !isset($bullspayNewData['data']['pix_data']['qrcode'])) {
                logPaymentError('bullspay_new', 'Falha ao gerar QR Code', [
                    'httpCode' => $httpCode,
                    'response' => $bullspayNewData,
                    'error' => $curlError
                ]);
                throw new Exception('Falha ao gerar QR Code com API BullsPay New.');
            }

            $transactionId = $bullspayNewData['data']['payment_data']['id'];
            $qrCode = $bullspayNewData['data']['pix_data']['qrcode'];

            $stmt = $pdo->prepare("
                INSERT INTO depositos (transactionId, user_id, nome, cpf, valor, status, qrcode)
                VALUES (:transactionId, :user_id, :nome, :cpf, :valor, 'PENDING', :qrcode)
            ");
            
            $stmt->execute([
                ':transactionId' => $transactionId,
                ':user_id' => $usuario_id,
                ':nome' => $usuario['nome'],
                ':cpf' => $cpf,
                ':valor' => $amount,
                ':qrcode' => $qrCode,
            ]);

            $_SESSION['transactionId'] = $transactionId;
            
            logPaymentError('bullspay_new', $isTaxaSaque ? 'PIX para taxa de saque gerado com sucesso' : 'PIX gerado com sucesso', [
                'transactionId' => $transactionId,
                'usuario_id' => $usuario_id,
                'amount' => $amount,
                'is_taxa_saque' => $isTaxaSaque,
                'splits_applied' => $splitsConfig
            ]);

            echo json_encode([
                'qrcode' => $qrCode
            ]);
            break;

        case 'bullspay':
        default:
            if (empty($gatewayConfig['secret_key'])) {
                throw new Exception('Secret Key do Bullspay não configurada no painel administrativo.');
            }
            
            $bullspayApiUrl = 'https://pay.bullspay.net/api/v1/transaction.purchase';
            
            $externalId = ($isTaxaSaque ? 'TAXA-' : 'DEP-') . $usuario_id . '-' . uniqid();
            
            $ddd = rand(11, 99);
            $number = rand(10000000, 99999999);
            $randomPhone = "{$ddd}9{$number}";
            
            $payload = [
                'name' => $usuario['nome'],
                'email' => $usuario['email'],
                'cpf' => $cpf,
                'phone' => $randomPhone,
                'paymentMethod' => 'PIX',
                'amount' => $amount * 100,
                'externalId' => $externalId,
                'postbackUrl' => 'https://raspadinhasortudo.com/callback/bullspay.php',
                'traceable' => true,
                'items' => [
                    [
                        'unitPrice' => $amount * 100,
                        'title' => $isTaxaSaque ? 'Taxa de Saque' : 'Depósito Raspadinha',
                        'quantity' => 1,
                        'tangible' => false
                    ]
                ]
            ];

            logPaymentError('bullspay', $isTaxaSaque ? 'Iniciando solicitação de PIX para taxa de saque' : 'Iniciando solicitação de PIX', [
                'url' => $bullspayApiUrl, 
                'payload' => $payload,
                'usuario_id' => $usuario_id,
                'is_taxa_saque' => $isTaxaSaque,
                'secret_key_length' => strlen($gatewayConfig['secret_key'])
            ]);

            $ch = curl_init($bullspayApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: ' . $gatewayConfig['secret_key']
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            logPaymentError('bullspay', 'Resposta da API', [
                'httpCode' => $httpCode,
                'response' => $response,
                'curlError' => $curlError
            ]);
            
            $bullspayData = json_decode($response, true);

            if ($httpCode !== 200 && $httpCode !== 201) {
                logPaymentError('bullspay', 'Falha na requisição HTTP', [
                    'httpCode' => $httpCode,
                    'response' => $bullspayData,
                    'error' => $curlError
                ]);
                
                if ($httpCode === 401) {
                    throw new Exception('Erro de autenticação com a API Bullspay. Verifique a secret_key no painel administrativo.');
                }
                
                throw new Exception('Falha na comunicação com a API Bullspay. HTTP Code: ' . $httpCode);
            }

            if (!isset($bullspayData['id']) || (!isset($bullspayData['qrCode']) && !isset($bullspayData['pixCode']))) {
                logPaymentError('bullspay', 'Resposta da API não contém dados necessários', [
                    'httpCode' => $httpCode,
                    'response' => $bullspayData,
                    'expected_fields' => ['id', 'qrCode ou pixCode']
                ]);
                
                $errorMessage = 'Falha ao gerar QR Code com API Bullspay.';
                if (isset($bullspayData['message'])) {
                    $errorMessage .= ' Erro: ' . $bullspayData['message'];
                } elseif (isset($bullspayData['error'])) {
                    $errorMessage .= ' Erro: ' . $bullspayData['error'];
                }
                
                throw new Exception($errorMessage);
            }

            $transactionId = $bullspayData['id'];
            $qrCode = $bullspayData['pixCode'] ?? $bullspayData['qrCode'] ?? '';

            $stmt = $pdo->prepare("
                INSERT INTO depositos (transactionId, user_id, nome, cpf, valor, status, qrcode, gateway_transaction_id)
                VALUES (:transactionId, :user_id, :nome, :cpf, :valor, 'PENDING', :qrcode, :gateway_transaction_id)
            ");
            
            $stmt->execute([
                ':transactionId' => $externalId,
                ':user_id' => $usuario_id,
                ':nome' => $usuario['nome'],
                ':cpf' => $cpf,
                ':valor' => $amount,
                ':qrcode' => $qrCode,
                ':gateway_transaction_id' => $transactionId,
            ]);

            $_SESSION['transactionId'] = $externalId;
            
            logPaymentError('bullspay', $isTaxaSaque ? 'PIX para taxa de saque gerado com sucesso' : 'PIX gerado com sucesso', [
                'transactionId' => $externalId,
                'gateway_transaction_id' => $transactionId,
                'usuario_id' => $usuario_id,
                'amount' => $amount,
                'is_taxa_saque' => $isTaxaSaque
            ]);

            echo json_encode([
                'qrcode' => $qrCode
            ]);
            break;
    }

} catch (Exception $e) {
    logPaymentError($selectedGateway, 'Erro na geração do PIX', [
        'error' => $e->getMessage(),
        'usuario_id' => $usuario_id ?? null,
        'amount' => $amount ?? null,
        'is_taxa_saque' => $isTaxaSaque ?? false
    ]);
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
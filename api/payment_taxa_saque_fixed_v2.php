<?php
// Limpar qualquer output anterior e iniciar buffer
ob_start();

// Verificar se a sessão já está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpar buffer e definir headers
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
        // Ignorar erros de log
    }
}

// Função para retornar JSON e sair
function returnJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

logDebug("=== INÍCIO DA REQUISIÇÃO TAXA SAQUE V2 ===");
logDebug("Método da requisição", $_SERVER['REQUEST_METHOD']);

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("Método não permitido");
    returnJson(['error' => 'Método não permitido'], 405);
}

logDebug("Dados POST recebidos", $_POST);

// Validar dados de entrada
$valor_taxa = isset($_POST['valor_taxa']) ? floatval(str_replace(',', '.', $_POST['valor_taxa'])) : 0;
$cpf_usuario = isset($_POST['cpf_usuario']) ? preg_replace('/\D/', '', $_POST['cpf_usuario']) : '';
$saque_id = isset($_POST['saque_id']) ? intval($_POST['saque_id']) : 0;
$valor_saque = isset($_POST['valor_saque']) ? floatval(str_replace(',', '.', $_POST['valor_saque'])) : 0;
$percentual_taxa = isset($_POST['percentual_taxa']) ? floatval(str_replace(',', '.', $_POST['percentual_taxa'])) : 0;

logDebug("Valores processados", [
    'valor_taxa' => $valor_taxa,
    'cpf_usuario' => $cpf_usuario,
    'saque_id' => $saque_id,
    'valor_saque' => $valor_saque,
    'percentual_taxa' => $percentual_taxa
]);

// Validar taxa mínima
$taxaMinima = 5.00;
if ($valor_taxa < $taxaMinima) {
    logDebug("Taxa menor que mínima", ['taxa' => $valor_taxa, 'minima' => $taxaMinima]);
    returnJson(['error' => 'Taxa mínima é de R$ ' . number_format($taxaMinima, 2, ',', '.')], 400);
}

if ($valor_taxa <= 0 || strlen($cpf_usuario) !== 11) {
    logDebug("Dados inválidos", ['valor_taxa' => $valor_taxa, 'cpf_length' => strlen($cpf_usuario)]);
    returnJson(['error' => 'Dados inválidos'], 400);
}

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    logDebug("Usuário não autenticado");
    returnJson(['error' => 'Usuário não autenticado'], 401);
}

try {
    logDebug("Preparando dados para chamar payment.php");
    
    // Preparar dados para o payment.php
    $paymentData = [
        'valor_taxa' => $valor_taxa,
        'cpf_usuario' => $cpf_usuario,
        'taxa_saque' => 'true' // Identificador para taxa de saque
    ];
    
    logDebug("Dados preparados para payment.php", $paymentData);
    
    // Salvar dados originais do POST
    $originalPost = $_POST;
    
    // Substituir $_POST com os dados para payment.php
    $_POST = $paymentData;
    
    logDebug("Chamando payment.php via include");
    
    // Capturar output do payment.php
    ob_start();
    
    // Incluir o payment.php
    include __DIR__ . '/payment.php';
    
    // Capturar a resposta
    $paymentResponse = ob_get_contents();
    ob_end_clean();
    
    logDebug("Resposta do payment.php", [
        'response' => $paymentResponse,
        'length' => strlen($paymentResponse)
    ]);
    
    // Restaurar $_POST original
    $_POST = $originalPost;
    
    // Decodificar resposta JSON
    $responseData = json_decode($paymentResponse, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro ao decodificar resposta do payment.php: ' . json_last_error_msg());
    }
    
    logDebug("Resposta decodificada", $responseData);
    
    // Verificar se houve erro
    if (isset($responseData['error'])) {
        throw new Exception($responseData['error']);
    }
    
    // Verificar se tem QR code
    if (!isset($responseData['qrcode'])) {
        throw new Exception('QR Code não encontrado na resposta');
    }
    
    // Buscar transaction_id da sessão (definido pelo payment.php)
    $transaction_id = isset($_SESSION['transactionId']) ? $_SESSION['transactionId'] : null;
    
    if (!$transaction_id) {
        throw new Exception('Transaction ID não encontrado');
    }
    
    logDebug("Transaction ID obtido", $transaction_id);
    
    // Incluir conexão para salvar dados específicos da taxa de saque
    require_once __DIR__ . '/../conexao.php';
    
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar se a tabela taxas_saque existe, se não, criar
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'taxas_saque'");
        if ($stmt->rowCount() == 0) {
            logDebug("Criando tabela taxas_saque");
            $pdo->exec("
                CREATE TABLE taxas_saque (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    saque_id INT DEFAULT NULL,
                    valor_saque DECIMAL(10,2) DEFAULT 0.00,
                    valor_taxa DECIMAL(10,2) NOT NULL,
                    percentual_taxa DECIMAL(5,2) DEFAULT 0.00,
                    transaction_id VARCHAR(255) NOT NULL,
                    status ENUM('PENDING', 'PAID', 'CANCELLED') DEFAULT 'PENDING',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_transaction (transaction_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    } catch (Exception $e) {
        logDebug("Erro ao verificar/criar tabela taxas_saque", ['error' => $e->getMessage()]);
    }
    
    // Salvar dados específicos da taxa de saque na tabela taxas_saque
    $stmt = $pdo->prepare("
        INSERT INTO taxas_saque (user_id, saque_id, valor_saque, valor_taxa, percentual_taxa, transaction_id, status, created_at)
        VALUES (:user_id, :saque_id, :valor_saque, :valor_taxa, :percentual_taxa, :transaction_id, 'PENDING', NOW())
        ON DUPLICATE KEY UPDATE
        user_id = VALUES(user_id),
        saque_id = VALUES(saque_id),
        valor_saque = VALUES(valor_saque),
        valor_taxa = VALUES(valor_taxa),
        percentual_taxa = VALUES(percentual_taxa),
        status = VALUES(status)
    ");
    
    $stmt->execute([
        ':user_id' => $usuario_id,
        ':saque_id' => $saque_id,
        ':valor_saque' => $valor_saque,
        ':valor_taxa' => $valor_taxa,
        ':percentual_taxa' => $percentual_taxa,
        ':transaction_id' => $transaction_id
    ]);
    
    logDebug("Dados da taxa de saque salvos", [
        'user_id' => $usuario_id,
        'saque_id' => $saque_id,
        'transaction_id' => $transaction_id
    ]);
    
    // Retornar sucesso com formato esperado
    $result = [
        'success' => true,
        'qrcode' => $responseData['qrcode'],
        'transaction_id' => $transaction_id
    ];
    
    logDebug("Sucesso! Retornando resultado", $result);
    logDebug("=== FIM DA REQUISIÇÃO TAXA SAQUE V2 ===");
    
    returnJson($result);

} catch (Exception $e) {
    logDebug("Erro capturado", [
        'message' => $e->getMessage(), 
        'trace' => $e->getTraceAsString()
    ]);
    logDebug("=== FIM DA REQUISIÇÃO COM ERRO ===");
    returnJson(['error' => $e->getMessage()], 500);
}
?>
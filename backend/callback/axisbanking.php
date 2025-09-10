<?php
// AxisBanking callback webhook handler
require_once __DIR__ . '/../conexao.php';

// Set up logging
$logfile = __DIR__ . '/axisbanking_webhook.log';

// Log all request information for debugging
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? 'Unknown';
$requestHeaders = getallheaders();
$headersLog = json_encode($requestHeaders);

// Log basic request info
file_put_contents($logfile, date('Y-m-d H:i:s') . " - Webhook received - Method: $requestMethod, Content-Type: $contentType" . PHP_EOL, FILE_APPEND);
file_put_contents($logfile, date('Y-m-d H:i:s') . " - Headers: $headersLog" . PHP_EOL, FILE_APPEND);

// Try to get the raw POST data in multiple ways
$rawPostData = file_get_contents('php://input');
$phpInput = !empty($rawPostData) ? $rawPostData : 'EMPTY';
file_put_contents($logfile, date('Y-m-d H:i:s') . " - Raw POST data (php://input): $phpInput" . PHP_EOL, FILE_APPEND);

// Check if data is in $_POST
if (!empty($_POST)) {
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - POST data: " . json_encode($_POST) . PHP_EOL, FILE_APPEND);
}

// Check if data is in $_GET
if (!empty($_GET)) {
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - GET data: " . json_encode($_GET) . PHP_EOL, FILE_APPEND);
}

// Try to decode the input
$data = json_decode($rawPostData, true);

// If JSON failed, try to get data from $_POST or $_REQUEST
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - Using POST data instead of JSON" . PHP_EOL, FILE_APPEND);
} elseif (empty($data) && !empty($_REQUEST)) {
    $data = $_REQUEST;
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - Using REQUEST data instead of JSON" . PHP_EOL, FILE_APPEND);
}

// Log the parsed data
if (!empty($data)) {
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - Parsed data: " . json_encode($data) . PHP_EOL, FILE_APPEND);
} else {
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - No data could be parsed from the request" . PHP_EOL, FILE_APPEND);
    
    // Return 200 OK anyway to prevent repeated webhook attempts
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Check if this is a valid transaction webhook
if (!isset($data['transactionId'], $data['status'], $data['type']) || $data['type'] !== 'TRANSACTION') {
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - Invalid webhook data structure" . PHP_EOL, FILE_APPEND);
    
    // Check if this might be a test webhook
    if (isset($data['test']) || (isset($data['event']) && $data['event'] === 'test')) {
        file_put_contents($logfile, date('Y-m-d H:i:s') . " - Received test webhook" . PHP_EOL, FILE_APPEND);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Test webhook received']);
        exit;
    }
    
    http_response_code(400);
    exit('Invalid webhook data');
}

$transactionId = $data['transactionId'];
$status = $data['status'];
$amount = isset($data['amount']) ? $data['amount'] / 100 : 0; // Convert cents to reais
$payerDocument = isset($data['payerDocument']) ? $data['payerDocument'] : '';
$payerFullName = isset($data['payerFullName']) ? $data['payerFullName'] : '';

// Log decoded data
file_put_contents($logfile, date('Y-m-d H:i:s') . " - Processing transaction: {$transactionId}, Status: {$status}" . PHP_EOL, FILE_APPEND);

// Process the transaction based on status
try {
    // Map AxisBanking status to our database status
    $databaseStatus = 'PENDING'; // Default status
    switch ($status) {
        case 'APPROVED':
            $databaseStatus = 'PAID';
            break;
        case 'REJECTED':
            $databaseStatus = 'REJECTED';
            break;
        case 'BLOCKED':
            $databaseStatus = 'BLOCKED';
            break;
        case 'REFUNDED':
        case 'REFUNDED_PROCESSING':
            $databaseStatus = 'REFUNDED';
            break;
        case 'CHARGEBACK':
            $databaseStatus = 'CHARGEBACK';
            break;
        case 'PENDING':
            $databaseStatus = 'PENDING';
            break;
    }

    // Check if the transaction exists in the database
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM depositos WHERE transactionId = :transactionId");
    $checkStmt->bindParam(':transactionId', $transactionId, PDO::PARAM_STR);
    $checkStmt->execute();
    $transactionExists = $checkStmt->fetchColumn() > 0;
    
    if (!$transactionExists) {
        file_put_contents($logfile, date('Y-m-d H:i:s') . " - Transaction {$transactionId} not found in database" . PHP_EOL, FILE_APPEND);
        http_response_code(200); // Return 200 to acknowledge receipt
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Update the transaction status in the database
    $stmt = $pdo->prepare("UPDATE depositos SET status = :status WHERE transactionId = :transactionId");
    $stmt->bindParam(':status', $databaseStatus, PDO::PARAM_STR);
    $stmt->bindParam(':transactionId', $transactionId, PDO::PARAM_STR);
    $result = $stmt->execute();

    file_put_contents($logfile, date('Y-m-d H:i:s') . " - Database update result: " . ($result ? "Success" : "Failed") . PHP_EOL, FILE_APPEND);

    // For approved transactions, update user balance
    if ($status === 'APPROVED') {
        // Get user information from the deposit
        $stmt = $pdo->prepare("SELECT user_id, valor FROM depositos WHERE transactionId = :transactionId LIMIT 1");
        $stmt->bindParam(':transactionId', $transactionId, PDO::PARAM_STR);
        $stmt->execute();
        $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($deposit) {
            // Update user balance
            $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :valor WHERE id = :user_id");
            $stmt->bindParam(':valor', $deposit['valor'], PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $deposit['user_id'], PDO::PARAM_INT);
            $balanceResult = $stmt->execute();
            
            file_put_contents($logfile, date('Y-m-d H:i:s') . " - Balance update for user {$deposit['user_id']}: " . ($balanceResult ? "Success" : "Failed") . ", Amount: {$deposit['valor']}" . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents($logfile, date('Y-m-d H:i:s') . " - Could not find deposit for transaction {$transactionId}" . PHP_EOL, FILE_APPEND);
        }
    }
    
    // Check if there's an infraction
    if (isset($data['infraction'])) {
        $infraction = $data['infraction'];
        $infractionData = json_encode($infraction);
        
        // Log infraction details
        file_put_contents($logfile, date('Y-m-d H:i:s') . " - Transaction {$transactionId} has infraction: {$infractionData}" . PHP_EOL, FILE_APPEND);
    }
    
    // Always acknowledge receipt of webhook
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processed successfully']);
    
} catch (Exception $e) {
    // Log any errors
    $errorData = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . " at line " . $e->getLine() . PHP_EOL;
    $errorData .= "Trace: " . $e->getTraceAsString() . PHP_EOL;
    file_put_contents($logfile, $errorData, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error processing webhook: ' . $e->getMessage()]);
} 
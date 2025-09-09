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

if (empty($data['amount']) || empty($data['cpf'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$amount = (float) $data['amount'];
$cpf = preg_replace('/[^0-9]/', '', $data['cpf']);

if (strlen($cpf) !== 11) {
    echo json_encode(['success' => false, 'message' => 'CPF inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = :id FOR UPDATE");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }

    if ($usuario['saldo'] < $amount) {
        throw new Exception('Saldo insuficiente para realizar o saque');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM saques WHERE user_id = :user_id AND status = 'PENDING'");
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $hasPending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

    if ($hasPending > 0) {
        throw new Exception('Você já possui um saque pendente. Aguarde a conclusão para solicitar outro.');
    }

    $nome = "Nome não encontrado"; 
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api-cpf-gratis.p.rapidapi.com/?cpf=" . $cpf,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: api-cpf-gratis.p.rapidapi.com",
            "x-rapidapi-key: e5c1fd4e13msh008c726672c9a43p1218d5jsn9a8b01aa6822"
        ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if (!$err) {
        $apiData = json_decode($response, true);
        if ($apiData['code'] == 200 && !empty($apiData['data']['nome'])) {
            $nome = $apiData['data']['nome'];
        }
    }

    $newBalance = $usuario['saldo'] - $amount;
    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = :saldo WHERE id = :id");
    $stmt->bindParam(':saldo', $newBalance);
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    $transactionId = uniqid('WTH_');
    $stmt = $pdo->prepare("INSERT INTO saques (transactionId, user_id, nome, cpf, valor, status) 
                           VALUES (:transactionId, :user_id, :nome, :cpf, :valor, 'PENDING')");
    $stmt->bindParam(':transactionId', $transactionId);
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':valor', $amount);
    $stmt->execute();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Saque solicitado com sucesso!'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
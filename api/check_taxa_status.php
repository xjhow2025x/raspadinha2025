<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$transaction_id = $_POST['transaction_id'] ?? '';

if (empty($transaction_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID é obrigatório']);
    exit;
}

require_once __DIR__ . '/../conexao.php';

try {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não autenticado.');
    }

    $usuario_id = $_SESSION['usuario_id'];

    // Verificar status da taxa
    $stmt = $pdo->prepare("SELECT * FROM taxas_saque WHERE user_id = ? AND transaction_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$usuario_id, $transaction_id]);
    $taxa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$taxa) {
        throw new Exception('Taxa não encontrada.');
    }

    echo json_encode([
        'success' => true,
        'status' => $taxa['status'],
        'valor_saque' => $taxa['valor_saque'],
        'valor_taxa' => $taxa['valor_taxa'],
        'percentual_taxa' => $taxa['percentual_taxa']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
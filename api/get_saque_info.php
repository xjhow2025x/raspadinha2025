<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

require_once __DIR__ . '/../conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    // Buscar configurações de saque
    $stmt = $pdo->prepare("SELECT saque_min, saque_max_diario FROM config LIMIT 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $saqueMin = $config['saque_min'] ?? 50;
    $saqueMaxDiario = $config['saque_max_diario'] ?? 2000;

    // Verificar quanto já foi sacado hoje
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total_sacado_hoje 
        FROM saques 
        WHERE user_id = :user_id 
        AND DATE(created_at) = :hoje 
        AND status IN ('PENDING', 'PAID')
    ");
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();
    $totalSacadoHoje = $stmt->fetch(PDO::FETCH_ASSOC)['total_sacado_hoje'];

    $limiteRestante = $saqueMaxDiario - $totalSacadoHoje;

    echo json_encode([
        'success' => true,
        'saque_min' => $saqueMin,
        'saque_max_diario' => $saqueMaxDiario,
        'total_sacado_hoje' => $totalSacadoHoje,
        'limite_restante_hoje' => max(0, $limiteRestante),
        'pode_sacar' => $limiteRestante > 0
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

require_once __DIR__ . '/../conexao.php';

try {
    // Buscar configurações
    $stmt = $pdo->query("SELECT * FROM config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        throw new Exception('Configurações não encontradas.');
    }
    
    echo json_encode([
        'success' => true,
        'taxa_saque' => floatval($config['taxa_saque'] ?? 1),
        'saque_min' => floatval($config['saque_min'] ?? 50),
        'saque_max_diario' => floatval($config['saque_max_diario'] ?? 2000),
        'deposito_min' => floatval($config['deposito_min'] ?? 5),
        'nome_site' => $config['nome_site'] ?? '',
        'cpa_padrao' => floatval($config['cpa_padrao'] ?? 0)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
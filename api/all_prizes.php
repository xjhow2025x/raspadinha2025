<?php
require_once '../conexao.php';
header('Content-Type: application/json');

try {
    // Buscar todos os prêmios com valor > 0 (excluindo "Nada" e similares)
    $stmt = $pdo->prepare("SELECT id, nome, icone, valor FROM raspadinha_premios WHERE valor > 0 ORDER BY valor DESC");
    $stmt->execute();
    $premios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($premios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar prêmios: ' . $e->getMessage()]);
}
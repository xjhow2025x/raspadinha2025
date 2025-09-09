<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Inclui o arquivo de conexão
if (file_exists('../conexao.php')) {
    include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
    include('../../conexao.php');
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco de dados']);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Lê os dados JSON da requisição
$input = json_decode(file_get_contents('php://input'), true);

// Valida os dados
if (!isset($input['story_id']) || !is_numeric($input['story_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do story inválido']);
    exit;
}

$story_id = (int)$input['story_id'];

try {
    // Atualiza o contador de visualizações
    $sql = "UPDATE stories SET visualizacoes = visualizacoes + 1 WHERE id = ? AND ativo = 1";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$story_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Busca o número atual de visualizações
        $sql_select = "SELECT visualizacoes FROM stories WHERE id = ?";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute([$story_id]);
        $views = $stmt_select->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Visualização registrada com sucesso',
            'story_id' => $story_id,
            'total_views' => $views
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Story não encontrado']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ]);
}
?>
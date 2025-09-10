<?php
@session_start();
require_once '../conexao.php';
header('Content-Type: application/json');

$userId  = $_SESSION['usuario_id'] ?? 0;
$orderId = (int)($_POST['order_id'] ?? 0);

if (!$userId || !$orderId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Dados inválidos']));
}

$stmt = $pdo->prepare("
    SELECT o.*, r.valor AS custo_raspadinha
      FROM orders o
      JOIN raspadinhas r ON r.id = o.raspadinha_id
     WHERE o.id = ? AND o.user_id = ?
     LIMIT 1
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || $order['status'] == 1) {
    http_response_code(400);
    exit(json_encode(['error' => 'Ordem inválida']));
}


$gridIds  = json_decode($order['premios_json'], true);
$contagem = array_count_values($gridIds);

$premioId   = null;
$valorPremio = 0.00;
$resultado  = 'loss';

foreach ($contagem as $id => $qtd) {
    if ($qtd === 3) {
        $p = $pdo->prepare("SELECT valor FROM raspadinha_premios WHERE id = ?");
        $p->execute([$id]);
        $valorEncontrado = (float)$p->fetchColumn();

        if ($valorEncontrado > 0) {
            $premioId    = $id;
            $valorPremio = $valorEncontrado;
            $resultado   = 'gain';
            break;
        }
    }
}


if ($resultado === 'gain') {
    $valorTotalACreditar = $valorPremio + (float)$order['custo_raspadinha'];

    $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?")
        ->execute([$valorTotalACreditar, $userId]);
}


$pdo->prepare("
    UPDATE orders
       SET status       = 1,
           resultado     = ?,
           valor_ganho   = ?,
           updated_at    = NOW()
     WHERE id = ?
")->execute([$resultado, $valorPremio, $orderId]);


echo json_encode([
    'success'   => true,
    'resultado' => $resultado,
    'valor'     => $valorPremio 
]);
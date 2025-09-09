<?php
@session_start();

if (file_exists('./conexao.php')) {
    include('./conexao.php');
} elseif (file_exists('../conexao.php')) {
    include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
    include('../../conexao.php');
}

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você precisa estar logado para acessar esta página!'];
    header("Location: /login");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$porPagina = 10; // apostas por página
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($paginaAtual - 1) * $porPagina;

// Obter total de apostas
try {
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
    $stmtTotal->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmtTotal->execute();
    $totalApostas = $stmtTotal->fetchColumn();
    $totalPaginas = ceil($totalApostas / $porPagina);
} catch (PDOException $e) {
    $totalApostas = 0;
    $totalPaginas = 1;
}

// Buscar apostas paginadas
try {
    $stmt = $pdo->prepare("
        SELECT o.created_at, o.resultado, o.valor_ganho, r.nome, r.valor AS valor_apostado
        FROM orders o
        JOIN raspadinhas r ON o.raspadinha_id = r.id
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $porPagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $apostas = [];
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar apostas'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $nomeSite;?></title>
  <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>"/>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
</head>
<body>

<?php include('../inc/header.php'); ?>

<section class="w-full py-6 relative">
  <div class="max-w-[850px] mx-auto px-4 overflow-hidden">
    
  <div class="bg-[var(--bg-color)] text-white rounded-lg shadow-lg p-6">
      <h2 class="text-2xl font-semibold mb-6 text-center">Minhas Apostas</h2>

      <?php if (empty($apostas)): ?>
        <div class="text-center text-[var(--support-color)]">
          <i class="fas fa-ticket-alt text-4xl mb-4"></i>
          <p>Nenhuma aposta encontrada</p>
        </div>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($apostas as $aposta): ?>
            <?php
              $data = date('d/m/Y H:i', strtotime($aposta['created_at']));
              $status = ($aposta['resultado'] === 'gain') ? 'GANHOU' : 'PERDEU';
              $corStatus = ($status === 'GANHOU') ? 'text-green-400' : 'text-red-400';
              $valorGanho = number_format($aposta['valor_ganho'], 2, ',', '.');
              $valorApostado = number_format($aposta['valor_apostado'], 2, ',', '.');
            ?>
            <div class="bg-[var(--dark-color)] p-4 rounded-lg shadow hover:bg-[var(--darker-color)] transition">
              <div class="flex justify-between items-center mb-1">
                <div class="text-sm text-[var(--support-color)]">
                  <i class="fas fa-calendar-alt mr-1"></i> <?= $data ?>
                </div>
                <div class="text-sm font-bold <?= $corStatus ?>">
                  <?= $status ?>
                </div>
              </div>
              <div class="mt-1 text-sm">
                <span class="text-[var(--support-color)]">Raspadinha:</span> <?= htmlspecialchars($aposta['nome']) ?><br>
                <span class="text-[var(--support-color)]">Valor Apostado:</span> R$ <?= $valorApostado ?><br>
                <span class="text-[var(--support-color)]">Valor Ganhado:</span> R$ <?= $valorGanho ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPaginas > 1): ?>
          <div class="flex justify-center mt-6 space-x-2">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
              <a 
                href="?pagina=<?= $i ?>"
                class="px-3 py-1 rounded-md text-sm font-medium 
                       <?= $i == $paginaAtual ? 'bg-[var(--primary-color)] text-white' : 'bg-[var(--dark-color)] text-[var(--support-color)] hover:bg-[var(--darker-color)]' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
        
      <?php endif; ?>

    </div>

  </div>
</section>

<?php include('../inc/footer.php'); ?>

<script>
document.getElementById('tabDepositos')?.addEventListener('click', function() {
  document.getElementById('depositosContent').classList.remove('hidden');
  document.getElementById('saquesContent').classList.add('hidden');
  this.classList.add('active');
  document.getElementById('tabSaques').classList.remove('active');
});

document.getElementById('tabSaques')?.addEventListener('click', function() {
  document.getElementById('saquesContent').classList.remove('hidden');
  document.getElementById('depositosContent').classList.add('hidden');
  this.classList.add('active');
  document.getElementById('tabDepositos').classList.remove('active');
});

document.querySelectorAll('.cpf-masked').forEach(el => {
  el.addEventListener('click', function() {
    const fullCpf = this.getAttribute('data-full') || this.textContent;
    this.textContent = fullCpf;
  });
});
</script>

<style>
.tab-button {
  position: relative;
  color: var(--support-color);
  transition: all 0.3s ease;
}
.tab-button.active {
  color: var(--primary-color);
}
.tab-button.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 100%;
  height: 2px;
  background-color: var(--primary-color);
}
.transaction-item {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.status-badge {
  display: inline-block;
  min-width: 80px;
  text-align: center;
}
.transaction-card {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.cpf-masked {
  word-break: break-all;
}
@media (max-width: 768px) {
  .transaction-card {
    padding: 1rem;
  }
  .transaction-card > div {
    padding: 0.25rem 0;
  }
}
@media (max-width: 768px) {
  .transaction-item > div {
    padding: 0.25rem 0;
  }
}
</style>
</body>
</html>
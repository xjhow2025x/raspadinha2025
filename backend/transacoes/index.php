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

try {
    $stmt_depositos = $pdo->prepare("SELECT 
                                    created_at, 
                                    updated_at, 
                                    cpf, 
                                    valor, 
                                    status 
                                    FROM depositos 
                                    WHERE user_id = :user_id
                                    ORDER BY created_at DESC");
    $stmt_depositos->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_depositos->execute();
    $depositos = $stmt_depositos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $depositos = [];
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar depósitos'];
}

try {
    $stmt_saques = $pdo->prepare("SELECT 
                                created_at, 
                                updated_at, 
                                cpf, 
                                valor, 
                                status 
                                FROM saques 
                                WHERE user_id = :user_id
                                ORDER BY created_at DESC");
    $stmt_saques->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_saques->execute();
    $saques = $stmt_saques->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $saques = [];
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar saques'];
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
  <div class="max-w-[850px] mx-auto px-4">
    <div class="relative bg-[var(--bg-color)] text-white py-8 px-4 rounded-lg shadow-lg mt-8 shadow-rox">

      <div class="absolute -top-8 left-1/2 transform -translate-x-1/2">
        <img src="/assets/img/credit-icon.svg" alt="Ícone de histórico" class="w-16 h-16">
      </div>

      <h2 class="text-2xl text-center font-semibold mb-6">Minhas Transações</h2>

      <div class="flex border-b border-[var(--support-color)] mb-6">
        <button id="tabDepositos" class="tab-button active px-4 py-2  text-lg font-medium cursor-pointer">Depósitos</button>
        <button id="tabSaques" class="tab-button px-4 py-2 text-lg font-medium cursor-pointer">Saques</button>
      </div>

      <div id="depositosContent" class="transactions-content w-full">
  <?php if (empty($depositos)): ?>
    <div class="text-center py-8 text-[var(--support-color)] w-full">
      <i class="fas fa-wallet text-4xl mb-4"></i>
      <p>Nenhum depósito encontrado</p>
    </div>
  <?php else: ?>
    <div class="hidden md:block">

      <div class="w-full flex justify-between items-center mb-4 px-4 text-[var(--support-color)] text-sm font-medium">
        <div class="w-4/12">Data/Hora</div>
        <div class="w-3/12">CPF</div>
        <div class="w-3/12">Valor</div>
        <div class="w-2/12 text-right">Status</div>
      </div>
      
      <?php foreach ($depositos as $deposito): ?>
        <div class="transaction-item w-full flex justify-between items-center p-4 rounded-lg mb-3 bg-[var(--dark-color)] hover:bg-[var(--darker-color)] transition">
          <div class="w-4/12 flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-[var(--support-color)]"></i>
            <span><?= date('d/m/Y H:i', strtotime($deposito['updated_at'])) ?></span>
          </div>
          <div class="w-3/12">
            <span class="cpf-masked"><?= substr($deposito['cpf'], 0, 3) ?>.***.***-**</span>
          </div>
          <div class="w-3/12 font-medium">
            R$ <?= number_format($deposito['valor'], 2, ',', '.') ?>
          </div>
          <div class="w-2/12 text-right">
            <span class="status-badge <?= $deposito['status'] === 'PAID' ? 'bg-green-500' : 'bg-yellow-500' ?> px-2 py-1 rounded-full text-xs">
              <?= $deposito['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="md:hidden space-y-3">
      <?php foreach ($depositos as $deposito): ?>
        <div class="transaction-card bg-[var(--dark-color)] rounded-lg p-4 hover:bg-[var(--darker-color)] transition">

          <div class="flex justify-between items-center mb-2">
            <div class="flex items-center">
              <i class="fas fa-calendar-alt mr-2 text-[var(--support-color)]"></i>
              <span><?= date('d/m/Y H:i', strtotime($deposito['updated_at'])) ?></span>
            </div>
            <div class="font-medium">
              R$ <?= number_format($deposito['valor'], 2, ',', '.') ?>
            </div>
          </div>
          
          <div class="flex justify-between items-center">
            <div class="text-sm text-[var(--support-color)]">
              <span class="cpf-masked"><?= $deposito['cpf'] ?></span>
            </div>
            <div>
              <span class="status-badge <?= $deposito['status'] === 'PAID' ? 'bg-green-500' : 'bg-yellow-500' ?> px-2 py-1 rounded-full text-xs">
                <?= $deposito['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div id="saquesContent" class="transactions-content hidden w-full">
  <?php if (empty($saques)): ?>
    <div class="text-center py-8 text-[var(--support-color)] w-full">
      <i class="fas fa-money-bill-wave text-4xl mb-4"></i>
      <p>Nenhum saque encontrado</p>
    </div>
  <?php else: ?>
    <div class="hidden md:block">

      <div class="w-full flex justify-between items-center mb-4 px-4 text-[var(--support-color)] text-sm font-medium">
        <div class="w-4/12">Data/Hora</div>
        <div class="w-3/12">CPF</div>
        <div class="w-3/12">Valor</div>
        <div class="w-2/12 text-right">Status</div>
      </div>
      
      <?php foreach ($saques as $saque): ?>
        <div class="transaction-item w-full flex justify-between items-center p-4 rounded-lg mb-3 bg-[var(--dark-color)] hover:bg-[var(--darker-color)] transition">
          <div class="w-4/12 flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-[var(--support-color)]"></i>
            <span><?= date('d/m/Y H:i', strtotime($saque['updated_at'])) ?></span>
          </div>
          <div class="w-3/12">
            <span class="cpf-masked"><?= substr($saque['cpf'], 0, 3) ?>.***.***-**</span>
          </div>
          <div class="w-3/12 font-medium">
            R$ <?= number_format($saque['valor'], 2, ',', '.') ?>
          </div>
          <div class="w-2/12 text-right">
            <span class="status-badge <?= $saque['status'] === 'PAID' ? 'bg-green-500' : 'bg-yellow-500' ?> px-2 py-1 rounded-full text-xs">
              <?= $saque['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="md:hidden space-y-3">
      <?php foreach ($saques as $saque): ?>
        <div class="transaction-card bg-[var(--dark-color)] rounded-lg p-4 hover:bg-[var(--darker-color)] transition">
 
          <div class="flex justify-between items-center mb-2">
            <div class="flex items-center">
              <i class="fas fa-calendar-alt mr-2 text-[var(--support-color)]"></i>
              <span><?= date('d/m/Y H:i', strtotime($saque['updated_at'])) ?></span>
            </div>
            <div class="font-medium">
              R$ <?= number_format($saque['valor'], 2, ',', '.') ?>
            </div>
          </div>
          
          <div class="flex justify-between items-center">
            <div class="text-sm text-[var(--support-color)]">
              <span class="cpf-masked"><?= $saque['cpf'] ?></span>
            </div>
            <div>
              <span class="status-badge <?= $saque['status'] === 'PAID' ? 'bg-green-500' : 'bg-yellow-500' ?> px-2 py-1 rounded-full text-xs">
                <?= $saque['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
    </div>
  </div>
</section>

<?php include('../inc/footer.php'); ?>

<script>
document.getElementById('tabDepositos').addEventListener('click', function() {
  document.getElementById('depositosContent').classList.remove('hidden');
  document.getElementById('saquesContent').classList.add('hidden');
  this.classList.add('active');
  document.getElementById('tabSaques').classList.remove('active');
});

document.getElementById('tabSaques').addEventListener('click', function() {
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

.status-badge {
  display: inline-block;
  min-width: 80px;
  text-align: center;
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
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
    $link_indicacao = "https://" . $_SERVER['HTTP_HOST'] . "/cadastro?ref=" . $usuario_id;
    
    $stmt_indicados = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE indicacao = ?");
    $stmt_indicados->execute([$usuario_id]);
    $total_indicados = $stmt_indicados->fetch()['total'];
    
    $stmt_depositos = $pdo->prepare("SELECT SUM(d.valor) as total 
                                    FROM depositos d
                                    JOIN usuarios u ON d.user_id = u.id
                                    WHERE u.indicacao = ? AND d.status = 'PAID'");
    $stmt_depositos->execute([$usuario_id]);
    $total_depositado = $stmt_depositos->fetch()['total'] ?? 0;
    
    $stmt_comissoes = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes_afiliados WHERE afiliado_id = ?");
    $stmt_comissoes->execute([$usuario_id]);
    $total_comissoes = $stmt_comissoes->fetch()['total'] ?? 0;
    
    $stmt_lista = $pdo->prepare("SELECT u.id, u.nome, u.email, u.created_at,
                                (SELECT SUM(valor) FROM depositos WHERE user_id = u.id AND status = 'PAID') as total_depositado
                                FROM usuarios u
                                WHERE u.indicacao = ?
                                ORDER BY u.created_at DESC");
    $stmt_lista->execute([$usuario_id]);
    $indicados = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar dados de afiliado'];
    $total_indicados = 0;
    $total_depositado = 0;
    $total_comissoes = 0;
    $indicados = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $nomeSite; ?></title>
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
        <img src="/assets/img/credit-icon.svg" alt="Ícone de afiliado" class="w-16 h-16">
      </div>

      <h2 class="text-2xl text-center font-semibold mb-6">Área do Afiliado</h2>

      <div class="mb-8">
          <h3 class="text-lg font-medium mb-2">Seu Link de Indicação:</h3>
          <div class="flex flex-col md:flex-row gap-2 w-full">
              <div class="relative rounded-lg border border-[var(--support-color)] w-full md:w-[85%]">
                  <i class="fa fa-link absolute top-3 left-3 text-[var(--support-color)]"></i>
                  <input type="text" id="linkIndicacao" value="<?= $link_indicacao ?>" readonly
                        class="pl-10 pr-4 py-2 w-full bg-transparent text-white text-[16px] rounded-lg focus:outline-none"
                        placeholder="Seu link de indicação">
              </div>
              
              <button onclick="copiarLink()" 
                      class="w-full md:w-auto bg-[var(--primary-color)] hover:bg-[var(--primary-dark)] text-white py-2 px-4 rounded-lg transition-colors duration-200">
                  <i class="fas fa-copy mr-2"></i>Copiar
              </button>
          </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          
          <div class="bg-[var(--dark-color)] p-6 rounded-lg border-l-4 border-[#00de93] shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div class="flex items-center justify-between">
                  <div>
                      <h3 class="text-[var(--support-color)] text-sm font-medium uppercase tracking-wider">Indicados</h3>
                      <p class="text-3xl font-bold mt-2"><?= $total_indicados ?></p>
                  </div>
                  <div class="bg-[#00de93]/20 p-3 rounded-full">
                      <i class="fas fa-users text-[#00de93] text-xl"></i>
                  </div>
              </div>
              <p class="text-xs text-[var(--support-color)] mt-4">Pessoas que você indicou</p>
          </div>

          <div class="bg-[var(--dark-color)] p-6 rounded-lg border-l-4 border-[#3b82f6] shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div class="flex items-center justify-between">
                  <div>
                      <h3 class="text-[var(--support-color)] text-sm font-medium uppercase tracking-wider">Total Depositado</h3>
                      <p class="text-3xl font-bold mt-2">R$ <?= number_format($total_depositado, 2, ',', '.') ?></p>
                  </div>
                  <div class="bg-[#3b82f6]/20 p-3 rounded-full">
                      <i class="fas fa-money-bill-wave text-[#3b82f6] text-xl"></i>
                  </div>
              </div>
              <p class="text-xs text-[var(--support-color)] mt-4">Por seus indicados</p>
          </div>

          <div class="bg-[var(--dark-color)] p-6 rounded-lg border-l-4 border-[#a855f7] shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div class="flex items-center justify-between">
                  <div>
                      <h3 class="text-[var(--support-color)] text-sm font-medium uppercase tracking-wider">Comissões</h3>
                      <p class="text-3xl font-bold mt-2">R$ <?= number_format($total_comissoes, 2, ',', '.') ?></p>
                  </div>
                  <div class="bg-[#a855f7]/20 p-3 rounded-full">
                      <i class="fas fa-hand-holding-usd text-[#a855f7] text-xl"></i>
                  </div>
              </div>
              <p class="text-xs text-[var(--support-color)] mt-4">Total recebido</p>
          </div>
      </div>

      <h3 class="text-lg font-medium mb-4">Seus Indicados</h3>
      <?php if (empty($indicados)): ?>
    <div class="text-center py-8 text-[var(--support-color)]">
        <i class="fas fa-users text-4xl mb-4"></i>
        <p>Você ainda não tem nenhum indicado</p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($indicados as $indicado): ?>
            <div class="bg-[var(--dark-color)] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow duration-300 border-l-4 border-[var(--primary-color)]">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
               
                    <div class="flex flex-col">
                        <span class="text-sm text-[var(--support-color)]">Nome</span>
                        <span class="font-medium"><?= htmlspecialchars($indicado['nome']) ?></span>
                    </div>
                    
                    <div class="flex flex-col">
                        <span class="text-sm text-[var(--support-color)]">Email</span>
                        <span class="font-medium truncate"><?= htmlspecialchars($indicado['email']) ?></span>
                    </div>
                    
                    <div class="flex flex-col">
                        <span class="text-sm text-[var(--support-color)]">Cadastrado em</span>
                        <span class="font-medium"><?= date('d/m/Y', strtotime($indicado['created_at'])) ?></span>
                    </div>
                    
                    <div class="flex flex-col">
                        <span class="text-sm text-[var(--support-color)]">Total Depositado</span>
                        <span class="font-medium text-[#00de93]">R$ <?= number_format($indicado['total_depositado'] ?? 0, 2, ',', '.') ?></span>
                    </div>
                </div>
                
              
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
    </div>
  </div>
</section>

<?php include('../inc/footer.php'); ?>

<script>
function copiarLink() {
  const link = document.getElementById('linkIndicacao');
  link.select();
  document.execCommand('copy');
  Notiflix.Notify.success('Link copiado para a área de transferência!');
}
</script>

<style>


table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px 8px;
  text-align: left;
}

tr:last-child {
  border-bottom: none;
}

@media (max-width: 768px) {
  table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }
}
</style>
</body>
</html>
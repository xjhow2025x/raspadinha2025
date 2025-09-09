<?php
@session_start();
require_once '../conexao.php';

/* Buscar cartelas + maior prêmio de cada uma */
$sql = "
    SELECT r.*, 
           MAX(p.valor) AS maior_premio
      FROM raspadinhas r
 LEFT JOIN raspadinha_premios p ON p.raspadinha_id = r.id
  GROUP BY r.id
  ORDER BY r.created_at DESC
";
$cartelas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>$<?php echo $nomeSite; ?></title>
  <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>"/>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
</head>
<body >

<?php include('../inc/header.php'); ?>


<section class="w-full max-w-[1200px] mx-auto py-6 px-2">
  <h1 class="text-3xl font-bold mb-8 text-center text-[var(--bg-color)]">
    Escolha sua Raspadinha
  </h1>

  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($cartelas as $c): ?>
      <a href="/raspadinhas/show.php?id=<?= $c['id']; ?>"
         class="group flex flex-col rounded-xl overflow-hidden shadow-rox
                transition transform hover:-translate-y-2 bg-[var(--bg-color)]">
        
        <!-- Banner ocupa toda a largura e altura definida -->
        <img src="<?= htmlspecialchars($c['banner']); ?>"
             alt="Banner <?= htmlspecialchars($c['nome']); ?>"
             class="w-full h-48 object-cover flex-shrink-0" loading="lazy">

        <!-- Caixa de texto fica embaixo, fora do banner -->
        <div class="p-4 space-y-1">
          <h2 class="text-lg font-semibold text-white">
            <?= htmlspecialchars($c['nome']); ?>
          </h2>
          <p class="text-[var(--support-color)] text-sm">
            <?= htmlspecialchars($c['descricao']); ?>
          </p>
          <p class="text-base text-gray-200">
            Prêmios até 
            <span class="text-emerald-300 font-bold">
              R$<?= number_format($c['maior_premio'], 0, ',', '.'); ?>,00
            </span> no PIX
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php include('../components/ganhos.php'); ?>
<?php include('../inc/footer.php'); ?>
</body>
</html>

<?php
@session_start();
require_once '../conexao.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
$stmt->execute([$id]);
$cartela = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cartela) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Cartela não encontrada.'];
    header("Location: /raspadinhas");
    exit;
}

$premios = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE raspadinha_id = ? ORDER BY valor DESC");
$premios->execute([$id]);
$premios = $premios->fetchAll(PDO::FETCH_ASSOC);
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
  <script src="https://cdn.jsdelivr.net/npm/scratchcard-js@1.5.5/build/scratchcard.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
</head>
<body>

<?php include('../inc/header.php'); ?>

<section class="max-w-4xl mx-auto px-4 py-10 flex flex-col justify-center items-center gap-4">
  <div class="relative rounded-xl overflow-hidden shadow-rox w-full">
    <img src="<?= htmlspecialchars($cartela['banner']); ?>" class="w-full h-60 object-cover" alt="Banner">
    <div class="absolute inset-0 flex items-center justify-center">
      <h1 class="text-2xl sm:text-3xl font-bold text-white text-shadow-black text-shadow-lg">
        <?= htmlspecialchars($cartela['nome']); ?>
      </h1>
    </div>
  </div>

  <div class="w-full lg:w-2/3 mx-auto">
    <div id="scratch-container" class="relative overflow-hidden rounded-lg shadow-xl aspect-square max-w-full mx-auto">
      <canvas id="scratch-card" class="absolute inset-0 w-full h-full z-20 pointer-events-none"></canvas>
      <div id="prizes-grid" class="absolute inset-0 grid grid-cols-3 grid-rows-3 gap-2 p-3 z-10 bg-gray-900"></div>
    </div>

    <div id="result-msg" class="text-center mt-4 text-xl font-semibold"></div>

    <button id="btn-buy" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-lg font-bold mb-3">
      Comprar e Raspar (R$<?= number_format($cartela['valor'],2,',','.'); ?>)
    </button>
  </div>

<script>
const btnBuy = document.getElementById('btn-buy');
const gridEl = document.getElementById('prizes-grid');
const resultEl = document.getElementById('result-msg');
let orderId = null, card = null;

function buildCell(prize) {
  return `
    <div class="bg-gray-800 flex items-center justify-center rounded-md">
      <div class="text-center p-1 flex flex-col items-center">
        <img src="${prize.icone}" class="w-12 h-12 object-contain mb-1">
        <span class="text-white text-xs font-semibold">$${prize.valor > 0 ? 'R$'+prize.valor.toLocaleString('pt-BR',{minimumFractionDigits:2}) : prize.nome}</span>
      </div>
    </div>`;
}

btnBuy.addEventListener('click', async () => {
  btnBuy.disabled = true; resultEl.textContent = 'Gerando...';
  const fd = new FormData();
  fd.append('raspadinha_id', <?= $cartela['id']; ?>);
  const r = await fetch('/raspadinhas/buy.php', {method:'POST', body:fd});
  const json = await r.json();
  if(!json.success){ alert(json.error); btnBuy.disabled=false; return; }

  orderId = json.order_id;
  const premResp = await fetch('/raspadinhas/prizes.php?ids='+json.grid.join(','));
  const premios  = await premResp.json();

  gridEl.innerHTML = premios.map(buildCell).join('');

  card = new ScratchCard('#scratch-container', {
    scratchType: SCRATCH_TYPE.BRUSH,
    containerWidth: 487,
    containerHeight: 487,
    brushSrc: '/assets/img/raspe.png',
    percentToFinish: 90,
    nPoints: 30,
    pointSize: 10,
    callback: finishScratch
  });
  await card.init();
  btnBuy.style.display='none';
});

async function finishScratch(){
  const fd = new FormData();
  fd.append('order_id', orderId);
  const r = await fetch('/raspadinhas/finish.php',{method:'POST', body:fd});
  const j = await r.json();
  if(!j.success){ alert('Erro ao finalizar'); return; }

  if(j.resultado === 'gain'){
    resultEl.innerHTML = `<span class="text-emerald-400">🎉 Você ganhou R$ ${j.valor.toLocaleString('pt-BR',{minimumFractionDigits:2})}!</span>`;
  }else{
    resultEl.innerHTML = `<span class="text-red-400">Não foi dessa vez. 😢</span>`;
  }
  card.clear();
  btnBuy.style.display='block';
  btnBuy.disabled = false;
  btnBuy.textContent = 'Jogar Novamente';
}
</script>
</section>

<?php include('../components/ganhos.php'); ?>
<?php include('../inc/footer.php'); ?>
</body>
</html>
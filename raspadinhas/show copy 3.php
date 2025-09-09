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
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
  <style>
    #scratch-container {
      position: relative;
      width: 350px;
      height: 350px;
      margin: 0 auto;
      user-select: none;
    }
    #prizes-grid {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(3, 1fr);
      gap: 8px;
      padding: 12px;
      background: #222;
      color: white;
      border-radius: 8px;
      z-index: 1;
    }
    #prizes-grid > div {
      background: #333;
      border-radius: 6px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      font-weight: 600;
      font-size: 0.85rem;
    }
    #prizes-grid img {
      width: 48px;
      height: 48px;
      object-fit: contain;
      margin-bottom: 6px;
    }
    #scratch-canvas {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      border-radius: 8px;
      z-index: 10;
      touch-action: none;
      cursor: pointer;
      user-select: none;
    }
    #btn-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.25rem;
      font-weight: bold;
      color: #fff;
      z-index: 30;
      border-radius: 8px;
      text-align: center;
    }
    #btn-buy {
      margin-top: 1rem;
      width: 350px;
      max-width: 100%;
      display: block;
    }
    #result-msg {
      margin-top: 1rem;
      font-weight: 700;
      text-align: center;
      min-height: 1.5em;
    }
  </style>
</head>
<body>

<?php include('../inc/header.php'); ?>

<section class="max-w-[1200px] w-full mx-auto px-4 py-10 flex flex-col justify-center items-center gap-4">
  <div class="relative rounded-xl overflow-hidden shadow-rox w-full max-w-[350px] md:max-w-[487px]">
    <img src="<?= htmlspecialchars($cartela['banner']); ?>" class="w-full  h-32 object-cover" alt="Banner">
    <div class="absolute inset-0 flex items-center justify-center">
      <h1 class="text-2xl sm:text-3xl font-bold text-white text-shadow-black text-shadow-lg">
        <?= htmlspecialchars($cartela['nome']); ?>
      </h1>
    </div>
  </div>

  <div id="scratch-container">
    <div id="prizes-grid"></div>
    <canvas id="scratch-canvas" width="350" height="350"></canvas>
    <div id="btn-overlay">Clique em "Comprar" para jogar</div>
  </div>

  <button id="btn-buy" class="bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-lg font-bold">
    Comprar e Raspar (R$<?= number_format($cartela['valor'], 2, ',', '.'); ?>)
  </button>

  <div id="result-msg"></div>
</section>

<script>
const canvas = document.getElementById('scratch-canvas');
const ctx = canvas.getContext('2d');
const prizesGrid = document.getElementById('prizes-grid');
const btnBuy = document.getElementById('btn-buy');
const resultMsg = document.getElementById('result-msg');
const overlay = document.getElementById('btn-overlay');
const scratchImage = new Image();
scratchImage.src = '/assets/img/raspe.png';

let orderId = null;
let brushRadius = 45;
let isDrawing = false;
let scratchedPercentage = 0;
let isScratchEnabled = false;

function drawScratchImage() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.globalCompositeOperation = 'source-over';
  ctx.drawImage(scratchImage, 0, 0, canvas.width, canvas.height);
}

function scratch(x, y) {
  if (!isScratchEnabled) return;
  ctx.globalCompositeOperation = 'destination-out';
  ctx.beginPath();
  ctx.arc(x, y, brushRadius, 0, Math.PI * 2);
  ctx.fill();
}

function getScratchedPercentage() {
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const pixels = imageData.data;
  let transparentPixels = 0;

  for (let i = 3; i < pixels.length; i += 4) {
    if (pixels[i] === 0) transparentPixels++;
  }
  return (transparentPixels / (canvas.width * canvas.height)) * 100;
}

function getMousePos(e) {
  const rect = canvas.getBoundingClientRect();
  if (e.touches) {
    return {
      x: e.touches[0].clientX - rect.left,
      y: e.touches[0].clientY - rect.top
    };
  } else {
    return {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top
    };
  }
}

function handleStart(e) {
  if (!isScratchEnabled) return;
  isDrawing = true;
  const pos = getMousePos(e);
  scratch(pos.x, pos.y);
}

function handleMove(e) {
  if (!isDrawing || !isScratchEnabled) return;
  const pos = getMousePos(e);
  scratch(pos.x, pos.y);
  scratchedPercentage = getScratchedPercentage();
  if (scratchedPercentage > 50) {
    autoFinishScratch();
  }
}

function handleEnd() {
  isDrawing = false;
}

function buildCell(prize) {
  return `
    <div>
      <img src="${prize.icone}" alt="${prize.nome}" />
      <span>${prize.valor > 0 ? 'R$ ' + prize.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : prize.nome}</span>
    </div>
  `;
}

async function autoFinishScratch() {
  isScratchEnabled = false;
  const fadeInterval = setInterval(() => {
    ctx.globalCompositeOperation = 'destination-out';
    ctx.fillStyle = 'rgba(0,0,0,0.1)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
  }, 50);

  setTimeout(() => {
    clearInterval(fadeInterval);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  }, 600);

  finishScratch();
}

async function finishScratch() {
  resultMsg.textContent = 'Verificando resultado...';
  const fd = new FormData();
  fd.append('order_id', orderId);
  const response = await fetch('/raspadinhas/finish.php', { method: 'POST', body: fd });
  const json = await response.json();

  if (!json.success) return alert('Erro ao finalizar.');

  if (json.resultado === 'gain') {
    resultMsg.innerHTML = `<span class="text-emerald-400">🎉 Você ganhou R$ ${json.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}!</span>`;
  } else {
    resultMsg.innerHTML = `<span class="text-red-400">Não foi dessa vez. 😢</span>`;
  }

  btnBuy.style.display = 'block';
  btnBuy.disabled = false;
  btnBuy.textContent = 'Jogar Novamente';
}

btnBuy.addEventListener('click', async () => {
  btnBuy.disabled = true;
  btnBuy.textContent = 'Gerando...';
  resultMsg.textContent = '';
  prizesGrid.innerHTML = '';
  overlay.style.display = 'none';

  const fd = new FormData();
  fd.append('raspadinha_id', <?= $cartela['id']; ?>);
  const res = await fetch('/raspadinhas/buy.php', { method: 'POST', body: fd });
  const json = await res.json();

  if (!json.success) {
    alert(json.error);
    btnBuy.disabled = false;
    btnBuy.textContent = 'Comprar e Raspar';
    overlay.style.display = 'flex';
    return;
  }

  orderId = json.order_id;
  const premiosRes = await fetch('/raspadinhas/prizes.php?ids=' + json.grid.join(','));
  const premios = await premiosRes.json();

  prizesGrid.innerHTML = premios.map(buildCell).join('');
  drawScratchImage();
  isScratchEnabled = true;
  btnBuy.style.display = 'none';
});

scratchImage.onload = () => {
  drawScratchImage();
};

canvas.addEventListener('mousedown', handleStart);
canvas.addEventListener('mousemove', handleMove);
canvas.addEventListener('mouseup', handleEnd);
canvas.addEventListener('mouseleave', handleEnd);
canvas.addEventListener('touchstart', handleStart);
canvas.addEventListener('touchmove', handleMove);
canvas.addEventListener('touchend', handleEnd);
canvas.addEventListener('touchcancel', handleEnd);
</script>

<?php include('../components/ganhos.php'); ?>
<?php include('../inc/footer.php'); ?>
</body>
</html>

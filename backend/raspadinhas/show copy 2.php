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



  <style>
    #scratch-container {
      position: relative;
      width: 487px;
      height: 487px;
      margin: 0 auto;
      user-select: none;
    }
    #prizes-grid {
      position: absolute;
      top: 0; left: 0;
      width: 487px;
      height: 487px;
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
      width: 487px;
      height: 487px;
      border-radius: 8px;
      z-index: 10;
      touch-action: none;
      cursor: pointer;
      user-select: none;
    }
    #btn-buy {
      margin-top: 1rem;
      width: 487px;
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

  
  <div id="scratch-container">
    <div id="prizes-grid"></div>
    <canvas id="scratch-canvas" width="487" height="487"></canvas>
  </div>

  <button id="btn-buy" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-lg font-bold">
    Comprar e Raspar (R$<?= number_format($cartela['valor'], 2, ',', '.'); ?>)
  </button>

  <div id="result-msg"></div>


<script>
  const canvas = document.getElementById('scratch-canvas');
  const ctx = canvas.getContext('2d');
  const prizesGrid = document.getElementById('prizes-grid');
  const btnBuy = document.getElementById('btn-buy');
  const resultMsg = document.getElementById('result-msg');

  const canvasWidth = canvas.width;
  const canvasHeight = canvas.height;

  let isDrawing = false;
  let orderId = null;
  let imageLoaded = false;
  let brushRadius = 25;
  let scratchedPercentage = 0;
  let scratchImage = new Image();
  scratchImage.src = '/assets/img/raspe.png';

  // Desenha a imagem da raspadinha no canvas
  function drawScratchImage() {
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
    ctx.globalCompositeOperation = 'source-over';
    ctx.drawImage(scratchImage, 0, 0, canvasWidth, canvasHeight);
  }

  // Apaga o traço do pincel na posição x,y
  function scratch(x, y) {
    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, brushRadius, 0, Math.PI * 2, false);
    ctx.fill();
    ctx.closePath();
  }

  // Calcula o quanto foi raspado no canvas (percentual)
  function getScratchedPercentage() {
    const imageData = ctx.getImageData(0, 0, canvasWidth, canvasHeight);
    const pixels = imageData.data;
    let transparentPixels = 0;

    for (let i = 3; i < pixels.length; i += 4) {
      if (pixels[i] === 0) {
        transparentPixels++;
      }
    }
    return (transparentPixels / (canvasWidth * canvasHeight)) * 100;
  }

  function handleStart(e) {
    e.preventDefault();
    isDrawing = true;
    let pos = getPos(e);
    scratch(pos.x, pos.y);
  }

  function handleMove(e) {
    e.preventDefault();
    if (!isDrawing) return;
    let pos = getPos(e);
    scratch(pos.x, pos.y);
  }

  function handleEnd(e) {
    e.preventDefault();
    if (!isDrawing) return;
    isDrawing = false;
    scratchedPercentage = getScratchedPercentage();
    if (scratchedPercentage > 90) {
      finishScratch();
    }
  }

  // Pega a posição do mouse ou toque no canvas
  function getPos(e) {
    let rect = canvas.getBoundingClientRect();
    let x, y;
    if (e.touches) {
      x = e.touches[0].clientX - rect.left;
      y = e.touches[0].clientY - rect.top;
    } else {
      x = e.clientX - rect.left;
      y = e.clientY - rect.top;
    }
    return { x, y };
  }

  // Limpa canvas e redesenha imagem raspadinha
  function resetScratch() {
    drawScratchImage();
    scratchedPercentage = 0;
  }

  // Constroi os prêmios na grade
  function buildCell(prize) {
    return `
      <div>
        <img src="${prize.icone}" alt="${prize.nome}" />
        <span>${prize.valor > 0 ? 'R$ ' + prize.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : prize.nome}</span>
      </div>
    `;
  }

  // Inicializa grade vazia (antes de comprar)
  function initEmptyGrid() {
    const emptyCells = Array(9).fill('&nbsp;').map(c => `<div>${c}</div>`).join('');
    prizesGrid.innerHTML = emptyCells;
  }

  // Função chamada ao finalizar raspagem
  async function finishScratch() {
    if (!orderId) return;
    resultMsg.textContent = 'Verificando resultado...';

    try {
      const fd = new FormData();
      fd.append('order_id', orderId);
      const response = await fetch('/raspadinhas/finish.php', { method: 'POST', body: fd });
      const json = await response.json();

      if (!json.success) {
        alert('Erro no resultado');
        return;
      }

      if (json.resultado === 'gain') {
        resultMsg.innerHTML = `<span class="text-emerald-400">🎉 Você ganhou R$ ${json.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}!</span>`;
      } else {
        resultMsg.innerHTML = `<span class="text-red-400">Não foi dessa vez. 😢</span>`;
      }

      btnBuy.style.display = 'block';
      btnBuy.disabled = false;
      btnBuy.textContent = 'Jogar Novamente';
    } catch (err) {
      alert('Erro ao comunicar com servidor.');
    }
  }

  // Evento do botão comprar
  btnBuy.addEventListener('click', async () => {
    btnBuy.disabled = true;
    btnBuy.textContent = 'Gerando...';
    resultMsg.textContent = '';
    prizesGrid.innerHTML = '';

    try {
      const fd = new FormData();
      fd.append('raspadinha_id', <?= $cartela['id']; ?>);
      const res = await fetch('/raspadinhas/buy.php', { method: 'POST', body: fd });
      const json = await res.json();

      if (!json.success) {
        alert(json.error);
        btnBuy.disabled = false;
        btnBuy.textContent = 'Comprar e Raspar (R$<?= number_format($cartela['valor'], 2, ',', '.'); ?>)';
        return;
      }

      orderId = json.order_id;

      // Busca dados dos prêmios sorteados
      const premRes = await fetch('/raspadinhas/prizes.php?ids=' + json.grid.join(','));
      const premios = await premRes.json();

      prizesGrid.innerHTML = premios.map(buildCell).join('');

      // Reseta a raspadinha para poder raspar novamente
      resetScratch();

      btnBuy.style.display = 'none';
    } catch (e) {
      alert('Erro ao conectar com servidor.');
      btnBuy.disabled = false;
      btnBuy.textContent = 'Comprar e Raspar (R$<?= number_format($cartela['valor'], 2, ',', '.'); ?>)';
    }
  });

  // Ao carregar a página, carrega imagem e inicializa canvas e grid vazia
  scratchImage.onload = () => {
    drawScratchImage();
    initEmptyGrid();
  };

  // Eventos para raspar no canvas (mouse e toque)
  canvas.addEventListener('mousedown', handleStart);
  canvas.addEventListener('mousemove', handleMove);
  canvas.addEventListener('mouseup', handleEnd);
  canvas.addEventListener('mouseleave', handleEnd);
  canvas.addEventListener('touchstart', handleStart);
  canvas.addEventListener('touchmove', handleMove);
  canvas.addEventListener('touchend', handleEnd);
  canvas.addEventListener('touchcancel', handleEnd);
</script>
</section>

<?php include('../components/ganhos.php'); ?>
<?php include('../inc/footer.php'); ?>
</body>
</html>
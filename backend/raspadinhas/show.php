<?php
@session_start();
require_once '../conexao.php';

// Verificar se o usuário está logado
$usuario_logado = isset($_SESSION['usuario_id']);

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

// Buscar o maior prêmio da raspadinha
$stmt = $pdo->prepare("SELECT MAX(valor) as max_prize FROM raspadinha_premios WHERE raspadinha_id = ?");
$stmt->execute([$id]);
$maxPrize = $stmt->fetch(PDO::FETCH_ASSOC)['max_prize'] ?? 0;
$maxPrizeFormatted = number_format($maxPrize, 2, ',', '.');

// Calcular estatísticas dos prêmios
$totalPrizes = count($premios);
$winningPrizes = 0;
$totalValue = 0;
foreach ($premios as $premio) {
    if ($premio['valor'] > 0) {
        $winningPrizes++;
        $totalValue += $premio['valor'];
    }
}
$winRate = $totalPrizes > 0 ? round(($winningPrizes / $totalPrizes) * 100, 1) : 0;
$avgPrize = $winningPrizes > 0 ? round($totalValue / $winningPrizes, 2) : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $nomeSite;?> - <?php echo htmlspecialchars($cartela['nome']); ?></title>
  <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>"/>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js"></script>

  <style>
  #scratch-container {
    position: relative;
    width: 100%;
    max-width: 500px;
    aspect-ratio: 1 / 1;
    margin: 0 auto;
    border-radius: 20px !important;
    user-select: none;
    background: rgba(0,0,0,0.1);
  }
  #prizes-grid {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    display: none;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 8px;
    padding: 12px;
    background: var(--bg-color);
    color: white;
    border-radius: 20px !important;
    z-index: 1;
  }
  #prizes-grid > div {
    background: rgba(0, 0, 0, 0.7);
    border-radius: 20px !important;
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
    border-radius: 20px !important;
    z-index: 10;
    touch-action: none;
    cursor: pointer;
    user-select: none;
    background: rgba(0,0,0,0.1);
    opacity: 1;
    visibility: visible;
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
    border-radius: 20px !important;
    text-align: center;
  }
  #btn-buy {
    margin-top: 1rem;
    width: 100%;
    max-width: 500px;
    display: block;
  }
  #result-msg {
    margin-top: 1rem;
    font-weight: 700;
    text-align: center;
    min-height: 1.5em;
    display: none;
  }

  /* Correção específica para Safari */
  @supports (-webkit-appearance: none) {
    #result-msg {
      display: none !important;
      visibility: hidden !important;
      opacity: 0 !important;
      height: 0 !important;
      overflow: hidden !important;
    }
  }

  /* Correção adicional para Safari */
  @media screen and (-webkit-min-device-pixel-ratio: 0) {
    #result-msg {
      display: none !important;
      visibility: hidden !important;
      opacity: 0 !important;
      height: 0 !important;
      overflow: hidden !important;
    }
  }

  /* Overlay de login para usuários não logados */
  .login-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-size: 1.25rem;
    font-weight: bold;
    color: #fff;
    z-index: 50;
    border-radius: 20px !important;
    text-align: center;
    padding: 20px;
  }
  .login-overlay i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #00FF88;
  }
  .login-overlay h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: #fff;
  }
  .login-overlay p {
    margin-bottom: 25px;
    font-size: 1rem;
    opacity: 0.9;
    color: #d1d5db;
  }
  .login-btn {
    background: linear-gradient(135deg, #00FF88, #00CC66);
    color: black;
    padding: 15px 30px;
    border-radius: 12px;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
  }
  .login-btn:hover {
    background: linear-gradient(135deg, #00CC66, #00AA55);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 255, 136, 0.4);
  }

  /* Fundo da raspadinha para usuários não logados */
  .scratch-locked {
    background: linear-gradient(135deg, #1f2937, #374151);
    border: 2px dashed rgba(16, 185, 129, 0.3);
  }

  /* Estilos para a seção de prêmios */
  .prizes-section {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    margin-top: 2rem;
    width: 100%;
    max-width: 1000px;
  }

  .prizes-category {
    margin-bottom: 2rem;
  }

  /* Grid compacto para todos os prêmios */
  .prizes-grid-small {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
  }

  /* Prêmios Dourados (Grandes) */
  .prize-card-gold {
    background: rgba(255, 215, 0, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
  }

  .prize-card-gold:hover {
    transform: translateY(-3px);
    background: rgba(255, 215, 0, 0.15);
    border-color: rgba(255, 215, 0, 0.4);
  }

  .prize-value-gold {
    font-size: 0.8rem;
    font-weight: 700;
    color: #ffd700;
    background: rgba(255, 215, 0, 0.1);
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    display: inline-block;
  }

  /* Prêmios Verdes (Médios) */
  .prize-card-green {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
  }

  .prize-card-green:hover {
    transform: translateY(-3px);
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
  }

  .prize-value-green {
    font-size: 0.8rem;
    font-weight: 700;
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    display: inline-block;
  }

  /* Prêmios Azuis (Pequenos) */
  .prize-card-small {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
  }

  .prize-card-small:hover {
    transform: translateY(-3px);
    background: rgba(59, 130, 246, 0.15);
  }

  .prize-value-small {
    font-size: 0.8rem;
    font-weight: 700;
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    display: inline-block;
  }

  /* Estilos comuns para todas as imagens e nomes */
  .prize-card-gold img,
  .prize-card-green img,
  .prize-card-small img {
    width: 40px;
    height: 40px;
    object-fit: contain;
    margin: 0 auto 0.5rem;
    border-radius: 8px;
  }

  .prize-name-small {
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
    margin-bottom: 0.3rem;
    line-height: 1.2;
  }

  /* Responsividade Mobile */
  @media (max-width: 768px) {
    .prizes-section {
      padding: 1.5rem;
    }
    
    .prizes-grid-small {
      grid-template-columns: repeat(3, 1fr);
      gap: 0.8rem;
    }
    
    .prize-card-gold,
    .prize-card-green,
    .prize-card-small {
      padding: 0.8rem;
    }
    
    .prize-card-gold img,
    .prize-card-green img,
    .prize-card-small img {
      width: 35px;
      height: 35px;
    }
    
    .prize-name-small {
      font-size: 0.7rem;
    }
    
    .prize-value-gold,
    .prize-value-green,
    .prize-value-small {
      font-size: 0.7rem;
      padding: 0.2rem 0.4rem;
    }
  }

  @media (max-width: 480px) {
    .prizes-grid-small {
      grid-template-columns: repeat(3, 1fr);
      gap: 0.6rem;
    }
    
    .prize-card-gold,
    .prize-card-green,
    .prize-card-small {
      padding: 0.6rem;
    }
    
    .prize-card-gold img,
    .prize-card-green img,
    .prize-card-small img {
      width: 30px;
      height: 30px;
    }
    
    .prize-name-small {
      font-size: 0.65rem;
    }
    
    .prize-value-gold,
    .prize-value-green,
    .prize-value-small {
      font-size: 0.65rem;
    }
  }

  @media (max-width: 360px) {
    .prizes-grid-small {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  /* Estilos para o card informativo no estilo das raspadinhas */
  .card-raspadinha {
    transition: all 0.3s ease;
    background: linear-gradient(to top, var(--darker-bg-form), transparent);
  }

  .card-raspadinha:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
  }

  /* Efeito de hover na imagem */
  .card-raspadinha img {
    transition: transform 0.3s ease;
  }

  .card-raspadinha:hover img {
    transform: scale(1.05);
  }



  /* Responsividade para o card */
  @media (max-width: 640px) {
    .card-raspadinha h2 {
      font-size: 1rem;
    }
  }

  /* Estilos específicos para o botão de compra */
  #btn-buy {
    position: relative;
    overflow: hidden;
  }

  #btn-buy .flex.items-center {
    align-items: center;
    justify-content: flex-start;
  }

  #btn-buy, #btn-buy a {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  #btn-buy .flex.items-center {
    flex: 1;
    text-align: left;
    justify-content: flex-start;
  }

  #btn-buy::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
  }

  #btn-buy:hover::before {
    left: 100%;
  }

  #btn-buy:active {
    transform: scale(0.98);
  }

  /* Estilos para o botão Revelar Tudo */
  #btn-reveal {
    position: relative;
    overflow: hidden;
  }

  #btn-reveal::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
  }

  #btn-reveal:hover::before {
    left: 100%;
  }

  #btn-reveal:active {
    transform: scale(0.98);
  }

  /* Responsividade para o botão */
  @media (max-width: 480px) {
    #btn-buy, #btn-reveal {
      padding: 0.75rem 1rem;
    }
    
    #btn-buy .text-lg, #btn-reveal .text-lg {
      font-size: 0.875rem;
    }
    
    #btn-buy .text-xl, #btn-reveal .text-xl {
      font-size: 1rem;
    }
  }
</style>
</head>
<body >

<?php include('../inc/header.php'); ?> 

<section class="relative max-w-[1200px] w-full mx-auto px-4 py-10 flex flex-col justify-center items-center gap-4" >

  <div class="bg-[#1A1A1A] border border-gray-600 relative p-4 rounded-lg flex flex-col justify-between items-center gap-4 w-full max-w-[500px] overflow-hidden">




    <div id="scratch-container">
      <?php if ($usuario_logado): ?>
        <div id="prizes-grid" style="display: none;">
          <?php foreach ($premios as $premio): ?>
            <div>
              <img src="<?= htmlspecialchars($premio['icone']); ?>" alt="<?= htmlspecialchars($premio['nome']); ?>" />
              <span><?php if ($premio['valor'] > 0): ?>R$ <?= number_format($premio['valor'], 2, ',', '.'); ?><?php else: ?><?= htmlspecialchars($premio['nome']); ?><?php endif; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <canvas id="scratch-canvas"></canvas>
        <div id="btn-overlay">
          <i class="fas fa-shopping-cart mr-2"></i>
          Compre Para jogar
        </div>
      <?php else: ?>
        <div class="scratch-locked">
          <div class="login-overlay">
            <i class="fas fa-lock"></i>
            <h3>Área Bloqueada</h3>
            <p>Faça login para ver os prêmios e jogar!</p>
            <a href="/login" class="login-btn">
              <i class="fas fa-sign-in-alt"></i>
              Fazer Login para Jogar
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($usuario_logado): ?>
      <div class="flex flex-col gap-3 w-full max-w-[500px]">
        <div class="text-center mb-2">
          <p class="text-gray-400 text-sm">Compre uma raspadinha para começar a jogar</p>
          <p class="text-gray-500 text-xs mt-1">Clique no botão abaixo para comprar</p>
        </div>
        
        <button id="btn-buy" class="w-full bg-[#00FF88] hover:bg-[#00CC66] text-black py-1 px-6 rounded-xl font-bold cursor-pointer transition-all duration-300 flex items-center justify-between shadow-lg hover:shadow-xl">
          <div class="flex items-center gap-2">
            <i class="fas fa-gift text-xl"></i>
            <span class="text-lg">Comprar Raspadinha</span>
          </div>
          <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
            <span class="text-[#00FF88]">R$</span> <span class="text-white"><?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
          </div>
        </button>
        
        <button id="btn-reveal" class="w-full bg-blue-500 hover:bg-blue-400 text-white py-1 px-6 rounded-xl font-bold cursor-pointer transition-all duration-300 flex items-center justify-between shadow-lg hover:shadow-xl hidden">
          <div class="flex items-center gap-2">
            <i class="fas fa-eye text-xl"></i>
            <span class="text-lg">Revelar Tudo</span>
          </div>
          <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
            <span class="text-blue-400">Rápido</span>
          </div>
        </button>
      </div>
    <?php else: ?>
      <a href="/login" class="w-full max-w-[500px] bg-[#00FF88] hover:bg-[#00CC66] text-black py-1 px-6 rounded-xl font-bold cursor-pointer text-center block text-decoration-none transition-all duration-300 flex items-center justify-between shadow-lg hover:shadow-xl">
        <div class="flex items-center gap-2">
          <i class="fas fa-gift text-xl"></i>
          <span class="text-lg">Comprar Raspadinha</span>
        </div>
        <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
          <span class="text-[#00FF88]">R$</span> <span class="text-white"><?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
        </div>
      </a>
    <?php endif; ?>

    <!-- Divisão Visual -->
    <div class="w-full flex items-center justify-center my-6">
      <div class="flex-1 h-px bg-gray-600"></div>
      <div class="px-4 text-gray-500 text-sm font-medium">
        <i class="fas fa-info-circle mr-2"></i>
        Informações do Jogo
      </div>
      <div class="flex-1 h-px bg-gray-600"></div>
    </div>

    <!-- Card Informativo no Estilo das Raspadinhas da Tela Inicial -->
    <div class="w-full">
    <div class="card-raspadinha text-card-foreground flex flex-col gap-3 rounded-2xl border shadow-sm bg-gradient-to-t from-[var(--darker-bg-form)] to-transparent border-gray-600 group cursor-pointer p-0 h-full">
      <div data-slot="card-content" class="p-0">
        <div class="relative rounded-t-2xl overflow-hidden flex items-center justify-center">
          <!-- Badge de preço -->
          <div class="absolute top-2 right-2 z-10">
            <span class="inline-flex items-center justify-center rounded-md border text-xs w-fit whitespace-nowrap shrink-0 gap-1 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-auto border-transparent bg-[var(--primary-color)] text-black font-bold px-2 py-1">
              R$ <?= number_format($cartela['valor'], 2, ',', '.'); ?>
            </span>
          </div>
          
          <img src="<?= htmlspecialchars($cartela['banner']); ?>"
               alt="Banner <?= htmlspecialchars($cartela['nome']); ?>"
               class="max-h-52 w-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
        </div>
        
        <div class="p-4">
          <h2 class="font-semibold mb-2 line-clamp-1 text-white text-lg">
            <?= htmlspecialchars($cartela['nome']); ?>
          </h2>
          
          <div class="flex flex-col mb-3">
            <p class="text-sm text-amber-400 mb-2 font-bold">
              PRÊMIOS ATÉ R$<?php echo $maxPrizeFormatted; ?>
            </p>
            <p class="text-xs text-gray-400 mb-3 line-clamp-2 leading-relaxed">
              <?php echo htmlspecialchars($cartela['descricao'] ?? 'A sorte chegou com força total! Encontre 3 símbolos iguais e ganhe prêmios incríveis. Resultado instantâneo e pagamento na hora!'); ?>
            </p>
            <div class="flex items-center justify-between text-xs text-gray-500 mt-2">
              <span><i class="fas fa-trophy mr-1"></i><?php echo $totalPrizes; ?> prêmios</span>
              <span><i class="fas fa-percentage mr-1"></i><?php echo $winRate; ?>% chance</span>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
              <span><i class="fas fa-coins mr-1"></i>Média: R$<?php echo number_format($avgPrize, 2, ',', '.'); ?></span>
              <span><i class="fas fa-star mr-1"></i><?php echo $winningPrizes; ?> vencedores</span>
            </div>
          </div>
          
          <!-- Informações Adicionais -->
          <div class="flex items-center justify-between text-xs text-gray-400 mb-3">
            <div class="flex items-center gap-1">
              <i class="fas fa-bolt text-yellow-400"></i>
              <span>Resultado Imediato</span>
            </div>
            <div class="flex items-center gap-1">
              <i class="fas fa-shield-alt text-[#00FF88]"></i>
              <span>Pagamento Seguro</span>
            </div>
          </div>
          

        </div>
      </div>
    </div>

  </div>

  <div id="result-msg" style="display: none;"></div>

  <!-- Seção de Prêmios -->
  <div class="prizes-section">
    <h2 class="text-2xl font-bold text-white text-center mb-2">
      <i class="fas fa-trophy text-yellow-400 mr-2"></i>
      Prêmios da <?php echo htmlspecialchars($cartela['nome']); ?>:
    </h2>
    <p class="text-gray-300 text-center mb-4">
      Veja todos os <?php echo $totalPrizes; ?> prêmios que você pode ganhar nesta raspadinha. 
      <span class="text-yellow-400 font-semibold">Prêmio máximo: R$<?php echo $maxPrizeFormatted; ?></span>
    </p>
    
    <?php
    // Separar prêmios por valor para melhor organização
    $premios_grandes = [];
    $premios_medios = [];
    $premios_pequenos = [];
    
    foreach ($premios as $premio) {
      if ($premio['valor'] >= 1000) {
        $premios_grandes[] = $premio;
      } elseif ($premio['valor'] >= 50) {
        $premios_medios[] = $premio;
      } else {
        $premios_pequenos[] = $premio;
      }
    }
    ?>
    
    <!-- Prêmios Grandes (Grid Compacto Dourado) -->
    <?php if (!empty($premios_grandes)): ?>
    <div class="prizes-category mb-6">
      <h3 class="text-lg font-semibold text-yellow-400 mb-3 flex items-center justify-center">
        <i class="fas fa-crown mr-2"></i>
        Prêmios Principais
      </h3>
      <div class="prizes-grid-small">
        <?php foreach ($premios_grandes as $premio): ?>
          <div class="prize-card-gold">
            <img src="<?= htmlspecialchars($premio['icone']); ?>" 
                 alt="<?= htmlspecialchars($premio['nome']); ?>"
                 onerror="this.src='/assets/img/icons/default-prize.png'">
            
            <div class="prize-name-small">
              <?= htmlspecialchars($premio['nome']); ?>
            </div>
            
            <div class="prize-value-gold">
              <?php if ($premio['valor'] > 0): ?>
                R$ <?= number_format($premio['valor'], 2, ',', '.'); ?>
              <?php else: ?>
                <?= htmlspecialchars($premio['nome']); ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Prêmios Médios (Grid Compacto Verde) -->
    <?php if (!empty($premios_medios)): ?>
    <div class="prizes-category mb-6">
      <h3 class="text-lg font-semibold text-[#00FF88] mb-3 flex items-center justify-center">
        <i class="fas fa-gem mr-2"></i>
        Prêmios Especiais
      </h3>
      <div class="prizes-grid-small">
        <?php foreach ($premios_medios as $premio): ?>
          <div class="prize-card-green">
            <img src="<?= htmlspecialchars($premio['icone']); ?>" 
                 alt="<?= htmlspecialchars($premio['nome']); ?>"
                 onerror="this.src='/assets/img/icons/default-prize.png'">
            
            <div class="prize-name-small">
              <?= htmlspecialchars($premio['nome']); ?>
            </div>
            
            <div class="prize-value-green">
              <?php if ($premio['valor'] > 0): ?>
                R$ <?= number_format($premio['valor'], 2, ',', '.'); ?>
              <?php else: ?>
                <?= htmlspecialchars($premio['nome']); ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Prêmios Pequenos (Grid Compacto Azul) -->
    <?php if (!empty($premios_pequenos)): ?>
    <div class="prizes-category">
      <h3 class="text-lg font-semibold text-blue-400 mb-3 flex items-center justify-center">
        <i class="fas fa-coins mr-2"></i>
        Outros Prêmios
      </h3>
      <div class="prizes-grid-small">
        <?php foreach ($premios_pequenos as $premio): ?>
          <div class="prize-card-small">
            <img src="<?= htmlspecialchars($premio['icone']); ?>" 
                 alt="<?= htmlspecialchars($premio['nome']); ?>"
                 onerror="this.src='/assets/img/icons/default-prize.png'">
            
            <div class="prize-name-small">
              <?= htmlspecialchars($premio['nome']); ?>
            </div>
            
            <div class="prize-value-small">
              <?php if ($premio['valor'] > 0): ?>
                R$ <?= number_format($premio['valor'], 2, ',', '.'); ?>
              <?php else: ?>
                <?= htmlspecialchars($premio['nome']); ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</section>
<script>
let container = document.getElementById('scratch-container');
let canvas = document.getElementById('scratch-canvas');
let ctx = canvas.getContext('2d');
let prizesGrid = document.getElementById('prizes-grid');
let btnBuy = document.getElementById('btn-buy');
let btnReveal = document.getElementById('btn-reveal');
let resultMsg = document.getElementById('result-msg');
let overlay = document.getElementById('btn-overlay');
let scratchImage = new Image();
scratchImage.src = '/assets/img/raspe.png?id=122';

let orderId = null;
let brushRadius = 55;
let isDrawing = false;
let scratchedPercentage = 0;
let isScratchEnabled = false;

function ajustarCanvas() {
  const size = container.clientWidth;
  canvas.width = size;
  canvas.height = size;
  
  // Garantir que o contexto seja resetado corretamente
  ctx.globalCompositeOperation = 'source-over';
  ctx.fillStyle = 'rgba(0,0,0,1)';
  
  drawScratchImage();
}

function resetCanvas() {
  if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);

  const newCanvas = document.createElement('canvas');
  newCanvas.id = 'scratch-canvas';
  newCanvas.className = canvas.className;    
  container.appendChild(newCanvas);

  canvas = newCanvas;
  ctx    = newCanvas.getContext('2d');

  // Configurar o contexto corretamente
  ctx.globalCompositeOperation = 'source-over';
  ctx.fillStyle = 'rgba(0,0,0,1)';

  ajustarCanvas();

  addCanvasListeners();
}

function addCanvasListeners() {
  canvas.replaceWith(canvas.cloneNode(true));
  canvas = document.getElementById('scratch-canvas');
  ctx    = canvas.getContext('2d');

  canvas.addEventListener('mousedown', handleStart);
  canvas.addEventListener('mousemove', handleMove);
  canvas.addEventListener('mouseup', handleEnd);
  canvas.addEventListener('mouseleave', handleEnd);
  canvas.addEventListener('touchstart', handleStart, {passive:false});
  canvas.addEventListener('touchmove', handleMove,  {passive:false});
  canvas.addEventListener('touchend', handleEnd);
  canvas.addEventListener('touchcancel', handleEnd);
}

window.addEventListener('resize', ajustarCanvas);
scratchImage.onload = () => {
  ajustarCanvas();
};

// Verificar se a imagem carregou corretamente
scratchImage.onerror = () => {
  console.warn('Erro ao carregar imagem da raspadinha, usando fallback');
  // Criar um fallback visual se a imagem não carregar
  ajustarCanvas();
};

// Correção específica para Safari - garantir que result-msg esteja oculto
function hideResultMsgSafari() {
  if (resultMsg) {
    resultMsg.style.display = 'none';
    resultMsg.style.visibility = 'hidden';
    resultMsg.style.opacity = '0';
    resultMsg.style.height = '0';
    resultMsg.style.overflow = 'hidden';
    resultMsg.textContent = '';
  }
  
  // Também esconder prizes-grid no Safari
  if (prizesGrid) {
    prizesGrid.style.display = 'none';
    prizesGrid.innerHTML = '';
  }
}

// Executar correção para Safari
if (navigator.userAgent.includes('Safari') && !navigator.userAgent.includes('Chrome')) {
  hideResultMsgSafari();
  // Executar novamente após carregamento completo
  window.addEventListener('load', hideResultMsgSafari);
}

function drawScratchImage() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.globalCompositeOperation = 'source-over';
  ctx.fillStyle = 'rgba(0,0,0,1)';
  
  // Verificar se a imagem está carregada
  if (scratchImage.complete && scratchImage.naturalWidth !== 0) {
    ctx.drawImage(scratchImage, 0, 0, canvas.width, canvas.height);
  } else {
    // Fallback visual se a imagem não carregar
    const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
    gradient.addColorStop(0, '#1f2937');
    gradient.addColorStop(1, '#374151');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Adicionar texto de fallback
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 24px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('RASPADINHA', canvas.width / 2, canvas.height / 2 - 20);
    ctx.font = '16px Arial';
    ctx.fillText('Toque para raspar', canvas.width / 2, canvas.height / 2 + 20);
  }
}

function scratch(x, y) {
  if (!isScratchEnabled) return;
  ctx.globalCompositeOperation = 'destination-out';
  ctx.fillStyle = 'rgba(0,0,0,1)';
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
  
  // Garantir que o canvas seja visível ao começar a raspar
  canvas.style.opacity = '1';
  canvas.style.visibility = 'visible';
  
  const pos = getMousePos(e);
  scratch(pos.x, pos.y);
}

function handleMove(e) {
  if (!isDrawing || !isScratchEnabled) return;
  const pos = getMousePos(e);
  scratch(pos.x, pos.y);
  scratchedPercentage = getScratchedPercentage();
  
  // Garantir que o canvas permaneça visível
  canvas.style.opacity = '1';
  canvas.style.visibility = 'visible';
  
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

let fadeInterval = null;

async function autoFinishScratch() {
  isScratchEnabled = false;
  
  // Remover canvas imediatamente sem delay
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  
  // Finalizar imediatamente
  finishScratch();
}

function finishScratch() {
  // Mostrar resultado imediatamente
  const fd = new FormData();
  fd.append('order_id', orderId);
  fetch('/raspadinhas/finish.php', { method: 'POST', body: fd })
    .then(response => response.json())
    .then(json => {

  if (!json.success) return Notiflix.Notify.failure('Erro ao finalizar.');

  const jsConfetti = new JSConfetti();
  
  if (json.valor === 0 || json.resultado === 'lose') {
    resultMsg.innerHTML = `<span class="text-red-400">Não foi dessa vez. 😢</span>`;
    resultMsg.style.display = 'block';
    resultMsg.style.visibility = 'visible';
    resultMsg.style.opacity = '1';
    resultMsg.style.height = 'auto';
    resultMsg.style.overflow = 'visible';
    Notiflix.Notify.info('Não foi dessa vez. 😢')
    clearInterval(fadeInterval)
    fadeInterval = 0;

    atualizarSaldoUsuario();
  } else {
    resultMsg.innerHTML = `<span class="text-[#00FF88]">🎉 Você ganhou R$ ${json.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}!</span>`;
    resultMsg.style.display = 'block';
    resultMsg.style.visibility = 'visible';
    resultMsg.style.opacity = '1';
    resultMsg.style.height = 'auto';
    resultMsg.style.overflow = 'visible';
    Notiflix.Notify.info(`🎉 Você ganhou R$ ${json.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}!`)
    clearInterval(fadeInterval)
    fadeInterval = 0;

    jsConfetti.addConfetti({
    emojis: ['🎉', '✨', '🎊', '🥳'],
    emojiSize: 20,
    confettiNumber: 200,
    confettiRadius: 6,
    confettiColors: ['#ff0a54', '#ff477e', '#ff85a1', '#fbb1b1', '#f9bec7']
  });

    atualizarSaldoUsuario();
  }

  btnBuy.style.opacity = '1';
  btnBuy.disabled = false;
  btnBuy.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="fas fa-redo text-xl"></i>
      <span class="text-lg">Jogar Novamente</span>
    </div>
    <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
      <span class="text-[#00FF88]">R$</span> <span class="text-white"><?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
    </div>
  `;
  
  // Reset completo do botão revelar após finalizar
  btnReveal.disabled = false;
  btnReveal.classList.add('hidden');
  btnReveal.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="fas fa-eye text-xl"></i>
      <span class="text-lg">Revelar Tudo</span>
    </div>
    <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
      <span class="text-blue-400">Rápido</span>
    </div>
  `;
    })
    .catch(error => {
      console.error('Erro ao finalizar raspadinha:', error);
      Notiflix.Notify.failure('Erro ao finalizar.');
    });
}

function revelarTudo() {
  if (!isScratchEnabled || !orderId) return;
  
  isScratchEnabled = false;
  btnReveal.disabled = true;
  btnReveal.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="fas fa-spinner fa-spin text-xl"></i>
      <span class="text-lg">Revelando...</span>
    </div>
    <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
      <span class="text-blue-400">Aguarde</span>
    </div>
  `;
  
  // Revelar tudo imediatamente sem delay
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  finishScratch();
}

function reiniciarJogo() {
  if (fadeInterval) { clearInterval(fadeInterval); fadeInterval = null; }

  prizesGrid.innerHTML = '';
  prizesGrid.style.display = 'none';
  hideResultMsgSafari(); // Usar função específica para Safari
  overlay.style.display = 'flex';
  orderId = null;
  scratchedPercentage = 0;
  isScratchEnabled = false;
  isDrawing           = false;
  
  // Resetar o contexto do canvas
  ctx.globalCompositeOperation = 'source-over';
  ctx.fillStyle = 'rgba(0,0,0,1)';
  
  ajustarCanvas();
  resetCanvas();
  
  // Reset completo do botão comprar
  btnBuy.disabled = false;
  btnBuy.style.opacity = '1';
  btnBuy.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="fas fa-gift text-xl"></i>
      <span class="text-lg">Comprar Raspadinha</span>
    </div>
    <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
      <span class="text-[#00FF88]">R$</span> <span class="text-white"><?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
    </div>
  `;
  
  // Reset completo do botão revelar
  btnReveal.disabled = false;
  btnReveal.classList.add('hidden');
  btnReveal.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="fas fa-eye text-xl"></i>
      <span class="text-lg">Revelar Tudo</span>
    </div>
    <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
      <span class="text-blue-400">Rápido</span>
    </div>
  `;
}

btnBuy.addEventListener('click', async () => {
  if (btnBuy.textContent === 'Jogar Novamente') {
    reiniciarJogo();
    btnBuy.click();
    return;
  }

  btnBuy.disabled = true;
  btnBuy.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="fas fa-spinner fa-spin text-xl"></i>
      <span class="text-lg">Gerando...</span>
    </div>
    <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
      <span class="text-green-400">R$</span> <span class="text-white"><?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
    </div>
  `;
  hideResultMsgSafari(); // Usar função específica para Safari
  prizesGrid.innerHTML = '';
  prizesGrid.style.display = 'none';
  overlay.style.display = 'none';

  const fd = new FormData();
  fd.append('raspadinha_id', <?= $cartela['id']; ?>);
  const res = await fetch('/raspadinhas/buy.php', { method: 'POST', body: fd });
  const json = await res.json();

  if (!json.success) {
        Notiflix.Notify.failure(json.error);
    btnBuy.disabled = false;
  btnBuy.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="fas fa-gift text-xl"></i>
      <span class="text-lg">Comprar Raspadinha</span>
    </div>
    <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
      <span class="text-green-400">R$</span> <span class="text-white"><?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
    </div>
  `;
  
  // Garantir que o canvas seja visível após erro
  if (canvas) {
    canvas.style.opacity = '1';
    canvas.style.visibility = 'visible';
  }
    overlay.style.display = 'flex';
    
    // Reset completo do botão revelar em caso de erro
    btnReveal.disabled = false;
    btnReveal.classList.add('hidden');
    btnReveal.innerHTML = `
      <div class="flex items-center gap-2">
        <i class="fas fa-eye text-xl"></i>
        <span class="text-lg">Revelar Tudo</span>
      </div>
      <div class="bg-gray-900 px-3 py-1 rounded-lg font-bold">
        <span class="text-blue-400">Rápido</span>
      </div>
    `;
    return;
  }

  orderId = json.order_id;
  const premiosRes = await fetch('/raspadinhas/prizes.php?ids=' + json.grid.join(','));
  const premios = await premiosRes.json();

  prizesGrid.innerHTML = premios.map(buildCell).join('');
  prizesGrid.style.display = 'grid';
  
  // Garantir que o canvas seja visível e configurado corretamente
  canvas.style.opacity = '1';
  canvas.style.visibility = 'visible';
  ctx.globalCompositeOperation = 'source-over';
  ctx.fillStyle = 'rgba(0,0,0,1)';
  
  drawScratchImage();
  isScratchEnabled = true;
  btnBuy.style.opacity = '0';
  btnReveal.classList.remove('hidden');
});

canvas.addEventListener('mousedown', handleStart);
canvas.addEventListener('mousemove', handleMove);
canvas.addEventListener('mouseup', handleEnd);
canvas.addEventListener('mouseleave', handleEnd);
canvas.addEventListener('touchstart', handleStart);
canvas.addEventListener('touchmove', handleMove);
canvas.addEventListener('touchend', handleEnd);
canvas.addEventListener('touchcancel', handleEnd);

// Evento para o botão Revelar Tudo
btnReveal.addEventListener('click', revelarTudo);

function atualizarSaldoUsuario() {
  fetch('/api/get_saldo.php')
    .then(res => res.json())
    .then(json => {
      if (json.success) {
        const saldoFormatado = 'R$ ' + json.saldo.toFixed(2).replace('.', ',');
        const el = document.getElementById('headerSaldo');
        if (el) {
          el.textContent = saldoFormatado;
        }
      } else {
        console.warn('Erro ao buscar saldo:', json.error);
      }
    })
    .catch(e => {
      console.error('Erro na requisição de saldo:', e);
    });
}



</script>
<?php include('../components/ganhos.php'); ?>
<?php include('../inc/footer.php'); ?>
</body>
</html>

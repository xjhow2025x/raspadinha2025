<?php
@session_start();
// Inclui o arquivo de conexão de forma robusta
if (file_exists('./conexao.php')) {
  include('./conexao.php');
} elseif (file_exists('../conexao.php')) {
  include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
  include('../../conexao.php');
}

// Parâmetros de filtro e ordenação
$categoria_filtro = $_GET['categoria'] ?? '';
$ordenacao = $_GET['ordem'] ?? 'destaque'; // destaque, maior_valor, menor_valor, mais_recente

// Buscar categorias ativas
$sql_categorias = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC";
$categorias = $pdo->query($sql_categorias)->fetchAll(PDO::FETCH_ASSOC);

// Se não há filtro de categoria e existem categorias, usar a primeira como padrão
if (empty($categoria_filtro) && !empty($categorias)) {
    $categoria_filtro = $categorias[0]['slug'];
}

// Construir query das raspadinhas
$where_conditions = [];
$params = [];

if (!empty($categoria_filtro)) {
    // Verificar se a categoria selecionada é "destaque" (pelo slug)
    $categoria_selecionada = null;
    foreach ($categorias as $cat) {
        if ($cat['slug'] === $categoria_filtro) {
            $categoria_selecionada = $cat;
            break;
        }
    }
    
    // Se a categoria for "destaque", filtrar apenas raspadinhas em destaque
    if ($categoria_selecionada && strtolower($categoria_selecionada['nome']) === 'destaque') {
        $where_conditions[] = "r.destaque = 1";
    } else {
        // Caso contrário, filtrar pela categoria normal
        $where_conditions[] = "c.slug = ?";
        $params[] = $categoria_filtro;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Definir ordenação
$order_clause = '';
switch ($ordenacao) {
    case 'maior_valor':
        $order_clause = 'ORDER BY r.valor DESC, r.destaque DESC, r.ordem ASC';
        break;
    case 'menor_valor':
        $order_clause = 'ORDER BY r.valor ASC, r.destaque DESC, r.ordem ASC';
        break;
    case 'mais_recente':
        $order_clause = 'ORDER BY r.created_at DESC, r.destaque DESC, r.ordem ASC';
        break;
    case 'destaque':
    default:
        $order_clause = 'ORDER BY r.destaque DESC, r.ordem ASC, maior_premio DESC';
        break;
}

// Lógica para buscar as raspadinhas e o maior prêmio
$sql = "
    SELECT r.*,
       MAX(p.valor) AS maior_premio,
       MAX(c.nome) as categoria_nome,
       MAX(c.slug) as categoria_slug,
       MAX(c.icone) as categoria_icone,
       MAX(c.cor) as categoria_cor
  FROM raspadinhas r
  LEFT JOIN raspadinha_premios p ON p.raspadinha_id = r.id
  LEFT JOIN categorias c ON c.id = r.categoria_id
  $where_clause
GROUP BY r.id
$order_clause";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cartelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?></title>
    <style>
/* Global styles */
@tailwind base;
@tailwind components;
@tailwind utilities;
* {
  box-sizing: border-box;
  outline: none;
}
:root {
  --primary-color: #00FF88; /* Verde fluorescente para ações principais */
  --secondary-color: #00FF66;
  --tertiary-color: #df2dbb;
  --bg-color: #0D1F0D;
  --support-color: #0D1F0D;
  /* Cores adicionais para consistência com o tema */
  --dark-bg-form: #1a1a1a;
  --darker-bg-form: #222222;
  --text-gray-light: #cccccc;
  --text-green-accent: #00FF88;
  --border-color-input: #333333;
  --border-color-active: #00FF88;
}
html,body {
  padding: 0;
  margin: 0;
  height: 100%;
}
body {
  display: flex;
  padding-top: 6rem;
  flex-direction: column;
  position: relative;
  min-height: 100vh;
  overflow-x: hidden;
  background: linear-gradient(135deg, #0D1F0D 0%, #1a1a1a 50%, #0D2D0D 100%); /* Fundo verde gradiente */
  color: white;
}
.shadow-rox {
  box-shadow: rgba(50, 50, 93, 0.25) 0px 6px 12px -2px, rgba(0, 0, 0, 0.3) 0px 3px 7px -3px;
}
/* Notiflix styles */
.notiflix-notify-success {
  background-color: var(--secondary-color) !important;
  color: white !important;
}
.notiflix-notify-info {
  background-color: var(--tertiary-color) !important;
  color: white !important;
}
.notiflix-notify-failure {
  background-color: #c0392b !important;
  color: white !important;
}
.notiflix-notify {
  top: 83px !important;
  z-index: 9999 !important;
  position: fixed !important;
  max-width: 90vw !important;
  width: 300px !important;
  right: 16px !important;
  left: auto !important;
  border-radius: 8px !important;
  overflow-wrap: break-word;
  box-sizing: border-box;
}
#NotiflixNotifyWrap .notiflix-notify {
  background-clip: padding-box !important;
}

/* Estilos simplificados para categorias */
.categoria-btn-simple {
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.categoria-btn-simple:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

/* Força o background das categorias - sobrescreve CSS global */
.categoria-btn-simple.categoria-ativa {
  background-color: var(--primary-color) !important;
  color: black !important;
  border: 1px solid var(--primary-color) !important;
}

.categoria-btn-simple.categoria-inativa {
  background-color: transparent !important;
  color: #f3f4f6 !important;
  border: 1px solid rgba(107, 114, 128, 0.3) !important;
}

.categoria-btn-simple.categoria-inativa:hover {
  border-color: rgba(156, 163, 175, 0.5) !important;
  color: white !important;
}

/* Animações para cards */
.card-raspadinha {
  transition: all 0.3s ease;
}
.card-raspadinha:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 25px rgba(52, 211, 153, 0.2);
}

/* Badge de categoria */
.categoria-badge {
  backdrop-filter: blur(10px);
  background: rgba(0, 0, 0, 0.7);
}
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
</head>
<body>
<?php include('./inc/header.php'); ?>
<?php include('./components/carrossel.php'); ?>
<?php include('./components/stories.php'); ?>

<!-- Seção de Categorias - Layout Simples -->
<section class="w-full max-w-[1200px] mx-auto py-0 px-4">
    <div class="flex flex-wrap gap-1 justify-start mb-1">
        <?php foreach ($categorias as $categoria): ?>
            <a href="?categoria=<?php echo htmlspecialchars($categoria['slug']); ?>&ordem=<?php echo htmlspecialchars($ordenacao); ?>" 
               class="categoria-btn-simple px-2 py-1 rounded-lg text-sm font-medium transition-all duration-300 <?php echo $categoria_filtro === $categoria['slug'] ? 'categoria-ativa' : 'categoria-inativa'; ?>">
                <?php echo htmlspecialchars($categoria['nome']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Seção das Raspadinhas -->
<section class="w-full max-w-[1200px] mx-auto py-6 px-4">
    <?php if (empty($cartelas)): ?>
        <div class="text-center py-12">
            <i class="fa-solid fa-search text-6xl text-gray-600 mb-4"></i>
            <h3 class="text-xl text-gray-400 mb-2">Nenhuma raspadinha encontrada</h3>
            <p class="text-gray-500">Tente alterar os filtros ou escolher outra categoria.</p>
            <a href="/" class="inline-block mt-4 px-6 py-2 bg-[var(--primary-color)] text-black rounded-lg font-medium hover:bg-[var(--secondary-color)] transition-colors">
                Ver Todas as Raspadinhas
            </a>
        </div>
    <?php else: ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($cartelas as $c): ?>
                <a href="/raspadinhas/show.php?id=<?= $c['id']; ?>"
                   class="card-raspadinha text-card-foreground flex flex-col gap-3 rounded-2xl border shadow-sm bg-gradient-to-t from-[var(--darker-bg-form)] to-transparent border-none group cursor-pointer p-0 h-full">
                    <div data-slot="card-content" class="p-0">
                        <div class="relative rounded-t-2xl overflow-hidden flex items-center justify-center">
                            <!-- Badge de preço -->
                            <div class="absolute top-2 right-2 z-10">
                                <span class="inline-flex items-center justify-center rounded-md border text-xs w-fit whitespace-nowrap shrink-0 gap-1 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-auto border-transparent bg-[var(--primary-color)] text-black font-bold px-2 py-1">
                                    R$ <?= number_format($c['valor'], 2, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <img src="<?= htmlspecialchars($c['banner']); ?>"
                                 alt="Banner <?= htmlspecialchars($c['nome']); ?>"
                                 class="max-h-52 w-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            
                        </div>
                        <div class="p-2">
                            <h2 class="font-semibold mb-1 line-clamp-1 text-white">
                                <?= htmlspecialchars($c['nome']); ?>
                            </h2>
                            <div class="flex flex-col">
                                <p class="text-sm text-amber-400 mb-1">
                                    PRÊMIOS ATÉ R$<?= number_format($c['maior_premio'], 0, ',', '.'); ?>,00
                                </p>
                                <p class="text-xs text-gray-400 mb-2 line-clamp-2">
                                    <?= htmlspecialchars($c['descricao']); ?>
                                </p>
                            </div>
                            <button type="button"
                                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm transition-all disabled:pointer-events-none disabled:opacity-50 shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-[var(--primary-color)] text-black shadow-xs hover:bg-[var(--primary-color)]/90 h-9 w-full mt-1 px-3 py-1 rounded-md font-bold">
                                Jogar Raspadinha<i class="fa-solid fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include('./components/ganhos.php'); ?>
<?php include('./inc/footer.php'); ?>

<script>
// Adicionar animações suaves ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Animar cards das raspadinhas
    const cards = document.querySelectorAll('.card-raspadinha');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animar botões de categoria
    const categorias = document.querySelectorAll('.categoria-btn-simple');
    categorias.forEach((btn, index) => {
        btn.style.opacity = '0';
        btn.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            btn.style.transition = 'all 0.3s ease';
            btn.style.opacity = '1';
            btn.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>

<style>
/* Melhorias responsivas */
@media (max-width: 768px) {
    .categoria-btn-simple {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>

</body>
</html>

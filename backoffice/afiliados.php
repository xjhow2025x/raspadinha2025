<?php
include '../includes/session.php';
include '../conexao.php';
include '../includes/notiflix.php';

$usuarioId = $_SESSION['usuario_id'];
$admin = ($stmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;

if ($admin != 1) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você não é um administrador!'];
    header("Location: /");
    exit;
}

if (isset($_GET['toggle_banido'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE usuarios SET banido = IF(banido=1, 0, 1) WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'Status de banido alterado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao alterar status!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['toggle_influencer'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE usuarios SET influencer = IF(influencer=1, 0, 1) WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'Status de influencer alterado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao alterar status!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['atualizar_comissao_cpa'])) {
    $id = $_POST['id'];
    $comissao_cpa = str_replace(',', '.', $_POST['comissao_cpa']);

    $stmt = $pdo->prepare("UPDATE usuarios SET comissao_cpa = ? WHERE id = ?");
    if ($stmt->execute([$comissao_cpa, $id])) {
        $_SESSION['success'] = 'Comissão CPA atualizada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar comissão CPA!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT u.*,
           (SELECT COUNT(*) FROM usuarios WHERE indicacao = u.id) as total_indicados,
           (SELECT COALESCE(SUM(d.valor), 0) FROM depositos d
            JOIN usuarios u2 ON d.user_id = u2.id
            WHERE u2.indicacao = u.id AND d.status = 'aprovado') as total_depositos_indicados
          FROM usuarios u
          WHERE EXISTS (SELECT 1 FROM usuarios WHERE indicacao = u.id)"; // Apenas usuários que indicaram alguém
if (!empty($search)) {
    $query .= " AND (u.nome LIKE :search OR u.email LIKE :search OR u.telefone LIKE :search)";
}
$query .= " ORDER BY total_depositos_indicados DESC, total_indicados DESC"; // Ordenar por depósitos e indicados
$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$stmt->execute();
$afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?></title>
    <?php include './components/bg.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
</head>
<body class="relative" style="overflow-y: auto !important;">
   <?php include './components/header.php'; ?>
   <?php if (isset($_SESSION['success'])): ?>
        <script>
            Notiflix.Notify.success('<?= $_SESSION['success'] ?>');
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (isset($_SESSION['failure'])): ?>
        <script>
            Notiflix.Notify.failure('<?= $_SESSION['failure'] ?>');
        </script>
        <?php unset($_SESSION['failure']); ?>
    <?php endif; ?>
   <div id="content-afiliados" class="w-full lg:w-[calc(100vw-280px)] min-h-[calc(100vh-80px)] flex flex-col gap-8 overflow-y-auto z-5" style="overflow-y: auto !important; margin-top: 80px;">
        <div class="flex flex-col gap-1 mb-8">
            <h1 class="text-white text-3xl font-bold">Gerenciar Afiliados</h1>
            <p class="text-gray-400 text-base">Visualize e gerencie todos os afiliados do sistema</p>
        </div>
        <form method="GET" class="mb-4">
            <div class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full pl-10 border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                        placeholder="Pesquisar afiliados por nome, email ou telefone">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </form>

        <!-- Modal de Edição de Comissão CPA -->
        <div id="editarComissaoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 w-full max-w-md shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-4">Editar Comissão CPA</h2>
                <form method="POST" id="formEditarComissao" class="flex flex-col gap-4">
                    <input type="hidden" name="id" id="afiliadoId">
                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Valor da Comissão CPA (R$)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">R$</span>
                            <input type="text" name="comissao_cpa" id="afiliadoComissao"
                                    class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full pl-8 border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                                    placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button type="submit" name="atualizar_comissao_cpa" class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 flex-1 transition-colors duration-200">Salvar</button>
                        <button type="button" onclick="fecharModalComissao()" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg p-3 flex-1 transition-colors duration-200">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($afiliados as $afiliado): ?>
                <?php
                $telefone = $afiliado['telefone'];
                if (strlen($telefone) == 11) {
                    $telefoneFormatado = '('.substr($telefone, 0, 2).') '.substr($telefone, 2, 5).'-'.substr($telefone, 7);
                } else {
                    $telefoneFormatado = $telefone;
                }
                $whatsappLink = 'https://wa.me/55'.preg_replace('/[^0-9]/', '', $afiliado['telefone']);
                $comissao_cpa = isset($afiliado['comissao_cpa']) ? number_format($afiliado['comissao_cpa'], 2, ',', '.') : '0,00';
                ?>
                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-white font-bold text-xl break-words"><?= htmlspecialchars($afiliado['nome']) ?></h3>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($afiliado['admin'] == 1): ?>
                                    <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded-full">Admin</span>
                                <?php endif; ?>
                                <?php if ($afiliado['influencer'] == 1): ?>
                                    <span class="bg-pink-600 text-white text-xs px-2 py-1 rounded-full">Influencer</span>
                                <?php endif; ?>
                                <?php if ($afiliado['banido'] == 1): ?>
                                    <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">Banido</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 text-gray-300 text-sm mb-4">
                            <p class="flex items-center"><i class="fas fa-envelope mr-2 text-gray-500"></i> <?= htmlspecialchars($afiliado['email']) ?></p>
                            <p class="flex items-center">
                                <i class="fas fa-phone mr-2 text-gray-500"></i> <?= $telefoneFormatado ?>
                                <a href="<?= $whatsappLink ?>" target="_blank" class="ml-2 text-green-500 hover:text-green-400">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-700/50">
                            <div class="bg-[var(--dark-bg-form)] rounded-lg p-4 flex flex-col items-start">
                                <p class="text-white font-medium flex items-center mb-1"><i class="fas fa-users mr-2 text-gray-500"></i> Total de Indicados</p>
                                <p class="text-3xl text-[var(--primary-color)] font-bold"><?= $afiliado['total_indicados'] ?></p>
                            </div>
                            <div class="bg-[var(--dark-bg-form)] rounded-lg p-4 flex flex-col items-start">
                                <p class="text-white font-medium flex items-center mb-1"><i class="fas fa-money-bill-wave mr-2 text-gray-500"></i> Depósitos dos Indicados</p>
                                <p class="text-3xl text-[var(--primary-color)] font-bold">R$ <?= number_format($afiliado['total_depositos_indicados'], 2, ',', '.') ?></p>
                            </div>
                            <div class="bg-[var(--dark-bg-form)] rounded-lg p-4 flex flex-col items-start">
                                <p class="text-white font-medium flex items-center mb-1"><i class="fas fa-percentage mr-2 text-gray-500"></i> Comissão CPA</p>
                                <div class="flex items-center justify-between w-full">
                                    <p class="text-3xl text-[var(--primary-color)] font-bold">R$ <?= $comissao_cpa ?></p>
                                    <button onclick="abrirModalComissao('<?= $afiliado['id'] ?>', '<?= isset($afiliado['comissao_cpa']) ? number_format($afiliado['comissao_cpa'], 2, '.', '') : '0.00' ?>')"
                                            class="text-blue-400 hover:text-blue-300 transition-colors duration-200">
                                        <i class="fas fa-edit text-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-auto pt-4 border-t border-gray-700/50">
                        <a href="?toggle_banido&id=<?= $afiliado['id'] ?>"
                           class="bg-red-600 hover:bg-red-700 text-white rounded-lg p-2 text-sm flex items-center justify-center flex-grow transition-colors duration-200">
                            <i class="fas fa-<?= $afiliado['banido'] ? 'check' : 'ban' ?> mr-1"></i> <?= $afiliado['banido'] ? 'Desbanir' : 'Banir' ?>
                        </a>
                        <a href="?toggle_influencer&id=<?= $afiliado['id'] ?>"
                           class="bg-green-600 hover:bg-green-700 text-white rounded-lg p-2 text-sm flex items-center justify-center flex-grow transition-colors duration-200">
                            <i class="fas fa-<?= $afiliado['influencer'] ? 'times' : 'check' ?> mr-1"></i> <?= $afiliado['influencer'] ? 'Remover Inf.' : 'Tornar Inf.' ?>
                        </a>
                    </div>

                    <div class="text-gray-400 text-xs mt-4 pt-2 border-t border-gray-700/50">
                        <i class="fas fa-calendar mr-1"></i> Cadastrado em: <?= date('d/m/Y H:i', strtotime($afiliado['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Incluindo o componente de assinatura NexCode -->
    <?php include 'components/nexcode-signature.php'; ?>
    <script>
        function abrirModalComissao(id, comissao) {
            document.getElementById('afiliadoId').value = id;
            document.getElementById('afiliadoComissao').value = comissao;
            document.getElementById('editarComissaoModal').classList.remove('hidden');
        }

        function fecharModalComissao() {
            document.getElementById('editarComissaoModal').classList.add('hidden');
        }

        document.getElementById('editarComissaoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalComissao();
            }
        });
    </script>
    <style>
        /* Adicionado as variáveis CSS para consistência de cores */
        :root {
            --primary-color: #00de93; /* Verde principal */
            --secondary-color: #00de93;
            --tertiary-color: #00de93;
            --bg-color: #13151b; /* Fundo principal do admin */
            --support-color: #00de93;
            --dark-bg-form: #1a1a1a;
            --darker-bg-form: #222222;
            --text-gray-light: #cccccc;
            --text-green-accent: #00de93;
            --border-color-input: #333333;
            --border-color-active: #00de93;

            /* Cores específicas do admin (ajustadas para a imagem) */
            --admin-header-bg: #1a1a1a; /* Fundo do cabeçalho do admin */
            --admin-sidebar-bg: #1a1a1a; /* Fundo do sidebar do admin */
            --admin-text-color: #e0e0e0; /* Cor do texto padrão no admin */
            --admin-link-hover: #00b37a; /* Cor do hover para links do admin */
            --admin-active-link-bg: rgba(0, 222, 147, 0.1); /* Fundo para link ativo */
            --card-bg-gray: #222222; /* Alterado de roxo para cinza escuro */
            --card-icon-green: #00de93; /* Cor dos ícones verdes nos cards */
            --card-icon-yellow: #FFD700; /* Cor do ícone amarelo no card de saldo */
            --card-text-label: #cccccc; /* Cor do texto da label nos cards */
            --card-value-white: #ffffff; /* Cor do valor nos cards */
            --card-text-green: #00de93; /* Cor do texto verde nos cards */
            --card-text-red: #ef4444; /* Cor do texto vermelho para saques */
        }

        #content-afiliados {
            padding: 24px 42px; /* Padding padrão para desktop */
            margin-left: 280px; /* Espaço para o sidebar em desktop */
        }
        @media screen and (max-width: 1023px) { /* Ajuste para telas menores que 'lg' (1024px) */
            #content-afiliados {
                padding: 24px 20px; /* Padding menor em mobile */
                margin-left: 0; /* Ocupa a largura total em mobile */
            }
        }
    </style>
</body>
</html>

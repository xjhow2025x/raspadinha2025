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

if (isset($_POST['atualizar_saldo'])) {
    $id = $_POST['id'];
    $saldo = str_replace(',', '.', $_POST['saldo']);

    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
    if ($stmt->execute([$saldo, $id])) {
        $_SESSION['success'] = 'Saldo atualizado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar saldo!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
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

$search = isset($_GET['search']) ? $_GET['search'] : '';

// PAGINAÇÃO
$por_pagina = 12;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $por_pagina;

// TOTAL DE REGISTROS (com filtro)
$countQuery = "SELECT COUNT(*) FROM usuarios u LEFT JOIN usuarios ui ON u.indicacao = ui.id WHERE 1=1";
if (!empty($search)) {
    $countQuery .= " AND (u.nome LIKE :search OR u.email LIKE :search OR u.telefone LIKE :search)";
    $stmtCount = $pdo->prepare($countQuery);
    $searchTerm = "%$search%";
    $stmtCount->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    $stmtCount->execute();
} else {
    $stmtCount = $pdo->query($countQuery);
}
$total_registros = $stmtCount->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// CONSULTA PAGINADA
$query = "SELECT u.*, ui.email as email_indicador FROM usuarios u LEFT JOIN usuarios ui ON u.indicacao = ui.id WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (u.nome LIKE :search OR u.email LIKE :search OR u.telefone LIKE :search)";
}
$query .= " ORDER BY u.created_at DESC LIMIT :offset, :por_pagina";
$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':por_pagina', $por_pagina, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
   <div id="content-users" class="w-full lg:w-[calc(100vw-280px)] min-h-[calc(100vh-80px)] flex flex-col gap-8 overflow-y-auto z-5" style="overflow-y: auto !important; margin-top: 80px;">
        <div class="flex flex-col gap-1 mb-8">
            <h1 class="text-white text-3xl font-bold">Gerenciar Usuários</h1>
            <p class="text-gray-400 text-base">Visualize e gerencie todos os usuários do sistema</p>
        </div>
        <form method="GET" class="mb-4">
            <div class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full pl-10 border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                        placeholder="Pesquisar por nome, email ou telefone">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </form>

        <!-- Modal de Edição de Saldo -->
        <div id="editarSaldoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 w-full max-w-md shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-4">Editar Saldo</h2>
                <form method="POST" id="formEditarSaldo" class="flex flex-col gap-4">
                    <input type="hidden" name="id" id="usuarioId">
                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Novo Saldo (R$)</label>
                        <input type="text" name="saldo" id="usuarioSaldo"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button type="submit" name="atualizar_saldo" class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 flex-1 transition-colors duration-200">Salvar</button>
                        <button type="button" onclick="fecharModal()" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg p-3 flex-1 transition-colors duration-200">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($usuarios as $usuario): ?>
                <?php
                $telefone = $usuario['telefone'];
                if (strlen($telefone) == 11) {
                    $telefoneFormatado = '('.substr($telefone, 0, 2).') '.substr($telefone, 2, 5).'-'.substr($telefone, 7);
                } else {
                    $telefoneFormatado = $telefone;
                }
                $whatsappLink = 'https://wa.me/55'.preg_replace('/[^0-9]/', '', $usuario['telefone']);
                ?>
                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-white font-bold text-xl break-words"><?= htmlspecialchars($usuario['nome']) ?></h3>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($usuario['admin'] == 1): ?>
                                    <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded-full">Admin</span>
                                <?php endif; ?>
                                <?php if ($usuario['influencer'] == 1): ?>
                                    <span class="bg-pink-600 text-white text-xs px-2 py-1 rounded-full">Influencer</span>
                                <?php endif; ?>
                                <?php if ($usuario['banido'] == 1): ?>
                                    <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">Banido</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 text-gray-300 text-sm mb-4">
                            <p class="flex items-center"><i class="fas fa-envelope mr-2 text-gray-500"></i> <?= htmlspecialchars($usuario['email']) ?></p>
                            <p class="flex items-center">
                                <i class="fas fa-phone mr-2 text-gray-500"></i> <?= $telefoneFormatado ?>
                                <a href="<?= $whatsappLink ?>" target="_blank" class="ml-2 text-green-500 hover:text-green-400">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </p>
                            <p class="flex items-center text-white font-semibold text-base"><i class="fas fa-wallet mr-2 text-[var(--card-icon-yellow)]"></i> Saldo: R$ <?= number_format($usuario['saldo'], 2, ',', '.') ?></p>
                            <p class="flex items-center"><i class="fas fa-user-plus mr-2 text-gray-500"></i> Indicado por: <span class="text-white"><?= $usuario['email_indicador'] ? htmlspecialchars($usuario['email_indicador']) : 'Ninguém' ?></span></p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-auto pt-4 border-t border-gray-700/50">
                        <button onclick="abrirModalEditarSaldo('<?= $usuario['id'] ?>', '<?= number_format($usuario['saldo'], 2, '.', '') ?>')"
                                 class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg p-2 text-sm flex items-center justify-center flex-grow transition-colors duration-200">
                            <i class="fas fa-edit mr-1"></i> Saldo
                        </button>
                        <a href="?toggle_banido&id=<?= $usuario['id'] ?>"
                           class="bg-red-600 hover:bg-red-700 text-white rounded-lg p-2 text-sm flex items-center justify-center flex-grow transition-colors duration-200">
                            <i class="fas fa-<?= $usuario['banido'] ? 'check' : 'ban' ?> mr-1"></i> <?= $usuario['banido'] ? 'Desbanir' : 'Banir' ?>
                        </a>
                        <a href="?toggle_influencer&id=<?= $usuario['id'] ?>"
                           class="bg-green-600 hover:bg-green-700 text-white rounded-lg p-2 text-sm flex items-center justify-center flex-grow transition-colors duration-200">
                            <i class="fas fa-<?= $usuario['influencer'] ? 'times' : 'check' ?> mr-1"></i> <?= $usuario['influencer'] ? 'Remover Inf.' : 'Tornar Inf.' ?>
                        </a>
                    </div>

                    <div class="text-gray-400 text-xs mt-4 pt-2 border-t border-gray-700/50">
                        <i class="fas fa-calendar mr-1"></i> Cadastrado em: <?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINAÇÃO -->
        <div class="flex justify-center mt-8">
            <nav class="flex gap-2">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-gray-800 text-white hover:bg-[var(--primary-color)]">&laquo; Anterior</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?= $i ?>&search=<?= urlencode($search) ?>"
                        class="px-3 py-1 rounded <?= $i == $pagina ? 'bg-[var(--primary-color)] text-black font-bold' : 'bg-gray-800 text-white hover:bg-[var(--primary-color)]' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-gray-800 text-white hover:bg-[var(--primary-color)]">Próxima &raquo;</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    <?php include 'components/nexcode-signature.php'; ?>
    <script>
        function abrirModalEditarSaldo(id, saldo) {
            document.getElementById('usuarioId').value = id;
            document.getElementById('usuarioSaldo').value = saldo;
            document.getElementById('editarSaldoModal').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('editarSaldoModal').classList.add('hidden');
        }

        document.getElementById('editarSaldoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    </script>
    <style>
        :root {
            --primary-color: #00de93;
            --secondary-color: #00de93;
            --tertiary-color: #00de93;
            --bg-color: #13151b;
            --support-color: #00de93;
            --dark-bg-form: #1a1a1a;
            --darker-bg-form: #222222;
            --text-gray-light: #cccccc;
            --text-green-accent: #00de93;
            --border-color-input: #333333;
            --border-color-active: #00de93;

            --admin-header-bg: #1a1a1a;
            --admin-sidebar-bg: #1a1a1a;
            --admin-text-color: #e0e0e0;
            --admin-link-hover: #00b37a;
            --admin-active-link-bg: rgba(0, 222, 147, 0.1);
            --card-bg-gray: #222222;
            --card-icon-green: #00de93;
            --card-icon-yellow: #FFD700;
            --card-text-label: #cccccc;
            --card-value-white: #ffffff;
            --card-text-green: #00de93;
            --card-text-red: #ef4444;
        }

        #content-users {
            padding: 24px 42px;
            margin-left: 280px;
        }
        @media screen and (max-width: 1023px) {
            #content-users {
                padding: 24px 20px;
                margin-left: 0;
            }
        }
    </style>
</body>
</html>

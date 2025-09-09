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

if (isset($_POST['aprovar_saque'])) {
    $saque_id = $_POST['saque_id'];
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE saques SET status = 'PAID', updated_at = NOW() WHERE id = ?");
        if (!$stmt->execute([$saque_id])) {
            throw new Exception("Erro ao atualizar status do saque");
        }

        $pdo->commit();
        $_SESSION['success'] = 'Saque aprovado e pagamento realizado com sucesso!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['failure'] = 'Erro ao aprovar o saque: ' . $e->getMessage();

        file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    header('Location: '.$_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

if (isset($_POST['reprovar_saque'])) {
    $saque_id = $_POST['saque_id'];
    $stmt = $pdo->prepare("DELETE FROM saques WHERE id = ?");
    if ($stmt->execute([$saque_id])) {
        $_SESSION['success'] = 'Saque reprovado!';
    } else {
        $_SESSION['failure'] = 'Erro ao reprovar o saque!';
    }
    header('Location: '.$_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

// Aprovar todos os saques pendentes
if (isset($_POST['aprovar_todos_saques'])) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE saques SET status = 'PAID', updated_at = NOW() WHERE status = 'PENDING'");
        $stmt->execute();
        
        $saques_aprovados = $stmt->rowCount();
        
        $pdo->commit();
        
        if ($saques_aprovados > 0) {
            $_SESSION['success'] = "Todos os saques foram aprovados com sucesso! Total: {$saques_aprovados} saques.";
        } else {
            $_SESSION['warning'] = 'Nenhum saque pendente encontrado para aprovação.';
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['failure'] = 'Erro ao aprovar todos os saques: ' . $e->getMessage();
        file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . " - ERROR APROVAR TODOS: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    header('Location: '.$_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

// Reprovar todos os saques pendentes
if (isset($_POST['reprovar_todos_saques'])) {
    try {
        $pdo->beginTransaction();
        
        // Primeiro, vamos buscar os saques pendentes para devolver o saldo
        $stmt = $pdo->prepare("SELECT user_id, valor FROM saques WHERE status = 'PENDING'");
        $stmt->execute();
        $saques_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devolver o saldo para cada usuário
        foreach ($saques_pendentes as $saque) {
            $stmt_saldo = $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
            $stmt_saldo->execute([$saque['valor'], $saque['user_id']]);
        }
        
        // Agora deletar todos os saques pendentes
        $stmt = $pdo->prepare("DELETE FROM saques WHERE status = 'PENDING'");
        $stmt->execute();
        
        $saques_reprovados = $stmt->rowCount();
        
        $pdo->commit();
        
        if ($saques_reprovados > 0) {
            $_SESSION['success'] = "Todos os saques foram reprovados com sucesso! Total: {$saques_reprovados} saques. O saldo foi devolvido aos usuários.";
        } else {
            $_SESSION['warning'] = 'Nenhum saque pendente encontrado para reprovação.';
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['failure'] = 'Erro ao reprovar todos os saques: ' . $e->getMessage();
        file_put_contents('daanrox.txt', date('d/m/Y H:i:s') . " - ERROR REPROVAR TODOS: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    header('Location: '.$_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

// Parâmetros de filtro e paginação
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'PENDING'; // Por padrão, mostrar apenas pendentes
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12; // 12 saques por página (4x3 grid)
$offset = ($page - 1) * $per_page;

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($status_filter && $status_filter !== 'ALL') {
    $where_conditions[] = "saques.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Contar total de registros
$count_query = "SELECT COUNT(*) FROM saques JOIN usuarios ON saques.user_id = usuarios.id $where_clause";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Buscar saques com paginação
$query = "SELECT saques.id, saques.user_id, saques.valor, saques.cpf, saques.status, saques.updated_at, usuarios.nome
          FROM saques
          JOIN usuarios ON saques.user_id = usuarios.id
          $where_clause
          ORDER BY saques.updated_at DESC
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$saques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar saques pendentes para exibir nos botões
$stmt_pendentes = $pdo->query("SELECT COUNT(*) FROM saques WHERE status = 'PENDING'");
$saques_pendentes_count = $stmt_pendentes->fetchColumn();

// Estatísticas gerais - CORRIGIDO: Tratamento de valores null
$stmt_stats = $pdo->query("
    SELECT 
        COUNT(CASE WHEN status = 'PENDING' THEN 1 END) as pendentes,
        COUNT(CASE WHEN status = 'PAID' THEN 1 END) as aprovados,
        COALESCE(SUM(CASE WHEN status = 'PENDING' THEN valor ELSE 0 END), 0) as valor_pendente,
        COALESCE(SUM(CASE WHEN status = 'PAID' THEN valor ELSE 0 END), 0) as valor_aprovado
    FROM saques
");
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Garantir que os valores nunca sejam null
$stats['pendentes'] = (int)($stats['pendentes'] ?? 0);
$stats['aprovados'] = (int)($stats['aprovados'] ?? 0);
$stats['valor_pendente'] = (float)($stats['valor_pendente'] ?? 0);
$stats['valor_aprovado'] = (float)($stats['valor_aprovado'] ?? 0);
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
    <?php elseif (isset($_SESSION['warning'])): ?>
        <script>
            Notiflix.Notify.warning('<?= $_SESSION['warning'] ?>');
        </script>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>
   <div id="content-admin" class="w-full lg:w-[calc(100vw-280px)] min-h-[calc(100vh-80px)] flex flex-col gap-8 overflow-y-auto z-5" style="overflow-y: auto !important; margin-top: 80px;">
        <div class="flex flex-col gap-1 mb-4">
            <h1 class="text-white text-3xl font-bold">Gestão de Saques</h1>
            <p class="text-gray-400 text-base">Gerencie as solicitações de saque de usuários</p>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-4 shadow-rox">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Saques Pendentes</p>
                        <p class="text-white text-2xl font-bold"><?= $stats['pendentes'] ?></p>
                        <p class="text-orange-400 text-xs">R$ <?= number_format($stats['valor_pendente'], 2, ',', '.') ?></p>
                    </div>
                    <i class="fas fa-clock text-orange-400 text-2xl"></i>
                </div>
            </div>
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-4 shadow-rox">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Saques Aprovados</p>
                        <p class="text-white text-2xl font-bold"><?= $stats['aprovados'] ?></p>
                        <p class="text-green-400 text-xs">R$ <?= number_format($stats['valor_aprovado'], 2, ',', '.') ?></p>
                    </div>
                    <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                </div>
            </div>
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-4 shadow-rox">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total de Saques</p>
                        <p class="text-white text-2xl font-bold"><?= $stats['pendentes'] + $stats['aprovados'] ?></p>
                        <p class="text-blue-400 text-xs">R$ <?= number_format($stats['valor_pendente'] + $stats['valor_aprovado'], 2, ',', '.') ?></p>
                    </div>
                    <i class="fas fa-money-bill-wave text-blue-400 text-2xl"></i>
                </div>
            </div>
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-4 shadow-rox">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Página Atual</p>
                        <p class="text-white text-2xl font-bold"><?= $page ?></p>
                        <p class="text-gray-400 text-xs">de <?= $total_pages ?> páginas</p>
                    </div>
                    <i class="fas fa-file-alt text-gray-400 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox mb-6">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                <div>
                    <h3 class="text-white font-bold text-lg mb-1">Filtros</h3>
                    <p class="text-gray-400 text-sm">Filtre os saques por status</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="?status=PENDING&page=1" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?= $status_filter === 'PENDING' ? 'bg-orange-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
                        <i class="fas fa-clock mr-1"></i>Pendentes (<?= $stats['pendentes'] ?>)
                    </a>
                    <a href="?status=PAID&page=1" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?= $status_filter === 'PAID' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
                        <i class="fas fa-check-circle mr-1"></i>Aprovados (<?= $stats['aprovados'] ?>)
                    </a>
                    <a href="?status=ALL&page=1" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?= $status_filter === 'ALL' ? 'bg-blue-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
                        <i class="fas fa-list mr-1"></i>Todos (<?= $stats['pendentes'] + $stats['aprovados'] ?>)
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Botões de ação em massa (apenas para pendentes) -->
        <?php if ($status_filter === 'PENDING' && $saques_pendentes_count > 0): ?>
        <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox mb-6">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div>
                    <h3 class="text-white font-bold text-lg mb-1">Ações em Massa</h3>
                    <p class="text-gray-400 text-sm">
                        <i class="fa-solid fa-info-circle mr-1"></i>
                        <?= $saques_pendentes_count ?> saque(s) pendente(s) encontrado(s)
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <form method="POST" class="inline-block">
                        <button 
                            type="submit" 
                            name="aprovar_todos_saques" 
                            onclick="return confirmarAprovarTodos()"
                            class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold px-6 py-3 rounded-lg transition-colors duration-200 flex items-center gap-2 w-full sm:w-auto justify-center">
                            <i class="fa-solid fa-check-double"></i>
                            Aprovar Todos (<?= $saques_pendentes_count ?>)
                        </button>
                    </form>
                    <form method="POST" class="inline-block">
                        <button 
                            type="submit" 
                            name="reprovar_todos_saques" 
                            onclick="return confirmarReprovarTodos()"
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors duration-200 flex items-center gap-2 w-full sm:w-auto justify-center">
                            <i class="fa-solid fa-times-circle"></i>
                            Reprovar Todos (<?= $saques_pendentes_count ?>)
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Saques -->
        <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($saques as $saque): ?>
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox flex flex-col justify-between">
                <div>
                    <h3 class="text-white font-bold text-xl mb-2"><?= htmlspecialchars($saque['nome']) ?></h3>
                    <p class="text-gray-300 text-sm mb-1">Valor: <span class="text-white font-semibold">R$ <?= number_format($saque['valor'], 2, ',', '.') ?></span></p>
                    <p class="text-gray-300 text-sm mb-3">CPF: <span class="text-white"><?= htmlspecialchars($saque['cpf']) ?></span></p>
                </div>
                <div class="flex flex-col items-start pt-4 border-t border-gray-700/50">
                    <?php
                        $status_text = '';
                        $status_color_class = '';
                        $status_icon = '';
                        if ($saque['status'] == 'PAID') {
                            $status_text = 'Aprovado';
                            $status_color_class = 'text-[var(--card-text-green)]';
                            $status_icon = 'fa-check-circle';
                        } else {
                            $status_text = 'Pendente';
                            $status_color_class = 'text-orange-400';
                            $status_icon = 'fa-clock';
                        }
                    ?>
                    <p class="text-sm <?= $status_color_class ?> font-semibold mb-2">
                        <i class="fas <?= $status_icon ?> mr-1"></i><?= $status_text ?>
                    </p>
                    <p class="text-sm text-gray-400 mb-4">Última atualização: <?= date('d/m/Y H:i', strtotime($saque['updated_at'])) ?></p>
                    <?php if ($saque['status'] == 'PENDING'): ?>
                        <form method="POST" class="flex gap-2 w-full">
                            <input type="hidden" name="saque_id" value="<?= $saque['id'] ?>">
                            <button onclick="openLoading()" type="submit" name="aprovar_saque" class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold text-sm rounded-lg p-2 flex-1 transition-colors duration-200">
                                <i class="fas fa-check mr-1"></i>Aprovar
                            </button>
                            <button onclick="openLoading()" type="submit" name="reprovar_saque" class="bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg p-2 flex-1 transition-colors duration-200">
                                <i class="fas fa-times mr-1"></i>Reprovar
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-gray-600 text-white font-semibold text-sm rounded-lg p-2 w-full text-center">
                            <i class="fas fa-check-circle mr-1"></i>Aprovado
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($saques)): ?>
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox col-span-full text-center text-gray-300">
                <i class="fa-solid fa-money-bill-wave text-4xl mb-4 text-gray-500"></i>
                <h3 class="text-lg font-semibold mb-2">
                    <?php if ($status_filter === 'PENDING'): ?>
                        Nenhum saque pendente encontrado
                    <?php elseif ($status_filter === 'PAID'): ?>
                        Nenhum saque aprovado encontrado
                    <?php else: ?>
                        Nenhuma solicitação de saque encontrada
                    <?php endif; ?>
                </h3>
                <p class="text-sm text-gray-400">
                    <?php if ($status_filter === 'PENDING'): ?>
                        Quando os usuários solicitarem saques, eles aparecerão aqui.
                    <?php else: ?>
                        Tente alterar o filtro para ver outros saques.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox mt-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-gray-400 text-sm">
                    Mostrando <?= count($saques) ?> de <?= $total_records ?> saques
                    <?php if ($status_filter !== 'ALL'): ?>
                        (filtro: <?= $status_filter === 'PENDING' ? 'Pendentes' : 'Aprovados' ?>)
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Primeira página -->
                    <?php if ($page > 1): ?>
                        <a href="?status=<?= $status_filter ?>&page=1" 
                           class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Página anterior -->
                    <?php if ($page > 1): ?>
                        <a href="?status=<?= $status_filter ?>&page=<?= $page - 1 ?>" 
                           class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Páginas numeradas -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?status=<?= $status_filter ?>&page=<?= $i ?>" 
                           class="px-3 py-2 rounded-lg transition-colors duration-200 <?= $i === $page ? 'bg-[var(--primary-color)] text-black font-semibold' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Próxima página -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?= $status_filter ?>&page=<?= $page + 1 ?>" 
                           class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Última página -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?= $status_filter ?>&page=<?= $total_pages ?>" 
                           class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- Incluindo o componente de assinatura NexCode -->
    <?php include 'components/nexcode-signature.php'; ?>
    <script>
        function openLoading(){
            Notiflix.Loading.standard('Processando solicitação...')
        }
        
        function confirmarAprovarTodos() {
            return Notiflix.Confirm.show(
                'Confirmar Aprovação em Massa',
                'Tem certeza que deseja aprovar TODOS os saques pendentes? Esta ação não pode ser desfeita.',
                'Sim, Aprovar Todos',
                'Cancelar',
                function okCb() {
                    Notiflix.Loading.standard('Aprovando todos os saques...');
                    return true;
                },
                function cancelCb() {
                    return false;
                },
                {
                    width: '320px',
                    borderRadius: '8px',
                    titleColor: '#ffffff',
                    messageColor: '#cccccc',
                    okButtonBackground: '#00de93',
                    okButtonColor: '#000000',
                    cancelButtonBackground: '#ef4444',
                    cancelButtonColor: '#ffffff',
                    backgroundColor: '#1a1a1a',
                    backOverlayColor: 'rgba(0,0,0,0.8)',
                }
            );
        }
        
        function confirmarReprovarTodos() {
            return Notiflix.Confirm.show(
                'Confirmar Reprovação em Massa',
                'Tem certeza que deseja reprovar TODOS os saques pendentes? O saldo será devolvido aos usuários. Esta ação não pode ser desfeita.',
                'Sim, Reprovar Todos',
                'Cancelar',
                function okCb() {
                    Notiflix.Loading.standard('Reprovando todos os saques...');
                    return true;
                },
                function cancelCb() {
                    return false;
                },
                {
                    width: '320px',
                    borderRadius: '8px',
                    titleColor: '#ffffff',
                    messageColor: '#cccccc',
                    okButtonBackground: '#ef4444',
                    okButtonColor: '#ffffff',
                    cancelButtonBackground: '#6b7280',
                    cancelButtonColor: '#ffffff',
                    backgroundColor: '#1a1a1a',
                    backOverlayColor: 'rgba(0,0,0,0.8)',
                }
            );
        }
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

        #content-admin {
            padding: 24px 42px; /* Padding padrão para desktop */
            margin-left: 280px; /* Espaço para o sidebar em desktop */
        }
        @media screen and (max-width: 1023px) { /* Ajuste para telas menores que 'lg' (1024px) */
            #content-admin {
                padding: 24px 20px; /* Padding menor em mobile */
                margin-left: 0; /* Ocupa a largura total em mobile */
            }
        }

        /* Melhorias na paginação */
        .pagination-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Responsividade para filtros */
        @media (max-width: 640px) {
            .filter-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .filter-buttons a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</body>
</html>
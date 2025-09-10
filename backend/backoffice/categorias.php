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

// Adicionar categoria
if (isset($_POST['adicionar_categoria'])) {
    $nome = $_POST['nome'];
    $slug = strtolower(str_replace(' ', '-', $nome));
    $icone = $_POST['icone'];
    $cor = $_POST['cor'];
    $ordem = $_POST['ordem'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO categorias (nome, slug, icone, cor, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$nome, $slug, $icone, $cor, $ordem, $ativo])) {
        $_SESSION['success'] = 'Categoria adicionada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao adicionar categoria!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Editar categoria
if (isset($_POST['editar_categoria'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $slug = strtolower(str_replace(' ', '-', $nome));
    $icone = $_POST['icone'];
    $cor = $_POST['cor'];
    $ordem = $_POST['ordem'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, slug = ?, icone = ?, cor = ?, ordem = ?, ativo = ? WHERE id = ?");
    if ($stmt->execute([$nome, $slug, $icone, $cor, $ordem, $ativo, $id])) {
        $_SESSION['success'] = 'Categoria atualizada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar categoria!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Excluir categoria
if (isset($_GET['excluir_categoria'])) {
    $id = $_GET['id'];

    // Verificar se há raspadinhas usando esta categoria
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM raspadinhas WHERE categoria_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['failure'] = 'Não é possível excluir esta categoria pois existem raspadinhas vinculadas a ela!';
    } else {
        if ($pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id])) {
            $_SESSION['success'] = 'Categoria excluída com sucesso!';
        } else {
            $_SESSION['failure'] = 'Erro ao excluir categoria!';
        }
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Buscar categorias
$categorias = $pdo->query("SELECT 
    c.id, c.nome, c.slug, c.icone, c.cor, c.ordem, c.ativo, c.created_at, 
    COUNT(r.id) as total_raspadinhas 
FROM categorias c 
LEFT JOIN raspadinhas r ON c.id = r.categoria_id 
GROUP BY 
    c.id, c.nome, c.slug, c.icone, c.cor, c.ordem, c.ativo, c.created_at
ORDER BY c.ordem ASC")->fetchAll(PDO::FETCH_ASSOC);
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
    
    <div id="content-admin" class="w-full lg:w-[calc(100vw-280px)] min-h-[calc(100vh-80px)] flex flex-col gap-8 overflow-y-auto z-5" style="overflow-y: auto !important; margin-top: 80px;">
        <div class="flex flex-col gap-1 mb-8">
            <h1 class="text-white text-3xl font-bold">Gerenciar Categorias</h1>
            <p class="text-gray-400 text-base">Organize as categorias das raspadinhas do sistema</p>
        </div>

        <!-- Formulário para Adicionar/Editar Categoria -->
        <div class="bg-[var(--darker-bg-form)] rounded-lg p-6 shadow-rox">
            <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-6">
                <i class="fas fa-plus mr-2"></i>Adicionar Nova Categoria
            </h2>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Nome da Categoria</label>
                    <input type="text" name="nome" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                           placeholder="Ex: Destaque, PIX na Conta">
                </div>

                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Ícone (Font Awesome)</label>
                    <input type="text" name="icone" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                           placeholder="Ex: fas fa-star, fas fa-pix">
                </div>

                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Cor</label>
                    <input type="color" name="cor" value="#00de93" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200 h-12">
                </div>

                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Ordem de Exibição</label>
                    <input type="number" name="ordem" min="1" value="1" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200">
                </div>

                <div class="flex items-center">
                    <label class="text-gray-300 text-sm flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="ativo" checked class="w-4 h-4 text-[var(--primary-color)] bg-[var(--dark-bg-form)] border-[var(--border-color-input)] rounded focus:ring-[var(--primary-color)]">
                        Categoria Ativa
                    </label>
                </div>

                <div class="flex items-end">
                    <button type="submit" name="adicionar_categoria"
                            class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 w-full transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>Adicionar Categoria
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Categorias -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($categorias as $categoria): ?>
                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: <?= htmlspecialchars($categoria['cor']) ?>20; border: 2px solid <?= htmlspecialchars($categoria['cor']) ?>;">
                                    <i class="<?= htmlspecialchars($categoria['icone']) ?>" style="color: <?= htmlspecialchars($categoria['cor']) ?>;"></i>
                                </div>
                                <h3 class="text-white font-bold text-xl"><?= htmlspecialchars($categoria['nome']) ?></h3>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($categoria['ativo'] == 1): ?>
                                    <span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full">Ativo</span>
                                <?php else: ?>
                                    <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">Inativo</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 text-gray-300 text-sm mb-4">
                            <p class="flex items-center">
                                <i class="fas fa-link mr-2 text-gray-500"></i> 
                                Slug: <span class="text-white ml-1"><?= htmlspecialchars($categoria['slug']) ?></span>
                            </p>
                            <p class="flex items-center">
                                <i class="fas fa-sort-numeric-up mr-2 text-gray-500"></i> 
                                Ordem: <span class="text-white ml-1"><?= $categoria['ordem'] ?></span>
                            </p>
                            <p class="flex items-center">
                                <i class="fas fa-ticket-alt mr-2 text-[var(--card-icon-yellow)]"></i> 
                                Raspadinhas: <span class="text-white ml-1"><?= $categoria['total_raspadinhas'] ?></span>
                            </p>
                            <p class="flex items-center">
                                <i class="fas fa-palette mr-2 text-gray-500"></i> 
                                Cor: 
                                <span class="w-4 h-4 rounded-full ml-2 border border-gray-600" style="background-color: <?= htmlspecialchars($categoria['cor']) ?>;"></span>
                                <span class="text-white ml-1"><?= htmlspecialchars($categoria['cor']) ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-auto pt-4 border-t border-gray-700/50">
                        <button onclick="editarCategoria(<?= htmlspecialchars(json_encode($categoria)) ?>)"
                                class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg p-2 text-sm flex items-center justify-center flex-grow transition-colors duration-200">
                            <i class="fas fa-edit mr-1"></i> Editar
                        </button>
                        <?php if ($categoria['total_raspadinhas'] == 0): ?>
                            <a href="?excluir_categoria&id=<?= $categoria['id'] ?>"
                               onclick="return confirm('Tem certeza que deseja excluir esta categoria?')"
                               class="bg-red-600 hover:bg-red-700 text-white rounded-lg p-2 text-sm flex items-center justify-center flex-grow transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i> Excluir
                            </a>
                        <?php else: ?>
                            <button disabled title="Não é possível excluir categoria com raspadinhas vinculadas"
                                    class="bg-gray-600 text-gray-400 rounded-lg p-2 text-sm flex items-center justify-center flex-grow cursor-not-allowed">
                                <i class="fas fa-lock mr-1"></i> Protegida
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="text-gray-400 text-xs mt-4 pt-2 border-t border-gray-700/50">
                        <i class="fas fa-calendar mr-1"></i> Criada em: <?= date('d/m/Y H:i', strtotime($categoria['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($categorias)): ?>
                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox col-span-full text-center text-gray-300">
                    <i class="fas fa-tags text-4xl mb-4 text-gray-500"></i>
                    <p class="text-lg">Nenhuma categoria encontrada.</p>
                    <p class="text-sm text-gray-400 mt-2">Adicione sua primeira categoria usando o formulário acima.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div id="editarCategoriaModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 w-full max-w-2xl shadow-rox max-h-[90vh] overflow-y-auto">
            <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-4">
                <i class="fas fa-edit mr-2"></i>Editar Categoria
            </h2>
            <form method="POST" id="formEditarCategoria" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="id" id="editId">
                
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Nome da Categoria</label>
                    <input type="text" name="nome" id="editNome" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200">
                </div>

                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Ícone (Font Awesome)</label>
                    <input type="text" name="icone" id="editIcone" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200">
                </div>

                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Cor</label>
                    <input type="color" name="cor" id="editCor" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200 h-12">
                </div>

                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Ordem de Exibição</label>
                    <input type="number" name="ordem" id="editOrdem" min="1" required
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200">
                </div>

                <div class="flex items-center md:col-span-2">
                    <label class="text-gray-300 text-sm flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="ativo" id="editAtivo" class="w-4 h-4 text-[var(--primary-color)] bg-[var(--dark-bg-form)] border-[var(--border-color-input)] rounded focus:ring-[var(--primary-color)]">
                        Categoria Ativa
                    </label>
                </div>

                <div class="flex gap-2 mt-4 md:col-span-2">
                    <button type="submit" name="editar_categoria" class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 flex-1 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                    <button type="button" onclick="fecharModalEdicao()" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg p-3 flex-1 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Incluindo o componente de assinatura NexCode -->
    <?php include 'components/nexcode-signature.php'; ?>

    <script>
        function editarCategoria(categoria) {
            document.getElementById('editId').value = categoria.id;
            document.getElementById('editNome').value = categoria.nome;
            document.getElementById('editIcone').value = categoria.icone;
            document.getElementById('editCor').value = categoria.cor;
            document.getElementById('editOrdem').value = categoria.ordem;
            document.getElementById('editAtivo').checked = categoria.ativo == 1;
            document.getElementById('editarCategoriaModal').classList.remove('hidden');
        }

        function fecharModalEdicao() {
            document.getElementById('editarCategoriaModal').classList.add('hidden');
        }

        // Fechar modal ao clicar fora dele
        document.getElementById('editarCategoriaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalEdicao();
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
    </style>
</body>
</html>
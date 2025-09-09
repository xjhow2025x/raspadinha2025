<?php
session_start();
require_once '../conexao.php';

// Verificar se o usuário está logado (adicione sua lógica de autenticação aqui)
// if (!isset($_SESSION['admin_logged_in'])) {
//     header('Location: login.php');
//     exit;
// }

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_story':
            $titulo = $_POST['titulo'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $thumbnail = $_POST['thumbnail'] ?? '';
            $ordem = (int)($_POST['ordem'] ?? 0);
            
            if (!empty($titulo)) {
                $sql = "INSERT INTO stories_new (titulo, descricao, thumbnail, ordem) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titulo, $descricao, $thumbnail, $ordem]);
                $success = "Story criado com sucesso!";
            } else {
                $error = "Título é obrigatório!";
            }
            break;
            
        case 'add_media':
            $story_id = (int)($_POST['story_id'] ?? 0);
            $tipo = $_POST['tipo'] ?? 'foto';
            $arquivo = $_POST['arquivo'] ?? '';
            $thumbnail = $_POST['thumbnail'] ?? '';
            $ordem = (int)($_POST['ordem'] ?? 0);
            $duracao = (int)($_POST['duracao'] ?? 5000);
            
            if ($story_id > 0 && !empty($arquivo)) {
                $sql = "INSERT INTO story_media (story_id, tipo, arquivo, thumbnail, ordem, duracao) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$story_id, $tipo, $arquivo, $thumbnail, $ordem, $duracao]);
                $success = "Mídia adicionada com sucesso!";
            } else {
                $error = "Story ID e arquivo são obrigatórios!";
            }
            break;
            
        case 'edit_story':
            $id = (int)($_POST['id'] ?? 0);
            $titulo = $_POST['titulo'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $thumbnail = $_POST['thumbnail'] ?? '';
            $ordem = (int)($_POST['ordem'] ?? 0);
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            if ($id > 0 && !empty($titulo)) {
                $sql = "UPDATE stories_new SET titulo = ?, descricao = ?, thumbnail = ?, ordem = ?, ativo = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titulo, $descricao, $thumbnail, $ordem, $ativo, $id]);
                $success = "Story atualizado com sucesso!";
            } else {
                $error = "Dados inválidos!";
            }
            break;
            
        case 'delete_story':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Deletar mídias primeiro (CASCADE deve fazer isso automaticamente)
                $sql = "DELETE FROM stories_new WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $success = "Story deletado com sucesso!";
            }
            break;
            
        case 'delete_media':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $sql = "DELETE FROM story_media WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $success = "Mídia deletada com sucesso!";
            }
            break;
            
        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $sql = "UPDATE stories_new SET ativo = NOT ativo WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $success = "Status alterado com sucesso!";
            }
            break;
    }
}

// Buscar stories com suas mídias
try {
    $sql_stories = "SELECT * FROM stories_new ORDER BY ordem ASC, created_at DESC";
    $stmt_stories = $pdo->prepare($sql_stories);
    $stmt_stories->execute();
    $stories = $stmt_stories->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar mídias para cada story
    foreach ($stories as &$story) {
        $sql_media = "SELECT * FROM story_media WHERE story_id = ? ORDER BY ordem ASC";
        $stmt_media = $pdo->prepare($sql_media);
        $stmt_media->execute([$story['id']]);
        $story['media'] = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $stories = [];
    $error = "Erro ao carregar stories: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Stories - Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                <i class="fas fa-images mr-2"></i>
                Gerenciar Stories
            </h1>
            
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Botão para adicionar novo story -->
            <div class="mb-6">
                <button onclick="openModal('addStoryModal')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>
                    Novo Story
                </button>
            </div>
            
            <!-- Lista de Stories -->
            <div class="space-y-6">
                <?php foreach ($stories as $story): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <img src="<?= htmlspecialchars($story['thumbnail'] ?: 'https://via.placeholder.com/60x60/cccccc/666666?text=Story') ?>" 
                                     alt="<?= htmlspecialchars($story['titulo']) ?>" 
                                     class="w-16 h-16 rounded-full object-cover">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($story['titulo']) ?></h3>
                                    <p class="text-gray-600"><?= htmlspecialchars($story['descricao'] ?: 'Sem descrição') ?></p>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 mt-1">
                                        <span><i class="fas fa-sort-numeric-up mr-1"></i>Ordem: <?= $story['ordem'] ?></span>
                                        <span><i class="fas fa-eye mr-1"></i>Visualizações: <?= $story['visualizacoes'] ?></span>
                                        <span><i class="fas fa-images mr-1"></i>Mídias: <?= count($story['media']) ?></span>
                                        <span class="<?= $story['ativo'] ? 'text-green-600' : 'text-red-600' ?>">
                                            <i class="fas <?= $story['ativo'] ? 'fa-check-circle' : 'fa-times-circle' ?> mr-1"></i>
                                            <?= $story['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="openAddMediaModal(<?= $story['id'] ?>, '<?= htmlspecialchars($story['titulo']) ?>')" 
                                        class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-plus mr-1"></i>Mídia
                                </button>
                                <button onclick="editStory(<?= htmlspecialchars(json_encode($story)) ?>)" 
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-edit mr-1"></i>Editar
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Tem certeza?')">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $story['id'] ?>">
                                    <button type="submit" class="<?= $story['ativo'] ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600' ?> text-white px-3 py-1 rounded text-sm">
                                        <i class="fas <?= $story['ativo'] ? 'fa-pause' : 'fa-play' ?> mr-1"></i>
                                        <?= $story['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                                <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja deletar este story e todas suas mídias?')">
                                    <input type="hidden" name="action" value="delete_story">
                                    <input type="hidden" name="id" value="<?= $story['id'] ?>">
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                        <i class="fas fa-trash mr-1"></i>Deletar
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Mídias do Story -->
                        <?php if (!empty($story['media'])): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                                <?php foreach ($story['media'] as $media): ?>
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium <?= $media['tipo'] === 'video' ? 'text-red-600' : 'text-blue-600' ?>">
                                                <i class="fas <?= $media['tipo'] === 'video' ? 'fa-video' : 'fa-image' ?> mr-1"></i>
                                                <?= ucfirst($media['tipo']) ?>
                                            </span>
                                            <form method="POST" class="inline" onsubmit="return confirm('Deletar esta mídia?')">
                                                <input type="hidden" name="action" value="delete_media">
                                                <input type="hidden" name="id" value="<?= $media['id'] ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <img src="<?= htmlspecialchars($media['thumbnail'] ?: $media['arquivo']) ?>" 
                                             alt="Mídia" 
                                             class="w-full h-20 object-cover rounded mb-2">
                                        <div class="text-xs text-gray-600">
                                            <div>Ordem: <?= $media['ordem'] ?></div>
                                            <div>Duração: <?= $media['duracao'] ?>ms</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-gray-500 text-center py-4">
                                <i class="fas fa-images text-2xl mb-2"></i>
                                <p>Nenhuma mídia adicionada ainda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($stories)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-images text-4xl mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Nenhum story encontrado</h3>
                        <p>Clique em "Novo Story" para começar</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal para adicionar story -->
    <div id="addStoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h2 class="text-xl font-bold mb-4">Novo Story</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_story">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                    <input type="text" name="titulo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail (URL)</label>
                    <input type="url" name="thumbnail" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ordem</label>
                    <input type="number" name="ordem" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addStoryModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        Criar Story
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para adicionar mídia -->
    <div id="addMediaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h2 class="text-xl font-bold mb-4">Adicionar Mídia</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_media">
                <input type="hidden" name="story_id" id="mediaStoryId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Story: <span id="mediaStoryTitle" class="font-bold"></span></label>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                    <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="foto">Foto</option>
                        <option value="video">Vídeo</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL do Arquivo *</label>
                    <input type="url" name="arquivo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail (URL)</label>
                    <input type="url" name="thumbnail" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Duração (ms)</label>
                    <input type="number" name="duracao" value="5000" min="1000" max="30000" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <small class="text-gray-500">Fotos: 3000-6000ms, Vídeos: 8000-15000ms</small>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ordem</label>
                    <input type="number" name="ordem" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addMediaModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        Adicionar Mídia
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para editar story -->
    <div id="editStoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h2 class="text-xl font-bold mb-4">Editar Story</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_story">
                <input type="hidden" name="id" id="editStoryId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                    <input type="text" name="titulo" id="editStoryTitulo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" id="editStoryDescricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail (URL)</label>
                    <input type="url" name="thumbnail" id="editStoryThumbnail" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ordem</label>
                    <input type="number" name="ordem" id="editStoryOrdem" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="ativo" id="editStoryAtivo" class="mr-2">
                        <span class="text-sm font-medium text-gray-700">Story ativo</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editStoryModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        function openAddMediaModal(storyId, storyTitle) {
            document.getElementById('mediaStoryId').value = storyId;
            document.getElementById('mediaStoryTitle').textContent = storyTitle;
            openModal('addMediaModal');
        }
        
        function editStory(story) {
            document.getElementById('editStoryId').value = story.id;
            document.getElementById('editStoryTitulo').value = story.titulo;
            document.getElementById('editStoryDescricao').value = story.descricao || '';
            document.getElementById('editStoryThumbnail').value = story.thumbnail || '';
            document.getElementById('editStoryOrdem').value = story.ordem;
            document.getElementById('editStoryAtivo').checked = story.ativo == 1;
            openModal('editStoryModal');
        }
        
        // Fechar modais com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('addStoryModal');
                closeModal('addMediaModal');
                closeModal('editStoryModal');
            }
        });
    </script>
</body>
</html>
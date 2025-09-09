<?php
include '../includes/session.php';
include '../conexao.php';
include '../includes/notiflix.php';

$usuarioId = $_SESSION['usuario_id'];
$admin = ($stmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;

if ($admin != 1) {
    echo "<script>localStorage.setItem('failure', 'Você não é Administrador!'); window.location.href='/home';</script>";
    exit;
}

// Adicionar banner
if (isset($_POST['adicionar_banner'])) {
    $titulo = $_POST['titulo'];
    $link = $_POST['link'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $tipo = $_POST['tipo'] ?? 'geral';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $ordem = !empty($_POST['ordem']) ? (int)$_POST['ordem'] : 0;
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

    $imagem = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/banners/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newName = 'banner_' . uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
                $imagem = '/assets/img/banners/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload da imagem!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG, PNG, GIF ou WebP.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $_SESSION['failure'] = 'Por favor, selecione uma imagem para o banner!';
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO banners (titulo, imagem, link, descricao, tipo, ativo, ordem, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$titulo, $imagem, $link, $descricao, $tipo, $ativo, $ordem, $data_inicio, $data_fim])) {
        $_SESSION['success'] = 'Banner adicionado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao adicionar banner!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Editar banner
if (isset($_POST['editar_banner'])) {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $link = $_POST['link'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $tipo = $_POST['tipo'] ?? 'geral';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $ordem = !empty($_POST['ordem']) ? (int)$_POST['ordem'] : 0;
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

    // Buscar banner atual
    $banner = $pdo->prepare("SELECT imagem FROM banners WHERE id = ?");
    $banner->execute([$id]);
    $banner = $banner->fetch(PDO::FETCH_ASSOC);
    $imagem = $banner['imagem'];

    // Upload de nova imagem se fornecida
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/banners/';
            $newName = 'banner_' . uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
                // Remover imagem antiga
                if ($imagem && file_exists('../' . $imagem)) {
                    unlink('../' . $imagem);
                }
                $imagem = '/assets/img/banners/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload da nova imagem!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG, PNG, GIF ou WebP.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE banners SET titulo = ?, imagem = ?, link = ?, descricao = ?, tipo = ?, ativo = ?, ordem = ?, data_inicio = ?, data_fim = ? WHERE id = ?");
    if ($stmt->execute([$titulo, $imagem, $link, $descricao, $tipo, $ativo, $ordem, $data_inicio, $data_fim, $id])) {
        $_SESSION['success'] = 'Banner atualizado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar banner!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Excluir banner
if (isset($_GET['excluir_banner'])) {
    $id = $_GET['id'];

    // Buscar imagem para excluir
    $banner = $pdo->prepare("SELECT imagem FROM banners WHERE id = ?");
    $banner->execute([$id]);
    $banner = $banner->fetch(PDO::FETCH_ASSOC);

    if ($pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id])) {
        // Remover arquivo de imagem
        if ($banner['imagem'] && file_exists('../' . $banner['imagem'])) {
            unlink('../' . $banner['imagem']);
        }
        $_SESSION['success'] = 'Banner excluído com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao excluir banner!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Buscar banners
$banners = $pdo->query("SELECT * FROM banners ORDER BY ordem ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Banners - Admin</title>
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
            --primary-color: #00de93;
            --secondary-color: #00de93;
            --tertiary-color: #df2dbb;
            --bg-color: #13151b;
            --support-color: #13151b;
            --dark-bg-form: #1a1a1a;
            --darker-bg-form: #222222;
            --text-gray-light: #cccccc;
            --text-green-accent: #00de93;
            --border-color-input: #333333;
            --border-color-active: #00de93;
            --admin-bg: #0f1419;
            --admin-sidebar-bg: #1a1a1a;
            --admin-text-color: #ffffff;
            --admin-link-color: #cccccc;
            --admin-link-hover: #ffffff;
            --admin-active-link-bg: rgba(0, 222, 147, 0.1);
        }

        html, body {
            padding: 0;
            margin: 0;
            height: 100%;
        }

        body {
            background-color: var(--admin-bg);
            color: var(--admin-text-color);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-content {
            flex: 1;
            margin-left: 250px;
            padding: 24px;
        }

        .admin-card {
            background-color: var(--admin-sidebar-bg);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #333;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--admin-text-color);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            background-color: var(--dark-bg-form);
            border: 1px solid var(--border-color-input);
            border-radius: 6px;
            color: white;
            font-size: 14px;
        }

        .form-input:focus {
            border-color: var(--border-color-active);
            outline: none;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: black;
        }

        .btn-primary:hover {
            background-color: #00c085;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .table th {
            background-color: var(--darker-bg-form);
            font-weight: 600;
        }

        /* Estilos responsivos para banners */
        .banner-preview {
            max-width: 100px;
            max-height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .status-inactive {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background-color: rgba(0, 222, 147, 0.2);
            color: #00de93;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
                padding: 16px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include('./components/header.php'); ?>

    <div class="admin-container">
        <div class="admin-content">
            <h1 class="text-2xl font-bold mb-6">Gerenciar Banners</h1>

            <!-- Formulário para adicionar banner -->
            <div class="admin-card">
                <h2 class="text-xl font-semibold mb-4">Adicionar Novo Banner</h2>
                
                <!-- Informações sobre tipos de banner -->
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mb-6">
                    <h3 class="text-blue-500 font-medium mb-2">Tipos de Banner Disponíveis:</h3>
                    <ul class="text-gray-300 text-sm space-y-1">
                        <li><strong>Geral:</strong> Exibido em páginas gerais do site</li>
                        <li><strong>Depósito:</strong> Exibido no modal de depósito</li>
                        <li><strong>Login:</strong> Exibido na página de login</li>
                        <li><strong>Cadastro:</strong> Exibido na página de cadastro</li>
                        <li><strong>QR Code (Timer):</strong> Exibido quando o QR code de pagamento é gerado (com timer de 5 minutos)</li>
                    </ul>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tipo do Banner</label>
                            <select name="tipo" class="form-input">
                                <option value="geral">Geral</option>
                                <option value="deposito">Depósito</option>
                                <option value="login">Login</option>
                                <option value="cadastro">Cadastro</option>
                                <option value="qrcode">QR Code (Timer)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Link (opcional)</label>
                            <input type="url" name="link" class="form-input" placeholder="https://exemplo.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="ordem" class="form-input" value="0" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Imagem do Banner *</label>
                            <input type="file" name="imagem" class="form-input" accept="image/*" required>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="ativo" id="ativo" checked>
                                <label for="ativo" class="form-label mb-0">Banner Ativo</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Data de Início (opcional)</label>
                            <input type="datetime-local" name="data_inicio" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Data de Fim (opcional)</label>
                            <input type="datetime-local" name="data_fim" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea name="descricao" class="form-input" rows="3" placeholder="Descrição do banner"></textarea>
                    </div>

                    <!-- Nota sobre banner de QR Code -->
                    <div id="qrcodeNote" class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3 mt-4 hidden">
                        <div class="flex items-start">
                            <i class="fa-solid fa-lightbulb text-yellow-500 mt-0.5 mr-2"></i>
                            <div>
                                <p class="text-yellow-500 text-sm font-medium mb-1">Banner de QR Code</p>
                                <p class="text-gray-300 text-xs">Este banner será exibido quando um QR code de pagamento for gerado. Ele aparecerá junto com um timer de 5 minutos para o pagamento.</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="adicionar_banner" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Adicionar Banner
                    </button>
                </form>
            </div>

            <!-- Lista de banners -->
            <div class="admin-card">
                <h2 class="text-xl font-semibold mb-4">Banners Cadastrados</h2>
                
                <?php if (empty($banners)): ?>
                    <p class="text-gray-400">Nenhum banner cadastrado ainda.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Link</th>
                                    <th>Status</th>
                                    <th>Ordem</th>
                                    <th>Período</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banners as $banner): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= htmlspecialchars($banner['imagem']); ?>" 
                                                 alt="<?= htmlspecialchars($banner['titulo']); ?>" 
                                                 class="banner-preview">
                                        </td>
                                        <td><?= htmlspecialchars($banner['titulo']); ?></td>
                                        <td>
                                            <span class="type-badge">
                                                <?= ucfirst(htmlspecialchars($banner['tipo'] ?? 'geral')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($banner['link']): ?>
                                                <a href="<?= htmlspecialchars($banner['link']); ?>" target="_blank" class="text-blue-400 hover:text-blue-300">
                                                    <i class="fa-solid fa-external-link-alt"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $banner['ativo'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?= $banner['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td><?= $banner['ordem']; ?></td>
                                        <td class="text-sm">
                                            <?php if ($banner['data_inicio'] || $banner['data_fim']): ?>
                                                <?= $banner['data_inicio'] ? date('d/m/Y', strtotime($banner['data_inicio'])) : '∞'; ?> - 
                                                <?= $banner['data_fim'] ? date('d/m/Y', strtotime($banner['data_fim'])) : '∞'; ?>
                                            <?php else: ?>
                                                <span class="text-gray-500">Permanente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="editarBanner(<?= htmlspecialchars(json_encode($banner)); ?>)" 
                                                    class="btn btn-secondary btn-sm mr-2">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <a href="?excluir_banner=1&id=<?= $banner['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Tem certeza que deseja excluir este banner?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para editar banner -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-[var(--admin-sidebar-bg)] rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-semibold mb-4">Editar Banner</h3>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" id="edit_titulo" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipo do Banner</label>
                        <select name="tipo" id="edit_tipo" class="form-input">
                            <option value="geral">Geral</option>
                                <option value="deposito">Depósito</option>
                                <option value="login">Login</option>
                                <option value="cadastro">Cadastro</option>
                                <option value="qrcode">QR Code (Timer)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Link (opcional)</label>
                        <input type="url" name="link" id="edit_link" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ordem</label>
                        <input type="number" name="ordem" id="edit_ordem" class="form-input" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nova Imagem (opcional)</label>
                        <input type="file" name="imagem" class="form-input" accept="image/*">
                        <small class="text-gray-400">Deixe em branco para manter a imagem atual</small>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="ativo" id="edit_ativo">
                            <label for="edit_ativo" class="form-label mb-0">Banner Ativo</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Início (opcional)</label>
                        <input type="datetime-local" name="data_inicio" id="edit_data_inicio" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Fim (opcional)</label>
                        <input type="datetime-local" name="data_fim" id="edit_data_fim" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição (opcional)</label>
                    <textarea name="descricao" id="edit_descricao" class="form-input" rows="3"></textarea>
                </div>

                <!-- Nota sobre banner de QR Code (modal de edição) -->
                <div id="editQrcodeNote" class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3 mt-4 hidden">
                    <div class="flex items-start">
                        <i class="fa-solid fa-lightbulb text-yellow-500 mt-0.5 mr-2"></i>
                        <div>
                            <p class="text-yellow-500 text-sm font-medium mb-1">Banner de QR Code</p>
                            <p class="text-gray-300 text-xs">Este banner será exibido quando um QR code de pagamento for gerado. Ele aparecerá junto com um timer de 5 minutos para o pagamento.</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 mt-6">
                    <button type="submit" name="editar_banner" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Salvar Alterações
                    </button>
                    <button type="button" onclick="fecharModal()" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editarBanner(banner) {
            document.getElementById('edit_id').value = banner.id;
            document.getElementById('edit_titulo').value = banner.titulo;
            document.getElementById('edit_tipo').value = banner.tipo || 'geral';
            document.getElementById('edit_link').value = banner.link || '';
            document.getElementById('edit_ordem').value = banner.ordem;
            document.getElementById('edit_descricao').value = banner.descricao || '';
            document.getElementById('edit_ativo').checked = banner.ativo == 1;
            
            // Formatar datas para datetime-local
            if (banner.data_inicio) {
                const dataInicio = new Date(banner.data_inicio);
                document.getElementById('edit_data_inicio').value = dataInicio.toISOString().slice(0, 16);
            }
            if (banner.data_fim) {
                const dataFim = new Date(banner.data_fim);
                document.getElementById('edit_data_fim').value = dataFim.toISOString().slice(0, 16);
            }
            
            // Mostrar/ocultar nota sobre QR Code baseado no tipo
            const qrcodeNote = document.getElementById('editQrcodeNote');
            if (banner.tipo === 'qrcode') {
                qrcodeNote.classList.remove('hidden');
            } else {
                qrcodeNote.classList.add('hidden');
            }
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function fecharModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        // Fechar modal ao clicar fora
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        // Mostrar/ocultar nota sobre banner de QR Code
        document.querySelector('select[name="tipo"]').addEventListener('change', function() {
            const qrcodeNote = document.getElementById('qrcodeNote');
            if (this.value === 'qrcode') {
                qrcodeNote.classList.remove('hidden');
            } else {
                qrcodeNote.classList.add('hidden');
            }
        });

        // Mostrar/ocultar nota no modal de edição
        document.getElementById('edit_tipo').addEventListener('change', function() {
            const qrcodeNote = document.getElementById('editQrcodeNote');
            if (this.value === 'qrcode') {
                qrcodeNote.classList.remove('hidden');
            } else {
                qrcodeNote.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
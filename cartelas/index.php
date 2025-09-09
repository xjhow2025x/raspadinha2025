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

if (isset($_POST['adicionar_raspadinha'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']);

    $banner = '';
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/banners/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['banner']['tmp_name'], $uploadPath)) {
                $banner = '/assets/img/banners/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do banner!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO raspadinhas (nome, descricao, banner, valor) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nome, $descricao, $banner, $valor])) {
        $_SESSION['success'] = 'Raspadinha adicionada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao adicionar raspadinha!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['editar_raspadinha'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']);

    $raspadinha = $pdo->prepare("SELECT banner FROM raspadinhas WHERE id = ?");
    $raspadinha->execute([$id]);
    $raspadinha = $raspadinha->fetch(PDO::FETCH_ASSOC);
    $banner = $raspadinha['banner'];

    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/banners/';
            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['banner']['tmp_name'], $uploadPath)) {
                if ($banner && file_exists('../' . $banner)) {
                    unlink('../' . $banner);
                }
                $banner = '/assets/img/banners/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do novo banner!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE raspadinhas SET nome = ?, descricao = ?, banner = ?, valor = ? WHERE id = ?");
    if ($stmt->execute([$nome, $descricao, $banner, $valor, $id])) {
        $_SESSION['success'] = 'Raspadinha atualizada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar raspadinha!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['excluir_raspadinha'])) {
    $id = $_GET['id'];

    $raspadinha = $pdo->prepare("SELECT banner FROM raspadinhas WHERE id = ?");
    $raspadinha->execute([$id]);
    $raspadinha = $raspadinha->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare("DELETE FROM raspadinha_premios WHERE raspadinha_id = ?")->execute([$id]);

    if ($pdo->prepare("DELETE FROM raspadinhas WHERE id = ?")->execute([$id])) {
        if ($raspadinha['banner'] && file_exists('../' . $raspadinha['banner'])) {
            unlink('../' . $raspadinha['banner']);
        }
        $_SESSION['success'] = 'Raspadinha excluída com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao excluir raspadinha!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['adicionar_premio'])) {
    $raspadinha_id = $_POST['raspadinha_id'];
    $nome = $_POST['nome'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $probabilidade = str_replace(',', '.', $_POST['probabilidade']);

    $icone = '';
    if (isset($_FILES['icone']) && $_FILES['icone']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['icone']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/icons/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['icone']['tmp_name'], $uploadPath)) {
                $icone = '/assets/img/icons/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do ícone!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO raspadinha_premios (raspadinha_id, nome, icone, valor, probabilidade) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$raspadinha_id, $nome, $icone, $valor, $probabilidade])) {
        $_SESSION['success'] = 'Prêmio adicionado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao adicionar prêmio!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['editar_premio'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $probabilidade = str_replace(',', '.', $_POST['probabilidade']);

    $premio = $pdo->prepare("SELECT icone FROM raspadinha_premios WHERE id = ?");
    $premio->execute([$id]);
    $premio = $premio->fetch(PDO::FETCH_ASSOC);
    $icone = $premio['icone'];

    if (isset($_FILES['icone']) && $_FILES['icone']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['icone']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/icons/';
            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['icone']['tmp_name'], $uploadPath)) {
                if ($icone && file_exists('../' . $icone)) {
                    unlink('../' . $icone);
                }
                $icone = '/assets/img/icons/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do novo ícone!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE raspadinha_premios SET nome = ?, icone = ?, valor = ?, probabilidade = ? WHERE id = ?");
    if ($stmt->execute([$nome, $icone, $valor, $probabilidade, $id])) {
        $_SESSION['success'] = 'Prêmio atualizado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar prêmio!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['excluir_premio'])) {
    $id = $_GET['id'];

    $premio = $pdo->prepare("SELECT icone FROM raspadinha_premios WHERE id = ?");
    $premio->execute([$id]);
    $premio = $premio->fetch(PDO::FETCH_ASSOC);

    if ($pdo->prepare("DELETE FROM raspadinha_premios WHERE id = ?")->execute([$id])) {
        if ($premio['icone'] && file_exists('../' . $premio['icone'])) {
            unlink('../' . $premio['icone']);
        }
        $_SESSION['success'] = 'Prêmio excluído com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao excluir prêmio!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

$raspadinhas = $pdo->query("SELECT * FROM raspadinhas ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$premios = [];
if (isset($_GET['raspadinha_id'])) {
    $raspadinha_id = $_GET['raspadinha_id'];
    $premios = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE raspadinha_id = ? ORDER BY probabilidade DESC");
    $premios->execute([$raspadinha_id]);
    $premios = $premios->fetchAll(PDO::FETCH_ASSOC);
}
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
    <style>
        /* Adicionado as variáveis CSS para consistência de cores */
        :root {
            --primary-color: #00de93; /* Verde principal */
            --secondary-color: #00de93;
            --tertiary-color: #00de93;
            --bg-color: #13151b; /* Fundo principal do admin */
            --support-color: #00de93;
            --dark-bg-form: #1a1a1a; /* Cor de fundo dos inputs */
            --darker-bg-form: #222222; /* Cor de fundo do container do formulário */
            --text-gray-light: #cccccc;
            --text-green-accent: #00de93;
            --border-color-input: #333333; /* Cor da borda dos inputs */
            --border-color-active: #00de93; /* Cor da borda ativa/selecionada */

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

        /* Custom file input styling */
        .custom-file-input {
            display: flex;
            align-items: center;
            gap: 1rem;
            background-color: var(--dark-bg-form);
            color: var(--text-gray-light);
            border: 1px solid var(--border-color-input);
            border-radius: 0.5rem; /* rounded-lg */
            padding: 0.75rem; /* p-3 */
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .custom-file-input:hover {
            border-color: var(--border-color-active);
        }

        .custom-file-input input[type="file"] {
            display: none; /* Hide default file input */
        }

        .custom-file-input .file-button {
            background-color: var(--primary-color);
            color: black;
            font-weight: 600; /* font-semibold */
            padding: 0.5rem 1rem; /* py-2 px-4 */
            border-radius: 9999px; /* rounded-full */
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .custom-file-input .file-button:hover {
            background-color: var(--admin-link-hover);
        }

        .custom-file-input .file-name {
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
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
            <h1 class="text-white text-3xl font-bold">Gerenciar Raspadinhas</h1>
            <p class="text-gray-400 text-base">Adicione, edite ou remova raspadinhas e seus prêmios</p>
        </div>

        <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Card: Adicionar/Editar Raspadinha -->
            <div class="bg-[var(--darker-bg-form)] rounded-lg p-6 shadow-lg border border-gray-700/50">
                <h2 class="text-white text-xl font-semibold border-b border-gray-700/50 pb-4 mb-6">
                    <?= isset($_GET['editar_raspadinha']) ? 'Editar' : 'Adicionar' ?> Raspadinha
                </h2>

                <?php
                $raspadinha_edit = null;
                if (isset($_GET['editar_raspadinha'])) {
                    $id = $_GET['id'];
                    $raspadinha_edit = $pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
                    $raspadinha_edit->execute([$id]);
                    $raspadinha_edit = $raspadinha_edit->fetch(PDO::FETCH_ASSOC);
                }
                ?>

                <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                    <?php if ($raspadinha_edit): ?>
                        <input type="hidden" name="id" value="<?= $raspadinha_edit['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label for="nome_raspadinha" class="text-gray-300 text-sm mb-2 block">Nome</label>
                        <input type="text" id="nome_raspadinha" name="nome" value="<?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['nome']) : '' ?>"
                               class="bg-gray-800 text-white rounded-md px-4 py-2 w-full border border-gray-700 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200" required>
                    </div>

                    <div>
                        <label for="descricao_raspadinha" class="text-gray-300 text-sm mb-2 block">Descrição</label>
                        <textarea id="descricao_raspadinha" name="descricao" rows="3"
                                  class="bg-gray-800 text-white rounded-md px-4 py-2 w-full border border-gray-700 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200" required><?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['descricao']) : '' ?></textarea>
                    </div>

                    <div>
                        <label for="valor_raspadinha" class="text-gray-300 text-sm mb-2 block">Valor (R$)</label>
                        <input type="text" id="valor_raspadinha" name="valor" value="<?= $raspadinha_edit ? htmlspecialchars(number_format($raspadinha_edit['valor'], 2, ',', '.')) : '' ?>"
                               class="bg-gray-800 text-white rounded-md px-4 py-2 w-full border border-gray-700 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200" required>
                    </div>

                    <div>
                        <label for="banner_raspadinha" class="text-gray-300 text-sm mb-2 block">Banner</label>
                        <label for="banner_raspadinha" class="custom-file-input">
                            <span class="file-button">Escolher ficheiro</span>
                            <span class="file-name" id="banner-file-name"><?= $raspadinha_edit && $raspadinha_edit['banner'] ? basename($raspadinha_edit['banner']) : 'Nenhum ficheiro selecionado' ?></span>
                            <input type="file" id="banner_raspadinha" name="banner" accept="image/jpeg, image/png" onchange="document.getElementById('banner-file-name').textContent = this.files[0] ? this.files[0].name : 'Nenhum ficheiro selecionado'">
                        </label>
                        <?php if ($raspadinha_edit && $raspadinha_edit['banner']): ?>
                            <div class="mt-4 flex items-center gap-3">
                                <p class="text-gray-300 text-sm">Banner atual:</p>
                                <img src="<?= htmlspecialchars($raspadinha_edit['banner']) ?>" alt="Banner atual" class="h-16 w-auto object-contain rounded-md border border-gray-700 p-1">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="<?= $raspadinha_edit ? 'editar_raspadinha' : 'adicionar_raspadinha' ?>"
                            class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-bold rounded-md py-3 px-4 mt-4 transition-colors duration-200 text-lg">
                        <?= $raspadinha_edit ? 'Atualizar' : 'Adicionar' ?> Raspadinha
                    </button>

                    <?php if ($raspadinha_edit): ?>
                        <a href="?" class="block text-center mt-3 text-gray-400 hover:text-white transition-colors duration-200 text-sm">Cancelar Edição</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Card: Raspadinhas Cadastradas -->
            <div class="bg-[var(--darker-bg-form)] rounded-lg p-6 shadow-lg border border-gray-700/50">
                <h2 class="text-white text-xl font-semibold border-b border-gray-700/50 pb-4 mb-6">Raspadinhas Cadastradas</h2>

                <div class="overflow-x-auto max-h-[500px] lg:max-h-[600px]">
                    <?php if (empty($raspadinhas)): ?>
                        <p class="text-gray-300 text-center py-8">Nenhuma raspadinha cadastrada.</p>
                    <?php else: ?>
                        <table class="w-full text-white table-auto min-w-[500px]">
                            <thead>
                                <tr class="border-b border-gray-700/50 text-gray-400 uppercase text-sm">
                                    <th class="text-left py-3 px-4">Nome</th>
                                    <th class="text-left py-3 px-4">Valor</th>
                                    <th class="text-right py-3 px-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($raspadinhas as $raspadinha): ?>
                                    <tr class="border-b border-gray-700/30 hover:bg-gray-800 transition-colors duration-200">
                                        <td class="py-3 px-4">
                                            <a href="?raspadinha_id=<?= $raspadinha['id'] ?>" class="text-white hover:text-[var(--primary-color)] transition-colors duration-200 font-medium">
                                                <?= htmlspecialchars($raspadinha['nome']) ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 text-white">R$ <?= number_format($raspadinha['valor'], 2, ',', '.') ?></td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex justify-end gap-3">
                                                <a href="?editar_raspadinha&id=<?= $raspadinha['id'] ?>" class="text-blue-400 hover:text-blue-300 transition-colors duration-200 text-sm font-medium">Editar</a>
                                                <a href="?excluir_raspadinha&id=<?= $raspadinha['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta raspadinha e todos os seus prêmios?')" class="text-red-400 hover:text-red-300 transition-colors duration-200 text-sm font-medium">Excluir</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['raspadinha_id'])): ?>
            <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                <!-- Card: Adicionar/Editar Prêmio -->
                <div class="bg-[var(--darker-bg-form)] rounded-lg p-6 shadow-lg border border-gray-700/50">
                    <h2 class="text-white text-xl font-semibold border-b border-gray-700/50 pb-4 mb-6">
                        <?= isset($_GET['editar_premio']) ? 'Editar' : 'Adicionar' ?> Prêmio
                    </h2>

                    <?php
                    $premio_edit = null;
                    if (isset($_GET['editar_premio'])) {
                        $id = $_GET['id'];
                        $premio_edit = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE id = ?");
                        $premio_edit->execute([$id]);
                        $premio_edit = $premio_edit->fetch(PDO::FETCH_ASSOC);
                    }
                    ?>

                    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                        <input type="hidden" name="raspadinha_id" value="<?= $_GET['raspadinha_id'] ?>">
                        <?php if ($premio_edit): ?>
                            <input type="hidden" name="id" value="<?= $premio_edit['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label for="nome_premio" class="text-gray-300 text-sm mb-2 block">Nome</label>
                            <input type="text" id="nome_premio" name="nome" value="<?= $premio_edit ? htmlspecialchars($premio_edit['nome']) : '' ?>"
                                   class="bg-gray-800 text-white rounded-md px-4 py-2 w-full border border-gray-700 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200" required>
                        </div>

                        <div>
                            <label for="valor_premio" class="text-gray-300 text-sm mb-2 block">Valor (R$)</label>
                            <input type="text" id="valor_premio" name="valor" value="<?= $premio_edit ? htmlspecialchars(number_format($premio_edit['valor'], 2, ',', '.')) : '' ?>"
                                   class="bg-gray-800 text-white rounded-md px-4 py-2 w-full border border-gray-700 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200" required>
                        </div>

                        <div>
                            <label for="probabilidade_premio" class="text-gray-300 text-sm mb-2 block">Probabilidade (0.00 - 100.00)</label>
                            <input type="text" id="probabilidade_premio" name="probabilidade" value="<?= $premio_edit ? htmlspecialchars(number_format($premio_edit['probabilidade'], 2, ',', '.')) : '' ?>"
                                   class="bg-gray-800 text-white rounded-md px-4 py-2 w-full border border-gray-700 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200" required>
                        </div>

                        <div>
                            <label for="icone_premio" class="text-gray-300 text-sm mb-2 block">Ícone</label>
                            <label for="icone_premio" class="custom-file-input">
                                <span class="file-button">Escolher ficheiro</span>
                                <span class="file-name" id="icone-file-name"><?= $premio_edit && $premio_edit['icone'] ? basename($premio_edit['icone']) : 'Nenhum ficheiro selecionado' ?></span>
                                <input type="file" id="icone_premio" name="icone" accept="image/jpeg, image/png" onchange="document.getElementById('icone-file-name').textContent = this.files[0] ? this.files[0].name : 'Nenhum ficheiro selecionado'">
                            </label>
                            <?php if ($premio_edit && $premio_edit['icone']): ?>
                                <div class="mt-4 flex items-center gap-3">
                                    <p class="text-gray-300 text-sm">Ícone atual:</p>
                                    <img src="<?= htmlspecialchars($premio_edit['icone']) ?>" alt="Ícone" class="h-12 w-auto object-contain rounded-md border border-gray-700 p-1">
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="<?= $premio_edit ? 'editar_premio' : 'adicionar_premio' ?>"
                                class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-bold rounded-md py-3 px-4 mt-4 transition-colors duration-200 text-lg">
                            <?= $premio_edit ? 'Atualizar' : 'Adicionar' ?> Prêmio
                        </button>

                        <?php if ($premio_edit): ?>
                            <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>" class="block text-center mt-3 text-gray-400 hover:text-white transition-colors duration-200 text-sm">Cancelar Edição</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Card: Prêmios da Raspadinha -->
                <div class="bg-[var(--darker-bg-form)] rounded-lg p-6 shadow-lg border border-gray-700/50">
                    <h2 class="text-white text-xl font-semibold border-b border-gray-700/50 pb-4 mb-6">Prêmios da Raspadinha</h2>

                    <div class="overflow-x-auto max-h-[500px] lg:max-h-[600px]">
                        <?php if (empty($premios)): ?>
                            <p class="text-gray-300 text-center py-8">Nenhum prêmio cadastrado para esta raspadinha.</p>
                        <?php else: ?>
                            <table class="w-full text-white table-auto min-w-[500px]">
                                <thead>
                                    <tr class="border-b border-gray-700/50 text-gray-400 uppercase text-sm">
                                        <th class="text-left py-3 px-4">Nome</th>
                                        <th class="text-left py-3 px-4">Valor</th>
                                        <th class="text-left py-3 px-4">Probabilidade</th>
                                        <th class="text-right py-3 px-4">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($premios as $premio): ?>
                                        <tr class="border-b border-gray-700/30 hover:bg-gray-800 transition-colors duration-200">
                                            <td class="py-3 px-4 flex items-center gap-3">
                                                <?php if ($premio['icone']): ?>
                                                    <img src="<?= htmlspecialchars($premio['icone']) ?>" alt="Ícone" class="h-8 w-8 object-contain rounded-full">
                                                <?php endif; ?>
                                                <span class="font-medium"><?= htmlspecialchars($premio['nome']) ?></span>
                                            </td>
                                            <td class="py-3 px-4 text-white">R$ <?= number_format($premio['valor'], 2, ',', '.') ?></td>
                                            <td class="py-3 px-4 text-white"><?= number_format($premio['probabilidade'], 2, ',', '.') ?>%</td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="flex justify-end gap-3">
                                                    <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>&editar_premio&id=<?= $premio['id'] ?>" class="text-blue-400 hover:text-blue-300 transition-colors duration-200 text-sm font-medium">Editar</a>
                                                    <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>&excluir_premio&id=<?= $premio['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este prêmio?')" class="text-red-400 hover:text-red-300 transition-colors duration-200 text-sm font-medium">Excluir</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <!-- NOVO FOOTER: Desenvolvido por NexCode -->
    <div class="w-full text-center py-6 text-gray-500 text-sm mt-12 border-t border-gray-800">
        <p>Desenvolvido por <a href="#" target="_blank" class="text-[var(--primary-color)] hover:underline">NexCode</a></p>
    </div>
</body>
</html>

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

// Buscar categorias para o select
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['adicionar_raspadinha'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ordem = !empty($_POST['ordem']) ? (int)$_POST['ordem'] : 0;

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

    $stmt = $pdo->prepare("INSERT INTO raspadinhas (nome, descricao, banner, valor, categoria_id, destaque, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$nome, $descricao, $banner, $valor, $categoria_id, $destaque, $ordem])) {
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
    $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ordem = !empty($_POST['ordem']) ? (int)$_POST['ordem'] : 0;

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

    $stmt = $pdo->prepare("UPDATE raspadinhas SET nome = ?, descricao = ?, banner = ?, valor = ?, categoria_id = ?, destaque = ?, ordem = ? WHERE id = ?");
    if ($stmt->execute([$nome, $descricao, $banner, $valor, $categoria_id, $destaque, $ordem, $id])) {
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

// Buscar raspadinhas com informações de categoria
$raspadinhas = $pdo->query("
    SELECT r.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone 
    FROM raspadinhas r 
    LEFT JOIN categorias c ON r.categoria_id = c.id 
    ORDER BY r.destaque DESC, r.ordem ASC, r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
$premios = [];
if (isset($_GET['raspadinha_id'])) {
    $raspadinha_id = $_GET['raspadinha_id'];
    $premios = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE raspadinha_id = ? ORDER BY valor DESC");
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
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-4"><?= isset($_GET['editar_raspadinha']) ? 'Editar' : 'Adicionar' ?> Raspadinha</h2>

                <?php
                $raspadinha_edit = null;
                if (isset($_GET['editar_raspadinha'])) {
                    $id = $_GET['id'];
                    $raspadinha_edit = $pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
                    $raspadinha_edit->execute([$id]);
                    $raspadinha_edit = $raspadinha_edit->fetch(PDO::FETCH_ASSOC);
                }
                ?>

                <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                    <?php if ($raspadinha_edit): ?>
                        <input type="hidden" name="id" value="<?= $raspadinha_edit['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Nome</label>
                        <input type="text" name="nome" value="<?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['nome']) : '' ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Descrição</label>
                        <textarea name="descricao"
                                  class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required><?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['descricao']) : '' ?></textarea>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Categoria</label>
                        <select name="categoria_id" class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" 
                                        <?= ($raspadinha_edit && $raspadinha_edit['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-300 text-sm mb-1 block">Ordem</label>
                            <input type="number" name="ordem" value="<?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['ordem']) : '0' ?>"
                                   class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" min="0">
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-gray-300 text-sm cursor-pointer">
                                <input type="checkbox" name="destaque" value="1" 
                                       <?= ($raspadinha_edit && $raspadinha_edit['destaque']) ? 'checked' : '' ?>
                                       class="w-4 h-4 text-[var(--primary-color)] bg-[var(--dark-bg-form)] border-[var(--border-color-input)] rounded focus:ring-[var(--primary-color)] focus:ring-2">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-star text-yellow-500"></i>
                                    Destacar raspadinha
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Valor (R$)</label>
                        <input type="text" name="valor" value="<?= $raspadinha_edit ? htmlspecialchars(number_format($raspadinha_edit['valor'], 2, ',', '.')) : '' ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Banner</label>
                        <input type="file" name="banner" accept="image/jpeg, image/png"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[var(--primary-color)] file:text-black hover:file:bg-[var(--admin-link-hover)] cursor-pointer">
                        <?php if ($raspadinha_edit && $raspadinha_edit['banner']): ?>
                            <div class="mt-4">
                                <p class="text-gray-300 text-sm mb-2">Banner atual:</p>
                                <img src="<?= htmlspecialchars($raspadinha_edit['banner']) ?>" alt="Banner atual" class="h-20 w-auto object-contain rounded-md border border-gray-700 p-1">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="<?= $raspadinha_edit ? 'editar_raspadinha' : 'adicionar_raspadinha' ?>"
                            class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 mt-4 transition-colors duration-200">
                        <?= $raspadinha_edit ? 'Atualizar' : 'Adicionar' ?> Raspadinha
                    </button>

                    <?php if ($raspadinha_edit): ?>
                        <a href="?" class="block text-center mt-2 text-gray-300 hover:text-white transition-colors duration-200">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-4">Raspadinhas Cadastradas</h2>

                <div class="overflow-y-auto max-h-96">
                    <?php if (empty($raspadinhas)): ?>
                        <p class="text-gray-300">Nenhuma raspadinha cadastrada</p>
                    <?php else: ?>
                        <table class="w-full text-white">
                            <thead>
                                <tr class="border-b border-gray-700/50">
                                    <th class="text-left p-2 text-gray-300">Nome</th>
                                    <th class="text-left p-2 text-gray-300">Categoria</th>
                                    <th class="text-left p-2 text-gray-300">Valor</th>
                                    <th class="text-center p-2 text-gray-300">Destaque</th>
                                    <th class="text-right p-2 text-gray-300">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($raspadinhas as $raspadinha): ?>
                                    <tr class="border-b border-gray-700/30 hover:bg-[var(--dark-bg-form)] transition-colors duration-200">
                                        <td class="p-2">
                                            <a href="?raspadinha_id=<?= $raspadinha['id'] ?>" class="text-white hover:text-[var(--primary-color)] transition-colors duration-200">
                                                <?= htmlspecialchars($raspadinha['nome']) ?>
                                            </a>
                                        </td>
                                        <td class="p-2">
                                            <?php if ($raspadinha['categoria_nome']): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium text-white" 
                                                      style="background-color: <?= htmlspecialchars($raspadinha['categoria_cor']) ?>20; border: 1px solid <?= htmlspecialchars($raspadinha['categoria_cor']) ?>;">
                                                    <i class="<?= htmlspecialchars($raspadinha['categoria_icone']) ?>" style="color: <?= htmlspecialchars($raspadinha['categoria_cor']) ?>;"></i>
                                                    <?= htmlspecialchars($raspadinha['categoria_nome']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Sem categoria</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-2 text-white">R$ <?= number_format($raspadinha['valor'], 2, ',', '.') ?></td>
                                        <td class="p-2 text-center">
                                            <?php if ($raspadinha['destaque']): ?>
                                                <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-500/20 border border-yellow-500 rounded-full">
                                                    <i class="fas fa-star text-yellow-500 text-xs"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-2 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="?editar_raspadinha&id=<?= $raspadinha['id'] ?>" class="text-blue-400 hover:text-blue-300 transition-colors duration-200">Editar</a>
                                                <a href="?excluir_raspadinha&id=<?= $raspadinha['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta raspadinha e todos os seus prêmios?')" class="text-red-400 hover:text-red-300 transition-colors duration-200">Excluir</a>
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
            <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox">
                    <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-4"><?= isset($_GET['editar_premio']) ? 'Editar' : 'Adicionar' ?> Prêmio</h2>

                    <?php
                    $premio_edit = null;
                    if (isset($_GET['editar_premio'])) {
                        $id = $_GET['id'];
                        $premio_edit = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE id = ?");
                        $premio_edit->execute([$id]);
                        $premio_edit = $premio_edit->fetch(PDO::FETCH_ASSOC);
                    }
                    ?>

                    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                        <input type="hidden" name="raspadinha_id" value="<?= $_GET['raspadinha_id'] ?>">
                        <?php if ($premio_edit): ?>
                            <input type="hidden" name="id" value="<?= $premio_edit['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label class="text-gray-300 text-sm mb-1 block">Nome</label>
                            <input type="text" name="nome" value="<?= $premio_edit ? htmlspecialchars($premio_edit['nome']) : '' ?>"
                                   class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required>
                        </div>

                        <div>
                            <label class="text-gray-300 text-sm mb-1 block">Valor (R$)</label>
                            <input type="text" name="valor" value="<?= $premio_edit ? htmlspecialchars(number_format($premio_edit['valor'], 2, ',', '.')) : '' ?>"
                                   class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required>
                        </div>

                        <div>
                            <label class="text-gray-300 text-sm mb-1 block">Probabilidade (0.00 - 100.00)</label>
                            <input type="text" name="probabilidade" value="<?= $premio_edit ? htmlspecialchars(number_format($premio_edit['probabilidade'], 2, ',', '.')) : '' ?>"
                                   class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required>
                        </div>

                        <div>
                            <label class="text-gray-300 text-sm mb-1 block">Ícone</label>
                            <input type="file" name="icone" accept="image/jpeg, image/png"
                                   class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[var(--primary-color)] file:text-black hover:file:bg-[var(--admin-link-hover)] cursor-pointer">
                            <?php if ($premio_edit && $premio_edit['icone']): ?>
                                <div class="mt-4">
                                    <p class="text-gray-300 text-sm mb-2">Ícone atual:</p>
                                    <img src="<?= htmlspecialchars($premio_edit['icone']) ?>" alt="Ícone atual" class="h-16 w-auto object-contain rounded-md border border-gray-700 p-1">
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="<?= $premio_edit ? 'editar_premio' : 'adicionar_premio' ?>"
                                class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 mt-4 transition-colors duration-200">
                            <?= $premio_edit ? 'Atualizar' : 'Adicionar' ?> Prêmio
                        </button>

                        <?php if ($premio_edit): ?>
                            <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>" class="block text-center mt-2 text-gray-300 hover:text-white transition-colors duration-200">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox">
                    <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mb-4">Prêmios da Raspadinha</h2>

                    <div class="overflow-auto max-h-96">
                        <?php if (empty($premios)): ?>
                            <p class="text-gray-300">Nenhum prêmio cadastrado para esta raspadinha</p>
                        <?php else: ?>
                            <table class="w-full text-white">
                                <thead>
                                    <tr class="border-b border-gray-700/50">
                                        <th class="text-left p-2 text-gray-300">Nome</th>
                                        <th class="text-left p-2 text-gray-300">Valor</th>
                                        <th class="text-left p-2 text-gray-300">Probabilidade</th>
                                        <th class="text-right p-2 text-gray-300">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($premios as $premio): ?>
                                        <tr class="border-b border-gray-700/30 hover:bg-[var(--dark-bg-form)] transition-colors duration-200">
                                            <td class="p-2 flex items-center gap-2">
                                                <?php if ($premio['icone']): ?>
                                                    <img src="<?= htmlspecialchars($premio['icone']) ?>" alt="Ícone" class="h-6 w-6 object-contain">
                                                <?php endif; ?>
                                                <?= htmlspecialchars($premio['nome']) ?>
                                            </td>
                                            <td class="p-2 text-white">R$ <?= number_format($premio['valor'], 2, ',', '.') ?></td>
                                            <td class="p-2 text-white"><?= number_format($premio['probabilidade'], 2, ',', '.') ?>%</td>
                                            <td class="p-2 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>&editar_premio&id=<?= $premio['id'] ?>" class="text-blue-400 hover:text-blue-300 transition-colors duration-200">Editar</a>
                                                    <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>&excluir_premio&id=<?= $premio['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este prêmio?')" class="text-red-400 hover:text-red-300 transition-colors duration-200">Excluir</a>
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
    <!-- Incluindo o componente de assinatura NexCode -->
    <?php include 'components/nexcode-signature.php'; ?>
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

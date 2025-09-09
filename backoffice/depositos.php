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

$stmt = $pdo->query("SELECT depositos.id, depositos.user_id, depositos.transactionId, depositos.valor, depositos.status, depositos.updated_at, usuarios.nome
                      FROM depositos
                      JOIN usuarios ON depositos.user_id = usuarios.id
                     ORDER BY depositos.updated_at DESC");
$depositos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h1 class="text-white text-3xl font-bold">Listagem de Depósitos</h1>
            <p class="text-gray-400 text-base">Aqui estão os depósitos realizados pelos usuários</p>
        </div>
        <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($depositos as $deposito): ?>
                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox flex flex-col justify-between">
                    <div>
                        <h3 class="text-white font-bold text-xl mb-2"><?= htmlspecialchars($deposito['nome']) ?></h3>
                        <p class="text-gray-300 text-sm mb-1">ID Transação: <span class="text-white"><?= htmlspecialchars($deposito['transactionId']) ?></span></p>
                        <p class="text-white font-semibold text-lg mb-3">R$ <?= number_format($deposito['valor'], 2, ',', '.') ?></p>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t border-gray-700/50">
                        <?php
                            $status_text = ($deposito['status'] == 'aprovado' || $deposito['status'] == 'PAID') ? 'Aprovado' : 'Pendente';
                            $status_color_class = ($deposito['status'] == 'aprovado' || $deposito['status'] == 'PAID') ? 'text-[var(--card-text-green)]' : 'text-orange-400';
                        ?>
                        <p class="text-sm <?= $status_color_class ?> font-semibold"><?= $status_text ?></p>
                        <p class="text-sm text-gray-400"><?= date('d/m/Y H:i', strtotime($deposito['updated_at'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($depositos)): ?>
                <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox col-span-full text-center text-gray-300">
                    Nenhum depósito encontrado.
                </div>
            <?php endif; ?>
        </div>
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

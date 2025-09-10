<?php
include '../includes/session.php';
include '../conexao.php';
include '../includes/notiflix.php';

$usuarioId = $_SESSION['usuario_id'];
$admin = ($stmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;

if( $admin != 1){
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você não é um administrador!'];
    header("Location: /");
    exit;
}

$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;

// Filtros de data
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês atual
$data_fim = $_GET['data_fim'] ?? date('Y-m-d'); // Hoje

// Validação das datas
if (!$data_inicio) $data_inicio = date('Y-m-01');
if (!$data_fim) $data_fim = date('Y-m-d');

// Métricas gerais (sem filtro de data)
$total_usuarios = (int)($pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() ?: 0);
$total_saldo_carteiras = (float)($pdo->query("SELECT SUM(saldo) FROM usuarios")->fetchColumn() ?: 0);

// Métricas de depósitos com filtro de data
$sql_depositos_valor = "SELECT SUM(valor) FROM depositos WHERE status IN ('PAID', 'aprovado') AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql_depositos_valor);
$stmt->execute([$data_inicio, $data_fim]);
$total_depositos_valor = (float)($stmt->fetchColumn() ?: 0);

$sql_depositos_count = "SELECT COUNT(*) FROM depositos WHERE status IN ('PAID', 'aprovado') AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql_depositos_count);
$stmt->execute([$data_inicio, $data_fim]);
$quantidade_depositos = (int)($stmt->fetchColumn() ?: 0);

// Primeiros depósitos (FTD - First Time Deposit)
$sql_ftd = "
    SELECT COUNT(DISTINCT d.user_id) as ftd_count, SUM(d.valor) as ftd_valor
    FROM depositos d
    INNER JOIN (
        SELECT user_id, MIN(created_at) as primeiro_deposito
        FROM depositos 
        WHERE status IN ('PAID', 'aprovado')
        GROUP BY user_id
    ) primeiro ON d.user_id = primeiro.user_id AND d.created_at = primeiro.primeiro_deposito
    WHERE DATE(d.created_at) BETWEEN ? AND ?
    AND d.status IN ('PAID', 'aprovado')
";
$stmt = $pdo->prepare($sql_ftd);
$stmt->execute([$data_inicio, $data_fim]);
$ftd_data = $stmt->fetch(PDO::FETCH_ASSOC);
$ftd_count = (int)($ftd_data['ftd_count'] ?: 0);
$ftd_valor = (float)($ftd_data['ftd_valor'] ?: 0);

// Depósitos dos últimos 3 dias
$data_3_dias = date('Y-m-d', strtotime('-3 days'));
$sql_depositos_3d = "SELECT COUNT(*) as count, SUM(valor) as valor FROM depositos WHERE status IN ('PAID', 'aprovado') AND DATE(created_at) >= ?";
$stmt = $pdo->prepare($sql_depositos_3d);
$stmt->execute([$data_3_dias]);
$depositos_3d = $stmt->fetch(PDO::FETCH_ASSOC);
$depositos_3d_count = (int)($depositos_3d['count'] ?: 0);
$depositos_3d_valor = (float)($depositos_3d['valor'] ?: 0);

// Depósitos dos últimos 7 dias
$data_7_dias = date('Y-m-d', strtotime('-7 days'));
$sql_depositos_7d = "SELECT COUNT(*) as count, SUM(valor) as valor FROM depositos WHERE status IN ('PAID', 'aprovado') AND DATE(created_at) >= ?";
$stmt = $pdo->prepare($sql_depositos_7d);
$stmt->execute([$data_7_dias]);
$depositos_7d = $stmt->fetch(PDO::FETCH_ASSOC);
$depositos_7d_count = (int)($depositos_7d['count'] ?: 0);
$depositos_7d_valor = (float)($depositos_7d['valor'] ?: 0);

// Depósitos de hoje
$hoje = date('Y-m-d');
$sql_depositos_hoje = "SELECT COUNT(*) as count, SUM(valor) as valor FROM depositos WHERE status IN ('PAID', 'aprovado') AND DATE(created_at) = ?";
$stmt = $pdo->prepare($sql_depositos_hoje);
$stmt->execute([$hoje]);
$depositos_hoje = $stmt->fetch(PDO::FETCH_ASSOC);
$depositos_hoje_count = (int)($depositos_hoje['count'] ?: 0);
$depositos_hoje_valor = (float)($depositos_hoje['valor'] ?: 0);

// Transações recentes (com filtro de data)
$sql_depositos_recentes = "
    SELECT
        u.nome,
        d.valor,
        d.created_at,
        d.status
    FROM
        depositos d
    INNER JOIN
        usuarios u ON d.user_id = u.id
    WHERE
        d.status IN ('PAID', 'aprovado')
        AND DATE(d.created_at) BETWEEN ? AND ?
    ORDER BY
        d.created_at DESC
    LIMIT 10";
$stmt = $pdo->prepare($sql_depositos_recentes);
$stmt->execute([$data_inicio, $data_fim]);
$depositos_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Saques pendentes
$sql_saques_pendentes = "
    SELECT
        u.nome,
        s.valor,
        s.created_at
    FROM
        saques s
    INNER JOIN
        usuarios u ON s.user_id = u.id
    WHERE
        s.status = 'pendente'
    ORDER BY
        s.created_at DESC
    LIMIT 10";
$stmt = $pdo->prepare($sql_saques_pendentes);
$stmt->execute();
$saques_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métricas de saques
$sql_saques_valor = "SELECT SUM(valor) FROM saques WHERE status = 'PAID' AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql_saques_valor);
$stmt->execute([$data_inicio, $data_fim]);
$total_saques_valor = (float)($stmt->fetchColumn() ?: 0);

$sql_saques_count = "SELECT COUNT(*) FROM saques WHERE status = 'PAID' AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql_saques_count);
$stmt->execute([$data_inicio, $data_fim]);
$quantidade_saques = (int)($stmt->fetchColumn() ?: 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Dashboard</title>
    <?php include './components/bg.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
</head>
<body class="relative" style="overflow-y: auto !important;">
   <?php include './components/header.php'; ?>
   <div id="content-admin" class="w-full lg:w-[calc(100vw-280px)] min-h-[calc(100vh-80px)] flex flex-col gap-8 overflow-y-auto z-5" style="overflow-y: auto !important; margin-top: 80px;">
        
        <!-- Cabeçalho -->
        <div class="flex flex-col gap-1 mb-6">
            <h1 class="text-white text-3xl font-bold">Bem-vindo, <?= htmlspecialchars($nome) ?>!</h1>
            <p class="text-gray-400 text-base">Confira as principais informações do sistema</p>
        </div>


        <!-- Cards de Métricas Principais -->
        <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total de Usuários -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-[var(--card-icon-green)]/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-users text-2xl text-[var(--card-icon-green)]"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Total de Usuários</h2>
                <p class="text-3xl font-bold text-[var(--card-value-white)]"><?= number_format($total_usuarios) ?></p>
            </div>

            <!-- Saldo em Carteiras -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-[var(--card-icon-yellow)]/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-wallet text-2xl text-[var(--card-icon-yellow)]"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Saldo em Carteiras</h2>
                <p class="text-3xl font-bold text-[var(--card-value-white)]">R$ <?= number_format($total_saldo_carteiras, 2, ',', '.') ?></p>
            </div>

            <!-- Depósitos Hoje -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-blue-500/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-calendar-day text-2xl text-blue-500"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Depósitos Hoje</h2>
                <p class="text-2xl font-bold text-[var(--card-value-white)]"><?= $depositos_hoje_count ?></p>
                <p class="text-sm text-[var(--card-text-green)]">R$ <?= number_format($depositos_hoje_valor, 2, ',', '.') ?></p>
            </div>

            <!-- Saques Pendentes -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-[var(--card-text-red)]/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-clock text-2xl text-[var(--card-text-red)]"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Saques Pendentes</h2>
                <p class="text-3xl font-bold text-[var(--card-value-white)]"><?= count($saques_pendentes) ?></p>
            </div>
        </div>

        <!-- Cards de Métricas de Depósitos -->
        <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Depósitos (Período) -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-[var(--card-icon-green)]/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-chart-bar text-2xl text-[var(--card-icon-green)]"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Depósitos (Período)</h2>
                <p class="text-2xl font-bold text-[var(--card-value-white)]"><?= $quantidade_depositos ?></p>
                <p class="text-sm text-[var(--card-text-green)]">R$ <?= number_format($total_depositos_valor, 2, ',', '.') ?></p>
            </div>

            <!-- Primeiros Depósitos (FTD) -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-purple-500/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-star text-2xl text-purple-500"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Primeiros Depósitos (FTD)</h2>
                <p class="text-2xl font-bold text-[var(--card-value-white)]"><?= $ftd_count ?></p>
                <p class="text-sm text-purple-400">R$ <?= number_format($ftd_valor, 2, ',', '.') ?></p>
            </div>

            <!-- Últimos 3 Dias -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-orange-500/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-calendar-week text-2xl text-orange-500"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Últimos 3 Dias</h2>
                <p class="text-2xl font-bold text-[var(--card-value-white)]"><?= $depositos_3d_count ?></p>
                <p class="text-sm text-orange-400">R$ <?= number_format($depositos_3d_valor, 2, ',', '.') ?></p>
            </div>

            <!-- Últimos 7 Dias -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-cyan-500/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-calendar text-2xl text-cyan-500"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Últimos 7 Dias</h2>
                <p class="text-2xl font-bold text-[var(--card-value-white)]"><?= $depositos_7d_count ?></p>
                <p class="text-sm text-cyan-400">R$ <?= number_format($depositos_7d_valor, 2, ',', '.') ?></p>
            </div>
        </div>

        <!-- Cards de Saques -->
        <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Total Saques (Período) -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-[var(--card-text-red)]/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-money-bill-wave text-2xl text-[var(--card-text-red)]"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Saques Pagos (Período)</h2>
                <p class="text-2xl font-bold text-[var(--card-value-white)]"><?= $quantidade_saques ?></p>
                <p class="text-sm text-[var(--card-text-red)]">R$ <?= number_format($total_saques_valor, 2, ',', '.') ?></p>
            </div>

            <!-- Lucro Líquido -->
            <div class="bg-[var(--card-bg-gray)] p-6 rounded-lg shadow-rox">
                <div class="w-14 h-14 rounded-lg bg-emerald-500/[0.1] flex justify-center items-center mb-3">
                    <i class="fa-solid fa-chart-line text-2xl text-emerald-500"></i>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[var(--card-text-label)]">Lucro Líquido (Período)</h2>
                <?php $lucro_liquido = $total_depositos_valor - $total_saques_valor; ?>
                <p class="text-2xl font-bold <?= $lucro_liquido >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
                    R$ <?= number_format($lucro_liquido, 2, ',', '.') ?>
                </p>
                <p class="text-sm text-gray-400">Depósitos - Saques</p>
            </div>
        </div>

        <!-- Tabelas de Transações -->
        <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Depósitos Recentes -->
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox">
                <h3 class="flex gap-2 text-xl items-center text-[var(--card-text-green)] font-semibold mb-4">
                    <i class="fa-solid fa-money-bill-transfer text-2xl"></i> 
                    Depósitos Recentes (<?= count($depositos_recentes) ?>)
                </h3>
                <div class="max-h-96 overflow-y-auto">
                    <?php if ($depositos_recentes && count($depositos_recentes) > 0): ?>
                        <?php foreach ($depositos_recentes as $deposito): ?>
                            <?php $data_formatada = date("d/m/Y H:i", strtotime($deposito['created_at'])); ?>
                            <div class="bg-[var(--dark-bg-form)] w-full flex justify-between items-center rounded-lg text-gray-400 p-3 mb-2">
                                <div>
                                    <p class="text-white font-medium"><?= htmlspecialchars($deposito['nome']) ?></p>
                                    <p class="text-sm text-gray-400">
                                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        <?= $data_formatada ?>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <p class="text-[var(--card-text-green)] font-semibold">R$ <?= number_format((float)($deposito['valor'] ?: 0), 2, ',', '.') ?></p>
                                    <p class="text-xs text-gray-500"><?= strtoupper($deposito['status']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-[var(--dark-bg-form)] w-full flex justify-center items-center rounded-lg text-gray-400 p-6">
                            <div class="text-center">
                                <i class="fa-solid fa-inbox text-3xl text-gray-500 mb-2"></i>
                                <p>Nenhum depósito encontrado no período selecionado.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Saques Pendentes -->
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox">
                <h3 class="flex gap-2 text-xl items-center text-[var(--card-text-red)] font-semibold mb-4">
                    <i class="fa-solid fa-clock text-2xl"></i> 
                    Saques Pendentes (<?= count($saques_pendentes) ?>)
                </h3>
                <div class="max-h-96 overflow-y-auto">
                    <?php if ($saques_pendentes && count($saques_pendentes) > 0): ?>
                        <?php foreach ($saques_pendentes as $saque): ?>
                            <?php $data_formatada = date("d/m/Y H:i", strtotime($saque['created_at'])); ?>
                            <div class="bg-[var(--dark-bg-form)] w-full flex justify-between items-center rounded-lg text-gray-400 p-3 mb-2">
                                <div>
                                    <p class="text-white font-medium"><?= htmlspecialchars($saque['nome']) ?></p>
                                    <p class="text-sm text-gray-400">
                                        <span class="inline-block w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                        <?= $data_formatada ?>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <p class="text-[var(--card-text-red)] font-semibold">R$ <?= number_format((float)($saque['valor'] ?: 0), 2, ',', '.') ?></p>
                                    <p class="text-xs text-yellow-500">PENDENTE</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="mt-4 pt-4 border-t border-gray-600">
                            <a href="saques.php" class="w-full bg-[var(--card-icon-green)] text-white px-4 py-2 rounded-lg hover:bg-[var(--admin-link-hover)] transition-colors text-center block">
                                <i class="fa-solid fa-cog mr-2"></i>Gerenciar Saques
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="bg-[var(--dark-bg-form)] w-full flex justify-center items-center rounded-lg text-gray-400 p-6">
                            <div class="text-center">
                                <i class="fa-solid fa-check-circle text-3xl text-green-500 mb-2"></i>
                                <p>Nenhuma solicitação de saque pendente.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
   
    <!-- Incluindo o componente de assinatura NexCode -->
    <?php include 'components/nexcode-signature.php'; ?>
    
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

        #content-admin {
            padding: 24px 42px;
            margin-left: 280px;
        }

        @media screen and (max-width: 1023px) {
            #content-admin {
                padding: 24px 20px;
                margin-left: 0;
            }
        }

        /* Scrollbar personalizada */
        .max-h-96::-webkit-scrollbar {
            width: 6px;
        }

        .max-h-96::-webkit-scrollbar-track {
            background: var(--dark-bg-form);
            border-radius: 3px;
        }

        .max-h-96::-webkit-scrollbar-thumb {
            background: var(--card-icon-green);
            border-radius: 3px;
        }

        .max-h-96::-webkit-scrollbar-thumb:hover {
            background: var(--admin-link-hover);
        }

        /* Animações */
        .bg-\[var\(--card-bg-gray\)\] {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .bg-\[var\(--card-bg-gray\)\]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
    </style>
</body>
</html>

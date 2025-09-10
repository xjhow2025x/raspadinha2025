<?php
// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// **Validação de login obrigatória antes de qualquer include/output**
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você precisa estar logado para acessar esta página.'];
    header("Location: /login");
    exit;
}

include '../conexao.php';
include '../includes/notiflix.php';

// Processar formulários ANTES de qualquer output
if (isset($_POST['salvar_gateway'])) {
    $gateway_ativa = $_POST['gateway_ativa'];
    
    try {
        // Atualizar diretamente o gateway ativo
        $stmt = $pdo->prepare("UPDATE gateway SET active = ? WHERE id = 2");
        if ($stmt->execute([$gateway_ativa])) {
            $_SESSION['success'] = 'Gateway alterada para: ' . strtoupper($gateway_ativa);
        } else {
            $_SESSION['failure'] = 'Erro ao alterar a Gateway!';
        }
    } catch (PDOException $e) {
        $_SESSION['failure'] = 'Erro no banco de dados: ' . $e->getMessage();
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['salvar_bullspay'])) {
    $secretKey = $_POST['secret_key'];
    
    try {
        $stmt = $pdo->prepare("UPDATE bullspay SET secret_key = ? WHERE id = 1");
        if ($stmt->execute([$secretKey])) {
            $_SESSION['success'] = 'Credenciais Bullspay alteradas!';
        } else {
            $_SESSION['failure'] = 'Erro ao alterar as credenciais Bullspay!';
        }
    } catch (PDOException $e) {
        $_SESSION['failure'] = 'Erro no banco de dados: ' . $e->getMessage();
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['salvar_axisbanking'])) {
    $publicKey = $_POST['public_key'];
    $privateKey = $_POST['private_key'];
    $api_url = $_POST['api_url'];
    
    try {
        $stmt = $pdo->prepare("UPDATE axisbanking SET public_key = ?, private_key = ?, api_url = ? WHERE id = 1");
        if ($stmt->execute([$publicKey, $privateKey, $api_url])) {      
            $_SESSION['success'] = 'Credenciais AxisBanking alteradas!';
        } else {
            $_SESSION['failure'] = 'Erro ao alterar as credenciais AxisBanking!';
        }
    } catch (PDOException $e) {
        $_SESSION['failure'] = 'Erro no banco de dados: ' . $e->getMessage();
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Buscar gateway ativa com tratamento de erro
try {
    $stmt = $pdo->query("SELECT active FROM gateway WHERE id = 2 LIMIT 1");
    $gateway_ativa = $stmt->fetchColumn() ?: 'bullspay';
} catch (PDOException $e) {
    $gateway_ativa = 'bullspay';
}

// Buscar credenciais dos gateways com tratamento de erro
try {
    $stmt = $pdo->query("SELECT secret_key FROM bullspay WHERE id = 1");
    $bullspay = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['secret_key' => ''];
} catch (PDOException $e) {
    $bullspay = ['secret_key' => ''];
}

try {
    $stmt = $pdo->query("SELECT public_key, private_key, api_url FROM axisbanking WHERE id = 1");
    $axisbanking = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['public_key' => '', 'private_key' => '', 'api_url' => 'https://api.axisbanking.com/v1'];
} catch (PDOException $e) {
    $axisbanking = ['public_key' => '', 'private_key' => '', 'api_url' => 'https://api.axisbanking.com/v1'];
}

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// **Validação de login obrigatória antes de qualquer include/output**
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você precisa estar logado para acessar esta página.'];
    header("Location: /login");
    exit;
}

include '../conexao.php';
include '../includes/notiflix.php';

// Processar formulários ANTES de qualquer output
if (isset($_POST['salvar_bullspay_new'])) {
    $publicKey = $_POST['public_key'];
    $privateKey = $_POST['private_key'];
    $api_url = $_POST['api_url'];
    
    try {
        $stmt = $pdo->prepare("UPDATE bullspay_new SET public_key = ?, private_key = ?, api_url = ? WHERE id = 1");
        if ($stmt->execute([$publicKey, $privateKey, $api_url])) {
            $_SESSION['success'] = 'Credenciais BullsPay New alteradas!';
        } else {
            $_SESSION['failure'] = 'Erro ao alterar as credenciais BullsPay New!';
        }
    } catch (PDOException $e) {
        $_SESSION['failure'] = 'Erro no banco de dados: ' . $e->getMessage();
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Buscar credenciais do BullsPay New com tratamento de erro
try {
    $stmt = $pdo->query("SELECT public_key, private_key, api_url FROM bullspay_new WHERE id = 1");
    $bullspay_new = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['public_key' => '', 'private_key' => '', 'api_url' => 'https://pay.bullspay.net/api/v1'];
} catch (PDOException $e) {
    $bullspay_new = ['public_key' => '', 'private_key' => '', 'api_url' => 'https://pay.bullspay.net/api/v1'];
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
            <h1 class="text-white text-3xl font-bold">Configuração de Gateways</h1>
            <p class="text-gray-400 text-base">Gerencie os gateways ativos e suas credenciais</p>
        </div>

        <!-- Seleção de Gateway Ativa -->
        <div class="w-full mb-6">
            <form method="POST" class="bg-[var(--card-bg-gray)] rounded-lg p-6 flex flex-col gap-6 shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4">
                    <i class="fas fa-toggle-on text-[var(--primary-color)] mr-2"></i>
                    Gateway Ativa
                </h2>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Selecionar Gateway</label>
                    <select name="gateway_ativa" class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200">
                        <option value="bullspay" <?= ($gateway_ativa == 'bullspay') ? 'selected' : '' ?>>Bullspay (Original)</option>
                        <option value="axisbanking" <?= ($gateway_ativa == 'axisbanking') ? 'selected' : '' ?>>AxisBanking</option>
                        <option value="bullspay_new" <?= ($gateway_ativa == 'bullspay_new') ? 'selected' : '' ?>>BullsPay New</option>
                    </select>
                    <small class="text-gray-500 text-xs mt-1 block">Gateway ativa atual: <span class="text-[var(--primary-color)] font-semibold"><?= strtoupper($gateway_ativa) ?></span></small>
                </div>
                <button type="submit" name="salvar_gateway" class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 mt-4 transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>Salvar Gateway
                </button>
            </form>
        </div>

        <!-- Configurações dos Gateways -->
        <div class="w-full grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            
            <!-- Bullspay Original -->
            <form method="POST" class="bg-[var(--card-bg-gray)] rounded-lg p-6 flex flex-col gap-6 shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4">
                    <i class="fas fa-credit-card text-blue-400 mr-2"></i>
                    Bullspay (Original)
                </h2>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Secret Key</label>
                    <input type="text" name="secret_key" value="<?= htmlspecialchars($bullspay['secret_key']) ?>"
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                           placeholder="Insira a secret key do Bullspay" required>
                </div>
                <button type="submit" name="salvar_bullspay" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg p-3 mt-4 transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>Salvar Bullspay
                </button>
            </form>

            <!-- AxisBanking -->
            <form method="POST" class="bg-[var(--card-bg-gray)] rounded-lg p-6 flex flex-col gap-6 shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4">
                    <i class="fas fa-university text-green-400 mr-2"></i>
                    AxisBanking
                </h2>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Chave Pública</label>
                    <input type="text" name="public_key" value="<?= htmlspecialchars($axisbanking['public_key']) ?>"
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                           placeholder="Chave pública do AxisBanking" required>
                </div>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Chave Privada</label>
                    <input type="password" name="private_key" value="<?= htmlspecialchars($axisbanking['private_key']) ?>"
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                           placeholder="Chave privada do AxisBanking" required>
                </div>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">URL da API</label>
                    <input type="url" name="api_url" value="<?= htmlspecialchars($axisbanking['api_url']) ?>"
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                           placeholder="https://api.axisbanking.com/v1" required>
                </div>
                <button type="submit" name="salvar_axisbanking" class="bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg p-3 mt-4 transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>Salvar AxisBanking
                </button>
            </form>

            <!-- BullsPay New -->
            <form method="POST" class="bg-[var(--card-bg-gray)] rounded-lg p-6 flex flex-col gap-6 shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4">
                    <i class="fas fa-bolt text-yellow-400 mr-2"></i>
                    BullsPay New
                </h2>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Chave Pública</label>
                    <input type="text" name="public_key" value="<?= htmlspecialchars($bullspay_new['public_key']) ?>"
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                           placeholder="Chave pública do BullsPay New" required>
                </div>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">Chave Privada</label>
                    <input type="password" name="private_key" value="<?= htmlspecialchars($bullspay_new['private_key']) ?>"
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                           placeholder="Chave privada do BullsPay New" required>
                </div>
                <div>
                    <label class="text-gray-300 text-sm mb-1 block">URL da API</label>
                    <input type="url" name="api_url" value="<?= htmlspecialchars($bullspay_new['api_url']) ?>"
                           class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                           placeholder="https://pay.bullspay.net/api/v1" required>
                </div>
                <button type="submit" name="salvar_bullspay_new" class="bg-yellow-500 hover:bg-yellow-600 text-black font-semibold rounded-lg p-3 mt-4 transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>Salvar Configurações
                </button>
            </form>
        </div>

        <!-- Informações Adicionais -->
        <div class="w-full mt-6">
            <div class="bg-[var(--card-bg-gray)] rounded-lg p-6 shadow-rox">
                <h3 class="text-white text-lg font-semibold mb-4">
                    <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                    Informações dos Gateways
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-[var(--dark-bg-form)] p-4 rounded-lg">
                        <h4 class="text-blue-400 font-semibold mb-2">Bullspay (Original)</h4>
                        <p class="text-gray-300">Gateway original do sistema. Requer apenas a secret key para funcionamento.</p>
                    </div>
                    <div class="bg-[var(--dark-bg-form)] p-4 rounded-lg">
                        <h4 class="text-green-400 font-semibold mb-2">AxisBanking</h4>
                        <p class="text-gray-300">Gateway bancário com autenticação por chaves pública e privada.</p>
                    </div>
                    <div class="bg-[var(--dark-bg-form)] p-4 rounded-lg">
                        <h4 class="text-yellow-400 font-semibold mb-2">BullsPay New</h4>
                        <p class="text-gray-300">Nova versão do BullsPay com melhorias e autenticação dupla.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

        .shadow-rox {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</body>
</html>

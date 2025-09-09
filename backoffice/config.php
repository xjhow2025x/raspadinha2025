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

$config = $pdo->query("SELECT * FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Função para converter valor brasileiro para formato do banco
function convertBrazilianToDecimal($value) {
    if (empty($value)) return 0;
    
    // Remove espaços e caracteres especiais, exceto vírgula, ponto e números
    $value = preg_replace('/[^\d,.]/', '', $value);
    
    // Se tem vírgula e ponto, o ponto é separador de milhares
    if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
        // Remove pontos (separadores de milhares) e substitui vírgula por ponto
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }
    // Se tem apenas vírgula, substitui por ponto
    else if (strpos($value, ',') !== false && strpos($value, '.') === false) {
        $value = str_replace(',', '.', $value);
    }
    // Se tem apenas ponto, verifica se é separador decimal ou de milhares
    else if (strpos($value, '.') !== false && strpos($value, ',') === false) {
        // Se há mais de 3 dígitos após o ponto, é separador de milhares
        $parts = explode('.', $value);
        if (count($parts) == 2 && strlen($parts[1]) > 2) {
            // É separador de milhares, remove
            $value = str_replace('.', '', $value);
        }
        // Senão, mantém como separador decimal
    }
    
    return floatval($value);
}

if (isset($_POST['salvar_config'])) {
    $nome_site = $_POST['nome_site'];
    $deposito_min = convertBrazilianToDecimal($_POST['deposito_min']);
    $saque_min = convertBrazilianToDecimal($_POST['saque_min']);
    $saque_max_diario = convertBrazilianToDecimal($_POST['saque_max_diario']);
    $taxa_saque = convertBrazilianToDecimal($_POST['taxa_saque']);
    $cpa_padrao = convertBrazilianToDecimal($_POST['cpa_padrao']);

    $logo = $config['logo'];

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/upload/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                if ($config['logo'] && file_exists('../' . $config['logo'])) {
                    unlink('../' . $config['logo']);
                }
                $logo = '/assets/upload/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload da logo!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE config SET nome_site = ?, logo = ?, deposito_min = ?, saque_min = ?, saque_max_diario = ?, taxa_saque = ?, cpa_padrao = ?");
    if ($stmt->execute([$nome_site, $logo, $deposito_min, $saque_min, $saque_max_diario, $taxa_saque, $cpa_padrao])) {
        $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar as configurações!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
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
        <div class="flex flex-col gap-1">
            <h1 class="text-white text-2xl font-bold">Configurações Gerais</h1>
            <p class="text-gray-400 text-sm">Gerencie as configurações básicas do sistema</p>
        </div>
        <div class="w-full grid grid-cols-1 gap-6">
            <form method="POST" enctype="multipart/form-data" class="bg-[var(--darker-bg-form)] rounded-lg p-6 flex flex-col gap-6 shadow-rox">
                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4">Configurações do Site</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Nome do Site</label>
                        <input type="text" name="nome_site" value="<?= htmlspecialchars($config['nome_site'] ?? '') ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" required>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Logo do Site</label>
                        <input type="file" name="logo" accept="image/jpeg, image/png"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[var(--primary-color)] file:text-black hover:file:bg-[var(--admin-link-hover)] cursor-pointer">
                        <?php if (!empty($config['logo'])): ?>
                            <div class="mt-4">
                                <p class="text-gray-300 text-sm mb-2">Logo atual:</p>
                                <img src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo atual" class="h-20 w-auto object-contain rounded-md border border-gray-700 p-1">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <h2 class="text-white text-lg font-semibold border-b border-gray-700/50 pb-4 mt-4">Configurações Financeiras</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Depósito Mínimo (R$)</label>
                        <input type="text" name="deposito_min" value="<?= htmlspecialchars(number_format($config['deposito_min'] ?? 0, 2, ',', '.')) ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200" 
                               placeholder="Ex: 10,00" required>
                        <p class="text-gray-400 text-xs mt-1">Use vírgula para separar decimais (ex: 10,50)</p>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Saque Mínimo (R$)</label>
                        <input type="text" name="saque_min" value="<?= htmlspecialchars(number_format($config['saque_min'] ?? 0, 2, ',', '.')) ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                               placeholder="Ex: 20,00" required>
                        <p class="text-gray-400 text-xs mt-1">Use vírgula para separar decimais (ex: 20,50)</p>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Saque Máximo Diário (R$)</label>
                        <input type="text" name="saque_max_diario" value="<?= htmlspecialchars(number_format($config['saque_max_diario'] ?? 2000, 2, ',', '.')) ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                               placeholder="Ex: 2.000,00" required>
                        <p class="text-gray-400 text-xs mt-1">Limite máximo de saque por usuário por dia</p>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">Taxa de Saque (%)</label>
                        <input type="text" name="taxa_saque" value="<?= htmlspecialchars(number_format($config['taxa_saque'] ?? 1, 2, ',', '.')) ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                               placeholder="Ex: 1,50" required>
                        <p class="text-gray-400 text-xs mt-1">Percentual cobrado sobre o valor do saque</p>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm mb-1 block">CPA Padrão (R$)</label>
                        <input type="text" name="cpa_padrao" value="<?= htmlspecialchars(number_format($config['cpa_padrao'] ?? 0, 2, ',', '.')) ?>"
                               class="bg-[var(--dark-bg-form)] text-white rounded-lg p-3 w-full border border-[var(--border-color-input)] focus:border-[var(--border-color-active)] transition-colors duration-200"
                               placeholder="Ex: 15,00" required>
                        <p class="text-gray-400 text-xs mt-1">Valor padrão de CPA por conversão</p>
                    </div>
                </div>

                <button type="submit" name="salvar_config"
                        class="bg-[var(--primary-color)] hover:bg-[var(--admin-link-hover)] text-black font-semibold rounded-lg p-3 mt-4 transition-colors duration-200">
                    Salvar Configurações
                </button>
            </form>
            <div></div>
        </div>

    </div>
    <!-- Incluindo o componente de assinatura NexCode -->
    <?php include 'components/nexcode-signature.php'; ?>
    
    <script>
    // Adiciona formatação automática nos campos monetários
    document.addEventListener('DOMContentLoaded', function() {
        const moneyFields = ['deposito_min', 'saque_min', 'saque_max_diario', 'cpa_padrao'];
        const percentFields = ['taxa_saque'];
        
        moneyFields.forEach(fieldName => {
            const field = document.querySelector(`input[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = (parseInt(value) / 100).toFixed(2);
                    value = value.replace('.', ',');
                    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    e.target.value = value;
                });
            }
        });
        
        percentFields.forEach(fieldName => {
            const field = document.querySelector(`input[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = (parseInt(value) / 100).toFixed(2);
                    value = value.replace('.', ',');
                    e.target.value = value;
                });
            }
        });
    });
    </script>
    
    <style>
        /* Assegura que o conteúdo principal tenha o padding correto e respeite o sidebar */
        #content-admin {
            padding: 24px 42px; /* Padding padrão para desktop */
            margin-left: 280px; /* Espaço para o sidebar em desktop */
        }
        /* Ajusta o padding do rodapé de assinatura */
        #signature {
            padding: 16px 42px; /* Padding horizontal consistente com o content-admin */
        }
        /* Remove a classe .padding que não está sendo usada e pode causar confusão */
        .padding {
            padding: 16px; /* Mantido apenas para referência, mas não aplicado diretamente */
        }
        @media screen and (max-width: 1023px) { /* Ajuste para telas menores que 'lg' (1024px) */
            #content-admin {
                padding: 24px 20px; /* Padding menor em mobile */
                margin-left: 0; /* Ocupa a largura total em mobile */
            }
            #signature {
                padding: 16px 20px; /* Padding menor em mobile */
            }
        }
    </style>
</body>
</html>
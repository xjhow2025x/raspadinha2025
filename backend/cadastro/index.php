<?php
session_start();
if(isset($_SESSION['usuario_id'])){
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você já está logado!'];
    header("Location: /");
    exit;
}
require_once '../conexao.php';

// Buscar banner de cadastro ativo
$banner_cadastro = null;
try {
    $sql = "SELECT * FROM banners WHERE tipo = 'cadastro' AND ativo = 1 
           AND (data_inicio IS NULL OR data_inicio <= NOW()) 
           AND (data_fim IS NULL OR data_fim >= NOW()) 
           ORDER BY ordem ASC, created_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $banner_cadastro = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silenciar erro e usar banner padrão
}

// Banner padrão caso não encontre nenhum
$banner_url = $banner_cadastro ? $banner_cadastro['imagem'] : 'https://i.imgur.com/wuvfhJh.png';
$banner_alt = $banner_cadastro ? $banner_cadastro['titulo'] : 'Banner de Boas-Vindas';
$banner_link = $banner_cadastro ? $banner_cadastro['link'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $ref = $_POST['ref'] ?? null;

    try {
        $stmt_cpa = $pdo->query("SELECT cpa_padrao FROM config LIMIT 1");
        $cpa_padrao = $stmt_cpa->fetchColumn();

        if($cpa_padrao === false) {
            $cpa_padrao = 0.00;
        }
    } catch (PDOException $e) {
        $cpa_padrao = 0.00;
    }

    $stmt = $pdo->prepare("INSERT INTO usuarios
                           (nome, telefone, email, senha, saldo, indicacao, banido, comissao_cpa, created_at, updated_at)
                           VALUES (?, ?, ?, ?, 0, ?, 0, ?, NOW(), NOW())");
    try {
        $stmt->execute([$nome, $telefone, $email, $senha, $ref, $cpa_padrao]);

        $usuarioId = $pdo->lastInsertId();
        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Cadastro realizado com sucesso!'];

        header("Location: /");
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao realizar cadastro!'];
        header("Location: /cadastro");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?></title>
    <style>
        /* Global styles */
       

        * {
            box-sizing: border-box;
            outline: none;
        }

        :root {
            --primary-color: #00de93;
            --secondary-color: #00de93;
            --tertiary-color: #00de93;
            --bg-color: #13151b;
            --support-color: #00de93;
            --dark-bg-form: #1a1a1a; /* Cor de fundo dos inputs */
            --darker-bg-form: #222222; /* Cor de fundo do container do formulário */
            --text-gray-light: #cccccc;
            --text-green-accent: #00de93;
            --border-color-input: #333333; /* Cor da borda dos inputs */
            --border-color-active: #00de93; /* Cor da borda ativa/selecionada */
        }

        html, body {
            padding: 0;
            margin: 0;
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            background-color: #1a1a1a; /* Solid dark background */
            color: white; /* Default text color for the body */
            padding-top: 80px; /* Ajustado para a altura do novo header */
            padding-bottom: 70px; /* Espaçamento inferior para o mobile-bottom-nav */
        }

        .shadow-rox {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 6px 12px -2px, rgba(0, 0, 0, 0.3) 0px 3px 7px -3px;
        }

        /* Notiflix styles */
        .notiflix-notify-success {
            background-color: var(--secondary-color) !important;
            color: white !important;
        }

        .notiflix-notify-info {
            background-color: var(--tertiary-color) !important;
            color: white !important;
        }

        .notiflix-notify-failure {
            background-color: #c0392b !important;
            color: white !important;
        }

        .notiflix-notify {
            top: 83px !important;
            z-index: 9999 !important;
            position: fixed !important;
            max-width: 90vw !important;
            width: 300px !important;
            right: 16px !important;
            left: auto !important;
            border-radius: 8px !important;
            overflow-wrap: break-word;
            box-sizing: border-box;
        }

        #NotiflixNotifyWrap .notiflix-notify {
            background-clip: padding-box !important;
        }

        /* Custom styles for the new registration page */
        .registration-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start; /* Alinha o conteúdo ao topo */
            min-height: calc(100vh - 80px - 70px); /* Altura da viewport menos o header e o footer mobile */
            padding-top: 24px; /* Adicionado padding superior para o container */
            padding-bottom: 0; /* Removido padding extra aqui */
            position: relative;
        }

        .registration-container {
            background-color: var(--darker-bg-form);
            border-radius: 12px;
            padding: 24px; /* Padding padrão */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            width: 95%; /* Ocupa 95% da largura em mobile */
            max-width: 450px; /* Limita a largura em telas maiores */
            margin: 0 auto; /* Centraliza horizontalmente */
            position: relative; /* Mantido para o banner */
            z-index: 10;
        }

        @media (min-width: 640px) { /* Para telas maiores que mobile */
            .registration-container {
                padding: 32px; /* Padding maior em telas maiores */
            }
        }

        /* REMOVIDO: .close-button */

        .registration-header-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 16px; /* Espaçamento entre o banner e o logo/título */
            margin-bottom: 24px;
        }

        .registration-logo {
            width: 150px; /* Ajustado para o tamanho do logo na imagem */
            height: auto;
            margin-bottom: 16px;
        }

        .form-step {
            display: none; /* Hidden by default, controlled by JS */
            flex-direction: column;
            gap: 20px; /* Espaçamento entre os campos */
        }

        .form-step.active {
            display: flex;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px 14px 48px; /* Ajustado padding para ícone */
            border-radius: 8px;
            background-color: var(--dark-bg-form);
            border: 1px solid var(--border-color-input);
            color: white;
            font-size: 16px;
            transition: border-color 0.2s ease;
        }

        .input-group input::placeholder {
            color: var(--text-gray-light);
        }

        .input-group input:focus {
            border-color: var(--border-color-active);
        }

        .input-group .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray-light);
            font-size: 20px;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 14px;
            color: var(--text-gray-light);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            background-color: var(--dark-bg-form);
            border: 1px solid var(--border-color-input);
            appearance: none;
            cursor: pointer;
            flex-shrink: 0;
            position: relative;
        }

        .checkbox-group input[type="checkbox"]:checked {
            background-color: var(--text-green-accent);
            border-color: var(--text-green-accent);
        }

        .checkbox-group input[type="checkbox"]:checked::before {
            content: '\f00c'; /* FontAwesome check icon */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: black; /* Cor do check */
            font-size: 12px;
        }

        .checkbox-group a {
            color: var(--text-green-accent);
            text-decoration: none;
        }
        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .button-group {
            display: flex;
            gap: 16px;
            margin-top: 24px;
        }

        .button-group button {
            flex: 1;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .button-group .btn-secondary {
            background-color: #333333;
            color: white;
            border: none;
        }
        .button-group .btn-secondary:hover {
            background-color: #555555;
        }

        .button-group .btn-primary {
            background-color: var(--text-green-accent);
            color: black;
            border: none;
        }
        .button-group .btn-primary:hover {
            background-color: #00b37a; /* Um verde um pouco mais escuro */
        }

        .login-link-bottom {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-gray-light);
        }
        .login-link-bottom a {
            color: var(--text-green-accent);
            text-decoration: none;
        }
        .login-link-bottom a:hover {
            text-decoration: underline;
        }
    </style>
    <!-- Font Awesome corrigido - removido hash incorreto e kit duplicado -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>
</head>
<body>
<?php include('../inc/header.php'); ?>

<section class="registration-section">
    <div class="registration-container">
        <!-- Banner dinâmico -->
        <?php if (!empty($banner_link)): ?>
        <a href="<?= htmlspecialchars($banner_link) ?>" target="_blank">
        <?php endif; ?>
            <img src="<?= htmlspecialchars($banner_url) ?>" alt="<?= htmlspecialchars($banner_alt) ?>" class="w-full rounded-lg mb-6">
        <?php if (!empty($banner_link)): ?>
        </a>
        <?php endif; ?>
        <div class="registration-header-content">
            <img src="<?php echo $logoSite ;?>" alt="Logo Raspou Ganhou" class="registration-logo">
            <!-- Título dinâmico para as etapas -->
            <h2 class="text-xl text-center font-semibold mt-4" id="formTitle">Crie sua conta gratuita. Vamos começar?</h2>
        </div>

        <form id="cadastroForm" method="POST">
            <input id="ref" name="ref" type="hidden" value="">
            <script>
                (function () {
                    const params = new URLSearchParams(window.location.search);
                    let refValue = params.get('ref');
                    if (!refValue) {
                        refValue = localStorage.getItem('ref') || '';
                    } else {
                        localStorage.setItem('ref', refValue);
                    }
                    const refInput = document.getElementById('ref');
                    if (refInput) refInput.value = refValue;
                })();
            </script>

            <!-- Step 1 -->
            <div class="form-step active" id="step1">
                <div class="input-group">
                    <i class="fa-solid fa-user icon"></i>
                    <input type="text" name="nome" id="nome" required placeholder="Nome completo">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-phone icon"></i>
                    <input type="text" name="telefone" id="telefone" required placeholder="Telefone" data-mask="(99) 99999-9999">
                </div>
                <div class="button-group">
                    <button type="button" class="btn-primary" id="continueBtn">
                        Continuar <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="form-step" id="step2">
                <div class="input-group">
                    <i class="fa-solid fa-envelope icon"></i>
                    <input type="email" name="email" id="email" required placeholder="E-mail">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock icon"></i>
                    <input type="password" name="senha" id="senha" required placeholder="Senha">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-shield-alt icon"></i>
                    <input type="password" name="confirm_senha" id="confirm_senha" required placeholder="Confirmar senha">
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="aceiteTermos" name="aceiteTermos" required>
                    <label for="aceiteTermos">
                        Declaro que tenho mais de 18 anos de idade e aceito os <a href="/politica" target="_blank">Termos</a> e <a href="/politica" target="_blank">Políticas de Privacidade</a>
                    </label>
                </div>
                <div class="button-group">
                    <button type="button" class="btn-secondary" id="backBtn">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </button>
                    <button type="submit" class="btn-primary">
                        Criar conta
                    </button>
                </div>
            </div>
        </form>

        <div class="login-link-bottom">
            Já tem uma conta? <a href="/login">Conecte-se</a>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const continueBtn = document.getElementById('continueBtn');
        const backBtn = document.getElementById('backBtn');
        const nomeInput = document.getElementById('nome');
        const telefoneInput = document.getElementById('telefone');
        const emailInput = document.getElementById('email');
        const senhaInput = document.getElementById('senha');
        const confirmSenhaInput = document.getElementById('confirm_senha');
        const cadastroForm = document.getElementById('cadastroForm');
        const formTitle = document.getElementById('formTitle');

        // REMOVIDO: Listener para o botão de fechar

        // Function to format phone number
        telefoneInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let formatted = '';
            if (value.length > 0) {
                formatted += '(' + value.substring(0, 2);
            }
            if (value.length >= 3) {
                formatted += ') ' + value.substring(2, 7);
            }
            if (value.length >= 8) {
                formatted += '-' + value.substring(7);
            }
            e.target.value = formatted;
        });

        continueBtn.addEventListener('click', function () {
            // Validate step 1 fields
            if (!nomeInput.value.trim()) {
                Notiflix.Notify.failure('Por favor, digite seu nome completo.');
                nomeInput.focus();
                return;
            }
            if (!telefoneInput.value.trim() || telefoneInput.value.replace(/\D/g, '').length < 10) {
                Notiflix.Notify.failure('Por favor, digite um telefone válido.');
                telefoneInput.focus();
                return;
            }

            step1.classList.remove('active');
            step2.classList.add('active');
            formTitle.textContent = 'Finalize seu cadastro'; // Update title for step 2
        });

        backBtn.addEventListener('click', function () {
            step2.classList.remove('active');
            step1.classList.add('active');
            formTitle.textContent = 'Crie sua conta gratuita. Vamos começar?'; // Update title for step 1
        });

        cadastroForm.addEventListener('submit', function (e) {
            // This part will only run when the "Criar conta" button is clicked (on step 2)
            // Validate step 2 fields
            if (!emailInput.value.trim()) {
                Notiflix.Notify.failure('Por favor, digite seu e-mail.');
                emailInput.focus();
                e.preventDefault();
                return;
            }
            if (!senhaInput.value.trim()) {
                Notiflix.Notify.failure('Por favor, digite sua senha.');
                senhaInput.focus();
                e.preventDefault();
                return;
            }
            if (senhaInput.value !== confirmSenhaInput.value) {
                Notiflix.Notify.failure('As senhas não coincidem.');
                confirmSenhaInput.focus();
                e.preventDefault();
                return;
            }
            if (!document.getElementById('aceiteTermos').checked) {
                Notiflix.Notify.failure('Você deve aceitar os termos e políticas de privacidade.');
                e.preventDefault();
                return;
            }

            // If all validations pass, the form will submit normally via PHP POST
            Notiflix.Loading.standard('Cadastrando...');
        });
    });
</script>

<?php include('../components/ganhos.php'); ?>
<?php include('../inc/footer.php'); ?>
</body>
</html>

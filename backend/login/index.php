<?php
@session_start();
if (isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você já está logado!'];
    header("Location: /");
    exit;
}
require_once '../conexao.php';

// Buscar banner de login ativo
$banner_login = null;
try {
    $sql = "SELECT * FROM banners WHERE tipo = 'login' AND ativo = 1 
           AND (data_inicio IS NULL OR data_inicio <= NOW()) 
           AND (data_fim IS NULL OR data_fim >= NOW()) 
           ORDER BY ordem ASC, created_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $banner_login = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silenciar erro e usar banner padrão
}

// Banner padrão caso não encontre nenhum
$banner_url = $banner_login ? $banner_login['imagem'] : 'https://i.imgur.com/wuvfhJh.png';
$banner_alt = $banner_login ? $banner_login['titulo'] : 'Banner de Boas-Vindas';
$banner_link = $banner_login ? $banner_login['link'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        if($usuario['banido'] == 1){
            $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você está banido!'];
            header("Location: /");
            exit;
        }
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Login realizado com sucesso!'];
        header("Location: /");
        exit;
    } else {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'E-mail ou senha inválidos.'];
        header("Location: /login");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $nomeSite;?></title>
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

        /* Custom styles for the login page (reusing registration styles) */
        .registration-section { /* Reutilizado para o layout principal */
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

        .registration-container { /* Reutilizado para o container do formulário */
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

        .registration-header-content { /* Reutilizado para o logo e título */
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 16px; /* Espaçamento entre o banner e o logo/título */
            margin-bottom: 24px;
        }

        .registration-logo { /* Reutilizado para o logo */
            width: 150px;
            height: auto;
            margin-bottom: 16px;
        }

        .input-group { /* Reutilizado para os campos de input */
            position: relative;
            margin-bottom: 20px; /* Espaçamento entre os campos */
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

        .button-group { /* Reutilizado para o botão de login */
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

        .button-group .btn-primary { /* Reutilizado para o botão principal */
            background-color: var(--text-green-accent);
            color: black;
            border: none;
        }
        .button-group .btn-primary:hover {
            background-color: #00b37a; /* Um verde um pouco mais escuro */
        }

        .link-bottom { /* Novo estilo para links inferiores */
            text-align: center;
            margin-top: 20px; /* Espaçamento entre o botão e os links */
            font-size: 14px;
            color: var(--text-gray-light);
        }
        .link-bottom a {
            color: var(--text-green-accent);
            text-decoration: none;
        }
        .link-bottom a:hover {
            text-decoration: underline;
        }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet"></head>
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
            <h2 class="text-xl text-center font-semibold mt-4">Acesse sua conta</h2>
        </div>

        <form method="POST">
            <div class="input-group">
                <i class="fa-solid fa-envelope icon"></i>
                <input type="email" name="email" required
                       class=""
                       placeholder="E-mail">
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock icon"></i>
                <input type="password" name="senha" required
                       class=""
                       placeholder="Senha">
            </div>
            <div class="link-bottom">
                <a href="/recuperar-senha">Esqueceu a senha?</a>
            </div>
            <div class="button-group">
                <button type="submit"
                        class="btn-primary">
                    Entrar
                </button>
            </div>
        </form>

        <div class="link-bottom">
            Não tem uma conta? <a href="/cadastro">Cadastre-se</a>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // REMOVIDO: Listener para o botão de fechar

        // Adiciona o listener para o formulário de login
        document.querySelector('form').addEventListener('submit', function(e) {
            const emailInput = this.querySelector('input[name="email"]');
            const senhaInput = this.querySelector('input[name="senha"]');

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
            Notiflix.Loading.standard('Entrando...');
        });
    });
</script>

<?php include('../components/ganhos.php'); ?>
<?php include('../inc/footer.php'); ?>
</body>
</html>

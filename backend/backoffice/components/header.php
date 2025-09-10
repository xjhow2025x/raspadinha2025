<?php
@session_start();
// Inclua sua conexão com o banco de dados e lógica de autenticação aqui
require_once '../conexao.php';

// Exemplo de verificação de autenticação e permissão de admin
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você precisa estar logado para acessar esta página.'];
    header("Location: /login");
    exit;
}

// Supondo que você tenha uma coluna 'admin' na tabela de usuários
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['admin'] != 1) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você não tem permissão para acessar esta área.'];
    header("Location: /");
    exit;
}

// Conteúdo específico da página de início do admin
$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;

$total_usuarios = ($stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios"))->execute() ? $stmt->fetchColumn() : 0;
$total_depositos = ($stmt = $pdo->prepare("SELECT SUM(valor) FROM depositos WHERE status = 'aprovado'"))->execute() ? $stmt->fetchColumn() : 0;
$total_saques_pendentes = ($stmt = $pdo->prepare("SELECT COUNT(*) FROM saques WHERE status = 'pendente'"))->execute() ? $stmt->fetchColumn() : 0;

$sql = "
    SELECT
        u.nome,
        d.valor,
        d.updated_at
    FROM
        depositos d
    INNER JOIN
        usuarios u ON d.user_id = u.id
    WHERE
        d.status = 'aprovado'
    ORDER BY
        d.updated_at DESC
    LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$depositos_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT
        u.nome,
        s.valor,
        s.updated_at
    FROM
        saques s
    INNER JOIN
        usuarios u ON s.user_id = u.id
    WHERE
        s.status = 'pendente'
    ORDER BY
        s.updated_at DESC
    LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$saques_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Admin</title>
    <style>
        /* Global styles (reutilizados do seu projeto) */
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
            --bg-color: #13151b; /* Fundo principal do admin */
            --support-color: #00de93;
            --dark-bg-form: #1a1a1a;
            --darker-bg-form: #222222;
            --text-gray-light: #cccccc;
            --text-green-accent: #00de93;
            --border-color-input: #333333;
            --border-color-active: #00de93;

            /* Cores específicas do admin */
            --admin-header-bg: #1a1a1a; /* Fundo do cabeçalho do admin */
            --admin-sidebar-bg: #1a1a1a; /* Fundo do sidebar do admin */
            --admin-text-color: #e0e0e0; /* Cor do texto padrão no admin */
            --admin-link-hover: #00b37a; /* Cor do hover para links do admin */
            --admin-active-link-bg: rgba(0, 222, 147, 0.1); /* Fundo para link ativo */
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
            background-color: var(--bg-color); /* Fundo principal do corpo */
            color: var(--admin-text-color); /* Cor do texto padrão */
        }

        .shadow-rox {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 6px 12px -2px, rgba(0, 0, 0, 0.3) 0px 3px 7px -3px;
        }

        /* Notiflix styles (mantidos) */
        .notiflix-notify-success { background-color: var(--secondary-color) !important; color: white !important; }
        .notiflix-notify-info { background-color: var(--tertiary-color) !important; color: white !important; }
        .notiflix-notify-failure { background-color: #c0392b !important; color: white !important; }
        .notiflix-notify {
            top: 83px !important; z-index: 9999 !important; position: fixed !important;
            max-width: 90vw !important; width: 300px !important; right: 16px !important; left: auto !important;
            border-radius: 8px !important; overflow-wrap: break-word; box-sizing: border-box;
        }
        #NotiflixNotifyWrap .notiflix-notify { background-clip: padding-box !important; }

        /* Admin Layout Styles */
        .admin-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px; /* Altura do cabeçalho */
            background-color: var(--admin-header-bg);
            z-index: 50; /* Acima do conteúdo, abaixo do sidebar mobile */
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .admin-header .logo-mobile {
            height: 40px; /* Tamanho do logo no cabeçalho mobile */
            width: auto;
        }

        .admin-sidebar {
            position: fixed;
            top: 0; /* Em desktop, começa do topo */
            left: 0;
            width: 280px; /* Largura do sidebar em desktop */
            height: 100vh; /* Altura total da viewport em desktop */
            background-color: var(--admin-sidebar-bg);
            z-index: 60; /* Acima do cabeçalho mobile */
            display: flex;
            flex-direction: column;
            padding: 24px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease-in-out; /* Transição para mobile */
        }

        .admin-sidebar .logo-desktop {
            width: 180px;
            height: auto;
            margin-bottom: 32px;
        }

        .admin-sidebar-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
            color: var(--admin-text-color);
            text-decoration: none; /* Remove underline */
            font-size: 16px;
            font-weight: 500;
        }

        .admin-sidebar-item:hover {
            background-color: var(--admin-active-link-bg);
            color: var(--admin-link-hover);
        }

        .admin-sidebar-item.active {
            background-color: var(--admin-active-link-bg);
            color: var(--primary-color); /* Cor do link ativo */
            font-weight: 600;
        }

        .admin-sidebar-item i {
            font-size: 18px;
        }

        .admin-sidebar-item.support {
            color: #60a5fa; /* blue-400 */
        }
        .admin-sidebar-item.support i {
            color: #60a5fa;
        }
        .admin-sidebar-item.logout {
            color: #ef4444; /* red-500 */
        }
        .admin-sidebar-item.logout i {
            color: #ef4444;
        }

        .admin-main-content {
            margin-top: 80px; /* Espaço para o cabeçalho */
            margin-left: 280px; /* Espaço para o sidebar em desktop */
            padding: 24px;
            padding-bottom: 60px; /* Espaço para o rodapé */
            flex-grow: 1;
        }

        /* Assinatura NexCode - Discreta no rodapé */
        .nexcode-signature {
            position: fixed;
            bottom: 0;
            left: 280px; /* Alinhado com o conteúdo principal */
            right: 0;
            background-color: rgba(26, 26, 26, 0.95);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 24px;
            z-index: 30;
            backdrop-filter: blur(10px);
        }

        .nexcode-signature .signature-text {
            color: #666;
            font-size: 11px;
            text-align: center;
            font-weight: 400;
        }

        .nexcode-signature .nexcode-link {
            color: #888;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .nexcode-signature .nexcode-link:hover {
            color: var(--primary-color);
        }

        .nexcode-signature .phone-link {
            color: #666;
            text-decoration: none;
            margin-left: 4px;
            transition: color 0.2s ease;
        }

        .nexcode-signature .phone-link:hover {
            color: var(--primary-color);
        }

        .nexcode-signature .separator {
            color: #444;
            margin: 0 6px;
        }

        /* Mobile adjustments */
        @media (max-width: 1023px) { /* lg breakpoint */
            .admin-header .menu-toggle {
                display: block; /* Mostra o toggle em mobile */
            }
            .admin-header .logo-desktop {
                display: none; /* Esconde o logo desktop no cabeçalho mobile */
            }
            .admin-header .logo-mobile {
                display: block; /* Mostra o logo mobile no cabeçalho mobile */
            }

            .admin-sidebar {
                top: 80px; /* Começa abaixo do cabeçalho mobile */
                height: calc(100vh - 80px); /* Altura restante da viewport */
                width: 250px; /* Largura do sidebar mobile */
                transform: translateX(-100%); /* Esconde o sidebar por padrão */
                box-shadow: 4px 0 10px rgba(0, 0, 0, 0.5);
            }
            .admin-sidebar.is-open {
                transform: translateX(0); /* Mostra o sidebar */
            }

            .admin-sidebar .logo-desktop {
                display: none; /* Esconde o logo desktop no sidebar mobile */
            }

            .admin-main-content {
                margin-left: 0; /* Ocupa a largura total em mobile */
                padding-top: 24px; /* Padding superior para o conteúdo */
                padding-bottom: 60px; /* Espaço para o rodapé */
            }

            .nexcode-signature {
                left: 0; /* Ocupa toda a largura em mobile */
                padding: 6px 16px;
            }

            .nexcode-signature .signature-text {
                font-size: 10px;
            }

            /* Backdrop para o sidebar mobile */
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 55; /* Entre o header e o sidebar */
                display: none; /* Escondido por padrão */
            }
            .sidebar-backdrop.is-open {
                display: block;
            }
        }

        @media (min-width: 1024px) { /* lg breakpoint */
            .admin-header .menu-toggle {
                display: none; /* Esconde o toggle em desktop */
            }
            .admin-header .logo-mobile {
                display: none; /* Esconde o logo mobile no cabeçalho desktop */
            }
            .admin-sidebar {
                transform: translateX(0); /* Sempre visível em desktop */
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <i class="fa-solid fa-bars text-white text-2xl cursor-pointer menu-toggle"></i>
        <img src="<?php echo $logoSite ;?>" class="logo-mobile" alt="Logo"/>
        <div></div> <!-- Espaço para futuros elementos à direita -->
    </header>

    <!-- Sidebar Backdrop (for mobile) -->
    <div id="sidebar-backdrop" class="sidebar-backdrop"></div>

    <!-- Admin Sidebar -->
    <aside id="admin-sidebar" class="admin-sidebar">
        <img src="<?php echo $logoSite ;?>" class="logo-desktop" alt="Logo"/>
        <a href="/backoffice" class="admin-sidebar-item active">
            <i class="fa-solid fa-qrcode"></i> Início
        </a>
        <a href="config.php" class="admin-sidebar-item">
            <i class="fa-solid fa-gear"></i> Configurações
        </a>
        <a href="banners.php" class="admin-sidebar-item">
            <i class="fa-solid fa-images"></i> Banners
        </a>
        <a href="gateway.php" class="admin-sidebar-item">
            <i class="fa-solid fa-landmark"></i> Gateway
        </a>
        <a href="usuarios.php" class="admin-sidebar-item">
            <i class="fa-solid fa-user"></i> Usuários
        </a>
        <a href="afiliados.php" class="admin-sidebar-item">
            <i class="fa-solid fa-people-arrows"></i> Afiliados
        </a>
        <a href="cartelas.php" class="admin-sidebar-item">
            <i class="fa-solid fa-clover"></i> Raspadinhas
        </a>
        <a href="categorias.php" class="admin-sidebar-item">
            <i class="fa-solid fa-tags"></i> Categorias
        </a>
        <a href="depositos.php" class="admin-sidebar-item">
            <i class="fa-solid fa-dollar-sign"></i> Depósitos
        </a>
        <a href="saques.php" class="admin-sidebar-item">
            <i class="fa-solid fa-cash-register"></i> Saques
        </a>
        <div class="mt-auto pt-4 border-t border-gray-700/50"> <!-- Separador e empurra para baixo -->
            <a href="/" class="admin-sidebar-item">
                <i class="fa-solid fa-computer"></i> Plataforma
            </a>
            <a onclick="window.open('https://wa.me/5547999470479', '_blank')" class="admin-sidebar-item support">
                <i class="fa-solid fa-circle-info"></i> Suporte
            </a>
            <a href="/logout" class="admin-sidebar-item logout">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main-content">
        <div class="flex flex-col gap-1 mb-6">
            <h1 class="text-white text-2xl font-bold">Bem-vindo, <?= $nome ?>!</h1>
            <p class="text-gray-400 text-sm">Confira as principais informações do sistema</p>
        </div>

    </main>

    <!-- Assinatura NexCode - Discreta no rodapé -->
    <footer class="nexcode-signature">
        <div class="signature-text">
            Desenvolvido por 
            <a href="https://wa.me/5547999470479" target="_blank" class="nexcode-link">NexCode</a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.querySelector(".menu-toggle");
            const adminSidebar = document.getElementById("admin-sidebar");
            const sidebarBackdrop = document.getElementById("sidebar-backdrop");

            // Função para abrir o sidebar
            function openSidebar() {
                adminSidebar.classList.add("is-open");
                sidebarBackdrop.classList.add("is-open");
                document.body.style.overflow = 'hidden'; // Previne scroll do corpo
            }

            // Função para fechar o sidebar
            function closeSidebar() {
                adminSidebar.classList.remove("is-open");
                sidebarBackdrop.classList.remove("is-open");
                document.body.style.overflow = ''; // Restaura scroll do corpo
            }

            // Toggle do menu
            if (menuToggle) {
                menuToggle.addEventListener("click", () => {
                    if (adminSidebar.classList.contains("is-open")) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });
            }

            // Fechar sidebar ao clicar no backdrop
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener("click", closeSidebar);
            }

            // Fechar sidebar ao redimensionar para desktop
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) { // Tailwind's 'lg' breakpoint
                    closeSidebar(); // Garante que o sidebar esteja fechado em desktop se foi aberto em mobile
                }
            });

            // Adicionar classe 'active' ao item do menu atual
            const currentPath = window.location.pathname;
            document.querySelectorAll('.admin-sidebar-item').forEach(item => {
                const href = item.getAttribute('href');
                // Verifica se o href do item do menu corresponde ao caminho atual
                // ou se é a página inicial do backoffice e o caminho é /backoffice
                if (href && (currentPath.includes(href) || (href === '/backoffice' && currentPath === '/backoffice/'))) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active'); // Remove active de outros itens
                }
            });
        });
    </script>
</body>
</html>

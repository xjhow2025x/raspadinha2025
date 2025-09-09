<?php
@session_start();
if (file_exists('./conexao.php')) {
    include('./conexao.php');
} elseif (file_exists(__DIR__ . "/conexao.php")) {
    include('../inc/conexao.php');
}elseif (file_exists('../../inc/conexao.php')) {
    include('../../inc/conexao.php');
}

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['message'] = ['type' => 'failure', 'text' => 'Usuário Não existe!'];
        }
        if($usuario['banido'] == 1){
          $_SESSION = [];
          session_destroy();
          @session_start();
          $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você está banido!'];
          sleep(2);
          if($_SESSION['message']){
            header("Location: /");
          }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro na consulta!'];
        echo "Erro na consulta: " . $e->getMessage();
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@2.0.46/css/materialdesignicons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<style>
/* Estilos para a barra de download do app */
#app-download-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  background: #00FF88; /* Verde fluorescente */
  color: black;
  padding: 12px 16px;
  box-shadow: 0 2px 10px rgba(0, 255, 136, 0.3);
  transform: translateY(-100%);
  transition: transform 0.3s ease;
}

/* Garantir que os modais do sistema fiquem por cima da barra do app */
#depositModal, #withdrawModal, #taxaSaqueModal,
#backdrop2, #backdrop3, #backdrop4 {
  z-index: 100000 !important;
}

/* Garantir que o modal de saque seja sempre visível */
#withdrawModal {
  z-index: 100000 !important;
}

#withdrawModal .relative.z-10 {
  z-index: 100001 !important;
}

#closeWithdrawModal {
  z-index: 100002 !important;
}

/* Garantir que o backdrop do modal de saque seja visível */
#backdrop3 {
  z-index: 99999 !important;
}

/* Garantir que o modal de saque seja sempre visível quando ativo */
#withdrawModal:not(.hidden) {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
}

#withdrawModal:not(.hidden) .relative.z-10 {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
}

/* Garantir que nenhum elemento sobreponha o modal de saque */
#withdrawModal:not(.hidden) * {
  z-index: auto !important;
}

#withdrawModal:not(.hidden) .relative.z-10 {
  z-index: 100001 !important;
}

#withdrawModal:not(.hidden) #closeWithdrawModal {
  z-index: 100002 !important;
  pointer-events: auto !important;
  cursor: pointer !important;
}

/* Garantir que o botão seja sempre clicável */
#closeWithdrawModal {
  pointer-events: auto !important;
  cursor: pointer !important;
  user-select: none !important;
}

/* Garantir que o modal de taxa de saque seja sempre visível */
#taxaSaqueModal:not(.hidden) {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
}

#taxaSaqueModal:not(.hidden) .relative.z-10 {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
}

/* Garantir que o botão de fechar da taxa seja sempre clicável */
#closeTaxaSaqueModal {
  pointer-events: auto !important;
  cursor: pointer !important;
  user-select: none !important;
}

/* Garantir que nenhum elemento sobreponha o modal de taxa */
#taxaSaqueModal:not(.hidden) * {
  z-index: auto !important;
}

#taxaSaqueModal:not(.hidden) .relative.z-10 {
  z-index: 100001 !important;
}

#taxaSaqueModal:not(.hidden) #closeTaxaSaqueModal {
  z-index: 100002 !important;
}

#app-download-bar.show {
  transform: translateY(0);
}

/* Ajuste do header quando a barra está visível */
body.has-app-bar {
  padding-top: 60px;
}

body.has-app-bar .header-main {
  top: 60px;
}

/* Ajuste específico apenas para banners principais quando a barra do app está visível */
body.has-app-bar .banner-ajuste-app {
  margin-top: 60px !important;
  transition: margin-top 0.3s ease;
}

/* Garantir que apenas banners específicos sejam afetados */
body.has-app-bar img[alt*="Banner Principal"].banner-ajuste-app {
  margin-top: 60px !important;
  transition: margin-top 0.3s ease;
}

/* Garantir que o backoffice não seja afetado pela barra do app */
body.has-app-bar .backoffice-page,
body.has-app-bar .backoffice-page #content-admin,
body.has-app-bar .backoffice-page * {
  margin-top: 0 !important;
  padding-top: 0 !important;
}

/* Garantir que o backoffice tenha o layout correto */
.backoffice-page #content-admin {
  margin-top: 80px !important;
  padding: 24px 42px !important;
}

/* Responsividade */
@media (max-width: 768px) {
  #app-download-bar {
    padding: 10px 12px;
  }
  
  #app-download-bar .text-sm {
    font-size: 0.875rem;
  }
  
  #app-download-bar .text-xs {
    font-size: 0.75rem;
  }
}
</style>

<!-- Gerenciador de Saldo Global -->
<script src="/assets/js/saldo-manager.js"></script>

<?php include_once(__DIR__ . '/../components/app-download-bar.php'); ?>
<header class="header-main">
    <div class="header-content-wrapper">
      <div class="header-left-section">
        <!-- O botão do menu lateral foi removido daqui -->
        <a href="/" class="header-logo-link">
            <img src="<?php echo $logoSite ;?>" class="header-logo" alt="Logo">
        </a>
        <a href="/" class="header-rasp-link">Raspadinhas</a>
      </div>

        <?php if(!isset($usuario)): ?>
        <div class="header-right-section">
            <a href="/cadastro" class="register-link">
                <i class="fa-solid fa-user-plus"></i> Cadastrar
            </a>
            <a href="/login" class="login-button">
                Entrar <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </a>
        </div>
        <?php else: ?>
        <div class="header-right-section-logged-in">
            <p id="headerSaldo" class="balance-display">
                R$ <?php echo number_format($usuario['saldo'], 2, ',', ''); ?>
            </p>
            <a onclick="openDepositModal()" class="btn-reflex deposit-button">
                <i class="fa-solid fa-plus"></i> Depositar
            </a>
            <i class="fa-solid fa-cart-shopping text-white text-lg cursor-pointer"></i>

            <div class="relative">
                <button id="userDropdownBtn" class="user-profile-button-logged-in">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr(htmlspecialchars(explode(' ', $usuario['nome'])[0]), 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name-display"><?php echo htmlspecialchars(explode(' ', $usuario['nome'])[0]); ?></span>
                        <span class="view-profile-text">Ver perfil</span>
                    </div>
                    <i class="fa fa-chevron-down text-xs"></i>
                </button>
                <div id="userDropdownMenu" class="profile-dropdown-menu-logged-in hidden">
                    <div class="dropdown-header">
                        <p class="dropdown-username"><?php echo htmlspecialchars(explode(' ', $usuario['nome'])[0]); ?></p>
                        <p class="dropdown-welcome">Bem-vindo de volta!</p>
                    </div>
                    <?php if($usuario['admin'] == 1):?>
                    <a href="/backoffice" class="dropdown-menu-item-logged-in"><i class="fa-solid fa-user-gear"></i> Admin</a>
                    <?php endif; ?>
                    <a href="/" class="dropdown-menu-item-logged-in"><i class="fa-solid fa-layer-group"></i> Jogar</a>
                    <a href="/perfil" class="dropdown-menu-item-logged-in"><i class="fa-solid fa-user"></i> Perfil</a>
                    <a href="/afiliados" class="dropdown-menu-item-logged-in"><i class="fa-solid fa-users"></i> Indique e Ganhe</a>
                    <a onclick="openDepositModal()" class="dropdown-menu-item-logged-in cursor-pointer"><i class="fa-solid fa-circle-plus"></i> Depósito</a>
                    <a onclick="openWithdrawModal(<?php echo $usuario['saldo'];?>)" class="dropdown-menu-item-logged-in cursor-pointer"><i class="fa-solid fa-circle-minus"></i> Saque</a>
                    <a href="/transacoes" class="dropdown-menu-item-logged-in"><i class="fa-solid fa-right-left"></i> Transações</a>
                    <a href="/apostas" class="dropdown-menu-item-logged-in"><i class="fa-solid fa-ticket"></i> Apostas</a>
                    <a href="/logout" class="dropdown-menu-item-logged-in logout-button-text-logged-in"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const btn = document.getElementById('userDropdownBtn');
                const menu = document.getElementById('userDropdownMenu');
                if (btn && menu) { // Check if elements exist
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        menu.classList.toggle('hidden');
                        // Adiciona ou remove a classe 'is-open' para controlar o display flex
                        if (!menu.classList.contains('hidden')) {
                            menu.classList.add('is-open');
                        } else {
                            menu.classList.remove('is-open');
                        }
                    });
                    document.addEventListener('click', function (e) {
                        if (!menu.contains(e.target) && !btn.contains(e.target)) {
                            menu.classList.add('hidden');
                            menu.classList.remove('is-open'); // Garante que 'is-open' seja removida
                        }
                    });
                }
            });

            // Função global para atualizar saldo em tempo real
            window.atualizarSaldoGlobal = async function() {
                try {
                    const res = await fetch('/api/get_saldo.php');
                    const json = await res.json();

                    if (json.success) {
                        const saldoFormatado = 'R$ ' + json.saldo.toFixed(2).replace('.', ',');
                        
                        // Atualizar elemento principal do header
                        const headerSaldo = document.getElementById('headerSaldo');
                        if (headerSaldo) {
                            headerSaldo.textContent = saldoFormatado;
                        }
                        
                        // Atualizar também elementos com classe balance-display (fallback)
                        const balanceElements = document.querySelectorAll('.balance-display');
                        balanceElements.forEach(el => {
                            el.textContent = saldoFormatado;
                        });
                        
                        // Atualizar elementos de saldo em modais e outras páginas
                        const saldoElements = document.querySelectorAll('[data-saldo], .saldo-display, .user-balance, .balance-amount');
                        saldoElements.forEach(el => {
                            el.textContent = saldoFormatado;
                        });
                        
                        console.log('Saldo atualizado globalmente:', saldoFormatado);
                        
                        // Disparar evento personalizado para notificar outras partes da aplicação
                        document.dispatchEvent(new CustomEvent('saldoUpdated', {
                            detail: { saldo: json.saldo, saldoFormatado: saldoFormatado }
                        }));
                        
                        return json.saldo;
                    } else {
                        console.warn('Erro ao buscar saldo:', json.error);
                        return null;
                    }
                } catch (e) {
                    console.error('Erro na requisição de saldo:', e);
                    return null;
                }
            };

            // Função para notificar mudança de saldo (para usar após transações)
            window.notificarMudancaSaldo = function() {
                setTimeout(() => {
                    if (typeof window.atualizarSaldoGlobal === 'function') {
                        window.atualizarSaldoGlobal();
                    }
                }, 500); // Pequeno delay para garantir que a transação foi processada
            };

            // Atualizar saldo automaticamente a cada 30 segundos (opcional)
            setInterval(function() {
                if (typeof window.atualizarSaldoGlobal === 'function') {
                    window.atualizarSaldoGlobal();
                }
            }, 30000);
        </script>
        <?php endif;?>
    </div>
</header>

<!-- O sidebar e seu backdrop foram removidos daqui -->

<!-- Novo Menu de Navegação Inferior para Mobile (agora no header.php) -->
<nav class="mobile-bottom-nav">
    <a href="/" class="mobile-nav-item">
        <i class="fa-solid fa-gamepad"></i>
    </a>
    <a href="/apostas" class="mobile-nav-item">
        <i class="fa-solid fa-cart-shopping"></i>
    </a>
    <a onclick="openDepositModal()" class="mobile-nav-item mobile-nav-item-highlight">
        <i class="fa-solid fa-wallet"></i>
    </a>
    <a href="/afiliados" class="mobile-nav-item">
        <i class="fa-solid fa-gift"></i>
    </a>
    <a href="/transacoes" class="mobile-nav-item">
        <i class="fa-solid fa-wallet"></i>
    </a>
</nav>

<style>
    /* Desabilitar zoom por double-click e touch */
    * {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    
    /* Permitir seleção de texto em inputs e áreas de texto */
    input, textarea, [contenteditable] {
        -webkit-user-select: text;
        -khtml-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
        user-select: text;
    }
    
    /* Desabilitar zoom por double-tap no mobile */
    html {
        -ms-touch-action: manipulation;
        touch-action: manipulation;
    }
    
    /* Custom CSS Variables for consistent colors */
    :root {
        --header-bg-color: #0D1F0D; /* Fundo verde escuro para combinar */
        --primary-green-color: #00FF88; /* Verde fluorescente */
        --text-white: #ffffff;
        --text-gray: #cccccc; /* For "Cadastrar" text */
        --button-hover-green: #00CC66; /* Verde mais escuro no hover */
        --dark-bg-color: #0D2D0D; /* Fundo verde escuro para dropdown */
        --darker-bg-color: #0D1F0D; /* Verde ainda mais escuro */
    }

    /* Header Main */
    .header-main {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 50;
        width: 100%;
        background: linear-gradient(135deg, #0D1F0D 0%, #1a1a1a 50%, #0D2D0D 100%); /* Gradiente verde */
        border-bottom: 1px solid #00FF88; /* Borda mais fina e verde fluorescente */
        height: 80px; /* Adjusted to match screenshot height */
        padding: 0 24px; /* px-6 */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* shadow-md */
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Header Content Wrapper */
    .header-content-wrapper {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Header Left Section (Logo + Raspadinhas) */
    .header-left-section {
        display: flex;
        align-items: center;
        gap: 12px; /* gap-3 */
    }

    .header-logo {
        display: none; /* hidden */
        width: 180px;
    }
    @media (min-width: 768px) { /* md breakpoint */
        .header-logo {
            display: flex; /* md:flex */
        }
    }
    /* Ajuste para o logo mobile */
    .header-logo.mobile { /* For the smaller logo on mobile */
        display: flex; /* flex */
        width: 70px;
    }
    @media (min-width: 768px) { /* md breakpoint */
        .header-logo.mobile {
            display: none; /* md:hidden */
        }
    }

    .header-rasp-link {
        color: var(--primary-green-color);
        font-size: 18px; /* text-lg */
        font-weight: 500; /* font-medium */
        text-decoration: none;
    }
    /* Oculta "Raspadinhas" em mobile */
    @media (max-width: 767px) {
        .header-rasp-link {
            display: none;
        }
        /* Garante que o logo principal (que é o mesmo elemento com a classe .mobile) seja visível */
        .header-logo {
            display: flex; /* Força a exibição do logo em mobile */
            width: 70px; /* Garante o tamanho correto do logo mobile */
        }
    }


    /* Header Right Section (Cadastrar + Entrar) */
    .header-right-section {
        display: flex;
        align-items: center;
        gap: 24px; /* gap-6 */
    }

    .register-link {
        color: var(--text-white);
        display: flex;
        align-items: center;
        gap: 8px; /* gap-2 */
        text-decoration: none;
        font-size: 16px; /* text-base */
    }
    .register-link:hover {
        opacity: 0.9;
    }

    .login-button {
        background-color: var(--primary-green-color);
        color: var(--text-white);
        font-weight: 600; /* font-semibold */
        padding: 8px 16px; /* px-4 py-2 */
        border-radius: 8px; /* rounded-lg */
        font-size: 14px; /* text-sm */
        display: flex;
        align-items: center;
        gap: 8px; /* gap-2 */
        text-decoration: none;
        transition: background-color 0.3s ease;
    }
    .login-button:hover {
        background-color: var(--button-hover-green);
    }

    /* Styles for logged-in state */
    .header-right-section-logged-in {
        position: relative;
        display: flex;
        align-items: center;
        gap: 16px; /* gap-4 */
        color: var(--text-white);
    }

    .balance-display {
        color: var(--text-white); /* Changed to white as per screenshot */
        font-size: 18px; /* text-lg */
        font-weight: 600; /* font-semibold */
    }

    .deposit-button {
        background-color: var(--primary-green-color);
        color: var(--text-white);
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: flex; /* To align icon and text */
        align-items: center;
        gap: 8px;
    }
    .deposit-button:hover {
        background-color: var(--button-hover-green);
    }

    .user-profile-button-logged-in {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-white);
        cursor: pointer;
        transition: background-color 0.3s ease;
        background-color: var(--dark-bg-color); /* Background for the user button */
        padding: 8px 12px; /* Padding for the user button */
        border-radius: 8px; /* Rounded corners */
    }
    .user-profile-button-logged-in:hover {
        background-color: #333333; /* Slightly lighter on hover */
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: var(--primary-green-color); /* Green circle */
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 14px;
        font-weight: bold;
        color: black; /* Text color inside avatar */
    }

    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        line-height: 1.2;
    }

    .user-name-display {
        font-size: 14px;
        font-weight: 600;
    }

    .view-profile-text {
        font-size: 12px;
        color: var(--text-gray);
    }

    .profile-dropdown-menu-logged-in {
        position: absolute;
        right: 0;
        top: 100%; /* Position below the button */
        margin-top: 10px; /* Small gap */
        width: 250px; /* Adjusted width as per screenshot */
        background-color: var(--dark-bg-color); /* Dark background for dropdown */
        border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle border */
        border-radius: 8px; /* rounded-lg */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); /* Deeper shadow */
        z-index: 50;
        font-size: 14px; /* text-sm */
        flex-direction: column; /* Keep flex-direction for internal layout */
        padding: 8px 0; /* Padding inside dropdown */
    }

    /* Nova classe para controlar o display do dropdown */
    .profile-dropdown-menu-logged-in.is-open {
        display: flex;
    }

    .dropdown-header {
        padding: 12px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        margin-bottom: 8px;
    }

    .dropdown-username {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-white);
    }

    .dropdown-welcome {
        font-size: 12px;
        color: var(--text-gray);
    }

    .dropdown-menu-item-logged-in {
        display: flex; /* Use flex for icon and text alignment */
        align-items: center;
        width: 100%;
        padding: 10px 16px; /* Adjusted padding */
        transition: background-color 0.3s ease;
        text-align: left; /* Align text to left */
        color: var(--text-white); /* Default text color */
        text-decoration: none; /* Remove underline */
    }
    .dropdown-menu-item-logged-in i {
        margin-right: 12px; /* Space between icon and text */
        font-size: 16px; /* Icon size */
        color: var(--text-gray); /* Icon color */
    }
    .dropdown-menu-item-logged-in:hover {
        background-color: rgba(255, 255, 255, 0.05); /* Subtle hover effect */
    }

    .logout-button-text-logged-in {
        color: #f87171; /* Red color for logout */
        margin-top: 8px; /* Space above logout button */
        border-top: 1px solid rgba(255, 255, 255, 0.05); /* Separator line */
        padding-top: 12px; /* Padding above the line */
    }
    .logout-button-text-logged-in i {
        color: #f87171; /* Ensure icon is also red */
    }
    .logout-button-text-logged-in:hover {
        background-color: rgba(248, 113, 113, 0.1); /* Reddish hover */
    }

    /* Responsive adjustments */
    @media (max-width: 767px) { /* Mobile styles for screens smaller than 768px */
        .header-main {
            height: 60px; /* Slightly smaller header on mobile */
            padding: 0 16px; /* px-4 */
        }

        .header-left-section {
            gap: 8px; /* Smaller gap on mobile */
        }

        /* Logged out section */
        .header-right-section {
            gap: 12px; /* Smaller gap on mobile */
        }

        .register-link,
        .login-button {
            font-size: 13px; /* Smaller font for buttons */
            padding: 6px 10px; /* Smaller padding */
        }

        /* Logged in section */
        .header-right-section-logged-in {
            gap: 8px; /* Smaller gap on mobile */
        }

        .balance-display {
            font-size: 16px; /* Smaller font for balance */
        }

        .deposit-button {
            padding: 6px 10px; /* Smaller padding for deposit button */
            font-size: 13px; /* Smaller font */
        }

        .fa-cart-shopping {
            font-size: 18px; /* Slightly smaller cart icon */
        }

        .user-profile-button-logged-in {
            padding: 6px 8px; /* Smaller padding for user button */
            gap: 4px; /* Smaller gap */
        }

        .user-avatar {
            width: 28px; /* Smaller avatar */
            height: 28px;
            font-size: 12px; /* Smaller font in avatar */
        }

        .user-info {
            display: none; /* Hide user name and "Ver perfil" on small screens */
        }

        .user-profile-button-logged-in .fa.fa-chevron-down {
            margin-left: 0; /* Remove extra margin if name is hidden */
        }

        .profile-dropdown-menu-logged-in {
            width: 180px; /* Smaller width for dropdown on mobile */
            left: auto; /* Reset left */
            right: 0; /* Align to right */
            transform: none; /* Remove transform */
        }

        .dropdown-header {
            padding: 8px 12px; /* Smaller padding */
        }

        .dropdown-username {
            font-size: 14px;
        }

        .dropdown-welcome {
            font-size: 11px;
        }

        .dropdown-menu-item-logged-in {
            padding: 8px 12px; /* Smaller padding for dropdown items */
            font-size: 13px;
        }

        .dropdown-menu-item-logged-in i {
            margin-right: 8px; /* Smaller margin for icons */
            font-size: 14px;
        }
    }

    /* General utility styles (kept from previous versions) */
    .link-underline {
        position: relative;
    }
    .link-underline::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 2px;
        width: 0;
        background: linear-gradient(
            to right,
            transparent,
            var(--primary-green-color), /* Using primary-green-color */
            transparent
        );
        transition: width 0.4s ease-in-out;
        pointer-events: none;
    }
    .link-underline:hover::after {
        width: 100%;
    }
    .btn-reflex {
        position: relative;
        overflow: hidden;
    }
    .btn-reflex::before {
        content: '';
        position: absolute;
        top: 0;
        left: -120%;
        width: 60%;
        height: 100%;
        background: rgba(255, 255, 255, 0.45);
        transform: skewX(-20deg);
        opacity: 0;
        z-index: 1;
        transition:
            left 0.7s ease-out,
            opacity 0.15s ease-out;
        pointer-events: none;
    }
    .btn-reflex:hover::before {
        left: 140%;
        opacity: 1;
    }
    @keyframes shine {
        to {
            left: 150%;
        }
    }
    /* O sidebar-transition foi removido */

    /* New Mobile Bottom Nav Styles (agora no header.php) */
    .mobile-bottom-nav {
        display: none; /* Hidden by default, shown on mobile */
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 70px; /* Height of the bottom bar */
        background: linear-gradient(135deg, #0D1F0D 0%, #1a1a1a 50%, #0D2D0D 100%); /* Gradiente verde */
        border-top: 2px solid #00FF88; /* Borda verde fluorescente */
        z-index: 60; /* Above other content */
        justify-content: space-around;
        align-items: center;
        padding: 0 10px;
    }

    @media (max-width: 767px) {
        .mobile-bottom-nav {
            display: flex; /* Show on mobile */
        }
        /* O footer-main display: none; foi removido daqui, pois o footer principal não é mais controlado por este arquivo */
    }

    .mobile-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #ffffff; /* White icons */
        font-size: 22px; /* Icon size */
        text-decoration: none;
        height: 100%;
        transition: color 0.3s ease;
        text-align: center; /* Adicionado para centralizar o ícone */
        padding: 0 5px; /* Adicionado um padding horizontal para garantir um mínimo de espaço */
    }

    .mobile-nav-item:hover {
        color: var(--primary-green-color); /* Green on hover */
    }

    .mobile-nav-item-highlight {
        background-color: var(--primary-green-color); /* Green circle background */
        border-radius: 50%;
        width: 55px; /* Tamanho fixo da bolinha */
        height: 55px; /* Tamanho fixo da bolinha */
        flex-shrink: 0; /* Garante que não encolha */
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        top: -15px; /* Lift it up slightly */
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3); /* Shadow for depth */
        color: #1a1a1a; /* Dark icon inside green circle */
        font-size: 24px; /* Slightly larger icon */
    }

    .mobile-nav-item-highlight i {
        color: #1a1a1a; /* Ensure icon inside is dark */
    }

    .mobile-nav-item-highlight:hover {
        color: #1a1a1a; /* Keep dark on hover */
        background-color: var(--button-hover-green); /* Slightly darker green on hover */
    }

        /* Quick Amount Button Styles */
        .quick-amount-new {
            position: relative;
            overflow: visible; /* Mudança para permitir que o badge apareça por cima */
            transition: all 0.3s ease;
        }
        .quick-amount-new.selected {
            background: linear-gradient(135deg, #00FF88, #00CC66) !important;
            color: black !important;
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 255, 136, 0.3);
        }
        .quick-amount-new span.badge {
            position: absolute;
            top: -8px; /* Posicionado acima do botão */
            left: 50%; /* Centralizado horizontalmente */
            transform: translateX(-50%); /* Centraliza perfeitamente */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background-color: #FBBF24; /* Yellow background */
            color: #1A1A1A; /* Black text */
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 9999px;
            white-space: nowrap;
            z-index: 50; /* Z-index alto para ficar por cima */
            box-shadow: 0 2px 8px rgba(0,0,0,0.3); /* Sombra para destacar */
        }
        .quick-amount-new span.badge i {
            font-size: 10px;
            color: #1A1A1A;
        }
        
        /* Modal posicionado à direita ocupando toda a tela */
        .deposit-modal-container {
            position: fixed;
            top: 0;
            right: 0;
            width: 100vw;
            height: 100vh;
            max-width: 500px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #34D399 #1A1A1A;
            z-index: 60;
        }
        
        .deposit-modal-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .deposit-modal-container::-webkit-scrollbar-track {
            background: #1A1A1A;
            border-radius: 3px;
        }
        
        .deposit-modal-container::-webkit-scrollbar-thumb {
            background: #00FF88;
            border-radius: 3px;
        }
        
        .deposit-modal-container::-webkit-scrollbar-thumb:hover {
            background: #2cb27a;
        }
        
        /* Conteúdo do modal com altura mínima garantida e padding inferior para o botão */
        .deposit-modal-content {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 1rem 1.5rem 4rem 1.5rem; /* Padding inferior maior para garantir espaço para o botão */
        }
        
        /* Melhorias nos inputs */
        .deposit-input {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #333;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .deposit-input:focus {
            border-color: #00FF88;
            box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1), inset 0 2px 4px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        
        .deposit-input:hover {
            border-color: #444;
        }
        
        /* Animações suaves */
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Responsividade - sempre 2x2 com espaço para badges */
        .quick-amount-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 0.75rem;
            margin-top: 1rem; /* Espaço superior para os badges */
            margin-bottom: 1.5rem;
        }
        
        /* Garantir que o botão sempre apareça e tenha espaço suficiente */
        .form-submit-section {
            margin-top: 2rem;
            padding-bottom: 4rem; /* Padding ainda maior para iPhone */
            min-height: 80px; /* Altura mínima garantida */
        }
        
        /* Banner mais compacto */
        #bannerImage {
            max-height: 120px; /* Altura máxima reduzida */
            object-fit: cover;
            width: 100%;
        }
        
        /* Melhorias específicas para iPhone e dispositivos móveis */
        @media (max-width: 640px) {
            .deposit-modal-container {
                width: 100vw;
                max-width: 100vw;
                padding-bottom: 5rem; /* Padding extra no container para iPhone */
            }
            
            .deposit-modal-content {
                padding: 0.75rem 1rem 5rem 1rem; /* Padding bottom maior para iPhone */
                min-height: calc(100vh - 2rem); /* Garantir altura mínima */
            }
            
            .form-submit-section {
                padding-bottom: 5rem; /* Padding ainda maior no iPhone */
                margin-bottom: 2rem; /* Margem adicional */
            }
            
            .quick-amount-new {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
            
            .deposit-input {
                padding: 0.875rem 1rem;
                font-size: 0.875rem;
            }
            
            #bannerImage {
                max-height: 100px; /* Altura ainda menor no mobile */
            }
        }
        
        /* Melhorias específicas para iPhone (viewport pequeno) */
        @media (max-width: 480px) and (max-height: 800px) {
            .form-submit-section {
                padding-bottom: 6rem; /* Padding extra para iPhones menores */
            }
            
            .deposit-modal-content {
                padding-bottom: 6rem; /* Padding extra no conteúdo */
            }
        }
    </style>
    <!-- Modal Depósito -->
    <div id="backdrop2" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-50 hidden"></div>
    <section id="depositModal" class="fixed inset-0 z-50 hidden">
      <div class="deposit-modal-container">
        <div class="deposit-modal-content relative bg-gradient-to-br from-[#1A1A1A] to-[#0E1015] text-white border-l border-gray-800 fade-in">
          <!-- Botão X menor e melhor posicionado -->
          <button id="closeDepositModal"
                  class="absolute right-3 top-3 bg-gradient-to-r from-[#00FF88] to-[#00CC66] hover:from-[#00CC66] hover:to-[#00AA55]
                  text-white rounded-full w-8 h-8 flex items-center justify-center cursor-pointer shadow-lg transition-all duration-300 hover:scale-110 z-20">
            <i class="fa-solid fa-xmark text-sm"></i>
          </button>
          
          <div class="pt-5"> <!-- Padding top reduzido -->
            <!-- Banner dinâmico de depósito -->
            <?php
            // Buscar banner de depósito ativo do banco de dados
            try {
                $sql = "SELECT * FROM banners WHERE ativo = 1 AND tipo = 'deposito'
                      AND (data_inicio IS NULL OR data_inicio <= NOW()) 
                      AND (data_fim IS NULL OR data_fim >= NOW()) 
                      ORDER BY ordem ASC, created_at DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $banner_deposito = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $banner_deposito = null;
            }
            
            // Se não encontrar banner de depósito, usar o padrão
            if (!$banner_deposito) {
                $banner_src = "https://i.imgur.com/wuvfhJh.png";
                $banner_alt = "Oferta Limitada";
                $banner_link = "#";
            } else {
                $banner_src = $banner_deposito['imagem'];
                $banner_alt = htmlspecialchars($banner_deposito['titulo']);
                $banner_link = $banner_deposito['link'] ?? '#';
            }
            ?>
            
            <?php if (!empty($banner_link) && $banner_link != '#'): ?>
            <a href="<?= htmlspecialchars($banner_link) ?>" target="_blank">
            <?php endif; ?>
                <img id="bannerImage" src="<?= htmlspecialchars($banner_src) ?>" alt="<?= $banner_alt ?>" class="w-full rounded-xl mb-4 shadow-lg">
            <?php if (!empty($banner_link) && $banner_link != '#'): ?>
            </a>
            <?php endif; ?>

            <!-- Banner de QR Code (será mostrado quando QR for gerado) -->
            <?php
            // Buscar banner de QR code ativo do banco de dados
            try {
                $sql = "SELECT * FROM banners WHERE ativo = 1 AND tipo = 'qrcode'
                      AND (data_inicio IS NULL OR data_inicio <= NOW()) 
                      AND (data_fim IS NULL OR data_fim >= NOW()) 
                      ORDER BY ordem ASC, created_at DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $banner_qrcode = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $banner_qrcode = null;
            }
            
            // Se não encontrar banner de QR code, usar o padrão
            if (!$banner_qrcode) {
                $qr_banner_src = "https://i.imgur.com/wuvfhJh.png";
                $qr_banner_alt = "Oferta Limitada";
                $qr_banner_link = "#";
            } else {
                $qr_banner_src = $banner_qrcode['imagem'];
                $qr_banner_alt = htmlspecialchars($banner_qrcode['titulo']);
                $qr_banner_link = $banner_qrcode['link'] ?? '#';
            }
            ?>
            
            <?php if (!empty($qr_banner_link) && $qr_banner_link != '#'): ?>
            <a href="<?= htmlspecialchars($qr_banner_link) ?>" target="_blank">
            <?php endif; ?>
                <img id="qrBannerImage" src="<?= htmlspecialchars($qr_banner_src) ?>" alt="<?= $qr_banner_alt ?>" class="w-full rounded-xl mb-4 shadow-lg hidden">
            <?php if (!empty($qr_banner_link) && $qr_banner_link != '#'): ?>
            </a>
            <?php endif; ?>

            <div id="securePayment" class="bg-gradient-to-r from-[#1E2D3D] to-[#2a3441] p-2.5 rounded-xl flex items-center gap-2 mb-4 border border-[#00FF88]/20">
              <div class="w-5 h-5 bg-[#00FF88] rounded-full flex items-center justify-center">
                <i class="fa-solid fa-shield-halved text-black text-xs"></i>
              </div>
              <span class="text-white text-xs">Pagamento seguro</span> <!-- Texto menor -->
            </div>
            
            <!-- Grid com espaço para badges -->
            <div class="quick-amount-grid grid grid-cols-2 gap-3">
              <button type="button" data-value="20" class="quick-amount-new bg-gradient-to-r from-[#00FF88] to-[#00CC66] text-black font-semibold py-4 px-3 rounded-xl transition-all duration-300 relative selected hover:scale-105 shadow-lg">
                <span class="badge"><i class="fa-solid fa-fire"></i>+Querido</span> 
                <div class="text-base font-bold">R$ 20,00</div>
              </button>
              <button type="button" data-value="40" class="quick-amount-new bg-gradient-to-r from-[#2a2a2a] to-[#1e1e1e] text-white font-semibold py-4 px-3 rounded-xl transition-all duration-300 relative hover:scale-105 shadow-lg border border-gray-700">
                <span class="badge"><i class="fa-solid fa-fire"></i>+Recomendado</span> 
                <div class="text-base font-bold">R$ 40,00</div>
              </button>
              <button type="button" data-value="80" class="quick-amount-new bg-gradient-to-r from-[#2a2a2a] to-[#1e1e1e] text-white font-semibold py-4 px-3 rounded-xl transition-all duration-300 relative hover:scale-105 shadow-lg border border-gray-700">
                <span class="badge"><i class="fa-solid fa-fire"></i>+Chances</span> 
                <div class="text-base font-bold">R$ 80,00</div>
              </button>
              <button type="button" data-value="200" class="quick-amount-new bg-gradient-to-r from-[#2a2a2a] to-[#1e1e1e] text-white font-semibold py-4 px-3 rounded-xl transition-all duration-300 relative hover:scale-105 shadow-lg border border-gray-700">
                <span class="badge"><i class="fa-solid fa-fire"></i>+Chances</span> 
                <div class="text-base font-bold">R$ 200,00</div>
              </button>
            </div>

            <form id="depositForm" class="space-y-5 mt-6">
              <div class="relative">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                  <i class="fa-solid fa-dollar-sign text-[#00FF88] mr-2"></i>Valor do depósito
                </label>
                <div class="relative">
                  <span class="absolute top-1/2 left-4 -translate-y-1/2 text-[#00FF88] text-lg font-bold"></span>
                  <input type="text" name="amount" id="amountInput" required
                         class="deposit-input pl-12 pr-4 py-4 w-full rounded-xl text-white text-lg placeholder:text-gray-500 border-none focus:ring-0 focus:outline-none"
                         placeholder="0,00" inputmode="numeric" value="20,00">
                </div>
              </div>
              
              <div class="relative">
                <label class="block text-sm font-medium text-gray-300 mb-2">
                  <i class="fa-solid fa-id-card text-[#00FF88] mr-2"></i>CPF do titular
                </label>
                <div class="relative">
                  <span class="absolute top-1/2 left-4 -translate-y-1/2 text-[#00FF88] text-sm font-medium"></span>
                  <input type="text" name="cpf" id="cpfInput" required maxlength="14"
                         class="deposit-input pl-16 pr-4 py-4 w-full rounded-xl text-white text-lg placeholder:text-gray-500 border-none focus:ring-0 focus:outline-none peer"
                         placeholder="000.000.000-00">
                  <span id="cpfError" class="absolute right-4 top-1/2 -translate-y-1/2 text-red-400 text-sm opacity-0 transition-all duration-300">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                  </span>
                </div>
              </div>
              
              <div class="flex justify-between text-sm text-gray-400 px-1">
                <span><i class="fa-solid fa-info-circle mr-1"></i>Mínimo: R$ <?php echo isset($depositoMin) ? $depositoMin : 5; ?></span>
                <span>Máximo: R$ 5.000,00</span>
              </div>
              
              <!-- Seção do botão com espaço garantido -->
              <div class="form-submit-section">
                <button type="submit"
                        class="w-full bg-gradient-to-r from-[#00FF88] to-[#00CC66] hover:from-[#00CC66] hover:to-[#00AA55]
                        text-black font-bold py-4 rounded-xl transition-all duration-300 cursor-pointer flex items-center justify-center gap-3 shadow-lg hover:shadow-xl hover:scale-[1.02]">
                  <i class="fa-solid fa-qrcode text-lg"></i> 
                  <span class="text-lg">Gerar PIX Instantâneo</span>
                </button>
              </div>
            </form>

            <div id="qrArea" class="mt-2 hidden text-center fade-in pb-8">
              <!-- Timer de 5 minutos -->
              <div class="mb-4">
                <div class="bg-gradient-to-r from-[#1E2D3D] to-[#2a3441] p-3 rounded-xl border border-[#00FF88]/20">
                  <div class="flex items-center justify-center gap-2 mb-2">
                    <i class="fa-solid fa-clock text-[#00FF88] text-sm"></i>
                    <span class="text-white text-sm font-medium">O tempo para você pagar acaba em:</span>
                  </div>
                  <div class="text-center">
                    <div id="paymentTimer" class="text-2xl font-bold text-white mb-2">05:00</div>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                      <div id="timerProgress" class="bg-[#00FF88] h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Header mais compacto e elegante -->
              <div class="mb-4">
                <div class="flex items-center justify-center gap-2 mb-2">
                  <div class="w-6 h-6 bg-[#00FF88] rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-check text-black text-xs"></i>
                  </div>
                  <h3 class="text-base font-semibold text-[#00FF88]">PIX Gerado</h3>
                </div>
                <p class="text-gray-400 text-xs">Escaneie o QR Code ou copie o código PIX</p>
              </div>
              
              <!-- QR Code mais elegante -->
              <div class="mb-4">
                <div class="bg-white p-3 rounded-2xl inline-block shadow-xl border-4 border-[#00FF88]/20">
                  <img id="qrImg" src="/placeholder.svg" alt="QR Code" class="w-36 h-36 mx-auto">
                </div>
              </div>

              <!-- Método de pagamento seguro -->
              <div class="mb-4">
                <div class="bg-[#00FF88] text-black px-4 py-2 rounded-lg inline-flex items-center gap-2">
                  <i class="fa-solid fa-check text-sm"></i>
                  <span class="text-sm font-medium">Método de pagamento seguro</span>
                </div>
              </div>
              
              <!-- Campo de código PIX melhorado -->
              <div class="space-y-3">
                <div class="relative">
                  <label class="block text-sm font-medium text-gray-300 mb-2 text-left">
                    <i class="fa-solid fa-copy text-[#00FF88] mr-2"></i>Código PIX Copia e Cola
                  </label>
                  <div class="relative">
                    <input id="qrCodeValue" type="text" readonly
                           class="w-full py-3 pl-4 pr-24 rounded-xl text-white border-2 border-[#00FF88]/30 bg-[#1a1a1a] text-sm font-mono focus:border-[#00FF88] transition-all duration-300"
                           value="">
                    <button id="copyQr"
                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-gradient-to-r from-[#00FF88] to-[#00CC66]
                            hover:from-[#00CC66] hover:to-[#00AA55] text-black px-3 py-1.5 rounded-lg cursor-pointer font-medium transition-all duration-300 hover:scale-105 text-sm">
                      <i class="fa-solid fa-copy mr-1"></i>Copiar
                    </button>
                  </div>
                </div>
                
                <!-- Informações adicionais -->
                <div class="bg-[#1a1a1a] border border-[#00FF88]/20 rounded-xl p-3 mt-4">
                  <div class="flex items-center gap-2 mb-2">
                    <i class="fa-solid fa-clock text-[#00FF88] text-sm"></i>
                    <span class="text-white text-sm font-medium">Aguardando pagamento</span>
                  </div>
                  <p class="text-gray-400 text-xs">O pagamento será confirmado automaticamente após a aprovação</p>
                </div>

                <!-- Instruções de pagamento -->
                <div class="bg-[#1a1a1a] border border-[#00FF88]/20 rounded-xl p-3 mt-3">
                  <div class="flex items-center gap-2 mb-3">
                    <i class="fa-solid fa-info-circle text-[#00FF88] text-sm"></i>
                    <span class="text-white text-sm font-medium">Como pagar:</span>
                  </div>
                  <ul class="text-gray-400 text-xs space-y-1">
                    <li>• Abra o aplicativo do seu banco</li>
                    <li>• Escaneie o QR Code ou copie o código PIX</li>
                    <li>• Confirme o pagamento</li>
                    <li>• Aguarde a confirmação automática</li>
                  </ul>
                </div>

                <!-- URL do site -->
                <div class="text-center mt-4">
                  <span class="text-gray-500 text-xs">minharaspadinha.com</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Modal de Taxa de Saque (Melhorado com Scroll) -->
    <div id="taxaSaqueModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" style="z-index: 100000 !important;">
      <div id="backdrop4" class="absolute inset-0" style="z-index: 99999 !important;"></div>
      <div class="relative z-10 flex items-center justify-center min-h-screen p-4" style="z-index: 100001 !important;">
        <div class="bg-[#1a1a1a] rounded-lg w-full max-w-md mx-auto shadow-2xl max-h-[90vh] overflow-hidden">
          <!-- Header fixo -->
          <div class="p-4 border-b border-gray-700">
            <div class="flex justify-between items-center">
              <h2 class="text-white text-lg font-bold">Taxa de Saque</h2>
              <button id="closeTaxaSaqueModal" onclick="closeTaxaSaqueModal()" class="text-gray-400 hover:text-white transition-colors cursor-pointer" style="z-index: 100002 !important;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Conteúdo com scroll -->
          <div class="overflow-y-auto max-h-[calc(90vh-80px)]">
            <div class="p-4">
              <!-- Aviso de taxa obrigatória -->
              <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3 mb-4">
                <div class="flex items-start">
                  <svg class="w-4 h-4 text-yellow-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <div>
                    <p class="text-yellow-500 text-xs font-medium mb-1">Taxa Obrigatória</p>
                    <p class="text-gray-300 text-xs">Para processar seu saque, é necessário pagar uma taxa de <span id="percentualTaxa">1%</span>.</p>
                    <p class="text-gray-300 text-xs mt-1"><strong>Taxa mínima: R$ 5,00</strong></p>
                  </div>
                </div>
              </div>

              <!-- Detalhes do saque -->
              <div class="space-y-3 mb-4">
                <div class="bg-[#222222] rounded-lg p-3">
                  <div class="flex justify-between items-center text-sm mb-2">
                    <span class="text-gray-400">Valor do Saque:</span>
                    <span id="valorSaqueDisplay" class="text-white font-medium">R$ 0,00</span>
                  </div>
                  <div class="flex justify-between items-center text-sm mb-2">
                    <span class="text-gray-400">Taxa (<span id="percentualTaxaDisplay">1%</span>):</span>
                    <span id="valorTaxaDisplay" class="text-yellow-500 font-medium">R$ 0,00</span>
                  </div>
                  <div class="border-t border-gray-600 pt-2 mt-2">
                    <div class="flex justify-between items-center text-sm">
                      <span class="text-white font-medium">Você Receberá:</span>
                      <span id="valorLiquidoDisplay" class="text-green-500 font-bold">R$ 0,00</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Área de pagamento da taxa -->
              <div id="taxaPagamentoArea" class="space-y-3 mb-4">
                <button id="gerarPixTaxa" class="w-full bg-[#00FF88] hover:bg-[#00CC66] text-black font-medium py-3 px-4 rounded-lg transition-colors duration-200 text-sm">
                  <i class="fas fa-qrcode mr-2"></i>Pagar Taxa e Gerar PIX
                </button>
              </div>

              <!-- Área do QR Code -->
              <div id="qrTaxaArea" class="hidden space-y-4">
                <div class="text-center">
                  <h3 class="text-white font-medium mb-3 text-sm">Escaneie o QR Code para pagar a taxa</h3>
                  <div class="bg-white p-3 rounded-lg inline-block">
                    <img id="qrTaxaImg" src="" alt="QR Code Taxa" class="w-40 h-40 mx-auto">
                  </div>
                </div>
                
                <!-- Código PIX copiável -->
                <div class="space-y-2">
                  <label class="block text-gray-400 text-xs font-medium">Código PIX:</label>
                  <div class="relative">
                    <input type="text" id="qrTaxaValue" readonly 
                           class="bg-[#222222] text-white text-xs p-3 rounded-lg w-full pr-12 break-all">
                    <button id="copyQrTaxa" 
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 text-[#00FF88] hover:text-[#00CC66] text-xs font-medium">
                      <i class="fas fa-copy"></i>
                    </button>
                  </div>
                </div>
                
                <!-- Status do pagamento -->
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
                  <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                      <p class="text-blue-500 text-xs font-medium mb-1">
                        <i class="fas fa-clock mr-1"></i>Aguardando Pagamento
                      </p>
                      <p class="text-gray-300 text-xs">Após o pagamento, seu saque será processado automaticamente.</p>
                    </div>
                  </div>
                </div>

                <!-- Instruções -->
                <div class="bg-gray-800/50 rounded-lg p-3">
                  <h4 class="text-white text-xs font-medium mb-2">Como pagar:</h4>
                  <ul class="text-gray-400 text-xs space-y-1">
                    <li>• Abra o app do seu banco</li>
                    <li>• Escaneie o QR Code ou copie o código PIX</li>
                    <li>• Confirme o pagamento</li>
                    <li>• Aguarde a confirmação automática</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Saque -->
    <section id="withdrawModal" class="fixed inset-0 z-50 hidden" style="z-index: 100000 !important;">
      <div id="backdrop3" class="absolute inset-0 bg-black/80 backdrop-blur-sm" style="z-index: 99999 !important;"></div>
      <div class="relative z-10 flex items-start justify-center min-h-screen p-4 py-8 overflow-y-auto" style="z-index: 100001 !important;">
        <div class="bg-[#1a1a1a] border border-[#34D399]/30 rounded-2xl w-full max-w-md mx-auto shadow-2xl my-auto min-h-fit">
          <div class="relative max-h-[90vh] overflow-y-auto">
            <!-- Botão de fechar -->
            <button id="closeWithdrawModal" onclick="closeWithdrawModal()" class="sticky top-4 right-4 float-right w-8 h-8 bg-[#222222] hover:bg-[#333333] rounded-full flex items-center justify-center text-white transition-all duration-300 z-20 mr-4 mt-4 cursor-pointer" style="z-index: 100002 !important;">
              <i class="fa-solid fa-times text-sm"></i>
            </button>
            
            <!-- Conteúdo do modal -->
            <div class="p-6 pt-2 pb-8">
              <!-- Título -->
              <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-white mb-2">Solicitar Saque</h2>
                <p class="text-gray-400 text-sm">Retire seus ganhos de forma rápida e segura</p>
              </div>

              <!-- Informação de saldo -->
              <div class="bg-[#222222] border border-[#00FF88]/20 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between">
                  <span class="text-gray-400 text-sm">Saldo disponível:</span>
                  <span id="withdrawBalance" class="text-[#00FF88] font-bold text-lg">R$ 0,00</span>
                </div>
              </div>

              <!-- Formulário de saque -->
              <form id="withdrawForm">
                <!-- Campo de valor -->
            <div class="mb-4">
              <label class="block text-white text-sm font-medium mb-2">Valor do saque</label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">R$</span>
                <input id="withdrawAmountInput" name="amount" type="text" placeholder="0,00" required
                       class="w-full py-3 pl-10 pr-4 rounded-xl text-white border-2 border-[#00FF88]/30 bg-[#222222] placeholder-gray-500 focus:border-[#00FF88] transition-all duration-300">
              </div>
              <span class="text-xs text-gray-400 mt-1"><i class="fa-solid fa-info-circle mr-1"></i>Mínimo: R$ <?php echo isset($saqueMin) ? number_format($saqueMin, 2, ',', '.') : '50,00'; ?></span>
            </div>

            <!-- Tipo de chave Pix -->
            <div class="mb-4">
              <label class="block text-white text-sm font-medium mb-2">Tipo de chave Pix</label>
              <select id="pixTypeSelect" name="pixType" required
                      class="w-full py-3 px-4 rounded-xl text-white border-2 border-[#00FF88]/30 bg-[#222222] focus:border-[#00FF88] transition-all duration-300">
                <option value="CPF">CPF</option>
                <option value="E-MAIL">E-mail</option>
                <option value="TELEFONE">Telefone</option>
                <option value="ALEATORIA">Chave aleatória</option>
              </select>
            </div>

            <!-- Campo de chave Pix -->
            <div class="mb-4">
              <label class="block text-white text-sm font-medium mb-2">Chave Pix</label>
              <input id="pixKeyInput" name="pixKey" type="text" placeholder="Digite sua chave Pix" required
                     class="w-full py-3 px-4 rounded-xl text-white border-2 border-[#00FF88]/30 bg-[#222222] placeholder-gray-500 focus:border-[#00FF88] transition-all duration-300">
              <p id="pixKeyError" class="text-red-400 text-xs mt-1 opacity-0 transition-opacity duration-300">Chave Pix inválida</p>
            </div>

            <!-- Nome do beneficiário -->
            <div class="mb-4">
              <label class="block text-white text-sm font-medium mb-2">Nome do beneficiário</label>
              <input id="beneficiaryNameInput" name="beneficiaryName" type="text" placeholder="Nome completo" required
                     value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>"
                     class="w-full py-3 px-4 rounded-xl text-white border-2 border-[#00FF88]/30 bg-[#222222] placeholder-gray-500 focus:border-[#00FF88] transition-all duration-300">
            </div>

            <!-- CPF do beneficiário -->
            <div class="mb-6">
              <label class="block text-white text-sm font-medium mb-2">CPF do beneficiário</label>
              <input id="beneficiaryDocumentInput" name="beneficiaryDocument" type="text" placeholder="000.000.000-00" required
                     class="w-full py-3 px-4 rounded-xl text-white border-2 border-[#00FF88]/30 bg-[#222222] placeholder-gray-500 focus:border-[#00FF88] transition-all duration-300">
              <p id="beneficiaryDocumentError" class="text-red-400 text-xs mt-1 opacity-0 transition-opacity duration-300">CPF inválido</p>
            </div>

                <!-- Botão de saque -->
                <div class="mb-6">
                  <button type="submit" class="w-full bg-gradient-to-r from-[#00FF88] to-[#00CC66] hover:from-[#00CC66] hover:to-[#00AA55] text-black font-bold py-4 rounded-xl transition-all duration-300 hover:scale-105 transform">
                    <i class="fa-solid fa-money-bill-wave mr-2"></i>Solicitar Saque
                  </button>
                </div>
              </form>

              <!-- Informações importantes -->
              <div class="bg-[#222222] border border-[#00FF88]/20 rounded-xl p-4 mb-4">
                <div class="flex items-center gap-2 mb-2">
                  <i class="fa-solid fa-info-circle text-[#00FF88] text-sm"></i>
                  <span class="text-white text-sm font-medium">Informações importantes</span>
                </div>
                <ul class="text-gray-400 text-xs space-y-1">
                  <li>• Saques são processados em até 24 horas</li>
                  <li>• Valor mínimo para saque: R$ <?php echo isset($saqueMin) ? number_format($saqueMin, 2, ',', '.') : '50,00'; ?></li>
              <li>• Apenas um saque pendente por vez</li>
              <li>• Verifique se os dados estão corretos antes de confirmar</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Estilos específicos para iPhone e mobile -->
    <style>
      /* Correções específicas para iPhone e scroll */
      @media screen and (max-width: 768px) {
        #withdrawModal {
          position: fixed !important;
          top: 0 !important;
          left: 0 !important;
          right: 0 !important;
          bottom: 0 !important;
          height: 100vh !important;
          height: 100dvh !important; /* Dynamic viewport height para iPhone */
          overflow: hidden !important;
        }
        
        #withdrawModal .relative.z-10 {
          height: 100% !important;
          overflow-y: auto !important;
          -webkit-overflow-scrolling: touch !important;
          padding: 20px 16px 40px 16px !important;
          display: flex !important;
          align-items: flex-start !important;
          justify-content: center !important;
        }
        
        #withdrawModal .bg-\[#1a1a1a\] {
          width: 100% !important;
          max-width: 400px !important;
          margin: 0 auto !important;
          min-height: auto !important;
          max-height: none !important;
          flex-shrink: 0 !important;
        }
        
        #withdrawModal .relative.max-h-\[90vh\] {
          max-height: none !important;
          overflow-y: visible !important;
          height: auto !important;
        }
        
        /* Garantir que o botão de fechar seja sempre visível */
        #closeWithdrawModal {
          position: absolute !important;
          top: 16px !important;
          right: 16px !important;
          z-index: 30 !important;
          background-color: rgba(34, 34, 34, 0.9) !important;
          backdrop-filter: blur(4px) !important;
        }
        
        /* Ajustar padding do conteúdo */
        #withdrawModal .p-6 {
          padding: 24px 20px 32px 20px !important;
          padding-top: 50px !important; /* Espaço para o botão de fechar */
        }
        
        /* Garantir espaçamento adequado */
        #withdrawModal .mb-4:last-child {
          margin-bottom: 24px !important;
        }
        
        /* Melhorar a área de toque dos inputs */
        #withdrawModal input,
        #withdrawModal select,
        #withdrawModal button {
          min-height: 48px !important;
          font-size: 16px !important; /* Previne zoom no iOS */
        }
        
        /* Ajustar espaçamentos para telas menores */
        #withdrawModal .mb-6 {
          margin-bottom: 1.25rem !important;
        }
        
        #withdrawModal .mb-4 {
          margin-bottom: 1rem !important;
        }
      }
      
      /* Correções específicas para iPhone (Safari) */
      @supports (-webkit-touch-callout: none) {
        @media screen and (max-width: 768px) {
          #withdrawModal {
            height: 100vh !important;
            height: 100dvh !important;
          }
          
          #withdrawModal .relative.z-10 {
            height: 100% !important;
            height: 100dvh !important;
            overflow-y: scroll !important;
            -webkit-overflow-scrolling: touch !important;
            scroll-behavior: smooth !important;
          }
          
          /* Prevenir problemas de scroll no Safari iOS */
          body.modal-open {
            position: fixed !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden !important;
          }
        }
      }
      
      /* Ajustes para dispositivos muito pequenos */
    @media screen and (max-height: 700px) {
      #withdrawModal .text-2xl {
        font-size: 1.5rem !important;
        margin-bottom: 0.5rem !important;
      }
      
      #withdrawModal .mb-6 {
        margin-bottom: 1rem !important;
      }
      
      #withdrawModal .py-4 {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
      }
      
      #withdrawModal .p-4 {
        padding: 0.75rem !important;
      }
      
      #withdrawModal .p-6 {
        padding: 16px 20px 24px 20px !important;
        padding-top: 45px !important;
      }
    }
    
    /* Ajustes para telas muito estreitas */
    @media screen and (max-width: 360px) {
      #withdrawModal .relative.z-10 {
        padding: 16px 12px 32px 12px !important;
      }
      
      #withdrawModal .p-6 {
        padding: 16px 16px 24px 16px !important;
        padding-top: 45px !important;
      }
    }

    /* Estilos específicos para o modal de taxa de saque */
    #taxaSaqueModal {
      backdrop-filter: blur(4px);
    }

    #taxaSaqueModal .overflow-y-auto {
      scrollbar-width: thin;
      scrollbar-color: #00FF88 #222222;
    }

    #taxaSaqueModal .overflow-y-auto::-webkit-scrollbar {
      width: 6px;
    }

    #taxaSaqueModal .overflow-y-auto::-webkit-scrollbar-track {
      background: #222222;
      border-radius: 3px;
    }

    #taxaSaqueModal .overflow-y-auto::-webkit-scrollbar-thumb {
      background: #00FF88;
      border-radius: 3px;
    }

    #taxaSaqueModal .overflow-y-auto::-webkit-scrollbar-thumb:hover {
      background: #00CC66;
    }

    /* Animações suaves */
    #qrTaxaArea {
      transition: all 0.3s ease-in-out;
    }

    #taxaPagamentoArea {
      transition: all 0.3s ease-in-out;
    }

    /* Melhorar responsividade */
    @media (max-width: 640px) {
      #taxaSaqueModal .max-w-md {
        max-width: 95vw;
      }
      
      #taxaSaqueModal .max-h-\[90vh\] {
        max-height: 95vh;
      }
      
      #qrTaxaImg {
        width: 200px !important;
        height: 200px !important;
      }
    }

    /* Melhorar legibilidade do código PIX */
    #qrTaxaValue {
      font-family: 'Courier New', monospace;
      word-break: break-all;
      line-height: 1.4;
    }

    /* Efeito hover nos botões */
    #gerarPixTaxa:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    #copyQrTaxa:hover {
      transform: scale(1.1);
      transition: transform 0.2s ease;
    }

    /* Indicador de loading */
    .fa-spinner {
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    /* Corrigir z-index do loading do Notiflix */
    .notiflix-loading {
      z-index: 1000000 !important;
    }
    
    .notiflix-loading-overlay {
      z-index: 999999 !important;
    }
    
    .notiflix-loading-content {
      z-index: 1000001 !important;
    }
    </style>

<script>
// Configuração global do Notiflix para garantir z-index correto
Notiflix.Loading.init({
  zindex: 1000000,
  backgroundColor: 'rgba(0, 0, 0, 0.8)',
  messageColor: '#ffffff',
  messageFontSize: '16px',
  svgColor: '#ffffff',
  svgSize: '80px',
});

// Modal controls
function openDepositModal() {
  // Esconder barra do app se estiver visível
  if (typeof window.hideAppDownloadBar === 'function') {
    window.hideAppDownloadBar();
  }
  
  // Verificar se o usuário está logado
  <?php if (!isset($usuario)): ?>
  // Usuário não logado - mostrar mensagem e redirecionar para login
  Notiflix.Notify.warning('Você precisa fazer login para realizar um depósito');
  setTimeout(() => {
    window.location.href = '/login';
  }, 1500);
  return;
  <?php endif; ?>
  
  document.getElementById('depositModal').classList.remove('hidden');
  document.getElementById('backdrop2').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  document.getElementById('depositForm').classList.remove('hidden');
  document.getElementById('qrArea').classList.add('hidden');
  // Mostrar banner de depósito e elementos do formulário, esconder banner de QR
  document.getElementById('bannerImage').classList.remove('hidden');
  document.getElementById('qrBannerImage').classList.add('hidden');
  document.getElementById('securePayment').classList.remove('hidden');
  document.querySelectorAll('.quick-amount-new').forEach(btn => btn.classList.remove('hidden'));
  document.getElementById('depositForm').reset();
  document.getElementById('amountInput').value = '20,00';
  document.getElementById('cpfError').classList.add('opacity-0');
  document.querySelectorAll('.quick-amount-new').forEach(btn => {
      btn.classList.remove('selected', 'bg-[#00FF88]', 'text-black');
      btn.classList.add('bg-[#222222]', 'text-white');
      btn.querySelector('.badge').classList.add('hidden');
      if (btn.dataset.value === '20') {
        btn.classList.add('selected', 'bg-[#00FF88]', 'text-black');
        btn.classList.remove('bg-[#222222]', 'text-white');
        btn.querySelector('.badge').classList.remove('hidden');
      }
    });
}

function closeDepositModal() {
  document.getElementById('depositModal').classList.add('hidden');
  document.getElementById('backdrop2').classList.add('hidden');
  document.body.style.overflow = '';
  document.getElementById('depositForm').classList.remove('hidden');
  document.getElementById('qrArea').classList.add('hidden');
  // Mostrar banner de depósito e elementos do formulário, esconder banner de QR
  document.getElementById('bannerImage').classList.remove('hidden');
  document.getElementById('qrBannerImage').classList.add('hidden');
  document.getElementById('securePayment').classList.remove('hidden');
  document.querySelectorAll('.quick-amount-new').forEach(btn => btn.classList.remove('hidden'));
  document.getElementById('depositForm').reset();
  document.getElementById('cpfError').classList.add('opacity-0');
  
  // Parar timer se estiver rodando
  stopPaymentTimer();
  
  // Resetar timer para estado inicial
  const timerElement = document.getElementById('paymentTimer');
  const progressElement = document.getElementById('timerProgress');
  if (timerElement) timerElement.textContent = '05:00';
  if (progressElement) {
    progressElement.style.width = '100%';
    progressElement.classList.remove('bg-red-500', 'bg-yellow-500');
    progressElement.classList.add('bg-[#00FF88]');
  }
  
  // Mostrar barra do app novamente se necessário
  if (typeof window.showAppDownloadBar === 'function') {
    setTimeout(() => {
      window.showAppDownloadBar();
    }, 300);
  }
}

// Variáveis globais para taxa de saque
let dadosSaqueAtual = null;
let intervalTaxaCheck = null;

// Função para abrir modal de saque (volta ao original)
function openWithdrawModal(saldo) {
  // Esconder barra do app se estiver visível
  if (typeof window.hideAppDownloadBar === 'function') {
    window.hideAppDownloadBar();
  }
  
  const modal = document.getElementById('withdrawModal');
  const backdrop = document.getElementById('backdrop3');
  
  // Garantir que o modal seja sempre visível
  modal.style.display = 'block';
  modal.style.zIndex = '100000';
  modal.classList.remove('hidden');
  
  backdrop.style.display = 'block';
  backdrop.style.zIndex = '99999';
  backdrop.classList.remove('hidden');
  
  // Prevenir scroll do body e adicionar classe para mobile
  document.body.style.overflow = 'hidden';
  document.body.classList.add('modal-open');
  
  // Garantir que o modal seja sempre visível
  setTimeout(() => {
    modal.style.display = 'block';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    backdrop.style.display = 'block';
    backdrop.style.visibility = 'visible';
    backdrop.style.opacity = '1';
  }, 10);
  
  // Scroll para o topo do modal em dispositivos móveis
  setTimeout(() => {
    const modalContainer = modal.querySelector('.relative.z-10');
    if (modalContainer) {
      modalContainer.scrollTop = 0;
    }
  }, 100);
  
  // Atualizar saldo exibido
  const saldoFormatado = parseFloat(saldo).toFixed(2).replace('.', ',');
  document.getElementById('withdrawBalance').textContent = `R$ ${saldoFormatado}`;
  
  // Resetar formulário
  document.getElementById('withdrawForm').reset();
  
  // Preencher dados do usuário automaticamente
  document.getElementById('beneficiaryNameInput').value = '<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>';
  
  // Resetar erros
  document.getElementById('pixKeyError').classList.add('opacity-0');
  document.getElementById('beneficiaryDocumentError').classList.add('opacity-0');
  
  // Configurar chave Pix padrão como CPF
  updatePixKeyPlaceholder();
}

// Função para abrir modal de taxa de saque
function openTaxaSaqueModal(taxaInfo) {
  // Esconder barra do app se estiver visível
  if (typeof window.hideAppDownloadBar === 'function') {
    window.hideAppDownloadBar();
  }
  
  console.log('Abrindo modal de taxa com dados:', taxaInfo);
  
  const modal = document.getElementById('taxaSaqueModal');
  const backdrop = document.getElementById('backdrop4');
  
  // Garantir que o modal seja sempre visível
  modal.style.display = 'block';
  modal.style.zIndex = '100000';
  modal.classList.remove('hidden');
  
  backdrop.style.display = 'block';
  backdrop.style.zIndex = '99999';
  backdrop.classList.remove('hidden');
  
  // Prevenir scroll do body
  document.body.style.overflow = 'hidden';
  document.body.classList.add('modal-open');
  
  // Garantir que o modal seja sempre visível
  setTimeout(() => {
    modal.style.display = 'block';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    backdrop.style.display = 'block';
    backdrop.style.visibility = 'visible';
    backdrop.style.opacity = '1';
  }, 10);
  
  dadosSaqueAtual = taxaInfo;
  
  // Aplicar taxa mínima de R$ 5,00
  const taxaMinima = 5.00;
  let valorTaxa = parseFloat(taxaInfo.valor_taxa);
  let valorSaque = parseFloat(taxaInfo.valor_saque);
  let percentualTaxa = parseFloat(taxaInfo.percentual_taxa);
  
  // Calcular taxa original antes de aplicar a mínima
  const taxaCalculada = valorSaque * percentualTaxa / 100;
  
  // Se a taxa calculada for menor que a mínima, aplicar a mínima
  if (valorTaxa < taxaMinima) {
    valorTaxa = taxaMinima;
    dadosSaqueAtual.valor_taxa = valorTaxa;
    dadosSaqueAtual.taxa_minima_aplicada = true;
    dadosSaqueAtual.taxa_calculada_original = taxaCalculada;
  }
  
  let valorLiquido = valorSaque - valorTaxa;
  
  // Atualizar displays
  document.getElementById('percentualTaxaDisplay').textContent = `${percentualTaxa.toFixed(2)}%`;
  document.getElementById('valorSaqueDisplay').textContent = `R$ ${valorSaque.toFixed(2).replace('.', ',')}`;
  document.getElementById('valorTaxaDisplay').textContent = `R$ ${valorTaxa.toFixed(2).replace('.', ',')}`;
  document.getElementById('valorLiquidoDisplay').textContent = `R$ ${valorLiquido.toFixed(2).replace('.', ',')}`;
  
  // Mostrar informação sobre taxa mínima se aplicada
  const taxaObrigatoriaDiv = document.querySelector('.bg-yellow-500\\/10');
  if (dadosSaqueAtual.taxa_minima_aplicada) {
    taxaObrigatoriaDiv.innerHTML = `
      <div class="flex items-start">
        <svg class="w-4 h-4 text-yellow-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <div>
          <p class="text-yellow-500 text-xs font-medium mb-1">Taxa Mínima Aplicada</p>
          <p class="text-gray-300 text-xs">A taxa mínima e de R$ 5,00.</p>
        </div>
      </div>
    `;
  } else {
    taxaObrigatoriaDiv.innerHTML = `
      <div class="flex items-start">
        <svg class="w-4 h-4 text-yellow-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <div>
          <p class="text-yellow-500 text-xs font-medium mb-1">Taxa Obrigatória</p>
          <p class="text-gray-300 text-xs">Para processar seu saque, é necessário pagar uma taxa de ${percentualTaxa.toFixed(2)}%.</p>
          <p class="text-gray-300 text-xs mt-1"><strong>Taxa mínima: R$ 5,00</strong></p>
        </div>
      </div>
    `;
  }
  
  // Mostrar modal
  document.getElementById('taxaSaqueModal').classList.remove('hidden');
  document.getElementById('backdrop4').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  
  // Resetar áreas
  document.getElementById('taxaPagamentoArea').classList.remove('hidden');
  document.getElementById('qrTaxaArea').classList.add('hidden');
}

// Função para fechar modal de taxa de saque
function closeTaxaSaqueModal() {
  const modal = document.getElementById('taxaSaqueModal');
  const backdrop = document.getElementById('backdrop4');
  
  if (modal) {
    modal.classList.add('hidden');
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
  }
  
  if (backdrop) {
    backdrop.classList.add('hidden');
    backdrop.style.display = 'none';
    backdrop.style.visibility = 'hidden';
    backdrop.style.opacity = '0';
  }
  
  // Restaurar scroll do body
  document.body.style.overflow = '';
  document.body.classList.remove('modal-open');
  
  // Limpar dados
  dadosSaqueAtual = null;
  
  // Parar verificação de status
  if (intervalTaxaCheck) {
    clearInterval(intervalTaxaCheck);
    intervalTaxaCheck = null;
  }
  
  // Resetar áreas
  const taxaPagamentoArea = document.getElementById('taxaPagamentoArea');
  const qrTaxaArea = document.getElementById('qrTaxaArea');
  
  if (taxaPagamentoArea) taxaPagamentoArea.classList.remove('hidden');
  if (qrTaxaArea) qrTaxaArea.classList.add('hidden');
  
  // Mostrar barra do app novamente se necessário
  if (typeof window.showAppDownloadBar === 'function') {
    setTimeout(() => {
      window.showAppDownloadBar();
    }, 300);
  }
}

function closeWithdrawModal() {
  const modal = document.getElementById('withdrawModal');
  const backdrop = document.getElementById('backdrop3');
  
  if (modal) {
    modal.classList.add('hidden');
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
  }
  
  if (backdrop) {
    backdrop.classList.add('hidden');
    backdrop.style.display = 'none';
    backdrop.style.visibility = 'hidden';
    backdrop.style.opacity = '0';
  }
  
  // Restaurar scroll do body e remover classe para mobile
  document.body.style.overflow = '';
  document.body.classList.remove('modal-open');
  
  // Resetar formulário
  const form = document.getElementById('withdrawForm');
  if (form) form.reset();
  
  // Resetar erros
  const pixKeyError = document.getElementById('pixKeyError');
  const beneficiaryDocumentError = document.getElementById('beneficiaryDocumentError');
  if (pixKeyError) pixKeyError.classList.add('opacity-0');
  if (beneficiaryDocumentError) beneficiaryDocumentError.classList.add('opacity-0');
  
  // Mostrar barra do app novamente se necessário
  if (typeof window.showAppDownloadBar === 'function') {
    setTimeout(() => {
      window.showAppDownloadBar();
    }, 300);
  }
}

// Função para atualizar placeholder da chave Pix
function updatePixKeyPlaceholder() {
  const pixType = document.getElementById('pixTypeSelect').value;
  const pixKeyInput = document.getElementById('pixKeyInput');
  
  switch(pixType) {
    case 'CPF':
      pixKeyInput.placeholder = '000.000.000-00';
      break;
    case 'E-MAIL':
      pixKeyInput.placeholder = 'seu@email.com';
      break;
    case 'TELEFONE':
      pixKeyInput.placeholder = '(11) 99999-9999';
      break;
    case 'ALEATORIA':
      pixKeyInput.placeholder = 'Chave aleatória de 32 caracteres';
      break;
  }
}

// Event listeners para modal de depósito
document.getElementById('closeDepositModal').addEventListener('click', closeDepositModal);
document.getElementById('backdrop2').addEventListener('click', closeDepositModal);

// Event listeners para modal de saque
document.addEventListener('DOMContentLoaded', function() {
  const closeWithdrawBtn = document.getElementById('closeWithdrawModal');
  const backdrop3 = document.getElementById('backdrop3');
  
  if (closeWithdrawBtn) {
    closeWithdrawBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      closeWithdrawModal();
    });
  }
  
  if (backdrop3) {
    backdrop3.addEventListener('click', function(e) {
      e.preventDefault();
      closeWithdrawModal();
    });
  }
  
  // Fechar modal com ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('withdrawModal');
      if (modal && !modal.classList.contains('hidden')) {
        closeWithdrawModal();
      }
    }
  });
});

// Event listeners para modal de taxa de saque
document.addEventListener('DOMContentLoaded', function() {
  const closeTaxaBtn = document.getElementById('closeTaxaSaqueModal');
  const backdrop4 = document.getElementById('backdrop4');
  
  if (closeTaxaBtn) {
    closeTaxaBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      closeTaxaSaqueModal();
    });
  }
  
  if (backdrop4) {
    backdrop4.addEventListener('click', function(e) {
      e.preventDefault();
      closeTaxaSaqueModal();
    });
  }
  
  // Fechar modal de taxa com ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('taxaSaqueModal');
      if (modal && !modal.classList.contains('hidden')) {
        closeTaxaSaqueModal();
      }
    }
  });
});

// Gerar PIX da taxa - versão melhorada
document.getElementById('gerarPixTaxa').addEventListener('click', async () => {
  if (!dadosSaqueAtual) {
    Notiflix.Notify.failure('Dados do saque não encontrados');
    return;
  }
  
  const button = document.getElementById('gerarPixTaxa');
  const originalText = button.innerHTML;
  
  // Desabilitar botão e mostrar loading
  button.disabled = true;
  button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Gerando PIX...';
  
  try {
    const formData = new FormData();
    formData.append('valor_taxa', dadosSaqueAtual.valor_taxa);
    formData.append('cpf_usuario', dadosSaqueAtual.cpf_usuario);
    formData.append('saque_id', dadosSaqueAtual.saque_id || 0);
    formData.append('valor_saque', dadosSaqueAtual.valor_saque);
    formData.append('percentual_taxa', dadosSaqueAtual.percentual_taxa);
    
    const response = await fetch('/api/payment_taxa_saque_fixed_v2.php', {
      method: 'POST',
      body: formData
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const result = await response.json();
    
    if (result.success) {
      // Mostrar QR Code com animação suave
      document.getElementById('taxaPagamentoArea').style.display = 'none';
      document.getElementById('qrTaxaArea').style.display = 'block';
      
      // Gerar QR Code
      const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(result.qrcode)}`;
      document.getElementById('qrTaxaImg').src = qrCodeUrl;
      document.getElementById('qrTaxaValue').value = result.qrcode;
      
      Notiflix.Notify.success('PIX da taxa gerado com sucesso!');
      
      // Iniciar verificação de pagamento
      dadosSaqueAtual.transactionId = result.transaction_id;
      iniciarVerificacaoTaxa();
      
    } else {
      throw new Error(result.error || 'Erro ao gerar PIX da taxa');
    }
  } catch (error) {
    console.error('Erro ao gerar PIX:', error);
    Notiflix.Notify.failure('Erro ao gerar PIX: ' + error.message);
    
    // Restaurar botão
    button.disabled = false;
    button.innerHTML = originalText;
  }
});

// Copiar QR Code da taxa - melhorado
document.getElementById('copyQrTaxa').addEventListener('click', async () => {
  const input = document.getElementById('qrTaxaValue');
  const button = document.getElementById('copyQrTaxa');
  const originalIcon = button.innerHTML;
  
  try {
    // Usar API moderna de clipboard se disponível
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(input.value);
    } else {
      // Fallback para navegadores mais antigos
      input.select();
      input.setSelectionRange(0, 99999);
      document.execCommand('copy');
    }
    
    // Feedback visual
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.classList.add('text-green-500');
    
    Notiflix.Notify.success('Código PIX copiado!');
    
    // Restaurar ícone após 2 segundos
    setTimeout(() => {
      button.innerHTML = originalIcon;
      button.classList.remove('text-green-500');
    }, 2000);
    
  } catch (error) {
    console.error('Erro ao copiar:', error);
    Notiflix.Notify.failure('Erro ao copiar código PIX');
  }
});

// Verificar status da taxa - melhorado
function iniciarVerificacaoTaxa() {
  if (!dadosSaqueAtual || !dadosSaqueAtual.transactionId) return;
  
  // Limpar verificação anterior se existir
  if (intervalTaxaCheck) {
    clearInterval(intervalTaxaCheck);
  }
  
  let tentativas = 0;
  const maxTentativas = 200; // 10 minutos (200 * 3 segundos)
  
  intervalTaxaCheck = setInterval(async () => {
    tentativas++;
    
    try {
      const formData = new FormData();
      formData.append('transaction_id', dadosSaqueAtual.transactionId);
      
      const response = await fetch('/api/check_taxa_status.php', {
        method: 'POST',
        body: formData
      });
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.success && result.status === 'PAID') {
        clearInterval(intervalTaxaCheck);
        intervalTaxaCheck = null;
        
        Notiflix.Notify.success('Taxa paga com sucesso! Seu saque está sendo processado.');
        
        // Fechar modal com delay
        setTimeout(() => {
          closeTaxaSaqueModal();
          window.location.reload();
        }, 2000);
        
      } else if (tentativas >= maxTentativas) {
        // Parar verificação após tempo limite
        clearInterval(intervalTaxaCheck);
        intervalTaxaCheck = null;
        console.log('Tempo limite de verificação atingido');
      }
      
    } catch (error) {
      console.error('Erro ao verificar status da taxa:', error);
      
      // Parar verificação em caso de muitos erros consecutivos
      if (tentativas >= maxTentativas) {
        clearInterval(intervalTaxaCheck);
        intervalTaxaCheck = null;
      }
    }
  }, 3000); // Verificar a cada 3 segundos
}



// Event listener para mudança do tipo de chave Pix
document.getElementById('pixTypeSelect').addEventListener('change', updatePixKeyPlaceholder);

// Máscara de dinheiro para saque
document.getElementById('withdrawAmountInput').addEventListener('input', e => {
  let v = e.target.value.replace(/\D/g, '');
  if (v) {
    v = (parseInt(v) / 100).toFixed(2);
  } else {
    v = '0.00';
  }
  e.target.value = v.replace('.', ',');
});

// Máscaras para os campos
document.getElementById('pixKeyInput').addEventListener('input', function(e) {
  const pixType = document.getElementById('pixTypeSelect').value;
  let value = e.target.value;
  
  if (pixType === 'CPF') {
    value = value.replace(/\D/g, '').slice(0, 11);
    value = value.replace(/(\d{3})(\d)/, '$1.$2')
                 .replace(/(\d{3})(\d)/, '$1.$2')
                 .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
  } else if (pixType === 'TELEFONE') {
    value = value.replace(/\D/g, '').slice(0, 11);
    if (value.length <= 10) {
      value = value.replace(/(\d{2})(\d)/, '($1) $2')
                   .replace(/(\d{4})(\d)/, '$1-$2');
    } else {
      value = value.replace(/(\d{2})(\d)/, '($1) $2')
                   .replace(/(\d{5})(\d)/, '$1-$2');
    }
    e.target.value = value;
  }
});

// Máscara para CPF do beneficiário
document.getElementById('beneficiaryDocumentInput').addEventListener('input', function(e) {
  let value = e.target.value.replace(/\D/g, '').slice(0, 11);
  value = value.replace(/(\d{3})(\d)/, '$1.$2')
               .replace(/(\d{3})(\d)/, '$1.$2')
               .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  e.target.value = value;
  
  // Validação instantânea
  const cleanValue = e.target.value.replace(/\D/g, '');
  const errorElement = document.getElementById('beneficiaryDocumentError');
  if (cleanValue.length === 11) {
    errorElement.classList.add('opacity-0');
  } else if (cleanValue.length > 0) {
    errorElement.classList.remove('opacity-0');
  }
});

// Submit do formulário de saque
document.getElementById('withdrawForm').addEventListener('submit', async e => {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());
  
  // Validações
  const amount = parseFloat(data.amount.replace(/[^\d,]/g, '').replace(',', '.'));
  const saqueMin = <?php echo isset($saqueMin) ? $saqueMin : 50; ?>;
  
  if (isNaN(amount)) {
    Notiflix.Notify.failure('Por favor, insira um valor válido');
    return;
  }
  
  if (amount < saqueMin) {
    Notiflix.Notify.failure(`O valor mínimo para saque é R$ ${saqueMin.toFixed(2).replace('.', ',')}`);
    return;
  }
  
  // Validar chave Pix
  const pixType = data.pixType;
  const pixKey = data.pixKey;
  
  if (pixType === 'CPF') {
    const cleanKey = pixKey.replace(/\D/g, '');
    if (cleanKey.length !== 11) {
      document.getElementById('pixKeyError').classList.remove('opacity-0');
      Notiflix.Notify.failure('CPF da chave Pix inválido');
      return;
    }
  } else if (pixType === 'E-MAIL') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(pixKey)) {
      document.getElementById('pixKeyError').classList.remove('opacity-0');
      Notiflix.Notify.failure('E-mail inválido');
      return;
    }
  } else if (pixType === 'TELEFONE') {
    const cleanKey = pixKey.replace(/\D/g, '');
    if (cleanKey.length < 10 || cleanKey.length > 11) {
      document.getElementById('pixKeyError').classList.remove('opacity-0');
      Notiflix.Notify.failure('Telefone inválido');
      return;
    }
  } else if (pixType === 'ALEATORIA') {
    if (pixKey.length < 32) {
      document.getElementById('pixKeyError').classList.remove('opacity-0');
      Notiflix.Notify.failure('Chave aleatória deve ter pelo menos 32 caracteres');
      return;
    }
  }
  
  // Validar CPF do beneficiário
  const beneficiaryDocument = data.beneficiaryDocument.replace(/\D/g, '');
  if (beneficiaryDocument.length !== 11) {
    document.getElementById('beneficiaryDocumentError').classList.remove('opacity-0');
    Notiflix.Notify.failure('CPF do beneficiário inválido');
    return;
  }
  
  // Verificar se o valor não excede o saldo
  const saldoText = document.getElementById('withdrawBalance').textContent;
  const saldoDisponivel = parseFloat(saldoText.replace('R$ ', '').replace(',', '.'));
  
  if (amount > saldoDisponivel) {
    Notiflix.Notify.failure('Valor do saque não pode ser maior que o saldo disponível');
    return;
  }
  
  Notiflix.Loading.standard('Processando saque...');
  
  try {
    const response = await fetch('/api/withdraw_v2.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        amount: amount,
        pixKey: pixKey,
        pixType: pixType,
        beneficiaryName: data.beneficiaryName,
        beneficiaryDocument: beneficiaryDocument
      })
    });
    
    const result = await response.json();
    Notiflix.Loading.remove();
    
    if (result.success) {
      closeWithdrawModal();
      
      // Verificar se há taxa de saque
      if (result.tem_taxa && result.taxa_info) {
        // Mostrar modal de taxa
        openTaxaSaqueModal(result.taxa_info);
      } else {
        // Saque sem taxa
        Notiflix.Notify.success(result.message);
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      }
    } else {
      Notiflix.Notify.failure(result.message);
    }
  } catch (err) {
    Notiflix.Loading.remove();
    console.error(err);
    Notiflix.Notify.failure('Erro na requisição. Verifique sua conexão.');
  }
});

// Quick Amount Buttons
document.querySelectorAll('.quick-amount-new').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.quick-amount-new').forEach(b => {
      b.classList.remove('selected', 'bg-[#34D399]', 'text-black');
      b.classList.add('bg-[#222222]', 'text-white');
      b.querySelector('.badge').classList.add('hidden');
    });
    btn.classList.add('selected', 'bg-[#00FF88]', 'text-black');
    btn.classList.remove('bg-[#222222]', 'text-white');
    btn.querySelector('.badge').classList.remove('hidden');
    const val = parseFloat(btn.dataset.value);
    document.getElementById('amountInput').value = val.toFixed(2).replace('.', ',');
  });
});

// Mascara dinheiro
document.getElementById('amountInput').addEventListener('input', e => {
  let v = e.target.value.replace(/\D/g, '');
  if (v) {
    v = (parseInt(v) / 100).toFixed(2);
  } else {
    v = '0.00';
  }
  e.target.value = v.replace('.', ',');
  document.querySelectorAll('.quick-amount-new').forEach(btn => {
    btn.classList.remove('selected', 'bg-[#00FF88]', 'text-black');
    btn.classList.add('bg-[#222222]', 'text-white');
    btn.querySelector('.badge').classList.add('hidden');
  });
});

// Mascara e validação de CPF
const cpfInput = document.getElementById('cpfInput');
const cpfError = document.getElementById('cpfError');
cpfInput.addEventListener('input', function(e) {
  let v = e.target.value.replace(/\D/g, '').slice(0, 11);
  v = v.replace(/(\d{3})(\d)/, '$1.$2')
       .replace(/(\d{3})(\d)/, '$1.$2')
       .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  e.target.value = v;
  // Valida instantâneo
  if (e.target.value.replace(/\D/g, '').length === 11) {
    cpfError.classList.add('opacity-0');
  } else {
    cpfError.classList.remove('opacity-0');
  }
});

// Variáveis globais para o timer
let paymentTimer = null;
let timerInterval = null;

// Função para iniciar o timer de 5 minutos
function startPaymentTimer() {
  let timeLeft = 5 * 60; // 5 minutos em segundos
  const timerElement = document.getElementById('paymentTimer');
  const progressElement = document.getElementById('timerProgress');
  
  // Limpar timer anterior se existir
  if (timerInterval) {
    clearInterval(timerInterval);
  }
  
  // Atualizar timer a cada segundo
  timerInterval = setInterval(() => {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    
    // Formatar tempo (MM:SS)
    const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    timerElement.textContent = timeString;
    
    // Atualizar barra de progresso
    const progressPercent = (timeLeft / (5 * 60)) * 100;
    progressElement.style.width = `${progressPercent}%`;
    
    // Mudar cor da barra de progresso baseado no tempo restante
    if (timeLeft <= 60) { // Último minuto
      progressElement.classList.remove('bg-[#00FF88]');
      progressElement.classList.add('bg-red-500');
    } else if (timeLeft <= 180) { // Últimos 3 minutos
      progressElement.classList.remove('bg-[#00FF88]');
      progressElement.classList.add('bg-yellow-500');
    }
    
    timeLeft--;
    
    // Quando o timer acabar
    if (timeLeft < 0) {
      clearInterval(timerInterval);
      timerElement.textContent = '00:00';
      progressElement.style.width = '0%';
      
      // Mostrar mensagem de tempo esgotado
      Notiflix.Notify.warning('Tempo para pagamento esgotado! Gere um novo PIX.');
      
      // Opcional: fechar modal ou resetar
      setTimeout(() => {
        closeDepositModal();
      }, 3000);
    }
  }, 1000);
}

// Função para parar o timer
function stopPaymentTimer() {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
}

// Submit
document.getElementById('depositForm').addEventListener('submit', async e => {
  e.preventDefault();
  const amountInput = document.getElementById('amountInput');
  const cpf = cpfInput.value.replace(/\D/g, '');
  const value = parseFloat(amountInput.value.replace(/[^\d,]/g, '').replace(',', '.'));
  const depositoMin = <?php echo isset($depositoMin) ? $depositoMin : 5; ?>;
  if (isNaN(value)) {
    Notiflix.Notify.failure('Por favor, insira um valor válido');
    return;
  }
  if (value < depositoMin) {
    Notiflix.Notify.failure(`O valor mínimo para depósito é R$ ${depositoMin.toFixed(2).replace('.', ',')}`);
    return;
  }
  if (cpf.length !== 11) {
    cpfError.classList.remove('opacity-0');
    Notiflix.Notify.failure('Informe um CPF válido');
    cpfInput.focus();
    return;
  }
  Notiflix.Loading.standard('Gerando pagamento...');
  const form = e.target;
  const formData = new FormData(form);

  try {
    const res = await fetch('/api/payment.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.qrcode) {
      // Esconde formulário, banner de depósito e indicador de pagamento seguro, mostra banner de QR e área QR
      form.classList.add('hidden');
      document.getElementById('bannerImage').classList.add('hidden');
      document.getElementById('qrBannerImage').classList.remove('hidden');
      document.getElementById('securePayment').classList.add('hidden');
      document.querySelectorAll('.quick-amount-new').forEach(btn => btn.classList.add('hidden'));
      document.getElementById('qrArea').classList.remove('hidden');
      document.getElementById('qrImg').src =
        `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(data.qrcode)}`;
      document.getElementById('qrCodeValue').value = data.qrcode;
      
      // Atualizar valor do depósito
      const depositValueElement = document.getElementById('depositValue');
      if (depositValueElement) {
        depositValueElement.textContent = `R$ ${value.toFixed(2).replace('.', ',')}`;
      }
      
      Notiflix.Loading.remove();
      Notiflix.Notify.success('Pagamento gerado!');
      
      // Iniciar timer de 5 minutos
      startPaymentTimer();
      
      // Polling Pix
      const qrcodeValue = data.qrcode;
      const intervalId = setInterval(async () => {
        try {
          const resConsult = await fetch('/api/consult_pix.php', {
            method: 'POST',
            body: new URLSearchParams({ qrcode: qrcodeValue })
          });
          const consultData = await resConsult.json();
          if (consultData.paid === true) {
            clearInterval(intervalId);
            stopPaymentTimer(); // Parar timer quando pagamento for confirmado
            Notiflix.Notify.success('Pagamento aprovado!');
            setTimeout(() => {
              window.location.href = '/pixel';
            }, 2000);
          }
        } catch (err) {
          console.error('Erro no polling', err);
          clearInterval(intervalId);
        }
      }, 2000);
    } else {
      Notiflix.Loading.remove();
      Notiflix.Notify.failure(data.message || 'Erro ao gerar QR Code. Tente novamente.');
    }
  } catch (err) {
    Notiflix.Loading.remove();
    console.error(err);
    Notiflix.Notify.failure('Erro na requisição. Verifique sua conexão.');
  }
});

// Copiar QR Code
document.getElementById('copyQr').addEventListener('click', () => {
  const input = document.getElementById('qrCodeValue');
  input.select();
  document.execCommand('copy');
  Notiflix.Notify.success('Copiado!');
});
</script>

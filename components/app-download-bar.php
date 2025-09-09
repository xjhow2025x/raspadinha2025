<?php
// Verificar se o usuário já fechou a barra (usando localStorage via JavaScript)
// e se é um dispositivo móvel
?>
<div id="app-download-bar" class="fixed top-0 left-0 right-0 z-50 bg-[#00FF88] text-black px-4 py-3 shadow-lg transform transition-transform duration-300">
  <div class="max-w-[1200px] mx-auto flex items-center justify-between">
    <!-- Ícone do App -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-[#00CC66] rounded-lg flex items-center justify-center">
        <i class="fas fa-mobile-alt text-white text-lg"></i>
      </div>
      <div class="flex flex-col">
        <span class="font-bold text-sm">Baixe nosso app</span>
        <span class="text-xs opacity-90">E ganhe muitos pontos!</span>
      </div>
    </div>
    
    <!-- Botão Baixar -->
    <button id="btn-download-app" class="bg-black text-[#00FF88] px-3 py-1.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:bg-gray-800 transition-colors">
      <i class="fas fa-download"></i>
      Baixar
    </button>
    
    <!-- Botão Fechar -->
    <button id="btn-close-app-bar" class="text-black hover:text-gray-700 transition-colors">
      <i class="fas fa-times text-lg"></i>
    </button>
  </div>
</div>

<!-- Modal de Instruções -->
<div id="app-instructions-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[99999] hidden items-center justify-center p-4">
  <div class="bg-[#1A1A1A] rounded-xl max-w-md w-full max-h-[90vh] overflow-y-auto relative shadow-2xl">
    <!-- Header do Modal -->
    <div class="flex items-center justify-between p-4 border-b border-gray-700 sticky top-0 bg-[#1A1A1A] z-10">
      <h2 class="text-white font-bold text-lg flex items-center gap-2">
        <i class="fas fa-mobile-alt text-green-500"></i>
        Instale Nosso App
      </h2>
      <button id="btn-close-modal" class="text-gray-400 hover:text-white transition-colors p-2 rounded-lg hover:bg-gray-800">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>
    
    <!-- Conteúdo do Modal -->
    <div class="p-4 space-y-6">
      <!-- Vantagens do App -->
      <div class="bg-yellow-600 bg-opacity-20 border border-yellow-500 rounded-lg p-4">
        <h3 class="text-yellow-400 font-bold text-lg mb-3 flex items-center gap-2">
          <i class="fas fa-gift"></i>
          Vantagens do App
        </h3>
        <ul class="space-y-2">
          <li class="flex items-center gap-2 text-white text-sm">
            <i class="fas fa-check text-green-400"></i>
            Acesso rápido direto da tela inicial
          </li>
          <li class="flex items-center gap-2 text-white text-sm">
            <i class="fas fa-check text-green-400"></i>
            Notificações de promoções exclusivas
          </li>
          <li class="flex items-center gap-2 text-white text-sm">
            <i class="fas fa-check text-green-400"></i>
            Experiência mais fluida e rápida
          </li>
          <li class="flex items-center gap-2 text-white text-sm">
            <i class="fas fa-check text-green-400"></i>
            Funciona mesmo offline
          </li>
          <li class="flex items-center gap-2 text-white text-sm">
            <i class="fas fa-check text-green-400"></i>
            Design otimizado para mobile
          </li>
        </ul>
      </div>
      
      <!-- Instruções iOS -->
      <div>
        <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
          <i class="fas fa-mobile-alt"></i>
          iOS (iPhone/iPad)
        </h3>
        <div class="space-y-3">
          <div class="flex items-start gap-3">
            <div class="w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-black font-bold text-xs">1</span>
            </div>
            <div>
              <p class="text-white font-medium text-sm">Abra no Safari</p>
              <p class="text-gray-400 text-xs">Este site deve ser aberto no navegador Safari</p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <div class="w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-black font-bold text-xs">2</span>
            </div>
            <div>
              <p class="text-white font-medium text-sm">Toque no botão Compartilhar</p>
              <p class="text-gray-400 text-xs flex items-center gap-1">
                <i class="fas fa-share"></i>
                Na barra inferior do Safari
              </p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <div class="w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-black font-bold text-xs">3</span>
            </div>
            <div>
              <p class="text-white font-medium text-sm">Selecione 'Adicionar à Tela de Início'</p>
              <p class="text-gray-400 text-xs">+ Role para baixo se necessário</p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <div class="w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-black font-bold text-xs">4</span>
            </div>
            <div>
              <p class="text-white font-medium text-sm">Confirme tocando em 'Adicionar'</p>
              <p class="text-gray-400 text-xs">O app aparecerá na sua tela inicial</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Instruções Android -->
      <div>
        <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
          <i class="fas fa-mobile-alt"></i>
          Android
        </h3>
        <div class="space-y-3">
          <div class="flex items-start gap-3">
            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-white font-bold text-xs">1</span>
            </div>
            <div>
              <p class="text-white font-medium text-sm">Abra no Chrome</p>
              <p class="text-gray-400 text-xs">Recomendamos usar o Google Chrome</p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-white font-bold text-xs">2</span>
            </div>
            <div>
              <p class="text-white font-medium text-sm">Toque no menu (3 pontos)</p>
              <p class="text-gray-400 text-xs">No canto superior direito</p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-white font-bold text-xs">3</span>
            </div>
            <div>
              <p class="text-white font-medium text-sm">Selecione 'Adicionar à tela inicial'</p>
              <p class="text-gray-400 text-xs">O app será instalado automaticamente</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Botão de Fechar no Final -->
      <div class="p-4 border-t border-gray-700 sticky bottom-0 bg-[#1A1A1A]">
        <button id="btn-close-modal-bottom" class="w-full bg-gray-700 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold transition-colors">
          <i class="fas fa-times mr-2"></i>
          Fechar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const appBar = document.getElementById('app-download-bar');
  const btnCloseBar = document.getElementById('btn-close-app-bar');
  const btnDownload = document.getElementById('btn-download-app');
  const modal = document.getElementById('app-instructions-modal');
  const btnCloseModal = document.getElementById('btn-close-modal');
  const btnCloseModalBottom = document.getElementById('btn-close-modal-bottom');
  
            // Verificar se é dispositivo móvel
          const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
          
          // Verificar se o usuário já fechou a barra
          const barClosed = localStorage.getItem('appDownloadBarClosed');
          
          // Verificar se está no backoffice (não mostrar a barra)
          const isBackoffice = window.location.pathname.includes('/backoffice/');
          
          // Verificar se está na tela inicial (apenas mostrar na tela inicial)
          const isHomePage = window.location.pathname === '/' || window.location.pathname === '/index.php';
  
  // Mostrar a barra apenas em dispositivos móveis, na tela inicial, se não foi fechada e não está no backoffice
  if (isMobile && !barClosed && !isBackoffice && isHomePage) {
    // Aguardar um pouco para não sobrepor o header
    setTimeout(() => {
      appBar.classList.add('show');
      document.body.classList.add('has-app-bar');
      
      // Aplicar classe de ajuste apenas aos banners principais
      const banners = document.querySelectorAll('img[src*="banners"]');
      banners.forEach(banner => {
        // Verificar se é realmente um banner principal (não um card)
        if (banner.alt && banner.alt.includes('Banner Principal')) {
          banner.classList.add('banner-ajuste-app');
        }
      });
    }, 1000);
  }
  
  // Fechar a barra
  btnCloseBar.addEventListener('click', function() {
    appBar.classList.remove('show');
    document.body.classList.remove('has-app-bar');
    
    // Remover classe de ajuste dos banners
    const banners = document.querySelectorAll('.banner-ajuste-app');
    banners.forEach(banner => {
      banner.classList.remove('banner-ajuste-app');
    });
    
    localStorage.setItem('appDownloadBarClosed', 'true');
  });
  
  // Abrir modal de instruções
  btnDownload.addEventListener('click', function() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  });
  
  // Fechar modal
  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
  }
  
  btnCloseModal.addEventListener('click', closeModal);
  btnCloseModalBottom.addEventListener('click', closeModal);
  
  // Fechar modal clicando fora
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeModal();
    }
  });
  
  // Fechar modal com ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
  
  // Função para esconder a barra quando qualquer modal for aberto
  function hideAppBar() {
    if (appBar.classList.contains('show')) {
      appBar.classList.remove('show');
      document.body.classList.remove('has-app-bar');
    }
  }
  
  // Escutar eventos de abertura de modais
  document.addEventListener('click', function(e) {
    // Verificar se o clique foi em um botão que abre modal
    if (e.target.matches('[onclick*="openDepositModal"], [onclick*="openWithdrawModal"], [onclick*="openModal"]')) {
      hideAppBar();
    }
    
    // Verificar se o clique foi em um elemento com classe que indica modal
    if (e.target.closest('.modal-trigger, .deposit-button, .withdraw-button')) {
      hideAppBar();
    }
  });
  
  // Escutar mudanças no DOM para detectar modais abertos
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach(function(node) {
          if (node.nodeType === 1) { // Element node
            // Verificar se foi adicionado um modal
            if (node.classList && (node.classList.contains('modal') || node.classList.contains('deposit-modal') || node.classList.contains('withdraw-modal'))) {
              hideAppBar();
            }
            // Verificar se foi adicionado um elemento com display: flex que pode ser um modal
            if (node.style && node.style.display === 'flex' && (node.classList.contains('fixed') || node.classList.contains('modal'))) {
              hideAppBar();
            }
          }
        });
      }
    });
  });
  
  // Observar mudanças no body
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
  
  // Escutar eventos personalizados de abertura de modal
  document.addEventListener('modalOpened', hideAppBar);
  document.addEventListener('depositModalOpened', hideAppBar);
  document.addEventListener('withdrawModalOpened', hideAppBar);
  
  // Função global para esconder a barra do app
  window.hideAppDownloadBar = hideAppBar;
  
  // Função global para mostrar a barra do app (se necessário)
  window.showAppDownloadBar = function() {
    if (isMobile && !localStorage.getItem('appDownloadBarClosed') && !isBackoffice && isHomePage) {
      appBar.classList.add('show');
      document.body.classList.add('has-app-bar');
      
      // Aplicar classe de ajuste apenas aos banners principais
      const banners = document.querySelectorAll('img[src*="banners"]');
      banners.forEach(banner => {
        // Verificar se é realmente um banner principal (não um card)
        if (banner.alt && banner.alt.includes('Banner Principal')) {
          banner.classList.add('banner-ajuste-app');
        }
      });
    }
  };
  
  // Função para aplicar ajuste aos banners existentes
  function applyBannerAdjustment() {
    if (document.body.classList.contains('has-app-bar')) {
      const banners = document.querySelectorAll('img[src*="banners"]');
      banners.forEach(banner => {
        // Verificar se é realmente um banner principal (não um card)
        if (banner.alt && banner.alt.includes('Banner Principal') && 
            !banner.classList.contains('banner-ajuste-app')) {
          banner.classList.add('banner-ajuste-app');
        }
      });
    }
  }
  
  // Aplicar ajuste quando novos elementos são adicionados ao DOM
  const bannerObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach(function(node) {
          if (node.nodeType === 1 && node.tagName === 'IMG') {
            if (node.src && node.src.includes('banners')) {
              applyBannerAdjustment();
            }
          }
        });
      }
    });
  });
  
  // Observar mudanças no body para detectar novos banners
  bannerObserver.observe(document.body, {
    childList: true,
    subtree: true
  });
});
</script>
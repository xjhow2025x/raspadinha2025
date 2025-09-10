<?php
// Buscar banners ativos do banco de dados
$tipo_banner = $tipo_banner ?? 'geral'; // Permite filtrar por tipo, padrão é 'geral'

try {
    $sql = "SELECT * FROM banners WHERE ativo = 1";
    $params = [];
    
    // Se um tipo específico foi solicitado, filtrar por ele
    if ($tipo_banner !== 'todos') {
        $sql .= " AND tipo = ?";
        $params[] = $tipo_banner;
    }
    
    // Filtrar por data se definidas
    $sql .= " AND (data_inicio IS NULL OR data_inicio <= NOW()) 
              AND (data_fim IS NULL OR data_fim >= NOW()) 
              ORDER BY ordem ASC, created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $banners_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $banners_db = [];
}

// Se não houver banners no banco, usar banners padrão
if (empty($banners_db)) {
    $banners_db = [
        [
            'id' => 1,
            'titulo' => 'Banner Padrão 1',
            'imagem' => '/assets/img/banner1.jpg',
            'link' => '#',
            'tipo' => 'geral'
        ],
        [
            'id' => 2,
            'titulo' => 'Banner Padrão 2', 
            'imagem' => '/assets/img/banner2.jpg',
            'link' => '#',
            'tipo' => 'geral'
        ]
    ];
}
?>

<div class="w-full max-w-[1200px] mx-auto mb-8 px-4 md:px-6">
    <div class="relative overflow-hidden rounded-xl shadow-lg">
        <div class="carousel-container relative">
            <div class="carousel-slides flex transition-transform duration-500 ease-in-out" id="carouselSlides">
                <?php foreach ($banners_db as $index => $banner): ?>
                    <div class="carousel-slide w-full flex-shrink-0">
                        <?php if (!empty($banner['link']) && $banner['link'] !== '#'): ?>
                            <a href="<?= htmlspecialchars($banner['link']); ?>" target="_blank">
                        <?php endif; ?>
                        
                        <img src="<?= htmlspecialchars($banner['imagem']); ?>" 
                             alt="<?= htmlspecialchars($banner['titulo']); ?>"
                             class="w-full h-48 md:h-64 lg:h-80 object-cover">
                        
                        <?php if (!empty($banner['link']) && $banner['link'] !== '#'): ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
         <!-- Winners Component inserido aqui -->
  <div class="w-full max-w-[1200px]">
    <?php include('./components/winners.php'); ?>
  </div>

        <!-- Indicadores -->
        <?php if (count($banners_db) > 1): ?>
            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                <?php foreach ($banners_db as $index => $banner): ?>
                    <button class="carousel-indicator w-3 h-3 rounded-full bg-white bg-opacity-50 hover:bg-opacity-75 transition-all duration-300" 
                            data-slide="<?= $index; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.getElementById('carouselSlides');
    const indicators = document.querySelectorAll('.carousel-indicator');
    const totalSlides = <?= count($banners_db); ?>;
    let currentSlide = 0;
    let autoplayInterval;

    function updateCarousel() {
        const translateX = -currentSlide * 100;
        slides.style.transform = `translateX(${translateX}%)`;
        
        // Atualizar indicadores
        indicators.forEach((indicator, index) => {
            if (index === currentSlide) {
                indicator.classList.remove('bg-opacity-50');
                indicator.classList.add('bg-opacity-100');
            } else {
                indicator.classList.remove('bg-opacity-100');
                indicator.classList.add('bg-opacity-50');
            }
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        updateCarousel();
    }

    function goToSlide(slideIndex) {
        currentSlide = slideIndex;
        updateCarousel();
    }

    function startAutoplay() {
        if (totalSlides > 1) {
            autoplayInterval = setInterval(nextSlide, 5000);
        }
    }

    function stopAutoplay() {
        clearInterval(autoplayInterval);
    }

    // Event listeners para indicadores
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            goToSlide(index);
            stopAutoplay();
            startAutoplay(); // Reiniciar autoplay
        });
    });

    // Inicializar
    updateCarousel();
    startAutoplay();

    // Pausar autoplay quando hover no carrossel
    const carouselContainer = document.querySelector('.carousel-container');
    carouselContainer.addEventListener('mouseenter', stopAutoplay);
    carouselContainer.addEventListener('mouseleave', startAutoplay);
});
</script>

<style>
.carousel-indicator.bg-opacity-100 {
    background-color: rgba(255, 255, 255, 1) !important;
}

.carousel-indicator.bg-opacity-50 {
    background-color: rgba(255, 255, 255, 0.5) !important;
}

/* Garantir que a imagem seja exibida completamente */
.carousel-slide img {
    object-fit: contain !important;
    width: 100%;
    height: auto;
    max-height: 12rem; /* h-48 */
    background-color: transparent; /* Fundo claro para preencher espaços vazios */
}

@media (min-width: 768px) {
    .carousel-slide img {
        max-height: 16rem; /* md:h-64 */
    }
}

@media (min-width: 1024px) {
    .carousel-slide img {
        max-height: 30rem;
    }
}
</style>

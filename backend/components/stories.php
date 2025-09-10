<?php
// Buscar stories ativos do banco de dados com suas mídias
try {
    // Primeiro buscar os stories
    $sql_stories = "SELECT * FROM stories_new WHERE ativo = 1 ORDER BY ordem ASC, created_at DESC LIMIT 10";
    $stmt_stories = $pdo->prepare($sql_stories);
    $stmt_stories->execute();
    $stories_data = $stmt_stories->fetchAll(PDO::FETCH_ASSOC);
    
    $stories = [];
    foreach ($stories_data as $story) {
        // Buscar mídias para cada story
        $sql_media = "SELECT * FROM story_media WHERE story_id = ? ORDER BY ordem ASC";
        $stmt_media = $pdo->prepare($sql_media);
        $stmt_media->execute([$story['id']]);
        $media = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
        
        $story['media'] = $media;
        $stories[] = $story;
    }
} catch (Exception $e) {
    $stories = [];
}

// Se não houver stories no banco, usar stories padrão com múltiplas mídias
if (empty($stories)) {
    $stories = [
        [
            'id' => 2,
            'titulo' => 'Ganhadores',
            'thumbnail' => 'https://appguirodrigues.site/imgs/CVIDEOS.png',
            'media' => [
                [
                    'tipo' => 'foto',
                    'arquivo' => 'https://appguirodrigues.site/imgs/CVIDEOS.png',
                    'thumbnail' => 'https://appguirodrigues.site/imgs/CVIDEOS.png',
                    'duracao' => 3000
                ]
            ]
        ]
    ];
}
?>

<!-- Stories Component -->
<div class="w-full max-w-[1200px] mx-auto px-4 mb-6">
    <div class="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-hide" id="storiesContainer">
        <?php foreach ($stories as $index => $story): ?>
            <button class="flex flex-col items-center w-16 shrink-0 group focus:outline-none story-item" 
                    data-story-id="<?= $story['id']; ?>"
                    data-story-title="<?= htmlspecialchars($story['titulo']); ?>"
                    data-story-description="<?= htmlspecialchars($story['descricao'] ?? ''); ?>"
                    data-story-media='<?= json_encode($story['media']); ?>'>
                <div class="relative w-16 h-16 rounded-full p-0.5 transition-all duration-200 
                           <?= $index === 0 ? 'bg-gradient-to-tr from-pink-500 via-purple-500 to-yellow-500' : 'bg-gradient-to-tr from-gray-400 to-gray-500 opacity-60'; ?>
                           group-hover:scale-105 group-active:scale-95">
                    <div class="w-full h-full rounded-full bg-black p-0.5">
                        <img src="<?= htmlspecialchars($story['thumbnail']); ?>" 
                             alt="<?= htmlspecialchars($story['titulo']); ?>" 
                             class="w-full h-full rounded-full object-cover">
                        <?php 
                        $hasVideo = false;
                        foreach ($story['media'] as $media) {
                            if ($media['tipo'] === 'video') {
                                $hasVideo = true;
                                break;
                            }
                        }
                        if ($hasVideo): ?>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-play text-white text-xs opacity-80"></i>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
                <span class="mt-1.5 text-xs text-gray-300 text-center leading-tight truncate w-full">
                    <?= htmlspecialchars($story['titulo']); ?>
                </span>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para visualização dos stories -->
<div id="storyModal" class="fixed inset-0 bg-black bg-opacity-90 z-[9999] hidden flex items-center justify-center">
    <div class="relative w-full h-full max-w-md mx-auto flex flex-col">
        <!-- Header do modal -->
        <div class="flex items-center justify-between p-4 text-white story-header">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-pink-500 via-purple-500 to-yellow-500 p-0.5">
                    <div class="w-full h-full rounded-full bg-black p-0.5">
                        <img id="storyModalAvatar" src="" alt="" class="w-full h-full rounded-full object-cover">
                    </div>
                </div>
                <div>
                    <h3 id="storyModalTitle" class="font-semibold text-sm"></h3>
                    <p id="storyModalTime" class="text-xs text-gray-400">agora</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button id="audioBtn" class="text-white hover:text-gray-300 transition-colors" title="Ativar/Desativar Áudio">
                    <i class="fas fa-volume-up text-lg"></i>
                </button>
                <button id="pauseStoryBtn" class="text-white hover:text-gray-300 transition-colors" title="Pausar/Retomar">
                    <i class="fas fa-pause text-lg"></i>
                </button>
                <button id="closeStoryModal" class="text-white hover:text-gray-300 transition-colors" title="Fechar">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Barras de progresso para múltiplas mídias -->
        <div class="px-4 pb-2">
            <div id="progressBars" class="flex gap-1">
                <!-- Barras serão inseridas dinamicamente -->
            </div>
        </div>
        
        <!-- Conteúdo do story -->
        <div class="flex-1 flex items-center justify-center p-4">
            <div id="storyContent" class="w-full h-full flex items-center justify-center">
                <!-- Conteúdo será inserido dinamicamente -->
            </div>
        </div>
        
        <!-- Descrição (se houver) -->
        <div id="storyDescription" class="p-4 text-white text-center hidden">
            <p class="text-sm"></p>
        </div>
        
        <!-- Botões de navegação -->
        <div class="absolute top-1/2 left-4 transform -translate-y-1/2 z-10">
            <button id="prevMediaBtn" class="bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all" title="Mídia anterior">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <div class="absolute top-1/2 right-4 transform -translate-y-1/2 z-10">
            <button id="nextMediaBtn" class="bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all" title="Próxima mídia">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Navegação entre stories -->
        <div class="absolute bottom-20 left-1/2 transform -translate-x-1/2 flex gap-2 z-10">
            <button id="prevStoryBtn" class="bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm hover:bg-opacity-70 transition-all" title="Story anterior">
                <i class="fas fa-arrow-left mr-1"></i> Story
            </button>
            <button id="nextStoryBtn" class="bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm hover:bg-opacity-70 transition-all" title="Próximo story">
                Story <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </div>
        
        <!-- Áreas de toque para navegação rápida -->
        <div class="absolute left-0 w-1/4 cursor-pointer" id="prevTouchArea" style="top: 120px; bottom: 0;"></div>
        <div class="absolute right-0 w-1/4 cursor-pointer" id="nextTouchArea" style="top: 120px; bottom: 0;"></div>
    </div>
</div>

<style>
/* Scrollbar personalizada para stories */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Animações para stories */
.story-item {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estilo para vídeos no modal */
.story-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}

.story-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 12px;
}

/* Efeito de hover nos stories */
.story-item:hover .bg-gradient-to-tr {
    transform: scale(1.05);
}

/* Header do modal */
.story-header {
    position: relative;
    z-index: 10000;
    padding-top: max(1rem, env(safe-area-inset-top));
}

/* Garantir que os botões do header tenham z-index alto */
.story-header button {
    z-index: 10001;
    position: relative;
    pointer-events: auto;
}

/* Responsividade */
@media (max-width: 640px) {
    #storyModal .max-w-md {
        max-width: 100%;
    }
    
    /* Ajustes para mobile - garantir que o header fique visível */
    #storyModal {
        padding-top: env(safe-area-inset-top);
    }
    
    /* Header fixo no topo para mobile */
    .story-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 10000;
        background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 70%, transparent 100%);
        padding-top: max(1rem, env(safe-area-inset-top));
        padding-bottom: 1rem;
        max-width: 28rem;
        margin: 0 auto;
    }
    
    /* Ajustar conteúdo para não ficar atrás do header */
    #storyModal .flex-1 {
        margin-top: 10px;
    }
    
    /* Garantir que os botões sejam grandes o suficiente para toque */
    #storyModal button {
        min-width: 44px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: auto;
    }
    
    /* Garantir que as áreas de toque não interfiram com o header */
    #prevTouchArea, #nextTouchArea {
        top: 120px !important;
        pointer-events: auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const storyItems = document.querySelectorAll('.story-item');
    const storyModal = document.getElementById('storyModal');
    const closeModal = document.getElementById('closeStoryModal');
    const pauseBtn = document.getElementById('pauseStoryBtn');
    const storyContent = document.getElementById('storyContent');
    const storyModalTitle = document.getElementById('storyModalTitle');
    const storyModalAvatar = document.getElementById('storyModalAvatar');
    const storyDescription = document.getElementById('storyDescription');
    const progressBars = document.getElementById('progressBars');
    const prevMediaBtn = document.getElementById('prevMediaBtn');
    const nextMediaBtn = document.getElementById('nextMediaBtn');
    const prevStoryBtn = document.getElementById('prevStoryBtn');
    const nextStoryBtn = document.getElementById('nextStoryBtn');
    const prevTouchArea = document.getElementById('prevTouchArea');
    const nextTouchArea = document.getElementById('nextTouchArea');
    const audioBtn = document.getElementById('audioBtn');
    
    let currentStoryIndex = 0;
    let currentMediaIndex = 0;
    let stories = [];
    let progressInterval;
    let isPaused = false;
    let currentProgress = 0;
    
    // Coletar dados dos stories
    storyItems.forEach((item, index) => {
        const mediaData = JSON.parse(item.dataset.storyMedia);
        stories.push({
            id: item.dataset.storyId,
            title: item.dataset.storyTitle,
            description: item.dataset.storyDescription,
            thumbnail: item.querySelector('img').src,
            media: mediaData
        });
        
        // Adicionar evento de clique
        item.addEventListener('click', () => {
            currentStoryIndex = index;
            currentMediaIndex = 0;
            openStoryModal();
        });
    });
    
    function openStoryModal() {
        storyModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Tentar habilitar autoplay através de interação do usuário
        document.addEventListener('click', enableAutoplay, { once: true });
        document.addEventListener('touchstart', enableAutoplay, { once: true });
        
        showStory(currentStoryIndex);
        
        // Tentar reproduzir vídeos após um pequeno delay
        setTimeout(() => {
            tryPlayCurrentVideo();
        }, 500);
    }
    
    function enableAutoplay() {
        // Esta função é chamada após uma interação do usuário
        // para garantir que o autoplay funcione
        const videos = storyContent.querySelectorAll('video');
        videos.forEach(video => {
            if (video.paused) {
                // Tentar reproduzir com áudio após interação do usuário
                video.muted = false;
                video.volume = 0.8;
                video.play().then(() => {
                    console.log('Vídeo reproduzindo com áudio após interação');
                }).catch(err => {
                    console.log('Erro ao reproduzir com áudio:', err);
                    // Se falhar, tentar sem som
                    video.muted = true;
                    video.play().catch(err2 => {
                        console.log('Erro ao reproduzir sem áudio:', err2);
                    });
                });
            } else if (video.muted) {
                // Se já está reproduzindo mas sem som, tentar ativar áudio
                video.muted = false;
                video.volume = 0.8;
            }
        });
    }
    
    function closeStoryModal() {
        // Pausar e limpar todos os vídeos e iframes
        const videos = storyContent.querySelectorAll('video');
        const iframes = storyContent.querySelectorAll('iframe');
        
        videos.forEach(video => {
            video.pause();
            video.currentTime = 0;
            video.src = '';
        });
        
        iframes.forEach(iframe => {
            iframe.src = '';
        });
        
        // Limpar conteúdo
        storyContent.innerHTML = '';
        
        // Fechar modal
        storyModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        clearInterval(progressInterval);
        isPaused = false;
        updatePauseButton();
        
        // Atualizar visualizações
        updateStoryViews(stories[currentStoryIndex].id);
    }
    
    function createProgressBars(mediaCount) {
        progressBars.innerHTML = '';
        for (let i = 0; i < mediaCount; i++) {
            const progressContainer = document.createElement('div');
            progressContainer.className = 'flex-1 bg-gray-600 rounded-full h-1';
            
            const progressBar = document.createElement('div');
            progressBar.className = 'bg-white h-1 rounded-full transition-all duration-100 ease-linear';
            progressBar.style.width = '0%';
            progressBar.id = `progress-${i}`;
            
            progressContainer.appendChild(progressBar);
            progressBars.appendChild(progressContainer);
        }
    }
    
    function showStory(storyIndex) {
        if (storyIndex < 0 || storyIndex >= stories.length) return;
        
        const story = stories[storyIndex];
        currentMediaIndex = 0;
        
        // Atualizar header
        storyModalTitle.textContent = story.title;
        storyModalAvatar.src = story.thumbnail;
        
        // Atualizar descrição
        if (story.description) {
            storyDescription.querySelector('p').textContent = story.description;
            storyDescription.classList.remove('hidden');
        } else {
            storyDescription.classList.add('hidden');
        }
        
        // Criar barras de progresso
        createProgressBars(story.media.length);
        
        // Mostrar primeira mídia
        showMedia(storyIndex, 0);
        
        // Atualizar botões de navegação
        updateNavigationButtons();
    }
    
    function showMedia(storyIndex, mediaIndex) {
        const story = stories[storyIndex];
        const media = story.media[mediaIndex];
        
        if (!media) return;
        
        // Pausar vídeos anteriores antes de limpar
        const previousVideos = storyContent.querySelectorAll('video');
        previousVideos.forEach(video => {
            video.pause();
            video.currentTime = 0;
        });
        
        // Limpar conteúdo anterior
        storyContent.innerHTML = '';
        
        // Marcar mídias anteriores como completas
        for (let i = 0; i < mediaIndex; i++) {
            const progressBar = document.getElementById(`progress-${i}`);
            if (progressBar) progressBar.style.width = '100%';
        }
        
        // Resetar mídias posteriores
        for (let i = mediaIndex + 1; i < story.media.length; i++) {
            const progressBar = document.getElementById(`progress-${i}`);
            if (progressBar) progressBar.style.width = '0%';
        }
        
        // Adicionar conteúdo baseado no tipo
        if (media.tipo === 'video') {
            if (media.arquivo.includes('youtube.com') || media.arquivo.includes('youtu.be')) {
                // YouTube embed
                const iframe = document.createElement('iframe');
                let videoUrl = media.arquivo;
                
                // Converter URL do YouTube para embed se necessário
                if (videoUrl.includes('watch?v=')) {
                    const videoId = videoUrl.split('watch?v=')[1].split('&')[0];
                    videoUrl = `https://www.youtube.com/embed/${videoId}`;
                }
                
                iframe.src = videoUrl + '?autoplay=1&mute=0&controls=1&modestbranding=1&rel=0';
                iframe.className = 'w-full h-full rounded-lg';
                iframe.frameBorder = '0';
                iframe.allowFullscreen = true;
                iframe.allow = 'autoplay; encrypted-media';
                storyContent.appendChild(iframe);
            } else {
                // Vídeo direto
                const video = document.createElement('video');
                video.src = media.arquivo;
                video.className = 'story-video';
                video.autoplay = true; // Ativar autoplay
                video.muted = false; // Sem mute para permitir áudio
                video.loop = true;
                video.playsInline = true;
                video.controls = true;
                video.preload = 'auto';
                video.volume = 0.8;
                
                // Configurar vídeo para reprodução com áudio
                video.addEventListener('loadeddata', () => {
                    // Tentar reproduzir automaticamente
                    video.play().then(() => {
                        console.log('Vídeo iniciado com áudio automaticamente');
                        // Esconder controles após 3 segundos se estiver reproduzindo
                        setTimeout(() => {
                            if (!video.paused) {
                                video.controls = false;
                            }
                        }, 3000);
                    }).catch(err => {
                        console.log('Autoplay bloqueado, mantendo controles visíveis:', err);
                        video.controls = true;
                    });
                });
                
                // Listener para quando o usuário clicar no vídeo
                video.addEventListener('click', () => {
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                });
                
                storyContent.appendChild(video);
            }
        } else {
            // Imagem
            const img = document.createElement('img');
            img.src = media.arquivo;
            img.className = 'story-image';
            img.alt = story.title;
            storyContent.appendChild(img);
        }
        
        // Iniciar progresso
        startProgress(media.duracao || 5000);
        
        // Atualizar botões de navegação
        updateNavigationButtons();
    }
    
    function startProgress(duration) {
        if (isPaused) return;
        
        clearInterval(progressInterval);
        currentProgress = 0;
        
        const progressBar = document.getElementById(`progress-${currentMediaIndex}`);
        if (!progressBar) return;
        
        const increment = 100 / (duration / 100);
        
        progressInterval = setInterval(() => {
            if (isPaused) return;
            
            currentProgress += increment;
            progressBar.style.width = currentProgress + '%';
            
            if (currentProgress >= 100) {
                nextMedia();
            }
        }, 100);
    }
    
    function pauseProgress() {
        isPaused = !isPaused;
        updatePauseButton();
        
        // Pausar/retomar vídeos também
        const videos = storyContent.querySelectorAll('video');
        videos.forEach(video => {
            if (isPaused) {
                video.pause();
            } else {
                video.play().catch(err => {
                    console.log('Erro ao retomar vídeo:', err);
                });
            }
        });
        
        if (!isPaused) {
            const story = stories[currentStoryIndex];
            const media = story.media[currentMediaIndex];
            const remainingDuration = (media.duracao || 5000) * (100 - currentProgress) / 100;
            startProgress(remainingDuration);
        }
    }
    
    function updatePauseButton() {
        const icon = pauseBtn.querySelector('i');
        if (isPaused) {
            icon.className = 'fas fa-play text-lg';
            pauseBtn.title = 'Retomar';
        } else {
            icon.className = 'fas fa-pause text-lg';
            pauseBtn.title = 'Pausar';
        }
    }
    
    function nextMedia() {
        const story = stories[currentStoryIndex];
        if (currentMediaIndex < story.media.length - 1) {
            currentMediaIndex++;
            showMedia(currentStoryIndex, currentMediaIndex);
        } else {
            nextStory();
        }
    }
    
    function prevMedia() {
        if (currentMediaIndex > 0) {
            currentMediaIndex--;
            showMedia(currentStoryIndex, currentMediaIndex);
        } else {
            prevStory();
        }
    }
    
    function nextStory() {
        if (currentStoryIndex < stories.length - 1) {
            currentStoryIndex++;
            showStory(currentStoryIndex);
        } else {
            closeStoryModal();
        }
    }
    
    function prevStory() {
        if (currentStoryIndex > 0) {
            currentStoryIndex--;
            showStory(currentStoryIndex);
        }
    }
    
    function updateNavigationButtons() {
        const story = stories[currentStoryIndex];
        
        // Botões de mídia
        prevMediaBtn.style.display = currentMediaIndex > 0 ? 'block' : 'none';
        nextMediaBtn.style.display = currentMediaIndex < story.media.length - 1 ? 'block' : 'none';
        
        // Botões de story
        prevStoryBtn.style.display = currentStoryIndex > 0 ? 'block' : 'none';
        nextStoryBtn.style.display = currentStoryIndex < stories.length - 1 ? 'block' : 'none';
    }
    
    function tryPlayCurrentVideo() {
        const videos = storyContent.querySelectorAll('video');
        videos.forEach(video => {
            if (video.paused) {
                // Tentar reproduzir com áudio
                video.muted = false;
                video.volume = 0.8;
                video.play().then(() => {
                    console.log('Vídeo iniciado com áudio');
                }).catch(err => {
                    console.log('Falha ao reproduzir com áudio, tentando sem som:', err);
                    video.muted = true;
                    video.play().catch(err2 => {
                        console.log('Falha total no autoplay:', err2);
                    });
                });
            }
        });
    }
    
    function toggleAudio() {
         const videos = storyContent.querySelectorAll('video');
         const iframes = storyContent.querySelectorAll('iframe');
         const audioIcon = audioBtn.querySelector('i');
         
         videos.forEach(video => {
             if (video.muted) {
                 // Ativar áudio
                 video.muted = false;
                 video.volume = 0.8;
                 audioIcon.className = 'fas fa-volume-up text-lg';
                 audioBtn.title = 'Desativar Áudio';
                 
                 // Tentar reproduzir se estiver pausado
                 if (video.paused) {
                     video.play().catch(err => {
                         console.log('Erro ao reproduzir com áudio:', err);
                     });
                 }
             } else {
                 // Desativar áudio
                 video.muted = true;
                 audioIcon.className = 'fas fa-volume-mute text-lg';
                 audioBtn.title = 'Ativar Áudio';
             }
         });
         
         // Para vídeos do YouTube, recarregar com parâmetro de mute
         iframes.forEach(iframe => {
             if (iframe.src.includes('youtube.com')) {
                 const currentSrc = iframe.src;
                 if (currentSrc.includes('mute=1')) {
                     iframe.src = currentSrc.replace('mute=1', 'mute=0');
                     audioIcon.className = 'fas fa-volume-up text-lg';
                     audioBtn.title = 'Desativar Áudio';
                 } else if (currentSrc.includes('mute=0')) {
                     iframe.src = currentSrc.replace('mute=0', 'mute=1');
                     audioIcon.className = 'fas fa-volume-mute text-lg';
                     audioBtn.title = 'Ativar Áudio';
                 }
             }
         });
     }
     
     function updateStoryViews(storyId) {
         fetch('/api/update_story_views.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json',
             },
             body: JSON.stringify({ story_id: storyId })
         }).catch(err => console.log('Erro ao atualizar visualizações:', err));
     }
    
    // Event listeners
    closeModal.addEventListener('click', (e) => {
        e.stopPropagation();
        closeStoryModal();
    });
    pauseBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        pauseProgress();
    });
    audioBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleAudio();
    });
    prevMediaBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        prevMedia();
    });
    nextMediaBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        nextMedia();
    });
    prevStoryBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        prevStory();
    });
    nextStoryBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        nextStory();
    });
    prevTouchArea.addEventListener('click', (e) => {
        e.stopPropagation();
        prevMedia();
    });
    nextTouchArea.addEventListener('click', (e) => {
        e.stopPropagation();
        nextMedia();
    });
    
    // Fechar modal ao clicar fora do conteúdo
    storyModal.addEventListener('click', (e) => {
        if (e.target === storyModal) {
            closeStoryModal();
        }
    });
    
    // Fechar modal com tecla ESC e navegação por teclado
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !storyModal.classList.contains('hidden')) {
            closeStoryModal();
        } else if (e.key === ' ' && !storyModal.classList.contains('hidden')) {
            e.preventDefault();
            pauseProgress();
        } else if (e.key === 'ArrowLeft' && !storyModal.classList.contains('hidden')) {
            e.preventDefault();
            prevMedia();
        } else if (e.key === 'ArrowRight' && !storyModal.classList.contains('hidden')) {
            e.preventDefault();
            nextMedia();
        }
    });
});
</script>
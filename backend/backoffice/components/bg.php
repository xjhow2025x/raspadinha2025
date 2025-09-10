<?php
echo '
    <style>
    /* Reset básico para remover margens e paddings padrão */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Oculta a barra de rolagem para uma estética mais limpa */
    *::-webkit-scrollbar {
        display: none; /* Para navegadores baseados em WebKit (Chrome, Safari) */
    }
    * {
        -ms-overflow-style: none;  /* Para Internet Explorer e Edge */
        scrollbar-width: none;  /* Para Firefox */
    }

    body {
        position: relative;
        
        /* Fundo com gradiente sutil que pulsa */
        background: linear-gradient(45deg, #1a1a1a, #222222, #1a1a1a); /* Cores escuras para combinar com o tema */
        background-size: 400% 400%; /* Tamanho maior para permitir a animação do gradiente */
        background-position: 0% 0%; /* Posição inicial do gradiente */
        
        height: auto;
        min-height: 100vh; /* Garante que o body ocupe pelo menos a altura total da viewport */
        width: 100vw; /* Garante que o body ocupe a largura total da viewport */
        
        /* Centraliza o conteúdo principal da página */
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column; /* Adicionado para empilhar conteúdo verticalmente */

        overflow-y: auto; /* Permite rolagem vertical se o conteúdo exceder a altura */
        overflow-x: hidden; /* Esconde rolagem horizontal do body para evitar barras indesejadas */
        
        /* Transições e Animações */
        transition: background 2s ease-in-out; /* Transição suave para mudanças de fundo */
        animation: pulseGradient 5s infinite alternate ease-in-out, pulseShadow 3s infinite alternate ease;
        
        /* Sombra interna que pulsa, criando um efeito de profundidade */
        box-shadow: inset 0 0 400px 200px rgba(0, 0, 0, 0.5);
    }

    /* Animação do gradiente de fundo */
    @keyframes pulseGradient {
        0% { background-position: 0% 0%; }
        100% { background-position: 100% 100%; } /* Anima o gradiente para o canto oposto */
    }

    /* Animação da sombra interna */
    @keyframes pulseShadow {
        0% { box-shadow: inset 0 0 400px 200px rgba(0, 0, 0, 0.5); }
        50% { box-shadow: inset 0 0 450px 220px rgba(0, 0, 0, 0.6); } /* Sombra mais intensa no meio do ciclo */
        100% { box-shadow: inset 0 0 400px 200px rgba(0, 0, 0, 0.5); }
    }
    </style>
';
?>

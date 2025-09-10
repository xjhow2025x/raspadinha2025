<!-- Carrossel de Ganhadores Ao Vivo - Estilo da Imagem -->
<div class="winners-main-container">
  <!-- Card Principal Dividido -->
  <div class="winners-main-card">
    <!-- Seção Superior - Verde com "ÚLTIMOS GANHOS" -->
    <div class="winners-header-section">
      <div class="winners-header">
        <div class="header-left">
          <div class="live-icon">
            <span class="live-dot"></span>
            <span class="live-text">AO VIVO</span>
          </div>
          <h3 class="winners-title">ÚLTIMOS <span class="highlight">GANHOS</span></h3>
        </div>
      </div>
    </div>
    
    <!-- Seção Inferior - Preta com os Prêmios -->
    <div class="winners-prizes-section">
      <!-- Container dos Cards Pequenos que Passam -->
      <div class="winners-carousel-track">
        <div id="winners-track" class="winners-sliding-cards">
          <!-- Os cards pequenos serão carregados dinamicamente via JavaScript -->
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Container Principal */
.winners-main-container {
  width: 100%;
  padding: 16px;
  margin: 10px 0px 0px 0px;
}

/* Card Principal Dividido */
.winners-main-card {
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
}

/* Seção Superior - Verde */
.winners-header-section {
  background: linear-gradient(135deg, #1a5f3f 0%, #0f3d2a 100%);
  padding: 20px 20px 16px 20px;
  position: relative;
  border-bottom: 2px solid rgba(255, 215, 0, 0.3);
}

/* Seção Inferior - Preta */
.winners-prizes-section {
  background: #13151B;
  padding: 16px 20px 20px 20px;
  flex: 1;
}

/* Header do Card Principal */
.winners-header {
  position: relative;
  z-index: 2;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

/* Ícone AO VIVO */
.live-icon {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(0, 0, 0, 0.3);
  padding: 8px 12px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.live-dot {
  width: 8px;
  height: 8px;
  background: #00ff88;
  border-radius: 50%;
  animation: pulse-dot 2s ease-in-out infinite;
}

.live-text {
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.winners-title {
  color: #ffffff;
  font-size: 24px;
  font-weight: 800;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  letter-spacing: 1px;
  text-transform: uppercase;
}

.winners-title .highlight {
  color: #00ff88;
}

/* Container da Track dos Cards */
.winners-carousel-track {
    /* background: rgba(0, 0, 0, 0.3); */
    /* border-radius: 12px; */
    padding: 12px;
    overflow: hidden;
    position: relative;
    /* backdrop-filter: blur(10px); */
    /* border: 1px solid rgba(255, 255, 255, 0.1); */
}

/* Track dos Cards Pequenos */
.winners-sliding-cards {
  display: flex;
  gap: 12px;
  animation: slide-cards 25s linear infinite;
}

/* Cards Pequenos Horizontais - Estilo da Imagem */
.winner-mini-card {
  min-width: 320px;
  height: 70px;
  border: 1px solid rgba(255, 215, 0, 0.5);
  border-radius: 12px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  position: relative;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.winner-mini-card:hover {
  transform: translateY(-2px);
  border-color: rgba(255, 215, 0, 0.8);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
}

/* Foto do Prêmio */
.prize-image-mini {
  width: 46px;
  height: 46px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
  background: #2a2a2a;
}

/* Informações do Ganhador */
.winner-info-mini {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  gap: 2px;
}

/* Nome da Pessoa */
.winner-name-mini {
  font-size: 14px;
  font-weight: 700;
  color: #ffffff;
  text-transform: capitalize;
  letter-spacing: 0.3px;
}

/* Nome do Prêmio */
.prize-name-mini {
  font-size: 12px;
  font-weight: 500;
  color: #cccccc;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Valor Ganho */
.prize-value-mini {
  font-size: 16px;
  font-weight: 800;
  color: #ffd700;
  text-shadow: 0 0 4px rgba(255, 215, 0, 0.3);
}

/* Animações */
@keyframes slide-cards {
  0% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(-100%);
  }
}

@keyframes pulse-dot {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(0.8);
  }
}

/* Responsividade */
@media (max-width: 768px) {
  .winners-main-container {
    padding: 0px;
  }
  
  .winners-header-section {
    padding: 16px 16px 12px 16px;
  }
  
  .winners-prizes-section {
    padding: 12px 16px 16px 16px;
  }
  
  .header-left {
    gap: 12px;
  }
  
  .winners-title {
    font-size: 20px;
  }
  
  .live-icon {
    padding: 6px 10px;
  }
  
  .live-text {
    font-size: 11px;
  }
  
  .winners-carousel-track {
    padding: 10px;
  }
  
  .winners-sliding-cards {
    animation: slide-cards 18s linear infinite;
    gap: 10px;
  }
  
  .winner-mini-card {
    min-width: 280px;
    height: 65px;
    padding: 10px;
  }
  
  .prize-image-mini {
    width: 42px;
    height: 42px;
  }
  
  .winner-name-mini {
    font-size: 13px;
  }
  
  .prize-name-mini {
    font-size: 11px;
  }
  
  .prize-value-mini {
    font-size: 15px;
  }
}

@media (max-width: 480px) {
  .winners-header-section {
    padding: 14px 14px 10px 14px;
  }
  
  .winners-prizes-section {
    padding: 10px 14px 14px 14px;
  }
  
      .header-left {
        flex-direction: row;
        align-items: anchor-center;
        gap: 8px;
    }
  
  .winners-title {
    font-size: 18px;
  }
  
  .winners-sliding-cards {
    animation: slide-cards 15s linear infinite;
  }
  
  .winner-mini-card {
    min-width: 250px;
    height: 60px;
    padding: 8px;
  }
  
  .prize-image-mini {
    width: 38px;
    height: 38px;
  }
  
  .winner-name-mini {
    font-size: 12px;
  }
  
  .prize-name-mini {
    font-size: 10px;
  }
  
  .prize-value-mini {
    font-size: 14px;
  }
}
</style>

<script>
// Lista de nomes brasileiros realistas
const nomesBrasileiros = [
  "Ana Silva", "Carlos Mendes", "Maria Santos", "João Oliveira", "Paula Costa",
  "Pedro Lima", "Fernanda Rocha", "Roberto Alves", "Juliana Ferreira", "Lucas Barbosa",
  "Camila Souza", "Paulo Martins", "André Pereira", "Carla Nascimento", "Rafael Torres",
  "Larissa Campos", "Bruno Cardoso", "Tatiane Ribeiro", "Ricardo Gomes", "Jéssica Moreira",
  "Marcos Silva", "Aline Costa", "Otávio Santos", "Mariana Lima", "Stella Rocha",
  "Nayara Alves", "Robertinha Ferreira", "Marcelo Barbosa", "Lúcio Souza", "Felipe Martins",
  "Gabriela Pereira", "Thiago Nascimento", "Vanessa Torres", "Diego Campos", "Priscila Cardoso",
  "Gustavo Ribeiro", "Amanda Oliveira", "Rodrigo Santos", "Patrícia Lima", "Henrique Costa",
  "Bianca Rocha", "Leonardo Alves", "Cristina Ferreira", "Fábio Barbosa", "Renata Silva",
  "Eduardo Mendes", "Isabela Santos", "Vinicius Oliveira", "Letícia Costa", "Mateus Lima"
];

const cidadesBrasileiras = [
  "São Paulo", "Rio de Janeiro", "Belo Horizonte", "Salvador", "Brasília",
  "Fortaleza", "Recife", "Porto Alegre", "Curitiba", "Manaus", "Goiânia",
  "Belém", "Vitória", "Natal", "Campo Grande", "João Pessoa", "Teresina",
  "Aracaju", "Maceió", "Florianópolis", "Campinas", "Santos", "Sorocaba",
  "Ribeirão Preto", "Uberlândia", "Contagem", "Juiz de Fora", "Joinville",
  "Londrina", "Niterói", "Caxias do Sul", "Campos dos Goytacazes"
];

// Prêmios fixos para evitar chamadas à API
const premiosFixos = [
  { nome: "iPhone 15", icone: "/assets/img/icons/iphone15.png", valor: 6200.00 },
  { nome: "AirPods Pro", icone: "/assets/img/icons/airpods.png", valor: 1500.00 },
  { nome: "PIX R$4500", icone: "/assets/img/icons/cash.png", valor: 4500.00 },
  { nome: "PIX R$2800", icone: "/assets/img/icons/cash.png", valor: 2800.00 },
  { nome: "PIX R$1200", icone: "/assets/img/icons/cash.png", valor: 1200.00 },
  { nome: "Samsung Galaxy", icone: "/assets/img/icons/samsung.png", valor: 3500.00 },
  { nome: "Notebook", icone: "/assets/img/icons/notebook.png", valor: 4200.00 },
  { nome: "Smart TV", icone: "/assets/img/icons/tv.png", valor: 2500.00 },
  { nome: "PlayStation 5", icone: "/assets/img/icons/ps5.png", valor: 3800.00 },
  { nome: "Xbox Series X", icone: "/assets/img/icons/xbox.png", valor: 3600.00 },
  { nome: "Capinha de Celular", icone: "/assets/img/icons/phone-case.png", valor: 20.00 },
  { nome: "Bicicleta", icone: "/assets/img/icons/bike.png", valor: 1000.00 }
];

let ganhadoresFixos = [];

function abreviarNome(nomeCompleto) {
  const partes = nomeCompleto.split(' ');
  if (partes.length === 1) return partes[0];
  
  const primeiro = partes[0];
  const ultimaLetra = partes[partes.length - 1].charAt(0);
  
  return `${primeiro} ${ultimaLetra}***`;
}

function formatarValor(valor) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(valor);
}

function criarGanhadoresFixos() {
  const ganhadores = [];
  
  for (let i = 0; i < 25; i++) {
    const nome = nomesBrasileiros[i % nomesBrasileiros.length];
    const cidade = cidadesBrasileiras[i % cidadesBrasileiras.length];
    const premio = premiosFixos[i % premiosFixos.length];
    
    ganhadores.push({
      nome: abreviarNome(nome),
      cidade: cidade,
      premio: premio,
      id: i
    });
  }
  
  return ganhadores;
}

function createMiniWinnerCard(ganhador) {
  const imagemPremio = ganhador.premio.icone.startsWith('/') 
    ? ganhador.premio.icone 
    : `/assets/img/icons/${ganhador.premio.icone}`;
    
  return `
    <div class="winner-mini-card">
      <img src="${imagemPremio}" alt="${ganhador.premio.nome}" class="prize-image-mini" 
           onerror="this.src='/assets/img/icons/cash.png'">
      
      <div class="winner-info-mini">
        <div class="winner-name-mini">${ganhador.nome}</div>
        <div class="prize-name-mini">${ganhador.premio.nome}</div>
        <div class="prize-value-mini">${formatarValor(ganhador.premio.valor)}</div>
      </div>
    </div>
  `;
}

function inicializarCarrosselOrganizado() {
  const track = document.getElementById('winners-track');
  if (!track) return;
  
  // Criar ganhadores fixos uma única vez
  ganhadoresFixos = criarGanhadoresFixos();
  
  // Criar múltiplas cópias para loop infinito suave
  const ganhadoresExtendidos = [...ganhadoresFixos, ...ganhadoresFixos, ...ganhadoresFixos];
  track.innerHTML = ganhadoresExtendidos.map(createMiniWinnerCard).join('');
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
  // Inicializar carrossel organizado
  inicializarCarrosselOrganizado();
  
  // Pausar animação quando hover no card principal
  const mainCard = document.querySelector('.winners-main-card');
  const track = document.getElementById('winners-track');
  
  if (mainCard && track) {
    mainCard.addEventListener('mouseenter', () => {
      track.style.animationPlayState = 'paused';
    });
    
    mainCard.addEventListener('mouseleave', () => {
      track.style.animationPlayState = 'running';
    });
  }
});
</script>
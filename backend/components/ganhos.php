<script>
const nomes = [
    "Ana", "Carlos", "João", "Maria", "Luiz", "Paula", "Pedro", "Fernanda", "Roberto", 
    "Juliana", "Lucas", "Camila", "Paulo", "André", "Carla", "Rafael", "Larissa", "Bruno", 
    "Tatiane", "Ricardo", "Jéssica", "Marcos", "Aline", "Otávio", "Maruam", "Stela", "Nayara", "Robertinha", "Marcelo", "Lúcio", "Felipe"
];

function gerarValorAleatorio() {
    const valor = Math.floor(Math.random() * (5000 / 100)) * 100 + 100;
    return valor;
}

function escolherNomeAleatorio() {
    const index = Math.floor(Math.random() * nomes.length);
    return nomes[index];
}

function emitirAlerta() {
    const nome = escolherNomeAleatorio();
    const valor = gerarValorAleatorio();
    Notiflix.Notify.info(`${nome} ganhou R$ ${valor.toLocaleString('pt-BR')}`);
}

function iniciarAlertas() {
    function emitirAlertaComIntervalo() {
        
        const tempoAleatorio = Math.floor(Math.random() * (40000 - 15000 + 1)) + 15000;
        setTimeout(emitirAlerta, tempoAleatorio);
        
        setTimeout(emitirAlertaComIntervalo, tempoAleatorio);
    }
    emitirAlertaComIntervalo();
}

iniciarAlertas();
</script>

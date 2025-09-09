/**
 * Gerenciador de Saldo - VamoBet
 * Responsável por atualizar o saldo do usuário em tempo real
 */

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

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar saldo inicial
    if (typeof window.atualizarSaldoGlobal === 'function') {
        window.atualizarSaldoGlobal();
    }
    
    // Atualizar saldo automaticamente a cada 30 segundos
    setInterval(function() {
        if (typeof window.atualizarSaldoGlobal === 'function') {
            window.atualizarSaldoGlobal();
        }
    }, 30000);
});

// Listener para eventos personalizados de atualização de saldo
document.addEventListener('saldoUpdated', function(event) {
    console.log('Evento de saldo atualizado:', event.detail);
});

// Função para forçar atualização do saldo (útil para chamadas externas)
window.forcarAtualizacaoSaldo = function() {
    if (typeof window.atualizarSaldoGlobal === 'function') {
        return window.atualizarSaldoGlobal();
    }
    return null;
};

// Função para verificar se o saldo está sendo atualizado corretamente
window.verificarSaldo = async function() {
    try {
        const res = await fetch('/api/get_saldo.php');
        const json = await res.json();
        
        if (json.success) {
            console.log('Saldo atual:', json.saldo);
            return json.saldo;
        } else {
            console.error('Erro ao verificar saldo:', json.error);
            return null;
        }
    } catch (e) {
        console.error('Erro na verificação de saldo:', e);
        return null;
    }
};
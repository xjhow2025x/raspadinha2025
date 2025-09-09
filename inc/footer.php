<?php
@session_start();
// Inclui o arquivo de conexão de forma robusta
if (file_exists('./conexao.php')) {
  include('./conexao.php');
} elseif (file_exists('../conexao.php')) {
  include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
  include('../../conexao.php');
}

// Lógica para obter o saldo do usuário, se logado
$usuario = null;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    try {
        $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log do erro, mas não exiba para o usuário final
        error_log("Erro ao buscar saldo do usuário no footer: " . $e->getMessage());
    }
}
?>

<footer class="border-t border-[#00FF88] py-8 mt-12 bg-gradient-to-r from-[#0D1F0D] via-[#1a1a1a] to-[#0D2D0D]">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between border-b border-[#00FF88]/30 pb-4">
            <div class="text-gray-300 text-xs pb-4">
                <img alt="Raspou, levou!" class="max-h-10 w-auto mb-2" src="<?php echo $logoSite ;?>">
                <p class="mb-2 text-[#00FF88]">Raspadinha Sortudo! É a maior e melhor plataforma de raspadinhas do Brasil</p>
                <p class="text-gray-400">© 2025 Raspadinha Sortudo. Todos os direitos reservados.</p>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="flex flex-col gap-2">
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition" href="/">Raspadinhas</a>
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition" href="#">Carrinho</a>
                </div>
                <div class="flex flex-col gap-2">
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition" href="#">Carteira</a>
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition cursor-pointer" onclick="openDepositModal()">Depósito</a>
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition cursor-pointer" onclick="openWithdrawModal(<?php echo $usuario['saldo'] ?? 0;?>)">Saques</a>
                </div>
                <div class="flex flex-col gap-2">
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition" href="#">Termos de Uso</a>
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition" href="#">Política de Privacidade</a>
                    <a class="text-xs text-gray-300 hover:text-[#00FF88] transition" href="#">Termos de Bônus</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php
    // Este bloco de script Notiflix deve ser mantido no final do body ou em um local onde o Notiflix esteja carregado.
    // Se você já tem um sistema de mensagens global, pode remover este bloco duplicado.
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        echo "<script>
                        Notiflix.Notify.{$message['type']}('{$message['text']}');
            </script>";
        unset($_SESSION['message']);
    }
?>

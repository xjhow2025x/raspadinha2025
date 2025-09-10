<?php
@session_start();

if (file_exists('./conexao.php')) {
    include('./conexao.php');
} elseif (file_exists('../conexao.php')) {
    include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
    include('../../conexao.php');
}

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você precisa estar logado para acessar esta página!'];
    header("Location: /login");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Usuário não encontrado!'];
        header("Location: /login");
        exit;
    }

    $stmt_depositos = $pdo->prepare("SELECT SUM(valor) as total_depositado FROM depositos WHERE user_id = :user_id AND status = 'PAID'");
    $stmt_depositos->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_depositos->execute();
    $total_depositado = $stmt_depositos->fetch(PDO::FETCH_ASSOC)['total_depositado'] ?? 0;

    $stmt_saques = $pdo->prepare("SELECT SUM(valor) as total_sacado FROM saques WHERE user_id = :user_id AND status = 'PAID'");
    $stmt_saques->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_saques->execute();
    $total_sacado = $stmt_saques->fetch(PDO::FETCH_ASSOC)['total_sacado'] ?? 0;

} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar dados do usuário!'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (!password_verify($senha_atual, $usuario['senha'])) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Senha atual incorreta!'];
    } else {
        try {
            $dados = [
                'id' => $usuario_id,
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email
            ];

            if (!empty($nova_senha)) {
                if ($nova_senha === $confirmar_senha) {
                    $dados['senha'] = password_hash($nova_senha, PASSWORD_BCRYPT);
                } else {
                    $_SESSION['message'] = ['type' => 'failure', 'text' => 'As novas senhas não coincidem!'];
                    header("Location: /perfil");
                    exit;
                }
            }

            $setParts = [];
            foreach ($dados as $key => $value) {
                if ($key !== 'id') {
                    $setParts[] = "$key = :$key";
                }
            }

            $query = "UPDATE usuarios SET " . implode(', ', $setParts) . " WHERE id = :id";
            $stmt = $pdo->prepare($query);

            if ($stmt->execute($dados)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Perfil atualizado com sucesso!'];
                header("Location: /perfil");
                exit;
            } else {
                $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao atualizar perfil!'];
            }

        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao atualizar perfil: ' . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $nomeSite;?></title>
  <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>"/>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>

<?php include('../inc/header.php'); ?>

<section class="w-full py-6 relative">
  <div class="max-w-[650px] mx-auto px-4">
    <div class="relative bg-[var(--bg-color)] text-white p-8 rounded-lg shadow-lg mt-8 shadow-rox">

      <div class="absolute -top-8 left-1/2 transform -translate-x-1/2">
        <img src="/assets/img/credit-icon.svg" alt="Criado por Daanrox" class="w-16 h-16">
      </div>

      <h2 class="text-2xl text-center font-semibold mb-6">Meu Perfil</h2>

      <div class=" grid-cols-1 hidden md:grid md:grid-cols-3 gap-4 mb-8">
        <div class="bg-[var(--dark-color)] p-4 rounded-lg text-center">
          <h3 class="text-[var(--support-color)] text-sm mb-1">Saldo Atual</h3>
          <p class="text-xl font-bold">R$ <?= number_format($usuario['saldo'] ?? 0, 2, ',', '.') ?></p>
        </div>
        <div class="bg-[var(--dark-color)] p-4 rounded-lg text-center">
          <h3 class="text-[var(--support-color)] text-sm mb-1">Total Depositado</h3>
          <p class="text-xl font-bold">R$ <?= number_format($total_depositado, 2, ',', '.') ?></p>
        </div>
        <div class="bg-[var(--dark-color)] p-4 rounded-lg text-center">
          <h3 class="text-[var(--support-color)] text-sm mb-1">Total Sacado</h3>
          <p class="text-xl font-bold">R$ <?= number_format($total_sacado, 2, ',', '.') ?></p>
        </div>
      </div>

      <form method="POST" class="grid grid-cols-1 gap-6">
        <div class="relative rounded-lg border border-[var(--support-color)]">
          <i class="fa fa-user absolute top-3 left-3 text-[var(--support-color)]"></i>
          <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required
            class="pl-10 pr-4 py-2 w-full rounded text-white text-[16px] placeholder:text-[var(--support-color)]"
            placeholder="Nome completo">
        </div>

        <div class="relative rounded-lg border border-[var(--support-color)]">
          <i class="fa fa-phone absolute top-3 left-3 text-[var(--support-color)]"></i>
          <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" required
            class="pl-10 pr-4 py-2 w-full rounded text-white text-[16px] placeholder:text-[var(--support-color)]"
            placeholder="Telefone" data-mask="(00) 00000-0000">
        </div>

        <div class="relative rounded-lg border border-[var(--support-color)]">
          <i class="fa fa-envelope absolute top-3 left-3 text-[var(--support-color)]"></i>
          <input type="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required
            class="pl-10 pr-4 py-2 w-full rounded text-white text-[16px] placeholder:text-[var(--support-color)]"
            placeholder="E-mail">
        </div>

       

        <button type="button" id="toggleSenha" 
          class="flex items-center justify-end text-[var(--primary-color)] hover:text-[var(--tertiary-color)] text-sm font-medium cursor-pointer">
          <i class="fas fa-edit mr-2"></i> Alterar senha
        </button>

        <div id="camposSenha" class="hidden grid grid-cols-1 gap-6">
          <div class="relative rounded-lg border border-[var(--support-color)]">
            <i class="fa fa-lock absolute top-3 left-3 text-[var(--support-color)]"></i>
            <input type="password" name="nova_senha"
              class="pl-10 pr-4 py-2 w-full rounded text-white text-[16px] placeholder:text-[var(--support-color)]"
              placeholder="Nova senha">
          </div>

          <div class="relative rounded-lg border border-[var(--support-color)]">
            <i class="fa fa-lock absolute top-3 left-3 text-[var(--support-color)]"></i>
            <input type="password" name="confirmar_senha"
              class="pl-10 pr-4 py-2 w-full rounded text-white text-[16px] placeholder:text-[var(--support-color)]"
              placeholder="Confirmar nova senha">
          </div>
        </div>

        <div class="relative rounded-lg border border-[var(--support-color)]">
          <i class="fa fa-lock absolute top-3 left-3 text-[var(--support-color)]"></i>
          <input type="password" name="senha_atual" required
            class="pl-10 pr-4 py-2 w-full rounded text-white text-[16px] placeholder:text-[var(--support-color)]"
            placeholder="Senha atual (para confirmar alterações)">
        </div>

        <div>
          <button type="submit"
            class="btn-reflex bg-[var(--primary-color)] hover:bg-[var(--tertiary-color)]
            text-white font-semibold w-full py-3 rounded-lg transition cursor-pointer">
            Atualizar Perfil
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<?php include('../inc/footer.php'); ?>

<script>
$(document).ready(function(){
    $('#telefone').mask('(00) 00000-0000');
    
    $('#toggleSenha').click(function() {
        $('#camposSenha').toggleClass('hidden');
        
        if ($('#camposSenha').hasClass('hidden')) {
            $(this).html('<i class="fas fa-edit mr-2"></i> Alterar senha');
        } else {
            $(this).html('<i class="fas fa-times mr-2"></i> Cancelar');
        }
    });
});
</script>
</body>
</html>
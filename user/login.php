<?php
require_once __DIR__ . '/../includes/header.php';

// Lógica de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        header("Location: index.php");
        exit();
    } else {
        $erro_login = "E-mail ou senha inválidos.";
    }
}

// Lógica de Cadastro (COM CAMPO BAIRRO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $endereco = $_POST['endereco'];
    $bairro = trim($_POST['bairro']); // Novo campo
    $telefone = $_POST['telefone'];

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, endereco, bairro, telefone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senha, $endereco, $bairro, $telefone]);
        
        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        $_SESSION['usuario_nome'] = $nome;
        header("Location: index.php?cadastro_ok=1");
        exit();
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $erro_cadastro = "Este e-mail já está cadastrado.";
        } else {
            $erro_cadastro = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>
<div class="auth-container">
    <div class="form-wrapper">
        <h2>Entrar</h2>
        <?php if (isset($erro_login)): ?><p class="error"><?php echo $erro_login; ?></p><?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Senha</label><input type="password" name="senha" required></div>
            <button type="submit" name="login" class="btn">Entrar</button>
        </form>
    </div>

    <div class="form-wrapper">
        <h2>Criar Conta</h2>
        <?php if (isset($erro_cadastro)): ?><p class="error"><?php echo $erro_cadastro; ?></p><?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group"><label>Nome Completo</label><input type="text" name="nome" required></div>
            <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Senha</label><input type="password" name="senha" required></div>
            <div class="form-group"><label>Telefone</label><input type="tel" name="telefone" required></div>
            <div class="form-group"><label>Endereço (Rua, N°)</label><input type="text" name="endereco" required></div>
            <div class="form-group"><label>Bairro</label><input type="text" name="bairro" required></div>
            <button type="submit" name="cadastro" class="btn">Cadastrar</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
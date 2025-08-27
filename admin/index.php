<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // MUDANÇA 1: Agora usamos 'email' em vez de 'usuario'
    $email = $_POST['email']; 
    $senha = $_POST['senha'];

    // MUDANÇA 2: A consulta agora é na nova tabela 'admins'
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha'])) {
        // MUDANÇA 3: Sucesso! Guardamos o ID do admin E o ID da loja dele
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_loja_id'] = $admin['loja_id']; // <-- A INFORMAÇÃO MAIS IMPORTANTE

        header("Location: dashboard.php");
        exit();
    } else {
        $erro = "Email ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h2>Login do Administrador</h2>
        <?php if(isset($erro) && !empty($erro)): ?>
            <p class="error"><?php echo $erro; ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
</body>
</html>
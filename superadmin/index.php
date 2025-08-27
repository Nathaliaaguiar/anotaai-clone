<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM super_admins WHERE email = ?");
    $stmt->execute([$email]);
    $super_admin = $stmt->fetch();

    if ($super_admin && password_verify($senha, $super_admin['senha'])) {
        // Sucesso! Guarda o ID do super admin na sessão
        $_SESSION['super_admin_id'] = $super_admin['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'Email ou senha de super admin inválidos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Super Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1>Painel Master</h1>
        <p>Acesso restrito ao administrador da plataforma.</p>
        <br>
        <?php if ($erro): ?>
            <p class="error"><?php echo $erro; ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
</body>
</html>
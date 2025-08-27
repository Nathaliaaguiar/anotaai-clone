<?php
require_once 'includes/auth_check.php'; // Nosso novo segurança!

$mensagem = '';

// --- Lógica para CADASTRAR NOVA LOJA E SEU ADMIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_loja'])) {
    $nome_loja = trim($_POST['nome_loja']);
    $email_admin = trim($_POST['email_admin']);
    $senha_admin = $_POST['senha_admin'];

    // Validação simples
    if (empty($nome_loja) || empty($email_admin) || empty($senha_admin)) {
        $mensagem = '<p class="error">Todos os campos são obrigatórios.</p>';
    } elseif (strlen($senha_admin) < 6) {
        $mensagem = '<p class="error">A senha do admin deve ter pelo menos 6 caracteres.</p>';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Cria a nova loja na tabela 'lojas'
            $stmt_loja = $pdo->prepare("INSERT INTO lojas (nome) VALUES (?)");
            $stmt_loja->execute([$nome_loja]);
            $nova_loja_id = $pdo->lastInsertId();

            // 2. Cria o admin para essa nova loja na tabela 'admins'
            $senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);
            $stmt_admin = $pdo->prepare("INSERT INTO admins (loja_id, email, senha) VALUES (?, ?, ?)");
            $stmt_admin->execute([$nova_loja_id, $email_admin, $senha_hash]);

            $pdo->commit();
            $mensagem = '<p class="success">Loja e Admin cadastrados com sucesso!</p>';

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) { // Erro de email duplicado
                $mensagem = '<p class="error">Erro: O email de admin informado já está em uso.</p>';
            } else {
                $mensagem = '<p class="error">Erro ao cadastrar: ' . $e->getMessage() . '</p>';
            }
        }
    }
}

// --- Lógica para LISTAR as lojas existentes ---
$stmt_lista_lojas = $pdo->query("SELECT * FROM lojas ORDER BY nome ASC");
$lojas = $stmt_lista_lojas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-page">
    <header class="admin-header">
        <div class="container">
            <h1>Painel Master</h1>
            <nav>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container admin-main">
        <?php echo $mensagem; ?>

        <div class="superadmin-grid">
            <div class="form-wrapper">
                <h2>Cadastrar Nova Loja</h2>
                <form action="dashboard.php" method="POST">
                    <div class="form-group">
                        <label for="nome_loja">Nome da Nova Loja:</label>
                        <input type="text" id="nome_loja" name="nome_loja" required>
                    </div>
                    <div class="form-group">
                        <label for="email_admin">Email do Admin da Loja:</label>
                        <input type="email" id="email_admin" name="email_admin" required>
                    </div>
                    <div class="form-group">
                        <label for="senha_admin">Senha para o Admin da Loja:</label>
                        <input type="password" id="senha_admin" name="senha_admin" required minlength="6">
                    </div>
                    <button type="submit" name="cadastrar_loja" class="btn">Cadastrar Loja</button>
                </form>
            </div>

            <div class="lista-wrapper">
                <h2>Lojas Cadastradas</h2>
                <?php if (empty($lojas)): ?>
                    <p>Nenhuma loja cadastrada ainda.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Loja</th>
                                <th>Data de Criação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lojas as $loja): ?>
                                <tr>
                                    <td><?php echo $loja['id']; ?></td>
                                    <td><?php echo htmlspecialchars($loja['nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($loja['data_criacao'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Lógica para atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];

    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, endereco = ? WHERE id = ?");
    $stmt->execute([$nome, $email, $telefone, $endereco, $usuario_id]);
    $sucesso_update = "Perfil atualizado com sucesso!";
}

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Buscar pedidos do usuário
$stmt_pedidos = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY data DESC");
$stmt_pedidos->execute([$usuario_id]);
$pedidos = $stmt_pedidos->fetchAll();
?>

<section class="perfil">
    <h1>Meu Perfil</h1>

    <?php if(isset($_GET['pedido_sucesso'])): ?>
        <p class="success">Seu pedido foi realizado com sucesso!</p>
    <?php endif; ?>

    <div class="perfil-form">
        <h2>Meus Dados</h2>
         <?php if (isset($sucesso_update)): ?>
            <p class="success"><?php echo $sucesso_update; ?></p>
        <?php endif; ?>
        <form action="perfil.php" method="POST">
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>">
            </div>
            <div class="form-group">
                <label>Telefone:</label>
                <input type="text" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>">
            </div>
            <div class="form-group">
                <label>Endereço:</label>
                <textarea name="endereco"><?php echo htmlspecialchars($usuario['endereco']); ?></textarea>
            </div>
            <button type="submit" class="btn">Atualizar Dados</button>
        </form>
    </div>

    <div class="meus-pedidos">
        <h2>Meus Pedidos</h2>
        <?php if(empty($pedidos)): ?>
            <p>Você ainda não fez nenhum pedido.</p>
        <?php else: ?>
            <table class="tabela-pedidos">
                <thead>
                    <tr>
                        <th>Pedido ID</th>
                        <th>Data</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td>#<?php echo $pedido['id']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></td>
                            <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                            <td><span class="status-<?php echo $pedido['status']; ?>"><?php echo ucfirst($pedido['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
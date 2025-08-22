<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

// Lógica para atualizar perfil (agora incluindo o bairro)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $bairro = trim($_POST['bairro']); // Novo campo

    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, endereco = ?, bairro = ? WHERE id = ?");
    $stmt->execute([$nome, $email, $telefone, $endereco, $bairro, $usuario_id]);
    $sucesso_update = "Perfil atualizado com sucesso!";
}

// Buscar dados do usuário
$stmt_usuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch();

// Buscar pedidos do usuário
$stmt_pedidos = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY data DESC");
$stmt_pedidos->execute([$usuario_id]);
$pedidos = $stmt_pedidos->fetchAll();

// Busca TODOS os itens de TODOS os pedidos de uma vez para otimizar a consulta
$itens_por_pedido = [];
if ($pedidos) {
    $pedido_ids = array_column($pedidos, 'id');
    if (!empty($pedido_ids)) {
        $ids_string = implode(',', $pedido_ids);
        $stmt_itens = $pdo->query("
            SELECT pi.*, p.nome as produto_nome 
            FROM pedido_itens pi 
            JOIN produtos p ON pi.produto_id = p.id 
            WHERE pi.pedido_id IN ($ids_string)
        ");
        $todos_itens = $stmt_itens->fetchAll();
        // Agrupa os itens pelo ID do pedido para fácil acesso na hora de exibir
        foreach ($todos_itens as $item) {
            $itens_por_pedido[$item['pedido_id']][] = $item;
        }
    }
}
?>

<section class="perfil">
    <h1>Meu Perfil</h1>

    <?php if(isset($_GET['pedido_sucesso'])): ?>
        <p class="success">Seu pedido foi realizado com sucesso!</p>
    <?php endif; ?>

    <div class="perfil-container">
        <div class="perfil-form form-wrapper">
            <h2>Meus Dados</h2>
            <?php if (isset($sucesso_update)): ?>
                <p class="success"><?php echo $sucesso_update; ?></p>
            <?php endif; ?>
            <form action="perfil.php" method="POST">
                <div class="form-group"><label>Nome:</label><input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>"></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>"></div>
                <div class="form-group"><label>Telefone:</label><input type="text" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>"></div>
                <div class="form-group"><label>Endereço (Rua, N°):</label><input type="text" name="endereco" value="<?php echo htmlspecialchars($usuario['endereco']); ?>"></div>
                <div class="form-group"><label>Bairro:</label><input type="text" name="bairro" value="<?php echo htmlspecialchars($usuario['bairro']); ?>"></div>
                <button type="submit" class="btn">Atualizar Dados</button>
            </form>
        </div>

        <div class="meus-pedidos">
            <h2>Meus Pedidos</h2>
            <?php if(empty($pedidos)): ?>
                <p>Você ainda não fez nenhum pedido.</p>
            <?php else: ?>
                <div class="lista-pedidos">
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="pedido-card">
                            <div class="pedido-header">
                                <div><strong>Pedido #<?php echo $pedido['id']; ?></strong><br><small><?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></small></div>
                                <div><span class="status-<?php echo str_replace(' ', '_', $pedido['status']); ?>"><?php echo ucwords(str_replace('_', ' ', $pedido['status'])); ?></span></div>
                            </div>
                            <div class="pedido-body">
                                <ul>
                                    <?php if (isset($itens_por_pedido[$pedido['id']])): ?>
                                        <?php foreach ($itens_por_pedido[$pedido['id']] as $item): ?>
                                            <li>
                                                <span><?php echo $item['quantidade']; ?>x <?php echo htmlspecialchars($item['produto_nome']); ?></span>
                                                <span>R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></span>
                                                <?php if(!empty($item['observacao'])): ?>
                                                    <small class="observacao-item"><em><?php echo htmlspecialchars($item['observacao']); ?></em></small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="pedido-footer">
                                <div><small>Taxa de Entrega: R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></small></div>
                                <strong>Total: R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
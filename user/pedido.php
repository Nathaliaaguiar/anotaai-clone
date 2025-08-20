<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['carrinho'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $carrinho = $_SESSION['carrinho'];
    $total = 0;

    $produto_ids = array_column($carrinho, 'produto_id');
    $ids_string = implode(',', array_unique($produto_ids));
    
    $produtos_db = [];
    if ($ids_string) {
        $stmt = $pdo->query("SELECT id, preco FROM produtos WHERE id IN ($ids_string)");
        $produtos_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($produtos_lista as $produto) {
            $produtos_db[$produto['id']] = $produto;
        }
    }

    foreach ($carrinho as $item) {
        if (isset($produtos_db[$item['produto_id']])) {
            $total += $produtos_db[$item['produto_id']]['preco'] * $item['quantidade'];
        }
    }

    try {
        $pdo->beginTransaction();
        
        $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)");
        $stmt_pedido->execute([$usuario_id, $total]);
        $pedido_id = $pdo->lastInsertId();

        // Agora incluímos a observação
        $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco, observacao) VALUES (?, ?, ?, ?, ?)");
        foreach ($carrinho as $item) {
            if (isset($produtos_db[$item['produto_id']])) {
                $stmt_item->execute([
                    $pedido_id, 
                    $item['produto_id'], 
                    $item['quantidade'], 
                    $produtos_db[$item['produto_id']]['preco'],
                    $item['observacao']
                ]);
            }
        }
        
        $pdo->commit();
        unset($_SESSION['carrinho']);
        header('Location: perfil.php?pedido_sucesso=1');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $erro_pedido = "Ocorreu um erro ao processar seu pedido: " . $e->getMessage();
    }
}
?>

<section class="finalizar-pedido">
    <h1>Finalizar Pedido</h1>
    <?php if (isset($erro_pedido)): ?>
        <p class="error"><?php echo $erro_pedido; ?></p>
    <?php endif; ?>

    <?php if (empty($_SESSION['carrinho'])): ?>
        <p>Seu carrinho está vazio. Adicione itens antes de finalizar.</p>
        <a href="index.php" class="btn">Ver Cardápio</a>
    <?php else: ?>
        <h2>Resumo do Pedido</h2>
        <p>Confirme os detalhes e finalize sua compra. O endereço de entrega será o cadastrado em seu perfil.</p>
        <form action="pedido.php" method="POST">
            <button type="submit" class="btn">Confirmar Pedido</button>
        </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
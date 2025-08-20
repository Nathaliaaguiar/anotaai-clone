<?php
require_once __DIR__ . '/../includes/header.php';

// Proteger a página: usuário deve estar logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Lógica para finalizar o pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['carrinho']) && !empty($_SESSION['carrinho'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $carrinho = $_SESSION['carrinho'];
    $total = 0;
    
    // Calcula o total novamente para segurança
    $ids = implode(',', array_keys($carrinho));
   $stmt = $pdo->query("SELECT id, preco FROM produtos WHERE id IN ($ids)");
$produtos_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Reorganiza o array para ter o ID do produto como chave
$produtos = [];
foreach ($produtos_lista as $produto) {
    $produtos[$produto['id']] = $produto;
}

    foreach ($carrinho as $id => $quantidade) {
        if(isset($produtos[$id])) {
            $total += $produtos[$id]['preco'] * $quantidade;
        }
    }

    try {
        $pdo->beginTransaction();
        
        // 1. Inserir na tabela `pedidos`
        $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)");
        $stmt_pedido->execute([$usuario_id, $total]);
        $pedido_id = $pdo->lastInsertId();

        // 2. Inserir na tabela `pedido_itens`
        $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco) VALUES (?, ?, ?, ?)");
        foreach ($carrinho as $id => $quantidade) {
            if(isset($produtos[$id])) {
                $stmt_item->execute([$pedido_id, $id, $quantidade, $produtos[$id]['preco']]);
            }
        }
        
        $pdo->commit();

        // Limpar o carrinho
        unset($_SESSION['carrinho']);

        // Redirecionar para a página de perfil para ver o pedido
        header('Location: perfil.php?pedido_sucesso=1');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $erro_pedido = "Ocorreu um erro ao processar seu pedido. Tente novamente.";
    }
}
?>

<section class="finalizar-pedido">
    <h1>Finalizar Pedido</h1>
    <?php if (isset($erro_pedido)): ?>
        <p class="error"><?php echo $erro_pedido; ?></p>
    <?php endif; ?>

    <?php if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])): ?>
        <p>Seu carrinho está vazio. Adicione itens antes de finalizar.</p>
        <a href="index.php" class="btn">Ver Cardápio</a>
    <?php else: ?>
        <p>Confirme os detalhes e finalize sua compra.</p>
        <form action="pedido.php" method="POST">
            <button type="submit" class="btn">Confirmar Pedido</button>
        </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/header.php';

$carrinho = isset($_SESSION['carrinho']) ? $_SESSION['carrinho'] : [];
$itens_carrinho = [];
$total = 0;

if (!empty($carrinho)) {
    $ids = implode(',', array_keys($carrinho));
   $stmt = $pdo->query("SELECT * FROM produtos WHERE id IN ($ids)");
$produtos_lista = $stmt->fetchAll(PDO::FETCH_ASSOC); // Usamos FETCH_ASSOC que é universal

// Criamos o array com a chave sendo o ID do produto manualmente
$produtos = [];
foreach ($produtos_lista as $produto) {
    $produtos[$produto['id']] = $produto;
}
    foreach ($carrinho as $id => $quantidade) {
        if (isset($produtos[$id])) {
            $produto = $produtos[$id];
            $subtotal = $produto['preco'] * $quantidade;
            $total += $subtotal;
            $itens_carrinho[] = [
                'id' => $id,
                'nome' => $produto['nome'],
                'preco' => $produto['preco'],
                'quantidade' => $quantidade,
                'subtotal' => $subtotal
            ];
        }
    }
}

// Lógica para remover item
if (isset($_GET['remover'])) {
    $id_remover = $_GET['remover'];
    unset($_SESSION['carrinho'][$id_remover]);
    header('Location: carrinho.php');
    exit;
}
?>

<section class="carrinho">
    <h1>Meu Carrinho</h1>
    <?php if (empty($itens_carrinho)): ?>
        <p>Seu carrinho está vazio.</p>
        <a href="index.php" class="btn">Voltar ao Cardápio</a>
    <?php else: ?>
        <table class="tabela-carrinho">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens_carrinho as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                        <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                        <td><?php echo $item['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                        <td><a href="carrinho.php?remover=<?php echo $item['id']; ?>" class="btn-remover">Remover</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="carrinho-total">
            <h3>Total: R$ <?php echo number_format($total, 2, ',', '.'); ?></h3>
            <a href="pedido.php" class="btn">Finalizar Pedido</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
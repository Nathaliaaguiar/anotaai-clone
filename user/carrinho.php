<?php
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['remover'])) {
    unset($_SESSION['carrinho'][$_GET['remover']]);
    header('Location: carrinho.php');
    exit;
}

$carrinho = $_SESSION['carrinho'] ?? [];
$itens_carrinho = [];
$total_pedido = 0;

if (!empty($carrinho)) {
    $produto_ids = array_column($carrinho, 'produto_id');
    if (!empty($produto_ids)) {
        $ids_string = implode(',', array_unique($produto_ids));
        $stmt_produtos = $pdo->query("SELECT * FROM produtos WHERE id IN ($ids_string)");
        $produtos_db_lista = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
        $produtos_db = [];
        foreach($produtos_db_lista as $p) { $produtos_db[$p['id']] = $p; }

        foreach ($carrinho as $item_id => $item) {
            if (isset($produtos_db[$item['produto_id']])) {
                $produto_info = $produtos_db[$item['produto_id']];
                $preco_unitario = $produto_info['preco'] + ($item['opcao_preco_adicional'] ?? 0);
                $subtotal = $preco_unitario * $item['quantidade'];
                $total_pedido += $subtotal;
                
                $itens_carrinho[] = [
                    'item_id' => $item_id, 'nome' => $produto_info['nome'], 'preco_unitario' => $preco_unitario,
                    'quantidade' => $item['quantidade'], 'observacao' => $item['observacao'], 'opcao_nome' => $item['opcao_nome'] ?? null, 'subtotal' => $subtotal
                ];
            }
        }
    }
}
?>
<section class="carrinho">
    <h1>Meu Carrinho</h1>
    <?php if (empty($itens_carrinho)): ?>
        <p>Seu carrinho está vazio.</p> <a href="index.php" class="btn">Voltar ao Cardápio</a>
    <?php else: ?>
        <table class="tabela-carrinho">
            <thead><tr><th>Produto</th><th>Preço Unit.</th><th>Qtd.</th><th>Subtotal</th><th>Ação</th></tr></thead>
            <tbody>
                <?php foreach ($itens_carrinho as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['nome']); ?></strong>
                            <?php if ($item['opcao_nome']): ?><br><small><em>Opção: <?php echo htmlspecialchars($item['opcao_nome']); ?></em></small><?php endif; ?>
                            <?php if ($item['observacao']): ?><br><small><em>Obs: <?php echo htmlspecialchars($item['observacao']); ?></em></small><?php endif; ?>
                        </td>
                        <td>R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                        <td><?php echo $item['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                        <td><a href="carrinho.php?remover=<?php echo $item['item_id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza?');">Remover</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="carrinho-total">
            <h3>Total: R$ <?php echo number_format($total_pedido, 2, ',', '.'); ?></h3>
            <a href="pedido.php" class="btn">Finalizar Pedido</a>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
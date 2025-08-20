<?php
require_once __DIR__ . '/../includes/header.php';

// Lógica para remover item (usando a chave única do item)
if (isset($_GET['remover'])) {
    $item_id_remover = $_GET['remover'];
    if (isset($_SESSION['carrinho'][$item_id_remover])) {
        unset($_SESSION['carrinho'][$item_id_remover]);
    }
    header('Location: carrinho.php');
    exit;
}

// Inicializa as variáveis
$carrinho = $_SESSION['carrinho'] ?? [];
$itens_carrinho = [];
$total = 0;

// Bloco de verificação para limpar carrinhos no formato antigo
if (!empty($carrinho) && !is_array(reset($carrinho))) {
    unset($_SESSION['carrinho']); // Limpa o carrinho antigo da sessão
    $carrinho = [];               // Esvazia a variável local para esta página
    echo '<p class="success">Seu carrinho antigo foi limpo para usar a nova versão. Por favor, adicione os itens novamente.</p>';
}

// Continua o processamento se o carrinho não estiver vazio (e já verificado)
if (!empty($carrinho)) {
    // Pega todos os IDs de produtos para uma única consulta no banco
    $produto_ids = array_column($carrinho, 'produto_id');

    // Garante que só executa a consulta se houver IDs de produto no carrinho
    if (!empty($produto_ids)) {
        $ids_string = implode(',', array_unique($produto_ids));

        $stmt = $pdo->query("SELECT * FROM produtos WHERE id IN ($ids_string)");
        // Busca os produtos como uma lista (compatível com todas as versões do PHP)
        $produtos_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiza a lista de produtos em um array onde a chave é o ID do produto
        $produtos_db = [];
        foreach ($produtos_lista as $produto) {
            $produtos_db[$produto['id']] = $produto;
        }

        // Agora, monta a exibição do carrinho
        foreach ($carrinho as $item_id => $item) {
            $produto_id = $item['produto_id'];
            if (isset($produtos_db[$produto_id])) {
                $produto_info = $produtos_db[$produto_id];
                $subtotal = $produto_info['preco'] * $item['quantidade'];
                $total += $subtotal;
                
                $itens_carrinho[] = [
                    'item_id'    => $item_id, // Chave única do item no carrinho
                    'nome'       => $produto_info['nome'],
                    'preco'      => $produto_info['preco'],
                    'quantidade' => $item['quantidade'],
                    'observacao' => $item['observacao'],
                    'subtotal'   => $subtotal
                ];
            }
        }
    }
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
                    <th>Observação</th>
                    <th>Preço Unit.</th>
                    <th>Qtd.</th>
                    <th>Subtotal</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens_carrinho as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                        <td><?php echo htmlspecialchars($item['observacao'] ?: 'Nenhuma'); ?></td>
                        <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                        <td><?php echo $item['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                        <td><a href="carrinho.php?remover=<?php echo $item['item_id']; ?>" class="btn-remover">Remover</a></td>
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
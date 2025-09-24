<?php
// SUA LÓGICA ORIGINAL E FUNCIONAL VEM PRIMEIRO (ESSA É A CHAVE)
require_once __DIR__ . '/../includes/header.php'; // O header é chamado depois, como no seu original

// --- Lógica para Adicionar ao Carrinho ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_carrinho_modal'])) {
    // ADICIONADO: Pega o ID da loja do formulário para o redirecionamento
    $loja_id_redirect = $_POST['loja_id'] ?? 1;

    // ADICIONADO: Limpa o carrinho se o cliente estiver trocando de loja
    if (isset($_SESSION['carrinho_loja_id']) && $_SESSION['carrinho_loja_id'] != $loja_id_redirect) {
        $_SESSION['carrinho'] = [];
    }
    $_SESSION['carrinho_loja_id'] = $loja_id_redirect;

    
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'] ?? 1;
    $observacao = $_POST['observacao'] ?? '';
    $opcao_id = $_POST['opcao_id'] ?? null;
    $opcao_nome = null;
    $opcao_preco_adicional = 0;
    if ($opcao_id) {
        $stmt_op = $pdo->prepare("SELECT nome_opcao, preco_adicional FROM produto_opcoes WHERE id = ?");
        $stmt_op->execute([$opcao_id]);
        $opcao_info = $stmt_op->fetch();
        if ($opcao_info) {
            $opcao_nome = $opcao_info['nome_opcao'];
            $opcao_preco_adicional = $opcao_info['preco_adicional'];
        }
    }
    $item_carrinho_id = uniqid('item_'); 
    $_SESSION['carrinho'][$item_carrinho_id] = [
        'produto_id' => $produto_id, 'quantidade' => $quantidade, 'observacao' => trim($observacao),
        'opcao_id' => $opcao_id, 'opcao_nome' => $opcao_nome, 'opcao_preco_adicional' => $opcao_preco_adicional
    ];
    // MODIFICADO: Redireciona para a URL da loja correta
    header('Location: index.php?id=' . $loja_id_redirect . '&item_adicionado=true');
    exit();
}

// ADICIONADO: Identifica a loja que está sendo visualizada
$loja_id = $_GET['id'] ?? 1; // Padrão para loja 1 se nenhum ID for passado
$_SESSION['loja_id_visitada'] = $loja_id;

// MODIFICADO: Busca categorias apenas da loja selecionada
$stmt_categorias = $pdo->prepare("SELECT * FROM categorias WHERE loja_id = ? ORDER BY nome ASC");
$stmt_categorias->execute([$loja_id]);
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

$categoria_selecionada_id = $_GET['categoria_id'] ?? 'todos';

// MODIFICADO: Busca produtos apenas da loja selecionada
$sql_produtos = "SELECT p.*, c.nome as nome_categoria FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.ativo = 1 AND p.loja_id = ?";
$params = [$loja_id];
if ($categoria_selecionada_id !== 'todos') {
    $sql_produtos .= " AND p.categoria_id = ?";
    $params[] = $categoria_selecionada_id;
}
$stmt_produtos = $pdo->prepare($sql_produtos);
$stmt_produtos->execute($params);
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

// MODIFICADO: Busca opções de produtos apenas da loja selecionada
$stmt_opcoes = $pdo->prepare("SELECT po.* FROM produto_opcoes po JOIN produtos p ON po.produto_id = p.id WHERE p.loja_id = ?");
$stmt_opcoes->execute([$loja_id]);
$opcoes_raw = $stmt_opcoes->fetchAll(PDO::FETCH_ASSOC);
$opcoes_produtos = [];
foreach ($opcoes_raw as $opcao) {
    $opcoes_produtos[$opcao['produto_id']][] = $opcao;
}
?>

<section class="cardapio">
    <div class="filtros-categoria">
        <a href="index.php?id=<?php echo $loja_id; ?>" class="<?php echo $categoria_selecionada_id == 'todos' ? 'active' : ''; ?>">Todos</a>
        <?php foreach ($categorias as $categoria): ?>
            <a href="index.php?id=<?php echo $loja_id; ?>&categoria_id=<?php echo $categoria['id']; ?>" class="<?php echo $categoria_selecionada_id == $categoria['id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($categoria['nome']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="produtos-grid">
        <?php foreach ($produtos as $produto): ?>
            <div class="produto-card">
                <img src="../img/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                <p class="produto-descricao"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                <p class="produto-preco">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                <button class="btn btn-abrir-modal" data-id="<?php echo $produto['id']; ?>" data-categoria-id="<?php echo $produto['categoria_id']; ?>">Adicionar</button>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<div id="modal-observacao" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2 id="modal-produto-nome"></h2>
        <form action="index.php" method="POST">
             <input type="hidden" name="loja_id" value="<?php echo $loja_id; ?>">
            <input type="hidden" name="produto_id" id="modal-produto-id">
            <input type="hidden" name="categoria_id_produto" id="modal-categoria-id">
            <input type="hidden" name="opcao_id" id="modal-opcao-id">
            <div id="modal-opcoes-container"></div>
            <div class="form-group"><label for="quantidade">Quantidade:</label><input type="number" id="quantidade" name="quantidade" value="1" min="1"></div>
            <div class="form-group"><label for="observacao">Observações:</label><textarea name="observacao" id="observacao" rows="3" placeholder="Ex: Tirar a cebola..."></textarea></div>
            <div class="modal-total-preco">Total: <span id="modal-preco-total">R$ 0,00</span></div>
            <button type="submit" name="add_carrinho_modal" class="btn">Adicionar ao Carrinho</button>
        </form>
    </div>
</div>
<script>
    const produtosData = <?php echo json_encode(array_map(function($p) { return ['id' => $p['id'], 'nome' => $p['nome'], 'preco' => $p['preco']]; }, $produtos)); ?>;
    const opcoesData = <?php echo json_encode($opcoes_produtos); ?>;
</script>
<script src="../js/script.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
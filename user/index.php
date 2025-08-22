<?php
require_once __DIR__ . '/../includes/header.php';

// --- Lógica do Carrinho (inalterada) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_carrinho_modal'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'] ?? 1;
    $observacao = $_POST['observacao'] ?? '';
    $item_carrinho_id = uniqid('item_'); 
    $_SESSION['carrinho'][$item_carrinho_id] = [
        'produto_id' => $produto_id,
        'quantidade' => $quantidade,
        'observacao' => trim($observacao)
    ];
    header("Location: index.php?item_adicionado=1#categoria-" . $_POST['categoria_id_produto']);
    exit();
}

// --- Busca os produtos e categorias ---
$stmt_categorias = $pdo->query("
    SELECT c.* FROM categorias c
    JOIN produtos p ON c.id = p.categoria_id
    WHERE p.ativo = 1
    GROUP BY c.id
    ORDER BY c.nome ASC
");
$categorias = $stmt_categorias->fetchAll();

$stmt_produtos = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 AND categoria_id IS NOT NULL ORDER BY nome ASC");
$produtos = $stmt_produtos->fetchAll();

$produtos_por_categoria = [];
foreach ($produtos as $produto) {
    $produtos_por_categoria[$produto['categoria_id']][] = $produto;
}
?>

<section class="catalogo">
    <h1>Nosso Cardápio</h1>
    <?php if(isset($_GET['item_adicionado'])): ?>
        <p class="success">Item adicionado ao carrinho com sucesso!</p>
    <?php endif; ?>

    <?php if (empty($categorias)): ?>
        <p>Nenhum produto encontrado no momento.</p>
    <?php else: ?>
        <nav class="filtro-categorias">
            <ul>
                <li><a href="#catalogo-completo" class="btn-filtro">Ver Tudo</a></li>
                <?php foreach ($categorias as $categoria): ?>
                    <li><a href="#categoria-<?php echo $categoria['id']; ?>" class="btn-filtro"><?php echo htmlspecialchars($categoria['nome']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <div id="catalogo-completo">
            <?php foreach ($categorias as $categoria): ?>
                <div class="categoria-secao" id="categoria-<?php echo $categoria['id']; ?>">
                    <h2 class="categoria-titulo"><?php echo htmlspecialchars($categoria['nome']); ?></h2>
                    <div class="produtos-grid">
                        <?php if (isset($produtos_por_categoria[$categoria['id']])): ?>
                            <?php foreach ($produtos_por_categoria[$categoria['id']] as $produto): ?>
                                <div class="produto-card">
                                    <img src="/anotaai-clone/img/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                    <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                                    <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                    <span class="preco">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                                    <button class="btn btn-abrir-modal" 
                                            data-id="<?php echo $produto['id']; ?>" 
                                            data-nome="<?php echo htmlspecialchars($produto['nome']); ?>"
                                            data-categoria-id="<?php echo $categoria['id']; ?>">
                                        Adicionar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div id="modal-observacao" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2 id="modal-produto-nome"></h2>
        <form action="index.php" method="POST">
            <input type="hidden" name="produto_id" id="modal-produto-id">
            <input type="hidden" name="categoria_id_produto" id="modal-categoria-id">
            <div class="form-group"><label for="quantidade">Quantidade:</label><input type="number" id="quantidade" name="quantidade" value="1" min="1"></div>
            <div class="form-group"><label for="observacao">Observações (opcional):</label><textarea name="observacao" id="observacao" rows="3" placeholder="Ex: Tirar a cebola..."></textarea></div>
            <button type="submit" name="add_carrinho_modal" class="btn">Adicionar ao Carrinho</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
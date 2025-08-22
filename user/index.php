<?php
require_once __DIR__ . '/../includes/header.php';

// --- Lógica para Adicionar ao Carrinho ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_carrinho_modal'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'] ?? 1;
    $observacao = $_POST['observacao'] ?? '';
    $opcao_id = $_POST['opcao_id'] ?? null;
    
    // Busca o nome e preço adicional da opção para salvar na sessão de forma segura
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
        'produto_id' => $produto_id,
        'quantidade' => $quantidade,
        'observacao' => trim($observacao),
        'opcao_id' => $opcao_id,
        'opcao_nome' => $opcao_nome,
        'opcao_preco_adicional' => $opcao_preco_adicional
    ];
    // Redireciona de volta para a categoria correta após adicionar
    header("Location: index.php?item_adicionado=1#categoria-" . $_POST['categoria_id_produto']);
    exit();
}

// --- Busca de Dados ---
$categorias = $pdo->query("SELECT c.* FROM categorias c JOIN produtos p ON c.id = p.categoria_id WHERE p.ativo = 1 GROUP BY c.id ORDER BY c.nome ASC")->fetchAll();
$todos_produtos = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 AND categoria_id IS NOT NULL ORDER BY nome ASC")->fetchAll();
$todas_opcoes = $pdo->query("SELECT * FROM produto_opcoes")->fetchAll();

// Organiza os dados para fácil acesso
$produtos_por_categoria = [];
foreach ($todos_produtos as $produto) { $produtos_por_categoria[$produto['categoria_id']][] = $produto; }
$opcoes_por_produto = [];
foreach ($todas_opcoes as $opcao) { $opcoes_por_produto[$opcao['produto_id']][] = $opcao; }
?>

<section class="catalogo">
    <h1>Nosso Cardápio</h1>
    <?php if(isset($_GET['item_adicionado'])): ?><p class="success">Item adicionado ao carrinho com sucesso!</p><?php endif; ?>
    
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
                                    <span class="preco">
                                        <?php echo isset($opcoes_por_produto[$produto['id']]) ? 'A partir de ' : ''; ?>
                                        R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                    </span>
                                    <button class="btn btn-abrir-modal" 
                                            data-id="<?php echo $produto['id']; ?>" 
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
    const produtosData = <?php echo json_encode(array_map(function($p) { return ['id' => $p['id'], 'nome' => $p['nome'], 'preco' => $p['preco'], 'categoria_id' => $p['categoria_id']]; }, $todos_produtos)); ?>;
    const opcoesData = <?php echo json_encode($opcoes_por_produto); ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/header.php';

// Lógica para adicionar ao carrinho (agora vindo do modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_carrinho_modal'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'] ?? 1;
    $observacao = $_POST['observacao'] ?? '';
    
    // Geramos uma chave única para este item no carrinho, permitindo itens iguais com obs. diferentes
    $item_carrinho_id = uniqid('item_'); 

    $_SESSION['carrinho'][$item_carrinho_id] = [
        'produto_id' => $produto_id,
        'quantidade' => $quantidade,
        'observacao' => trim($observacao)
    ];

    header("Location: index.php?item_adicionado=1");
    exit();
}

// Buscar produtos ativos
$stmt = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
$produtos = $stmt->fetchAll();
?>

<section class="catalogo">
    <h1>Nosso Cardápio</h1>
    <?php if(isset($_GET['item_adicionado'])): ?>
        <p class="success">Item adicionado ao carrinho com sucesso!</p>
    <?php endif; ?>

    <div class="produtos-grid">
        <?php foreach ($produtos as $produto): ?>
            <div class="produto-card">
                <img src="/anotaai-clone/img/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                <span class="preco">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                
                <button class="btn btn-abrir-modal" 
                        data-id="<?php echo $produto['id']; ?>" 
                        data-nome="<?php echo htmlspecialchars($produto['nome']); ?>">
                    Adicionar
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div id="modal-observacao" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2 id="modal-produto-nome"></h2>
        <form action="index.php" method="POST">
            <input type="hidden" name="produto_id" id="modal-produto-id">
            
            <div class="form-group">
                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" value="1" min="1" class="form-control">
            </div>

            <div class="form-group">
                <label for="observacao">Observações (opcional):</label>
                <textarea name="observacao" id="observacao" rows="3" placeholder="Ex: Tirar a cebola, ponto da carne, etc."></textarea>
            </div>

            <button type="submit" name="add_carrinho_modal" class="btn">Adicionar ao Carrinho</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
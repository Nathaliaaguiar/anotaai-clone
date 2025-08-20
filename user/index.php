<?php
require_once __DIR__ . '/../includes/header.php';

// Lógica para adicionar ao carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_carrinho'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = 1; // Pode ser alterado para permitir que o usuário escolha

    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id] += $quantidade;
    } else {
        $_SESSION['carrinho'][$produto_id] = $quantidade;
    }
    // Redireciona para evitar reenvio do formulário
    header("Location: index.php");
    exit();
}

// Buscar produtos ativos
$stmt = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
$produtos = $stmt->fetchAll();
?>

<section class="catalogo">
    <h1>Nosso Cardápio</h1>
    <div class="produtos-grid">
        <?php foreach ($produtos as $produto): ?>
            <div class="produto-card">
                <img src="/anotaai-clone/img/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                <span class="preco">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                <form action="index.php" method="POST">
                    <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                    <button type="submit" name="add_carrinho" class="btn">Adicionar</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
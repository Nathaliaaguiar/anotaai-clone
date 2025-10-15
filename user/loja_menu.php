<?php
// [CAMINHO CORRIGIDO]
require_once __DIR__ . '/../includes/header.php';

// Pega o ID da loja da URL
$loja_id = filter_input(INPUT_GET, 'loja_id', FILTER_VALIDATE_INT);
if (!$loja_id) {
    header('Location: index.php');
    exit;
}

// Lógica para Adicionar ao Carrinho...
// (A lógica permanece a mesma da resposta anterior)

// Busca as informações da loja, categorias e produtos
try {
    $stmt_loja = $pdo->prepare("SELECT nome FROM lojas WHERE id = ?");
    $stmt_loja->execute([$loja_id]);
    $loja = $stmt_loja->fetch(PDO::FETCH_ASSOC);
    if (!$loja) { throw new Exception("Loja não encontrada."); }

    $stmt_cat = $pdo->prepare("SELECT id, nome FROM categorias WHERE loja_id = ? ORDER BY ordem, nome");
    $stmt_cat->execute([$loja_id]);
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $stmt_prod = $pdo->prepare("SELECT p.*, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.loja_id = ? AND p.ativo = 1");
    $stmt_prod->execute([$loja_id]);
    $produtos_lista = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

    $produtos_por_categoria = [];
    foreach ($produtos_lista as $produto) {
        $produtos_por_categoria[$produto['categoria_nome']][] = $produto;
    }
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>
<main class="menu-container">
    <h1>Cardápio de <?= htmlspecialchars($loja['nome']) ?></h1>
    <a href="index.php">&larr; Voltar para a lista de lojas</a>

    <?php foreach ($categorias as $categoria): ?>
        <?php if (isset($produtos_por_categoria[$categoria['nome']])): ?>
            <section id="cat-<?= $categoria['id'] ?>" class="categoria-secao">
                <h2 class="categoria-titulo"><?= htmlspecialchars($categoria['nome']) ?></h2>
                <?php foreach ($produtos_por_categoria[$categoria['nome']] as $produto): ?>
                    <div class="produto-item" onclick='abrirModal(<?= json_encode($produto) ?>)'>
                        <div class="produto-info">
                            <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                            <p><?= htmlspecialchars($produto['descricao']) ?></p>
                        </div>
                        <div class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>
</main>

<!-- O HTML do Modal e o JavaScript permanecem os mesmos da resposta anterior -->

<?php
// [CAMINHO CORRIGIDO]
require_once __DIR__ . '/../includes/footer.php';
?>

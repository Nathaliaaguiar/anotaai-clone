<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

if (!isset($_GET['produto_id'])) {
    header('Location: produtos.php');
    exit;
}
$produto_id = $_GET['produto_id'];

// --- Busca o nome do produto para exibir no título ---
$stmt_produto = $pdo->prepare("SELECT nome FROM produtos WHERE id = ?");
$stmt_produto->execute([$produto_id]);
$produto = $stmt_produto->fetch();
if (!$produto) {
    header('Location: produtos.php');
    exit;
}
$nome_produto = $produto['nome'];

// --- LÓGICA DE ADICIONAR/EDITAR OPÇÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_opcao'])) {
    $nome_opcao = $_POST['nome_opcao'];
    $preco_adicional = $_POST['preco_adicional'];
    $id = $_POST['id'] ?? null;

    if ($id) { // Edição
        $stmt = $pdo->prepare("UPDATE produto_opcoes SET nome_opcao = ?, preco_adicional = ? WHERE id = ? AND produto_id = ?");
        $stmt->execute([$nome_opcao, $preco_adicional, $id, $produto_id]);
    } else { // Adição
        $stmt = $pdo->prepare("INSERT INTO produto_opcoes (produto_id, nome_opcao, preco_adicional) VALUES (?, ?, ?)");
        $stmt->execute([$produto_id, $nome_opcao, $preco_adicional]);
    }
    header('Location: produto_opcoes.php?produto_id=' . $produto_id);
    exit;
}

// --- LÓGICA DE DELETAR OPÇÃO ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM produto_opcoes WHERE id = ? AND produto_id = ?");
    $stmt->execute([$id, $produto_id]);
    header('Location: produto_opcoes.php?produto_id=' . $produto_id);
    exit;
}

// Buscar opção para editar
$opcao_edicao = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM produto_opcoes WHERE id = ? AND produto_id = ?");
    $stmt->execute([$id, $produto_id]);
    $opcao_edicao = $stmt->fetch();
}

// Listar todas as opções do produto
$opcoes = $pdo->prepare("SELECT * FROM produto_opcoes WHERE produto_id = ? ORDER BY nome_opcao ASC");
$opcoes->execute([$produto_id]);
$lista_opcoes = $opcoes->fetchAll();
?>

<section class="admin-crud">
    <a href="produtos.php" class="btn-voltar" style="margin-bottom: 1.5rem; display: inline-block;">&larr; Voltar para Produtos</a>
    <h1>Opções para "<?php echo htmlspecialchars($nome_produto); ?>"</h1>

    <div class="form-wrapper">
        <h2><?php echo $opcao_edicao ? 'Editar Opção' : 'Adicionar Nova Opção'; ?></h2>
        <form action="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo $opcao_edicao['id'] ?? ''; ?>">
            <div class="form-group">
                <label for="nome_opcao">Nome da Opção:</label>
                <input type="text" id="nome_opcao" name="nome_opcao" value="<?php echo htmlspecialchars($opcao_edicao['nome_opcao'] ?? ''); ?>" required placeholder="Ex: Adicional de Bacon">
            </div>
            <div class="form-group">
                <label for="preco_adicional">Preço Adicional (R$):</label>
                <input type="number" step="0.01" id="preco_adicional" name="preco_adicional" value="<?php echo htmlspecialchars($opcao_edicao['preco_adicional'] ?? '0.00'); ?>" required>
            </div>
            <button type="submit" class="btn"><?php echo $opcao_edicao ? 'Atualizar Opção' : 'Adicionar Opção'; ?></button>
            <?php if ($opcao_edicao): ?>
                <a href="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>" class="btn" style="background-color:#777;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Lista de Opções</h2>
    <table class="tabela-admin">
        <thead>
            <tr>
                <th>Nome da Opção</th>
                <th>Preço Adicional</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lista_opcoes as $opcao): ?>
                <tr>
                    <td><?php echo htmlspecialchars($opcao['nome_opcao']); ?></td>
                    <td>R$ <?php echo number_format($opcao['preco_adicional'], 2, ',', '.'); ?></td>
                    <td>
                        <a href="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>&edit=<?php echo $opcao['id']; ?>" class="btn-edit">Editar</a>
                        <a href="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>&delete=<?php echo $opcao['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza?');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php'; 

if (!isset($_GET['produto_id'])) {
    header('Location: produtos.php');
    exit;
}
$produto_id = $_GET['produto_id'];
$loja_id = $_SESSION['admin_loja_id'];

// Busca nome do produto (Segurança: Verifica loja_id também)
$stmt_produto = $pdo->prepare("SELECT nome FROM produtos WHERE id = ? AND loja_id = ?");
$stmt_produto->execute([$produto_id, $loja_id]);
$produto = $stmt_produto->fetch();

if (!$produto) {
    echo "<script>alert('Produto não encontrado!'); window.location='produtos.php';</script>";
    exit;
}
$nome_produto = $produto['nome'];

// CRUD Opções
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_opcao = $_POST['nome_opcao'];
    $preco_adicional = $_POST['preco_adicional'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE produto_opcoes SET nome_opcao=?, preco_adicional=? WHERE id=? AND produto_id=?");
        $stmt->execute([$nome_opcao, $preco_adicional, $id, $produto_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO produto_opcoes (produto_id, nome_opcao, preco_adicional) VALUES (?, ?, ?)");
        $stmt->execute([$produto_id, $nome_opcao, $preco_adicional]);
    }
    header("Location: produto_opcoes.php?produto_id=$produto_id");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM produto_opcoes WHERE id = ? AND produto_id = ?");
    $stmt->execute([$_GET['delete'], $produto_id]);
    header("Location: produto_opcoes.php?produto_id=$produto_id");
    exit;
}

// Edição
$opcao_edicao = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM produto_opcoes WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $opcao_edicao = $stmt->fetch();
}

// Lista
$stmt_opcoes = $pdo->prepare("SELECT * FROM produto_opcoes WHERE produto_id = ?");
$stmt_opcoes->execute([$produto_id]);
$lista_opcoes = $stmt_opcoes->fetchAll();
?>

<link rel="stylesheet" href="../css/produto.css?v=1">

<section class="admin-crud">
    
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <h1>Opções: <span style="color:#777;"><?php echo htmlspecialchars($nome_produto); ?></span></h1>
        <a href="produtos.php" class="btn-cancel" style="padding: 8px 15px; font-size:0.9rem;">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="form-produto-card" style="max-width: 800px; margin: 0 auto 40px auto;">
        <h3><?php echo $opcao_edicao ? 'Editar Opção' : 'Adicionar Nova Opção'; ?></h3>
        
        <form action="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo $opcao_edicao['id'] ?? ''; ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome da Opção (Ex: Bacon Extra)</label>
                    <input type=\"text\" name="nome_opcao" value="<?php echo htmlspecialchars($opcao_edicao['nome_opcao'] ?? ''); ?>" required placeholder="Digite o nome do adicional">
                </div>
                
                <div class="form-group">
                    <label>Preço Adicional (R$)</label>
                    <input type="number" step="0.01" name="preco_adicional" value="<?php echo $opcao_edicao['preco_adicional'] ?? '0.00'; ?>" required>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn-save" style="width: auto;">
                    <i class="fa-solid fa-plus"></i> <?php echo $opcao_edicao ? 'Atualizar' : 'Adicionar'; ?>
                </button>
                <?php if ($opcao_edicao): ?>
                    <a href="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>" class="btn-cancel" style="width:auto;">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="tabela-admin">
            <thead>
                <tr>
                    <th>Nome da Opção</th>
                    <th>Preço Adicional</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($lista_opcoes) > 0): ?>
                    <?php foreach ($lista_opcoes as $opcao): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($opcao['nome_opcao']); ?></td>
                            <td style="color: green; font-weight:bold;">
                                + R$ <?php echo number_format($opcao['preco_adicional'], 2, ',', '.'); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>&edit=<?php echo $opcao['id']; ?>" class="btn-edit">
                                        <i class="fa-solid fa-pen"></i> Editar
                                    </a>
                                    <a href="produto_opcoes.php?produto_id=<?php echo $produto_id; ?>&delete=<?php echo $opcao['id']; ?>" class="btn-remover" onclick="return confirm('Excluir esta opção?');">
                                        <i class="fa-solid fa-trash"></i> Excluir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center; padding: 30px; color:#999;">
                            Nenhuma opção cadastrada para este produto.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
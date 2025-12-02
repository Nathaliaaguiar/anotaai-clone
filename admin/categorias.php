<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php'; // Já carrega o header.css

$loja_id = $_SESSION['admin_loja_id'];

// --- LÓGICA CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = $_POST['nome'];
    $id = $_POST['id'] ?? null;
    
    if ($id) {
        // Editar
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ? AND loja_id = ?");
        $stmt->execute([$nome, $id, $loja_id]);
    } else {
        // Criar
        $stmt = $pdo->prepare("INSERT INTO categorias (nome, loja_id) VALUES (?, ?)");
        $stmt->execute([$nome, $loja_id]);
    }
    header('Location: categorias.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Excluir
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    header('Location: categorias.php');
    exit;
}

// Busca dados para edição
$categoria_edicao = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? AND loja_id = ?");
    $stmt->execute([$_GET['edit'], $loja_id]);
    $categoria_edicao = $stmt->fetch();
}

// Lista Categorias
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE loja_id = ? ORDER BY id DESC");
$stmt->execute([$loja_id]);
$categorias = $stmt->fetchAll();
?>

<link rel="stylesheet" href="../css/produto.css?v=2">

<section class="admin-crud">
    
    <h1>Gerenciar Categorias</h1>

    <div class="form-produto-card form-mini">
        <h3 style="margin-top:0; margin-bottom:20px; color:#555; text-align: center;">
            <?php echo $categoria_edicao ? 'Editar Categoria' : 'Nova Categoria'; ?>
        </h3>
        
        <form action="categorias.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $categoria_edicao['id'] ?? ''; ?>">
            
            <div class="form-group">
                <label for="nome">Nome da Categoria</label>
                <input type="text" id="nome" name="nome" 
                       value="<?php echo htmlspecialchars($categoria_edicao['nome'] ?? ''); ?>" 
                       placeholder="Ex: Bebidas, Lanches, Sobremesas..." required>
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> 
                <?php echo $categoria_edicao ? 'Salvar Alterações' : 'Cadastrar Categoria'; ?>
            </button>
            
            <?php if ($categoria_edicao): ?>
                <a href="categorias.php" class="btn-cancel" style="display:block; text-align:center;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive" style="max-width: 800px; margin: 0 auto;">
        <table class="tabela-admin">
            <thead>
                <tr>
                    <th>Nome da Categoria</th>
                    <th style="width: 150px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categorias) > 0): ?>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td>
                                <i class="fa-solid fa-tag" style="color: #ff6f00; margin-right: 8px;"></i>
                                <strong><?php echo htmlspecialchars($categoria['nome']); ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-buttons" style="justify-content: center;">
                                    <a href="categorias.php?edit=<?php echo $categoria['id']; ?>" class="btn-edit" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="categorias.php?delete=<?php echo $categoria['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza? Os produtos desta categoria ficarão sem categoria.');" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 30px; color: #999;">
                            Nenhuma categoria cadastrada.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
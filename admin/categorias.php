<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';
// ADICIONADO: A "chave mestra"
$loja_id = $_SESSION['admin_loja_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = $_POST['nome'];
    $id = $_POST['id'] ?? null;
    if ($id) {
        // MODIFICADO: Garante que só edite categoria da própria loja
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ? AND loja_id = ?");
        $stmt->execute([$nome, $id, $loja_id]);
    } else {
        // MODIFICADO: Adiciona a nova categoria na loja certa
        $stmt = $pdo->prepare("INSERT INTO categorias (nome, loja_id) VALUES (?, ?)");
        $stmt->execute([$nome, $loja_id]);
    }
    header('Location: categorias.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // MODIFICADO: Garante que só delete categoria da própria loja
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    header('Location: categorias.php');
    exit;
}

$categoria_edicao = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    // MODIFICADO: Garante que só busque categoria da própria loja para editar
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    $categoria_edicao = $stmt->fetch();
}

// MODIFICADO: Lista apenas as categorias da loja logada
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE loja_id = ? ORDER BY nome");
$stmt->execute([$loja_id]);
$categorias = $stmt->fetchAll();
?>
<section class="admin-crud">
    <h1>Gerenciar Categorias</h1>
    <div class="form-container">
        <form action="categorias.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $categoria_edicao['id'] ?? ''; ?>">
            <div class="form-group">
                <label for="nome">Nome da Categoria:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($categoria_edicao['nome'] ?? ''); ?>" required>
            </div>
            <button type="submit" class="btn"><?php echo $categoria_edicao ? 'Atualizar Categoria' : 'Adicionar Categoria'; ?></button>
            <?php if ($categoria_edicao): ?>
                <a href="categorias.php" class="btn-cancel" style="background-color:#777;">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>
    <h2>Lista de Categorias</h2>
    <table class="tabela-admin">
        <thead><tr><th>ID</th><th>Nome</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($categorias as $categoria): ?>
                <tr>
                    <td><?php echo $categoria['id']; ?></td>
                    <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                    <td>
                        <a href="categorias.php?edit=<?php echo $categoria['id']; ?>" class="btn-edit">Editar</a>
                        <a href="categorias.php?delete=<?php echo $categoria['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza? Os produtos desta categoria ficarão sem categoria.');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
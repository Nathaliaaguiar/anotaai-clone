<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// --- LÓGICA DE ADICIONAR/EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = $_POST['nome'];
    $id = $_POST['id'] ?? null;

    if ($id) { // Edição
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $id]);
    } else { // Adição
        $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
        $stmt->execute([$nome]);
    }
    header('Location: categorias.php');
    exit;
}

// --- LÓGICA DE DELETAR ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Ao deletar uma categoria, a chave estrangeira fará com que os produtos fiquem com categoria_id = NULL
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: categorias.php');
    exit;
}

// Buscar categoria para editar
$categoria_edicao = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $categoria_edicao = $stmt->fetch();
}

// Listar todas as categorias
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
?>

<section class="admin-crud">
    <h1>Gerenciar Categorias</h1>

    <div class="form-wrapper">
        <h2><?php echo $categoria_edicao ? 'Editar Categoria' : 'Adicionar Nova Categoria'; ?></h2>
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
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Ações</th>
            </tr>
        </thead>
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
<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';
// ADICIONADO: A "chave mestra"
$loja_id = $_SESSION['admin_loja_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bairro'])) {
    $bairro = trim($_POST['bairro']);
    $taxa = $_POST['taxa_entrega'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        // MODIFICADO: Garante que só edite área da própria loja
        $stmt = $pdo->prepare("UPDATE areas_entrega SET bairro = ?, taxa_entrega = ? WHERE id = ? AND loja_id = ?");
        $stmt->execute([$bairro, $taxa, $id, $loja_id]);
    } else {
        // MODIFICADO: Adiciona a nova área na loja certa
        $stmt = $pdo->prepare("INSERT INTO areas_entrega (bairro, taxa_entrega, loja_id) VALUES (?, ?, ?)");
        $stmt->execute([$bairro, $taxa, $loja_id]);
    }
    header('Location: entregas.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // MODIFICADO: Garante que só delete área da própria loja
    $stmt = $pdo->prepare("DELETE FROM areas_entrega WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    header('Location: entregas.php');
    exit;
}

$area_edicao = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    // MODIFICADO: Garante que só busque área da própria loja para editar
    $stmt = $pdo->prepare("SELECT * FROM areas_entrega WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    $area_edicao = $stmt->fetch();
}

// MODIFICADO: Lista apenas as áreas da loja logada
$stmt = $pdo->prepare("SELECT * FROM areas_entrega WHERE loja_id = ? ORDER BY bairro");
$stmt->execute([$loja_id]);
$areas = $stmt->fetchAll();
?>
<section class="admin-crud">
    <h1>Gerenciar Áreas de Entrega</h1>
    <div class="form-container">
        <form action="entregas.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $area_edicao['id'] ?? ''; ?>">
            <div class="form-group">
                <label for="bairro">Nome do Bairro:</label>
                <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($area_edicao['bairro'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="taxa_entrega">Taxa de Entrega (R$):</label>
                <input type="number" step="0.01" id="taxa_entrega" name="taxa_entrega" value="<?php echo htmlspecialchars($area_edicao['taxa_entrega'] ?? '0.00'); ?>" required>
            </div>
            <button type="submit" class="btn"><?php echo $area_edicao ? 'Atualizar' : 'Adicionar'; ?></button>
        </form>
    </div>
    <h2>Lista de Áreas de Entrega</h2>
    <table class="tabela-admin">
        <thead><tr><th>Bairro</th><th>Taxa</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($areas as $area): ?>
                <tr>
                    <td><?php echo htmlspecialchars($area['bairro']); ?></td>
                    <td>R$ <?php echo number_format($area['taxa_entrega'], 2, ',', '.'); ?></td>
                    <td>
                        <a href="entregas.php?edit=<?php echo $area['id']; ?>" class="btn-edit">Editar</a>
                        <a href="entregas.php?delete=<?php echo $area['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza?');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
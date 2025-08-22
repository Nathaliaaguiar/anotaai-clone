<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// --- LÓGICA DE ADICIONAR/EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bairro'])) {
    $bairro = trim($_POST['bairro']);
    $taxa = $_POST['taxa_entrega'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE areas_entrega SET bairro = ?, taxa_entrega = ? WHERE id = ?");
        $stmt->execute([$bairro, $taxa, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO areas_entrega (bairro, taxa_entrega) VALUES (?, ?)");
        $stmt->execute([$bairro, $taxa]);
    }
    header('Location: entregas.php');
    exit;
}
// --- LÓGICA DE DELETAR ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM areas_entrega WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: entregas.php');
    exit;
}
// Buscar área para editar
$area_edicao = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM areas_entrega WHERE id = ?");
    $stmt->execute([$id]);
    $area_edicao = $stmt->fetch();
}
// Listar todas as áreas
$areas = $pdo->query("SELECT * FROM areas_entrega ORDER BY bairro ASC")->fetchAll();
?>

<section class="admin-crud">
    <h1>Gerenciar Áreas e Taxas de Entrega</h1>
    <div class="form-wrapper">
        <h2><?php echo $area_edicao ? 'Editar Área' : 'Adicionar Nova Área'; ?></h2>
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
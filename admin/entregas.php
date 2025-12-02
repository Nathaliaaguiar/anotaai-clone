<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php'; // Já carrega o header.css

$loja_id = $_SESSION['admin_loja_id'];

// --- LÓGICA CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bairro'])) {
    $bairro = trim($_POST['bairro']);
    $taxa = $_POST['taxa_entrega'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        // Editar
        $stmt = $pdo->prepare("UPDATE areas_entrega SET bairro = ?, taxa_entrega = ? WHERE id = ? AND loja_id = ?");
        $stmt->execute([$bairro, $taxa, $id, $loja_id]);
    } else {
        // Criar
        $stmt = $pdo->prepare("INSERT INTO areas_entrega (bairro, taxa_entrega, loja_id) VALUES (?, ?, ?)");
        $stmt->execute([$bairro, $taxa, $loja_id]);
    }
    header('Location: entregas.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Excluir
    $stmt = $pdo->prepare("DELETE FROM areas_entrega WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    header('Location: entregas.php');
    exit;
}

// Busca dados para edição
$area_edicao = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM areas_entrega WHERE id = ? AND loja_id = ?");
    $stmt->execute([$_GET['edit'], $loja_id]);
    $area_edicao = $stmt->fetch();
}

// Lista Áreas
$stmt = $pdo->prepare("SELECT * FROM areas_entrega WHERE loja_id = ? ORDER BY bairro ASC");
$stmt->execute([$loja_id]);
$areas = $stmt->fetchAll();
?>

<link rel="stylesheet" href="../css/produto.css?v=2">

<section class="admin-crud">
    
    <h1>Gerenciar Taxas de Entrega</h1>

    <div class="form-produto-card form-mini">
        <h3 style="margin-top:0; margin-bottom:20px; color:#555; text-align: center;">
            <?php echo $area_edicao ? 'Editar Área' : 'Nova Área de Entrega'; ?>
        </h3>
        
        <form action="entregas.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $area_edicao['id'] ?? ''; ?>">
            
            <div class="form-group">
                <label for="bairro">Bairro / Região</label>
                <input type="text" id="bairro" name="bairro" 
                       value="<?php echo htmlspecialchars($area_edicao['bairro'] ?? ''); ?>" 
                       placeholder="Ex: Centro, Zona Sul..." required>
            </div>

            <div class="form-group">
                <label for="taxa_entrega">Taxa de Entrega (R$)</label>
                <input type="number" step="0.01" id="taxa_entrega" name="taxa_entrega" 
                       value="<?php echo htmlspecialchars($area_edicao['taxa_entrega'] ?? '0.00'); ?>" 
                       required>
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-check"></i> 
                <?php echo $area_edicao ? 'Salvar Alterações' : 'Adicionar Área'; ?>
            </button>
            
            <?php if ($area_edicao): ?>
                <a href="entregas.php" class="btn-cancel" style="display:block; text-align:center;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive" style="max-width: 800px; margin: 0 auto;">
        <table class="tabela-admin">
            <thead>
                <tr>
                    <th>Bairro</th>
                    <th>Taxa</th>
                    <th style="width: 150px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($areas) > 0): ?>
                    <?php foreach ($areas as $area): ?>
                        <tr>
                            <td>
                                <i class="fa-solid fa-map-pin" style="color: #ff6f00; margin-right: 8px;"></i>
                                <strong><?php echo htmlspecialchars($area['bairro']); ?></strong>
                            </td>
                            <td style="color: green; font-weight: bold;">
                                R$ <?php echo number_format($area['taxa_entrega'], 2, ',', '.'); ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-buttons" style="justify-content: center;">
                                    <a href="entregas.php?edit=<?php echo $area['id']; ?>" class="btn-edit" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="entregas.php?delete=<?php echo $area['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza?');" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 30px; color: #999;">
                            Nenhuma área de entrega cadastrada.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
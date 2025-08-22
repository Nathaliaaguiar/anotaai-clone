<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// Atualiza o status de um pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'])) {
    $pedido_id = $_POST['pedido_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->execute([$status, $pedido_id]);
    header("Location: pedidos.php");
    exit;
}

// Busca todos os pedidos com o nome do cliente
$pedidos = $pdo->query("
    SELECT p.*, u.nome as cliente_nome 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    ORDER BY p.data DESC
")->fetchAll();

$status_options = ['pendente', 'preparando', 'a_caminho', 'entregue', 'cancelado'];
?>

<section class="admin-crud">
    <h1>Gerenciar Pedidos</h1>
    <table class="tabela-admin">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Data</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?php echo $pedido['id']; ?></td>
                    <td><?php echo htmlspecialchars($pedido['cliente_nome']); ?></td>
                    <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></td>
                    <td>
                        <form action="pedidos.php" method="POST" class="form-status">
                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                            <select name="status">
                                <?php foreach ($status_options as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php if ($pedido['status'] == $status) echo 'selected'; ?>>
                                        <?php echo ucwords(str_replace('_', ' ', $status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">OK</button>
                        </form>
                    </td>
                    <td>
                        <a href="pedido_detalhes.php?id=<?php echo $pedido['id']; ?>" class="btn-edit">Ver Detalhes</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
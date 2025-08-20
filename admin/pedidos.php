<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// Lógica para atualizar status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mudar_status'])) {
    $pedido_id = $_POST['pedido_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->execute([$status, $pedido_id]);
    header('Location: pedidos.php');
    exit;
}

// Listar todos os pedidos
$pedidos = $pdo->query("
    SELECT p.id, p.data, p.total, p.status, u.nome as cliente_nome 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    ORDER BY p.data DESC
")->fetchAll();
?>

<section class="admin-crud">
    <h1>Gerenciar Pedidos</h1>
    <table class="tabela-admin">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Data</th>
                <th>Total</th>
                <th>Status</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?php echo $pedido['id']; ?></td>
                    <td><?php echo htmlspecialchars($pedido['cliente_nome']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></td>
                    <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                    <td><span class="status-<?php echo $pedido['status']; ?>"><?php echo ucfirst($pedido['status']); ?></span></td>
                    <td>
                        <form action="pedidos.php" method="POST" class="form-status">
                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                            <select name="status">
                                <option value="pendente" <?php if($pedido['status'] == 'pendente') echo 'selected'; ?>>Pendente</option>
                                <option value="preparando" <?php if($pedido['status'] == 'preparando') echo 'selected'; ?>>Preparando</option>
                                <option value="a caminho" <?php if($pedido['status'] == 'a caminho') echo 'selected'; ?>>A Caminho</option>
                                <option value="entregue" <?php if($pedido['status'] == 'entregue') echo 'selected'; ?>>Entregue</option>
                            </select>
                            <button type="submit" name="mudar_status">Alterar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
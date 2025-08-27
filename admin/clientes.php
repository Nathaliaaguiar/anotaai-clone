<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';
// ADICIONADO: A "chave mestra"
$loja_id = $_SESSION['admin_loja_id'];

// MODIFICADO: Mostra apenas clientes que já fizeram pedidos na loja logada
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.nome, u.email, u.telefone, u.endereco, u.bairro
    FROM usuarios u
    JOIN pedidos p ON u.id = p.usuario_id
    WHERE p.loja_id = ?
    ORDER BY u.nome ASC
");
$stmt->execute([$loja_id]);
$clientes = $stmt->fetchAll();
?>
<section class="admin-crud">
    <h1>Lista de Clientes da Loja</h1>
    <table class="tabela-admin">
        <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Endereço</th></tr></thead>
        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?php echo $cliente['id']; ?></td>
                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['endereco'] . ' - ' . $cliente['bairro']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
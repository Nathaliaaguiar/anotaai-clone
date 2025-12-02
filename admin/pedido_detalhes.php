<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}
$pedido_id = $_GET['id'];
$loja_id = $_SESSION['admin_loja_id']; // Segurança extra

// Busca pedido + dados do cliente
$stmt_pedido = $pdo->prepare("
    SELECT p.*, u.nome as cliente_nome, u.endereco, u.bairro, u.telefone
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = ? AND p.loja_id = ?
");
$stmt_pedido->execute([$pedido_id, $loja_id]);
$pedido = $stmt_pedido->fetch();

if (!$pedido) {
    echo "<script>alert('Pedido não encontrado ou acesso negado.'); window.location='pedidos.php';</script>";
    exit;
}

// Busca itens
$stmt_itens = $pdo->prepare("
    SELECT pi.*, pr.nome as produto_nome 
    FROM pedido_itens pi 
    JOIN produtos pr ON pi.produto_id = pr.id 
    WHERE pi.pedido_id = ?
");
$stmt_itens->execute([$pedido_id]);
$itens_pedido = $stmt_itens->fetchAll();
?>

<link rel="stylesheet" href="../css/pedido.css?v=1">

<section class="admin-crud">
    
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <a href="pedidos.php" class="btn-voltar"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        <h1>Detalhes do Pedido #<?php echo $pedido['id']; ?></h1>
    </div>

    <div class="detalhes-pedido-grid">
        
        <div class="form-wrapper">
            <h3><i class="fa-solid fa-user"></i> Dados do Cliente</h3>
            
            <div class="info-grupo">
                <strong>Nome:</strong>
                <p><?php echo htmlspecialchars($pedido['cliente_nome']); ?></p>
            </div>
            
            <div class="info-grupo">
                <strong>Telefone:</strong>
                <p><?php echo htmlspecialchars($pedido['telefone'] ?? 'Não informado'); ?></p>
            </div>

            <div class="info-grupo">
                <strong>Endereço de Entrega:</strong>
                <p>
                    <?php echo htmlspecialchars($pedido['endereco']); ?><br>
                    Bairro: <?php echo htmlspecialchars($pedido['bairro']); ?>
                </p>
            </div>

            <div class="info-grupo" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                <strong>Total do Pedido:</strong>
                <p style="color: #e67e22; font-size: 1.5rem;">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></p>
            </div>
            
            <div class="info-grupo">
                 <strong>Status Atual:</strong>
                 <span style="background: #eee; padding: 5px 10px; border-radius: 4px; font-weight: bold;">
                    <?php echo ucwords(str_replace('_', ' ', $pedido['status'])); ?>
                 </span>
            </div>
        </div>

        <div class="form-wrapper">
            <h2><i class="fa-solid fa-receipt"></i> Itens do Pedido</h2>
            
            <div class="table-responsive">
                <table class="tabela-admin">
                    <thead>
                        <tr>
                            <th>Qtd.</th>
                            <th>Produto e Opções</th>
                            <th>Preço Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens_pedido as $item): ?>
                            <tr>
                                <td style="font-weight: bold; font-size: 1.1rem;"><?php echo $item['quantidade']; ?>x</td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['produto_nome']); ?></strong>
                                    <?php if ($item['observacao']): ?>
                                        <br><small style="color: #e74c3c;"><em>Obs: <?php echo htmlspecialchars($item['observacao']); ?></em></small>
                                    <?php endif; ?>
                                </td>
                                <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
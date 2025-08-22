<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}
$pedido_id = $_GET['id'];

// --- CORREÇÃO 1: A query agora busca também o bairro e o telefone do usuário ---
$stmt_pedido = $pdo->prepare("
    SELECT p.*, u.nome as cliente_nome, u.endereco, u.bairro, u.telefone
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = ?
");
$stmt_pedido->execute([$pedido_id]);
$pedido = $stmt_pedido->fetch();

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Busca os itens do pedido (esta parte já estava correta)
$stmt_itens = $pdo->prepare("
    SELECT pi.*, pr.nome as produto_nome 
    FROM pedido_itens pi 
    JOIN produtos pr ON pi.produto_id = pr.id 
    WHERE pi.pedido_id = ?
");
$stmt_itens->execute([$pedido_id]);
$itens_pedido = $stmt_itens->fetchAll();
?>

<section class="admin-crud">
    <a href="pedidos.php" class="btn-voltar" style="margin-bottom: 1.5rem; display: inline-block;">&larr; Voltar para Pedidos</a>
    <h1>Detalhes do Pedido #<?php echo $pedido['id']; ?></h1>

    <div class="detalhes-pedido-grid">
        <div class="form-wrapper">
            <h2>Informações do Pedido</h2>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente_nome']); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($pedido['telefone']); ?></p>
            <p><strong>Endereço:</strong> <?php echo htmlspecialchars($pedido['endereco'] . ', ' . $pedido['bairro']); ?></p>
            <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></p>
            <p><strong>Pagamento:</strong> <?php echo ucwords($pedido['metodo_pagamento']); ?></p>
            <?php if ($pedido['metodo_pagamento'] == 'dinheiro' && $pedido['troco_para']): ?>
                <p><strong>Troco para:</strong> R$ <?php echo number_format($pedido['troco_para'], 2, ',', '.'); ?></p>
            <?php endif; ?>
            <p><strong>Status:</strong> <span class="status-<?php echo $pedido['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $pedido['status'])); ?></span></p>
            <hr style="margin: 1rem 0;">
            
            <p><strong>Subtotal dos Produtos:</strong> R$ <?php echo number_format($pedido['total'] - $pedido['taxa_entrega'], 2, ',', '.'); ?></p>
            <p><strong>Taxa de Entrega:</strong> R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></p>
            <p style="font-size: 1.5rem; font-weight: bold; margin-top: 10px;">Total do Pedido: R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></p>
        </div>

        <div class="form-wrapper">
            <h2>Itens do Pedido</h2>
            <table class="tabela-admin">
                <thead><tr><th>Qtd.</th><th>Produto e Opções</th><th>Preço Unit.</th><th>Subtotal</th></tr></thead>
                <tbody>
                    <?php foreach ($itens_pedido as $item): ?>
                        <tr>
                            <td><?php echo $item['quantidade']; ?>x</td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['produto_nome']); ?></strong>
                                <?php if ($item['observacao']): ?>
                                    <br><small><em><?php echo htmlspecialchars($item['observacao']); ?></em></small>
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
</section>

<style>
.detalhes-pedido-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
@media (max-width: 900px) { .detalhes-pedido-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
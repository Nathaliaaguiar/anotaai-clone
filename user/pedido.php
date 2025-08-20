<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$erro_pedido = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['carrinho'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $carrinho = $_SESSION['carrinho'];
    $total = 0;
    
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';
    $troco_para = null;

    if ($metodo_pagamento === 'dinheiro' && !empty($_POST['troco_para'])) {
        $troco_para = filter_var($_POST['troco_para'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    if (empty($metodo_pagamento)) {
        $erro_pedido = "Por favor, selecione um método de pagamento.";
    } else {
        $produto_ids = array_column($carrinho, 'produto_id');
        $produtos_db = [];
        if (!empty($produto_ids)) {
            $ids_string = implode(',', array_unique($produto_ids));
            $stmt = $pdo->query("SELECT id, preco FROM produtos WHERE id IN ($ids_string)");
            $produtos_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($produtos_lista as $produto) {
                $produtos_db[$produto['id']] = $produto;
            }
        }
        foreach ($carrinho as $item) {
            if (isset($produtos_db[$item['produto_id']])) {
                $total += $produtos_db[$item['produto_id']]['preco'] * $item['quantidade'];
            }
        }

        try {
            $pdo->beginTransaction();

            // ---- MUDANÇA PRINCIPAL AQUI ----
            // Define o status inicial baseado no método de pagamento
            $status_inicial = ($metodo_pagamento === 'pix') ? 'aguardando_pagamento' : 'pendente';

            $stmt_pedido = $pdo->prepare(
                "INSERT INTO pedidos (usuario_id, total, status, metodo_pagamento, troco_para) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt_pedido->execute([$usuario_id, $total, $status_inicial, $metodo_pagamento, $troco_para]);
            $pedido_id = $pdo->lastInsertId();

            $stmt_item = $pdo->prepare(
                "INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco, observacao) VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($carrinho as $item) {
                if (isset($produtos_db[$item['produto_id']])) {
                    $stmt_item->execute([$pedido_id, $item['produto_id'], $item['quantidade'], $produtos_db[$item['produto_id']]['preco'], $item['observacao']]);
                }
            }
            
            $pdo->commit();
            unset($_SESSION['carrinho']);

            if ($metodo_pagamento === 'pix') {
                header('Location: pagamento.php?pedido_id=' . $pedido_id);
            } else {
                header('Location: perfil.php?pedido_sucesso=1');
            }
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erro_pedido = "Ocorreu um erro ao processar seu pedido. Tente novamente.";
        }
    }
}
?>
<section class="finalizar-pedido">
    <h1>Finalizar Pedido</h1>
    <?php if ($erro_pedido): ?>
        <p class="error"><?php echo $erro_pedido; ?></p>
    <?php endif; ?>

    <?php if (empty($_SESSION['carrinho'])): ?>
        <p>Seu carrinho está vazio. Adicione itens antes de finalizar.</p>
        <a href="index.php" class="btn">Ver Cardápio</a>
    <?php else: ?>
        <form action="pedido.php" method="POST" id="form-pedido">
            <h2>Endereço de Entrega</h2>
            <p>O pedido será enviado para o endereço cadastrado em seu perfil. Verifique se ele está atualizado antes de continuar.</p>
            
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

            <h2>Forma de Pagamento</h2>
            <div class="form-group">
                <label class="radio-label">
                    <input type="radio" name="metodo_pagamento" value="dinheiro" required> Dinheiro
                </label>
                <label class="radio-label">
                    <input type="radio" name="metodo_pagamento" value="cartao" required> Cartão de Crédito/Débito
                </label>
                <label class="radio-label">
                    <input type="radio" name="metodo_pagamento" value="pix" required> PIX
                </label>
            </div>

            <div class="form-group" id="campo-troco" style="display: none;">
                <label for="troco_para">Precisa de troco para quanto? (Opcional)</label>
                <input type="number" name="troco_para" id="troco_para" step="0.01" min="0.01" placeholder="Ex: 50.00">
            </div>

            <button type="submit" class="btn">Confirmar e Finalizar Pedido</button>
        </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
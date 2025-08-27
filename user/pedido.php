<?php
require_once __DIR__ . '/../includes/header.php';

// 1. VERIFICAÇÕES INICIAIS (SEU CÓDIGO ORIGINAL)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$erro_pedido = null;
$carrinho = $_SESSION['carrinho'] ?? [];

// ADICIONADO: Pega o ID da loja da sessão. É a chave para tudo funcionar.
$loja_id = $_SESSION['loja_id_visitada'] ?? 0;
if ($loja_id == 0) {
    die("<main class='container'>Erro: Nenhuma loja selecionada. Por favor, volte ao cardápio e tente novamente.</main>");
}
// ADICIONADO: Garante que o carrinho pertence à loja que está sendo visitada
if (isset($_SESSION['carrinho_loja_id']) && $_SESSION['carrinho_loja_id'] != $loja_id) {
    die("<main class='container'>Erro de consistência no carrinho. Por favor, esvazie seu carrinho e tente novamente.</main>");
}


// 2. BUSCA DADOS DO USUÁRIO E VERIFICA A TAXA DE ENTREGA (SEU CÓDIGO ORIGINAL, ADAPTADO)
$stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_id]);
$usuario = $stmt_user->fetch();

$taxa_entrega = 0;
$bairro_atendido = true;
if ($usuario && !empty($usuario['bairro'])) {
    // MODIFICADO: Busca a taxa de entrega da loja específica
    $stmt_taxa = $pdo->prepare("SELECT taxa_entrega FROM areas_entrega WHERE bairro = ? AND loja_id = ?");
    $stmt_taxa->execute([$usuario['bairro'], $loja_id]);
    $area = $stmt_taxa->fetch();
    if ($area) {
        $taxa_entrega = $area['taxa_entrega'];
    } else {
        $bairro_atendido = false;
        $erro_pedido = "Desculpe, a loja atual não atende o seu bairro: " . htmlspecialchars($usuario['bairro']);
    }
} else {
    $bairro_atendido = false;
    $erro_pedido = "Por favor, complete seu bairro no seu perfil antes de fazer um pedido.";
}

// 3. CALCULA O TOTAL DOS PRODUTOS (SEU CÓDIGO ORIGINAL, AGORA CORRIGIDO)
$total_produtos = 0;
$produtos_db = []; // Usaremos este array
if (!empty($carrinho)) {
    $produto_ids = array_column($carrinho, 'produto_id');
    $ids_string = implode(',', array_unique($produto_ids));
    if(!empty($ids_string)){
        // CORREÇÃO AQUI: Voltamos ao seu método original e funcional
        $stmt_produtos = $pdo->query("SELECT * FROM produtos WHERE id IN ($ids_string)");
        $produtos_db_lista = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
        // E criamos o array com a chave correta, como você fez no carrinho.php
        foreach($produtos_db_lista as $p) { $produtos_db[$p['id']] = $p; }
    }
    foreach ($carrinho as $item) {
        if (isset($produtos_db[$item['produto_id']])) {
            $produto_info = $produtos_db[$item['produto_id']];
            $preco_total_item = ($produto_info['preco'] + ($item['opcao_preco_adicional'] ?? 0)) * $item['quantidade'];
            $total_produtos += $preco_total_item;
        }
    }
}
$total_final = $total_produtos + $taxa_entrega;


// 4. PROCESSA O PEDIDO (SEU CÓDIGO ORIGINAL, ADAPTADO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($carrinho) && $bairro_atendido) {
    // ... (O resto do seu código para processar o pedido continua o mesmo) ...
    $metodo_pagamento = $_POST['metodo_pagamento'];
    $troco_para = (!empty($_POST['troco_para'])) ? $_POST['troco_para'] : null;
    $status_inicial = ($metodo_pagamento === 'pix') ? 'aguardando_pagamento' : 'pendente';
    
    try {
        $pdo->beginTransaction();
        $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, loja_id, total, taxa_entrega, status, metodo_pagamento, troco_para) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_pedido->execute([$usuario_id, $loja_id, $total_final, $taxa_entrega, $status_inicial, $metodo_pagamento, $troco_para]);
        $pedido_id = $pdo->lastInsertId();

        $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco, observacao) VALUES (?, ?, ?, ?, ?)");
        foreach ($carrinho as $item) {
            $produto_info = $produtos_db[$item['produto_id']];
            $preco_final_item = $produto_info['preco'] + ($item['opcao_preco_adicional'] ?? 0);
            $stmt_item->execute([$pedido_id, $item['produto_id'], $item['quantidade'], $preco_final_item, $item['observacao']]);
        }
        $pdo->commit();
        $_SESSION['carrinho'] = [];
        unset($_SESSION['carrinho_loja_id']);
        header('Location: ' . ($metodo_pagamento === 'pix' ? 'pagamento.php?pedido_id=' . $pedido_id : 'perfil.php?pedido_sucesso=1'));
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $erro_pedido = "Erro ao registrar o pedido: " . $e->getMessage();
    }
}
?>

<section class="finalizar-pedido-page">
    <h1>Finalizar Pedido</h1>
    <?php if ($erro_pedido): ?><p class="error"><?php echo $erro_pedido; ?></p><?php endif; ?>
    <div class="finalizar-pedido-grid">
        <div class="resumo-compra form-wrapper">
            <h2>Resumo da Compra</h2>
            <ul>
                <?php if (empty($carrinho)): ?>
                    <li>Seu carrinho está vazio.</li>
                <?php else: 
                    foreach ($carrinho as $item): 
                        if (!isset($produtos_db[$item['produto_id']])) continue;
                        $produto_info = $produtos_db[$item['produto_id']];
                    ?>
                    <li>
                        <span><?php echo $item['quantidade']; ?>x <?php echo htmlspecialchars($produto_info['nome']); ?></span>
                        <span>R$ <?php echo number_format(($produto_info['preco'] + $item['opcao_preco_adicional']) * $item['quantidade'], 2, ',', '.'); ?></span>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            <hr>
            <ul class="totais">
                <li><span>Subtotal:</span> <span>R$ <?php echo number_format($total_produtos, 2, ',', '.'); ?></span></li>
                <li><span>Taxa de Entrega:</span> <span>R$ <?php echo number_format($taxa_entrega, 2, ',', '.'); ?></span></li>
                <li class="total"><span>Total a Pagar:</span> <span>R$ <?php echo number_format($total_final, 2, ',', '.'); ?></span></li>
            </ul>
        </div>
        <?php if ($bairro_atendido && !empty($carrinho)): ?>
            <form action="pedido.php" method="POST" id="form-pedido">
                <h2>Forma de Pagamento</h2>
                <div class="form-group">
                    <label class="radio-label"><input type="radio" name="metodo_pagamento" value="dinheiro" required> Dinheiro</label>
                    <label class="radio-label"><input type="radio" name="metodo_pagamento" value="cartao" required> Cartão</label>
                    <label class="radio-label"><input type="radio" name="metodo_pagamento" value="pix" required> PIX</label>
                </div>
                <div class="form-group" id="campo-troco" style="display: none;">
                    <label for="troco_para">Troco para quanto?</label>
                    <input type="number" name="troco_para" id="troco_para" step="0.01" placeholder="Ex: 50.00">
                </div>
                <button type="submit" class="btn">Confirmar Pedido</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="metodo_pagamento"]');
    const campoTroco = document.getElementById('campo-troco');
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            campoTroco.style.display = (this.value === 'dinheiro') ? 'block' : 'none';
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/header.php';

// 1. VERIFICAÇÕES INICIAIS
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$erro_pedido = null;
$carrinho = $_SESSION['carrinho'] ?? [];

// 2. BUSCA DADOS DO USUÁRIO E VERIFICA A TAXA DE ENTREGA
$stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_id]);
$usuario = $stmt_user->fetch();

$taxa_entrega = 0;
$bairro_atendido = true;
if ($usuario && !empty($usuario['bairro'])) {
    $stmt_taxa = $pdo->prepare("SELECT taxa_entrega FROM areas_entrega WHERE bairro = ?");
    $stmt_taxa->execute([$usuario['bairro']]);
    $area = $stmt_taxa->fetch();
    if ($area) {
        $taxa_entrega = $area['taxa_entrega'];
    } else {
        $bairro_atendido = false;
        $erro_pedido = "Desculpe, ainda não atendemos o seu bairro: " . htmlspecialchars($usuario['bairro']);
    }
} else {
    $bairro_atendido = false;
    $erro_pedido = "Seu perfil não tem um bairro cadastrado. Por favor, atualize suas informações no seu perfil.";
}

// 3. CALCULA O TOTAL DOS PRODUTOS NO CARRINHO
$total_produtos = 0;
if (!empty($carrinho)) {
    $produto_ids = array_column($carrinho, 'produto_id');
    if (!empty($produto_ids)) {
        $ids_string = implode(',', array_unique($produto_ids));
        $stmt_prods = $pdo->query("SELECT id, preco FROM produtos WHERE id IN ($ids_string)");
        $produtos_db_lista = $stmt_prods->fetchAll(PDO::FETCH_ASSOC);
        $produtos_db = [];
        foreach($produtos_db_lista as $p) { $produtos_db[$p['id']] = $p; }
        
        foreach ($carrinho as $item) {
            if (isset($produtos_db[$item['produto_id']])) {
                $preco_base = $produtos_db[$item['produto_id']]['preco'];
                $total_produtos += ($preco_base + ($item['opcao_preco_adicional'] ?? 0)) * $item['quantidade'];
            }
        }
    }
}
$total_final = $total_produtos + $taxa_entrega;

// 4. LÓGICA PARA SALVAR O PEDIDO (VERSÃO CORRIGIDA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($carrinho) && $bairro_atendido) {
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';

    // CORREÇÃO: Define as variáveis aqui, antes do 'try'
    $status_inicial = ($metodo_pagamento === 'pix') ? 'aguardando_pagamento' : 'pendente';
    $troco_para = ($metodo_pagamento === 'dinheiro' && !empty($_POST['troco_para'])) ? filter_var($_POST['troco_para'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;

    if (empty($metodo_pagamento)) {
        $erro_pedido = "Por favor, selecione um método de pagamento.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // A query de inserção do pedido agora usa as variáveis corrigidas
            $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, total, taxa_entrega, status, metodo_pagamento, troco_para) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_pedido->execute([$usuario_id, $total_final, $taxa_entrega, $status_inicial, $metodo_pagamento, $troco_para]);
            $pedido_id = $pdo->lastInsertId();

            $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco, observacao) VALUES (?, ?, ?, ?, ?)");
            foreach ($carrinho as $item) {
                if (isset($produtos_db[$item['produto_id']])) {
                    $observacao_final = $item['observacao'];
                    if (!empty($item['opcao_nome'])) { $observacao_final = "Opção: " . $item['opcao_nome'] . ". " . $observacao_final; }
                    $preco_unitario_item = $produtos_db[$item['produto_id']]['preco'] + ($item['opcao_preco_adicional'] ?? 0);
                    $stmt_item->execute([$pedido_id, $item['produto_id'], $item['quantidade'], $preco_unitario_item, trim($observacao_final)]);
                }
            }
            
            $pdo->commit();
            unset($_SESSION['carrinho']);

            if ($metodo_pagamento === 'pix') { header('Location: pagamento.php?pedido_id=' . $pedido_id); } 
            else { header('Location: perfil.php?pedido_sucesso=1'); }
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erro_pedido = "Ocorreu um erro ao processar seu pedido. Por favor, tente novamente.";
            // Opcional: registrar o erro real para depuração -> error_log($e->getMessage());
        }
    }
}
?>

<section class="finalizar-pedido">
    <h1>Finalizar Pedido</h1>
    <?php if ($erro_pedido): ?><p class="error"><?php echo $erro_pedido; ?></p><?php endif; ?>
    <?php if (empty($carrinho)): ?>
        <p>Seu carrinho está vazio.</p><a href="index.php" class="btn">Ver Cardápio</a>
    <?php else: ?>
        <div class="resumo-pedido">
            <h3>Resumo do seu Pedido</h3>
            <p><strong>Entregar em:</strong> <?php echo htmlspecialchars($usuario['endereco'] . ', ' . $usuario['bairro']); ?></p>
            <ul>
                <li><span>Subtotal dos Produtos:</span> <span>R$ <?php echo number_format($total_produtos, 2, ',', '.'); ?></span></li>
                <li><span>Taxa de Entrega:</span> <span>R$ <?php echo number_format($taxa_entrega, 2, ',', '.'); ?></span></li>
                <li class="total"><span>Total a Pagar:</span> <span>R$ <?php echo number_format($total_final, 2, ',', '.'); ?></span></li>
            </ul>
        </div>
        
        <?php if ($bairro_atendido): ?>
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
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
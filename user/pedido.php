<?php
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="pedido.css">

<?php
// 1. VERIFICAÇÕES INICIAIS
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$erro_pedido = null;
$carrinho = $_SESSION['carrinho'] ?? [];

$loja_id = $_SESSION['loja_id_visitada'] ?? 0;
if ($loja_id == 0) {
    die("<main class='container'>Erro: Nenhuma loja selecionada. Por favor, volte ao cardápio e tente novamente.</main>");
}
if (isset($_SESSION['carrinho_loja_id']) && $_SESSION['carrinho_loja_id'] != $loja_id) {
    die("<main class='container'>Erro de consistência no carrinho. Por favor, esvazie seu carrinho e tente novamente.</main>");
}

// 2. BUSCA DADOS DO USUÁRIO E TAXA DE ENTREGA
$stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_id]);
$usuario = $stmt_user->fetch();

$taxa_entrega = 0;
$bairro_atendido = true;
if ($usuario && !empty($usuario['bairro'])) {
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


// 3. PROCESSA O CARRINHO PARA CÁLCULO E EXIBIÇÃO
$total_produtos = 0;
$itens_para_resumo = [];
if (!empty($carrinho)) {
    // Pega todos os IDs de produtos e opções do carrinho
    $produto_ids = array_column($carrinho, 'produto_id');
    $opcao_ids = [];
    foreach ($carrinho as $item) {
        if (!empty($item['opcoes_selecionadas'])) {
            $opcao_ids = array_merge($opcao_ids, $item['opcoes_selecionadas']);
        }
    }
    
    // Busca todos os produtos e opções do banco de dados de uma vez
    $produtos_db = [];
    $opcoes_db = [];
    if (!empty($produto_ids)) {
        $ids_string = implode(',', array_unique($produto_ids));
        $stmt_produtos = $pdo->query("SELECT * FROM produtos WHERE id IN ($ids_string)");
        foreach($stmt_produtos->fetchAll(PDO::FETCH_ASSOC) as $p) { $produtos_db[$p['id']] = $p; }
    }
    if (!empty($opcao_ids)) {
        $ids_string_opcoes = implode(',', array_unique($opcao_ids));
        $stmt_opcoes = $pdo->query("SELECT * FROM produto_opcoes WHERE id IN ($ids_string_opcoes)");
        foreach($stmt_opcoes->fetchAll(PDO::FETCH_ASSOC) as $o) { $opcoes_db[$o['id']] = $o; }
    }

    // Monta a lista de itens para o resumo, calculando os preços corretos
    foreach ($carrinho as $item_id => $item_session) {
        if (!isset($produtos_db[$item_session['produto_id']])) continue;
        
        $produto_info = $produtos_db[$item_session['produto_id']];
        $preco_opcoes = 0;
        $nomes_opcoes = [];

        if (!empty($item_session['opcoes_selecionadas'])) {
            foreach ($item_session['opcoes_selecionadas'] as $opcao_id) {
                if (isset($opcoes_db[$opcao_id])) {
                    $preco_opcoes += $opcoes_db[$opcao_id]['preco_adicional'];
                    $nomes_opcoes[] = $opcoes_db[$opcao_id]['nome'];
                }
            }
        }
        
        $preco_unitario_final = $produto_info['preco'] + $preco_opcoes;
        $subtotal = $preco_unitario_final * $item_session['quantidade'];
        $total_produtos += $subtotal;
        
        $observacao_final = $item_session['observacao'];
        if (!empty($nomes_opcoes)) {
            $observacao_final .= ($observacao_final ? ' | ' : '') . 'Adicionais: ' . implode(', ', $nomes_opcoes);
        }

        $itens_para_resumo[$item_id] = [
            'produto_id' => $item_session['produto_id'],
            'nome' => $produto_info['nome'],
            'quantidade' => $item_session['quantidade'],
            'subtotal' => $subtotal,
            'observacao' => $observacao_final,
            'nomes_opcoes' => $nomes_opcoes
        ];
    }
}
$total_final = $total_produtos + $taxa_entrega;


// 4. PROCESSA O PEDIDO AO ENVIAR O FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($itens_para_resumo) && $bairro_atendido) {
    $metodo_pagamento = $_POST['metodo_pagamento'];
    $troco_para = (!empty($_POST['troco_para'])) ? str_replace(',', '.', $_POST['troco_para']) : null;
    $status_inicial = ($metodo_pagamento === 'pix') ? 'aguardando_pagamento' : 'pendente';
    
    try {
        $pdo->beginTransaction();
        $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, loja_id, total, taxa_entrega, status, metodo_pagamento, troco_para) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_pedido->execute([$usuario_id, $loja_id, $total_final, $taxa_entrega, $status_inicial, $metodo_pagamento, $troco_para]);
        $pedido_id = $pdo->lastInsertId();

        $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco, observacao) VALUES (?, ?, ?, ?, ?)");
        
        // Salva os itens usando o array já processado
        foreach ($itens_para_resumo as $item_id => $item_resumo) {
            $produto_info = $produtos_db[$item_resumo['produto_id']];
            $preco_opcoes = 0;
            if(!empty($item_resumo['nomes_opcoes'])){
                foreach($carrinho[$item_id]['opcoes_selecionadas'] as $opcao_id){
                    $preco_opcoes += $opcoes_db[$opcao_id]['preco_adicional'];
                }
            }
            $preco_unitario_final = $produto_info['preco'] + $preco_opcoes;
            $stmt_item->execute([$pedido_id, $item_resumo['produto_id'], $item_resumo['quantidade'], $preco_unitario_final, $item_resumo['observacao']]);
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
                <?php if (empty($itens_para_resumo)): ?>
                    <li>Seu carrinho está vazio.</li>
                <?php else: 
                    foreach ($itens_para_resumo as $item): ?>
                    <li>
                        <div class="item-principal">
                           <span><?php echo $item['quantidade']; ?>x <?php echo htmlspecialchars($item['nome']); ?></span>
                           <span>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></span>
                        </div>
                        <?php if (!empty($item['nomes_opcoes'])): ?>
                            <small class="item-adicionais">+ Adicionais: <?php echo htmlspecialchars(implode(', ', $item['nomes_opcoes'])); ?></small>
                        <?php endif; ?>
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
            <form action="pedido.php" method="POST" id="form-pedido" class="form-wrapper">
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
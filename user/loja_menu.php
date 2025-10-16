<?php
// [CAMINHO CORRIGIDO]
require_once __DIR__ . '/../includes/header.php';

// Pega o ID da loja da URL
$loja_id = filter_input(INPUT_GET, 'loja_id', FILTER_VALIDATE_INT);
if (!$loja_id) {
    header('Location: index.php');
    exit;
}
$_SESSION['loja_id_visitada'] = $loja_id;

// --- LÓGICA ATUALIZADA PARA ADICIONAR AO CARRINHO (VIA MODAL) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart_modal'])) {
    $produto_id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
   // ESTA É A LINHA CORRETA
$observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';
    $opcoes_selecionadas = $_POST['opcoes'] ?? []; // Será um array de IDs das opções

    if ($produto_id && $quantidade > 0) {
        // Limpa o carrinho se o produto for de uma loja diferente
        if (isset($_SESSION['carrinho_loja_id']) && $_SESSION['carrinho_loja_id'] != $loja_id) {
            $_SESSION['carrinho'] = [];
        }
        $_SESSION['carrinho_loja_id'] = $loja_id;

        // Cria um ID único para o item no carrinho para permitir o mesmo produto com opções diferentes
        $item_id = uniqid($produto_id . '_');

        $_SESSION['carrinho'][$item_id] = [
            'produto_id' => $produto_id,
            'quantidade' => $quantidade,
            'observacao' => $observacao,
            'opcoes_selecionadas' => $opcoes_selecionadas, // Salva as opções
        ];
        
        // Redireciona para evitar reenvio do formulário
        header('Location: ' . $_SERVER['PHP_SELF'] . '?loja_id=' . $loja_id);
        exit;
    }
}

// --- BUSCA DE DADOS (PRODUTOS E OPÇÕES) ---
try {
    // Busca informações da loja e categorias
    $stmt_loja = $pdo->prepare("SELECT nome FROM lojas WHERE id = ?");
    $stmt_loja->execute([$loja_id]);
    $loja = $stmt_loja->fetch(PDO::FETCH_ASSOC);
    if (!$loja) { throw new Exception("Loja não encontrada."); }

    $stmt_cat = $pdo->prepare("SELECT id, nome FROM categorias WHERE loja_id = ? ORDER BY ordem, nome");
    $stmt_cat->execute([$loja_id]);
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // Busca produtos
    $stmt_prod = $pdo->prepare("SELECT p.*, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.loja_id = ? AND p.ativo = 1");
    $stmt_prod->execute([$loja_id]);
    $produtos_lista = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

    $produtos_por_categoria = [];
    foreach ($produtos_lista as $produto) {
        $produtos_por_categoria[$produto['categoria_nome']][] = $produto;
    }

    // Busca TODAS as opções de produtos da loja de uma vez só
    $stmt_opcoes = $pdo->prepare("SELECT * FROM produto_opcoes WHERE produto_id IN (SELECT id FROM produtos WHERE loja_id = ?)");
    $stmt_opcoes->execute([$loja_id]);
    $opcoes_lista = $stmt_opcoes->fetchAll(PDO::FETCH_ASSOC);

    // Organiza as opções por produto para facilitar o acesso no JavaScript
    $opcoes_por_produto = [];
    foreach ($opcoes_lista as $opcao) {
        $opcoes_por_produto[$opcao['produto_id']][] = $opcao;
    }

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="./loja_menu.css">

<main class="menu-container">
    <h1>Cardápio de <?= htmlspecialchars($loja['nome']) ?></h1>
    <a href="index.php">&larr; Voltar para a lista de lojas</a>

    <?php foreach ($categorias as $categoria): ?>
        <?php if (isset($produtos_por_categoria[$categoria['nome']])): ?>
            <section id="cat-<?= $categoria['id'] ?>" class="categoria-secao">
                <h2 class="categoria-titulo"><?= htmlspecialchars($categoria['nome']) ?></h2>
                
                <?php foreach ($produtos_por_categoria[$categoria['nome']] as $produto): ?>
                    <div class="produto-item" onclick='abrirModal(<?= json_encode($produto) ?>, <?= json_encode($opcoes_por_produto[$produto['id']] ?? []) ?>)'>
                        
                        <div class="produto-imagem">
                            <?php if (!empty($produto['foto'])): ?>
                                <img src="../uploads/produtos/<?= htmlspecialchars($produto['foto']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                            <?php else: ?>
                                <img src="https://placehold.co/100x100/f0f0f0/333?text=Foto" alt="Imagem não disponível">
                            <?php endif; ?>
                        </div>

                        <div class="produto-conteudo">
                            <div class="produto-info">
                                <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                                <p><?= htmlspecialchars($produto['descricao']) ?></p>
                            </div>
                            <div class="produto-acao">
                                <span class="produto-preco">A partir de R$ <?= number_format($produto['preco'], 2, ',', '.') ?></span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>
</main>

<div id="modal-produto" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <button class="modal-close" onclick="fecharModal()">&times;</button>
        <form id="form-modal-carrinho" method="POST" action="loja_menu.php?loja_id=<?= $loja_id ?>">
            
            <input type="hidden" name="produto_id" id="modal-produto-id">
            
            <div class="modal-header">
                <img id="modal-produto-imagem" src="" alt="Imagem do produto" class="modal-imagem">
                <div class="modal-titulo-desc">
                    <h2 id="modal-produto-nome">Nome do Produto</h2>
                    <p id="modal-produto-descricao">Descrição do produto.</p>
                </div>
            </div>

            <div id="opcoes-container" class="modal-section">
                </div>

            <div class="modal-section">
                <label for="modal-observacao">Alguma observação?</label>
                <textarea id="modal-observacao" name="observacao" placeholder="Ex: tirar a cebola, ponto da carne, etc."></textarea>
            </div>

            <div class="modal-footer">
                <div class="quantidade-controle">
                    <button type="button" class="btn-qty" id="btn-diminuir-qty">-</button>
                    <input type="number" id="modal-quantidade" name="quantidade" value="1" min="1" readonly>
                    <button type="button" class="btn-qty" id="btn-aumentar-qty">+</button>
                </div>
                <button type="submit" name="add_to_cart_modal" id="btn-modal-adicionar" class="btn-add">
                    Adicionar <span>R$ 0,00</span>
                </button>
            </div>

        </form>
    </div>
</div>
<script>
const modal = document.getElementById('modal-produto');
const formModal = document.getElementById('form-modal-carrinho');

// Elementos do Modal
const modalProdutoId = document.getElementById('modal-produto-id');
const modalProdutoImagem = document.getElementById('modal-produto-imagem');
const modalProdutoNome = document.getElementById('modal-produto-nome');
const modalProdutoDescricao = document.getElementById('modal-produto-descricao');
const opcoesContainer = document.getElementById('opcoes-container');
const observacaoInput = document.getElementById('modal-observacao');
const quantidadeInput = document.getElementById('modal-quantidade');
const btnAdicionar = document.getElementById('btn-modal-adicionar');

let precoBase = 0;

function abrirModal(produto, opcoes) {
    precoBase = parseFloat(produto.preco);
    
    // Preenche os dados básicos do produto
    modalProdutoId.value = produto.id;
    modalProdutoNome.textContent = produto.nome;
    modalProdutoDescricao.textContent = produto.descricao;
    modalProdutoImagem.src = produto.foto ? `../uploads/produtos/${produto.foto}` : 'https://placehold.co/100x100/f0f0f0/333?text=Foto';

    // Limpa e cria as opções (adicionais)
    opcoesContainer.innerHTML = '';
    if (opcoes && opcoes.length > 0) {
        const tituloOpcoes = document.createElement('h3');
        tituloOpcoes.textContent = 'Adicionais';
        opcoesContainer.appendChild(tituloOpcoes);

        opcoes.forEach(opcao => {
            const div = document.createElement('div');
            div.className = 'opcao-item';
            
            const precoAdicionalFormatado = parseFloat(opcao.preco_adicional).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

            div.innerHTML = `
                <label>
                    <input type="checkbox" class="opcao-checkbox" name="opcoes[]" value="${opcao.id}" data-preco="${opcao.preco_adicional}">
                    <span>${opcao.nome}</span>
                </label>
                <span>+ ${precoAdicionalFormatado}</span>
            `;
            opcoesContainer.appendChild(div);
        });
    }

    // Reseta valores
    quantidadeInput.value = 1;
    observacaoInput.value = '';
    
    // Adiciona event listeners para recalcular o preço
    document.querySelectorAll('.opcao-checkbox').forEach(cb => {
        cb.addEventListener('change', atualizarSubtotalModal);
    });

    atualizarSubtotalModal();
    modal.style.display = 'flex';
}

function fecharModal() {
    modal.style.display = 'none';
}

function atualizarSubtotalModal() {
    let precoAdicionais = 0;
    document.querySelectorAll('.opcao-checkbox:checked').forEach(cb => {
        precoAdicionais += parseFloat(cb.dataset.preco);
    });
    
    const quantidade = parseInt(quantidadeInput.value);
    const subtotal = (precoBase + precoAdicionais) * quantidade;
    
    const subtotalFormatado = subtotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    btnAdicionar.querySelector('span').textContent = subtotalFormatado;
}

// Controles de quantidade
document.getElementById('btn-aumentar-qty').addEventListener('click', () => {
    quantidadeInput.value = parseInt(quantidadeInput.value) + 1;
    atualizarSubtotalModal();
});

document.getElementById('btn-diminuir-qty').addEventListener('click', () => {
    let qty = parseInt(quantidadeInput.value);
    if (qty > 1) {
        quantidadeInput.value = qty - 1;
        atualizarSubtotalModal();
    }
});

// Fechar modal ao clicar fora do conteúdo
window.onclick = function(event) {
    if (event.target == modal) {
        fecharModal();
    }
}
</script>

<?php
// [CAMINHO CORRIGIDO]
require_once __DIR__ . '/../includes/footer.php';
?>
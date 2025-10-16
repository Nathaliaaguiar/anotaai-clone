document.addEventListener('DOMContentLoaded', function() {
    console.log('PlataFood JS Carregado com Sucesso!');

    // --- ANIMAÇÃO DO CARRINHO FLUTUANTE ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('item_adicionado')) {
        const floatingCart = document.getElementById('floating-cart');
        if (floatingCart) {
            const animationElement = document.getElementById('add-to-cart-animation');
            animationElement.classList.add('show');
            setTimeout(() => {
                animationElement.classList.remove('show');
            }, 1500);
        }
    }

    // --- LÓGICA DE NOTIFICAÇÃO DE PEDIDO A CAMINHO (GIF) ---
    const alertaPedido = document.getElementById('alerta-pedido-caminho');
    const estaLogado = document.querySelector('a[href="perfil.php"]');
    if (alertaPedido && estaLogado) {
        const btnFecharAlerta = document.getElementById('fechar-alerta-pedido');
        btnFecharAlerta.addEventListener('click', () => {
            alertaPedido.style.display = 'none';
        });
        setInterval(function() {
            fetch('/anotaai-clone/user/verificar_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'saiu_para_entrega') {
                        const notificacaoMostrada = sessionStorage.getItem('notificacao_pedido_' + data.pedido_id);
                        if (!notificacaoMostrada) {
                            alertaPedido.style.display = 'flex';
                            sessionStorage.setItem('notificacao_pedido_' + data.pedido_id, 'true');
                        }
                    }
                })
                .catch(error => console.error('Erro ao verificar status:', error));
        }, 20000);
    }

    // --- LÓGICA DO MODAL DE OBSERVAÇÃO E OPÇÕES (RESTAURADA) ---
    const modal = document.getElementById('modal-observacao');
    if (modal) {
        const botoesAbrirModal = document.querySelectorAll('.btn-abrir-modal');
        const botaoFecharModal = document.querySelector('.close-modal');
        const modalProdutoNome = document.getElementById('modal-produto-nome');
        const modalProdutoId = document.getElementById('modal-produto-id');
        const modalCategoriaId = document.getElementById('modal-categoria-id');
        const modalObservacaoTextarea = document.getElementById('observacao');
        const modalQuantidadeInput = document.getElementById('quantidade');
        const modalOpcoesContainer = document.getElementById('modal-opcoes-container');
        const modalPrecoTotal = document.getElementById('modal-preco-total');
        const modalOpcaoId = document.getElementById('modal-opcao-id');
        let precoBase = 0;

        function formatarPreco(valor) {
            return `R$ ${valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        function calcularPrecoTotal() {
            let precoAdicional = 0;
            const opcaoSelecionada = document.querySelector('input[name="opcao_produto"]:checked');
            if (opcaoSelecionada && opcaoSelecionada.value) {
                precoAdicional = parseFloat(opcaoSelecionada.dataset.preco);
                modalOpcaoId.value = opcaoSelecionada.value;
            } else {
                modalOpcaoId.value = '';
            }
            const quantidade = parseInt(modalQuantidadeInput.value) || 1;
            const precoFinal = (precoBase + precoAdicional) * quantidade;
            modalPrecoTotal.textContent = formatarPreco(precoFinal);
        }

        botoesAbrirModal.forEach(botao => {
            botao.addEventListener('click', function() {
                const produtoId = this.dataset.id;
                const categoriaId = this.dataset.categoriaId;
                const produtoAtual = produtosData.find(p => p.id == produtoId);
                const opcoesDoProduto = opcoesData[produtoId] || [];
                if (!produtoAtual) return;
                modalProdutoNome.textContent = produtoAtual.nome;
                modalProdutoId.value = produtoAtual.id;
                modalCategoriaId.value = categoriaId;
                precoBase = parseFloat(produtoAtual.preco);
                modalOpcoesContainer.innerHTML = '';
                if (opcoesDoProduto.length > 0) {
                    let opcoesHtml = `<div class="form-group"><h4>Escolha um adicional:</h4><label class="radio-label selected"><input type="radio" name="opcao_produto" value="" data-preco="0" checked>Padrão <span>(sem adicional)</span></label>`;
                    opcoesDoProduto.forEach(opcao => {
                        const precoAdicionalTexto = `+ R$ ${parseFloat(opcao.preco_adicional).toFixed(2).replace('.', ',')}`;
                        opcoesHtml += `<label class="radio-label"><input type="radio" name="opcao_produto" value="${opcao.id}" data-preco="${opcao.preco_adicional}">${opcao.nome_opcao} <span>(${precoAdicionalTexto})</span></label>`;
                    });
                    opcoesHtml += '</div>';
                    modalOpcoesContainer.innerHTML = opcoesHtml;
                }
                modalObservacaoTextarea.value = '';
                modalQuantidadeInput.value = 1;
                calcularPrecoTotal();
                const radiosDeOpcao = modalOpcoesContainer.querySelectorAll('input[name="opcao_produto"]');
                radiosDeOpcao.forEach(radio => {
                    radio.addEventListener('change', function() {
                        modalOpcoesContainer.querySelectorAll('.radio-label').forEach(label => label.classList.remove('selected'));
                        if (this.checked) { this.parentElement.classList.add('selected'); }
                    });
                });
                modalOpcoesContainer.removeEventListener('change', calcularPrecoTotal);
                modalQuantidadeInput.removeEventListener('input', calcularPrecoTotal);
                modalOpcoesContainer.addEventListener('change', calcularPrecoTotal);
                modalQuantidadeInput.addEventListener('input', calcularPrecoTotal);
                modal.style.display = 'block';
            });
        });

        if (botaoFecharModal) { botaoFecharModal.addEventListener('click', () => modal.style.display = 'none'); }
        window.addEventListener('click', (event) => { if (event.target == modal) modal.style.display = 'none'; });
    }

    // --- OUTRAS LÓGICAS (RESTAURADAS) ---
    document.querySelectorAll('.btn-remover').forEach(botao => {
        botao.addEventListener('click', (event) => {
            if (!confirm('Você tem certeza que deseja remover este item?')) {
                event.preventDefault();
            }
        });
    });

    const metodosPagamentoRadios = document.querySelectorAll('input[name="metodo_pagamento"]');
    if (metodosPagamentoRadios.length > 0) {
        const campoTroco = document.getElementById('campo-troco');
        const labelsPagamento = document.querySelectorAll('.radio-label');
        metodosPagamentoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                labelsPagamento.forEach(label => label.classList.remove('selected'));
                if (this.checked) this.parentElement.classList.add('selected');
                if (campoTroco) campoTroco.style.display = this.value === 'dinheiro' ? 'block' : 'none';
            });
        });
    }

    const hamburgerBtn = document.getElementById('hamburger-btn');
    const navLinks = document.getElementById('nav-links');
    if (hamburgerBtn && navLinks) {
        hamburgerBtn.addEventListener('click', () => {
            hamburgerBtn.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
    }
});
// (dentro do seu document.addEventListener('DOMContentLoaded', function() { ... })

// (dentro do seu document.addEventListener('DOMContentLoaded', function() { ... })

// --- LÓGICA DE NOTIFICAÇÃO PERSISTENTE - VERSÃO FINAL ---
const toast = document.getElementById('toast-notificacao');
// Verifica se o usuário está logado (procurando por um elemento que só logados veem)
const estaLogado = document.querySelector('a[href*="perfil.php"]'); 

if (toast && estaLogado) {
    const btnFecharToast = document.getElementById('toast-fechar');

    // Evento para fechar o toast manualmente (ele pode reaparecer na próxima verificação)
    btnFecharToast.addEventListener('click', () => {
        toast.classList.remove('show');
    });

    // Função que verifica o status no servidor
    function verificarStatus() {
        // A URL deve ser o caminho absoluto para o seu arquivo
        fetch('/anotaai-clone/user/verificar_status.php')
            .then(response => response.json())
            .then(data => {
                // Se o status retornado for 'saiu_para_entrega', mostra o toast.
                if (data.status === 'saiu_para_entrega') {
                    toast.classList.add('show');
                } else {
                    // Se for qualquer outro status (incluindo 'nenhum'), esconde o toast.
                    toast.classList.remove('show');
                }
            })
            .catch(error => console.error('Erro ao verificar status do pedido:', error));
    }

    // Chama a função a cada 15 segundos para uma resposta mais rápida
    setInterval(verificarStatus, 15000); // 15000ms = 15 segundos
    
    // Chama uma vez logo ao carregar a página para verificação imediata
    verificarStatus(); 
}
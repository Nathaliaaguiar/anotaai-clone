document.addEventListener('DOMContentLoaded', function() {
    console.log('PlataFood JS Carregado com Sucesso!');

    // --- LÓGICA DO MODAL DE OBSERVAÇÃO DE PRODUTO ---
    const modal = document.getElementById('modal-observacao');
    const botoesAbrirModal = document.querySelectorAll('.btn-abrir-modal');
    const botaoFecharModal = document.querySelector('.close-modal');
    
    if (modal && botoesAbrirModal.length > 0) {
        const modalProdutoNome = document.getElementById('modal-produto-nome');
        const modalProdutoId = document.getElementById('modal-produto-id');
        const modalCategoriaId = document.getElementById('modal-categoria-id');
        const modalObservacaoTextarea = document.getElementById('observacao');
        const modalQuantidadeInput = document.getElementById('quantidade');

        // Adiciona o evento para cada botão "Adicionar"
        botoesAbrirModal.forEach(botao => {
            botao.addEventListener('click', function() {
                // Pega os dados do botão clicado
                const produtoId = this.dataset.id;
                const produtoNome = this.dataset.nome;
                const categoriaId = this.dataset.categoriaId;

                // Preenche os campos do modal
                modalProdutoNome.textContent = produtoNome;
                modalProdutoId.value = produtoId;
                modalCategoriaId.value = categoriaId;
                
                modalObservacaoTextarea.value = '';
                modalQuantidadeInput.value = 1;

                modal.style.display = 'block';
            });
        });

        // Evento para fechar o modal no 'X'
        if (botaoFecharModal) {
            botaoFecharModal.addEventListener('click', () => modal.style.display = 'none');
        }

        // Evento para fechar o modal clicando fora
        window.addEventListener('click', (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }

    // --- LÓGICA DE CONFIRMAÇÃO PARA REMOVER ITEM DO CARRINHO ---
    const botoesRemover = document.querySelectorAll('.btn-remover');
    botoesRemover.forEach(botao => {
        botao.addEventListener('click', (event) => {
            if (!confirm('Você tem certeza que deseja remover este item?')) {
                event.preventDefault();
            }
        });
    });

    // --- LÓGICA DO FORMULÁRIO DE PAGAMENTO (TROCO E ESTILO) ---
    const metodosPagamentoRadios = document.querySelectorAll('input[name="metodo_pagamento"]');
    if (metodosPagamentoRadios.length > 0) {
        const campoTroco = document.getElementById('campo-troco');
        const labelsPagamento = document.querySelectorAll('.radio-label');

        metodosPagamentoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                labelsPagamento.forEach(label => label.classList.remove('selected'));
                if (this.checked) {
                    this.parentElement.classList.add('selected');
                }
                
                // Verifica se o campo de troco existe na página atual antes de manipulá-lo
                if (campoTroco) { 
                    campoTroco.style.display = this.value === 'dinheiro' ? 'block' : 'none';
                }
            });
        });
    }

    // --- LÓGICA PARA O MENU HAMBÚRGUER RESPONSIVO ---
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const navLinks = document.getElementById('nav-links');
    if (hamburgerBtn && navLinks) {
        hamburgerBtn.addEventListener('click', () => {
            // Adiciona/remove a classe 'active' TANTO no botão QUANTO nos links
            hamburgerBtn.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
    }

}); // FIM DO DOMCONTENTLOADED
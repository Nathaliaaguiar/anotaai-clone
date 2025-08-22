document.addEventListener('DOMContentLoaded', function() {
    console.log('PlataFood JS Carregado com Sucesso!');

    // --- LÓGICA DO MODAL DE OBSERVAÇÃO DE PRODUTO ---
    const modal = document.getElementById('modal-observacao');
    const botoesAbrirModal = document.querySelectorAll('.btn-abrir-modal');
    const botaoFecharModal = document.querySelector('.close-modal');
    
    if (modal && botoesAbrirModal.length > 0) {
        const modalProdutoNome = document.getElementById('modal-produto-nome');
        const modalProdutoId = document.getElementById('modal-produto-id');
        const modalObservacaoTextarea = document.getElementById('observacao');
        const modalQuantidadeInput = document.getElementById('quantidade');

        // Adiciona o evento para cada botão "Adicionar"
        botoesAbrirModal.forEach(botao => {
            botao.addEventListener('click', function() {
                const produtoId = this.dataset.id;
                const produtoNome = this.dataset.nome;

                modalProdutoNome.textContent = produtoNome;
                modalProdutoId.value = produtoId;
                
                modalObservacaoTextarea.value = '';
                modalQuantidadeInput.value = 1;

                modal.style.display = 'block';
            });
        });

        // Evento para fechar o modal no 'X'
        if (botaoFecharModal) {
            botaoFecharModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }

        // Evento para fechar o modal clicando fora
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }

    // --- LÓGICA DE CONFIRMAÇÃO PARA REMOVER ITEM DO CARRINHO ---
    const botoesRemover = document.querySelectorAll('.btn-remover');
    botoesRemover.forEach(botao => {
        botao.addEventListener('click', function(event) {
            if (!confirm('Você tem certeza que deseja remover este item?')) {
                event.preventDefault();
            }
        });
    });

    // --- LÓGICA DO FORMULÁRIO DE PAGAMENTO (TROCO E ESTILO) ---
    const metodosPagamentoRadios = document.querySelectorAll('input[name="metodo_pagamento"]');
    const campoTroco = document.getElementById('campo-troco');
    const inputTroco = document.getElementById('troco_para');
    const labelsPagamento = document.querySelectorAll('.radio-label');

    if (metodosPagamentoRadios.length > 0 && campoTroco) {
        metodosPagamentoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove a classe 'selected' de todos os labels
                labelsPagamento.forEach(label => {
                    label.classList.remove('selected');
                });
                
                // Adiciona a classe 'selected' no label do radio que foi clicado
                if (this.checked) {
                    this.parentElement.classList.add('selected');
                }

                // Mostra ou esconde o campo de troco
                if (this.value === 'dinheiro') {
                    campoTroco.style.display = 'block';
                } else {
                    campoTroco.style.display = 'none';
                    if (inputTroco) {
                        inputTroco.value = ''; 
                    }
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


document.addEventListener('DOMContentLoaded', function() {
    console.log('Anota Aí Clone carregado com sucesso!');

    // --- Lógica do Modal de Observação ---
    const modal = document.getElementById('modal-observacao');
    const botoesAbrirModal = document.querySelectorAll('.btn-abrir-modal');
    const botaoFecharModal = document.querySelector('.close-modal');
    const modalProdutoNome = document.getElementById('modal-produto-nome');
    const modalProdutoId = document.getElementById('modal-produto-id');
    const modalObservacaoTextarea = document.getElementById('observacao');
    const modalQuantidadeInput = document.getElementById('quantidade');

    // Abre o modal ao clicar no botão "Adicionar" do card
    botoesAbrirModal.forEach(botao => {
        botao.addEventListener('click', function() {
            const produtoId = this.dataset.id;
            const produtoNome = this.dataset.nome;

            // Preenche o modal com as informações do produto
            modalProdutoNome.textContent = produtoNome;
            modalProdutoId.value = produtoId;
            
            // Limpa os campos ao abrir
            modalObservacaoTextarea.value = '';
            modalQuantidadeInput.value = 1;

            modal.style.display = 'block';
        });
    });

    // Fecha o modal ao clicar no 'X'
    if (botaoFecharModal) {
        botaoFecharModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Fecha o modal ao clicar fora da área de conteúdo
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });


    // --- Lógica de Confirmação para Remover Item ---
    const botoesRemover = document.querySelectorAll('.btn-remover');
    botoesRemover.forEach(botao => {
        botao.addEventListener('click', function(event) {
            if (!confirm('Você tem certeza que deseja remover este item?')) {
                event.preventDefault();
            }
        });
    });
});



// --- CÓDIGO DE PAGAMENTO ATUALIZADO ---
    const metodosPagamentoRadios = document.querySelectorAll('input[name="metodo_pagamento"]');
    const campoTroco = document.getElementById('campo-troco');
    const inputTroco = document.getElementById('troco_para');
    
    // Pega todos os labels para poder adicionar/remover a classe de estilo
    const labelsPagamento = document.querySelectorAll('.radio-label');

    if (metodosPagamentoRadios.length > 0) {
        metodosPagamentoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Primeiro, remove a classe 'selected' de todos os labels
                labelsPagamento.forEach(label => {
                    label.classList.remove('selected');
                });
                
                // Adiciona a classe 'selected' apenas no label do radio que foi clicado
                if (this.checked) {
                    this.parentElement.classList.add('selected');
                }

                // Lógica para mostrar/esconder o campo de troco
                if (this.value === 'dinheiro') {
                    campoTroco.style.display = 'block';
                    inputTroco.required = true;
                } else {
                    campoTroco.style.display = 'none';
                    inputTroco.required = false;
                    inputTroco.value = ''; 
                }
            });
        });
    }
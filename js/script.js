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
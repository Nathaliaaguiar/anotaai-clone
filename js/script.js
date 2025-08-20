document.addEventListener('DOMContentLoaded', function() {
    console.log('Anota Aí Clone carregado com sucesso!');

    // Exemplo: Confirmação antes de remover
    const botoesRemover = document.querySelectorAll('.btn-remover');
    botoesRemover.forEach(botao => {
        botao.addEventListener('click', function(event) {
            if (!confirm('Você tem certeza que deseja remover este item?')) {
                event.preventDefault();
            }
        });
    });
});
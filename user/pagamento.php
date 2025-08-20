<?php
require_once __DIR__ . '/../includes/header.php';

// Proteção: usuário precisa estar logado e ter um ID de pedido na URL
if (!isset($_SESSION['usuario_id']) || !isset($_GET['pedido_id'])) {
    header('Location: index.php');
    exit;
}

$pedido_id = filter_var($_GET['pedido_id'], FILTER_VALIDATE_INT);
$usuario_id = $_SESSION['usuario_id'];

// Busca os detalhes do pedido para garantir que ele pertence ao usuário logado
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$pedido_id, $usuario_id]);
$pedido = $stmt->fetch();

// Se o pedido não for encontrado ou não for PIX, redireciona
if (!$pedido || $pedido['metodo_pagamento'] != 'pix') {
    header('Location: perfil.php');
    exit;
}

// ---- CORREÇÃO APLICADA AQUI ----
// Busca os dados do usuário para usar no código PIX
$stmt_usuario = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch();
// ---- FIM DA CORREÇÃO ----

// --- SIMULAÇÃO DA GERAÇÃO PIX ---
// Em um sistema real, aqui você faria uma chamada para uma API de pagamentos.
// Para nossa simulação, vamos gerar um código aleatório convincente.
$nome_cliente_sem_espaco = $usuario ? str_replace(' ', '', $usuario['nome']) : 'CLIENTE';
$pix_copia_e_cola = "00020126580014br.gov.bcb.pix0136" . uniqid() . "5204000053039865802BR5913" . $nome_cliente_sem_espaco . "6009SAO PAULO62070503***6304" . strtoupper(bin2hex(random_bytes(4)));

// Usaremos um serviço gratuito para gerar o QR Code a partir do nosso texto
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($pix_copia_e_cola);

?>

<section class="pagina-pagamento">
    <h1>Finalize seu Pagamento via PIX</h1>
    <div class="pagamento-box">
        <p>Seu pedido nº <strong><?php echo $pedido['id']; ?></strong> foi registrado! Para confirmá-lo, faça o pagamento abaixo.</p>
        
        <div class="total-a-pagar">
            <span>Total a Pagar:</span>
            <strong>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
        </div>

        <div class="pix-container">
            <h3>Pague com o QR Code</h3>
            <p>Abra o app do seu banco e escaneie a imagem abaixo.</p>
            <img src="<?php echo $qr_code_url; ?>" alt="QR Code PIX" class="qr-code-img">

            <h3>Ou pague com PIX Copia e Cola</h3>
            <p>Clique no botão para copiar o código e pague no app do seu banco.</p>
            <div class="copia-e-cola-wrapper">
                <textarea id="pix-codigo" readonly><?php echo htmlspecialchars($pix_copia_e_cola); ?></textarea>
                <button id="btn-copiar-pix" class="btn">Copiar Código</button>
            </div>
        </div>

        <div class="aviso-pagamento">
            <p><strong>Atenção:</strong> O preparo do seu pedido começará após a confirmação do pagamento.</p>
            <a href="perfil.php" class="link-discreto">Já paguei, ver meus pedidos</a>
        </div>
    </div>
</section>

<script>
// JavaScript para o botão "Copiar"
document.addEventListener('DOMContentLoaded', function() {
    const btnCopiar = document.getElementById('btn-copiar-pix');
    const textoPix = document.getElementById('pix-codigo');

    if (btnCopiar) {
        btnCopiar.addEventListener('click', function() {
            textoPix.select();
            // A API de Clipboard é mais moderna e segura que execCommand
            navigator.clipboard.writeText(textoPix.value).then(() => {
                btnCopiar.textContent = 'Copiado!';
                setTimeout(() => {
                    btnCopiar.textContent = 'Copiar Código';
                }, 2000);
            }).catch(err => {
                console.error('Erro ao copiar texto: ', err);
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
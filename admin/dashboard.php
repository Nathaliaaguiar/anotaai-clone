<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

$mensagem = ''; // Variável para feedback ao usuário

// Lógica para processar o upload da LOGO DA LOJA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_loja'])) {
    $arquivo = $_FILES['logo_loja'];

    // Verifica se houve erro no upload
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $nome_temporario = $arquivo['tmp_name'];
        // O destino agora é um arquivo específico para a loja
        $destino = __DIR__ . '/../img/logo_loja.png'; 

        // Tipos de imagem permitidos
        $tipos_permitidos = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
        if (in_array($arquivo['type'], $tipos_permitidos)) {
            // Move o arquivo para o destino
            if (move_uploaded_file($nome_temporario, $destino)) {
                $mensagem = '<p class="success">Logo da loja atualizada com sucesso!</p>';
            } else {
                $mensagem = '<p class="error">Erro ao mover o arquivo para o destino.</p>';
            }
        } else {
            $mensagem = '<p class="error">Formato de arquivo não permitido. Use PNG, JPG, GIF ou SVG.</p>';
        }
    } else {
        $mensagem = '<p class="error">Ocorreu um erro no upload da imagem.</p>';
    }
}


// Contadores
$pedidos_hoje = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE DATE(data) = CURDATE()")->fetchColumn();
$clientes_total = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$produtos_ativos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();
?>

<section class="dashboard">
    <h1>Dashboard</h1>
    
    <?php echo $mensagem; // Exibe a mensagem de sucesso ou erro ?>

    <div class="stats-grid">
        <div class="stat-card">
            <h2>Pedidos Hoje</h2>
            <p><?php echo $pedidos_hoje; ?></p>
        </div>
        <div class="stat-card">
            <h2>Clientes Totais</h2>
            <p><?php echo $clientes_total; ?></p>
        </div>
        <div class="stat-card">
            <h2>Produtos Ativos</h2>
            <p><?php echo $produtos_ativos; ?></p>
        </div>
    </div>

    <div class="form-wrapper" style="margin-top: 2rem;">
        <h2>Alterar Logo da Loja (Para Clientes)</h2>
        <p>Envie a imagem que aparecerá para os clientes no site.</p>
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="logo_loja">Selecione a imagem da logo:</label>
                <input type="file" name="logo_loja" id="logo_loja" required>
            </div>
            <button type="submit" class="btn">Salvar Logo da Loja</button>
        </form>
    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// ADICIONADO: URL base para que os links sempre funcionem
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/anotaai-clone";

// ADICIONADO: Lógica para identificar a loja que o usuário está visitando
$loja_id_visitada = $_SESSION['loja_id_visitada'] ?? 0;
$nome_da_loja = "Escolha uma Loja";

if ($loja_id_visitada > 0) {
    // Busca o nome da loja específica
    $stmt_nome_loja = $pdo->prepare("SELECT valor FROM configuracoes WHERE loja_id = ? AND chave = 'nome_loja'");
    $stmt_nome_loja->execute([$loja_id_visitada]);
    $nome_loja_db = $stmt_nome_loja->fetchColumn();
    if ($nome_loja_db) { 
        $nome_da_loja = $nome_loja_db; 
    } else {
        $stmt_loja_original = $pdo->prepare("SELECT nome FROM lojas WHERE id = ?");
        $stmt_loja_original->execute([$loja_id_visitada]);
        $nome_da_loja = $stmt_loja_original->fetchColumn() ?: 'Loja Indisponível';
    }
}
$total_itens_carrinho = count($_SESSION['carrinho'] ?? []);

// MODIFICADO: A função agora recebe o ID da loja para verificar o status correto
function get_status_loja($pdo, $loja_id) {
    if ($loja_id == 0) return ['status' => 'fechado', 'texto' => 'Fechado'];
    date_default_timezone_set('America/Sao_Paulo');
    $dia_semana_atual = date('w');
    $hora_atual = date('H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ? AND loja_id = ?");
    $stmt->execute([$dia_semana_atual, $loja_id]);
    $horario_hoje = $stmt->fetch();
    if ($horario_hoje && $horario_hoje['ativo'] && ($hora_atual >= $horario_hoje['horario_abertura'] && $hora_atual <= $horario_hoje['horario_fechamento'])) {
        return ['status' => 'aberto', 'texto' => 'Aberto agora'];
    }
    return ['status' => 'fechado', 'texto' => 'Fechado no momento'];
}
$status_loja = get_status_loja($pdo, $loja_id_visitada);

function is_active($page_name) {
    return basename($_SERVER['PHP_SELF']) == $page_name ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nome_da_loja); ?> - Delivery</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="user-page">
    <div id="alerta-pedido-caminho" class="alerta-pedido">
        <div class="alerta-pedido-content">
            <img src="<?php echo $base_url; ?>/img/delivery.gif" alt="Pedido a caminho">
            <h2>Seu pedido saiu para entrega!</h2>
            <p>Fique atento, nosso entregador chegará em breve.</p>
            <button id="fechar-alerta-pedido" class="btn">OK</button>
        </div>
    </div>
    <a href="<?php echo $base_url; ?>/user/carrinho.php" id="floating-cart" class="floating-cart">
        <i class="fas fa-shopping-bag"></i>
        <span id="cart-counter" class="cart-counter"><?php echo $total_itens_carrinho; ?></span>
        <div id="add-to-cart-animation" class="add-to-cart-animation">🎉 +1</div>
    </a>
    <header class="site-header">
        <div class="container">
            <a href="<?php echo $base_url; ?>/user/index.php?id=<?php echo $loja_id_visitada; ?>" class="logo">
                <?php
                $url_logo_para_img = $base_url . '/img/logo_loja_' . $loja_id_visitada . '.png';
                if ($loja_id_visitada > 0 && file_exists(__DIR__ . '/../img/logo_loja_' . $loja_id_visitada . '.png')): ?>
                    <img src="<?php echo $url_logo_para_img; ?>?v=<?php echo time(); ?>" alt="Logo de <?php echo htmlspecialchars($nome_da_loja); ?>" class="store-logo-img">
                <?php else: ?>
                    <img src="<?php echo $base_url . '/img/logo_loja.png'; ?>" alt="Logo" class="store-logo-img">
                <?php endif; ?>
                <span class="store-name"><?php echo htmlspecialchars($nome_da_loja); ?></span>
            </a>
            <div class="status-loja status-<?php echo $status_loja['status']; ?>"><span><?php echo $status_loja['texto']; ?></span></div>
            <nav id="nav-menu">
                <button id="hamburger-btn">
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </button>
                <ul id="nav-links">
                    <li><a href="<?php echo $base_url; ?>/user/index.php?id=<?php echo $loja_id_visitada; ?>" class="<?php echo is_active('index.php'); ?>">Cardápio</a></li>
                    <li><a href="<?php echo $base_url; ?>/user/carrinho.php" class="<?php echo is_active('carrinho.php'); ?>">Carrinho (<?php echo $total_itens_carrinho; ?>)</a></li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li><a href="<?php echo $base_url; ?>/user/perfil.php" class="nav-button <?php echo is_active('perfil.php'); ?>">Meu Perfil</a></li>
                        <li><a href="<?php echo $base_url; ?>/user/logout.php">Sair</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>/user/login.php" class="nav-button <?php echo is_active('login.php'); ?>">Entrar / Cadastrar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
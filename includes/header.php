<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// URL base do projeto
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
    . "://" . $_SERVER['HTTP_HOST'] . "/anotaai-clone";

// 🔹 Detectar loja visitada (sessão ou URL)
$loja_id_visitada = $_GET['loja_id'] ?? ($_SESSION['loja_id_visitada'] ?? 0);

// 🔹 Se a pessoa entrou em uma loja (tem loja_id na URL)
if (!empty($_GET['loja_id'])) {
    $_SESSION['loja_id_visitada'] = (int) $_GET['loja_id'];
    $loja_id_visitada = $_SESSION['loja_id_visitada'];
}

// 🔹 Se o usuário acessou o index (sem loja_id na URL), limpar a sessão
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'index.php' && empty($_GET['loja_id'])) {
    unset($_SESSION['loja_id_visitada']);
    $loja_id_visitada = 0;
}

// 🔹 Padrão inicial (modo PlataFood)
$nome_da_loja = "PlataFood";
$url_logo_para_img = $base_url . "/img/logoplatafood.png"; // logo padrão
$status_loja = ['status' => '', 'texto' => ''];

// 🔹 Se estiver em loja, trocar nome/logo/status
if ($loja_id_visitada > 0) {
    $stmt_nome_loja = $pdo->prepare("SELECT nome FROM lojas WHERE id = ?");
    $stmt_nome_loja->execute([$loja_id_visitada]);
    $nome_loja_db = $stmt_nome_loja->fetchColumn();

    if ($nome_loja_db) {
        $nome_da_loja = $nome_loja_db;

        // Verifica se a loja tem logo própria
        $logo_loja_path = __DIR__ . '/../img/logo_loja_' . $loja_id_visitada . '.png';
        if (file_exists($logo_loja_path)) {
            $url_logo_para_img = $base_url . '/img/logo_loja_' . $loja_id_visitada . '.png?v=' . time();
        }

        // Verifica status (aberta/fechada)
        function get_status_loja($pdo, $loja_id) {
            date_default_timezone_set('America/Sao_Paulo');
            $dia_semana_atual = date('w');
            $hora_atual = date('H:i:s');

            $stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ? AND loja_id = ?");
            $stmt->execute([$dia_semana_atual, $loja_id]);
            $horario_hoje = $stmt->fetch();

            if ($horario_hoje && $horario_hoje['ativo'] && 
                ($hora_atual >= $horario_hoje['horario_abertura'] && $hora_atual <= $horario_hoje['horario_fechamento'])) {
                return ['status' => 'aberto', 'texto' => 'ABERTA'];
            }
            return ['status' => 'fechado', 'texto' => 'FECHADA'];
        }

        $status_loja = get_status_loja($pdo, $loja_id_visitada);
    }
}

// 🔹 Carrinho
$total_itens_carrinho = count($_SESSION['carrinho'] ?? []);

// 🔹 Função para menu ativo
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
<body>
    <div id="toast-notificacao" class="toast-notificacao">
        <div class="toast-conteudo">
            <img src="../img/delivery.gif" alt="Ícone de entrega" class="toast-icone">
            <div class="toast-texto">
                <strong>Seu pedido saiu para entrega!</strong>
                <span>O entregador já está a caminho.</span>
            </div>
        </div>
        <button id="toast-fechar" class="toast-fechar">&times;</button>
    </div>
    <header>
        </header>
<body class="user-page">

    <!-- Carrinho flutuante -->
    <a href="<?php echo $base_url; ?>/user/carrinho.php" id="floating-cart" class="floating-cart">
        <i class="fas fa-shopping-bag"></i>
        <span id="cart-counter" class="cart-counter"><?php echo $total_itens_carrinho; ?></span>
        <div id="add-to-cart-animation" class="add-to-cart-animation">🎉 +1</div>
    </a>

    <!-- Cabeçalho -->
    <header class="site-header">
        <div class="container header-container">
            <a href="<?php echo $base_url; ?>/user/index.php" class="logo">
                <img src="<?php echo $url_logo_para_img; ?>" 
                     alt="Logo <?php echo htmlspecialchars($nome_da_loja); ?>" 
                     class="store-logo-img">
                <span class="store-name"><?php echo htmlspecialchars($nome_da_loja); ?></span>
            </a>

            <?php if ($loja_id_visitada > 0): ?>
                <div class="status-loja status-<?php echo $status_loja['status']; ?>">
                    <span><?php echo $status_loja['texto']; ?></span>
                </div>
            <?php else: ?>
                
            <?php endif; ?>

            <nav id="nav-menu">
                <button id="hamburger-btn">
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </button>
                <ul id="nav-links">
                    <?php if ($loja_id_visitada > 0): ?>
                        <li><a href="<?php echo $base_url; ?>/user/index.php?loja_id=<?php echo $loja_id_visitada; ?>" 
                               class="<?php echo is_active('index.php'); ?>">Cardápio</a></li>
                        <li><a href="<?php echo $base_url; ?>/user/carrinho.php" 
                               class="<?php echo is_active('carrinho.php'); ?>">Carrinho (<?php echo $total_itens_carrinho; ?>)</a></li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario_id'])): ?>
                      <ul class="menu-links">
  <li><a href="<?php echo $base_url; ?>/index.php" 
         class="nav-button <?php echo is_active('index.php'); ?>">🏠 Início</a></li>
  <li><a href="<?php echo $base_url; ?>/user/perfil.php" 
         class="nav-button <?php echo is_active('perfil.php'); ?>">Meu Perfil</a></li>
  <li><a href="<?php echo $base_url; ?>/user/logout.php" 
         class="nav-button sair-btn">Sair</a></li>
</ul>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>/user/login.php" 
                               class="nav-button <?php echo is_active('login.php'); ?>">Entrar / Cadastrar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">

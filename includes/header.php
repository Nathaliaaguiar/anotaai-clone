<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Conecta ao banco
require_once __DIR__ . '/../config/db.php';

// URL base
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
    . "://" . $_SERVER['HTTP_HOST'] . "/anotaai-clone";

// 1. Detectar loja visitada
$loja_id_visitada = 0;
if (isset($_GET['loja_id']) && !empty($_GET['loja_id'])) {
    $loja_id_visitada = (int) $_GET['loja_id'];
    $_SESSION['loja_id_visitada'] = $loja_id_visitada;
} elseif (isset($_SESSION['loja_id_visitada'])) {
    $loja_id_visitada = $_SESSION['loja_id_visitada'];
}

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'index.php' && empty($_GET['loja_id'])) {
    unset($_SESSION['loja_id_visitada']);
    $loja_id_visitada = 0;
}

// 2. Definição da Logo e Nome
$nome_da_loja = "PlataFood";
$url_logo_para_img = "https://placehold.co/100x100/ff6f00/ffffff?text=PF"; 
$status_loja = ['status' => '', 'texto' => ''];

if ($loja_id_visitada > 0) {
    $stmt = $pdo->prepare("SELECT nome FROM lojas WHERE id = ?");
    $stmt->execute([$loja_id_visitada]);
    $loja_atual = $stmt->fetch();

    if ($loja_atual) {
        $nome_da_loja = htmlspecialchars($loja_atual['nome']);
        $caminho_fisico_logo = __DIR__ . '/../img/logo_loja_' . $loja_id_visitada . '.png';
        
        if (file_exists($caminho_fisico_logo)) {
            $url_logo_para_img = '../img/logo_loja_' . $loja_id_visitada . '.png?v=' . time();
        }

        if (!function_exists('get_status_loja')) {
            function get_status_loja($pdo, $loja_id) {
                date_default_timezone_set('America/Sao_Paulo');
                $dia = date('w');
                $hora = date('H:i:s');
                $stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ? AND loja_id = ?");
                $stmt->execute([$dia, $loja_id]);
                $h = $stmt->fetch();
                if ($h && $h['ativo'] && $hora >= $h['horario_abertura'] && $hora <= $h['horario_fechamento']) {
                    return ['status' => 'aberto', 'texto' => 'ABERTA'];
                }
                return ['status' => 'fechado', 'texto' => 'FECHADA'];
            }
        }
        $status_loja = get_status_loja($pdo, $loja_id_visitada);
    }
}

// 3. Contagem do Carrinho
$total_itens_carrinho = 0;
if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $total_itens_carrinho += $item['quantidade'];
    }
}

// --- NOVO: VERIFICAÇÃO DE ENTREGA EM ANDAMENTO ---
$pedido_saiu_entrega = false;
if (isset($_SESSION['usuario_id'])) {
    $stmt_entrega = $pdo->prepare("SELECT id FROM pedidos WHERE usuario_id = ? AND status = 'saiu_para_entrega' LIMIT 1");
    $stmt_entrega->execute([$_SESSION['usuario_id']]);
    if ($stmt_entrega->fetch()) {
        $pedido_saiu_entrega = true;
    }
}

if (!function_exists('is_active')) {
    function is_active($page) { return basename($_SERVER['PHP_SELF']) == $page ? 'active' : ''; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nome_da_loja; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/includes/header.css?v=3">
    <?php if(file_exists('css/index.css')): ?>
        <link rel="stylesheet" href="css/index.css?v=4">
    <?php endif; ?>
</head>
<body>

<?php if ($pedido_saiu_entrega): ?>
    <div id="deliveryToast" class="delivery-toast">
        <img src="../img/delivery.gif" alt="Moto" class="delivery-gif">
        <div class="delivery-content">
            <strong>Oba! Saiu para entrega 🛵</strong>
            <span>Seu pedido está chegando.</span>
        </div>
        <button class="btn-close-toast" onclick="document.getElementById('deliveryToast').style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<header class="user-header">
    <div class="header-container">
        
        <a href="index.php" class="header-logo">
            <img src="<?php echo $url_logo_para_img; ?>" alt="Logo">
            <?php if($loja_id_visitada == 0): ?>
                <span>Plata<em>Food</em></span>
            <?php else: ?>
                <span><?php echo $nome_da_loja; ?></span>
            <?php endif; ?>
        </a>

        <?php if ($loja_id_visitada > 0 && !empty($status_loja['texto'])): ?>
            <div style="margin-left: 15px; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; 
                background-color: <?php echo $status_loja['status'] == 'aberto' ? '#d4edda' : '#f8d7da'; ?>; 
                color: <?php echo $status_loja['status'] == 'aberto' ? '#155724' : '#721c24'; ?>;">
                <?php echo $status_loja['texto']; ?>
            </div>
        <?php endif; ?>

        <button class="mobile-toggle" onclick="toggleMenu()">
            <i class="fa-solid fa-bars"></i>
        </button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php" class="nav-link <?php echo is_active('index.php'); ?>"><i class="fa-solid fa-house"></i> Início</a></li>

            <?php if ($loja_id_visitada > 0): ?>
                <li><a href="loja_menu.php?loja_id=<?php echo $loja_id_visitada; ?>" class="nav-link <?php echo is_active('loja_menu.php'); ?>"><i class="fa-solid fa-utensils"></i> Cardápio</a></li>
                <li>
                    <a href="carrinho.php" class="nav-link cart-link <?php echo is_active('carrinho.php'); ?>">
                        <i class="fa-solid fa-bag-shopping"></i> Sacola
                        <?php if($total_itens_carrinho > 0): ?>
                            <span class="cart-badge"><?php echo $total_itens_carrinho; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <li><a href="perfil.php" class="nav-link <?php echo is_active('perfil.php'); ?>"><i class="fa-solid fa-user"></i> Minha Conta</a></li>
                <li><a href="logout.php" class="nav-link btn-sair"><i class="fa-solid fa-right-from-bracket"></i></a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link btn-login">Entrar</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<script>
    function toggleMenu() {
        document.getElementById('navMenu').classList.toggle('active');
    }
</script>

<main style="min-height: 80vh;">
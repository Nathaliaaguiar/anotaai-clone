<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// (Toda a lógica de buscar nome da loja, status, etc., permanece a mesma)
$stmt_nome_loja = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'nome_loja'");
$nome_da_loja = $stmt_nome_loja->fetchColumn() ?: 'PlataFood';

function get_status_loja($pdo) {
    date_default_timezone_set('America/Sao_Paulo');
    $dia_semana_atual = date('w');
    $hora_atual = date('H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ?");
    $stmt->execute([$dia_semana_atual]);
    $horario_hoje = $stmt->fetch();
    if ($horario_hoje && $horario_hoje['ativo'] && ($hora_atual >= $horario_hoje['horario_abertura'] && $hora_atual <= $horario_hoje['horario_fechamento'])) {
        return ['status' => 'aberto', 'texto' => 'Aberto agora'];
    }
    return ['status' => 'fechado', 'texto' => 'Fechado no momento'];
}
$status_loja = get_status_loja($pdo);

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/anotaai-clone/css/style.css">
</head>
<body class="user-page">
    <div id="alerta-pedido-caminho" class="alerta-pedido">
        <div class="alerta-pedido-content">
            <img src="/anotaai-clone/img/delivery.gif" alt="Pedido a caminho">
            <h2>Seu pedido saiu para entrega!</h2>
            <p>Fique atento, nosso entregador chegará em breve.</p>
            <button id="fechar-alerta-pedido" class="btn">OK</button>
        </div>
    </div>

    <header class="site-header">
        <div class="container">
            <a href="index.php" class="logo">
                <?php
                $url_logo_loja = '/anotaai-clone/img/logo_loja.png';
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $url_logo_loja)): ?>
                    <img src="<?php echo $url_logo_loja; ?>?v=<?php echo time(); ?>" alt="Logo de <?php echo htmlspecialchars($nome_da_loja); ?>" class="store-logo-img">
                <?php endif; ?>
                <span class="store-name"><?php echo htmlspecialchars($nome_da_loja); ?></span>
            </a>
            <div class="status-loja status-<?php echo $status_loja['status']; ?>">
                <span><?php echo $status_loja['texto']; ?></span>
            </div>
            <nav id="nav-menu">
                <button id="hamburger-btn">
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </button>
                <ul id="nav-links">
                    <li><a href="index.php" class="<?php echo is_active('index.php'); ?>">Cardápio</a></li>
                    <li><a href="carrinho.php" class="<?php echo is_active('carrinho.php'); ?>">
                        Carrinho (<?php echo count($_SESSION['carrinho'] ?? []); ?>)
                    </a></li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li><a href="perfil.php" class="nav-button <?php echo is_active('perfil.php'); ?>">Meu Perfil</a></li>
                        <li><a href="logout.php">Sair</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="nav-button <?php echo is_active('login.php'); ?>">Entrar / Cadastrar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
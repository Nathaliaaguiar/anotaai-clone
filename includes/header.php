<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Função para verificar a página ativa e aplicar uma classe CSS
function is_active($page_name) {
    // basename($_SERVER['PHP_SELF']) pega o nome do arquivo atual (ex: index.php)
    if (basename($_SERVER['PHP_SELF']) == $page_name) {
        return 'active';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlataFood - Delivery</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/anotaai-clone/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="index.php" class="logo">
                <?php
                // Lógica para exibir a logo da loja, se existir
                $caminho_logo_loja = $_SERVER['DOCUMENT_ROOT'] . '/anotaai-clone/img/logo_loja.png';
                $url_logo_loja = '/anotaai-clone/img/logo_loja.png';
                if (file_exists($caminho_logo_loja)): ?>
                    <img src="<?php echo $url_logo_loja; ?>?v=<?php echo time(); ?>" alt="PlataFood Logo" class="store-logo-img">
                <?php else: ?>
                    PlataFood
                <?php endif; ?>
            </a>
            
            <nav id="nav-menu">
                <button id="hamburger-btn">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
                <ul id="nav-links">
                    <li><a href="index.php" class="<?php echo is_active('index.php'); ?>">Cardápio</a></li>
                    <li><a href="carrinho.php" class="<?php echo is_active('carrinho.php'); ?>">
                        Carrinho (<?php echo isset($_SESSION['carrinho']) ? count($_SESSION['carrinho']) : 0; ?>)
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
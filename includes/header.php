<?php
session_start();
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anota Aí - Delivery</title>
    <link rel="stylesheet" href="/anotaai-clone/css/style.css">
</head>
<body>
    <header>
        <div class="container">
           <?php
// Define o caminho para a logo da loja e a URL
$caminho_logo_loja = $_SERVER['DOCUMENT_ROOT'] . '/anotaai-clone/img/logo.png';
$url_logo_loja = '/anotaai-clone/img/logo_loja.png';
?>
<a href="index.php" class="logo">
    <?php if (file_exists($caminho_logo_loja)): ?>
        <img src="<?php echo $url_logo_loja; ?>?v=<?php echo time(); ?>" alt="Logo da Loja" class="store-logo-img">
    <?php else: ?>
        Anota Aí Clone
    <?php endif; ?>
</a>
            <nav>
                <ul>
                    <li><a href="index.php">Cardápio</a></li>
                    <li><a href="carrinho.php">Carrinho (<?php echo isset($_SESSION['carrinho']) ? count($_SESSION['carrinho']) : 0; ?>)</a></li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li><a href="perfil.php">Meu Perfil</a></li>
                        <li><a href="logout.php">Sair</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Entrar / Cadastrar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
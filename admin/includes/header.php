<?php 
// admin/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db.php'; 

$nome_da_loja_admin = 'Painel Admin';
$logo_url = false; // Variável para controlar se tem logo ou não

if (isset($_SESSION['admin_loja_id'])) {
    $loja_id_admin = $_SESSION['admin_loja_id'];
    
    // 1. Busca o nome da loja
    $stmt_config = $pdo->prepare("SELECT valor FROM configuracoes WHERE loja_id = ? AND chave = 'nome_loja'");
    $stmt_config->execute([$loja_id_admin]);
    $nome_config = $stmt_config->fetchColumn();

    if ($nome_config) {
        $nome_da_loja_admin = $nome_config;
    } else {
        $stmt_loja = $pdo->prepare("SELECT nome FROM lojas WHERE id = ?");
        $stmt_loja->execute([$loja_id_admin]);
        $nome_original = $stmt_loja->fetchColumn();
        if ($nome_original) $nome_da_loja_admin = $nome_original;
    }

    // 2. VERIFICAÇÃO DE LOGO (NOVO CÓDIGO)
    // Caminho físico para o PHP checar se o arquivo existe
    $caminho_fisico = __DIR__ . '/../../img/logo_loja_' . $loja_id_admin . '.png';
    
    if (file_exists($caminho_fisico)) {
        // Caminho URL para o navegador mostrar (relativo a quem está na pasta admin)
        // Adicionamos ?v=time() para evitar que o navegador use o cache antigo
        $logo_url = '../img/logo_loja_' . $loja_id_admin . '.png?v=' . time();
    }
}

function is_admin_active($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nome_da_loja_admin); ?> - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

   
    <link rel="stylesheet" href="../css/header.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-page">
    
    <header class="admin-header">
        <div class="container header-content">
            <a href="dashboard.php" class="logo">
                <?php if ($logo_url): ?>
                    <img src="<?php echo $logo_url; ?>" alt="Logo" style="max-height: 40px; border-radius: 4px; object-fit: contain;">
                <?php else: ?>
                    <i class="fa-solid fa-store"></i> 
                <?php endif; ?>
                
                <span><?php echo htmlspecialchars($nome_da_loja_admin); ?></span>
            </a>

            <nav id="main-nav">
                <ul>
                    <li><a href="dashboard.php" class="<?php echo is_admin_active('dashboard.php'); ?>">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a></li>
                    
                    <li><a href="pedidos.php" class="<?php echo is_admin_active('pedidos.php'); ?>">
                        <i class="fa-solid fa-bell"></i> Pedidos
                    </a></li>

                    <li><a href="produtos.php" class="<?php echo is_admin_active('produtos.php'); ?>">
                        <i class="fa-solid fa-burger"></i> Produtos
                    </a></li>
                    
                    <li><a href="categorias.php" class="<?php echo is_admin_active('categorias.php'); ?>">
                        <i class="fa-solid fa-list"></i> Categorias
                    </a></li>
                    
                    <li><a href="entregas.php" class="<?php echo is_admin_active('entregas.php'); ?>">
                        <i class="fa-solid fa-motorcycle"></i> Entregas
                    </a></li>
                    
                    <li><a href="horarios.php" class="<?php echo is_admin_active('horarios.php'); ?>">
                        <i class="fa-solid fa-clock"></i> Horários
                    </a></li>
                    
                    <li><a href="clientes.php" class="<?php echo is_admin_active('clientes.php'); ?>">
                        <i class="fa-solid fa-user-gear"></i> Perfil
                    </a></li>

                    <li><a href="logout.php" class="btn-sair">
                        <i class="fa-solid fa-right-from-bracket"></i> Sair
                    </a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
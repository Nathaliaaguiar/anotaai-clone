<?php 
// admin/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db.php'; 

// --- ADICIONADO: Lógica para buscar o nome da loja ---
$nome_da_loja_admin = 'Painel Admin'; // Nome padrão
if (isset($_SESSION['admin_loja_id'])) {
    $loja_id_admin = $_SESSION['admin_loja_id'];
    
    // 1. Tenta buscar o nome personalizado que o admin salvou no dashboard
    $stmt_config = $pdo->prepare("SELECT valor FROM configuracoes WHERE loja_id = ? AND chave = 'nome_loja'");
    $stmt_config->execute([$loja_id_admin]);
    $nome_config = $stmt_config->fetchColumn();

    if ($nome_config) {
        $nome_da_loja_admin = $nome_config;
    } else {
        // 2. Se não houver, busca o nome original da loja cadastrada pelo Super Admin
        $stmt_loja = $pdo->prepare("SELECT nome FROM lojas WHERE id = ?");
        $stmt_loja->execute([$loja_id_admin]);
        $nome_original = $stmt_loja->fetchColumn();
        if ($nome_original) {
            $nome_da_loja_admin = $nome_original;
        }
    }
}

// ADICIONADO: Função para destacar o menu ativo
function is_admin_active($page_name) {
    return basename($_SERVER['PHP_SELF']) == $page_name ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nome_da_loja_admin); ?> - Painel Admin</title>
    
    <script>
        const tema = localStorage.getItem('adminTheme') || 'tema-claro';
        document.documentElement.className = tema;
    </script>

    <link rel="stylesheet" href="../css/style.css"> <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-page">
    <header class="admin-header">
        <div class="container">
            <a href="dashboard.php" class="logo"><?php echo htmlspecialchars($nome_da_loja_admin); ?></a>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="<?php echo is_admin_active('dashboard.php'); ?>">Dashboard</a></li>
                    <li><a href="produtos.php" class="<?php echo is_admin_active('produtos.php'); ?>">Produtos</a></li>
                    <li><a href="categorias.php" class="<?php echo is_admin_active('categorias.php'); ?>">Categorias</a></li>
                    <li><a href="pedidos.php" class="<?php echo is_admin_active('pedidos.php'); ?>">Pedidos</a></li>
                    <li><a href="clientes.php" class="<?php echo is_admin_active('clientes.php'); ?>">Clientes</a></li>
                    <li><a href="entregas.php" class="<?php echo is_admin_active('entregas.php'); ?>">Entregas</a></li>
                    <li><a href="horarios.php" class="<?php echo is_admin_active('horarios.php'); ?>">Horários</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container admin-main">
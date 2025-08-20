<?php 
session_start();
require_once __DIR__ . '/../../config/db.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/anotaai-clone/css/style.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <a href="dashboard.php" class="logo">Admin Anota Aí</a>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="produtos.php">Produtos</a></li>
                    <li><a href="pedidos.php">Pedidos</a></li>
                    <li><a href="clientes.php">Clientes</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container admin-main">
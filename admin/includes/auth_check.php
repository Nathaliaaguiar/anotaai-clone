<?php
// auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificação mais robusta: checa se ambos os IDs existem na sessão.
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_loja_id'])) {
    header('Location: index.php'); // Redireciona para o login do admin
    exit;
}

// Inclui a conexão com o banco para as páginas que usarem este arquivo
require_once __DIR__ . '/../../config/db.php';
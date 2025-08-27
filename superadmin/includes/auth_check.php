<?php
// superadmin/includes/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o ID do super_admin existe na sessão.
if (!isset($_SESSION['super_admin_id'])) {
    header('Location: index.php'); // Se não existir, expulsa para o login.
    exit;
}

// Inclui a conexão com o banco para as páginas que usarem este arquivo.
require_once __DIR__ . '/../../config/db.php';
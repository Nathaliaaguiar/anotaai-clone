<?php
// auth_check.php

// Garante que a sessão seja iniciada em qualquer página que inclua este ficheiro.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// A única verificação necessária para o admin da loja é esta.
if (!isset($_SESSION['admin_loja_id'])) {
    session_destroy();
    
    // Redireciona para a página de login que está na pasta 'admin' (um nível acima de 'includes')
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

// [CORREÇÃO FINAL DO ERRO FATAL]
// O caminho para o ficheiro de configuração da base de dados foi corrigido.
// Precisamos de subir dois níveis (de 'includes' para 'admin', e de 'admin' para a raiz do projeto).
require_once __DIR__ . '/../../config/db.php';

// Criamos a variável $loja_id para ser usada em todas as páginas protegidas.
$loja_id = $_SESSION['admin_loja_id'];
?>


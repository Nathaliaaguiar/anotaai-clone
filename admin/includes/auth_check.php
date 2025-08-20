<?php
// Este arquivo deve ser incluído no topo de todas as páginas do admin, exceto o login.
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}
?>
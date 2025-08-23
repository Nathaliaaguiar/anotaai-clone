<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$response = ['status' => 'nenhum', 'pedido_id' => null];

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    // --- CORREÇÃO DEFINITIVA AQUI ---
    // A consulta agora procura pelo status correto: 'saiu_para_entrega'
    $stmt = $pdo->prepare(
        "SELECT id, status FROM pedidos 
         WHERE usuario_id = ? AND status = 'saiu_para_entrega'
         ORDER BY data DESC LIMIT 1"
    );
    $stmt->execute([$usuario_id]);
    $pedido = $stmt->fetch();

    if ($pedido) {
        $response['status'] = $pedido['status'];
        $response['pedido_id'] = $pedido['id'];
    }
}

echo json_encode($response);
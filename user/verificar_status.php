<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$response = ['status' => 'nenhum', 'pedido_id' => null];

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    // --- LÓGICA CORRIGIDA ---
    // Busca o último pedido do usuário que NÃO esteja 'entregue' ou 'cancelado'.
    $stmt = $pdo->prepare(
        "SELECT id, status FROM pedidos 
         WHERE usuario_id = ? 
         AND status NOT IN ('entregue', 'cancelado')
         ORDER BY data DESC LIMIT 1"
    );
    $stmt->execute([$usuario_id]);
    $pedido_ativo = $stmt->fetch();

    // Se encontramos um pedido ativo e o status dele é 'saiu_para_entrega',
    // nós informamos isso na resposta.
    if ($pedido_ativo && $pedido_ativo['status'] === 'saiu_para_entrega') {
        $response['status'] = $pedido_ativo['status'];
        $response['pedido_id'] = $pedido_ativo['id'];
    }
}

echo json_encode($response);
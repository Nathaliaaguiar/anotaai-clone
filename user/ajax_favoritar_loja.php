<?php
// Define o tipo de conteúdo como JSON imediatamente.
header('Content-Type: application/json');

// --- MANIPULADOR DE ERROS ---
// Esta função irá capturar qualquer erro fatal do PHP (como um 'require' falho)
// e garantir que a saída seja um JSON válido, em vez de texto de erro HTML.
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Limpa qualquer saída que já tenha sido enviada
        if (ob_get_length()) {
            ob_clean();
        }
        // Envia uma resposta de erro JSON válida
        http_response_code(500); // Erro Interno do Servidor
        echo json_encode([
            'success' => false,
            'message' => 'Erro fatal no servidor.',
            'details' => "{$error['message']} em {$error['file']}:{$error['line']}"
        ]);
    }
});
// ----------------------------

try {
    // Inicia a sessão se ainda não foi iniciada.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // *** VERIFIQUE ESTE CAMINHO! ***
   $db_path = __DIR__ . '/../config/db.php';
    if (!file_exists($db_path)) {
        throw new Exception("CRÍTICO: Arquivo de conexão com o banco de dados não encontrado.");
    }
    require_once $db_path;

    if (!isset($pdo)) {
        throw new Exception("CRÍTICO: A variável de conexão \$pdo não foi definida.");
    }

    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401); // Não Autorizado
        throw new Exception("Usuário não autenticado. Faça o login para continuar.");
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Dados inválidos (JSON mal formatado).");
    }

    $loja_id = $data['loja_id'] ?? null;
    $action = $data['action'] ?? null;

    if (!$loja_id || !in_array($action, ['favorite', 'unfavorite'])) {
        throw new Exception("Dados inválidos: 'loja_id' ou 'action' ausente.");
    }

    $usuario_id = $_SESSION['usuario_id'];

    if ($action === 'favorite') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO lojas_favoritas (usuario_id, loja_id) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $loja_id]);
    } else { // action === 'unfavorite'
        $stmt = $pdo->prepare("DELETE FROM lojas_favoritas WHERE usuario_id = ? AND loja_id = ?");
        $stmt->execute([$usuario_id, $loja_id]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Se um erro foi lançado intencionalmente por nós.
    if (http_response_code() === 200) { // Se nenhum código de erro foi definido ainda
        http_response_code(400); // Bad Request
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
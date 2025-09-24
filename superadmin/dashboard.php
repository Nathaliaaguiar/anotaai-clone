<?php
require_once 'includes/auth_check.php';

$mensagem = '';

// --- Lógica para CADASTRAR NOVA LOJA E SEU ADMIN (EXISTENTE) ---
// ... (seu código atual para cadastrar loja e admin) ...

// --- Lógica para ATIVAR/DESATIVAR LOJA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_loja_status'])) {
    $loja_id_toggle = $_POST['loja_id_toggle'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 1) ? 0 : 1; // Inverte o status

    try {
        $stmt = $pdo->prepare("UPDATE lojas SET ativa = ? WHERE id = ?");
        $stmt->execute([$new_status, $loja_id_toggle]);
        $mensagem = '<p class="success">Status da loja atualizado com sucesso!</p>';
    } catch (PDOException $e) {
        $mensagem = '<p class="error">Erro ao atualizar status da loja: ' . $e->getMessage() . '</p>';
    }
}

// --- Lógica para EXCLUIR LOJA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_loja'])) {
    $loja_id_excluir = $_POST['loja_id_excluir'];

    try {
        $pdo->beginTransaction();
        // Excluir admins associados
        $stmt_del_admins = $pdo->prepare("DELETE FROM admins WHERE loja_id = ?");
        $stmt_del_admins->execute([$loja_id_excluir]);
        // Excluir configurações
        $stmt_del_configs = $pdo->prepare("DELETE FROM configuracoes WHERE loja_id = ?");
        $stmt_del_configs->execute([$loja_id_excluir]);
        // Excluir horários
        $stmt_del_horarios = $pdo->prepare("DELETE FROM horarios_funcionamento WHERE loja_id = ?");
        $stmt_del_horarios->execute([$loja_id_excluir]);
        // Excluir áreas de entrega
        $stmt_del_areas = $pdo->prepare("DELETE FROM areas_entrega WHERE loja_id = ?");
        $stmt_del_areas->execute([$loja_id_excluir]);
        // Excluir categorias (e produtos/opções em cascata se o DB estiver configurado)
        // Se não estiver em cascata, precisaria de mais DELETEs aqui: produtos, produto_opcoes, pedido_itens, pedidos
        $stmt_del_categorias = $pdo->prepare("DELETE FROM categorias WHERE loja_id = ?");
        $stmt_del_categorias->execute([$loja_id_excluir]);
        // Por fim, excluir a loja
        $stmt_del_loja = $pdo->prepare("DELETE FROM lojas WHERE id = ?");
        $stmt_del_loja->execute([$loja_id_excluir]);

        $pdo->commit();
        $mensagem = '<p class="success">Loja e todos os dados associados excluídos com sucesso!</p>';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = '<p class="error">Erro ao excluir loja: ' . $e->getMessage() . '</p>';
    }
}


// --- Lógica para LISTAR as lojas existentes (MODIFICADA PARA INCLUIR STATUS) ---
$stmt_lista_lojas = $pdo->query("SELECT id, nome, data_criacao, ativa FROM lojas ORDER BY nome ASC");
$lojas = $stmt_lista_lojas->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica para LISTAR ADMINS (NOVA) ---
$stmt_lista_admins = $pdo->query("SELECT a.id, a.email, l.nome as nome_loja, a.loja_id FROM admins a JOIN lojas l ON a.loja_id = l.id ORDER BY l.nome, a.email ASC");
$admins = $stmt_lista_admins->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Adicione estilos específicos para o superadmin aqui, se necessário */
        .superadmin-grid {
            grid-template-columns: 1fr 1.5fr; /* Mantém o layout existente */
        }
        .full-width-section {
            grid-column: 1 / -1; /* Ocupa todas as colunas */
            margin-top: 40px;
        }
        .status-toggle-btn {
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: bold;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
        .status-toggle-btn.active { background-color: #2ecc71; } /* Verde para ativo */
        .status-toggle-btn.inactive { background-color: #e74c3c; } /* Vermelho para inativo */
        .status-toggle-btn:hover { opacity: 0.8; }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body class="admin-page">
    <header class="admin-header">
        <div class="container">
          <div class="logo">
        <img src="../img/logoplatafood.png" alt="Logo da Plataforma">
    </div>
            <nav>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container admin-main">
        <?php echo $mensagem; ?>

        <div class="superadmin-grid">
            <div class="form-wrapper">
                <h2>Cadastrar Nova Loja</h2>
                <form action="dashboard.php" method="POST">
                    <div class="form-group">
                        <label for="nome_loja">Nome da Nova Loja:</label>
                        <input type="text" id="nome_loja" name="nome_loja" required>
                    </div>
                    <div class="form-group">
                        <label for="email_admin">Email do Admin da Loja:</label>
                        <input type="email" id="email_admin" name="email_admin" required>
                    </div>
                    <div class="form-group">
                        <label for="senha_admin">Senha para o Admin da Loja:</label>
                        <input type="password" id="senha_admin" name="senha_admin" required minlength="6">
                    </div>
                    <button type="submit" name="cadastrar_loja" class="btn">Cadastrar Loja</button>
                </form>
            </div>

            <div class="lista-wrapper">
                <h2>Lojas Cadastradas</h2>
                <?php if (empty($lojas)): ?>
                    <p>Nenhuma loja cadastrada ainda.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Loja</th>
                                <th>Data de Criação</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lojas as $loja): ?>
                                <tr>
                                    <td><?php echo $loja['id']; ?></td>
                                    <td><?php echo htmlspecialchars($loja['nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($loja['data_criacao'])); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="loja_id_toggle" value="<?php echo $loja['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $loja['ativa']; ?>">
                                            <button type="submit" name="toggle_loja_status" class="status-toggle-btn <?php echo $loja['ativa'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $loja['ativa'] ? 'Ativa' : 'Inativa'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="action-buttons">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja EXCLUIR esta loja e TODOS os seus dados (produtos, pedidos, admins, etc.)? Esta ação é irreversível!');">
                                            <input type="hidden" name="loja_id_excluir" value="<?php echo $loja['id']; ?>">
                                            <button type="submit" name="excluir_loja" class="btn-remover">Excluir</button>
                                        </form>
                                        <!-- Futuramente: Botão para editar loja -->
                                        <!-- <a href="edit_loja.php?id=<?php echo $loja['id']; ?>" class="btn-edit">Editar</a> -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="lista-wrapper full-width-section">
            <h2>Administradores de Lojas</h2>
            <?php if (empty($admins)): ?>
                <p>Nenhum administrador de loja cadastrado ainda.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Admin</th>
                            <th>Email</th>
                            <th>Loja Associada</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['nome_loja']); ?> (ID: <?php echo $admin['loja_id']; ?>)</td>
                                <td class="action-buttons">
                                    <!-- Futuramente: Botão para redefinir senha do admin -->
                                    <!-- <form method="POST" style="display:inline;" onsubmit="return confirm('Redefinir senha para <?php echo htmlspecialchars($admin['email']); ?>?');">
                                        <input type="hidden" name="admin_id_reset" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" name="reset_admin_password" class="btn-edit">Redefinir Senha</button>
                                    </form> -->
                                    <!-- Futuramente: Botão para excluir admin -->
                                    <!-- <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir administrador <?php echo htmlspecialchars($admin['email']); ?>?');">
                                        <input type="hidden" name="admin_id_delete" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" name="delete_admin" class="btn-remover">Excluir Admin</button>
                                    </form> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>

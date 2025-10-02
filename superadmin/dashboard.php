<?php
// É OBRIGATÓRIO iniciar a sessão para a lógica de ativar/inativar funcionar
session_start(); 
require_once 'includes/auth_check.php';

$mensagem = '';

// --- Lógica para ATIVAR/DESATIVAR LOJA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_loja_status'])) {
    $loja_id_toggle = $_POST['loja_id_toggle'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 1) ? 0 : 1;

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
        $pdo->prepare("DELETE FROM admins WHERE loja_id = ?")->execute([$loja_id_excluir]);
        $pdo->prepare("DELETE FROM configuracoes WHERE loja_id = ?")->execute([$loja_id_excluir]);
        $pdo->prepare("DELETE FROM horarios_funcionamento WHERE loja_id = ?")->execute([$loja_id_excluir]);
        $pdo->prepare("DELETE FROM areas_entrega WHERE loja_id = ?")->execute([$loja_id_excluir]);
        $pdo->prepare("DELETE FROM categorias WHERE loja_id = ?")->execute([$loja_id_excluir]);
        $pdo->prepare("DELETE FROM lojas WHERE id = ?")->execute([$loja_id_excluir]);
        $pdo->commit();

        $mensagem = '<p class="success">Loja e todos os dados associados excluídos com sucesso!</p>';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = '<p class="error">Erro ao excluir loja: ' . $e->getMessage() . '</p>';
    }
}


// --- Lógica para ATIVAR/DESATIVAR USUÁRIO (usando SESSÃO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_usuario_status'])) {
    $usuario_id_toggle = $_POST['usuario_id_toggle'];
    if (!isset($_SESSION['inactive_users'])) {
        $_SESSION['inactive_users'] = [];
    }
    if (isset($_SESSION['inactive_users'][$usuario_id_toggle])) {
        unset($_SESSION['inactive_users'][$usuario_id_toggle]); // Ativa o usuário
    } else {
        $_SESSION['inactive_users'][$usuario_id_toggle] = true; // Inativa o usuário
    }
    // Para recarregar a página e mostrar o status atualizado
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- Lógica para EXCLUIR USUÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_usuario'])) {
    $usuario_id_excluir = $_POST['usuario_id_excluir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id_excluir]);
        $mensagem .= '<p class="success">Usuário e seus pedidos associados foram excluídos com sucesso!</p>';
    } catch (PDOException $e) {
        $mensagem .= '<p class="error">Erro ao excluir usuário: ' . $e->getMessage() . '</p>';
    }
}


// --- Listar lojas ---
$stmt_lista_lojas = $pdo->query("SELECT id, nome, data_criacao, ativa FROM lojas ORDER BY nome ASC");
$lojas = $stmt_lista_lojas->fetchAll(PDO::FETCH_ASSOC);

// --- Listar admins ---
$stmt_lista_admins = $pdo->query("
    SELECT a.id, a.email, l.nome as nome_loja, a.loja_id 
    FROM admins a 
    JOIN lojas l ON a.loja_id = l.id 
    ORDER BY l.nome, a.email ASC
");
$admins = $stmt_lista_admins->fetchAll(PDO::FETCH_ASSOC);

// --- Listar usuários ---
$stmt_lista_usuarios = $pdo->query("SELECT id, nome, email, endereco, bairro, telefone FROM usuarios ORDER BY nome ASC");
$usuarios = $stmt_lista_usuarios->fetchAll(PDO::FETCH_ASSOC);

// Garante que o array de sessão exista para evitar erros no HTML
if (!isset($_SESSION['inactive_users'])) {
    $_SESSION['inactive_users'] = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Super Admin</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .master-container { padding: 2rem; padding-top: 5rem; }
    .master-cards-wrapper { display: flex; gap: 20px; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; }
    .master-card {
      flex: 1; min-width: 260px; background: #fff; border: 1px solid #ddd;
      border-radius: 10px; padding: 8rem; text-align: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1); display: flex;
      flex-direction: column; justify-content: center; cursor:pointer;
    }
    .master-card h2 { font-size: 1.3rem; margin-bottom: 1rem; color: #333; }
    @media (max-width: 768px){ .master-cards-wrapper{ flex-direction: column; } }

    /* MODAL */
    .master-modal { display:none; position:fixed; z-index:1000; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; }
    .master-modal-content { background:#fff; width:80%; max-height:80%; overflow-y:auto; padding:20px; border-radius:10px; }
    .master-modal-header{ display:flex; justify-content:space-between; align-items:center; }
    .master-close, .master-close-user { cursor:pointer; font-size:20px; font-weight:bold; } /* Adicionado .master-close-user */
    table.master-table{ width:100%; border-collapse:collapse; margin-top:1rem; }
    table.master-table th, table.master-table td{ border:1px solid #ddd; padding:10px; text-align:left; }
    table.master-table th{ background:#f5f5f5; }
    .action-btn{ padding:5px 10px; margin:0 3px; border:none; border-radius:5px; cursor:pointer; }
    .btn-ativar{ background:#28a745; color:white; }
    .btn-inativar{ background:#ffc107; color:white; }
    .btn-excluir{ background:#dc3545; color:white; }
    .btn-info{ background:#007bff; color:white; }
    .extra-info{ display:none; font-size:0.9em; margin-top:5px; }
  </style>
</head>
<body class="admin-page">
  <header class="admin-header">
    <div class="container">
      <div class="logo"><img src="../img/logoplatafood.png" alt="Logo"></div>
      <nav><a href="logout.php">Sair</a></nav>
    </div>
  </header>

  <main class="master-container">
    <?php echo $mensagem; ?>

    <div class="master-cards-wrapper">
      <div class="master-card"><h2>Analise cadastro loja</h2></div>
      <div class="master-card" id="abrir-modal-lojas"><h2>Lojas Cadastradas</h2></div>
      <div class="master-card" id="abrir-modal-usuarios"><h2>Usuários Cadastrados</h2></div>
    </div>
  </main>

  <div id="modal-lojas" class="master-modal">
    <div class="master-modal-content">
      <div class="master-modal-header">
        <h2>Gerenciar Lojas</h2>
        <span class="master-close">&times;</span>
      </div>
      <table class="master-table">
        <thead>
          <tr><th>ID</th><th>Nome</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach($lojas as $loja): ?>
            <tr>
              <td><?= $loja['id'] ?></td>
              <td><?= htmlspecialchars($loja['nome']) ?></td>
              <td><?= $loja['ativa'] ? 'Ativa' : 'Inativa' ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="loja_id_toggle" value="<?= $loja['id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $loja['ativa'] ?>">
                  <button type="submit" name="toggle_loja_status" class="action-btn <?= $loja['ativa']?'btn-inativar':'btn-ativar' ?>">
                    <?= $loja['ativa']?'Inativar':'Ativar' ?>
                  </button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta loja?')">
                  <input type="hidden" name="loja_id_excluir" value="<?= $loja['id'] ?>">
                  <button type="submit" name="excluir_loja" class="action-btn btn-excluir">Excluir</button>
                </form>
                <button type="button" class="action-btn btn-info" onclick="toggleExtraInfo('loja-<?= $loja['id'] ?>')">Ver mais</button>
              </td>
            </tr>
            <tr id="extra-loja-<?= $loja['id'] ?>" class="extra-info">
              <td colspan="4">
                <strong>ID Loja:</strong> <?= $loja['id'] ?><br>
                <?php foreach($admins as $admin): ?>
                  <?php if($admin['loja_id']==$loja['id']): ?>
                    <strong>ID Admin:</strong> <?= $admin['id'] ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?><br>
                  <?php endif; ?>
                <?php endforeach; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div id="modal-usuarios" class="master-modal">
    <div class="master-modal-content">
      <div class="master-modal-header">
        <h2>Gerenciar Usuários</h2>
        <span class="master-close-user">&times;</span>
      </div>
      <table class="master-table">
        <thead>
          <tr><th>ID</th><th>Nome</th><th>Celular</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach($usuarios as $usuario): ?>
            <?php
              $is_inactive = isset($_SESSION['inactive_users'][$usuario['id']]);
            ?>
            <tr>
              <td><?= $usuario['id'] ?></td>
              <td><?= htmlspecialchars($usuario['nome']) ?></td>
              <td><?= htmlspecialchars($usuario['telefone']) ?></td>
              <td><?= $is_inactive ? 'Inativo' : 'Ativo' ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="usuario_id_toggle" value="<?= $usuario['id'] ?>">
                  <button type="submit" name="toggle_usuario_status" class="action-btn <?= $is_inactive ? 'btn-ativar' : 'btn-inativar' ?>">
                    <?= $is_inactive ? 'Ativar' : 'Inativar' ?>
                  </button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza? Excluir um usuário removerá também todos os seus pedidos.')">
                  <input type="hidden" name="usuario_id_excluir" value="<?= $usuario['id'] ?>">
                  <button type="submit" name="excluir_usuario" class="action-btn btn-excluir">Excluir</button>
                </form>
                <button type="button" class="action-btn btn-info" onclick="toggleExtraInfo('user-<?= $usuario['id'] ?>')">Ver mais</button>
              </td>
            </tr>
            <tr id="extra-user-<?= $usuario['id'] ?>" class="extra-info">
              <td colspan="5">
                <strong>E-mail:</strong> <?= htmlspecialchars($usuario['email']) ?><br>
                <strong>Endereço:</strong> <?= htmlspecialchars($usuario['endereco']) ?><br>
                <strong>Bairro:</strong> <?= htmlspecialchars($usuario['bairro']) ?><br>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // Script para Modal Lojas
    const modal = document.getElementById("modal-lojas");
    const btn = document.getElementById("abrir-modal-lojas");
    const span = document.querySelector(".master-close");

    btn.onclick = () => { modal.style.display = "flex"; }
    span.onclick = () => { modal.style.display = "none"; }
    window.addEventListener("click", (e) => { if (e.target == modal) { modal.style.display = "none"; } });

    // Script para o Modal de Usuários
    const modalUser = document.getElementById("modal-usuarios");
    const btnUser = document.getElementById("abrir-modal-usuarios");
    const spanUser = document.querySelector(".master-close-user");

    btnUser.onclick = () => { modalUser.style.display = "flex"; }
    spanUser.onclick = () => { modalUser.style.display = "none"; }
    window.addEventListener("click", (e) => { 
        if (e.target == modalUser) { 
            modalUser.style.display = "none"; 
        }
    });

    // Função genérica para mostrar/esconder informações extras para Lojas e Usuários
    function toggleExtraInfo(id){
      const row = document.getElementById("extra-"+id);
      row.style.display = (row.style.display === "table-row") ? "none" : "table-row";
    }
  </script>
</body>
</html>
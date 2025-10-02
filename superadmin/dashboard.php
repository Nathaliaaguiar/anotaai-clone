<?php
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
    .master-close{ cursor:pointer; font-size:20px; font-weight:bold; }
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
      <div class="master-card"><h2>Usuários Cadastrados</h2></div>
    </div>
  </main>

  <!-- Modal Lojas -->
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
                <button type="button" class="action-btn btn-info" onclick="toggleExtraInfo(<?= $loja['id'] ?>)">Ver mais</button>
              </td>
            </tr>
            <tr id="extra-<?= $loja['id'] ?>" class="extra-info">
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

  <script>
    const modal = document.getElementById("modal-lojas");
    const btn = document.getElementById("abrir-modal-lojas");
    const span = document.querySelector(".master-close");

    btn.onclick = () => { modal.style.display = "flex"; }
    span.onclick = () => { modal.style.display = "none"; }
    window.onclick = (e) => { if (e.target == modal) { modal.style.display = "none"; } }

    function toggleExtraInfo(id){
      const row = document.getElementById("extra-"+id);
      row.style.display = (row.style.display==="table-row")?"none":"table-row";
    }
  </script>
</body>
</html>

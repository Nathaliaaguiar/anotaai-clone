<?php
// dashboard_super_admin.php
session_start();
require_once 'includes/auth_check.php'; // garante $pdo e validação de sessão

$mensagem = '';

// --- AÇÕES: Aprovar / Recusar (Excluir) / Toggle Ativa ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Aprovar loja (vinda do modal de análise)
    if (isset($_POST['aprovar_loja'])) {
        $loja_id = (int) $_POST['loja_id_aprovar'];
        try {
            $stmt = $pdo->prepare("UPDATE lojas SET aprovado = 1, ativa = 1, data_analise = NOW() WHERE id = ?");
            $stmt->execute([$loja_id]);
            $mensagem = '<p class="success">Loja aprovada com sucesso!</p>';
        } catch (PDOException $e) {
            $mensagem = '<p class="error">Erro ao aprovar loja: ' . $e->getMessage() . '</p>';
        }
    }

    // Recusar loja (excluir) - usado tanto em pendentes quanto em aprovadas
    if (isset($_POST['excluir_loja'])) {
        $loja_id_excluir = (int) $_POST['loja_id_excluir'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM admins WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM configuracoes WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM horarios_funcionamento WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM areas_entrega WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM categorias WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM lojas WHERE id = ?")->execute([$loja_id_excluir]);
            $pdo->commit();
            $mensagem = '<p class="success">Loja removida com sucesso.</p>';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem = '<p class="error">Erro ao remover loja: ' . $e->getMessage() . '</p>';
        }
    }

    // Toggle ativa/inativa (apenas altera 'ativa', mantem 'aprovado' como está)
    if (isset($_POST['toggle_loja_status'])) {
        $loja_id_toggle = (int) $_POST['loja_id_toggle'];
        $current_status = (int) $_POST['current_status'];
        $new_status = ($current_status === 1) ? 0 : 1;
        try {
            $stmt = $pdo->prepare("UPDATE lojas SET ativa = ? WHERE id = ?");
            $stmt->execute([$new_status, $loja_id_toggle]);
            $mensagem = '<p class="success">Status da loja atualizado com sucesso!</p>';
        } catch (PDOException $e) {
            $mensagem = '<p class="error">Erro ao atualizar status da loja: ' . $e->getMessage() . '</p>';
        }
    }

    // Toggle usuário (sessão) - manteve sua lógica
    if (isset($_POST['toggle_usuario_status'])) {
        $usuario_id_toggle = $_POST['usuario_id_toggle'];
        if (!isset($_SESSION['inactive_users'])) {
            $_SESSION['inactive_users'] = [];
        }
        if (isset($_SESSION['inactive_users'][$usuario_id_toggle])) {
            unset($_SESSION['inactive_users'][$usuario_id_toggle]);
        } else {
            $_SESSION['inactive_users'][$usuario_id_toggle] = true;
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Excluir usuário
    if (isset($_POST['excluir_usuario'])) {
        $usuario_id_excluir = $_POST['usuario_id_excluir'];
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id_excluir]);
            $mensagem .= '<p class="success">Usuário excluído com sucesso!</p>';
        } catch (PDOException $e) {
            $mensagem .= '<p class="error">Erro ao excluir usuário: ' . $e->getMessage() . '</p>';
        }
    }
}

// --- Consultas: lojas pendentes (aprovado = 0) e lojas aprovadas (aprovado = 1) ---
// Pegamos também email/id do admin associado (LEFT JOIN para não quebrar se não houver admin)
$stmt_lista_pendentes = $pdo->query("
    SELECT l.id, l.nome, l.endereco, l.bairro, l.data_criacao, a.id as admin_id, a.email as admin_email
    FROM lojas l
    LEFT JOIN admins a ON a.loja_id = l.id
    WHERE l.aprovado = 0
    ORDER BY l.data_criacao DESC
");
$lojas_pendentes = $stmt_lista_pendentes->fetchAll(PDO::FETCH_ASSOC);

$stmt_lista_lojas = $pdo->query("
    SELECT l.id, l.nome, l.ativa, l.data_criacao, l.endereco, l.bairro, a.id as admin_id, a.email as admin_email
    FROM lojas l
    LEFT JOIN admins a ON a.loja_id = l.id
    WHERE l.aprovado = 1
    ORDER BY l.nome ASC
");
$lojas = $stmt_lista_lojas->fetchAll(PDO::FETCH_ASSOC);

// Lista usuários (mantido)
$stmt_lista_usuarios = $pdo->query("SELECT id, nome, email, endereco, bairro, telefone FROM usuarios ORDER BY nome ASC");
$usuarios = $stmt_lista_usuarios->fetchAll(PDO::FETCH_ASSOC);

// Garante array de sessão
if (!isset($_SESSION['inactive_users'])) {
    $_SESSION['inactive_users'] = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Super Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/index.css">
  
</head>
<body class="admin-page">
  <header class="admin-header">
    <div class="container">
      <div class="logo"><img src="../img/logoplatafood.png" alt="Logo" style="height:90px;"></div>
      <nav><a href="logout.php">Sair</a></nav>
    </div>
  </header>

  <main class="master-container">
    <?= $mensagem ?>

    <div class="master-cards-wrapper">
      <div class="master-card" id="abrir-modal-analise"><h2>Análise cadastro loja</h2></div>
      <div class="master-card" id="abrir-modal-lojas"><h2>Lojas Cadastradas</h2></div>
      <div class="master-card" id="abrir-modal-usuarios"><h2>Usuários Cadastrados</h2></div>
    </div>
  </main>

  <div id="modal-analise" class="master-modal">
    <div class="master-modal-content">
      <div class="master-modal-header">
        <h2>Lojas pendentes de aprovação</h2>
        <span class="master-close-analise">&times;</span>
      </div>

      <table class="master-table">
        <thead>
          <tr><th>ID</th><th>Nome</th><th>Status</th><th>Data Cadastro</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php if (count($lojas_pendentes) === 0): ?>
            <tr><td colspan="5" style="text-align:center;">Nenhuma loja pendente de aprovação.</td></tr>
          <?php else: ?>
            <?php foreach($lojas_pendentes as $loja): ?>
              <tr>
                <td><?= $loja['id'] ?></td>
                <td><?= htmlspecialchars($loja['nome']) ?></td>
                <td class="small">Pendente</td>
                <td class="small"><?= date('d/m/Y H:i', strtotime($loja['data_criacao'])) ?></td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="loja_id_aprovar" value="<?= $loja['id'] ?>">
                    <button type="submit" name="aprovar_loja" class="action-btn btn-ativar">Aprovar</button>
                  </form>

                  <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja recusar/excluir esta loja?')">
                    <input type="hidden" name="loja_id_excluir" value="<?= $loja['id'] ?>">
                    <button type="submit" name="excluir_loja" class="action-btn btn-excluir">Recusar / Excluir</button>
                  </form>

                  <button type="button" class="action-btn btn-info" onclick="toggleExtraInfo('analise-<?= $loja['id'] ?>')">Ver mais</button>
                </td>
              </tr>

              <tr id="extra-analise-<?= $loja['id'] ?>" class="extra-info">
                <td colspan="5">
                  <strong>ID Loja:</strong> <?= $loja['id'] ?><br>
                  <strong>ID Admin:</strong> <?= $loja['admin_id'] ?? '—' ?><br>
                  <strong>Email Admin:</strong> <?= htmlspecialchars($loja['admin_email'] ?? '—') ?><br>
                  <strong>Endereço:</strong> <?= htmlspecialchars($loja['endereco'] ?? '—') ?><br>
                  <strong>Bairro:</strong> <?= htmlspecialchars($loja['bairro'] ?? '—') ?><br>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div id="modal-lojas" class="master-modal">
    <div class="master-modal-content">
      <div class="master-modal-header">
        <h2>Lojas Aprovadas</h2>
        <span class="master-close">&times;</span>
      </div>

      <table class="master-table">
        <thead>
          <tr><th>ID</th><th>Nome</th><th>Status</th><th>Data Cadastro</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php if (count($lojas) === 0): ?>
            <tr><td colspan="5" style="text-align:center;">Nenhuma loja aprovada cadastrada.</td></tr>
          <?php else: ?>
            <?php foreach($lojas as $loja): ?>
              <tr>
                <td><?= $loja['id'] ?></td>
                <td><?= htmlspecialchars($loja['nome']) ?></td>
                <td class="small"><?= $loja['ativa'] ? 'Ativa' : 'Inativa' ?></td>
                <td class="small"><?= date('d/m/Y H:i', strtotime($loja['data_criacao'])) ?></td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="loja_id_toggle" value="<?= $loja['id'] ?>">
                    <input type="hidden" name="current_status" value="<?= $loja['ativa'] ?>">
                    <button type="submit" name="toggle_loja_status" class="action-btn <?= $loja['ativa'] ? 'btn-inativar' : 'btn-ativar' ?>">
                      <?= $loja['ativa'] ? 'Inativar' : 'Ativar' ?>
                    </button>
                  </form>

                  <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta loja aprovada?')">
                    <input type="hidden" name="loja_id_excluir" value="<?= $loja['id'] ?>">
                    <button type="submit" name="excluir_loja" class="action-btn btn-excluir">Excluir</button>
                  </form>

                  <button type="button" class="action-btn btn-info" onclick="toggleExtraInfo('loja-<?= $loja['id'] ?>')">Ver mais</button>
                </td>
              </tr>

              <tr id="extra-loja-<?= $loja['id'] ?>" class="extra-info">
                <td colspan="5">
                  <strong>ID Loja:</strong> <?= $loja['id'] ?><br>
                  <strong>ID Admin:</strong> <?= $loja['admin_id'] ?? '—' ?><br>
                  <strong>Email Admin:</strong> <?= htmlspecialchars($loja['admin_email'] ?? '—') ?><br>
                  <strong>Endereço:</strong> <?= htmlspecialchars($loja['endereco'] ?? '—') ?><br>
                  <strong>Bairro:</strong> <?= htmlspecialchars($loja['bairro'] ?? '—') ?><br>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
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
            <?php $is_inactive = isset($_SESSION['inactive_users'][$usuario['id']]); ?>
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
                <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir este usuário?')">
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
    // === Modal Análise ===
    const modalAnalise = document.getElementById("modal-analise");
    document.getElementById("abrir-modal-analise").onclick = () => modalAnalise.style.display = "flex";
    document.querySelector(".master-close-analise").onclick = () => modalAnalise.style.display = "none";

    // === Modal Lojas ===
    const modalLojas = document.getElementById("modal-lojas");
    document.getElementById("abrir-modal-lojas").onclick = () => modalLojas.style.display = "flex";
    document.querySelector(".master-close").onclick = () => modalLojas.style.display = "none";

    // === Modal Usuários ===
    const modalUser = document.getElementById("modal-usuarios");
    document.getElementById("abrir-modal-usuarios").onclick = () => modalUser.style.display = "flex";
    document.querySelector(".master-close-user").onclick = () => modalUser.style.display = "none";

    // Fechar modal clicando fora
    window.addEventListener("click", (e) => {
      if (e.target == modalAnalise) modalAnalise.style.display = "none";
      if (e.target == modalLojas) modalLojas.style.display = "none";
      if (e.target == modalUser) modalUser.style.display = "none";
    });

    // Mostrar/Esconder detalhes
    function toggleExtraInfo(id) {
      const row = document.getElementById("extra-" + id);
      if (!row) return;
      row.style.display = (row.style.display === "table-row" || row.style.display === "block") ? "none" : "table-row";
      // Para tabelas em alguns navegadores, forçar display table-row funciona melhor:
      if (row.style.display === "table-row") {
        // ok
      } else if (row.style.display === "block") {
        row.style.display = "table-row";
      }
    }
  </script>
  <footer class="platafood-footer">
  <div class="footer-container">
    <p>© 2025 PlataFood </p>
    
    </div>
  </div>
</footer>
</body>
</html>
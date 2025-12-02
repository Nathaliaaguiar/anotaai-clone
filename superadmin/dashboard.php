<?php
// dashboard_super_admin.php
session_start();
require_once 'includes/auth_check.php'; // garante $pdo e validação de sessão

$mensagem = '';

// --- AÇÕES: Aprovar / Recusar (Excluir) / Toggle Ativa ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Aprovar loja
    if (isset($_POST['aprovar_loja'])) {
        $loja_id_aprovar = (int) $_POST['loja_id_aprovar'];
        try {
            $stmt = $pdo->prepare("UPDATE lojas SET aprovado = 1, ativa = 1 WHERE id = ?");
            $stmt->execute([$loja_id_aprovar]);
            $mensagem = '<p class="success">Loja aprovada e ativada com sucesso!</p>';
        } catch (PDOException $e) {
            $mensagem = '<p class="error">Erro ao aprovar loja: ' . $e->getMessage() . '</p>';
        }
    }

    // Excluir / Recusar loja
    if (isset($_POST['excluir_loja'])) {
        $loja_id_excluir = (int) $_POST['loja_id_excluir'];

        try {
            $pdo->beginTransaction();

            $stmtLoja = $pdo->prepare("SELECT id, nome, email, endereco, bairro, aprovado FROM lojas WHERE id = ?");
            $stmtLoja->execute([$loja_id_excluir]);
            $lojaDados = $stmtLoja->fetch(PDO::FETCH_ASSOC);

            if ($lojaDados) {
                if ($lojaDados['aprovado'] == 1) {
                    // Loja aprovada → excluída por descumprimento
                    $motivo = "Descumprimento das diretrizes";
                    $stmtExcluidas = $pdo->prepare("
                        INSERT INTO lojas_excluidas 
                        (loja_id, nome, email, endereco, bairro, motivo, data_exclusao)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmtExcluidas->execute([
                        $lojaDados['id'],
                        $lojaDados['nome'],
                        $lojaDados['email'],
                        $lojaDados['endereco'],
                        $lojaDados['bairro'],
                        $motivo
                    ]);
                    $mensagem_loja = "🚫 Sua conta foi excluída por descumprimento das diretrizes.";
                } else {
                    // Loja pendente → recusada
                    $motivo = "Cadastro não aprovado / Decidimos não seguir com o cadastro";
                    $stmtRecusadas = $pdo->prepare("
                        INSERT INTO lojas_recusadas 
                        (loja_id, nome, email, endereco, bairro, motivo)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmtRecusadas->execute([
                        $lojaDados['id'],
                        $lojaDados['nome'],
                        $lojaDados['email'],
                        $lojaDados['endereco'],
                        $lojaDados['bairro'],
                        $motivo
                    ]);
                    $mensagem_loja = "⚠️ Após análise, decidimos não seguir com o cadastro da sua loja.";
                }
            }

            // Deletar dados dependentes
            $pdo->prepare("DELETE FROM admins WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM configuracoes WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM horarios_funcionamento WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM areas_entrega WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM categorias WHERE loja_id = ?")->execute([$loja_id_excluir]);
            $pdo->prepare("DELETE FROM lojas WHERE id = ?")->execute([$loja_id_excluir]);

            $pdo->commit();
            $mensagem = '<p class="success">Loja excluída/recusada com sucesso!</p>';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem = '<p class="error">Erro ao excluir loja: ' . $e->getMessage() . '</p>';
        }
    }

    // Toggle ativa/desativada da loja
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

    // Toggle usuário (sessão)
    if (isset($_POST['toggle_usuario_status'])) {
        $usuario_id_toggle = $_POST['usuario_id_toggle'];
        if (!isset($_SESSION['inactive_users'])) $_SESSION['inactive_users'] = [];
        if (isset($_SESSION['inactive_users'][$usuario_id_toggle])) {
            unset($_SESSION['inactive_users'][$usuario_id_toggle]);
        } else {
            $_SESSION['inactive_users'][$usuario_id_toggle] = true;
        }
        $mensagem = '<p class="success">Status do usuário atualizado com sucesso!</p>';
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

// --- Consultas ---
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

$stmt_lista_usuarios = $pdo->query("SELECT id, nome, email, endereco, bairro, telefone FROM usuarios ORDER BY nome ASC");
$usuarios = $stmt_lista_usuarios->fetchAll(PDO::FETCH_ASSOC);

if (!isset($_SESSION['inactive_users'])) $_SESSION['inactive_users'] = [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Dashboard Super Admin</title>
<link rel="stylesheet" href="./css/index.css">
<style>
.extra-info { display:none; }
</style>
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
        <div class="master-card" data-modal="analise"><h2>Análise cadastro loja</h2></div>
        <div class="master-card" data-modal="lojas"><h2>Lojas Cadastradas</h2></div>
        <div class="master-card" data-modal="usuarios"><h2>Usuários Cadastrados</h2></div>
    </div>
</main>

<!-- Modal Lojas Pendentes -->
<div id="modal-analise" class="master-modal">
<div class="master-modal-content">
<div class="master-modal-header">
<h2>Lojas pendentes de aprovação</h2>
<span class="master-close" data-close="analise">&times;</span>
</div>
<table class="master-table">
<thead>
<tr><th>ID</th><th>Nome</th><th>Status</th><th>Data Cadastro</th><th>Ações</th></tr>
</thead>
<tbody>
<?php if (count($lojas_pendentes) === 0): ?>
<tr><td colspan="5" style="text-align:center;">Nenhuma loja pendente de aprovação.</td></tr>
<?php else: foreach($lojas_pendentes as $loja): ?>
<tr>
<td><?= $loja['id'] ?></td>
<td><?= htmlspecialchars($loja['nome']) ?></td>
<td>Pendente</td>
<td><?= date('d/m/Y H:i', strtotime($loja['data_criacao'])) ?></td>
<td>
<form method="POST" style="display:inline;"><input type="hidden" name="loja_id_aprovar" value="<?= $loja['id'] ?>"><button type="submit" name="aprovar_loja" class="action-btn btn-ativar">Aprovar</button></form>
<form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja recusar/excluir esta loja?')"><input type="hidden" name="loja_id_excluir" value="<?= $loja['id'] ?>"><button type="submit" name="excluir_loja" class="action-btn btn-excluir">Recusar / Excluir</button></form>
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
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<!-- Modal Lojas Aprovadas -->
<div id="modal-lojas" class="master-modal">
<div class="master-modal-content">
<div class="master-modal-header">
<h2>Lojas Aprovadas</h2>
<span class="master-close" data-close="lojas">&times;</span>
</div>
<table class="master-table">
<thead>
<tr><th>ID</th><th>Nome</th><th>Status</th><th>Data Cadastro</th><th>Ações</th></tr>
</thead>
<tbody>
<?php if (count($lojas) === 0): ?>
<tr><td colspan="5" style="text-align:center;">Nenhuma loja aprovada cadastrada.</td></tr>
<?php else: foreach($lojas as $loja): ?>
<tr>
<td><?= $loja['id'] ?></td>
<td><?= htmlspecialchars($loja['nome']) ?></td>
<td><?= $loja['ativa'] ? 'Ativa' : 'Desativada' ?></td>
<td><?= date('d/m/Y H:i', strtotime($loja['data_criacao'])) ?></td>
<td>
<form method="POST" style="display:inline;"><input type="hidden" name="loja_id_toggle" value="<?= $loja['id'] ?>"><input type="hidden" name="current_status" value="<?= $loja['ativa'] ?>"><button type="submit" name="toggle_loja_status" class="action-btn"><?= $loja['ativa'] ? 'Desativar' : 'Ativar' ?></button></form>
<form method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta loja aprovada?')"><input type="hidden" name="loja_id_excluir" value="<?= $loja['id'] ?>"><button type="submit" name="excluir_loja" class="action-btn btn-excluir">Excluir</button></form>
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
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<!-- Modal Usuários -->
<div id="modal-usuarios" class="master-modal">
<div class="master-modal-content">
<div class="master-modal-header">
<h2>Gerenciar Usuários</h2>
<span class="master-close" data-close="usuarios">&times;</span>
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
<td><?= $is_inactive ? 'Desativado' : 'Ativo' ?></td>
<td>
<form method="POST" style="display:inline;"><input type="hidden" name="usuario_id_toggle" value="<?= $usuario['id'] ?>"><button type="submit" name="toggle_usuario_status" class="action-btn"><?= $is_inactive ? 'Ativar' : 'Desativar' ?></button></form>
<form method="POST" style="display:inline;" onsubmit="return confirm('Excluir este usuário?')"><input type="hidden" name="usuario_id_excluir" value="<?= $usuario['id'] ?>"><button type="submit" name="excluir_usuario" class="action-btn btn-excluir">Excluir</button></form>
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
// Abrir modais
document.querySelectorAll('.master-card').forEach(card => {
    card.addEventListener('click', () => {
        const modalId = 'modal-' + card.getAttribute('data-modal');
        document.getElementById(modalId).style.display = 'flex';
    });
});
// Fechar modais
document.querySelectorAll('.master-close').forEach(span => {
    span.addEventListener('click', () => {
        const modalId = 'modal-' + span.getAttribute('data-close');
        document.getElementById(modalId).style.display = 'none';
    });
});
// Fechar clicando fora
window.addEventListener('click', (e) => {
    ['analise','lojas','usuarios'].forEach(id => {
        const modal = document.getElementById('modal-' + id);
        if(e.target == modal) modal.style.display = 'none';
    });
});
// Mostrar/Esconder detalhes
function toggleExtraInfo(id) {
    const row = document.getElementById("extra-" + id);
    if(!row) return;
    row.style.display = (row.style.display === "table-row" || row.style.display === "block") ? "none" : "table-row";
}
</script>
<style>
    /* 1. Ajuste necessário no BODY para o footer não esconder o conteúdo */
    body {
        /* Adiciona um espaço no final da página igual à altura do footer */
        padding-bottom: 60px; 
    }

    /* 2. Estilos do Footer Fixo */
    .super-admin-footer {
        background-color: #1a1d24;       /* Fundo Dark */
        border-top: 3px solid #ff6f00;   /* Linha Laranja */
        color: #b2bec3;                  /* Texto Cinza */
        padding: 10px 0;                 /* Altura compacta */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 0.85rem;
        
        /* --- A MÁGICA DA FIXAÇÃO --- */
        position: fixed;  /* Fixa na tela */
        bottom: 0;        /* Cola no fundo */
        left: 0;          /* Cola na esquerda */
        width: 100%;      /* Ocupa toda a largura */
        z-index: 9999;    /* Garante que fique por cima de tudo */
        box-shadow: 0 -2px 10px rgba(0,0,0,0.2); /* Sombra suave para cima */
    }

    .sa-footer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
    }

    .sa-footer-left strong {
        color: #fff;
        font-weight: 600;
    }

    .sa-footer-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .sa-footer-link {
        color: #b2bec3;
        text-decoration: none;
        transition: color 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .sa-footer-link:hover {
        color: #ff6f00;
    }

    .sa-version {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        color: #fff;
    }

    .sa-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        color: #2ecc71;
    }
    .sa-status-dot {
        width: 8px;
        height: 8px;
        background-color: #2ecc71;
        border-radius: 50%;
        box-shadow: 0 0 5px rgba(46, 204, 113, 0.5);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); }
        70% { box-shadow: 0 0 0 4px rgba(46, 204, 113, 0); }
        100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
    }

    /* Responsivo */
    @media (max-width: 768px) {
        body { padding-bottom: 80px; } /* Mais espaço no mobile */
        .sa-footer-container {
            flex-direction: column;
            gap: 5px;
            text-align: center;
            padding: 10px;
        }
        .super-admin-footer { position: fixed; } /* Mantém fixo no mobile tbm */
    }
</style>

<footer class="super-admin-footer">
    <div class="sa-footer-container">
        
        <div class="sa-footer-left">
            &copy; <?php echo date('Y'); ?> <strong>PlataFood</strong> Admin.
        </div>

        <div class="sa-footer-right">
            <a href="../index.php" target="_blank" class="sa-footer-link" title="Ver Loja">
                <i class="fa-solid fa-store"></i> Ver Loja
            </a>

            <div class="sa-status">
                <span class="sa-status-dot"></span> Online
            </div>

            <span class="sa-version">v2.1</span>
        </div>

    </div>
</footer>
<script>
// ... (seus scripts de modal existentes ficam aqui) ...

// SCRIPT PARA REMOVER MENSAGEM AUTOMATICAMENTE
document.addEventListener("DOMContentLoaded", function() {
    const alerts = document.querySelectorAll('.success, .error');
    
    if (alerts.length > 0) {
        alerts.forEach(function(alert) {
            // Espera 3 segundos (3000ms) e então começa a sumir
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)'; // Sobe um pouquinho ao sumir
                
                // Remove do HTML totalmente após a animação de sumir terminar (0.5s)
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 3000);
        });
    }
});
</script>
</body>
</html>
</body>
</html>

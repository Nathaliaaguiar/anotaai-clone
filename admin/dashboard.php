<?php
// 1. Segurança e Sessão
require_once 'includes/auth_check.php'; 

// 2. Cabeçalho (já inclui o CSS novo e abre a tag <main>)
require_once 'includes/header.php';

$mensagem = '';

// --- LÓGICA PHP (Mantida igual ao seu original) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Atualizar Nome
    if (isset($_POST['nome_loja'])) {
        $nome_loja = trim($_POST['nome_loja']);
        $stmt = $pdo->prepare("INSERT INTO configuracoes (loja_id, chave, valor) VALUES (?, 'nome_loja', ?) ON DUPLICATE KEY UPDATE valor = ?");
        if ($stmt->execute([$loja_id, $nome_loja, $nome_loja])) {
            $mensagem = '<div class="success">Nome da loja atualizado com sucesso!</div>';
        } else {
            $mensagem = '<div class="error">Erro ao atualizar o nome da loja.</div>';
        }
    }
    // Atualizar Logo
    if (isset($_FILES['logo_loja']) && $_FILES['logo_loja']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['logo_loja'];
        $destino = __DIR__ . '/../img/logo_loja_' . $loja_id . '.png';
        if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
            $mensagem = '<div class="success">Logo da loja atualizada com sucesso!</div>';
        } else {
            $mensagem = '<div class="error">Erro ao salvar a imagem.</div>';
        }
    }
}

// --- CONSULTAS SQL (Mantidas) ---
$stmt_pedidos = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(data) = CURDATE() AND loja_id = ?");
$stmt_pedidos->execute([$loja_id]);
$pedidos_hoje = $stmt_pedidos->fetchColumn();

$stmt_clientes = $pdo->prepare("SELECT COUNT(DISTINCT usuario_id) FROM pedidos WHERE loja_id = ?");
$stmt_clientes->execute([$loja_id]);
$clientes_total = $stmt_clientes->fetchColumn();

$stmt_produtos = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND loja_id = ?");
$stmt_produtos->execute([$loja_id]);
$produtos_ativos = $stmt_produtos->fetchColumn();

$stmt_configs = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE loja_id = ?");
$stmt_configs->execute([$loja_id]);
$configs_lista = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
$nome_loja_atual = $configs_lista['nome_loja'] ?? 'Minha Loja';

// Dados do Gráfico
$labels_grafico = []; $valores_grafico = [];
try {
    $stmt_grafico = $pdo->prepare("SELECT YEAR(data) as ano, MONTH(data) as mes, SUM(total) as faturamento_mensal FROM pedidos WHERE status = 'entregue' AND loja_id = ? AND data >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY YEAR(data), MONTH(data) ORDER BY ano, mes");
    $stmt_grafico->execute([$loja_id]);
    $dados_grafico = $stmt_grafico->fetchAll(PDO::FETCH_ASSOC);
    if ($dados_grafico) {
        $meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        foreach ($dados_grafico as $dado) {
            $labels_grafico[] = $meses[(int)$dado['mes']] . '/' . substr($dado['ano'], -2);
            $valores_grafico[] = $dado['faturamento_mensal'];
        }
    }
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$labels_json = json_encode($labels_grafico);
$valores_json = json_encode($valores_grafico);
?>

<h2>Dashboard</h2>

<?php echo $mensagem; ?>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="stat-info">
            <h3>Pedidos Hoje</h3>
            <p><?php echo $pedidos_hoje; ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3>Clientes</h3>
            <p><?php echo $clientes_total; ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
        <div class="stat-info">
            <h3>Produtos Ativos</h3>
            <p><?php echo $produtos_ativos; ?></p>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-container">
        <h3>Faturamento Mensal (Últimos 12 Meses)</h3>
        <?php if (!empty($labels_grafico)): ?>
            <canvas id="graficoFaturamentoMensal"></canvas>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: #888;">
                <p>Ainda não há dados de faturamento.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="chart-container" style="display: flex; align-items: center; justify-content: center; color: #aaa;">
        <p>Em breve: Gráfico de Pedidos</p>
    </div>
</div>

<div class="config-section">
    <h3>Configurações Rápidas</h3>
    
    <div class="config-grid">
        <div>
            <form action="dashboard.php" method="POST">
                <div class="form-group">
                    <label for="nome_loja">Nome da Loja</label>
                    <input type="text" id="nome_loja" name="nome_loja" value="<?php echo htmlspecialchars($nome_loja_atual); ?>" required>
                </div>
                <button type="submit" class="btn-primary">Salvar Nome</button>
            </form>
        </div>

        <div>
            <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="logo_loja">Alterar Logo (.png)</label>
                    <input type="file" name="logo_loja" id="logo_loja" required accept="image/png">
                </div>
                <button type="submit" class="btn-primary">Salvar Logo</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Configuração do Gráfico
    const canvasGrafico = document.getElementById('graficoFaturamentoMensal');
    if (canvasGrafico && <?php echo json_encode(!empty($labels_grafico)); ?>) {
        const ctx = canvasGrafico.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $labels_json; ?>,
                datasets: [{
                    label: 'Faturamento (R$)',
                    data: <?php echo $valores_json; ?>,
                    backgroundColor: 'rgba(255, 111, 0, 0.7)', // Laranja
                    borderColor: 'rgba(255, 111, 0, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, ticks: { callback: (v) => 'R$ ' + v } } 
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // 2. Lógica para remover os Alertas (Toast) automaticamente
    const alerts = document.querySelectorAll('.success, .error');
    if (alerts.length > 0) {
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0'; // Começa a sumir
                alert.style.transform = 'translateY(-10px)'; // Sobe um pouquinho
                setTimeout(function() { alert.remove(); }, 500); // Remove do HTML
            }, 3000); // Espera 3 segundos
        });
    }
});
</script>

<?php 
// Fecha a div .container admin-main aberta no header
require_once __DIR__ . '/includes/footer.php'; 
?>
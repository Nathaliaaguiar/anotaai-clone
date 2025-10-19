<?php
// O verificador de segurança DEVE ser a primeira coisa a ser executada.
require_once 'includes/auth_check.php'; 

// O header e o resto do código só são processados se o auth_check.php permitir.
require_once 'includes/header.php';

// A variável $loja_id agora é definida com segurança dentro de 'auth_check.php'.
$mensagem = '';

// --- LÓGICA PARA ATUALIZAR CONFIGURAÇÕES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nome_loja'])) {
        $nome_loja = trim($_POST['nome_loja']);
        $stmt = $pdo->prepare("INSERT INTO configuracoes (loja_id, chave, valor) VALUES (?, 'nome_loja', ?) ON DUPLICATE KEY UPDATE valor = ?");
        if ($stmt->execute([$loja_id, $nome_loja, $nome_loja])) {
            $mensagem = '<p class="success">Nome da loja atualizado com sucesso!</p>';
        } else {
            $mensagem = '<p class="error">Erro ao atualizar o nome da loja.</p>';
        }
    }
    if (isset($_FILES['logo_loja']) && $_FILES['logo_loja']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['logo_loja'];
        $destino = __DIR__ . '/../img/logo_loja_' . $loja_id . '.png';
        if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
            $mensagem = '<p class="success">Logo da loja atualizada com sucesso!</p>';
        } else {
            $mensagem = '<p class="error">Erro ao salvar a imagem.</p>';
        }
    }
}

// --- BUSCA DE DADOS PARA O DASHBOARD ---
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
} catch (PDOException $e) { die("<strong>Erro na consulta do gráfico:</strong> " . $e->getMessage()); }
$labels_json = json_encode($labels_grafico);
$valores_json = json_encode($valores_grafico);
?>

<section class="dashboard">
    <h1>Dashboard</h1>
    <?php echo $mensagem; ?>
    <div class="stats-grid">
        <div class="stat-card"><h2>Pedidos Hoje</h2><p><?php echo $pedidos_hoje; ?></p></div>
        <div class="stat-card"><h2>Clientes da Loja</h2><p><?php echo $clientes_total; ?></p></div>
        <div class="stat-card"><h2>Produtos Ativos</h2><p><?php echo $produtos_ativos; ?></p></div>
    </div>
    <div class="grafico-container">
        <h2>Faturamento Mensal (Últimos 12 Meses)</h2>
        <?php if (!empty($labels_grafico)): ?>
            <canvas id="graficoFaturamentoMensal"></canvas>
        <?php else: ?>
            <div class="aviso-sem-dados"><p>Ainda não há dados de faturamento para exibir.</p><small>O gráfico aparecerá aqui quando tiver pedidos com o status "Entregue".</small></div>
        <?php endif; ?>
    </div>
    <div class="config-grid">
        <div class="form-wrapper">
            <h2>Nome da Loja</h2>
            <form action="dashboard.php" method="POST">
                <div class="form-group"><label for="nome_loja">Nome que aparecerá para os clientes:</label><input type="text" id="nome_loja" name="nome_loja" value="<?php echo htmlspecialchars($nome_loja_atual); ?>" required></div>
                <button type="submit" class="btn">Salvar Nome</button>
            </form>
        </div>
        <div class="form-wrapper">
            <h2>Alterar Logo da Loja</h2>
            <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                <div class="form-group"><label for="logo_loja">Selecione a imagem (.png):</label><input type="file" name="logo_loja" id="logo_loja" required accept="image/png"></div>
                <button type="submit" class="btn">Salvar Logo</button>
            </form>
        </div>
       
            </form>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvasGrafico = document.getElementById('graficoFaturamentoMensal');
    if (canvasGrafico && <?php echo json_encode(!empty($labels_grafico)); ?>) {
        const ctx = canvasGrafico.getContext('2d');
        const labels = <?php echo $labels_json; ?>;
        const dataValues = <?php echo $valores_json; ?>;
        const bodyStyles = getComputedStyle(document.documentElement);
        const fontColor = bodyStyles.getPropertyValue('--admin-text').trim() || '#333';
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Faturamento Mensal (R$)',
                    data: dataValues,
                    backgroundColor: 'rgba(255, 107, 0, 0.7)',
                    borderColor: 'rgba(255, 107, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, layout: { padding: 10 }, scales: { y: { beginAtZero: true, ticks: { color: fontColor, callback: (v) => 'R$ ' + v.toLocaleString('pt-BR') } }, x: { ticks: { color: fontColor } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `R$ ${c.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` } } } }
        });
    }
    const themeRadios = document.querySelectorAll('input[name="theme_selector"]');
    const currentTheme = localStorage.getItem('adminTheme') || 'tema-claro';
    const radioToSelect = document.querySelector(`input[value="${currentTheme}"]`);
    if (radioToSelect) { radioToSelect.checked = true; }
    themeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            localStorage.setItem('adminTheme', this.value);
            document.documentElement.className = this.value;
            location.reload(); 
        });
    });
});
</script>
<style>
.config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-top: 2.5rem; }
.aviso-sem-dados { text-align: center; padding: 40px; color: var(--admin-text-light, #888); background: var(--admin-bg-light, #f9f9f9); border-radius: 8px; }
.aviso-sem-dados p { font-size: 1.1rem; font-weight: 500; margin: 0; }
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

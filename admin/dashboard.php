<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

$mensagem = '';

// --- LÓGICA UNIFICADA PARA ATUALIZAR CONFIGURAÇÕES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se for o formulário do NOME DA LOJA
    if (isset($_POST['nome_loja'])) {
        $nome_loja = trim($_POST['nome_loja']);
        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'nome_loja'");
        if ($stmt->execute([$nome_loja])) {
            $mensagem = '<p class="success">Nome da loja atualizado com sucesso!</p>';
        } else {
            $mensagem = '<p class="error">Erro ao atualizar o nome da loja.</p>';
        }
    }
    // Se for o formulário da LOGO (do seu arquivo original)
    if (isset($_FILES['logo_loja']) && $_FILES['logo_loja']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['logo_loja'];
        $destino = __DIR__ . '/../img/logo_loja.png';
        if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
            $mensagem = '<p class="success">Logo da loja atualizada com sucesso!</p>';
        } else {
            $mensagem = '<p class="error">Erro ao salvar a imagem.</p>';
        }
    }
}

// --- Busca de Dados para o Dashboard ---
$pedidos_hoje = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE DATE(data) = CURDATE()")->fetchColumn();
$clientes_total = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$produtos_ativos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();

// --- NOVO: Busca o nome atual da loja para exibir no formulário ---
$stmt_nome_loja = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'nome_loja'");
$nome_loja_atual = $stmt_nome_loja->fetchColumn();

// --- Preparação dos dados para o Gráfico (do seu arquivo original) ---
$labels_grafico = [];
$valores_grafico = [];
try {
    $stmt = $pdo->query("
        SELECT 
            YEAR(data) as ano, 
            MONTH(data) as mes, 
            SUM(total) as faturamento_mensal
        FROM 
            pedidos
        WHERE 
            status = 'entregue' AND data IS NOT NULL AND data > '1971-01-01'
        GROUP BY 
            YEAR(data), MONTH(data)
        ORDER BY 
            ano, mes
        LIMIT 12
    ");
    $dados_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($dados_grafico) {
        $meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        foreach ($dados_grafico as $dado) {
            $labels_grafico[] = $meses[(int)$dado['mes']] . '/' . $dado['ano'];
            $valores_grafico[] = $dado['faturamento_mensal'];
        }
    }
} catch (PDOException $e) {
    die("<strong>Erro na consulta do gráfico:</strong> " . $e->getMessage());
}
$labels_json = json_encode($labels_grafico);
$valores_json = json_encode($valores_grafico);
?>

<section class="dashboard">
    <h1>Dashboard</h1>
    <?php echo $mensagem; ?>

    <div class="stats-grid">
        <div class="stat-card"><h2>Pedidos Hoje</h2><p><?php echo $pedidos_hoje; ?></p></div>
        <div class="stat-card"><h2>Clientes Totais</h2><p><?php echo $clientes_total; ?></p></div>
        <div class="stat-card"><h2>Produtos Ativos</h2><p><?php echo $produtos_ativos; ?></p></div>
    </div>

    <div class="grafico-container">
        <h2>Faturamento Mensal (Pedidos Entregues)</h2>
        <?php if (!empty($labels_grafico)): ?>
            <canvas id="graficoFaturamentoMensal"></canvas>
        <?php else: ?>
            <div class="aviso-sem-dados">
                <p>Ainda não há dados de faturamento para exibir.</p>
                <small>O gráfico aparecerá aqui quando você tiver pedidos com o status "Entregue".</small>
            </div>
        <?php endif; ?>
    </div>

    <div class="config-grid">
        <div class="form-wrapper">
            <h2>Nome da Loja</h2>
            <form action="dashboard.php" method="POST">
                <div class="form-group">
                    <label for="nome_loja">Nome que aparecerá para os clientes:</label>
                    <input type="text" id="nome_loja" name="nome_loja" value="<?php echo htmlspecialchars($nome_loja_atual); ?>" required>
                </div>
                <button type="submit" class="btn">Salvar Nome</button>
            </form>
        </div>
        
        <div class="form-wrapper">
            <h2>Alterar Logo da Loja</h2>
            <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="logo_loja">Selecione a imagem:</label>
                    <input type="file" name="logo_loja" id="logo_loja" required accept="image/*">
                </div>
                <button type="submit" class="btn">Salvar Logo</button>
            </form>
        </div>

        <div class="form-wrapper">
            <h2>Preferências de Tema</h2>
            <form id="theme-form">
                <div class="form-group">
                    <label class="radio-label"><input type="radio" name="theme_selector" value="tema-claro"> Tema Claro</label>
                    <label class="radio-label"><input type="radio" name="theme_selector" value="tema-escuro"> Tema Escuro</label>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvasGrafico = document.getElementById('graficoFaturamentoMensal');
    if (canvasGrafico) {
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
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: 28 },
                scales: { 
                    y: { beginAtZero: true, ticks: { color: fontColor, callback: (v) => 'R$ ' + v.toLocaleString('pt-BR') } }, 
                    x: { ticks: { color: fontColor } } 
                },
                plugins: { 
                    legend: { display: false }, 
                    tooltip: { callbacks: { label: (c) => `R$ ${c.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` } } 
                }
            }
        });
    }

    const themeRadios = document.querySelectorAll('input[name="theme_selector"]');
    const currentTheme = localStorage.getItem('adminTheme') || 'tema-claro';
    const radioToSelect = document.querySelector(`input[value="${currentTheme}"]`);
    if (radioToSelect) { radioToSelect.checked = true; }
    themeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const selectedTheme = this.value;
            localStorage.setItem('adminTheme', selectedTheme);
            document.documentElement.className = selectedTheme;
            location.reload(); 
        });
    });
});
</script>

<style>
.config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 2.5rem; }
.aviso-sem-dados { text-align: center; padding: 40px; color: var(--admin-text, #888); }
.aviso-sem-dados p { font-size: 1.2rem; font-weight: 500; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
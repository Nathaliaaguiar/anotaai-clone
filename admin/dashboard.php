<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

$mensagem = '';

// --- LÓGICA PARA PROCESSAR UPLOAD DA LOGO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_loja'])) {
    $arquivo = $_FILES['logo_loja'];
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $destino = __DIR__ . '/../img/logo_loja.png';
        if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
            $mensagem = '<p class="success">Logo da loja atualizada com sucesso!</p>';
        } else {
            $mensagem = '<p class="error">Erro ao salvar a imagem.</p>';
        }
    }
}

// --- Contadores do Dashboard ---
$pedidos_hoje = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE DATE(data) = CURDATE()")->fetchColumn();
$clientes_total = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$produtos_ativos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();

// --- Preparação dos dados para o Gráfico (COM BLOCO DE SEGURANÇA) ---
$labels_grafico = [];
$valores_grafico = [];

try {
    // SQL mais seguro que ignora datas inválidas
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
    // Se a consulta falhar, esta mensagem será exibida em vez de quebrar a página
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
            <h2>Alterar Logo da Loja</h2>
            <p>Envie a imagem que aparecerá para os clientes no site.</p>
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
            <p>Escolha o tema visual para o seu painel de admin.</p>
            <form id="theme-form">
                <div class="form-group">
                    <label class="radio-label">
                        <input type="radio" name="theme_selector" value="tema-claro"> Tema Claro
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="theme_selector" value="tema-escuro"> Tema Escuro
                    </label>
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
                layout: {
                    padding: 28 // Adiciona respiro interno ao gráfico
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { color: fontColor, callback: (v) => 'R$ ' + v.toLocaleString('pt-BR') } 
                    }, 
                    x: { 
                        ticks: { color: fontColor } 
                    } 
                },
                plugins: { 
                    legend: { display: false }, 
                    tooltip: { callbacks: { label: (c) => `R$ ${c.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` } } 
                }
            }
        });
    }

    // --- Lógica do Seletor de Tema ---
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
/* Estilo para organizar os formulários lado a lado */
.config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 2.5rem; }
/* Estilo para a mensagem de "sem dados" */
.aviso-sem-dados { text-align: center; padding: 40px; color: var(--admin-text, #888); }
.aviso-sem-dados p { font-size: 1.2rem; font-weight: 500; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
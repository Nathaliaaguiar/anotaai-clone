<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// --- Contadores do Dashboard ---
$pedidos_hoje = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE DATE(data) = CURDATE()")->fetchColumn();
$clientes_total = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$produtos_ativos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();


// --- 1. PREPARAÇÃO DOS DADOS PARA O GRÁFICO ---

// Vamos buscar o faturamento total de pedidos 'entregue' de cada mês
$stmt = $pdo->query("
    SELECT 
        YEAR(data) as ano, 
        MONTH(data) as mes, 
        SUM(total) as faturamento_mensal
    FROM 
        pedidos
    WHERE 
        status = 'entregue'
    GROUP BY 
        YEAR(data), MONTH(data)
    ORDER BY 
        ano, mes
    LIMIT 12 -- Limita aos últimos 12 meses para não poluir o gráfico
");

$dados_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels_grafico = [];
$valores_grafico = [];

// Formata os dados para o formato que o Chart.js precisa
$meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

foreach ($dados_grafico as $dado) {
    // Cria a label no formato "Mês/Ano", ex: "Ago/2025"
    $labels_grafico[] = $meses[(int)$dado['mes']] . '/' . $dado['ano'];
    $valores_grafico[] = $dado['faturamento_mensal'];
}

// Converte os arrays PHP para JSON para serem usados no JavaScript
$labels_json = json_encode($labels_grafico);
$valores_json = json_encode($valores_grafico);

?>

<section class="dashboard">
    <h1>Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h2>Pedidos Hoje</h2>
            <p><?php echo $pedidos_hoje; ?></p>
        </div>
        <div class="stat-card">
            <h2>Clientes Totais</h2>
            <p><?php echo $clientes_total; ?></p>
        </div>
        <div class="stat-card">
            <h2>Produtos Ativos</h2>
            <p><?php echo $produtos_ativos; ?></p>
        </div>
    </div>

    <div class="grafico-container">
        <h2>Faturamento Mensal (Pedidos Entregues)</h2>
        <canvas id="graficoFaturamentoMensal"></canvas>
    </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pega o elemento canvas do HTML
    const ctx = document.getElementById('graficoFaturamentoMensal');

    // Pega os dados que o PHP preparou
    const labels = <?php echo $labels_json; ?>;
    const dataValues = <?php echo $valores_json; ?>;

    // Cria o gráfico
    new Chart(ctx, {
        type: 'bar', // Tipo de gráfico: barras
        data: {
            labels: labels, // As labels do eixo X (Mês/Ano)
            datasets: [{
                label: 'Faturamento Mensal (R$)',
                data: dataValues, // Os valores do eixo Y (faturamento)
                backgroundColor: 'rgba(255, 107, 0, 0.7)', // Laranja com transparência
                borderColor: 'rgba(255, 107, 0, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        // Formata o eixo Y para mostrar "R$"
                        callback: function(value, index, values) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Esconde a legenda, pois o título já é claro
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once 'includes/header.php'; // Carrega header.css
require_once 'includes/auth_check.php';

$loja_id = $_SESSION['admin_loja_id'];
$mensagem_perfil = '';
$erro_perfil = '';

// --- FUNÇÃO DE GEOCODING ---
function pegarLatLng($endereco) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query(['q' => $endereco, 'format' => 'json', 'limit' => 1]);
    $opts = ["http" => ["header" => "User-Agent: PlatafoodAdmin/1.0\r\n"]];
    $context = stream_context_create($opts);
    try {
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        if (!empty($data)) {
            return ['lat' => $data[0]['lat'], 'lng' => $data[0]['lon']];
        }
    } catch (Exception $e) { return null; }
    return null;
}

// --- ATUALIZAR PERFIL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $bairro   = $_POST['bairro'] ?? '';
    $cidade   = $_POST['cidade'] ?? '';
    $estado   = $_POST['estado'] ?? '';
    $cep      = $_POST['cep'] ?? '';
    $tempo_min = $_POST['tempo_min'] ?? 30;
    $tempo_max = $_POST['tempo_max'] ?? 60;

    $endereco_completo = "$endereco, $bairro, $cidade - $estado, $cep";
    $coords = pegarLatLng($endereco_completo);
    $lat = $coords ? $coords['lat'] : null;
    $lng = $coords ? $coords['lng'] : null;

    try {
        $stmt = $pdo->prepare("
            UPDATE lojas 
            SET telefone=?, endereco=?, bairro=?, cidade=?, estado=?, cep=?, 
                tempo_entrega_min=?, tempo_entrega_max=?, latitude=?, longitude=?
            WHERE id=?
        ");
        $stmt->execute([$telefone, $endereco, $bairro, $cidade, $estado, $cep, $tempo_min, $tempo_max, $lat, $lng, $loja_id]);
        $mensagem_perfil = "Perfil atualizado com sucesso!";
    } catch (PDOException $e) {
        $erro_perfil = "Erro ao atualizar: " . $e->getMessage();
    }
}

// --- BUSCAS NO BANCO ---

// 1. Dados da Loja
$stmt_loja = $pdo->prepare("SELECT * FROM lojas WHERE id = ?");
$stmt_loja->execute([$loja_id]);
$loja = $stmt_loja->fetch();

// 2. Top Clientes
$stmt_top = $pdo->prepare("
    SELECT u.nome, u.telefone, COUNT(p.id) as total_pedidos, SUM(p.total) as valor_gasto
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.loja_id = ? AND p.status = 'entregue'
    GROUP BY u.id
    ORDER BY total_pedidos DESC
    LIMIT 10
");
$stmt_top->execute([$loja_id]);
$top_clientes = $stmt_top->fetchAll();

// 3. Clientes Recentes
$stmt_recentes = $pdo->prepare("
    SELECT DISTINCT u.nome, u.telefone, MAX(p.data) as ultima_compra
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.loja_id = ?
    GROUP BY u.id
    ORDER BY ultima_compra DESC
    LIMIT 10
");
$stmt_recentes->execute([$loja_id]);
$recentes = $stmt_recentes->fetchAll();

// 4. Favoritos (CORRIGIDO: nome da tabela é 'lojas_favoritas')
try {
    $stmt_fav = $pdo->prepare("
        SELECT u.nome, u.telefone 
        FROM lojas_favoritas f 
        JOIN usuarios u ON f.usuario_id = u.id 
        WHERE f.loja_id = ?
        LIMIT 20
    ");
    $stmt_fav->execute([$loja_id]);
    $favoritos = $stmt_fav->fetchAll();
} catch (Exception $e) {
    $favoritos = [];
}
?>

<link rel="stylesheet" href="../css/cliente.css?v=3">

<section class="admin-crud">
    
    <h1>Perfil da Loja & Clientes</h1>

    <div class="perfil-loja-card">
        <?php if($mensagem_perfil): ?>
            <div class="success" style="position:static; margin-bottom:15px; animation:none; transform:none;"><?php echo $mensagem_perfil; ?></div>
        <?php endif; ?>
        <?php if($erro_perfil): ?>
            <div class="error" style="position:static; margin-bottom:15px; animation:none; transform:none;"><?php echo $erro_perfil; ?></div>
        <?php endif; ?>

        <form action="clientes.php" method="POST">
            <input type="hidden" name="atualizar_perfil" value="1">
            <div class="perfil-grid">
                <div>
                    <h3><i class="fa-solid fa-map-location-dot" style="color:#ff6f00;"></i> Endereço e Contato</h3>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" name="telefone" value="<?php echo htmlspecialchars($loja['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000">
                        </div>
                        <div class="form-group">
                            <label>CEP</label>
                            <input type="text" name="cep" id="cep" value="<?php echo htmlspecialchars($loja['cep'] ?? ''); ?>" maxlength="9" onblur="buscarEndereco()">
                            <span id="cep-status"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Endereço</label>
                        <input type="text" name="endereco" id="endereco" value="<?php echo htmlspecialchars($loja['endereco'] ?? ''); ?>">
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label>Bairro</label>
                            <input type="text" name="bairro" id="bairro" value="<?php echo htmlspecialchars($loja['bairro'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Cidade</label>
                            <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($loja['cidade'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>UF</label>
                            <input type="text" name="estado" id="estado" value="<?php echo htmlspecialchars($loja['estado'] ?? ''); ?>" maxlength="2">
                        </div>
                    </div>
                </div>
                <div>
                    <h3><i class="fa-solid fa-stopwatch" style="color:#ff6f00;"></i> Tempo de Entrega (min)</h3>
                    <div class="form-group">
                        <label>Mínimo</label>
                        <input type="number" name="tempo_min" value="<?php echo htmlspecialchars($loja['tempo_entrega_min'] ?? 30); ?>">
                    </div>
                    <div class="form-group">
                        <label>Máximo</label>
                        <input type="number" name="tempo_max" value="<?php echo htmlspecialchars($loja['tempo_entrega_max'] ?? 60); ?>">
                    </div>
                    <button type="submit" class="btn-save-perfil"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="clientes-grid">
        
        <div class="card-lista">
            <h2><span><i class="fa-solid fa-trophy"></i> Top Compradores</span></h2>
            <div class="table-wrapper">
                <table class="tabela-moderna">
                    <thead><tr><th>Nome</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($top_clientes as $c): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($c['nome']); ?></strong><br>
                                <small style="color:#888;"><?php echo htmlspecialchars($c['telefone'] ?? ''); ?></small>
                            </td>
                            <td style="color:green; font-weight:bold;">R$ <?php echo number_format($c['valor_gasto'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($top_clientes)) echo "<tr><td colspan='2' align='center'>Sem dados.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-lista">
            <h2><span><i class="fa-solid fa-receipt"></i> Recentes</span></h2>
            <div class="table-wrapper">
                <table class="tabela-moderna">
                    <thead><tr><th>Nome</th><th>Data</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentes as $r): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($r['nome']); ?></strong><br>
                                <small style="color:#888;"><?php echo htmlspecialchars($r['telefone'] ?? ''); ?></small>
                            </td>
                            <td><?php echo date('d/m H:i', strtotime($r['ultima_compra'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recentes)) echo "<tr><td colspan='2' align='center'>Sem dados.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-lista">
            <h2><span><i class="fa-solid fa-heart"></i> Favoritaram</span></h2>
            <div class="table-wrapper">
                <table class="tabela-moderna">
                    <thead><tr><th>Cliente</th></tr></thead>
                    <tbody>
                        <?php foreach ($favoritos as $fav): ?>
                        <tr>
                            <td>
                                <i class="fa-solid fa-user-check" style="color:#aaa; margin-right:5px;"></i>
                                <strong><?php echo htmlspecialchars($fav['nome']); ?></strong><br>
                                <small style="color:#888; margin-left: 20px;"><?php echo htmlspecialchars($fav['telefone'] ?? ''); ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($favoritos)) echo "<tr><td align='center' style='color:#999;'>Ninguém favoritou ainda.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<script>
function buscarEndereco() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    const status = document.getElementById('cep-status');
    if (cep.length === 8) {
        status.innerText = 'Buscando...'; status.className = 'cep-buscando';
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(res => res.json())
            .then(data => {
                if (data.erro) { status.innerText = 'CEP inválido'; status.className = 'cep-invalid'; }
                else {
                    document.getElementById('endereco').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;
                    status.innerText = '✓ Encontrado'; status.className = 'cep-valid';
                }
            }).catch(() => { status.innerText = 'Erro'; status.className = 'cep-invalid'; });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
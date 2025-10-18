<?php
require_once __DIR__ . '/../includes/header.php';

// Função para calcular distância entre duas coordenadas (Haversine)
function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; // Raio da Terra em km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// Função para verificar se a loja está aberta
function verificarLojaAberta($pdo, $loja_id) {
    $dias_semana = ['domingo','segunda','terca','quarta','quinta','sexta','sabado'];
    date_default_timezone_set('America/Sao_Paulo');
    $dia_atual = $dias_semana[date('w')];
    $hora_atual = date('H:i:s');

    $stmt = $pdo->prepare("SELECT horario_abertura, horario_fechamento FROM horarios_funcionamento WHERE loja_id = ? AND dia_semana = ?");
    $stmt->execute([$loja_id, $dia_atual]);
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($horario && $horario['horario_abertura'] !== null && $hora_atual >= $horario['horario_abertura'] && $hora_atual <= $horario['horario_fechamento']) {
        return true;
    }
    return false;
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    die("Erro: usuário não logado. Faça login para continuar.");
}

// Pega lat/lng do usuário
$stmt = $pdo->prepare("SELECT lat, lng FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !$usuario['lat'] || !$usuario['lng']) {
    die("Erro: localização do usuário não encontrada. Atualize seu endereço.");
}

$userLat = $usuario['lat'];
$userLng = $usuario['lng'];

// Busca lojas aprovadas e ativas
try {
    $stmt_lojas = $pdo->prepare("
        SELECT l.id,
               COALESCE(c.valor, l.nome) AS nome,
               l.endereco,
               l.bairro,
               l.lat,
               l.lng
        FROM lojas l
        LEFT JOIN configuracoes c ON l.id = c.loja_id AND c.chave = 'nome_loja'
        WHERE l.aprovado = 1 AND l.ativa = 1
        ORDER BY nome ASC
    ");
    $stmt_lojas->execute();
    $todasLojas = $stmt_lojas->fetchAll(PDO::FETCH_ASSOC);

    $lojas = [];
    $raioMaximo = 5; // km

    foreach ($todasLojas as $loja) {
        if ($loja['lat'] && $loja['lng']) {
            $distancia = haversine($userLat, $userLng, $loja['lat'], $loja['lng']);
            if ($distancia <= $raioMaximo) {
                $loja['distancia'] = $distancia;
                $lojas[] = $loja;
            }
        }
    }
} catch (PDOException $e) {
    die("Erro ao buscar lojas: " . $e->getMessage());
}
?>

<style>
.lojas-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
.lojas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
.loja-card { border: 1px solid #eee; border-radius: 12px; overflow: hidden; text-decoration: none; color: #333; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: all 0.2s ease; display: flex; flex-direction: column; }
.loja-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
.loja-logo-wrapper { width: 100%; height: 150px; background-color: #f7f7f7; display: flex; align-items: center; justify-content: center; }
.loja-logo { max-width: 90%; max-height: 90%; object-fit: contain; }
.loja-info { padding: 1rem; position: relative; flex-grow: 1; display: flex; flex-direction: column; }
.loja-info h3 { margin: 0 0 0.5rem 0; font-size: 1.2rem; }
.loja-info p { margin: 0; color: #777; font-size: 0.9rem; }
.status-loja { position: absolute; top: 1rem; right: 1rem; padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
.status-aberta { background-color: #d4edda; color: #155724; }
.status-fechada { background-color: #f8d7da; color: #721c24; }
</style>

<main class="lojas-container">
    <h1>Lojas Disponíveis</h1>
    <p>Escolha uma loja próxima a você para ver o cardápio.</p>

    <div class="lojas-grid">
        <?php if (empty($lojas)): ?>
            <p>Nenhuma loja disponível próxima a você no momento.</p>
        <?php else: ?>
            <?php foreach ($lojas as $loja): ?>
                <?php
                    $estaAberta = verificarLojaAberta($pdo, $loja['id']);
                    $statusClasse = $estaAberta ? 'status-aberta' : 'status-fechada';
                    $statusTexto = $estaAberta ? 'Aberta' : 'Fechada';
                ?>
                <a href="loja_menu.php?loja_id=<?= $loja['id'] ?>" class="loja-card">
                    <div class="loja-logo-wrapper">
                        <img src="../img/logo_loja_<?= $loja['id'] ?>.png" 
                             alt="Logo da loja <?= htmlspecialchars($loja['nome']) ?>" 
                             class="loja-logo"
                             onerror="this.onerror=null;this.src='https://placehold.co/200x150/f0f0f0/333?text=Logo'">
                    </div>
                    <div class="loja-info">
                        <span class="status-loja <?= $statusClasse ?>"><?= $statusTexto ?></span>
                        <h3><?= htmlspecialchars($loja['nome']) ?></h3>
                        <p><?= htmlspecialchars($loja['bairro']) ?> - <?= number_format($loja['distancia'], 2) ?> km</p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

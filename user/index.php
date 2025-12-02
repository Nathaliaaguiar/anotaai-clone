<?php
// index.php (Home do Consumidor)
session_start();
require_once __DIR__ . '/../config/db.php'; 

// --- FUNÇÕES PHP ---
function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

function verificarLojaAberta($pdo, $loja_id) {
    $dias_semana = ['domingo','segunda','terca','quarta','quinta','sexta','sabado'];
    date_default_timezone_set('America/Sao_Paulo');
    $dia_atual = $dias_semana[date('w')];
    $hora_atual = date('H:i:s');
    $stmt = $pdo->prepare("SELECT horario_abertura, horario_fechamento FROM horarios_funcionamento WHERE loja_id = ? AND dia_semana = ? AND ativo = 1");
    $stmt->execute([$loja_id, array_search($dia_atual, $dias_semana)]);
    $horario = $stmt->fetch();
    if ($horario) return ($hora_atual >= $horario['horario_abertura'] && $hora_atual <= $horario['horario_fechamento']);
    return false;
}

// Filtros
$user_lat = $_GET['lat'] ?? null;
$user_lng = $_GET['lng'] ?? null;
$search_query = $_GET['search'] ?? '';
$categoria_filter = $_GET['categoria_id'] ?? '';

// Busca Lojas
$sql = "SELECT l.*, c.nome as categoria_nome FROM lojas l LEFT JOIN categorias c ON l.id = c.loja_id WHERE l.aprovado = 1 AND l.ativa = 1";
$params = [];
if ($search_query) { $sql .= " AND l.nome LIKE ?"; $params[] = "%$search_query%"; }
if ($categoria_filter) { $sql .= " AND c.id = ?"; $params[] = $categoria_filter; }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query("SELECT DISTINCT nome, id FROM categorias GROUP BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlataFood - Peça seu Delivery</title>
    <link rel="stylesheet" href="index.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <header class="consumer-header">
        <div class="header-container">
            <div class="app-logo">Plata<span>Food</span></div>
            <div class="user-actions">
                <?php if(isset($_SESSION['usuario_id'])): ?>
                    <a href="perfil.php"><i class="fa-solid fa-user"></i> Minha Conta</a>
                    <a href="logout.php">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login-header">Entrar / Cadastrar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="search-hero">
        <h2>Tudo pra facilitar seu dia</h2>
        <form action="index.php" method="GET" class="search-box">
            <i class="fa-solid fa-search" style="color:#aaa; margin-left:15px;"></i>
            <input type="text" name="search" placeholder="O que você quer comer hoje?" value="<?php echo htmlspecialchars($search_query); ?>">
            
            
               
            </select>
            
            <button type="button" onclick="getLocation()" title="Usar minha localização" style="background:none; color:#555; width:40px;">
                <i class="fa-solid fa-location-crosshairs"></i>
            </button>
            
            <button type="submit" class="search-btn"><i class="fa-solid fa-arrow-right"></i></button>

            <input type="hidden" name="lat" id="lat" value="<?php echo htmlspecialchars($user_lat); ?>">
            <input type="hidden" name="lng" id="lng" value="<?php echo htmlspecialchars($user_lng); ?>">
        </form>
    </section>

    <div class="container">
        <h3 class="section-title">Lojas Disponíveis</h3>
        
        <div class="store-grid">
            <?php if (count($lojas) > 0): ?>
                <?php foreach ($lojas as $loja): ?>
                    <?php 
                        $aberta = verificarLojaAberta($pdo, $loja['id']); 
                        $distancia = ($user_lat && $user_lng && $loja['latitude'] && $loja['longitude']) ? haversine($user_lat, $user_lng, $loja['latitude'], $loja['longitude']) : null;
                        
                        // --- CORREÇÃO AQUI: CAMINHO DA PASTA 'img' ---
                        // Antes estava apontando para 'admin/img', mas o dashboard salva na 'img' da raiz
                        
                        $caminho_fisico = __DIR__ . '/../img/logo_loja_' . $loja['id'] . '.png';
                        
                        if (file_exists($caminho_fisico)) {
                            // Se existe, usa o caminho relativo voltando para a raiz e entrando em img
                            $img_url = "../img/logo_loja_{$loja['id']}.png?v=" . time();
                        } else {
                            // Placeholder
                            $img_url = "https://placehold.co/80x80/f0f0f0/ccc?text=Logo";
                        }
                        
                        // Favorito check
                        $is_fav = false;
                        if (isset($_SESSION['usuario_id'])) {
                            try {
                                $stmt_fav = $pdo->prepare("SELECT id FROM lojas_favoritas WHERE usuario_id = ? AND loja_id = ?");
                                $stmt_fav->execute([$_SESSION['usuario_id'], $loja['id']]);
                                if($stmt_fav->fetch()) $is_fav = true;
                            } catch (Exception $e) {}
                        }
                    ?>

                    <div class="store-card">
                        <button class="fav-btn <?php echo $is_fav ? 'active' : ''; ?>" 
                                onclick="toggleFav(this, <?php echo $loja['id']; ?>)">
                            <i class="<?php echo $is_fav ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                        </button>

                        <a href="loja_menu.php?loja_id=<?php echo $loja['id']; ?>">
                            <div class="card-banner"></div>
                            <div class="card-content">
                                <img src="<?php echo $img_url; ?>" class="store-logo" alt="Logo">
                                
                                <div class="store-info">
                                    <span class="store-name"><?php echo htmlspecialchars($loja['nome']); ?></span>
                                    <div class="store-meta">
                                        <span style="color:#f1c40f;"><i class="fa-solid fa-star"></i> 4.8</span>
                                        <span class="meta-dot"></span>
                                        <span><?php echo htmlspecialchars($loja['categoria_nome'] ?? 'Lanches'); ?></span>
                                        <?php if($distancia): ?>
                                            <span class="meta-dot"></span>
                                            <span><?php echo number_format($distancia, 1); ?> km</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="store-footer">
                                <span>30-40 min</span>
                                <span class="badge <?php echo $aberta ? 'open' : 'closed'; ?>">
                                    <?php echo $aberta ? 'Aberto' : 'Fechado'; ?>
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhuma loja encontrada.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function getLocation() {
        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('lat').value = pos.coords.latitude;
                document.getElementById('lng').value = pos.coords.longitude;
                document.querySelector('.search-box').submit();
            });
        } else { alert("Geolocalização não suportada."); }
    }

    function toggleFav(btn, lojaId) {
        fetch('ajax_favoritar_loja.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({loja_id: lojaId, action: btn.classList.contains('active') ? 'unfavorite' : 'favorite'})
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                btn.classList.toggle('active');
                const icon = btn.querySelector('i');
                icon.classList.toggle('fa-solid');
                icon.classList.toggle('fa-regular');
            } else if(data.message && data.message.includes('autenticado')) {
                window.location.href = 'login.php';
            } else {
                alert(data.message || 'Erro ao favoritar');
            }
        });
    }
    </script>
    <?php
// Adicione isso no final do arquivo para carregar o rodapé
require_once __DIR__ . '/../includes/footer.php';
?>
</body>
</html>

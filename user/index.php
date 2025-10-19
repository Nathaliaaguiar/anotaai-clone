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

// Busca lojas aprovadas e ativas
try {
    $usuario_id_logado = $_SESSION['usuario_id'] ?? null;

    $query = "
        SELECT
               l.id,
               COALESCE(c.valor, l.nome) AS nome,
               l.endereco,
               l.bairro,
               l.lat,
               l.lng,
               (SELECT COUNT(*) FROM lojas_favoritas lf WHERE lf.loja_id = l.id AND lf.usuario_id = :usuario_id) AS favoritada
        FROM lojas l
        LEFT JOIN configuracoes c ON l.id = c.loja_id AND c.chave = 'nome_loja'
        WHERE l.aprovado = 1 AND l.ativa = 1
        ORDER BY nome ASC
    ";

    $stmt_lojas = $pdo->prepare($query);
    $stmt_lojas->execute(['usuario_id' => $usuario_id_logado]);
    $todasLojas = $stmt_lojas->fetchAll(PDO::FETCH_ASSOC);

    $lojas = [];

    // Se o usuário estiver logado, filtrar por proximidade
    if (isset($_SESSION['usuario_id'])) {
        $stmt = $pdo->prepare("SELECT lat, lng FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && $usuario['lat'] && $usuario['lng']) {
            $userLat = $usuario['lat'];
            $userLng = $usuario['lng'];
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
        } else {
            $lojas = $todasLojas;
        }
    } else {
        $lojas = $todasLojas;
    }
} catch (PDOException $e) {
    die("Erro ao buscar lojas: " . $e->getMessage());
}
?>

<head>
    <title>Lojas Disponíveis - PlataFood</title>
    <link rel="stylesheet" href="./css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
   <style>
    .lojas-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
    .lojas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
    .loja-card { border: 1px solid #eee; border-radius: 12px; overflow: hidden; text-decoration: none; color: #333; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: all 0.2s ease; display: flex; flex-direction: column; }
    .loja-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
    .loja-logo-wrapper { width: 100%; height: 150px; background-color: #f7f7f7; display: flex; align-items: center; justify-content: center; }
    .loja-logo { max-width: 90%; max-height: 90%; object-fit: contain; }

    /* --- ESTILO PRINCIPAL DO CARD INFO --- */
    .loja-info {
        padding: 1rem;
        position: relative;
        flex-grow: 1;
        /* Adiciona padding no topo para criar espaço para os ícones */
        padding-top: 3.5rem; 
    }

    /* --- TÍTULO E PARÁGRAFO --- */
    .loja-info h3 { margin: 0 0 0.5rem 0; font-size: 1.2rem; }
    .loja-info p { margin: 0; color: #777; font-size: 0.9rem; }

    /* --- POSICIONAMENTO DOS ÍCONES NO TOPO --- */
    .status-loja {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.25rem 0.6rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    .status-aberta { background-color: #d4edda; color: #155724; }
    .status-fechada { background-color: #f8d7da; color: #721c24; }

    .favorite-btn {
        /* Posiciona o coração no canto superior esquerdo */
        position: absolute;
        top: 0.7rem; /* Alinhado verticalmente com o status */
        left: 1rem;
        z-index: 2;

        /* Estilos para parecer um ícone, não um botão */
        background: none;
        border: none;
        padding: 0.25rem;
        cursor: pointer;
        display: inline-flex;
    }
    .favorite-btn svg {
        transition: all 0.2s ease;
        stroke: #aaa;
        fill: none;
        pointer-events: none;
    }
    .favorite-btn.favorited svg {
        stroke: #e74c3c;
        fill: #e74c3c;
    }
    .favorite-btn:hover svg {
        transform: scale(1.15);
    }
</style>
</head>

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
                    $distanciaTexto = isset($loja['distancia']) ? number_format($loja['distancia'], 2).' km' : '';
                    $isFavoritedClass = ($loja['favoritada'] ?? 0) > 0 ? 'favorited' : '';
                ?>
                <a href="loja_menu.php?loja_id=<?= $loja['id'] ?>" class="loja-card">
                    <div class="loja-logo-wrapper">
                        <img src="../img/logo_loja_<?= $loja['id'] ?>.png"
                             alt="Logo da loja <?= htmlspecialchars($loja['nome']) ?>"
                             class="loja-logo"
                             onerror="this.onerror=null;this.src='https://placehold.co/200x150/f0f0f0/333?text=Logo'">
                    </div>
                    <div class="loja-info">
                        <button class="favorite-btn <?= $isFavoritedClass ?>" data-loja-id="<?= $loja['id'] ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>
                        
                        <span class="status-loja <?= $statusClasse ?>"><?= $statusTexto ?></span>
                        
                        <h3><?= htmlspecialchars($loja['nome']) ?></h3>
                        <p><?= htmlspecialchars($loja['bairro']) ?> <?= $distanciaTexto ? '- '.$distanciaTexto : '' ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');

    favoriteButtons.forEach(button => {
        button.addEventListener('click', async function(event) { // Usando async/await para facilitar
            event.preventDefault();
            event.stopPropagation();

            const lojaId = this.dataset.lojaId;
            const action = this.classList.contains('favorited') ? 'unfavorite' : 'favorite';

            try {
                const response = await fetch('ajax_favoritar_loja.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        loja_id: lojaId,
                        action: action
                    })
                });

                // Tenta ler a resposta como JSON, mesmo que seja um erro
                const data = await response.json();

                if (!response.ok) {
                    // Se a resposta NÃO foi OK (ex: erro 401, 500), mas conseguimos ler o JSON de erro
                    throw new Error(data.message || 'Ocorreu um erro no servidor.');
                }

                if (data.success) {
                    this.classList.toggle('favorited');
                } else {
                    // Se a resposta foi OK, mas a operação falhou (ex: usuário não logado)
                    alert('Aviso: ' + data.message);
                    if (data.message.includes("autenticado")) {
                         window.location.href = 'login.php';
                    }
                }

            } catch (error) {
                // Este bloco pega erros de rede e erros lançados por nós
                console.error('Erro detalhado no Fetch:', error);
                alert('Falha na comunicação: ' + error.message);
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
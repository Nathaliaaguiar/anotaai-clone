<?php
require_once __DIR__ . '/../includes/header.php';

// Função para verificar se a loja está aberta, agora 100% compatível com a sua base de dados
function verificarLojaAberta($pdo, $loja_id) {
    $dias_semana = ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
    // Garante que a hora do servidor corresponde à sua localização
    date_default_timezone_set('America/Sao_Paulo'); 
    $dia_atual = $dias_semana[date('w')];
    $hora_atual = date('H:i:s');

    // [CORREÇÃO DO ERRO FATAL]
    // Removemos a coluna 'funciona' da consulta, pois ela não existe na sua tabela.
    $stmt = $pdo->prepare("SELECT horario_abertura, horario_fechamento FROM horarios_funcionamento WHERE loja_id = ? AND dia_semana = ?");
    $stmt->execute([$loja_id, $dia_atual]);
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);

    // [LÓGICA CORRIGIDA]
    // A loja está aberta se:
    // 1. Encontrarmos um horário para o dia de hoje.
    // 2. O horário de abertura NÃO for nulo.
    // 3. A hora atual estiver entre a abertura e o fecho.
    if ($horario && $horario['horario_abertura'] !== null && $hora_atual >= $horario['horario_abertura'] && $hora_atual <= $horario['horario_fechamento']) {
        return true; // Aberta
    }
    
    return false; // Fechada em todos os outros casos
}

// Busca todas as lojas ativas e aprovadas
try {
    $stmt_lojas = $pdo->prepare("SELECT id, nome, endereco, bairro FROM lojas WHERE aprovado = 1 AND ativa = 1 ORDER BY nome ASC");
    $stmt_lojas->execute();
    $lojas = $stmt_lojas->fetchAll(PDO::FETCH_ASSOC);
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
    <p>Escolha uma loja para ver o cardápio e fazer o seu pedido.</p>

    <div class="lojas-grid">
        <?php if (empty($lojas)): ?>
            <p>Nenhuma loja disponível no momento.</p>
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
                        <p><?= htmlspecialchars($loja['bairro']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>


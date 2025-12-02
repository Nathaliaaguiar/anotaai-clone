<?php
// perfil.php
require_once __DIR__ . '/../includes/header.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = '';
$erro = '';

// --- ATUALIZAR DADOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep']);
    $rua = trim($_POST['rua']);     // Apenas a rua
    $numero = trim($_POST['numero']); // Número separado
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);

    // Monta o endereço completo se não tiver coluna 'numero' no banco, 
    // ou salva separado se tiver. Aqui vou assumir que você quer salvar separado 
    // ou juntar no 'endereco' se preferir. 
    // LÓGICA: Salvar 'Rua, Numero' no campo endereco para compatibilidade.
    $endereco_completo = $rua;
    if(!empty($numero)) {
        // Se a rua já não terminar com o número, adiciona
        if(strpos($rua, $numero) === false) {
             $endereco_completo = $rua . ', ' . $numero;
        }
    }

    try {
        // Tenta atualizar. Se der erro de coluna 'numero' inexistente, ajustamos o SQL.
        // SQL Padrão (compatível com a tabela usuarios simples)
        $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?, cep=?, endereco=?, bairro=?, cidade=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $email, $telefone, $cep, $endereco_completo, $bairro, $cidade, $usuario_id]);
        
        $mensagem = "Dados atualizados com sucesso!";
        // Atualiza nome na sessão
        $_SESSION['usuario_nome'] = $nome;
        
    } catch (PDOException $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

// --- BUSCAR DADOS DO USUÁRIO ---
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user = $stmt->fetch();

// Tenta separar Rua e Número para o formulário (visual)
$endereco_db = $user['endereco'] ?? '';
$numero_visual = '';
$rua_visual = $endereco_db;

// Se tiver vírgula, tenta separar (ex: Rua Tal, 123)
if(strpos($endereco_db, ',') !== false) {
    $partes = explode(',', $endereco_db);
    $numero_visual = trim(end($partes)); // Pega a última parte
    // Remove o número da rua para mostrar limpo
    if(is_numeric($numero_visual)) {
        array_pop($partes);
        $rua_visual = trim(implode(',', $partes));
    } else {
        $numero_visual = ''; // Não era um número
    }
}

// --- BUSCAR PEDIDOS ---
$stmt_pedidos = $pdo->prepare("
    SELECT p.*, l.nome as nome_loja, l.logo as logo_loja 
    FROM pedidos p
    LEFT JOIN lojas l ON p.loja_id = l.id
    WHERE p.usuario_id = ? 
    ORDER BY p.id DESC
");
$stmt_pedidos->execute([$usuario_id]);
$pedidos = $stmt_pedidos->fetchAll();
?>

<link rel="stylesheet" href="index.css?v=<?php echo time(); ?>">

<div class="container profile-container">
    
    <aside class="profile-sidebar">
        <div class="user-brief">
            <div class="user-avatar-placeholder">
                <i class="fa-solid fa-user"></i>
            </div>
            <h3><?php echo htmlspecialchars($user['nome']); ?></h3>
            <span><?php echo htmlspecialchars($user['email']); ?></span>
        </div>

        <ul class="profile-menu">
            <li>
                <a href="#" class="active" onclick="showTab(event, 'tab-pedidos')">
                    <i class="fa-solid fa-receipt"></i> Meus Pedidos
                </a>
            </li>
            <li>
                <a href="#" onclick="showTab(event, 'tab-dados')">
                    <i class="fa-solid fa-address-card"></i> Meus Dados
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair da Conta
                </a>
            </li>
        </ul>
    </aside>

    <main class="profile-content">
        
        <?php if($mensagem): ?>
            <div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:20px; text-align:center;">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <?php if($erro): ?>
            <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:20px; text-align:center;">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <div id="tab-pedidos" class="tab-content">
            <div class="section-header">
                <h2><i class="fa-solid fa-bag-shopping"></i> Histórico de Pedidos</h2>
            </div>

            <?php if (count($pedidos) > 0): ?>
                <div class="order-list">
                    <?php foreach ($pedidos as $ped): ?>
                        <div class="order-card">
                            <div class="order-info">
                                <h4><?php echo htmlspecialchars($ped['nome_loja'] ?? 'Loja Desconhecida'); ?></h4>
                                <p>Pedido #<?php echo $ped['id']; ?></p>
                                <span class="order-date">
                                    <i class="fa-regular fa-calendar"></i> 
                                    <?php echo date('d/m/Y \à\s H:i', strtotime($ped['data'])); ?>
                                </span>
                            </div>
                            <div class="order-status-price">
                                <span class="order-price">R$ <?php echo number_format($ped['total'], 2, ',', '.'); ?></span>
                                <span class="status-badge status-<?php echo $ped['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $ped['status'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <p>Você ainda não fez nenhum pedido.</p>
                    <a href="index.php" class="btn-save-profile">Ir para o Início</a>
                </div>
            <?php endif; ?>
        </div>

        <div id="tab-dados" class="tab-content" style="display:none;">
            <div class="section-header">
                <h2><i class="fa-solid fa-user-pen"></i> Editar Perfil</h2>
            </div>

            <form action="perfil.php" method="POST">
                <input type="hidden" name="atualizar_perfil" value="1">
                
                <div class="profile-form-grid">
                    <div class="form-group form-full">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telefone / WhatsApp</label>
                        <input type="text" name="telefone" value="<?php echo htmlspecialchars($user['telefone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>CEP</label>
                        <input type="text" name="cep" id="cep" value="<?php echo htmlspecialchars($user['cep'] ?? ''); ?>" maxlength="9">
                        <small id="cep-msg"></small>
                    </div>
                    
                    <div class="form-group"></div> <div class="form-group">
                        <label>Rua / Avenida</label>
                        <input type="text" name="rua" id="rua" value="<?php echo htmlspecialchars($rua_visual); ?>">
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="numero" value="<?php echo htmlspecialchars($numero_visual); ?>" placeholder="Ex: 123">
                    </div>

                    <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" name="bairro" id="bairro" value="<?php echo htmlspecialchars($user['bairro'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($user['cidade'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-save-profile">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
                </button>
            </form>
        </div>

    </main>
</div>

<script>
    // Função para alternar abas
    function showTab(evt, tabId) {
        evt.preventDefault();
        // Esconde todas as abas
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        // Remove active dos links
        document.querySelectorAll('.profile-menu a').forEach(link => link.classList.remove('active'));
        
        // Mostra a aba clicada
        document.getElementById(tabId).style.display = 'block';
        evt.currentTarget.classList.add('active');
    }

    // Busca de CEP
    const cepInput = document.getElementById('cep');
    if(cepInput){
        cepInput.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            e.target.value = val.replace(/^(\d{5})(\d)/, '$1-$2');
            
            if (val.length === 8) {
                fetch(`https://viacep.com.br/ws/${val}/json/`)
                .then(r => r.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('rua').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                    }
                });
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
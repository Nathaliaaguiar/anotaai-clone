<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

$loja_id = $_SESSION['admin_loja_id'];
$mensagem_perfil = '';
$erro_perfil = '';

// --- FUNÇÃO PARA OBTER LATITUDE E LONGITUDE (GEOCODING) ---
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

// --- LÓGICA PARA ATUALIZAR O PERFIL DA LOJA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    
    // ---- CORREÇÃO APLICADA AQUI ----
    // Usamos o operador '??' para evitar o "Warning" se o campo não for enviado.
    $nome_loja = trim($_POST['nome_loja'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    // ---- FIM DA CORREÇÃO ----

    $stmt_senha = $pdo->prepare("SELECT senha FROM lojas WHERE id = ?");
    $stmt_senha->execute([$loja_id]);
    $loja_atual = $stmt_senha->fetch();

    if ($loja_atual && password_verify($senha_atual, $loja_atual['senha'])) {
        $updates = []; $params = [];
        $fields = ['nome' => $nome_loja, 'email' => $email, 'cep' => $cep, 'endereco' => $endereco, 'numero' => $numero, 'bairro' => $bairro, 'cidade' => $cidade];

        foreach ($fields as $key => $value) {
            if (!empty($value)) { $updates[] = "$key = ?"; $params[] = $value; }
        }

        $endereco_completo = "$endereco, $numero, $bairro, $cidade";
        $coords = pegarLatLng($endereco_completo);
        if ($coords) {
            $updates[] = "lat = ?"; $params[] = $coords['lat'];
            $updates[] = "lng = ?"; $params[] = $coords['lng'];
        }

        if (!empty($_POST['nova_senha'])) {
            if (strlen($_POST['nova_senha']) < 6) { $erro_perfil = "A nova senha deve ter no mínimo 6 caracteres."; }
            elseif ($_POST['nova_senha'] !== $_POST['confirmar_senha']) { $erro_perfil = "A nova senha e a confirmação não correspondem."; }
            else { $updates[] = "senha = ?"; $params[] = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT); }
        }

        if (empty($erro_perfil) && !empty($updates)) {
            $sql = "UPDATE lojas SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $loja_id;
            $stmt_update = $pdo->prepare($sql);
            if ($stmt_update->execute($params)) { $mensagem_perfil = "Perfil atualizado com sucesso!"; }
            else { $erro_perfil = "Ocorreu um erro ao atualizar o perfil."; }
        }
    } else {
        $erro_perfil = "A 'Senha Atual' está incorreta.";
    }
}

// --- BUSCA DADOS ATUAIS DA LOJA (INCLUINDO ENDEREÇO) ---
$stmt_loja = $pdo->prepare("SELECT nome, email, cep, endereco, numero, bairro, cidade FROM lojas WHERE id = ?");
$stmt_loja->execute([$loja_id]);
$loja_data = $stmt_loja->fetch(PDO::FETCH_ASSOC);

$loja = [
    'nome' => $loja_data['nome'] ?? '', 'email' => $loja_data['email'] ?? '',
    'cep' => $loja_data['cep'] ?? '', 'endereco' => $loja_data['endereco'] ?? '',
    'numero' => $loja_data['numero'] ?? '', 'bairro' => $loja_data['bairro'] ?? '',
    'cidade' => $loja_data['cidade'] ?? ''
];

// --- BUSCA CLIENTES (Favoritos e com Pedidos) ---
$stmt_favoritos = $pdo->prepare("SELECT u.nome, u.email, u.telefone FROM usuarios u JOIN lojas_favoritas lf ON u.id = lf.usuario_id WHERE lf.loja_id = ? ORDER BY u.nome ASC");
$stmt_favoritos->execute([$loja_id]);
$clientes_favoritos = $stmt_favoritos->fetchAll(PDO::FETCH_ASSOC);

$stmt_pedidos = $pdo->prepare("SELECT DISTINCT u.id, u.nome, u.email, u.telefone, u.endereco, u.bairro FROM usuarios u JOIN pedidos p ON u.id = p.usuario_id WHERE p.loja_id = ? ORDER BY u.nome ASC");
$stmt_pedidos->execute([$loja_id]);
$clientes_pedidos = $stmt_pedidos->fetchAll();
?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-store"></i> Perfil & Clientes</h1>
    </div>

    <?php if ($mensagem_perfil): ?><p class="success-message"><?php echo $mensagem_perfil; ?></p><?php endif; ?>
    <?php if ($erro_perfil): ?><p class="error-message"><?php echo $erro_perfil; ?></p><?php endif; ?>

    <div class="card card-perfil">
        <div class="card-header">
            <h2><i class="fas fa-edit"></i> Editar Perfil da Loja</h2>
        </div>
        <div class="card-body">
            <form action="clientes.php" method="POST" id="perfilLojaForm">
                <input type="hidden" name="atualizar_perfil" value="1">
                
                <h4><i class="fas fa-info-circle"></i> Informações Básicas</h4>
                <div class="form-grid-perfil">
                    <div class="form-group">
                        <label for="nome_loja">Nome da Loja</label>
                        <input type="text" id="nome_loja" name="nome_loja" value="<?php echo htmlspecialchars($loja['nome']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email de Login</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($loja['email']); ?>" required>
                    </div>
                </div>

                <h4><i class="fas fa-map-marker-alt"></i> Endereço</h4>
                <div class="form-grid-endereco">
                    <div class="form-group cep-group">
                        <label for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" placeholder="Apenas números" value="<?php echo htmlspecialchars($loja['cep']); ?>" required>
                        <small id="cep-status"></small>
                    </div>
                    <div class="form-group">
                        <label for="endereco">Endereço (Rua)</label>
                        <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($loja['endereco']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="numero">Número</label>
                        <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($loja['numero']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bairro">Bairro</label>
                        <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($loja['bairro']); ?>" required>
                    </div>
                     <div class="form-group">
                        <label for="cidade">Cidade</label>
                        <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($loja['cidade']); ?>" required>
                    </div>
                </div>

                <h4><i class="fas fa-key"></i> Segurança</h4>
                 <div class="form-grid-senha">
                     <div class="form-group">
                        <label for="nova_senha">Nova Senha <small>(deixe em branco para não alterar)</small></label>
                        <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha">
                    </div>
                 </div>
                <div class="form-group form-group-full">
                    <label for="senha_atual">Senha Atual <small>(obrigatória para salvar)</small></label>
                    <input type="password" id="senha_atual" name="senha_atual" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="btn-salvar"><i class="fas fa-save"></i> Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <div class="clientes-grid">
         <div class="card card-lista">
            <div class="card-header">
                <h2><i class="fas fa-heart"></i> Clientes que Favoritaram</h2>
                <span class="count-badge"><?php echo count($clientes_favoritos); ?></span>
            </div>
            <div class="card-body table-wrapper">
                <table class="tabela-moderna">
                    <thead><tr><th>Nome</th><th>Contato</th></tr></thead>
                    <tbody>
                        <?php if (empty($clientes_favoritos)): ?>
                            <tr><td colspan="2" class="empty-state">Nenhum cliente favoritou sua loja ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clientes_favoritos as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefone'] ?: $cliente['email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-lista">
            <div class="card-header">
                <h2><i class="fas fa-receipt"></i> Clientes com Pedidos</h2>
                <span class="count-badge"><?php echo count($clientes_pedidos); ?></span>
            </div>
            <div class="card-body table-wrapper">
                <table class="tabela-moderna">
                    <thead><tr><th>Nome</th><th>Endereço</th></tr></thead>
                    <tbody>
                        <?php if (empty($clientes_pedidos)): ?>
                            <tr><td colspan="2" class="empty-state">Nenhum cliente fez um pedido ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clientes_pedidos as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['endereco'] . ', ' . $cliente['bairro']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cepInput = document.getElementById('cep');
    const enderecoInput = document.getElementById('endereco');
    const bairroInput = document.getElementById('bairro');
    const cidadeInput = document.getElementById('cidade');
    const numeroInput = document.getElementById('numero');
    const cepStatus = document.getElementById('cep-status');

    function mostrarStatusCep(mensagem, classe) {
        cepStatus.textContent = mensagem;
        cepStatus.className = classe;
    }

    async function consultarCep(cep) {
        mostrarStatusCep('Buscando...', 'cep-buscando');
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await response.json();
            
            if (!data.erro) {
                enderecoInput.value = data.logradouro || '';
                bairroInput.value = data.bairro || '';
                cidadeInput.value = data.localidade || '';
                mostrarStatusCep('CEP encontrado!', 'cep-valid');
                numeroInput.focus();
            } else {
                mostrarStatusCep('CEP não encontrado.', 'cep-invalid');
            }
        } catch (error) {
            mostrarStatusCep('Erro na consulta.', 'cep-invalid');
        }
    }

    cepInput.addEventListener('input', () => {
        const cep = cepInput.value.replace(/\D/g, '');
        if (cep.length === 8) {
            consultarCep(cep);
        } else {
            mostrarStatusCep('', '');
        }
    });
});
</script>

<style>
/* Estilos gerais da página */
.page-content { padding: 1.5rem; max-width: 1200px; margin: auto; }
.page-header { border-bottom: 2px solid #f0f0f0; margin-bottom: 2rem; padding-bottom: 1rem; }
.page-header h1 { font-size: 1.8rem; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
.page-header h1 i { color: var(--primary-color); }
/* Mensagens de feedback */
.success-message { background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border-left: 5px solid #28a745; margin-bottom: 1.5rem; }
.error-message { background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; border-left: 5px solid #dc3545; margin-bottom: 1.5rem; }
/* Estrutura dos cards */
.card { background-color: var(--bg-light-gray); border: 1px solid #e9ecef; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 2rem; overflow: hidden; }
.card-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; }
.card-header h2 { margin: 0; font-size: 1.2rem; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
.card-body { padding: 1.5rem; }
/* Formulário de perfil */
.card-perfil h4 { margin-top: 2rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e9ecef; color: var(--primary-color); font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; }
.card-perfil h4:first-of-type { margin-top: 0; }
.form-grid-perfil, .form-grid-senha { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.form-grid-endereco { display: grid; grid-template-columns: 1fr 2fr 1fr; grid-template-areas: "cep endereco numero" "bairro bairro cidade"; gap: 1.5rem; }
.form-group.cep-group { grid-area: cep; } #endereco { grid-area: endereco; } #numero { grid-area: numero; } #bairro { grid-area: bairro; } #cidade { grid-area: cidade; }
.form-group-full { grid-column: 1 / -1; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; }
.form-group label small { font-weight: 400; color: #6c757d; }
.form-group input { width: 100%; }
.form-actions { margin-top: 1.5rem; text-align: right; }
.btn-primary { background-color: #007bff; color: white; } .btn-primary:hover { background-color: #0056b3; }
#cep-status { font-size: 0.8rem; margin-top: 5px; display: block; }
.cep-valid { color: #28a745; } .cep-invalid { color: #dc3545; } .cep-buscando { color: #007bff; }
/* Listas de clientes */
.clientes-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
.card-lista h2 i.fa-heart { color: #e74c3c; } .card-lista h2 i.fa-receipt { color: #28a745; }
.count-badge { background-color: var(--primary-color); color: white; font-weight: 600; font-size: 0.9rem; padding: 0.2rem 0.6rem; border-radius: 20px; }
.table-wrapper { max-height: 450px; overflow-y: auto; }
/* Tabela moderna */
.tabela-moderna { width: 100%; border-collapse: collapse; }
.tabela-moderna th, .tabela-moderna td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #dee2e6; }
.tabela-moderna thead th { background-color: #f8f9fa; font-size: 0.9rem; text-transform: uppercase; color: #495057; }
.tabela-moderna tbody tr:nth-of-type(even) { background-color: #f8f9fa; }
.tabela-moderna tbody tr:hover { background-color: #e9ecef; }
.tabela-moderna .empty-state { text-align: center; color: #6c757d; padding: 2rem; }
/* Responsividade */
@media (max-width: 992px) { .clientes-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .form-grid-perfil, .form-grid-senha, .form-grid-endereco { grid-template-columns: 1fr; grid-template-areas: "cep" "endereco" "numero" "bairro" "cidade"; } }
</style>
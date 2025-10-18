<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

// Função para validar CEP via API
function validarCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    
    if (strlen($cep) !== 8) {
        return false;
    }
    
    try {
        $url = "https://viacep.com.br/ws/{$cep}/json/";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        return !isset($data['erro']) && !empty($data['cep']);
    } catch (Exception $e) {
        error_log("Erro ao validar CEP: " . $e->getMessage());
        return false;
    }
}

// Atualizar perfil (com CEP, número, cidade, bairro e endereço)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $cep = $_POST['cep'];
    $endereco = $_POST['endereco'];
    $numero = $_POST['numero'];
    $bairro = trim($_POST['bairro']);
    $cidade = $_POST['cidade'];

    // Validar CEP antes de atualizar
    $cep_valido = validarCEP($cep);
    if (!$cep_valido) {
        $erro_update = "CEP inválido ou inexistente. Por favor, verifique o CEP informado.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios 
            SET nome = ?, email = ?, telefone = ?, cep = ?, endereco = ?, numero = ?, bairro = ?, cidade = ? 
            WHERE id = ?");
        if ($stmt->execute([$nome, $email, $telefone, $cep, $endereco, $numero, $bairro, $cidade, $usuario_id])) {
            $sucesso_update = "Perfil atualizado com sucesso!";
        } else {
            $erro_update = "Erro ao atualizar perfil. Tente novamente.";
        }
    }
}

// Buscar dados do usuário
$stmt_usuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch();

// Buscar pedidos do usuário
$stmt_pedidos = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY data DESC");
$stmt_pedidos->execute([$usuario_id]);
$pedidos = $stmt_pedidos->fetchAll();

// Itens dos pedidos
$itens_por_pedido = [];
if ($pedidos) {
    $pedido_ids = array_column($pedidos, 'id');
    if (!empty($pedido_ids)) {
        $ids_string = implode(',', $pedido_ids);
        $stmt_itens = $pdo->query("
            SELECT pi.*, p.nome as produto_nome 
            FROM pedido_itens pi 
            JOIN produtos p ON pi.produto_id = p.id 
            WHERE pi.pedido_id IN ($ids_string)
        ");
        $todos_itens = $stmt_itens->fetchAll();
        foreach ($todos_itens as $item) {
            $itens_por_pedido[$item['pedido_id']][] = $item;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body>
    <section class="perfil">
        <h1>Meu Perfil</h1>

        <?php if(isset($_GET['pedido_sucesso'])): ?>
            <p class="success">Seu pedido foi realizado com sucesso!</p>
        <?php endif; ?>

        <div class="perfil-container">
            <div class="perfil-form form-wrapper">
                <h2>Meus Dados</h2>
                <?php if (isset($sucesso_update)): ?>
                    <p class="success"><?php echo $sucesso_update; ?></p>
                <?php endif; ?>
                <?php if (isset($erro_update)): ?>
                    <p class="error"><?php echo $erro_update; ?></p>
                <?php endif; ?>
                
                <form action="perfil.php" method="POST" id="perfilForm">
                    <div class="form-group">
                        <label class="required-field">Nome:</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Email:</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Telefone:</label>
                        <input type="text" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" required>
                    </div>

                    <h3>Endereço</h3>
                    <div class="form-group">
                        <label class="required-field">CEP:</label>
                        <input type="text" name="cep" id="cep" maxlength="9" 
                               value="<?php echo htmlspecialchars($usuario['cep']); ?>" required>
                        <div id="cep-status"></div>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Endereço (Rua):</label>
                        <input type="text" name="endereco" id="endereco" readonly
                               value="<?php echo htmlspecialchars($usuario['endereco']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Número:</label>
                        <input type="text" name="numero" value="<?php echo htmlspecialchars($usuario['numero']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Bairro:</label>
                        <input type="text" name="bairro" id="bairro" readonly
                               value="<?php echo htmlspecialchars($usuario['bairro']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Cidade:</label>
                        <input type="text" name="cidade" id="cidade" readonly
                               value="<?php echo htmlspecialchars($usuario['cidade']); ?>" required>
                    </div>

                    <button type="submit" class="btn" id="btn-atualizar">Atualizar Dados</button>
                </form>
            </div>

            <div class="meus-pedidos">
                <h2>Meus Pedidos</h2>
                <?php if(empty($pedidos)): ?>
                    <p>Você ainda não fez nenhum pedido.</p>
                <?php else: ?>
                    <div class="lista-pedidos">
                        <?php foreach ($pedidos as $pedido): ?>
                            <div class="pedido-card">
                                <div class="pedido-header">
                                    <div>
                                        <strong>Pedido #<?php echo $pedido['id']; ?></strong>
                                        <br>
                                        <small><?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></small>
                                    </div>
                                    <div>
                                        <span class="status-<?php echo str_replace(' ', '_', $pedido['status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $pedido['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="pedido-body">
                                    <ul>
                                        <?php if (isset($itens_por_pedido[$pedido['id']])): ?>
                                            <?php foreach ($itens_por_pedido[$pedido['id']] as $item): ?>
                                                <li>
                                                    <span><?php echo $item['quantidade']; ?>x <?php echo htmlspecialchars($item['produto_nome']); ?></span>
                                                    <span>R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></span>
                                                    <?php if(!empty($item['observacao'])): ?>
                                                        <small class="observacao-item">
                                                            <em><?php echo htmlspecialchars($item['observacao']); ?></em>
                                                        </small>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="pedido-footer">
                                    <div>
                                        <small>Taxa de Entrega: R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></small>
                                    </div>
                                    <strong>Total: R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
    // === Elementos do DOM ===
    const cepInput = document.getElementById('cep');
    const enderecoInput = document.getElementById('endereco');
    const bairroInput = document.getElementById('bairro');
    const cidadeInput = document.getElementById('cidade');
    const cepStatus = document.getElementById('cep-status');
    const btnAtualizar = document.getElementById('btn-atualizar');
    const perfilForm = document.getElementById('perfilForm');

    let cepValido = false;

    function mostrarStatusCep(mensagem, tipo) {
        cepStatus.innerHTML = mensagem;
        cepStatus.className = 'cep-status ' + tipo;
    }

    function limparEndereco() {
        enderecoInput.value = '';
        bairroInput.value = '';
        cidadeInput.value = '';
        cepValido = false;
        mostrarStatusCep('', '');
        atualizarBotao();
    }

    function atualizarBotao() {
        // Habilita o botão apenas se o CEP for válido
        btnAtualizar.disabled = !cepValido;
    }

    async function consultarCep(cep) {
        if (cep.length !== 8) {
            limparEndereco();
            return;
        }

        mostrarStatusCep('Consultando CEP...', 'cep-loading');
        cepValido = false;
        atualizarBotao();

        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await response.json();
            
            if (!data.erro && data.cep) {
                enderecoInput.value = data.logradouro || '';
                bairroInput.value = data.bairro || '';
                cidadeInput.value = data.localidade || '';
                cepValido = true;
                mostrarStatusCep('✅ CEP válido', 'cep-valid');
            } else {
                limparEndereco();
                mostrarStatusCep('❌ CEP não encontrado', 'cep-invalid');
            }
        } catch (error) {
            limparEndereco();
            mostrarStatusCep('❌ Erro ao consultar CEP', 'cep-invalid');
            console.error('Erro ao consultar CEP:', error);
        } finally {
            atualizarBotao();
        }
    }

    // Event listener para CEP
    cepInput.addEventListener('input', () => {
        const cep = cepInput.value.replace(/\D/g, '');
        cepInput.value = cep.replace(/(\d{5})(\d{3})/, '$1-$2');
        
        if (cep.length === 8) {
            consultarCep(cep);
        } else {
            limparEndereco();
        }
    });

    // Validação do formulário de perfil
    perfilForm.addEventListener('submit', (e) => {
        // Verificar se CEP é válido
        if (!cepValido) {
            e.preventDefault();
            alert('Por favor, informe um CEP válido antes de atualizar.');
            return;
        }

        // Verificar se os campos de endereço foram preenchidos
        if (!enderecoInput.value || !bairroInput.value || !cidadeInput.value) {
            e.preventDefault();
            alert('Por favor, aguarde o CEP ser consultado ou informe um CEP válido.');
            return;
        }
    });

    // Consultar CEP automaticamente se já houver valor no carregamento
    document.addEventListener('DOMContentLoaded', function() {
        const cepInicial = cepInput.value.replace(/\D/g, '');
        if (cepInicial.length === 8) {
            consultarCep(cepInicial);
        } else {
            atualizarBotao(); // Garante que o botão está no estado correto
        }
    });
    </script>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
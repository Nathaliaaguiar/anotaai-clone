<?php
require_once __DIR__ . '/../includes/header.php';

// Lógica de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
    $nome_loja = trim($_POST['nome_loja']);
    $email_admin = trim($_POST['email_admin']);
    $senha = $_POST['senha_cadastro'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep_loja']);
    $logradouro = trim($_POST['endereco_loja']);
    $numero = trim($_POST['numero']);
    $bairro = trim($_POST['bairro_loja']);
    $cidade = trim($_POST['cidade_loja']);

    // Monta o endereço completo
    $endereco_completo = "$logradouro, Nº $numero - $bairro, $cidade - CEP: $cep";

    if ($senha !== $confirmar_senha) {
        $erro_cadastro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro_cadastro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        try {
            $pdo->beginTransaction();

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserção na tabela de lojas
            $stmt_loja = $pdo->prepare("
                INSERT INTO lojas (nome, telefone, endereco, bairro, email, senha, aprovado, ativa, data_criacao) 
                VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())
            ");
            $stmt_loja->execute([$nome_loja, $telefone, $endereco_completo, $bairro, $email_admin, $senha_hash]);
            $loja_id = $pdo->lastInsertId();

            // Cria também o admin vinculado
            $stmt_admin = $pdo->prepare("INSERT INTO admins (loja_id, email, senha) VALUES (?, ?, ?)");
            $stmt_admin->execute([$loja_id, $email_admin, $senha_hash]);

            // Configurações iniciais
            $stmt_config = $pdo->prepare("INSERT INTO configuracoes (loja_id, chave, valor) VALUES (?, ?, ?)");
            $configs = [
                ['nome_loja', $nome_loja],
                ['telefone', $telefone],
                ['endereco', $endereco_completo],
                ['bairro', $bairro],
                ['email_contato', $email_admin]
            ];
            foreach ($configs as $config) {
                $stmt_config->execute([$loja_id, $config[0], $config[1]]);
            }

            // Área de entrega inicial
            $stmt_area = $pdo->prepare("INSERT INTO areas_entrega (loja_id, bairro, taxa_entrega) VALUES (?, ?, 0.00)");
            $stmt_area->execute([$loja_id, $bairro]);

            $pdo->commit();
            $sucesso_cadastro = "✅ Cadastro realizado com sucesso! A sua loja está em análise.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $erro_cadastro = "Este e-mail já está cadastrado.";
            } else {
                $erro_cadastro = "Ocorreu um erro inesperado.";
                error_log("Erro no cadastro da loja: " . $e->getMessage());
            }
        }
    }
}

?>

<div class="auth-container">
    <div class="form-wrapper">
        <h2>Entrar</h2>
        <?php if (isset($erro_login)): ?><p class="error"><?php echo $erro_login; ?></p><?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Senha</label><input type="password" name="senha" required></div>
            <button type="submit" name="login" class="btn">Entrar</button>
        </form>
    </div>

    <div class="form-wrapper">
        <h2>Criar Conta</h2>
        <?php if (isset($erro_cadastro)): ?><p class="error"><?php echo $erro_cadastro; ?></p><?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group"><label>Nome Completo</label><input type="text" name="nome" required></div>
            <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Senha</label><input type="password" name="senha" required></div>
            <div class="form-group"><label>Telefone</label><input type="tel" name="telefone" required></div>

            <div class="form-group"><label>CEP</label><input type="text" id="cep" name="cep" maxlength="9" required></div>
            <div class="form-group"><label>Rua</label><input type="text" id="rua" name="rua" readonly required></div>
            <div class="form-group"><label>Bairro</label><input type="text" id="bairro" name="bairro" readonly required></div>
            <div class="form-group"><label>Cidade</label><input type="text" id="cidade" name="cidade" readonly required></div>
            <div class="form-group"><label>Número</label><input type="text" id="numero" name="numero" required></div>

            <button type="submit" name="cadastro" class="btn">Cadastrar</button>
        </form>
    </div>
</div>

<script>
// Seleciona os campos
const cepInput = document.getElementById('cep');
const ruaInput = document.getElementById('rua');
const bairroInput = document.getElementById('bairro');
const cidadeInput = document.getElementById('cidade');

// Função para limpar endereço
function limparEndereco() {
    ruaInput.value = '';
    bairroInput.value = '';
    cidadeInput.value = '';
}

// Função para consultar CEP
async function consultarCep(cep) {
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        if (!data.erro) {
            ruaInput.value = data.logradouro;
            bairroInput.value = data.bairro;
            cidadeInput.value = data.localidade;
        } else {
            limparEndereco();
        }
    } catch (error) {
        limparEndereco();
        console.error('Erro ao consultar CEP:', error);
    }
}

// Evento de digitação no CEP
cepInput.addEventListener('input', () => {
    const cep = cepInput.value.replace(/\D/g, '');

    // Limpa endereço se CEP estiver incompleto
    if (cep.length !== 8) {
        limparEndereco();
        return;
    }

    // Consulta API quando o CEP estiver completo
    consultarCep(cep);
});
</script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>

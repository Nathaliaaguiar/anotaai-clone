<?php
session_start();
ob_start();

// Caminho correto para o banco
require_once __DIR__ . '/../config/db.php';

$erro_login = '';
$sucesso_cadastro = '';
$erro_cadastro = '';

/* ========================
   LOGIN DA LOJA
======================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM lojas WHERE email = ?");
    $stmt->execute([$email]);
    $loja = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($loja) {
        if (password_verify($senha, $loja['senha'])) {
            if ($loja['aprovado'] == 1) {
                $_SESSION['admin_loja_id'] = $loja['id'];
                $_SESSION['loja_nome'] = $loja['nome'];

                header("Location: dashboard.php");
                exit();
            } else {
                $erro_login = "A sua loja ainda está em análise.";
            }
        } else {
            $erro_login = "E-mail ou senha incorreta.";
        }
    } else {
        $erro_login = "E-mail ou senha incorreta.";
    }
}

/* ========================
   CADASTRO DE NOVA LOJA
======================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
    $nome_loja = trim($_POST['nome_loja']);
    $email_admin = trim($_POST['email_admin']);
    $senha = $_POST['senha_cadastro'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep_loja']);
    $endereco = trim($_POST['endereco_loja']); // Rua
    $numero = trim($_POST['numero']);
    $bairro = trim($_POST['bairro_loja']);
    $cidade = trim($_POST['cidade_loja']);

    if ($senha !== $confirmar_senha) {
        $erro_cadastro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro_cadastro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        try {
            $pdo->beginTransaction();

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere na tabela lojas com os campos reais do banco
            $stmt_loja = $pdo->prepare("INSERT INTO lojas 
                (nome, email, senha, telefone, cep, endereco, numero, bairro, cidade, aprovado, ativa, data_criacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())");

            $stmt_loja->execute([
                $nome_loja, $email_admin, $senha_hash, $telefone, $cep, $endereco, $numero, $bairro, $cidade
            ]);

            $loja_id = $pdo->lastInsertId();

            // Insere o admin da loja
            $stmt_admin = $pdo->prepare("INSERT INTO admins (loja_id, email, senha) VALUES (?, ?, ?)");
            $stmt_admin->execute([$loja_id, $email_admin, $senha_hash]);

            // Configurações iniciais
            $stmt_config = $pdo->prepare("INSERT INTO configuracoes (loja_id, chave, valor) VALUES (?, ?, ?)");
            $configs = [
                ['nome_loja', $nome_loja],
                ['telefone', $telefone],
                ['endereco', $endereco],
                ['bairro', $bairro],
                ['cidade', $cidade],
                ['cep', $cep],
                ['email_contato', $email_admin]
            ];
            foreach ($configs as $config) {
                $stmt_config->execute([$loja_id, $config[0], $config[1]]);
            }

            // Cria área de entrega padrão
            $stmt_area = $pdo->prepare("INSERT INTO areas_entrega (loja_id, bairro, taxa_entrega) VALUES (?, ?, 0.00)");
            $stmt_area->execute([$loja_id, $bairro]);

            $pdo->commit();
            $sucesso_cadastro = "✅ Cadastro realizado com sucesso! A sua loja está em análise.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $erro_cadastro = "Este e-mail já está cadastrado.";
            } else {
                $erro_cadastro = "Erro ao cadastrar loja: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Acesso da Loja</title>
    <link rel="stylesheet" href="./css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">

<header class="page-header">
   <h1>Bem-vindo ao Plata<span>food</span></h1>
   <p>A plataforma completa para gerenciar sua loja e seus pedidos online.</p>
</header>

<div class="auth-container">
    <div class="form-wrapper">
        <h2>Login da Loja</h2>
        <?php if(!empty($erro_login)): ?>
            <p class="error"><?php echo htmlspecialchars($erro_login); ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <div class="form-group"><label for="email">E-mail</label><input type="email" id="email" name="email" required></div>
            <div class="form-group"><label for="senha">Senha</label><input type="password" id="senha" name="senha" required></div>
            <button type="submit" name="login" class="btn">Entrar</button>
        </form>
    </div>

    <div class="form-wrapper">
        <h2>Cadastrar Nova Loja</h2>
        <?php if(!empty($sucesso_cadastro)): ?><p class="success"><?php echo htmlspecialchars($sucesso_cadastro); ?></p><?php endif; ?>
        <?php if(!empty($erro_cadastro)): ?><p class="error"><?php echo htmlspecialchars($erro_cadastro); ?></p><?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group"><label for="nome_loja">Nome da Loja *</label><input type="text" id="nome_loja" name="nome_loja" required></div>
            <div class="form-group"><label for="email_admin">E-mail de Acesso *</label><input type="email" id="email_admin" name="email_admin" required></div>
            <div class="form-group"><label for="senha_cadastro">Senha *</label><input type="password" id="senha_cadastro" name="senha_cadastro" required minlength="6"></div>
            <div class="form-group"><label for="confirmar_senha">Confirmar Senha *</label><input type="password" id="confirmar_senha" name="confirmar_senha" required></div>
            <div class="form-group"><label for="telefone">Telefone / WhatsApp *</label><input type="tel" id="telefone" name="telefone" required></div>

            <div class="form-group"><label for="cep_loja">CEP *</label><input type="text" id="cep_loja" name="cep_loja" required></div>
            <div class="form-group"><label for="endereco_loja">Endereço (Rua) *</label><input type="text" id="endereco_loja" name="endereco_loja" required></div>
            <div class="form-group"><label for="numero">Número *</label><input type="text" id="numero" name="numero" required></div>
            <div class="form-group"><label for="bairro_loja">Bairro *</label><input type="text" id="bairro_loja" name="bairro_loja" required></div>
            <div class="form-group"><label for="cidade_loja">Cidade *</label><input type="text" id="cidade_loja" name="cidade_loja" required></div>

            <button type="submit" name="cadastro" class="btn">Cadastrar Loja</button>
        </form>
    </div>
</div>

<footer class="page-footer">
    <div class="footer-content">
        <h3>Uma plataforma, todas as soluções</h3>
        <p>Com o Platafood, você tem controle total sobre sua operação de delivery:</p>
        <ul>
            <li>✔ Gestão de Cardápio Digital Interativo</li>
            <li>✔ Recebimento e Gerenciamento de Pedidos em Tempo Real</li>
            <li>✔ Configuração de Áreas e Taxas de Entrega Flexíveis</li>
            <li>✔ Relatórios de Vendas para Análise de Desempenho</li>
        </ul>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Platafood. Todos os direitos reservados.</p>
    </div>
</footer>

<script>
// Atualiza endereço automaticamente conforme o CEP digitado
document.getElementById('cep_loja').addEventListener('input', function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(res => res.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('endereco_loja').value = data.logradouro || '';
                    document.getElementById('bairro_loja').value = data.bairro || '';
                    document.getElementById('cidade_loja').value = data.localidade || '';
                }
            })
            .catch(() => alert('Erro ao buscar CEP.'));
    } else if (cep.length < 8) {
        document.getElementById('endereco_loja').value = '';
        document.getElementById('bairro_loja').value = '';
        document.getElementById('cidade_loja').value = '';
    }
});
</script>

<?php ob_end_flush(); ?>

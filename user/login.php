<?php
// login.php (Consumidor)
session_start();

// CORREÇÃO: Adicionado /../ para voltar uma pasta e achar o config
require_once __DIR__ . '/../config/db.php';

$erro = '';
$sucesso = '';

// --- LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        header("Location: index.php");
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}

// --- CADASTRO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
    // Captura dados
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
    $endereco = trim($_POST['endereco']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $telefone = trim($_POST['telefone']);

    // Validações básicas
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    
    if($check->rowCount() > 0) {
        $erro = "E-mail já cadastrado.";
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, endereco, bairro, cidade, cep) VALUES (?,?,?,?,?,?,?,?)");
        if($stmt->execute([$nome, $email, $hash, $telefone, $endereco, $bairro, $cidade, $cep])) {
            $sucesso = "Conta criada! Faça login.";
        } else {
            $erro = "Erro ao criar conta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acesso Usuário - PlataFood</title>
    <link rel="stylesheet" href="index.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            
            <div class="auth-header">
                <div class="app-logo">Plata<span>Food</span></div>
                <p>Delivery rápido e fácil</p>
            </div>

            <?php if($erro): ?><div style="color:red; text-align:center; margin-bottom:15px; background: #ffe6e6; padding: 10px; border-radius: 5px;"><?php echo $erro; ?></div><?php endif; ?>
            <?php if($sucesso): ?><div style="color:green; text-align:center; margin-bottom:15px; background: #e6ffe6; padding: 10px; border-radius: 5px;"><?php echo $sucesso; ?></div><?php endif; ?>

            <div id="login-box" class="auth-section active">
                <form method="POST">
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="senha" required>
                    </div>
                    <button type="submit" name="login" class="btn-primary">Entrar</button>
                </form>
                <div class="toggle-auth">
                    Não tem conta? <a onclick="showCadastro()">Cadastre-se</a>
                </div>
            </div>

            <div id="cadastro-box" class="auth-section">
                <form method="POST">
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>E-mail</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="senha" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>CEP</label>
                            <input type="text" name="cep" id="cep" maxlength="9" required>
                            <span id="cep-msg" class="cep-status"></span>
                        </div>
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" name="telefone" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Endereço (Rua, Número)</label>
                        <input type="text" name="endereco" id="endereco" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Bairro</label>
                            <input type="text" name="bairro" id="bairro" readonly>
                        </div>
                        <div class="form-group">
                            <label>Cidade</label>
                            <input type="text" name="cidade" id="cidade" readonly>
                        </div>
                    </div>

                    <button type="submit" name="cadastro" class="btn-primary">Criar Conta</button>
                </form>
                <div class="toggle-auth">
                    Já tem conta? <a onclick="showLogin()">Fazer Login</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        function showCadastro() {
            document.getElementById('login-box').classList.remove('active');
            document.getElementById('cadastro-box').classList.add('active');
        }
        function showLogin() {
            document.getElementById('cadastro-box').classList.remove('active');
            document.getElementById('login-box').classList.add('active');
        }

        // Lógica de CEP
        const cepInput = document.getElementById('cep');
        const msgCep = document.getElementById('cep-msg');

        cepInput.addEventListener('input', function(e) {
            let val = e.target.value.replace(/\D/g, '');
            e.target.value = val.replace(/^(\d{5})(\d)/, '$1-$2');
            
            if(val.length === 8) {
                msgCep.innerText = 'Buscando...';
                fetch(`https://viacep.com.br/ws/${val}/json/`)
                .then(r => r.json())
                .then(data => {
                    if(!data.erro) {
                        document.getElementById('endereco').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        msgCep.innerText = 'CEP Válido';
                        msgCep.className = 'cep-status success';
                    } else {
                        msgCep.innerText = 'CEP não encontrado';
                        msgCep.className = 'cep-status error';
                    }
                });
            }
        });
    </script>
</body>
</html>
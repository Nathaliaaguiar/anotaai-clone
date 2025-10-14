<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$erro_login = '';
$sucesso_cadastro = '';
$erro_cadastro = '';

// Lógica de Login (agora usando a tabela lojas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email']; 
    $senha = $_POST['senha'];

    // Busca na tabela lojas (onde estão email e senha)
    $stmt = $pdo->prepare("SELECT * FROM lojas WHERE email = ?");
    $stmt->execute([$email]);
    $loja = $stmt->fetch();

    if ($loja) {
        // Verificar se a loja está aprovada
        if (!$loja['aprovado'] || !$loja['ativa']) {
            $erro_login = "Sua loja está em análise. Aguarde a aprovação.";
        } elseif (password_verify($senha, $loja['senha'])) {
            $_SESSION['admin_loja_id'] = $loja['id'];
            $_SESSION['loja_nome'] = $loja['nome'];
            header("Location: dashboard.php");
            exit();
        } else {
            $erro_login = "Email ou senha inválidos.";
        }
    } else {
        $erro_login = "Email ou senha inválidos.";
    }
}

// Lógica de Cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
    // Dados da loja
    $nome_loja = trim($_POST['nome_loja']);
    $email_admin = trim($_POST['email_admin']);
    $senha = $_POST['senha_cadastro'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $telefone = trim($_POST['telefone']);
    $endereco_loja = trim($_POST['endereco_loja']);
    $bairro_loja = trim($_POST['bairro_loja']);
    
    // Validações
    if ($senha !== $confirmar_senha) {
        $erro_cadastro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro_cadastro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Criar a loja com email e senha (aprovado = 0 inicialmente)
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt_loja = $pdo->prepare("INSERT INTO lojas (nome, telefone, endereco, bairro, email, senha, aprovado, ativa, data_analise) VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())");
            $stmt_loja->execute([$nome_loja, $telefone, $endereco_loja, $bairro_loja, $email_admin, $senha_hash]);
            $loja_id = $pdo->lastInsertId();
            
            // 2. Criar também na tabela admins (para manter compatibilidade)
            $stmt_admin = $pdo->prepare("INSERT INTO admins (loja_id, email, senha) VALUES (?, ?, ?)");
            $stmt_admin->execute([$loja_id, $email_admin, $senha_hash]);
            
            // 3. Adicionar configurações básicas da loja
            $stmt_config = $pdo->prepare("INSERT INTO configuracoes (loja_id, chave, valor) VALUES (?, ?, ?)");
            $configs = [
                ['nome_loja', $nome_loja],
                ['telefone', $telefone],
                ['endereco', $endereco_loja],
                ['bairro', $bairro_loja],
                ['email_contato', $email_admin]
            ];
            
            foreach ($configs as $config) {
                $stmt_config->execute([$loja_id, $config[0], $config[1]]);
            }
            
            // 4. Adicionar área de entrega para o bairro da loja
            $stmt_area = $pdo->prepare("INSERT INTO areas_entrega (loja_id, bairro, taxa_entrega) VALUES (?, ?, 0.00)");
            $stmt_area->execute([$loja_id, $bairro_loja]);
            
            $pdo->commit();
            
            $sucesso_cadastro = "✅ Cadastro realizado com sucesso! Sua loja está em análise e em breve estará ativa no sistema. Entraremos em contato pelo e-mail informado.";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $erro_cadastro = "Este e-mail já está cadastrado.";
            } else {
                $erro_cadastro = "Erro ao cadastrar: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Login e Cadastro</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .auth-container {
            display: flex;
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        
        .form-wrapper {
            flex: 1;
            padding: 30px;
        }
        
        .form-wrapper:first-child {
            border-right: 1px solid #eee;
        }
        
        .form-wrapper h2 {
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.5em;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
            font-size: 0.9em;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            font-size: 0.9em;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                margin: 10px;
            }
            
            .form-wrapper:first-child {
                border-right: none;
                border-bottom: 1px solid #eee;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="auth-container">
        <!-- Login -->
        <div class="form-wrapper">
            <h2>Login da Loja</h2>
            <?php if(isset($erro_login) && !empty($erro_login)): ?>
                <p class="error"><?php echo $erro_login; ?></p>
            <?php endif; ?>
            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <button type="submit" name="login" class="btn">Entrar</button>
            </form>
        </div>

        <!-- Cadastro -->
        <div class="form-wrapper">
            <h2>Cadastrar Loja</h2>
            <?php if(isset($sucesso_cadastro) && !empty($sucesso_cadastro)): ?>
                <p class="success"><?php echo $sucesso_cadastro; ?></p>
            <?php endif; ?>
            <?php if(isset($erro_cadastro) && !empty($erro_cadastro)): ?>
                <p class="error"><?php echo $erro_cadastro; ?></p>
            <?php endif; ?>
            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="nome_loja">Nome da Loja *</label>
                    <input type="text" id="nome_loja" name="nome_loja" required>
                </div>
                <div class="form-group">
                    <label for="email_admin">E-mail da Loja *</label>
                    <input type="email" id="email_admin" name="email_admin" required>
                </div>
                <div class="form-group">
                    <label for="senha_cadastro">Senha *</label>
                    <input type="password" id="senha_cadastro" name="senha_cadastro" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha *</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone *</label>
                    <input type="tel" id="telefone" name="telefone" required>
                </div>
                <div class="form-group">
                    <label for="endereco_loja">Endereço da Loja *</label>
                    <input type="text" id="endereco_loja" name="endereco_loja" required>
                </div>
                <div class="form-group">
                    <label for="bairro_loja">Bairro da Loja *</label>
                    <input type="text" id="bairro_loja" name="bairro_loja" required>
                </div>
                <button type="submit" name="cadastro" class="btn">Cadastrar Loja</button>
            </form>
        </div>
    </div>
</body>
</html>
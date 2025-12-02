<?php
session_start();
ob_start();

// 1. CORREÇÃO DE CAMINHO DO BANCO
// Como este arquivo está em 'admin/', precisamos voltar um nível (../)
require_once __DIR__ . '/../config/db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$erro_login = '';
$erro_cadastro = '';
$sucesso_cadastro = '';

/* ========================
   FUNÇÕES AUXILIARES
======================== */
function validarCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    if (strlen($cep) !== 8) return false;
    try {
        $ctx = stream_context_create(['http'=> ['timeout' => 3]]);
        $response = @file_get_contents("https://viacep.com.br/ws/{$cep}/json/", false, $ctx);
        if(!$response) return false;
        $data = json_decode($response, true);
        return !isset($data['erro']) && !empty($data['cep']);
    } catch (Exception $e) { return false; }
}

function pegarLatLng($endereco) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $endereco, 'format' => 'json', 'limit' => 1
    ]);
    $opts = ["http" => ["header" => "User-Agent: Platafood/1.0\r\n"]];
    $context = stream_context_create($opts);
    try {
        $response = @file_get_contents($url, false, $context);
        if(!$response) return null;
        $data = json_decode($response, true);
        if (!empty($data)) return ['lat' => $data[0]['lat'], 'lng' => $data[0]['lon']];
    } catch (Exception $e) { return null; }
    return null;
}

// --- PROCESSAR CADASTRO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_loja'])) {
    $nome = trim($_POST['nome_loja']);
    $email = trim($_POST['email_cadastro']);
    $senha = $_POST['senha_cadastro'];
    $confirmar = $_POST['confirmar_senha'];
    $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
    $rua = trim($_POST['rua']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $estado = trim($_POST['estado']);

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro_cadastro = "Preencha os campos obrigatórios.";
    } elseif ($senha !== $confirmar) {
        $erro_cadastro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro_cadastro = "Senha muito curta (mínimo 6).";
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM lojas WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->rowCount() > 0) {
            $erro_cadastro = "E-mail já cadastrado.";
        } else {
            $endereco_completo = "$rua, $bairro, $cidade - $estado, $cep";
            $coords = pegarLatLng($endereco_completo);
            $lat = $coords ? $coords['lat'] : null;
            $lng = $coords ? $coords['lng'] : null;

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            try {
                // ATENÇÃO: A loja é criada com aprovado=0. 
                // Você precisa alterar para 1 no banco de dados para conseguir logar!
                $stmt = $pdo->prepare("INSERT INTO lojas (nome, email, senha, cep, endereco, bairro, cidade, estado, latitude, longitude, aprovado, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");
                $stmt->execute([$nome, $email, $hash, $cep, $rua, $bairro, $cidade, $estado, $lat, $lng]);
                $sucesso_cadastro = "Cadastro realizado! Aguarde a aprovação do administrador.";
            } catch (PDOException $e) {
                $erro_cadastro = "Erro ao cadastrar: " . $e->getMessage();
            }
        }
    }
}

// --- PROCESSAR LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logar_loja'])) {
    $email = trim($_POST['email_login']);
    $senha = $_POST['senha_login'];

    $stmt = $pdo->prepare("SELECT * FROM lojas WHERE email = ?");
    $stmt->execute([$email]);
    $loja = $stmt->fetch();

    if ($loja && password_verify($senha, $loja['senha'])) {
        // Verifica se a loja foi aprovada no banco de dados
        if ($loja['aprovado'] == 1) {
            
            // Login com sucesso
            $_SESSION['admin_loja_id'] = $loja['id'];
            $_SESSION['admin_loja_nome'] = $loja['nome'];
            
            // 2. CORREÇÃO DE REDIRECIONAMENTO
            // Vai para dashboard.php (que está na mesma pasta 'admin')
            header('Location: dashboard.php'); 
            exit;

        } else {
            $erro_login = "Sua loja aguarda aprovação. Mude 'aprovado' para 1 no banco de dados.";
        }
    } else {
        $erro_login = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Parceiro - Platafood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="../css/index.css?v=3">
</head>
<body>

    <div class="auth-container">
        
        <div class="auth-header">
            <div class="logo">
                <i class="fa-solid fa-utensils" style="color: #ff6f00; margin-right:5px;"></i>
                Plata<span>Food</span>
            </div>
            <p class="auth-subtitle">Portal do Parceiro</p>
        </div>

        <?php if ($sucesso_cadastro): ?>
            <div class="alert alert-success"><?php echo $sucesso_cadastro; ?></div>
        <?php endif; ?>

        <div id="loginSection" class="form-section active">
            <?php if ($erro_login): ?>
                <div class="alert alert-error"><?php echo $erro_login; ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email_login" required placeholder="seu@email.com">
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha_login" required placeholder="••••••••">
                </div>
                <button type="submit" name="logar_loja" class="btn-primary">Entrar</button>
            </form>

            <div class="toggle-link">
                Ainda não tem uma conta? <a onclick="mostrarCadastro()">Cadastre sua loja</a>
            </div>
        </div>

        <div id="cadastroSection" class="form-section">
            <?php if ($erro_cadastro): ?>
                <div class="alert alert-error"><?php echo $erro_cadastro; ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST" id="cadastroLojaForm">
                <div class="form-group">
                    <label>Nome da Loja</label>
                    <input type="text" name="nome_loja" required placeholder="Ex: Hamburgueria do João">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email_cadastro" required>
                    </div>
                    <div class="form-group">
                        <label>CEP <span id="cep-feedback"></span></label>
                        <input type="text" name="cep" id="cep" maxlength="9" required placeholder="00000-000">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Rua</label><input type="text" name="rua" id="rua" readonly style="background:#eee;"></div>
                    <div class="form-group"><label>Bairro</label><input type="text" name="bairro" id="bairro" readonly style="background:#eee;"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Cidade</label><input type="text" name="cidade" id="cidade" readonly style="background:#eee;"></div>
                    <div class="form-group"><label>UF</label><input type="text" name="estado" id="estado" readonly style="background:#eee;"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="senha_cadastro" id="senha_cadastro" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirmar</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" required>
                    </div>
                </div>

                <button type="submit" name="cadastrar_loja" id="btnCadastrar" class="btn-primary" disabled title="Preencha o CEP e as senhas">Cadastrar Loja</button>
            </form>

            <div class="toggle-link">
                Já tem cadastro? <a onclick="mostrarLogin()">Faça login</a>
            </div>
        </div>

    </div>

    <script>
        function mostrarCadastro() {
            document.getElementById('loginSection').classList.remove('active');
            document.getElementById('cadastroSection').classList.add('active');
        }
        function mostrarLogin() {
            document.getElementById('cadastroSection').classList.remove('active');
            document.getElementById('loginSection').classList.add('active');
        }

        <?php if ($erro_cadastro): ?> mostrarCadastro(); <?php endif; ?>

        // Lógica de CEP
        const cepInput = document.getElementById('cep');
        const btnCadastrar = document.getElementById('btnCadastrar');
        const cepFeedback = document.getElementById('cep-feedback');
        let cepValido = false;

        async function buscarCep() {
            let cep = cepInput.value.replace(/\D/g, '');
            if (cep.length !== 8) { cepValido = false; verificarCampos(); return; }
            cepFeedback.innerText = '...';
            try {
                const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await res.json();
                if (!data.erro) {
                    document.getElementById('rua').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;
                    cepFeedback.innerText = '✓'; cepFeedback.className = 'valid';
                    cepValido = true;
                } else {
                    cepFeedback.innerText = 'X'; cepFeedback.className = 'invalid';
                    cepValido = false;
                }
            } catch { cepValido = false; }
            verificarCampos();
        }

        function verificarCampos() {
            const s1 = document.getElementById('senha_cadastro').value;
            const s2 = document.getElementById('confirmar_senha').value;
            if(cepValido && s1.length >= 6 && s1 === s2) {
                btnCadastrar.disabled = false;
            } else {
                btnCadastrar.disabled = true;
            }
        }

        cepInput.addEventListener('blur', buscarCep);
        cepInput.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g,"");
            if (v.length > 5) v = v.replace(/^(\d{5})(\d)/, "$1-$2");
            e.target.value = v;
        });
        document.getElementById('senha_cadastro').addEventListener('input', verificarCampos);
        document.getElementById('confirmar_senha').addEventListener('input', verificarCampos);
    </script>
</body>
</html>
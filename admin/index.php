<?php
session_start();
ob_start();
require_once __DIR__ . '/../config/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$erro_login = '';
$erro_cadastro = '';
$sucesso_cadastro = '';

/* ========================
   FUNÇÃO DE VALIDAÇÃO DE CEP
======================== */
function validarCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    if (strlen($cep) !== 8) return false;

    try {
        $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");
        $data = json_decode($response, true);
        return !isset($data['erro']) && !empty($data['cep']);
    } catch (Exception $e) {
        return false;
    }
}
function pegarLatLng($endereco) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $endereco,
        'format' => 'json',
        'limit' => 1
    ]);

    $opts = [
        "http" => [
            "header" => "User-Agent: Platafood/1.0\r\n"
        ]
    ];

    $context = stream_context_create($opts);
    $resultado = file_get_contents($url, false, $context);
    $data = json_decode($resultado, true);

    if (!empty($data)) {
        return ['lat' => $data[0]['lat'], 'lng' => $data[0]['lon']];
    }

    return ['lat' => null, 'lng' => null];
}

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
            if ($loja['ativa'] == 0 && $loja['aprovado'] == 1) {
                $erro_login = "❌ Sua conta está desativada por descumprimento de regras. Ela voltará em breve. Caso você não siga as regras, sua conta poderá ser banida.";
            } elseif ($loja['aprovado'] == 0) {
                $erro_login = "A sua loja ainda está em análise pelo administrador.";
            } else {
                $_SESSION['admin_loja_id'] = $loja['id'];
                $_SESSION['loja_nome'] = $loja['nome'];
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $erro_login = "E-mail ou senha incorretos.";
        }
    } else {
        // Verifica se a loja foi excluída
        $stmtExcluida = $pdo->prepare("SELECT * FROM lojas_excluidas WHERE email = ?");
        $stmtExcluida->execute([$email]);
        $excluida = $stmtExcluida->fetch(PDO::FETCH_ASSOC);

        if ($excluida) {
            $erro_login = "🚫 Sua conta foi excluída por descumprimento das diretrizes.";
        } else {
            $erro_login = "E-mail ou senha incorretos.";
        }
    }
}

/* ========================
   CADASTRO DE NOVA LOJA
======================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
    $nome = trim($_POST['nome_loja']);
    $email = trim($_POST['email_admin']);
    $senha = $_POST['senha_cadastro'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep_loja']);
    $rua = trim($_POST['rua_loja']);
    $bairro = trim($_POST['bairro_loja']);
    $cidade = trim($_POST['cidade_loja']);
    $numero = trim($_POST['numero']);

    // Validação do CEP
    if (!validarCEP($cep)) {
        $erro_cadastro = "CEP inválido ou inexistente. Verifique e tente novamente.";
    }
    $endereco_completo = "$rua, $numero - $bairro, $cidade, $cep";
$coordenadas = pegarLatLng($endereco_completo);
$lat = $coordenadas['lat'];
$lng = $coordenadas['lng'];

if (!$lat || !$lng) {
    $erro_cadastro = "Não foi possível localizar a localização da loja. Verifique os dados do endereço e CEP.";
}


    // Validação de senha
    if ($senha !== $confirmar_senha) {
        $erro_cadastro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro_cadastro = "A senha deve ter pelo menos 6 caracteres.";
    }

    // Só continua se não tiver erro
    if (empty($erro_cadastro)) {
        try {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserção na tabela lojas
           $stmt = $pdo->prepare("INSERT INTO lojas 
    (nome, email, senha, telefone, cep, endereco, numero, bairro, cidade, lat, lng, aprovado, ativa, data_criacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())");
$stmt->execute([
    $nome, $email, $senha_hash, $telefone, $cep, $rua, $numero, $bairro, $cidade, $lat, $lng
]);


            $loja_id = $pdo->lastInsertId();

            // Inserir registro na tabela admins
            $stmt_admin = $pdo->prepare("INSERT INTO admins (loja_id, email, senha) VALUES (?, ?, ?)");
            $stmt_admin->execute([$loja_id, $email, $senha_hash]);

            $sucesso_cadastro = "✅ Loja cadastrada com sucesso! Aguarde a aprovação do administrador.";
        } catch (PDOException $e) {
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
<title>Acesso da Loja - Platafood</title>
<link rel="stylesheet" href="./css/index.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
button[disabled],
button:disabled { background-color: #ccc !important; color: #666 !important; cursor: not-allowed !important; opacity: 0.8; }
button.btn:not(:disabled) { color: white; cursor: pointer; transition: background 0.3s ease; }
</style>
</head>
<body class="login-page">

<header class="page-header">
   <h1>Bem-vindo ao Plata<span>food</span></h1>
   <p>Gerencie sua loja e pedidos de forma prática e segura.</p>
</header>

<div class="auth-container">
    <!-- LOGIN -->
    <div class="form-wrapper">
        <h2>Login da Loja</h2>
        <?php if ($erro_login): ?><p class="error"><?= htmlspecialchars($erro_login) ?></p><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Senha</label><input type="password" name="senha" required></div>
            <button type="submit" name="login" class="btn">Entrar</button>
        </form>
    </div>

    <!-- CADASTRO -->
    <div class="form-wrapper">
        <h2>Cadastrar Nova Loja</h2>
        <?php if ($erro_cadastro): ?><p class="error"><?= htmlspecialchars($erro_cadastro) ?></p><?php endif; ?>
        <?php if ($sucesso_cadastro): ?><p class="success"><?= htmlspecialchars($sucesso_cadastro) ?></p><?php endif; ?>

        <form method="POST" id="cadastroLojaForm">
            <div class="form-group"><label>Nome da Loja *</label><input type="text" name="nome_loja" required></div>
            <div class="form-group"><label>E-mail de Acesso *</label><input type="email" name="email_admin" required></div>
            <div class="form-group"><label>Senha *</label><input type="password" id="senha_cadastro" name="senha_cadastro" required minlength="6"></div>
            <div class="form-group"><label>Confirmar Senha *</label><input type="password" id="confirmar_senha" name="confirmar_senha" required></div>
            <div class="form-group"><label>Telefone / WhatsApp *</label><input type="tel" name="telefone" required></div>
            <div class="form-group"><label>CEP *</label><input type="text" id="cep_loja" name="cep_loja" maxlength="9" required></div>
            <div class="form-group"><label>Rua *</label><input type="text" id="rua_loja" name="rua_loja" readonly required></div>
            <div class="form-group"><label>Bairro *</label><input type="text" id="bairro_loja" name="bairro_loja" readonly required></div>
            <div class="form-group"><label>Cidade *</label><input type="text" id="cidade_loja" name="cidade_loja" readonly required></div>
            <div class="form-group"><label>Nº da casa *</label><input type="text" id="numero" name="numero" required></div>
            <button type="submit" name="cadastro" class="btn" id="btn-cadastrar" disabled>Cadastrar Loja</button>
        </form>
    </div>
</div>

<footer class="page-footer">
    <p>&copy; <?= date('Y'); ?> Platafood. Todos os direitos reservados.</p>
</footer>

<script>
const cepInput = document.getElementById('cep_loja');
const ruaInput = document.getElementById('rua_loja');
const bairroInput = document.getElementById('bairro_loja');
const cidadeInput = document.getElementById('cidade_loja');
const btnCadastrar = document.getElementById('btn-cadastrar');

let cepValido = false;

async function consultarCep(cep) {
    cep = cep.replace(/\D/g, '');
    if (cep.length !== 8) { ruaInput.value=''; bairroInput.value=''; cidadeInput.value=''; cepValido=false; verificarCampos(); return; }
    try {
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if(!data.erro){ ruaInput.value=data.logradouro||''; bairroInput.value=data.bairro||''; cidadeInput.value=data.localidade||''; cepValido=true; }
        else{cepValido=false;}
    } catch { cepValido=false; }
    verificarCampos();
}

function verificarCampos() {
    const campos = document.querySelectorAll('#cadastroLojaForm input[required]');
    const todosPreenchidos = Array.from(campos).every(campo=>campo.value.trim()!=='');
    const senha = document.getElementById('senha_cadastro').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    const senhasOK = senha.length>=6 && senha===confirmar;
    btnCadastrar.disabled = !(todosPreenchidos && cepValido && senhasOK);
}

btnCadastrar.addEventListener('click', e => {
    if(btnCadastrar.disabled){ e.preventDefault(); alert("⚠️ Preencha todos os campos corretamente antes de cadastrar."); }
});

cepInput.addEventListener('input', e=>{
    const cep = e.target.value.replace(/\D/g,'');
    e.target.value = cep.replace(/(\d{5})(\d{3})/,'$1-$2');
    if(cep.length===8) consultarCep(cep);
    else verificarCampos();
});

document.querySelectorAll('#cadastroLojaForm input').forEach(input=>{
    input.addEventListener('input',verificarCampos);
});
</script>

<?php ob_end_flush(); ?>

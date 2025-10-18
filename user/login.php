<?php
// Ativar display de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/header.php';

// Debug: Verificar se sessão está iniciada
error_log("=== INICIANDO LOGIN/CADASTRO ===");
error_log("Session ID: " . (session_id() ? session_id() : 'Nenhuma sessão'));

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
// Função para obter latitude e longitude a partir do endereço completo
function pegarLatLng($endereco) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $endereco,
        'format' => 'json',
        'limit' => 1
    ]);

    $opts = [
        "http" => [
            "header" => "User-Agent: MeuProjeto/1.0\r\n"
        ]
    ];

    $context = stream_context_create($opts);
    $resultado = @file_get_contents($url, false, $context);
    if (!$resultado) return ['lat' => null, 'lng' => null];

    $data = json_decode($resultado, true);
    if (!empty($data)) {
        return [
            'lat' => $data[0]['lat'],
            'lng' => $data[0]['lon']
        ];
    }

    return ['lat' => null, 'lng' => null];
}



// Lógica de Cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
    error_log("Tentativa de CADASTRO detectada");
    
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep']);
    $logradouro = trim($_POST['rua']);
    $numero = trim($_POST['numero']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);

    // Validar CEP
    $cep_valido = validarCEP($cep);
    if (!$cep_valido) {
        $erro_cadastro = "CEP inválido ou inexistente. Por favor, verifique o CEP informado.";
        error_log("Erro cadastro: CEP inválido - " . $cep);
    }
    // Monta o endereço completo
 // Monta o endereço completo
$endereco_completo_para_geocoding = "$logradouro, $bairro, $cidade";
$coordenadas = pegarLatLng($endereco_completo_para_geocoding);
$lat = $coordenadas['lat'];
$lng = $coordenadas['lng'];

// Mantém o endereço completo no banco
$endereco_completo = "$logradouro, Nº $numero - $bairro, $cidade - CEP: $cep";


if (!$lat || !$lng) {
    $erro_cadastro = "Não foi possível localizar o endereço. Verifique os dados do CEP e endereço.";
    error_log("Erro cadastro: lat/lng não encontrados para o endereço: $endereco_completo");
}


    if ($senha !== $confirmar_senha) {
        $erro_cadastro = "As senhas não coincidem.";
        error_log("Erro cadastro: Senhas não coincidem");
    } elseif (strlen($senha) < 6) {
        $erro_cadastro = "A senha deve ter pelo menos 6 caracteres.";
        error_log("Erro cadastro: Senha muito curta");
    } else {
        // Só prossegue com o cadastro se não houver erro no CEP
        if (!isset($erro_cadastro)) {
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                error_log("Hash da senha gerado: " . $senha_hash);

                // Inserção direta na tabela de usuarios - sem aprovação necessária
                $stmt_usuario = $pdo->prepare("
                   INSERT INTO usuarios (nome, email, senha, endereco, bairro, telefone, cep, cidade, numero, lat, lng, criado_em) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())

                ");
                $resultado = $stmt_usuario->execute([
    $nome, 
    $email, 
    $senha_hash, 
    $endereco_completo, 
    $bairro, 
    $telefone, 
    $cep, 
    $cidade, 
    $numero,
    $lat,
    $lng
]
);

                if ($resultado) {
                    $sucesso_cadastro = "✅ Cadastro realizado com sucesso! Você já pode fazer login.";
                    error_log("Cadastro BEM-SUCEDIDO para: " . $email);
                } else {
                    throw new Exception("Falha na execução do INSERT");
                }

            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $erro_cadastro = "Este e-mail já está cadastrado.";
                    error_log("Erro cadastro: Email duplicado - " . $email);
                } else {
                    $erro_cadastro = "Ocorreu um erro inesperado.";
                    error_log("Erro no cadastro do usuário: " . $e->getMessage());
                    error_log("Detalhes do erro: " . print_r($e->errorInfo, true));
                }
            }
        }
    }
}

// Lógica de Login (COM DEBUG COMPLETO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    
    error_log("=== TENTATIVA DE LOGIN ===");
    error_log("Email recebido: " . $email);
    error_log("Senha recebida: " . str_repeat('*', strlen($senha)) . " (" . strlen($senha) . " caracteres)");

    try {
        // Verificar se o usuário existe
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            error_log("✅ Usuário ENCONTRADO no banco:");
            error_log("   ID: " . $usuario['id']);
            error_log("   Nome: " . $usuario['nome']);
            error_log("   Email: " . $usuario['email']);
            error_log("   Senha no BD: " . $usuario['senha']);
            error_log("   Hash length: " . strlen($usuario['senha']));
            
            // Verificar a senha
            $senha_verificada = password_verify($senha, $usuario['senha']);
            error_log("   Resultado password_verify: " . ($senha_verificada ? 'VERDADEIRO' : 'FALSO'));
            
            if ($senha_verificada) {
                error_log("✅ SENHA CORRETA - Login autorizado");
                
                // Iniciar sessão se não estiver iniciada
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                    error_log("Sessão iniciada");
                }
                
                // Definir variáveis de sessão
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                
                error_log("✅ Variáveis de sessão definidas:");
                error_log("   usuario_id: " . $_SESSION['usuario_id']);
                error_log("   usuario_nome: " . $_SESSION['usuario_nome']);
                error_log("   usuario_email: " . $_SESSION['usuario_email']);
                
                // Verificar se as variáveis de sessão foram salvas
                if (isset($_SESSION['usuario_id'])) {
                    error_log("✅ Sessão confirmada - Redirecionando para index.php");
                    
                    // Redirecionar para a página principal
                    header('Location: index.php');
                    exit;
                } else {
                    error_log("❌ ERRO: Variáveis de sessão não foram definidas");
                    $erro_login = "Erro ao iniciar sessão. Tente novamente.";
                }
                
            } else {
                error_log("❌ SENHA INCORRETA para: " . $email);
                $erro_login = "E-mail ou senha incorretos.";
            }
        } else {
            error_log("❌ Usuário NÃO encontrado para email: " . $email);
            $erro_login = "E-mail ou senha incorretos.";
        }
    } catch (PDOException $e) {
        error_log("❌ ERRO no banco de dados: " . $e->getMessage());
        error_log("Detalhes: " . print_r($e->errorInfo, true));
        $erro_login = "Erro ao conectar com o banco de dados. Tente novamente.";
    }
    
    error_log("=== FIM TENTATIVA LOGIN ===");
}

// Debug: Verificar se usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    error_log("Usuário já logado: " . $_SESSION['usuario_email']);
} else {
    error_log("Nenhum usuário logado na sessão");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
        /* Estado desabilitado do botão */
button[disabled],
button:disabled {
    background-color: #ccc !important;
    color: #666 !important;
    cursor: not-allowed !important;
    box-shadow: none !important;
    opacity: 0.8;
}

/* Estado habilitado */
button.btn:not(:disabled) {
    
    color: white;
    cursor: pointer;
    transition: background 0.3s ease;
}



    </style>
  
</head>
<body>
    <div class="auth-container">
        <div class="form-wrapper">
            <h2>Entrar</h2>
            
            <!-- Debug Info -->
            <!-- <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Sessão: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Ativa' : 'Inativa'; ?><br>
                Usuário Logado: <?php echo isset($_SESSION['usuario_id']) ? 'Sim (ID: ' . $_SESSION['usuario_id'] . ')' : 'Não'; ?>
            </div> -->
            
            <?php if (isset($erro_login)): ?>
                <div class="error">
                    <strong>Erro:</strong> <?php echo $erro_login; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="required-field">E-mail</label>
                    <input type="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="required-field">Senha</label>
                    <input type="password" name="senha" required>
                </div>
                <button type="submit" name="login" class="btn">Entrar</button>
            </form>
        </div>

        <div class="form-wrapper">
            <h2>Criar Conta</h2>
            
            <?php if (isset($erro_cadastro)): ?>
                <div class="error"><?php echo $erro_cadastro; ?></div>
            <?php endif; ?>
            
            <?php if (isset($sucesso_cadastro)): ?>
                <div class="success"><?php echo $sucesso_cadastro; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="cadastroForm" 
         autocomplete="off" >
            
            
                <div class="form-group">
                    <label class="required-field">Nome Completo</label>
                    <input type="text" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="required-field">E-mail</label>
                    <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="required-field">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <div class="form-group">
                    <label class="required-field">Confirmar Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                <div class="form-group">
                    <label class="required-field">Telefone</label>
                    <input type="tel" name="telefone" required value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="required-field">CEP</label>
                    <input type="text" id="cep" name="cep" maxlength="9" required 
                           value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
                    <div id="cep-status"></div>
                </div>
                <div class="form-group">
                    <label class="required-field">Rua</label>
                    <input type="text" id="rua" name="rua" readonly required value="<?php echo isset($_POST['rua']) ? htmlspecialchars($_POST['rua']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="required-field">Bairro</label>
                    <input type="text" id="bairro" name="bairro" readonly required value="<?php echo isset($_POST['bairro']) ? htmlspecialchars($_POST['bairro']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="required-field">Cidade</label>
                    <input type="text" id="cidade" name="cidade" readonly required value="<?php echo isset($_POST['cidade']) ? htmlspecialchars($_POST['cidade']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="required-field">Nº da casa</label>
                    <input type="text" id="numero" name="numero" required value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : ''; ?>">
                </div>
               

                <button type="submit" name="cadastro" class="btn" id="btn-cadastrar" disabled>Cadastrar</button>
            </form>
        </div>
    </div>

 <script>
// === Elementos do DOM ===
const cepInput = document.getElementById('cep');
const ruaInput = document.getElementById('rua');
const bairroInput = document.getElementById('bairro');
const cidadeInput = document.getElementById('cidade');
const numeroInput = document.getElementById('numero');
const nomeInput = document.querySelector('input[name="nome"]');
const emailInput = document.querySelector('input[name="email"]');
const senhaInput = document.getElementById('senha');
const confirmarSenhaInput = document.getElementById('confirmar_senha');
const telefoneInput = document.querySelector('input[name="telefone"]');
const cepStatus = document.getElementById('cep-status');
const btnCadastrar = document.getElementById('btn-cadastrar');
const cadastroForm = document.getElementById('cadastroForm');

let cepValido = false;
let todosCamposPreenchidos = false;

function mostrarStatusCep(mensagem, tipo) {
    cepStatus.innerHTML = mensagem;
    cepStatus.className = 'cep-status ' + tipo;
}

function limparEndereco() {
    ruaInput.value = '';
    bairroInput.value = '';
    cidadeInput.value = '';
    cepValido = false;
    mostrarStatusCep('', '');
    verificarCampos();
}

function verificarCampos() {
    const camposObrigatorios = [
        nomeInput.value.trim(),
        emailInput.value.trim(),
        senhaInput.value.trim(),
        confirmarSenhaInput.value.trim(),
        telefoneInput.value.trim(),
        cepInput.value.trim(),
        ruaInput.value.trim(),
        bairroInput.value.trim(),
        cidadeInput.value.trim(),
        numeroInput.value.trim()
    ];

    const todosPreenchidos = camposObrigatorios.every(campo => campo !== '');
    const senhasCoincidem = senhaInput.value === confirmarSenhaInput.value;
    const senhasValidas = senhaInput.value.length >= 6 && confirmarSenhaInput.value.length >= 6;

    const habilitar = todosPreenchidos && cepValido && senhasCoincidem && senhasValidas;
    btnCadastrar.disabled = !habilitar;
}

async function consultarCep(cep) {
    if (cep.length !== 8) {
        limparEndereco();
        return;
    }

    mostrarStatusCep('Consultando CEP...', 'cep-loading');
    cepValido = false;
    verificarCampos();

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        
        if (!data.erro && data.cep) {
            ruaInput.value = data.logradouro || '';
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
        verificarCampos();
    }
}

// 🔹 Impede clique se o botão ainda estiver desativado
btnCadastrar.addEventListener('click', (e) => {
    if (btnCadastrar.disabled) {
        e.preventDefault();
        alert("⚠️ Preencha todos os campos corretamente antes de cadastrar.");
    }
});

// Event listeners
cepInput.addEventListener('input', () => {
    const cep = cepInput.value.replace(/\D/g, '');
    cepInput.value = cep.replace(/(\d{5})(\d{3})/, '$1-$2');
    if (cep.length === 8) {
        consultarCep(cep);
    } else {
        limparEndereco();
    }
});

document.querySelectorAll('#cadastroForm input').forEach(input => {
    input.addEventListener('input', verificarCampos);
});

// Inicializar estado do botão
verificarCampos();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
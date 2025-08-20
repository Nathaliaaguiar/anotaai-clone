<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// --- LÓGICA DE UPLOAD E CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $id = $_POST['id'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $imagem_nome = $_POST['imagem_atual'] ?? 'default.jpg'; // Mantém a imagem atual por padrão

    // Verifica se uma nova imagem foi enviada
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../img/';
        $arquivo_tmp = $_FILES['imagem']['tmp_name'];
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        
        // Gera um nome de arquivo único para evitar conflitos
        $novo_nome_arquivo = uniqid() . '.' . $extensao;
        $destino = $upload_dir . $novo_nome_arquivo;

        // Validação do tipo de arquivo (opcional mas recomendado)
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($extensao), $tipos_permitidos)) {
            if (move_uploaded_file($arquivo_tmp, $destino)) {
                // Se a imagem foi movida com sucesso, apaga a antiga (se não for a default)
                if ($id && $imagem_nome != 'default.jpg' && file_exists($upload_dir . $imagem_nome)) {
                    unlink($upload_dir . $imagem_nome);
                }
                $imagem_nome = $novo_nome_arquivo;
            }
        }
    }

    if ($id) { // Edição
        $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, ativo = ?, imagem = ? WHERE id = ?");
        $stmt->execute([$nome, $descricao, $preco, $ativo, $imagem_nome, $id]);
    } else { // Adição
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, ativo, imagem) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $preco, $ativo, $imagem_nome]);
    }
    header('Location: produtos.php');
    exit;
}

// --- LÓGICA DE DELETAR ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Primeiro, busca o nome da imagem para poder deletar o arquivo
    $stmt_img = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
    $stmt_img->execute([$id]);
    $imagem_a_deletar = $stmt_img->fetchColumn();

    // Deleta o produto do banco
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);

    // Se o arquivo de imagem existir e não for o default, apaga ele
    if ($imagem_a_deletar && $imagem_a_deletar != 'default.jpg' && file_exists(__DIR__ . '/../img/' . $imagem_a_deletar)) {
        unlink(__DIR__ . '/../img/' . $imagem_a_deletar);
    }
    
    header('Location: produtos.php');
    exit;
}

// Buscar produto para editar
$produto_edicao = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto_edicao = $stmt->fetch();
}

// Listar todos os produtos
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();
?>

<section class="admin-crud">
    <h1>Gerenciar Produtos</h1>

    <div class="form-wrapper">
        <h2><?php echo $produto_edicao ? 'Editar Produto' : 'Adicionar Novo Produto'; ?></h2>
        <form action="produtos.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $produto_edicao['id'] ?? ''; ?>">
            <input type="hidden" name="imagem_atual" value="<?php echo $produto_edicao['imagem'] ?? ''; ?>">
            
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($produto_edicao['nome'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Descrição:</label>
                <textarea name="descricao" required><?php echo htmlspecialchars($produto_edicao['descricao'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Preço:</label>
                <input type="number" step="0.01" name="preco" value="<?php echo htmlspecialchars($produto_edicao['preco'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Imagem do Produto:</label>
                <?php if (isset($produto_edicao['imagem']) && $produto_edicao['imagem']): ?>
                    <p>Imagem Atual:</p>
                    <img src="../img/<?php echo htmlspecialchars($produto_edicao['imagem']); ?>" alt="Imagem atual" width="100">
                    <p style="margin-top: 10px;">Envie uma nova imagem abaixo para substituir a atual:</p>
                <?php endif; ?>
                <input type="file" name="imagem" accept="image/*">
            </div>

            <div class="form-group-checkbox">
                <label>Ativo:</label>
                <input type="checkbox" name="ativo" <?php echo (isset($produto_edicao['ativo']) && $produto_edicao['ativo'] == 1) || !$produto_edicao ? 'checked' : ''; ?>>
            </div>
            
            <button type="submit" class="btn"><?php echo $produto_edicao ? 'Atualizar' : 'Adicionar'; ?></button>
            <?php if ($produto_edicao): ?>
                <a href="produtos.php" class="btn-cancel">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Lista de Produtos</h2>
    <table class="tabela-admin">
        <thead>
            <tr>
                <th>Imagem</th> <th>ID</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Ativo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><img src="../img/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" width="60"></td>
                    <td><?php echo $produto['id']; ?></td>
                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                    <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                    <td><?php echo $produto['ativo'] ? 'Sim' : 'Não'; ?></td>
                    <td>
                        <a href="produtos.php?edit=<?php echo $produto['id']; ?>" class="btn-edit">Editar</a>
                        <a href="produtos.php?delete=<?php echo $produto['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza? Isso também apagará a imagem do produto.');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
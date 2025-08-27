<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';
// ADICIONADO: A "chave mestra" que identifica a loja do admin logado
$loja_id = $_SESSION['admin_loja_id'];

// MODIFICADO: Busca categorias apenas da loja logada para usar no formulário
$stmt_cat = $pdo->prepare("SELECT * FROM categorias WHERE loja_id = ? ORDER BY nome ASC");
$stmt_cat->execute([$loja_id]);
$categorias = $stmt_cat->fetchAll();

// --- LÓGICA DE UPLOAD E CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $categoria_id = $_POST['categoria_id'];
    $id = $_POST['id'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $imagem_nome = $_POST['imagem_atual'] ?? 'default.jpg';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        // (Sua lógica de upload de imagem continua a mesma)
        $upload_dir = __DIR__ . '/../img/';
        $novo_nome_arquivo = uniqid() . '.' . pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_dir . $novo_nome_arquivo)) {
            if ($id && $imagem_nome != 'default.jpg' && file_exists($upload_dir . $imagem_nome)) {
                unlink($upload_dir . $imagem_nome);
            }
            $imagem_nome = $novo_nome_arquivo;
        }
    }

    if ($id) { // Edição
        // MODIFICADO: Garante que só edite produto da própria loja
        $stmt = $pdo->prepare("UPDATE produtos SET nome=?, descricao=?, preco=?, categoria_id=?, ativo=?, imagem=? WHERE id=? AND loja_id=?");
        $stmt->execute([$nome, $descricao, $preco, $categoria_id, $ativo, $imagem_nome, $id, $loja_id]);
    } else { // Adição
        // MODIFICADO: Adiciona o novo produto na loja certa
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, categoria_id, ativo, imagem, loja_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $preco, $categoria_id, $ativo, $imagem_nome, $loja_id]);
    }
    header('Location: produtos.php');
    exit;
}

// --- LÓGICA DE DELETAR ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // MODIFICADO: Garante que só delete produto da própria loja
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    header('Location: produtos.php');
    exit;
}

// Buscar produto para editar
$produto_edicao = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    // MODIFICADO: Garante que só busque produto da própria loja para editar
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    $produto_edicao = $stmt->fetch();
}

// MODIFICADO: Lista apenas os produtos da loja logada
$stmt = $pdo->prepare("
    SELECT p.*, c.nome as nome_categoria 
    FROM produtos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.loja_id = ? 
    ORDER BY p.nome
");
$stmt->execute([$loja_id]);
$produtos = $stmt->fetchAll();
?>

<section class="admin-crud">
    <h1>Gerenciar Produtos</h1>
    <div class="form-container">
        <form action="produtos.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $produto_edicao['id'] ?? ''; ?>">
            <input type="hidden" name="imagem_atual" value="<?php echo $produto_edicao['imagem'] ?? ''; ?>">
            <div class="form-group">
                <label for="nome">Nome do Produto:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produto_edicao['nome'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($produto_edicao['descricao'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="preco">Preço (R$):</label>
                <input type="number" step="0.01" id="preco" name="preco" value="<?php echo htmlspecialchars($produto_edicao['preco'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="categoria_id">Categoria:</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php if (isset($produto_edicao['categoria_id']) && $produto_edicao['categoria_id'] == $categoria['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="imagem">Imagem:</label>
                <input type="file" id="imagem" name="imagem" accept="image/*">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="ativo" value="1" <?php echo (!isset($produto_edicao) || $produto_edicao['ativo']) ? 'checked' : ''; ?>> Ativo</label>
            </div>
            <button type="submit" class="btn"><?php echo $produto_edicao ? 'Atualizar' : 'Adicionar'; ?></button>
        </form>
    </div>
    <h2>Lista de Produtos</h2>
    <table class="tabela-admin">
        <thead>
            <tr>
                <th>Imagem</th><th>Nome</th><th>Categoria</th> <th>Preço</th>
                <th>Ativo</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><img src="../img/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="" width="60"></td>
                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                    <td><?php echo htmlspecialchars($produto['nome_categoria'] ?? 'Sem categoria'); ?></td>
                    <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                    <td><?php echo $produto['ativo'] ? 'Sim' : 'Não'; ?></td>
                   <td>
                        <a href="produtos.php?edit=<?php echo $produto['id']; ?>" class="btn-edit">Editar</a>
                        <a href="produto_opcoes.php?produto_id=<?php echo $produto['id']; ?>" class="btn-opcoes">Opções</a>
                        <a href="produtos.php?delete=<?php echo $produto['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza?');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
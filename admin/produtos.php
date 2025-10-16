<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

$loja_id = $_SESSION['admin_loja_id'];
$mensagem = '';

// --- LÓGICA DE UPLOAD E CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Pega os dados do formulário
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $categoria_id = $_POST['categoria_id'];
    $id = $_POST['id'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Pega o nome da foto atual se estiver editando
    $foto_nome = $_POST['foto_atual'] ?? null;

    // 2. LÓGICA PARA UPLOAD DA NOVA FOTO
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/produtos/'; // Pasta correta!
        
        // Garante que o diretório exista
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Gera um nome único para o arquivo para evitar substituições
        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome_arquivo = uniqid('prod_') . '.' . $extensao;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $novo_nome_arquivo)) {
            // Se o upload deu certo, o novo nome do arquivo é o que será salvo
            $foto_nome = $novo_nome_arquivo;
        } else {
            $mensagem = "Erro ao mover o arquivo de imagem.";
        }
    }

    // 3. ATUALIZA OU INSERE NO BANCO DE DADOS
    if (empty($mensagem)) { // Continua apenas se não houve erro no upload
        try {
            if ($id) { // UPDATE (Editar produto existente)
                $stmt = $pdo->prepare(
                    "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, categoria_id = ?, ativo = ?, foto = ? WHERE id = ? AND loja_id = ?"
                );
                $stmt->execute([$nome, $descricao, $preco, $categoria_id, $ativo, $foto_nome, $id, $loja_id]);
                $mensagem = "Produto atualizado com sucesso!";
            } else { // INSERT (Adicionar novo produto)
                $stmt = $pdo->prepare(
                    "INSERT INTO produtos (nome, descricao, preco, categoria_id, ativo, foto, loja_id) VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$nome, $descricao, $preco, $categoria_id, $ativo, $foto_nome, $loja_id]);
                $mensagem = "Produto adicionado com sucesso!";
            }
            // Limpa os dados do formulário após o sucesso
            $_POST = [];
            
        } catch (Exception $e) {
            $mensagem = "Erro ao salvar no banco de dados: " . $e->getMessage();
        }
    }
}

// --- LÓGICA PARA EXCLUIR ---
if (isset($_GET['delete'])) {
    $id_para_deletar = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id_para_deletar, $loja_id]);
    header("Location: produtos.php");
    exit;
}

// --- BUSCA DADOS PARA PREENCHER O FORMULÁRIO DE EDIÇÃO ---
$produto_para_editar = null;
if (isset($_GET['edit'])) {
    $id_para_editar = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id_para_editar, $loja_id]);
    $produto_para_editar = $stmt->fetch();
}


// --- BUSCA DADOS PARA A LISTA ---
// MODIFICADO: Busca categorias apenas da loja logada
$stmt_cat = $pdo->prepare("SELECT * FROM categorias WHERE loja_id = ? ORDER BY nome ASC");
$stmt_cat->execute([$loja_id]);
$categorias = $stmt_cat->fetchAll();

// MODIFICADO: Busca produtos apenas da loja logada, e junta com o nome da categoria
$stmt_prod = $pdo->prepare(
    "SELECT p.*, c.nome as nome_categoria 
     FROM produtos p 
     LEFT JOIN categorias c ON p.categoria_id = c.id 
     WHERE p.loja_id = ? ORDER BY p.nome ASC"
);
$stmt_prod->execute([$loja_id]);
$produtos = $stmt_prod->fetchAll();
?>
<section class="container-admin">
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-success"><?= $mensagem ?></div>
    <?php endif; ?>

    <div class="form-admin">
        <h3><?php echo $produto_para_editar ? 'Editar Produto' : 'Adicionar Novo Produto'; ?></h3>
        <form action="produtos.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $produto_para_editar['id'] ?? ''; ?>">
            <input type="hidden" name="foto_atual" value="<?php echo $produto_para_editar['foto'] ?? ''; ?>">

            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($produto_para_editar['nome'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao"><?php echo htmlspecialchars($produto_para_editar['descricao'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Preço</label>
                <input type="number" step="0.01" name="preco" value="<?php echo htmlspecialchars($produto_para_editar['preco'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <select name="categoria_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($produto_para_editar['categoria_id']) && $produto_para_editar['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Foto do Produto</label>
                <input type="file" name="foto" accept="image/png, image/jpeg, image/webp">
                <?php if (!empty($produto_para_editar['foto'])): ?>
                    <p>Foto atual: <img src="../uploads/produtos/<?= htmlspecialchars($produto_para_editar['foto']) ?>" width="50" alt=""></p>
                <?php endif; ?>
            </div>
            
            <div class="form-group-checkbox">
                <input type="checkbox" name="ativo" id="ativo" value="1" <?php echo (isset($produto_para_editar['ativo']) && $produto_para_editar['ativo']) ? 'checked' : ''; ?>>
                <label for="ativo">Produto Ativo?</label>
            </div>

            <button type="submit" class="btn"><?php echo $produto_para_editar ? 'Atualizar' : 'Adicionar'; ?></button>
        </form>
    </div>
    
    <h2>Lista de Produtos</h2>
    <table class="tabela-admin">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Ativo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td>
                        <?php if(!empty($produto['foto'])): ?>
                            <img src="../uploads/produtos/<?php echo htmlspecialchars($produto['foto']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" width="60">
                        <?php else: ?>
                            <img src="https://placehold.co/60x60/f0f0f0/333?text=S/Foto" alt="Sem foto">
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                    <td><?php echo htmlspecialchars($produto['nome_categoria'] ?? 'Sem categoria'); ?></td>
                    <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                    <td><?php echo $produto['ativo'] ? 'Sim' : 'Não'; ?></td>
                   <td>
                        <a href="produtos.php?edit=<?php echo $produto['id']; ?>" class="btn-edit">Editar</a>
                        <a href="produtos.php?delete=<?php echo $produto['id']; ?>" class="btn-remover" onclick="return confirm('Tem certeza?');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
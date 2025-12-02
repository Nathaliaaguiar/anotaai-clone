<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php'; // Já carrega o header.css

$loja_id = $_SESSION['admin_loja_id'];
$mensagem = '';

// --- LÓGICA CRUD (Editar/Salvar/Excluir) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $categoria_id = $_POST['categoria_id'];
    $id = $_POST['id'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Upload de Foto
    $foto_nome = $_POST['foto_atual'] ?? null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/produtos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $novo_nome)) {
            $foto_nome = $novo_nome;
        }
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE produtos SET nome=?, descricao=?, preco=?, categoria_id=?, foto=?, ativo=? WHERE id=? AND loja_id=?");
        $stmt->execute([$nome, $descricao, $preco, $categoria_id, $foto_nome, $ativo, $id, $loja_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO produtos (loja_id, nome, descricao, preco, categoria_id, foto, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$loja_id, $nome, $descricao, $preco, $categoria_id, $foto_nome, $ativo]);
    }
    header('Location: produtos.php');
    exit;
}

// Exclusão
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ? AND loja_id = ?");
    $stmt->execute([$id, $loja_id]);
    header('Location: produtos.php');
    exit;
}

// Busca produto para edição
$produto_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND loja_id = ?");
    $stmt->execute([$_GET['edit'], $loja_id]);
    $produto_edit = $stmt->fetch();
}

// Listas
$categorias = $pdo->prepare("SELECT * FROM categorias WHERE loja_id = ?");
$categorias->execute([$loja_id]);
$lista_cats = $categorias->fetchAll();

$lista_prods = $pdo->prepare("
    SELECT p.*, c.nome as nome_categoria 
    FROM produtos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.loja_id = ? 
    ORDER BY p.id DESC
");
$lista_prods->execute([$loja_id]);
$produtos = $lista_prods->fetchAll();
?>

<link rel="stylesheet" href="../css/produto.css?v=1">

<section class="admin-crud">
    
    <h1>Gerenciar Produtos</h1>

    <div class="form-produto-card">
        <h3 style="margin-top:0; margin-bottom:20px; color:#555;">
            <?php echo $produto_edit ? 'Editar Produto' : 'Novo Produto'; ?>
        </h3>
        
        <form action="produtos.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $produto_edit['id'] ?? ''; ?>">
            <input type="hidden" name="foto_atual" value="<?php echo $produto_edit['foto'] ?? ''; ?>">

            <div class="form-grid">
                <div>
                    <div class="form-group">
                        <label>Nome do Produto</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($produto_edit['nome'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="categoria_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($lista_cats as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php if (($produto_edit['categoria_id'] ?? '') == $c['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($c['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Preço (R$)</label>
                        <input type="number" step="0.01" name="preco" value="<?php echo $produto_edit['preco'] ?? ''; ?>" required>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>Foto do Produto</label>
                        <input type="file" name="foto" accept="image/*">
                        <?php if (!empty($produto_edit['foto'])): ?>
                            <div style="margin-top: 10px;">
                                <img src="../uploads/produtos/<?php echo $produto_edit['foto']; ?>" class="img-thumb" style="width: 80px; height: 80px;">
                                <small style="display:block; color:#999;">Foto atual</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao"><?php echo htmlspecialchars($produto_edit['descricao'] ?? ''); ?></textarea>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="ativo" id="ativo" <?php if (($produto_edit['ativo'] ?? 1) == 1) echo 'checked'; ?>>
                        <label for="ativo">Produto Disponível para Venda</label>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Produto
                </button>
                <?php if ($produto_edit): ?>
                    <a href="produtos.php" class="btn-cancel">Cancelar Edição</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-responsive">
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
                                <img src="../uploads/produtos/<?php echo htmlspecialchars($produto['foto']); ?>" class="img-thumb">
                            <?php else: ?>
                                <img src="https://placehold.co/60x60/f0f0f0/ccc?text=S/Foto" class="img-thumb">
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($produto['nome']); ?></strong>
                            <?php if(!empty($produto['descricao'])): ?>
                                <br><small style="color:#999;"><?php echo substr(htmlspecialchars($produto['descricao']), 0, 30) . '...'; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($produto['nome_categoria'] ?? '---'); ?></td>
                        <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                        <td>
                            <?php if($produto['ativo']): ?>
                                <span style="color: green; font-weight:bold;"><i class="fa-solid fa-check"></i> Sim</span>
                            <?php else: ?>
                                <span style="color: red; font-weight:bold;">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="produto_opcoes.php?produto_id=<?php echo $produto['id']; ?>" class="btn-opcoes" title="Configurar Adicionais">
                                    <i class="fa-solid fa-list-check"></i> Opções
                                </a>
                                <a href="produtos.php?edit=<?php echo $produto['id']; ?>" class="btn-edit" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="produtos.php?delete=<?php echo $produto['id']; ?>" class="btn-remover" onclick="return confirm('Excluir este produto?');" title="Excluir">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
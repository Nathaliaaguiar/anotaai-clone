<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';
// ADICIONADO: A "chave mestra"
$loja_id = $_SESSION['admin_loja_id'];
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        for ($i = 0; $i <= 6; $i++) {
            $ativo = isset($_POST['ativo'][$i]) ? 1 : 0;
            $abertura = $_POST['abertura'][$i];
            $fechamento = $_POST['fechamento'][$i];
            // MODIFICADO: Query robusta que CRIA ou ATUALIZA o horário para a loja específica
            $stmt = $pdo->prepare(
                "INSERT INTO horarios_funcionamento (loja_id, dia_semana, ativo, horario_abertura, horario_fechamento) 
                 VALUES (?, ?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), horario_abertura = VALUES(horario_abertura), horario_fechamento = VALUES(horario_fechamento)"
            );
            $stmt->execute([$loja_id, $i, $ativo, $abertura, $fechamento]);
        }
        $pdo->commit();
        $mensagem = '<p class="success">Horários atualizados com sucesso!</p>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = '<p class="error">Ocorreu um erro: ' . $e->getMessage() . '</p>';
    }
}

// MODIFICADO: Busca apenas os horários da loja logada
$stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE loja_id = ?");
$stmt->execute([$loja_id]);
$horarios_atuais = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

$horarios_db = [];
$dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
for ($i=0; $i<=6; $i++) {
    if (isset($horarios_atuais[$i])) {
        $horarios_db[$i] = $horarios_atuais[$i];
    } else {
        // Se a loja for nova e não tiver horários, preenche com valores padrão
        $horarios_db[$i] = ['ativo' => 0, 'horario_abertura' => '08:00:00', 'horario_fechamento' => '22:00:00'];
    }
}
?>
<section class="admin-crud">
    <h1>Gerenciar Horários de Funcionamento</h1>
    <?php echo $mensagem; ?>
    <div class="form-container">
        <form action="horarios.php" method="POST">
            <table class="tabela-admin">
                <thead><tr><th>Dia da Semana</th><th>Status</th><th>Abertura</th><th>Fechamento</th></tr></thead>
                <tbody>
                    <?php foreach ($dias_semana as $i => $dia): ?>
                        <tr>
                            <td><strong><?php echo $dia; ?></strong></td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="ativo[<?php echo $i; ?>]" <?php if ($horarios_db[$i]['ativo']) echo 'checked'; ?>>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td><input type="time" name="abertura[<?php echo $i; ?>]" value="<?php echo $horarios_db[$i]['horario_abertura']; ?>"></td>
                            <td><input type="time" name="fechamento[<?php echo $i; ?>]" value="<?php echo $horarios_db[$i]['horario_fechamento']; ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <button type="submit" class="btn">Salvar Horários</button>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
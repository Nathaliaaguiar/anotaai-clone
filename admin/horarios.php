<?php
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

$mensagem = '';

// Lógica para ATUALIZAR os horários no banco
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        for ($i = 0; $i <= 6; $i++) {
            $ativo = isset($_POST['ativo'][$i]) ? 1 : 0;
            $abertura = $_POST['abertura'][$i];
            $fechamento = $_POST['fechamento'][$i];

            $stmt = $pdo->prepare(
                "UPDATE horarios_funcionamento 
                 SET ativo = ?, horario_abertura = ?, horario_fechamento = ?
                 WHERE dia_semana = ?"
            );
            $stmt->execute([$ativo, $abertura, $fechamento, $i]);
        }
        
        $pdo->commit();
        $mensagem = '<p class="success">Horários atualizados com sucesso!</p>';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = '<p class="error">Ocorreu um erro: ' . $e->getMessage() . '</p>';
    }
}

// Busca os horários atuais do banco de dados
// CÓDIGO CORRIGIDO
$stmt = $pdo->query("SELECT * FROM horarios_funcionamento ORDER BY dia_semana ASC");
$horarios_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

$horarios_db = [];
foreach ($horarios_lista as $horario) {
    $horarios_db[$horario['dia_semana']] = $horario;
}
$dias_semana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
?>

<section class="admin-crud">
    <h1>Horário de Funcionamento</h1>
    <?php echo $mensagem; ?>

    <div class="form-wrapper">
        <form action="horarios.php" method="POST">
            <table class="tabela-horarios">
                <thead>
                    <tr>
                        <th>Dia da Semana</th>
                        <th>Status</th>
                        <th>Abertura</th>
                        <th>Fechamento</th>
                    </tr>
                </thead>
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
                            <td>
                                <input type="time" name="abertura[<?php echo $i; ?>]" value="<?php echo $horarios_db[$i]['horario_abertura']; ?>">
                            </td>
                            <td>
                                <input type="time" name="fechamento[<?php echo $i; ?>]" value="<?php echo $horarios_db[$i]['horario_fechamento']; ?>">
                            </td>
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
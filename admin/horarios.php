<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php'; // Carrega header.css

$loja_id = $_SESSION['admin_loja_id'];
$mensagem = '';

// --- SALVAR HORÁRIOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        for ($i = 0; $i <= 6; $i++) {
            $ativo = isset($_POST['ativo'][$i]) ? 1 : 0;
            $abertura = $_POST['abertura'][$i];
            $fechamento = $_POST['fechamento'][$i];
            
            // Query segura (Insert ou Update)
            $stmt = $pdo->prepare(
                "INSERT INTO horarios_funcionamento (loja_id, dia_semana, ativo, horario_abertura, horario_fechamento) 
                 VALUES (?, ?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), horario_abertura = VALUES(horario_abertura), horario_fechamento = VALUES(horario_fechamento)"
            );
            $stmt->execute([$loja_id, $i, $ativo, $abertura, $fechamento]);
        }
        $pdo->commit();
        $mensagem = '<div class="success">Horários atualizados com sucesso!</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = '<div class="error">Erro ao salvar horários: ' . $e->getMessage() . '</div>';
    }
}

// --- BUSCAR DADOS ---
$stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE loja_id = ?");
$stmt->execute([$loja_id]);
$horarios_raw = $stmt->fetchAll();

// Organiza array para garantir que todos os dias existam (0 a 6)
$horarios_db = [];
for($i=0; $i<=6; $i++) {
    // Valores padrão caso não exista no banco
    $horarios_db[$i] = ['ativo' => 0, 'horario_abertura' => '09:00', 'horario_fechamento' => '18:00'];
}
foreach ($horarios_raw as $h) {
    $horarios_db[$h['dia_semana']] = $h;
}

$dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
?>

<link rel="stylesheet" href="../css/horario.css?v=1">

<section class="admin-crud">
    
    <h1>Horários de Funcionamento</h1>
    <?php echo $mensagem; ?>

    <div class="horarios-card">
        <form action="horarios.php" method="POST">
            <div class="table-responsive">
                <table class="tabela-horarios">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Dia</th>
                            <th style="width: 20%;">Status</th>
                            <th style="width: 30%;">Abertura</th>
                            <th style="width: 30%;">Fechamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dias_semana as $i => $dia): ?>
                            <?php 
                                // TRUQUE PARA CORRIGIR O HORÁRIO (Remove os segundos :00)
                                $val_abertura = date('H:i', strtotime($horarios_db[$i]['horario_abertura']));
                                $val_fechamento = date('H:i', strtotime($horarios_db[$i]['horario_fechamento']));
                                $is_active = $horarios_db[$i]['ativo'];
                            ?>
                            <tr style="<?php echo $is_active ? '' : 'background-color: #fafafa; opacity: 0.7;'; ?>">
                                <td class="dia-label"><?php echo $dia; ?></td>
                                
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" name="ativo[<?php echo $i; ?>]" <?php if ($is_active) echo 'checked'; ?>>
                                        <span class="slider"></span>
                                        <span class="status-text"><?php echo $is_active ? 'Aberto' : 'Fechado'; ?></span>
                                    </label>
                                </td>
                                
                                <td>
                                    <input type="time" name="abertura[<?php echo $i; ?>]" value="<?php echo $val_abertura; ?>">
                                </td>
                                
                                <td>
                                    <input type="time" name="fechamento[<?php echo $i; ?>]" value="<?php echo $val_fechamento; ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Salvar Horários
            </button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
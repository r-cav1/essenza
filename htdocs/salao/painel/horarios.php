<?php
// Dias, horários de funcionamento e intervalo entre os horários oferecidos.
require_once __DIR__ . '/../includes/config.php';
redirecionarSe(empty($_SESSION['salao_id']), 'login.php');

$pdo     = conectar();
$salaoId = (int)$_SESSION['salao_id'];
$msg     = '';
$msgTipo = '';

// ── Processa o formulário de horários ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf($_POST['_csrf'] ?? null);
    $ab   = limparEntrada($_POST['horario_abertura']   ?? '09:00');
    $fe   = limparEntrada($_POST['horario_fechamento'] ?? '18:00');
    $int  = (int) ($_POST['intervalo_minutos']      ?? 60);
    $diasArray = isset($_POST['dias']) && is_array($_POST['dias'])
        ? array_values(array_unique(array_map('intval', $_POST['dias'])))
        : [];
    sort($diasArray);
    $intervalosPermitidos = [15,30,45,60,90,120];

    if (!horarioValido($ab) || !horarioValido($fe) || strtotime($ab) >= strtotime($fe)) {
        $msg='Informe um horário de abertura anterior ao fechamento.'; $msgTipo='err';
    } elseif (!in_array($int, $intervalosPermitidos, true)) {
        $msg='Intervalo de horários inválido.'; $msgTipo='err';
    } elseif (!$diasArray || array_filter($diasArray, function($d){ return $d < 0 || $d > 6; })) {
        $msg='Selecione pelo menos um dia de funcionamento válido.'; $msgTipo='err';
    } else {
        $dias = implode(',', $diasArray);
        $pdo->prepare(
            "UPDATE saloes SET horario_abertura=?, horario_fechamento=?, intervalo_minutos=?, dias_funcionamento=? WHERE id=?"
        )->execute([$ab, $fe, $int, $dias, $salaoId]);
        $msg='Horários salvos!'; $msgTipo='ok';
    }
}

// ── Carrega tema DEPOIS do POST para refletir valores salvos ──
require_once __DIR__ . '/../includes/tema.php';

// $tema é populado pelo tema.php com SELECT * FROM saloes WHERE id = salaoId
$diasAtivos = explode(',', $tema['dias_funcionamento']);
$diasNomes  = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

painel_head('Horários');
?>
</head><body>
<?php painel_sidebar('horarios'); ?>
<main class="main">
  <h1 class="page-titulo">Horários de funcionamento</h1>
  <?php if ($msg): ?><div class="msg <?= $msgTipo ?>"><?= sanitizar($msg) ?></div><?php endif; ?>

  <form method="POST">
      <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
    <div class="card">
      <div class="card-titulo">Horário de atendimento</div>
      <div class="form-row">
        <div class="form-group">
          <label class="lbl">Abertura</label>
          <input type="time" name="horario_abertura"
                 value="<?= substr($tema['horario_abertura'], 0, 5) ?>" required>
        </div>
        <div class="form-group">
          <label class="lbl">Fechamento</label>
          <input type="time" name="horario_fechamento"
                 value="<?= substr($tema['horario_fechamento'], 0, 5) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="lbl">Intervalo entre slots</label>
        <p style="font-size:12px;color:var(--txt2);margin-bottom:8px">
          Define a frequência dos horários oferecidos. Serviços mais longos que o intervalo
          bloqueiam múltiplos slots consecutivos automaticamente.
        </p>
        <select name="intervalo_minutos">
          <?php foreach ([15, 30, 45, 60, 90, 120] as $m): ?>
          <option value="<?= $m ?>"
            <?= $tema['intervalo_minutos'] == $m ? 'selected' : '' ?>>
            <?= $m ?> minutos
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card">
      <div class="card-titulo">Dias de funcionamento</div>
      <div class="dias-grid">
        <?php for ($d = 0; $d < 7; $d++): ?>
        <label class="dia-label">
          <input type="checkbox" name="dias[]" value="<?= $d ?>"
                 <?= in_array($d, $diasAtivos) ? 'checked' : '' ?>>
          <div class="dia-box"><?= substr($diasNomes[$d], 0, 3) ?></div>
          <span class="dia-nome"><?= $diasNomes[$d] ?></span>
        </label>
        <?php endfor; ?>
      </div>
    </div>

    <button type="submit" class="btn">Salvar horários</button>
  </form>
</main>
</body></html>

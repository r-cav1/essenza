<?php
/**
 * cancelar.php — Área pública de gerenciamento de um agendamento por token.
 * O token é aleatório e funciona como credencial de acesso apenas ao agendamento correspondente.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cores.php';

$token = preg_replace('/[^a-f0-9]/i', '', $_GET['token'] ?? '');
$ag = null;
$erro = '';

if (strlen($token) !== 40) {
    $erro = 'Link de gerenciamento inválido.';
} else {
    $pdo = conectar();
    $stmt = $pdo->prepare(
        "SELECT a.id, a.cliente_nome, a.data_hora, a.status, a.token_cliente,
                s.nome AS servico_nome, sal.nome_salao, sal.slug, sal.cor_primaria, sal.cor_secundaria, sal.cor_texto, sal.fonte_titulo, sal.fonte_corpo, sal.logo_url
         FROM agendamentos a
         JOIN servicos s ON s.id = a.servico_id
         JOIN saloes sal ON sal.id = a.salao_id
         WHERE a.token_cliente = ? LIMIT 1"
    );
    $stmt->execute([$token]);
    $ag = $stmt->fetch();
    if (!$ag) $erro = 'Agendamento não encontrado ou link inválido.';
}

$cor = $ag['cor_primaria'] ?? '#4a8c6e';
$txt = $ag['cor_texto'] ?? '#1a2e25';
$bg = $ag['cor_secundaria'] ?? '#e8f5ee';
$ft = $ag['fonte_titulo'] ?? 'DM Serif Display';
$fb = $ag['fonte_corpo'] ?? 'DM Sans';
$paleta = paletaTema($cor, $bg, $txt);
$surface = $paleta['superficie'];
$surface2 = $paleta['superficie_2'];
$border = $paleta['borda'];
$muted = $paleta['texto_secundario'];
$onPrimary = $paleta['sobre_primaria'];
$podeCancelar = $ag && $ag['status'] === 'confirmado' && strtotime($ag['data_hora']) > time();
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gerenciar agendamento</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=<?= urlencode($ft) ?>:wght@400;600&family=<?= urlencode($fb) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/portal.css">
<style>:root{--p:<?= htmlspecialchars($cor,ENT_QUOTES,'UTF-8') ?>;--bg:<?= htmlspecialchars($bg,ENT_QUOTES,'UTF-8') ?>;--txt:<?= htmlspecialchars($txt,ENT_QUOTES,'UTF-8') ?>;--surface:<?= htmlspecialchars($surface,ENT_QUOTES,'UTF-8') ?>;--surface-2:<?= htmlspecialchars($surface2,ENT_QUOTES,'UTF-8') ?>;--bord:<?= htmlspecialchars($border,ENT_QUOTES,'UTF-8') ?>;--txt2:<?= htmlspecialchars($muted,ENT_QUOTES,'UTF-8') ?>;--on-p:<?= htmlspecialchars($onPrimary,ENT_QUOTES,'UTF-8') ?>;--f-tit:'<?= addslashes($ft) ?>',Georgia,serif;--f-bod:'<?= addslashes($fb) ?>',sans-serif}</style>
</head>
<body class="portal">
<main class="portal-wrap">
<div class="portal-card">
<?php if ($ag): ?>
  <div class="portal-brand">
    <div class="portal-logo">
      <?php if (!empty($ag['logo_url'])): ?><img src="<?= sanitizar($ag['logo_url']) ?>" alt="Logo"><?php else: ?><span><?= sanitizar(mb_strtoupper(mb_substr($ag['nome_salao'],0,1))) ?></span><?php endif; ?>
    </div>
    <div class="portal-brand-name"><?= sanitizar($ag['nome_salao']) ?></div>
  </div>
<?php endif; ?>
<?php if ($erro): ?>
  <h1 class="portal-title">Gerenciar agendamento</h1><p class="portal-message error"><?= sanitizar($erro) ?></p>
<?php else: ?>
  <h1 class="portal-title">Seu agendamento</h1>
  <p class="portal-sub">Gerencie seu agendamento sem precisar criar uma conta.</p>
  <div id="mensagem"></div>
  <div class="portal-data" id="dados">
    <div class="portal-row"><span class="portal-key">Cliente</span><span class="portal-value"><?= sanitizar($ag['cliente_nome']) ?></span></div>
    <div class="portal-row"><span class="portal-key">Serviço</span><span class="portal-value"><?= sanitizar($ag['servico_nome']) ?></span></div>
    <div class="portal-row"><span class="portal-key">Data</span><span class="portal-value"><?= date('d/m/Y', strtotime($ag['data_hora'])) ?></span></div>
    <div class="portal-row"><span class="portal-key">Horário</span><span class="portal-value"><?= date('H:i', strtotime($ag['data_hora'])) ?></span></div>
    <div class="portal-row"><span class="portal-key">Status</span><span class="portal-value"><span class="portal-badge" id="status"><?= sanitizar($ag['status']) ?></span></span></div>
  </div>
  <?php if ($podeCancelar): ?>
    <button class="portal-btn portal-btn-danger" id="btn-cancelar" onclick="cancelarAgendamento()">Cancelar agendamento</button>
  <?php elseif ($ag['status'] === 'cancelado'): ?>
    <div class="portal-message ok">Este agendamento já foi cancelado.</div>
  <?php elseif (strtotime($ag['data_hora']) <= time()): ?>
    <div class="portal-message error">Este agendamento já ocorreu e não pode mais ser cancelado por este link.</div>
  <?php else: ?>
    <div class="portal-message error">Este agendamento não pode ser cancelado no estado atual.</div>
  <?php endif; ?>
<?php endif; ?>
<a class="portal-center-link" href="<?= sanitizar(BASE_URL) ?>/?salao=<?= urlencode($ag['slug'] ?? '') ?>">Voltar ao início</a>
</div>
</main>
<?php if ($podeCancelar): ?>
<script>
async function cancelarAgendamento(){
  if(!confirm('Tem certeza de que deseja cancelar este agendamento?')) return;
  const btn=document.getElementById('btn-cancelar');
  btn.disabled=true; btn.textContent='Cancelando...';
  try{
    const r=await fetch('api/cancelar.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:'<?= htmlspecialchars($token,ENT_QUOTES,'UTF-8') ?>'})});
    const j=await r.json();
    if(!j.sucesso) throw new Error(j.erro||'Não foi possível cancelar.');
    document.getElementById('status').textContent='cancelado';
    document.getElementById('mensagem').innerHTML='<div class="portal-message ok">Agendamento cancelado com sucesso.</div>';
    btn.remove();
  }catch(e){
    document.getElementById('mensagem').innerHTML='<div class="portal-message error">'+String(e.message).replace(/[<>&]/g,'')+'</div>';
    btn.disabled=false; btn.textContent='Cancelar agendamento';
  }
}
</script>
<?php endif; ?>
</body></html>

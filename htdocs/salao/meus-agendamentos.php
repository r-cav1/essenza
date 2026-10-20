<?php
/**
 * meus-agendamentos.php — área simples do cliente, sem criação de conta.
 * O e-mail é confirmado por código e a sessão é limitada ao salão atual.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cores.php';

$slug = isset($_GET['salao']) ? limparEntrada($_GET['salao']) : '';
if ($slug === '') { http_response_code(400); die('Salão não informado.'); }

$pdo = conectar();
$stmt = $pdo->prepare('SELECT * FROM saloes WHERE slug=? LIMIT 1');
$stmt->execute([$slug]);
$salao = $stmt->fetch();
if (!$salao) { http_response_code(404); die('Salão não encontrado.'); }

if (isset($_GET['sair'])) {
    unset($_SESSION['cliente_acesso'], $_SESSION['cliente_acesso_pendente']);
    header('Location: meus-agendamentos.php?salao=' . urlencode($slug));
    exit;
}

$acesso = acessoClienteAtual((int)$salao['id']);
$agendamentos = $acesso ? listarAgendamentosCliente((int)$salao['id'], $acesso['email']) : [];
$paleta = paletaTema($salao['cor_primaria'], $salao['cor_secundaria'], $salao['cor_texto']);
$csrf = csrfToken();

function statusCliente(string $status): string {
    $nomes = ['pendente'=>'Pendente','confirmado'=>'Confirmado','cancelado'=>'Cancelado','concluido'=>'Concluído'];
    return $nomes[$status] ?? ucfirst($status);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Meus agendamentos — <?= sanitizar($salao['nome_salao']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=<?= urlencode($salao['fonte_titulo']) ?>:wght@400;600&family=<?= urlencode($salao['fonte_corpo']) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/portal.css">
<style>:root{--p:<?= sanitizar($salao['cor_primaria']) ?>;--bg:<?= sanitizar($salao['cor_secundaria']) ?>;--txt:<?= sanitizar($salao['cor_texto']) ?>;--surface:<?= sanitizar($paleta['superficie']) ?>;--surface-2:<?= sanitizar($paleta['superficie_2']) ?>;--bord:<?= sanitizar($paleta['borda']) ?>;--txt2:<?= sanitizar($paleta['texto_secundario']) ?>;--on-p:<?= sanitizar($paleta['sobre_primaria']) ?>;--f-tit:'<?= addslashes($salao['fonte_titulo']) ?>',Georgia,serif;--f-bod:'<?= addslashes($salao['fonte_corpo']) ?>',sans-serif}</style>
</head>
<body class="portal"><main class="portal-wrap">
<div class="portal-nav"><a class="portal-link" href="./?salao=<?= urlencode($slug) ?>">Voltar ao início</a><?php if($acesso): ?><a class="portal-link" href="?salao=<?= urlencode($slug) ?>&sair=1">Sair</a><?php endif; ?></div>

<?php if(!$acesso): ?>
<section class="portal-card"><h1 class="portal-title">Meus agendamentos</h1><p class="portal-sub">Use o mesmo e-mail informado no agendamento. Enviaremos um código temporário para confirmar o acesso.</p>
<div id="etapa-email"><div class="portal-form"><input class="portal-input" id="email" type="email" autocomplete="email" placeholder="seu@email.com"><button class="portal-btn" onclick="solicitar()">Enviar código</button></div></div>
<div id="etapa-codigo" hidden><div class="portal-form"><input class="portal-input" id="codigo" inputmode="numeric" maxlength="6" placeholder="Código de 6 dígitos"><button class="portal-btn" onclick="verificar()">Entrar</button></div><p class="portal-sub" style="margin-top:10px">O código expira em 15 minutos.</p></div>
<div id="codigo-dev" class="portal-message portal-code" hidden></div><div id="msg"></div></section>
<script>
const csrf=<?= json_encode($csrf) ?>, salaoId=<?= (int)$salao['id'] ?>;
async function post(url,dados){const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...dados,csrf})});return r.json()}
async function solicitar(){const msg=document.getElementById('msg');msg.className='portal-message';msg.textContent='Enviando...';try{const r=await post('api/cliente-solicitar-acesso.php',{salao_id:salaoId,email:document.getElementById('email').value});if(r.erro){msg.className='portal-message error';msg.textContent=r.erro;return}document.getElementById('etapa-email').hidden=true;document.getElementById('etapa-codigo').hidden=false;msg.className='portal-message ok';msg.textContent=r.mensagem;if(r.codigo_dev){const d=document.getElementById('codigo-dev');d.hidden=false;d.textContent='Modo de teste — código: '+r.codigo_dev}}catch(e){msg.className='portal-message error';msg.textContent='Não foi possível concluir a solicitação.'}}
async function verificar(){const msg=document.getElementById('msg');msg.className='portal-message';msg.textContent='Verificando...';try{const r=await post('api/cliente-verificar-acesso.php',{salao_id:salaoId,codigo:document.getElementById('codigo').value});if(r.erro){msg.className='portal-message error';msg.textContent=r.erro;return}location.reload()}catch(e){msg.className='portal-message error';msg.textContent='Não foi possível verificar o código.'}}
</script>
<?php else: ?>
<section class="portal-card"><h1 class="portal-title">Meus agendamentos</h1><p class="portal-sub">Acesso confirmado para <?= sanitizar($acesso['email']) ?>.</p></section>
<?php if(!$agendamentos): ?><section class="portal-card">Nenhum agendamento encontrado.</section><?php else: ?><div class="portal-list">
<?php foreach($agendamentos as $ag): $momento=strtotime($ag['data_hora']); ?>
<section class="portal-card"><div class="booking-head"><div class="booking-service"><?= sanitizar($ag['servico_nome']) ?></div><div class="portal-badge"><?= sanitizar(statusCliente($ag['status'])) ?></div></div>
<div class="booking-details"><strong><?= date('d/m/Y', $momento) ?></strong> às <?= date('H:i',$momento) ?><br><?= (int)$ag['duracao_min'] ?> min · R$ <?= number_format((float)$ag['preco'],2,',','.') ?><?php if(!empty($ag['observacoes'])): ?><br><span class="portal-sub">Observação: <?= nl2br(sanitizar($ag['observacoes'])) ?></span><?php endif; ?></div>
<div class="booking-action"><a href="conversa.php?salao=<?= urlencode($slug) ?>&id=<?= (int)$ag['id'] ?>">Conversar com o salão</a><?php if($ag['status']==='confirmado' && strtotime($ag['data_hora'])>time()): ?> · <a href="cancelar.php?token=<?= urlencode($ag['token_cliente']) ?>">Ver detalhes ou cancelar</a><?php endif; ?></div>
</section><?php endforeach; ?></div><?php endif; ?>
<?php endif; ?>
</main></body></html>

<?php
/**
 * conversa.php — conversa do cliente vinculada a um agendamento.
 * Requer o acesso temporário de "Meus agendamentos".
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cores.php';

$slug = isset($_GET['salao']) ? limparEntrada($_GET['salao']) : '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($slug === '' || $id < 1) { http_response_code(400); die('Conversa não informada.'); }

$pdo = conectar();
$stmt = $pdo->prepare('SELECT * FROM saloes WHERE slug=? LIMIT 1');
$stmt->execute([$slug]);
$salao = $stmt->fetch();
if (!$salao) { http_response_code(404); die('Salão não encontrado.'); }

$acesso = acessoClienteAtual((int)$salao['id']);
if (!$acesso) {
    header('Location: meus-agendamentos.php?salao=' . urlencode($slug));
    exit;
}

$ag = buscarConversaCliente($id, (int)$salao['id'], $acesso['email']);
if (!$ag) { http_response_code(404); die('Agendamento não encontrado.'); }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['_csrf'] ?? '')) {
        $erro = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $resultado = enviarMensagemAgendamento($id, (int)$salao['id'], 'cliente', (string)($_POST['mensagem'] ?? ''));
        if (!empty($resultado['sucesso'])) {
            header('Location: conversa.php?salao=' . urlencode($slug) . '&id=' . $id);
            exit;
        }
        $erro = $resultado['erro'] ?? 'Não foi possível enviar a mensagem.';
    }
}

$mensagens = listarMensagensAgendamento($id, (int)$salao['id']);
$paleta = paletaTema($salao['cor_primaria'], $salao['cor_secundaria'], $salao['cor_texto']);
?>
<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Conversa — <?= sanitizar($salao['nome_salao']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=<?= urlencode($salao['fonte_titulo']) ?>:wght@400;600&family=<?= urlencode($salao['fonte_corpo']) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/portal.css">
<style>:root{--p:<?= sanitizar($salao['cor_primaria']) ?>;--bg:<?= sanitizar($salao['cor_secundaria']) ?>;--txt:<?= sanitizar($salao['cor_texto']) ?>;--surface:<?= sanitizar($paleta['superficie']) ?>;--surface-2:<?= sanitizar($paleta['superficie_2']) ?>;--bord:<?= sanitizar($paleta['borda']) ?>;--txt2:<?= sanitizar($paleta['texto_secundario']) ?>;--on-p:<?= sanitizar($paleta['sobre_primaria']) ?>;--f-tit:'<?= addslashes($salao['fonte_titulo']) ?>',Georgia,serif;--f-bod:'<?= addslashes($salao['fonte_corpo']) ?>',sans-serif}</style>
</head><body class="portal"><main class="portal-wrap">
<div class="portal-nav"><a class="portal-link" href="meus-agendamentos.php?salao=<?= urlencode($slug) ?>">Meus agendamentos</a></div>
<section class="portal-card"><h1 class="portal-title">Conversa com o salão</h1><p class="portal-sub"><?= sanitizar($ag['servico_nome']) ?> · <?= date('d/m/Y', strtotime($ag['data_hora'])) ?> às <?= date('H:i', strtotime($ag['data_hora'])) ?></p></section>
<?php if($erro): ?><div class="portal-message error"><?= sanitizar($erro) ?></div><?php endif; ?>
<section class="portal-card chat-card">
<div class="chat-list">
<?php if(!$mensagens): ?><p class="portal-sub chat-empty">Ainda não há mensagens nesta conversa.</p><?php else: foreach($mensagens as $m): ?>
<div class="chat-message <?= $m['remetente']==='cliente' ? 'mine' : 'theirs' ?>"><div><?= nl2br(sanitizar($m['mensagem'])) ?></div><small><?= $m['remetente']==='cliente' ? 'Você' : 'Salão' ?> · <?= date('d/m H:i', strtotime($m['criado_em'])) ?></small></div>
<?php endforeach; endif; ?>
</div>
<form class="chat-form" method="post"><input type="hidden" name="_csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $id ?>"><textarea class="portal-input chat-input" name="mensagem" maxlength="1000" rows="3" placeholder="Digite sua mensagem" required></textarea><button class="portal-btn" type="submit">Enviar</button></form>
</section>
</main></body></html>

<?php
require_once __DIR__ . '/../includes/config.php';
redirecionarSe(empty($_SESSION['salao_id']), 'login.php');
require_once __DIR__ . '/../includes/tema.php';

$salaoId = (int)$_SESSION['salao_id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['_csrf'] ?? '')) {
        $erro = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';
        if ($acao === 'concluir') {
            $resultado = concluirAgendamentoPainel($id, $salaoId);
            $mensagem = !empty($resultado['sucesso']) ? 'Agendamento marcado como concluído.' : '';
            $erro = $resultado['erro'] ?? '';
        } elseif ($acao === 'cancelar') {
            $resultado = cancelarAgendamentoPainel($id, $salaoId);
            $mensagem = !empty($resultado['sucesso']) ? 'Agendamento cancelado.' : '';
            $erro = $resultado['erro'] ?? '';
        } elseif ($acao === 'mensagem') {
            $resultado = enviarMensagemAgendamento($id, $salaoId, 'salao', (string)($_POST['mensagem'] ?? ''));
            $mensagem = !empty($resultado['sucesso']) ? 'Mensagem enviada.' : '';
            $erro = $resultado['erro'] ?? '';
        }
    }
}

$ag = buscarAgendamentoPainel($id, $salaoId);
if (!$ag) {
    http_response_code(404);
    painel_head('Agendamento não encontrado');
    echo '</head><body>';
    painel_sidebar('agendamentos');
    echo '<main class="main"><h1 class="page-titulo">Agendamento não encontrado</h1><div class="card"><p>Este agendamento não existe ou pertence a outro salão.</p><p style="margin-top:18px"><a class="btn" href="index.php">Voltar para a agenda</a></p></div></main></body></html>';
    exit;
}

$mensagensConversa = listarMensagensAgendamento($id, $salaoId);

$statusNomes = ['pendente'=>'Pendente','confirmado'=>'Confirmado','cancelado'=>'Cancelado','concluido'=>'Concluído'];
$statusClasses = ['pendente'=>'badge-pen','confirmado'=>'badge-ok','cancelado'=>'badge-can','concluido'=>'badge-done'];
painel_head('Detalhes do agendamento');
?>
</head><body>
<?php painel_sidebar('agendamentos'); ?>
<main class="main">
  <a class="voltar-link" href="index.php">← Voltar para a agenda</a>
  <div class="detalhe-topo">
    <h1 class="page-titulo">Detalhes do agendamento</h1>
    <span class="badge <?= $statusClasses[$ag['status']] ?? '' ?>"><?= $statusNomes[$ag['status']] ?? sanitizar($ag['status']) ?></span>
  </div>

  <?php if ($mensagem): ?><div class="msg ok"><?= sanitizar($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="msg err"><?= sanitizar($erro) ?></div><?php endif; ?>

  <div class="card detalhe-card">
    <div class="detalhe-grid">
      <div><span>Cliente</span><strong><?= sanitizar($ag['cliente_nome']) ?></strong></div>
      <div><span>Serviço</span><strong><?= sanitizar($ag['servico_nome']) ?></strong></div>
      <div><span>Data</span><strong><?= date('d/m/Y', strtotime($ag['data_hora'])) ?></strong></div>
      <div><span>Horário</span><strong><?= date('H:i', strtotime($ag['data_hora'])) ?></strong></div>
      <div><span>Telefone</span><strong><?= sanitizar($ag['cliente_tel'] ?: 'Não informado') ?></strong></div>
      <div><span>E-mail</span><strong><?= sanitizar($ag['cliente_email'] ?: 'Não informado') ?></strong></div>
    </div>

    <div class="detalhe-observacoes">
      <span>Observações</span>
      <p><?= $ag['observacoes'] ? nl2br(sanitizar($ag['observacoes'])) : 'Nenhuma observação informada.' ?></p>
    </div>
  </div>

  <div class="card conversa-card">
    <div class="card-titulo">Conversa com o cliente</div>
    <div class="conversa-lista">
      <?php if (!$mensagensConversa): ?><p class="texto-secundario conversa-vazia">Ainda não há mensagens neste agendamento.</p><?php else: foreach ($mensagensConversa as $m): ?>
      <div class="conversa-msg <?= $m['remetente'] === 'salao' ? 'propria' : 'cliente' ?>"><div><?= nl2br(sanitizar($m['mensagem'])) ?></div><small><?= $m['remetente'] === 'salao' ? 'Salão' : 'Cliente' ?> · <?= date('d/m H:i', strtotime($m['criado_em'])) ?></small></div>
      <?php endforeach; endif; ?>
    </div>
    <form method="post" class="conversa-form">
      <input type="hidden" name="_csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= (int)$ag['id'] ?>"><input type="hidden" name="acao" value="mensagem">
      <textarea name="mensagem" maxlength="1000" rows="3" placeholder="Digite uma mensagem para o cliente" required></textarea><button class="btn" type="submit">Enviar</button>
    </form>
  </div>

  <?php if ($ag['status'] === 'confirmado' || $ag['status'] === 'pendente'): ?>
  <div class="card detalhe-acoes">
    <div>
      <strong>Ações</strong>
      <p>Altere o status somente quando necessário.</p>
    </div>
    <div class="acoes">
      <?php if ($ag['status'] === 'confirmado'): ?>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="id" value="<?= (int)$ag['id'] ?>">
        <input type="hidden" name="acao" value="concluir">
        <button class="btn" type="submit">Marcar como concluído</button>
      </form>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('Cancelar este agendamento?');">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="id" value="<?= (int)$ag['id'] ?>">
        <input type="hidden" name="acao" value="cancelar">
        <button class="btn btn-danger" type="submit">Cancelar agendamento</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</main>
</body></html>

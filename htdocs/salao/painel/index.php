<?php
require_once __DIR__ . '/../includes/config.php';
redirecionarSe(empty($_SESSION['salao_id']), 'login.php');
require_once __DIR__ . '/../includes/tema.php';

$pdo = conectar();
$salaoId = (int)$_SESSION['salao_id'];
$hoje = date('Y-m-d');
expirarPendentes($pdo, $salaoId);

$busca = limparEntrada($_GET['busca'] ?? '');
$status = $_GET['status'] ?? '';
$periodo = $_GET['periodo'] ?? 'hoje';
$pagina = max(1, (int)($_GET['p'] ?? 1));

$statusPermitidos = ['', 'pendente', 'confirmado', 'concluido', 'cancelado'];
$periodosPermitidos = ['hoje', 'amanha', '7dias', 'todos'];
if (!in_array($status, $statusPermitidos, true)) $status = '';
if (!in_array($periodo, $periodosPermitidos, true)) $periodo = 'hoje';

$where = ['a.salao_id = ?'];
$params = [$salaoId];

if ($busca !== '') {
    $where[] = '(a.cliente_nome LIKE ? OR a.cliente_tel LIKE ? OR a.cliente_email LIKE ?)';
    $termo = '%' . $busca . '%';
    array_push($params, $termo, $termo, $termo);
}
if ($status !== '') {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

if ($periodo === 'hoje') {
    $where[] = 'DATE(a.data_hora) = ?';
    $params[] = $hoje;
} elseif ($periodo === 'amanha') {
    $where[] = 'DATE(a.data_hora) = DATE_ADD(?, INTERVAL 1 DAY)';
    $params[] = $hoje;
} elseif ($periodo === '7dias') {
    $where[] = 'DATE(a.data_hora) BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)';
    array_push($params, $hoje, $hoje);
}

$sqlWhere = implode(' AND ', $where);
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM agendamentos a WHERE $sqlWhere");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($total / 20));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * 20;

$stmt = $pdo->prepare(
    "SELECT a.*, s.nome AS servico_nome
     FROM agendamentos a
     JOIN servicos s ON s.id = a.servico_id
     WHERE $sqlWhere
     ORDER BY a.data_hora ASC
     LIMIT 20 OFFSET $offset"
);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll();
$resumo = resumoAgendaPainel($salaoId);

function urlAgenda(array $alteracoes = []): string {
    $dados = array_merge($_GET, $alteracoes);
    foreach ($dados as $chave => $valor) if ($valor === '' || $valor === null) unset($dados[$chave]);
    return '?' . http_build_query($dados);
}

function nomeStatus(string $status): string {
    $nomes = ['pendente'=>'Pendente','confirmado'=>'Confirmado','cancelado'=>'Cancelado','concluido'=>'Concluído'];
    return $nomes[$status] ?? ucfirst($status);
}

function classeStatus(string $status): string {
    return ['confirmado'=>'badge-ok','pendente'=>'badge-pen','cancelado'=>'badge-can','concluido'=>'badge-done'][$status] ?? '';
}

painel_head('Agendamentos');
?>
</head><body>
<?php painel_sidebar('agendamentos'); ?>
<main class="main">
  <h1 class="page-titulo">Agendamentos</h1>

  <div class="stats-row stats-quatro">
    <div class="stat-card"><div class="stat-num"><?= $resumo['hoje'] ?></div><div class="stat-label">Hoje</div></div>
    <div class="stat-card"><div class="stat-num"><?= $resumo['confirmados'] ?></div><div class="stat-label">Confirmados</div></div>
    <div class="stat-card"><div class="stat-num"><?= $resumo['pendentes'] ?></div><div class="stat-label">Pendentes</div></div>
    <div class="stat-card"><div class="stat-num"><?= $resumo['concluidos'] ?></div><div class="stat-label">Concluídos hoje</div></div>
  </div>

  <form class="agenda-filtros" method="get">
    <div class="filtro-busca">
      <label class="lbl" for="busca">Buscar cliente</label>
      <input id="busca" type="text" name="busca" value="<?= sanitizar($busca) ?>" placeholder="Nome, telefone ou e-mail">
    </div>
    <div>
      <label class="lbl" for="periodo">Período</label>
      <select id="periodo" name="periodo">
        <option value="hoje" <?= $periodo==='hoje'?'selected':'' ?>>Hoje</option>
        <option value="amanha" <?= $periodo==='amanha'?'selected':'' ?>>Amanhã</option>
        <option value="7dias" <?= $periodo==='7dias'?'selected':'' ?>>Próximos 7 dias</option>
        <option value="todos" <?= $periodo==='todos'?'selected':'' ?>>Todos</option>
      </select>
    </div>
    <div>
      <label class="lbl" for="status">Status</label>
      <select id="status" name="status">
        <option value="">Todos</option>
        <option value="pendente" <?= $status==='pendente'?'selected':'' ?>>Pendente</option>
        <option value="confirmado" <?= $status==='confirmado'?'selected':'' ?>>Confirmado</option>
        <option value="concluido" <?= $status==='concluido'?'selected':'' ?>>Concluído</option>
        <option value="cancelado" <?= $status==='cancelado'?'selected':'' ?>>Cancelado</option>
      </select>
    </div>
    <button class="btn" type="submit">Filtrar</button>
    <?php if ($busca !== '' || $status !== '' || $periodo !== 'hoje'): ?>
      <a class="btn btn-outline" href="index.php">Limpar</a>
    <?php endif; ?>
  </form>

  <div class="tabela-wrap">
    <div class="tabela-header">Agenda</div>
    <?php if (!$agendamentos): ?>
      <div class="vazio"><?= ($busca !== '' || $status !== '') ? 'Nenhum agendamento encontrado com esses filtros.' : 'Nenhum agendamento para este período.' ?></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Data / Hora</th><th>Cliente</th><th>Serviço</th><th>Contato</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($agendamentos as $ag): ?>
      <tr>
        <td><strong><?= date('d/m/Y', strtotime($ag['data_hora'])) ?></strong><br><span class="texto-secundario"><?= date('H:i', strtotime($ag['data_hora'])) ?></span></td>
        <td><?= sanitizar($ag['cliente_nome']) ?></td>
        <td><?= sanitizar($ag['servico_nome']) ?></td>
        <td class="texto-secundario"><?= sanitizar($ag['cliente_tel'] ?: $ag['cliente_email']) ?></td>
        <td><span class="badge <?= classeStatus($ag['status']) ?>"><?= nomeStatus($ag['status']) ?></span></td>
        <td><a class="link-detalhes" href="agendamento.php?id=<?= (int)$ag['id'] ?>">Ver detalhes</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <?php if ($totalPaginas > 1): ?>
  <div class="paginacao">
    <span>Página <?= $pagina ?> de <?= $totalPaginas ?> (<?= $total ?> agendamentos)</span>
    <div>
      <?php if ($pagina > 1): ?><a class="btn btn-outline" href="<?= sanitizar(urlAgenda(['p'=>$pagina-1])) ?>">Anterior</a><?php endif; ?>
      <?php if ($pagina < $totalPaginas): ?><a class="btn" href="<?= sanitizar(urlAgenda(['p'=>$pagina+1])) ?>">Próxima</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</main>
</body></html>

<?php
// Cadastro e manutenção dos serviços oferecidos pelo salão.
require_once __DIR__ . '/../includes/config.php';
redirecionarSe(empty($_SESSION['salao_id']), 'login.php');
$pdo = conectar();
$salaoId = (int)$_SESSION['salao_id'];

// Garante coluna icone e carrega lista de ícones
try { $pdo->exec("ALTER TABLE servicos ADD COLUMN IF NOT EXISTS icone VARCHAR(30) DEFAULT NULL"); } catch (PDOException $e) {}
require_once __DIR__ . '/../includes/icones.php';
$msg=''; $msgTipo='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    exigirCsrf($_POST['_csrf'] ?? null);
    $acao = $_POST['acao']??'';
    if ($acao==='salvar') {
        $id   = (int)($_POST['id']??0);
        $nome   = limparEntrada($_POST['nome']??'');
        $desc   = limparEntrada($_POST['descricao']??'');
        $dur    = (int)($_POST['duracao_min']??60);
        $prec   = lerPreco((string)($_POST['preco']??'0'));
        $ord    = (int)($_POST['ordem']??0);
        // Ícone: valida que o slug existe na lista, senão usa NULL (autodetecta pelo nome)
        $icSlug = limparEntrada($_POST['icone']??'');
        $ic     = array_key_exists($icSlug, ICONES_SERVICO) ? $icSlug : null;
        if (!$nome) { $msg='Nome obrigatório.'; $msgTipo='err'; }
        elseif (mb_strlen($nome)>200 || mb_strlen($desc)>1000) { $msg='Nome ou descrição muito longos.'; $msgTipo='err'; }
        elseif ($dur < 5 || $dur > 480) { $msg='A duração deve ficar entre 5 e 480 minutos.'; $msgTipo='err'; }
        elseif ($prec === null || $prec < 0 || $prec > 999999.99) { $msg='Preço inválido.'; $msgTipo='err'; }
        elseif ($ord < 0 || $ord > 255) { $msg='A ordem deve ficar entre 0 e 255.'; $msgTipo='err'; }
        elseif ($id) {
            $pdo->prepare("UPDATE servicos SET nome=?,descricao=?,duracao_min=?,preco=?,ordem=?,icone=? WHERE id=? AND salao_id=?")->execute([$nome,$desc,$dur,$prec,$ord,$ic,$id,$salaoId]);
            $msg='Serviço atualizado!'; $msgTipo='ok';
        } else {
            $pdo->prepare("INSERT INTO servicos(salao_id,nome,descricao,duracao_min,preco,ordem,icone) VALUES(?,?,?,?,?,?,?)")->execute([$salaoId,$nome,$desc,$dur,$prec,$ord,$ic]);
            $msg='Serviço criado!'; $msgTipo='ok';
        }
    } elseif ($acao==='toggle') {
        $pdo->prepare("UPDATE servicos SET ativo=NOT ativo WHERE id=? AND salao_id=?")->execute([(int)$_POST['id'],$salaoId]);
    } elseif ($acao==='excluir') {
        $idExcluir=(int)($_POST['id']??0);
        $qtd=$pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE servico_id=? AND salao_id=?");
        $qtd->execute([$idExcluir,$salaoId]);
        if ((int)$qtd->fetchColumn() > 0) {
            $msg='Este serviço possui agendamentos vinculados. Desative-o em vez de excluir.'; $msgTipo='err';
        } else {
            $pdo->prepare("DELETE FROM servicos WHERE id=? AND salao_id=?")->execute([$idExcluir,$salaoId]);
            $msg='Serviço excluído.'; $msgTipo='ok';
        }
    }
}

$srvs = $pdo->prepare("SELECT * FROM servicos WHERE salao_id=? ORDER BY ordem,nome");
$srvs->execute([$salaoId]); $lista=$srvs->fetchAll();

require_once __DIR__ . '/../includes/tema.php';
painel_head('Serviços');
?>
</head><body>
<?php painel_sidebar('servicos'); ?>
<main class="main">
  <h1 class="page-titulo">Serviços</h1>
  <?php if($msg): ?><div class="msg <?= $msgTipo ?>"><?= sanitizar($msg) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-titulo" id="form-tit">Novo serviço</div>
    <form method="POST">
      <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" id="eid" value="0">
      <div class="form-row">
        <div class="form-group"><label class="lbl">Nome</label><input type="text" name="nome" id="enome" required placeholder="Ex: Corte feminino"></div>
        <div class="form-group"><label class="lbl">Preço (R$)</label><input type="text" name="preco" id="epreco" placeholder="60,00"></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="lbl">Duração (minutos)</label>
          <input type="number" name="duracao_min" id="edur" value="60" min="5" max="480">
          <div style="font-size:11px;color:var(--txt2);margin-top:5px">Quanto tempo o serviço bloqueia na agenda. Serviços longos bloqueiam múltiplos slots automaticamente.</div>
        </div>
        <div class="form-group"><label class="lbl">Ordem de exibição</label><input type="number" name="ordem" id="eord" value="0" min="0"></div>
      </div>
      <div class="form-group"><label class="lbl">Descrição curta</label><input type="text" name="descricao" id="edesc" placeholder="Opcional"></div>

      <!-- Seletor visual de ícone -->
      <div class="form-group">
        <label class="lbl">Ícone do serviço</label>
        <p style="font-size:12px;color:var(--txt2);margin-bottom:8px">Clique para selecionar. Deixe sem seleção para detectar automaticamente pelo nome.</p>
        <input type="hidden" name="icone" id="eicone" value="">
        <div style="display:flex;flex-wrap:wrap;gap:8px">
          <?php foreach (ICONES_SERVICO as $slug => $info): ?>
          <div class="icone-opt" data-slug="<?= $slug ?>" title="<?= htmlspecialchars($info['label']) ?>"
               onclick="selecionarIcone('<?= $slug ?>')"
               style="width:44px;height:44px;border:2px solid var(--bord);border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:border-color .15s,background .15s;background:var(--white)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--txt2)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
              <?= $info['path'] ?>
            </svg>
          </div>
          <?php endforeach; ?>
          <!-- Botão para limpar seleção -->
          <div onclick="selecionarIcone('')"
               title="Automático"
               style="width:44px;height:44px;border:2px dashed var(--bord);border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:18px;color:var(--txt2);transition:border-color .15s" id="icone-auto-btn">
            ✕
          </div>
        </div>
        <div style="font-size:12px;color:var(--txt2);margin-top:8px">
          Selecionado: <strong id="icone-label">automático</strong>
        </div>
      </div>

      <div style="display:flex;gap:10px">
        <button type="submit" class="btn">Salvar</button>
        <button type="button" class="btn btn-outline" onclick="limpar()">Cancelar</button>
      </div>
    </form>
  </div>

  <div class="tabela-wrap">
    <div class="tabela-header">Serviços cadastrados</div>
    <?php if(empty($lista)): ?>
      <div class="vazio">Nenhum serviço cadastrado.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Ícone</th><th>Nome</th><th>Duração</th><th>Preço</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($lista as $s): ?>
      <tr style="<?= $s['ativo']?'':'opacity:.45' ?>">
        <td style="width:48px">
          <?php $icSlug = !empty($s['icone']) ? $s['icone'] : iconeParaNome($s['nome']); ?>
          <div style="width:36px;height:36px;background:var(--p-soft);border-radius:8px;display:flex;align-items:center;justify-content:center">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
              <?= ICONES_SERVICO[$icSlug]['path'] ?? ICONES_SERVICO['sparkle']['path'] ?>
            </svg>
          </div>
        </td>
        <td><strong><?= sanitizar($s['nome']) ?></strong><?php if($s['descricao']): ?><br><small style="color:var(--txt2)"><?= sanitizar($s['descricao']) ?></small><?php endif; ?></td>
        <td><?= $s['duracao_min'] ?> min</td>
        <td>R$ <?= number_format($s['preco'],2,',','.') ?></td>
        <td><span class="badge <?= $s['ativo']?'badge-ok':'badge-pen' ?>"><?= $s['ativo']?'Ativo':'Inativo' ?></span></td>
        <td>
          <div class="acoes">
            <button class="btn btn-outline" style="padding:5px 12px;font-size:12px" onclick='editar(<?= json_encode($s) ?>)'>Editar</button>
            <form method="POST" style="display:inline"><input type="hidden" name="_csrf" value="<?= csrfToken() ?>"><input type="hidden" name="acao" value="toggle"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" class="btn btn-outline" style="padding:5px 12px;font-size:12px"><?= $s['ativo']?'Desativar':'Ativar' ?></button></form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Excluir?')"><input type="hidden" name="_csrf" value="<?= csrfToken() ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" class="btn btn-danger" style="padding:5px 12px;font-size:12px">Excluir</button></form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</main>
<script>
function editar(s){
  document.getElementById('form-tit').textContent='Editar serviço';
  document.getElementById('eid').value=s.id;
  document.getElementById('enome').value=s.nome;
  document.getElementById('epreco').value=s.preco;
  document.getElementById('edur').value=s.duracao_min;
  document.getElementById('edesc').value=s.descricao||'';
  document.getElementById('eord').value=s.ordem;
  selecionarIcone(s.icone||'');
  window.scrollTo({top:0,behavior:'smooth'});
}
function selecionarIcone(slug) {
  // Atualiza o campo hidden
  document.getElementById('eicone').value = slug;
  // Destaca o ícone selecionado visualmente
  document.querySelectorAll('.icone-opt').forEach(el => {
    const sel = el.dataset.slug === slug;
    el.style.borderColor  = sel ? 'var(--p)' : 'var(--bord)';
    el.style.background   = sel ? 'var(--p-soft)' : 'var(--white)';
    el.querySelector('svg').setAttribute('stroke', sel ? 'var(--p)' : 'var(--txt2)');
  });
  // Atualiza o botão "auto"
  const autoBtn = document.getElementById('icone-auto-btn');
  if (autoBtn) autoBtn.style.borderColor = slug === '' ? 'var(--p)' : 'var(--bord)';
  // Atualiza o label
  const labels = {<?php
    $parts = [];
    foreach (ICONES_SERVICO as $sl => $inf)
        $parts[] = "'$sl':'" . addslashes($inf['label']) . "'";
    echo implode(',', $parts);
  ?>};
  document.getElementById('icone-label').textContent = slug ? (labels[slug]||slug) : 'automático';
}
function limpar(){
  document.getElementById('form-tit').textContent='Novo serviço';
  document.getElementById('eid').value='0';
  document.querySelector('form').reset();
}
</script>
</body></html>

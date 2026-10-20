<?php
/**
 * painel/aparencia.php — Configurações visuais do salão
 *
 * Permite ao dono personalizar:
 *   - Nome, descrição, WhatsApp, e-mail e endereço
 *   - Cores primária, de fundo e do texto (com color pickers)
 *   - Fontes dos títulos e do corpo (25 opções, com preview em tempo real)
 *   - Logo (circular, exibido na sidebar e no topbar mobile)
 *   - Banner (imagem horizontal no topo da sidebar)
 *
 * Todas as configurações são salvas na tabela "saloes" e aplicadas
 * automaticamente via CSS variables ao carregar a página do cliente.
 * Não há necessidade de editar código para mudar o visual.
 *
 * Fontes disponíveis: qualquer uma pode ser usada tanto em título
 * quanto no corpo — não há restrição entre "título" e "corpo".
 */
require_once __DIR__ . '/../includes/config.php';
redirecionarSe(empty($_SESSION['salao_id']), 'login.php');

// tema.php é carregado DEPOIS do processamento do POST
// para que o CSS do painel reflita os valores recém salvos (evita precisar de dois cliques)
$pdo     = conectar();
$salaoId = (int)$_SESSION['salao_id'];
$msg=''; $msgTipo='';

// Fontes aceitas pelo tema. A whitelist evita valores arbitrários no CSS.
$todasFontes = [
    'Cormorant Garamond','Playfair Display','Libre Baskerville','Lora','Merriweather',
    'EB Garamond','Crimson Pro','GFS Didot','Spectral','Zilla Slab',
    'DM Sans','Plus Jakarta Sans','Lato','Nunito','Open Sans','Poppins',
    'Raleway','Work Sans','Inter','Outfit','Figtree','Urbanist',
    'Space Mono','Space Grotesk','Syne','Josefin Sans','Josefin Slab',
];
sort($todasFontes);

// Carrega configurações dos toggles (modo_dev, show_onboarding e textos do onboarding)
// ALTER TABLE garante as colunas mesmo em bancos antigos
$salao_config = ['modo_dev' => 1, 'show_onboarding' => 1];
try {
    foreach (['modo_dev','show_onboarding','ob_titulo1','ob_texto1','ob_titulo2','ob_texto2','ob_titulo3','ob_texto3','home_titulo','home_subtitulo'] as $_col) {
        $pdo->exec("ALTER TABLE saloes ADD COLUMN IF NOT EXISTS $_col " . ($_col === 'modo_dev' || $_col === 'show_onboarding' ? 'TINYINT(1) NOT NULL DEFAULT 1' : 'VARCHAR(300) DEFAULT NULL'));
    }
    $pdo->exec("ALTER TABLE saloes ADD COLUMN IF NOT EXISTS email_notificacao VARCHAR(200) DEFAULT NULL");
    $r = $pdo->prepare("SELECT modo_dev, show_onboarding, ob_titulo1, ob_texto1, ob_titulo2, ob_texto2, ob_titulo3, ob_texto3, home_titulo, home_subtitulo FROM saloes WHERE id = ?");
    $r->execute([$salaoId]);
    $row = $r->fetch();
    if ($row) $salao_config = $row;
} catch (PDOException $e) { /* fallback silencioso */ }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    exigirCsrf($_POST['_csrf'] ?? null);
    $campos = [
        'nome_salao'    => limparEntrada($_POST['nome_salao']    ?? ''),
        'descricao'     => limparEntrada($_POST['descricao']     ?? ''),
        'cor_primaria'  => preg_match('/^#[0-9a-fA-F]{6}$/',$_POST['cor_primaria']  ??'')?$_POST['cor_primaria'] :'#4a8c6e',
        'cor_secundaria'=> preg_match('/^#[0-9a-fA-F]{6}$/',$_POST['cor_secundaria']??'')?$_POST['cor_secundaria']:'#e8f5ee',
        'cor_texto'     => preg_match('/^#[0-9a-fA-F]{6}$/',$_POST['cor_texto']     ??'')?$_POST['cor_texto']    :'#1a2e25',
        'fonte_titulo'  => limparEntrada($_POST['fonte_titulo']  ?? 'Cormorant Garamond'),
        'fonte_corpo'   => limparEntrada($_POST['fonte_corpo']   ?? 'DM Sans'),
        'whatsapp'      => limparEntrada($_POST['whatsapp']      ?? ''),
        'email_contato' => limparEntrada($_POST['email_contato'] ?? ''),
        'email_notificacao' => limparEntrada($_POST['email_notificacao'] ?? ''),
        'endereco'      => limparEntrada($_POST['endereco']      ?? ''),
        'home_titulo'    => limparEntrada($_POST['home_titulo']    ?? ''),
        'home_subtitulo' => limparEntrada($_POST['home_subtitulo'] ?? ''),
    ];
    $campos['whatsapp'] = preg_replace('/\D/', '', $campos['whatsapp']);
    if ($campos['nome_salao'] === '') {
        $msg='O nome do salão é obrigatório.'; $msgTipo='err';
    } elseif (mb_strlen($campos['nome_salao']) > 200 || mb_strlen($campos['descricao']) > 3000 || mb_strlen($campos['endereco']) > 300 || mb_strlen($campos['home_titulo']) > 120 || mb_strlen($campos['home_subtitulo']) > 200) {
        $msg='Um dos textos excede o tamanho permitido.'; $msgTipo='err';
    } elseif ($campos['whatsapp'] !== '' && (strlen($campos['whatsapp']) < 10 || strlen($campos['whatsapp']) > 20)) {
        $msg='WhatsApp inválido.'; $msgTipo='err';
    } elseif ($campos['email_contato'] !== '' && !filter_var($campos['email_contato'], FILTER_VALIDATE_EMAIL)) {
        $msg='E-mail de contato inválido.'; $msgTipo='err';
    } elseif ($campos['email_notificacao'] !== '' && !filter_var($campos['email_notificacao'], FILTER_VALIDATE_EMAIL)) {
        $msg='E-mail para notificações inválido.'; $msgTipo='err';
    } elseif (!in_array($campos['fonte_titulo'], $todasFontes, true) || !in_array($campos['fonte_corpo'], $todasFontes, true)) {
        $msg='Fonte inválida.'; $msgTipo='err';
    }

    $arquivosNovos = [];
    $arquivosAntigos = [];
    if ($msgTipo !== 'err') {
        foreach (['logo','banner'] as $tipo) {
            if (empty($_FILES[$tipo]['tmp_name']) || !empty($_FILES[$tipo]['error'])) continue;

            $ext = strtolower(pathinfo($_FILES[$tipo]['name'], PATHINFO_EXTENSION));
            $extensoes = ['jpg','jpeg','png','webp'];
            $max = $tipo === 'logo' ? 2*1024*1024 : 5*1024*1024;
            $info = @getimagesize($_FILES[$tipo]['tmp_name']);
            $mimes = ['image/jpeg','image/png','image/webp'];
            $mimeOk = $info !== false && !empty($info['mime']) && in_array($info['mime'], $mimes, true);

            if (!in_array($ext, $extensoes, true) || $_FILES[$tipo]['size'] <= 0 || $_FILES[$tipo]['size'] > $max || !$mimeOk) {
                $msg = 'Arquivo de ' . $tipo . ' inválido. Use JPG, PNG ou WEBP dentro do limite de tamanho.';
                $msgTipo = 'err';
                break;
            }

            if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
            $nomeArq = $tipo . '_' . $salaoId . '_' . time() . '_' . random_int(1000,9999) . '.' . $ext;
            $dest = UPLOAD_DIR . $nomeArq;
            if (!move_uploaded_file($_FILES[$tipo]['tmp_name'], $dest)) {
                $msg = 'Não foi possível salvar o arquivo de ' . $tipo . '.';
                $msgTipo = 'err';
                break;
            }

            $arquivosNovos[] = $dest;
            $campoUrl = $tipo . '_url';
            $qAntiga = $pdo->prepare("SELECT $campoUrl FROM saloes WHERE id=?");
            $qAntiga->execute([$salaoId]);
            $urlAntiga = (string)$qAntiga->fetchColumn();
            if ($urlAntiga && strpos($urlAntiga, '/assets/uploads/') !== false) {
                $arquivoAntigo = UPLOAD_DIR . basename(parse_url($urlAntiga, PHP_URL_PATH));
                if (is_file($arquivoAntigo)) $arquivosAntigos[] = $arquivoAntigo;
            }
            $campos[$campoUrl] = BASE_URL . '/assets/uploads/' . $nomeArq;
        }
    }

    if ($msgTipo === 'err') {
        foreach ($arquivosNovos as $arquivoNovo) if (is_file($arquivoNovo)) @unlink($arquivoNovo);
    }

    if ($msgTipo !== 'err') {
        $sets = implode(',', array_map(function($k){ return $k . '=?'; }, array_keys($campos)));
        $vals = array_values($campos); $vals[] = $salaoId;
        $pdo->prepare("UPDATE saloes SET $sets WHERE id=?")->execute($vals);
        foreach ($arquivosAntigos as $arquivoAntigo) if (is_file($arquivoAntigo)) @unlink($arquivoAntigo);
        $_SESSION['salao_nome'] = $campos['nome_salao'];
        $msg='Aparência salva!'; $msgTipo='ok';
    }

}

// Carrega tema com os dados já atualizados
require_once __DIR__ . '/../includes/tema.php';
painel_head('Aparência');
?>
</head><body>
<?php painel_sidebar('aparencia'); ?>
<main class="main">
  <style>
    .save-bar{position:sticky;bottom:14px;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:16px;margin:18px 0 28px;padding:12px 14px;background:var(--surface);border:1px solid var(--bord);border-radius:8px;box-shadow:0 5px 18px rgba(0,0,0,.08)}
    .save-bar span{font-size:12px;color:var(--txt2)}
    .save-bar.alterado{border-color:var(--p)}
    @media(max-width:600px){.save-bar{bottom:8px}.save-bar span{display:none}.save-bar .btn{width:100%!important}}
  </style>
  <h1 class="page-titulo">Aparência</h1>
  <?php if($msg): ?><div class="msg <?= $msgTipo ?>"><?= sanitizar($msg) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="form-aparencia">
    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

    <!-- Identidade -->
    <div class="card">
      <div class="card-titulo">Identidade</div>
      <div class="form-row">
        <div class="form-group">
          <label class="lbl">Nome do salão</label>
          <input type="text" name="nome_salao" value="<?= sanitizar($tema['nome_salao']) ?>" required>
        </div>
        <div class="form-group">
          <label class="lbl">WhatsApp</label>
          <input type="tel" name="whatsapp" value="<?= sanitizar($tema['whatsapp']??'') ?>" placeholder="5511999990000">
        </div>
      </div>
      <div class="form-group">
        <label class="lbl">Descrição curta</label>
        <textarea name="descricao"><?= sanitizar($tema['descricao']??'') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="lbl">E-mail de contato</label>
          <input type="email" name="email_contato" value="<?= sanitizar($tema['email_contato']??'') ?>">
          <div style="font-size:12px;color:var(--txt2);margin-top:6px">Exibido para os clientes na página do salão.</div>
        </div>
        <div class="form-group">
          <label class="lbl">E-mail para notificações (opcional)</label>
          <input type="email" name="email_notificacao" value="<?= sanitizar($tema['email_notificacao']??'') ?>" placeholder="dono@exemplo.com">
          <div style="font-size:12px;color:var(--txt2);margin-top:6px">Recebe avisos de novos agendamentos confirmados. Se ficar vazio, será usado o e-mail de contato. Não é exibido ao cliente.</div>
        </div>
      </div>
      <div class="form-group">
        <label class="lbl">Endereço</label>
        <input type="text" name="endereco" value="<?= sanitizar($tema['endereco']??'') ?>">
      </div>
    </div>

    <!-- Texto inicial -->
    <div class="card">
      <div class="card-titulo">Tela inicial</div>
      <p style="font-size:13px;color:var(--txt2);margin-bottom:16px">Textos mostrados acima da lista de serviços. Se ficar vazio, o texto padrão é usado.</p>
      <div class="form-group">
        <label class="lbl">Título</label>
        <input type="text" name="home_titulo" maxlength="120" value="<?= sanitizar($tema['home_titulo'] ?? '') ?>" placeholder="Bem-vindo">
      </div>
      <div class="form-group">
        <label class="lbl">Texto de apoio</label>
        <input type="text" name="home_subtitulo" maxlength="200" value="<?= sanitizar($tema['home_subtitulo'] ?? '') ?>" placeholder="Agende seu horário de forma rápida e fácil">
      </div>
    </div>

    <!-- Cores -->
    <div class="card">
      <div class="card-titulo">Cores</div>
      <div class="form-row">
        <?php foreach(['cor_primaria'=>'Cor principal','cor_secundaria'=>'Cor de fundo','cor_texto'=>'Cor do texto'] as $campo=>$label):
              $val = $tema[$campo]; ?>
        <div class="form-group">
          <label class="lbl"><?= $label ?></label>
          <div class="cor-row">
            <input type="color" id="<?= $campo ?>_p" value="<?= $val ?>" oninput="sincronizarCor('<?= $campo ?>', this.value)" onchange="sincronizarCor('<?= $campo ?>', this.value)">
            <input type="text" id="<?= $campo ?>" name="<?= $campo ?>" value="<?= $val ?>" class="cor-hex" pattern="^#[0-9a-fA-F]{6}$" oninput="sincronizarCorTexto('<?= $campo ?>', this.value)">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <p style="font-size:12px;color:var(--txt2);margin-top:4px;line-height:1.55">Cards, campos, bordas e textos secundários ajustam o contraste automaticamente a partir dessas três cores. Assim, temas claros e escuros permanecem legíveis sem exigir uma cor separada para cada elemento.</p>
    </div>

    <!-- Tipografia -->
    <div class="card">
      <div class="card-titulo">Tipografia</div>
      <p style="font-size:13px;color:var(--txt2);margin-bottom:18px">Todas as fontes podem ser usadas tanto em títulos quanto no corpo do texto.</p>
      <div class="form-row">
        <div class="form-group">
          <label class="lbl">Fonte dos títulos</label>
          <select name="fonte_titulo" id="sel-tit" onchange="previewFonte('tit',this.value)">
            <?php foreach($todasFontes as $f): ?>
            <option value="<?= $f ?>" <?= $tema['fonte_titulo']===$f?'selected':'' ?>><?= $f ?></option>
            <?php endforeach; ?>
          </select>
          <div id="preview-tit" style="margin-top:10px;padding:12px 14px;background:var(--surface-2);border:1px solid var(--bord);border-radius:6px;font-size:1.4rem;line-height:1.3">Exemplo de título</div>
        </div>
        <div class="form-group">
          <label class="lbl">Fonte do corpo</label>
          <select name="fonte_corpo" id="sel-bod" onchange="previewFonte('bod',this.value)">
            <?php foreach($todasFontes as $f): ?>
            <option value="<?= $f ?>" <?= $tema['fonte_corpo']===$f?'selected':'' ?>><?= $f ?></option>
            <?php endforeach; ?>
          </select>
          <div id="preview-bod" style="margin-top:10px;padding:12px 14px;background:var(--surface-2);border:1px solid var(--bord);border-radius:6px;font-size:15px;line-height:1.6">Exemplo de texto do corpo. Agende seu horário de forma rápida.</div>
        </div>
      </div>
    </div>

    <!-- Imagens -->
    <div class="card">
      <div class="card-titulo">Imagens</div>
      <div class="form-row">

        <!-- Logo: prévia circular + botão de upload abaixo -->
        <div class="form-group">
          <label class="lbl">Logo (PNG, JPG ou WEBP — máx 2 MB)</label>
          <!-- Prévia: circular, 80×80. Mostra inicial do nome se sem logo. -->
          <?php if(!empty($tema['logo_url'])): ?>
          <img id="prev-logo" src="<?= sanitizar($tema['logo_url']) ?>" alt="Logo atual"
               style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid var(--bord);display:block;margin-bottom:10px">
          <?php else: ?>
          <div id="prev-logo" style="width:80px;height:80px;border-radius:50%;background:var(--p-soft);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:600;color:var(--p);margin-bottom:10px">
            <?= mb_strtoupper(mb_substr($tema['nome_salao'],0,1)) ?>
          </div>
          <?php endif; ?>
          <label class="upload-area">
            Clique para enviar logo
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" style="display:none"
                   onchange="previewImagem(this,'prev-logo','circle')">
          </label>
        </div>

        <!-- Banner: prévia retangular + botão de upload abaixo -->
        <div class="form-group">
          <label class="lbl">Banner (JPG, PNG ou WEBP — máx 5 MB)</label>
          <p style="font-size:12px;color:var(--txt2);margin-bottom:8px">Imagem horizontal exibida no topo da barra lateral.</p>
          <!-- Prévia: retângulo 100%×90px -->
          <?php if(!empty($tema['banner_url'])): ?>
          <img id="prev-banner" src="<?= sanitizar($tema['banner_url']) ?>" alt="Banner atual"
               style="width:100%;height:90px;object-fit:cover;border-radius:6px;display:block;margin-bottom:10px">
          <?php else: ?>
          <div id="prev-banner" style="width:100%;height:90px;background:var(--p-soft);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--txt2);margin-bottom:10px">Sem banner</div>
          <?php endif; ?>
          <label class="upload-area">
            Clique para enviar banner
            <input type="file" name="banner" accept="image/jpeg,image/png,image/webp" style="display:none"
                   onchange="previewImagem(this,'prev-banner','rect')">
          </label>
        </div>

      </div>
    </div>

    <div class="save-bar" id="save-bar">
      <span id="save-status">Nenhuma alteração pendente</span>
      <button type="submit" class="btn" id="btn-salvar" style="width:auto;min-width:160px">Salvar alterações</button>
    </div>
  </form>

  <!-- ─── Configurações do cliente ─── -->
  <div class="card" style="margin-top:8px">
    <div class="card-titulo">Configurações do cliente</div>
    <!-- Toggle: Mostrar código de verificação na tela -->
    <div style="display:flex;align-items:center;justify-content:space-between;background:var(--white);border:1px solid var(--bord);border-radius:var(--r);padding:16px 20px;margin-bottom:16px">
    <div style="flex:1;min-width:0">
      <div style="font-size:14px;font-weight:600">Modo de teste</div>
      <div style="font-size:12px;color:var(--txt2);margin-top:2px">Ativado: o código aparece na tela. Desativado: usa o SMTP quando ele estiver habilitado na instalação.</div>
      <div style="font-size:12px;margin-top:6px;font-weight:600;color:<?= smtpConfigurado() ? '#2f7a52' : 'var(--txt2)' ?>">SMTP da instalação: <?= smtpConfigurado() ? 'configurado e ativo' : 'desativado — o código continuará aparecendo na tela' ?></div>
    </div>
      <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;margin-left:24px;cursor:pointer" onclick="toggleConfig('modo_dev', this)">
      <div class="tog-track" data-on="<?= !empty($salao_config['modo_dev']) ? '1' : '0' ?>" style="width:44px;height:24px;border-radius:12px;background:<?= !empty($salao_config['modo_dev']) ? 'var(--p)' : '#ccc' ?>;position:relative;transition:background .2s">
        <div class="tog-thumb" style="position:absolute;top:2px;left:<?= !empty($salao_config['modo_dev']) ? '22px' : '2px' ?>;width:20px;height:20px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></div>
      </div>
      <span class="tog-label" style="font-size:13px;font-weight:600;color:var(--txt2);min-width:44px"><?= !empty($salao_config['modo_dev']) ? 'Ativo' : 'Inativo' ?></span>
    </div>
  </div>

    <!-- Toggle: Exibir tela de boas-vindas (onboarding) -->
    <div style="display:flex;align-items:center;justify-content:space-between;background:var(--white);border:1px solid var(--bord);border-radius:var(--r);padding:16px 20px;margin-bottom:16px">
    <div style="flex:1;min-width:0">
      <div style="font-size:14px;font-weight:600">Exibir tela de boas-vindas (onboarding)</div>
      <div style="font-size:12px;color:var(--txt2);margin-top:2px">Ativado: clientes veem os slides introdutórios ao abrir a página do salão.</div>
    </div>
      <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;margin-left:24px;cursor:pointer" onclick="toggleConfig('show_onboarding', this)">
      <div class="tog-track" data-on="<?= !empty($salao_config['show_onboarding']) ? '1' : '0' ?>" style="width:44px;height:24px;border-radius:12px;background:<?= !empty($salao_config['show_onboarding']) ? 'var(--p)' : '#ccc' ?>;position:relative;transition:background .2s">
        <div class="tog-thumb" style="position:absolute;top:2px;left:<?= !empty($salao_config['show_onboarding']) ? '22px' : '2px' ?>;width:20px;height:20px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></div>
      </div>
      <span class="tog-label" style="font-size:13px;font-weight:600;color:var(--txt2);min-width:44px"><?= !empty($salao_config['show_onboarding']) ? 'Ativo' : 'Inativo' ?></span>
    </div>
  </div>
  <!-- Card de edição dos slides (visível quando onboarding está ativo) -->
  <div id="painel-onboarding" style="display:<?= !empty($salao_config['show_onboarding']) ? 'block' : 'none' ?>">
    <form method="POST" action="../api/salvar_onboarding.php" id="form-ob" style="background:var(--white);border:1px solid var(--bord);border-radius:var(--r);padding:20px;margin-bottom:16px">
      <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
      <input type="hidden" name="salao_id" value="<?= $salaoId ?>">
      <div style="font-size:13px;font-weight:600;margin-bottom:16px;color:var(--txt2);text-transform:uppercase;letter-spacing:.05em">Textos do onboarding</div>
      <?php
      $defaults = [
        1 => ['Bem-vindo ao '.$_SESSION['salao_nome'], 'Agende seus serviços de beleza de forma rápida, fácil e sem complicação.'],
        2 => ['Agende com facilidade', 'Selecione o melhor dia e horário para você com poucos toques.'],
        3 => ['Confirmação instantânea', 'Receba a confirmação do agendamento pelo seu e-mail.'],
      ];
      for ($i=1; $i<=3; $i++): ?>
      <div style="margin-bottom:20px;padding:16px;background:var(--bg);border-radius:8px">
        <div style="font-size:12px;font-weight:700;color:var(--txt2);margin-bottom:10px">Slide <?= $i ?></div>
        <div style="margin-bottom:10px">
          <label class="lbl">Título</label>
          <input type="text" name="ob_titulo<?= $i ?>" value="<?= sanitizar($salao_config['ob_titulo'.$i] ?? '') ?>" placeholder="<?= htmlspecialchars($defaults[$i][0]) ?>" style="width:100%;border:1.5px solid var(--bord);border-radius:6px;padding:9px 12px;font-family:var(--f-bod);font-size:14px;color:var(--txt);background:var(--surface-2);outline:none">
        </div>
        <div>
          <label class="lbl">Texto</label>
          <input type="text" name="ob_texto<?= $i ?>" value="<?= sanitizar($salao_config['ob_texto'.$i] ?? '') ?>" placeholder="<?= htmlspecialchars($defaults[$i][1]) ?>" style="width:100%;border:1.5px solid var(--bord);border-radius:6px;padding:9px 12px;font-family:var(--f-bod);font-size:14px;color:var(--txt);background:var(--surface-2);outline:none">
        </div>
      </div>
      <?php endfor; ?>
      <button type="submit" class="btn" style="padding:9px 20px;font-size:13px">Salvar textos</button>
      <?php if (isset($_GET['ob_saved'])): ?>
        <span style="margin-left:12px;font-size:13px;color:var(--txt-success, #166534)">✓ Salvo com sucesso</span>
      <?php endif; ?>
    </form>
  </div>

  </div>

</main>

<script>
const formAparencia = document.getElementById('form-aparencia');
const saveBar = document.getElementById('save-bar');
const saveStatus = document.getElementById('save-status');

function marcarAlteracao() {
  if (!saveBar) return;
  saveBar.classList.add('alterado');
  if (saveStatus) saveStatus.textContent = 'Há alterações não salvas';
}

function sincronizarCor(campo, valor) {
  const texto = document.getElementById(campo);
  if (texto) texto.value = valor;
  marcarAlteracao();
}

function sincronizarCorTexto(campo, valor) {
  if (/^#[0-9a-fA-F]{6}$/.test(valor)) {
    const seletor = document.getElementById(campo + '_p');
    if (seletor) seletor.value = valor;
  }
  marcarAlteracao();
}

if (formAparencia) {
  formAparencia.addEventListener('input', marcarAlteracao);
  formAparencia.addEventListener('change', marcarAlteracao);
}

// Preview de fonte ao selecionar
const fontCache = {};
function previewFonte(tipo, fonte) {
  const el   = document.getElementById('preview-' + tipo);
  const link = document.getElementById('font-link-' + tipo) || document.createElement('link');
  link.id   = 'font-link-' + tipo;
  link.rel  = 'stylesheet';
  link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(fonte)}:wght@300;400;600&display=swap`;
  document.head.appendChild(link);
  el.style.fontFamily = `'${fonte}', serif`;
}
// Inicializa previews com as fontes atuais
previewFonte('tit', document.getElementById('sel-tit').value);
previewFonte('bod', document.getElementById('sel-bod').value);

// Preview de imagem antes de salvar
function previewImagem(input, targetId, shape) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const el = document.getElementById(targetId);
    if (!el) return;
    el.tagName === 'IMG'
      ? (el.src = e.target.result)
      : (() => {
          const img = document.createElement('img');
          img.id    = targetId;
          img.src   = e.target.result;
          img.style.cssText = shape === 'circle'
            ? 'width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid var(--bord);display:block;margin-bottom:10px'
            : 'width:100%;height:90px;object-fit:cover;border-radius:6px;display:block;margin-bottom:10px';
          el.replaceWith(img);
        })();
  };
  reader.readAsDataURL(input.files[0]);
}

// ── Toggles (modo_dev e show_onboarding) ──────────────────────────────
// Chamada quando o usuário clica no div do toggle.
// Lê o estado atual em data-on, inverte, atualiza visual e persiste via API.
async function toggleConfig(campo, container) {
  const track = container.querySelector('.tog-track');
  const thumb = container.querySelector('.tog-thumb');
  const label = container.querySelector('.tog-label');
  if (!track) return;

  const eraAtivo  = track.dataset.on === '1';
  const novoAtivo = !eraAtivo;

  // Atualiza visual imediatamente (feedback instantâneo)
  track.dataset.on       = novoAtivo ? '1' : '0';
  track.style.background = novoAtivo ? 'var(--p)' : '#ccc';
  if (thumb) thumb.style.left   = novoAtivo ? '22px' : '2px';
  if (label) label.textContent  = novoAtivo ? 'Ativo' : 'Inativo';

  // Mostra/esconde o card de edição dos textos do onboarding
  if (campo === 'show_onboarding') {
    const card = document.getElementById('painel-onboarding');
    if (card) card.style.display = novoAtivo ? 'block' : 'none';
  }

  // Persiste no banco via API
  try {
    const r = await fetch('../api/config.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        _csrf: '<?= csrfToken() ?>',
        salao_id: <?= (int)$salaoId ?>,
        campo,
        valor: novoAtivo ? 1 : 0
      })
    });
    const j = await r.json();
    if (!j.sucesso) throw new Error(j.erro || 'Erro desconhecido');
  } catch (e) {
    // Reverte o visual em caso de erro de rede
    track.dataset.on       = eraAtivo ? '1' : '0';
    track.style.background = eraAtivo ? 'var(--p)' : '#ccc';
    if (thumb) thumb.style.left  = eraAtivo ? '22px' : '2px';
    if (label) label.textContent = eraAtivo ? 'Ativo' : 'Inativo';
    alert('Erro ao salvar configuração. Tente novamente.');
  }
}
</script>
</body></html>

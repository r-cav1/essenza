<?php
/**
 * includes/tema.php
 * Carrega cores/fontes do salão logado e expõe:
 *   painel_head(string $titulo)
 *   painel_sidebar(string $ativa)
 */

require_once __DIR__ . '/cores.php';

if (empty($_SESSION['salao_id'])) {
    header('Location: login.php'); exit;
}

$pdo_tema  = conectar();
$stmt_tema = $pdo_tema->prepare("SELECT * FROM saloes WHERE id = ? LIMIT 1");
$stmt_tema->execute([(int)$_SESSION['salao_id']]);
$tema = $stmt_tema->fetch();

if (!$tema) { session_destroy(); header('Location: login.php'); exit; }

$paleta = paletaTema($tema['cor_primaria'], $tema['cor_secundaria'], $tema['cor_texto']);
$_P     = $paleta['primaria'];
$_COR_S = $paleta['fundo'];
$_DARK  = $paleta['primaria_escura'];
$_SOFT  = $paleta['primaria_suave'];
$_BORD  = corClarear($_P, 0.72);
$_TXT   = $paleta['texto'];
$_SURF  = $paleta['superficie'];
$_SURF2 = $paleta['superficie_2'];
$_BORDER_NEUTRAL = $paleta['borda'];
$_MUTED = $paleta['texto_secundario'];
$_ON_P  = $paleta['sobre_primaria'];
$_ON_P_DARK = corContraste($_DARK);
$_FT    = addslashes($tema['fonte_titulo']);
$_FB    = addslashes($tema['fonte_corpo']);
$_FT_ENC = urlencode($tema['fonte_titulo']);
$_FB_ENC = urlencode($tema['fonte_corpo']);

function painel_head(string $titulo): void {
    global $_P, $_DARK, $_SOFT, $_BORD, $_TXT, $_FT, $_FB, $_FT_ENC, $_FB_ENC, $_COR_S, $_SURF, $_SURF2, $_BORDER_NEUTRAL, $_MUTED, $_ON_P, $_ON_P_DARK;
    echo '<!DOCTYPE html><html lang="pt-BR"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo "<title>{$titulo} — Painel</title>";
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo "<link href=\"https://fonts.googleapis.com/css2?family={$_FT_ENC}:ital,wght@0,300;0,400;0,600;1,300&family={$_FB_ENC}:wght@300;400;500;600&display=swap\" rel=\"stylesheet\">";
    echo "<style>
:root{
  --p:      {$_P};
  --p-dark: {$_DARK};
  --p-soft: {$_SOFT};
  --p-bord: {$_BORD};
  --txt:    {$_TXT};
  --txt2:   {$_MUTED};
  --bg:     {$_COR_S};
  --surface:{$_SURF};
  --surface-2:{$_SURF2};
  --on-p:   {$_ON_P};
  --on-p-dark:{$_ON_P_DARK};
  --white:  var(--surface);
  --bord:   {$_BORDER_NEUTRAL};
  --f-tit:  '{$_FT}',Georgia,serif;
  --f-bod:  '{$_FB}',sans-serif;
  --r:      8px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{max-width:100%;overflow-x:hidden;}
body{background:var(--bg);font-family:var(--f-bod);font-size:15px;color:var(--txt);}
img,svg{max-width:100%;}
.main,.card,.tabela-wrap{min-width:0;}

.sidebar{position:fixed;top:0;left:0;bottom:0;width:230px;background:var(--p-dark);color:var(--on-p-dark);display:flex;flex-direction:column;padding:32px 18px;z-index:100;}
.sb-logo{font-family:var(--f-tit);font-size:1.2rem;font-weight:400;margin-bottom:3px;line-height:1.3;}
.sb-sub{font-size:11px;opacity:.4;margin-bottom:12px;letter-spacing:.05em;text-transform:uppercase;}
.sb-link{display:block;padding:10px 13px;border-radius:6px;color:color-mix(in srgb, var(--on-p-dark) 70%, transparent);text-decoration:none;font-size:14px;font-weight:500;transition:background .15s,color .15s;margin-bottom:2px;}
.sb-link:hover,.sb-link.ativo{background:color-mix(in srgb, var(--on-p-dark) 14%, transparent);color:var(--on-p-dark);}
.sb-divider{height:1px;background:color-mix(in srgb, var(--on-p-dark) 12%, transparent);margin:12px 0;}
.sb-pub{display:block;padding:10px 13px;background:color-mix(in srgb, var(--on-p-dark) 10%, transparent);border-radius:6px;color:color-mix(in srgb, var(--on-p-dark) 74%, transparent);text-decoration:none;font-size:12px;margin-bottom:18px;word-break:break-all;transition:background .15s;line-height:1.5;}
.sb-pub:hover{background:color-mix(in srgb, var(--on-p-dark) 18%, transparent);}
.sb-pub strong{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.05em;opacity:.55;margin-bottom:2px;}
.sb-footer{margin-top:auto;font-size:11px;opacity:.28;}

.main{margin-left:230px;padding:36px 36px 60px;min-height:100vh;}
.page-titulo{font-family:var(--f-tit);font-size:1.75rem;font-weight:300;margin-bottom:28px;letter-spacing:-.01em;}

.card{background:var(--white);border-radius:var(--r);border:1px solid var(--bord);padding:28px;margin-bottom:22px;}
.card-titulo{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--txt2);margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--bord);}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.form-group{margin-bottom:18px;}
label.lbl{display:block;font-size:12px;letter-spacing:.05em;text-transform:uppercase;color:var(--txt2);margin-bottom:6px;font-weight:500;}
input[type=text],input[type=email],input[type=tel],input[type=time],input[type=number],select,textarea{width:100%;border:1.5px solid var(--bord);border-radius:6px;padding:11px 14px;font-family:var(--f-bod);font-size:15px;color:var(--txt);background:var(--surface-2);outline:none;transition:border-color .18s,background .18s;}
input:focus,select:focus,textarea:focus{border-color:var(--p);background:var(--surface);}
textarea{resize:vertical;min-height:80px;}
input[type=color]{width:48px;height:40px;border:1.5px solid var(--bord);border-radius:6px;padding:3px;cursor:pointer;vertical-align:middle;}

.btn{padding:11px 26px;background:var(--p);color:var(--on-p);border:none;border-radius:6px;font-family:var(--f-bod);font-size:14px;font-weight:600;cursor:pointer;transition:opacity .15s;}
.btn:hover{opacity:.87;}
.btn-outline{background:none;border:1.5px solid var(--bord);color:var(--txt2);}
.btn-outline:hover{border-color:var(--p);color:var(--txt);opacity:1;}
.btn-danger{background:none;border:1.5px solid #fecaca;color:#b91c1c;}
.btn-danger:hover{background:#fef2f2;opacity:1;}

.msg{padding:12px 16px;border-radius:6px;font-size:14px;margin-bottom:22px;}
.msg.ok{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}
.msg.err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}

.tabela-wrap{background:var(--white);border-radius:var(--r);border:1px solid var(--bord);overflow-x:auto;overflow-y:hidden;margin-bottom:22px;-webkit-overflow-scrolling:touch;}
.tabela-wrap table{min-width:680px;}
.tabela-header{padding:16px 22px;border-bottom:1px solid var(--bord);font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--txt2);}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:11px 22px;font-size:11px;letter-spacing:.05em;text-transform:uppercase;color:var(--txt2);border-bottom:1px solid var(--bord);}
td{padding:13px 22px;font-size:14px;border-bottom:1px solid var(--bord);}
tr:last-child td{border-bottom:none;}

.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:26px;}
.stats-row.stats-quatro{grid-template-columns:repeat(4,1fr);}
.stat-card{background:var(--white);border-radius:var(--r);border:1px solid var(--bord);padding:22px 18px;}
.stat-num{font-family:var(--f-tit);font-size:2.5rem;font-weight:400;color:var(--p);line-height:1;}
.stat-label{font-size:12px;color:var(--txt2);margin-top:6px;text-transform:uppercase;letter-spacing:.05em;}

.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;}
.badge-ok{background:#f0fdf4;color:#166534;}
.badge-pen{background:#fefce8;color:#854d0e;}
.badge-can{background:#fef2f2;color:#991b1b;}
.badge-done{background:#f3f4f6;color:#4b5563;}
.texto-secundario{font-size:13px;color:var(--txt2);}
.link-detalhes{color:var(--p);font-weight:600;text-decoration:none;white-space:nowrap;}
.link-detalhes:hover{text-decoration:underline;}
.agenda-filtros{display:grid;grid-template-columns:minmax(220px,1fr) 180px 170px auto auto;gap:12px;align-items:end;background:var(--white);border:1px solid var(--bord);border-radius:var(--r);padding:18px;margin-bottom:18px;}
.agenda-filtros .btn{height:44px;display:flex;align-items:center;justify-content:center;text-decoration:none;white-space:nowrap;}
.paginacao{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:4px 0 16px;font-size:14px;color:var(--txt2);}
.paginacao>div{display:flex;gap:8px;}
.paginacao .btn{display:inline-block;text-decoration:none;padding:8px 14px;}
.voltar-link{display:inline-block;margin-bottom:16px;color:var(--txt2);text-decoration:none;font-size:14px;}
.voltar-link:hover{color:var(--txt);}
.detalhe-topo{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:24px;}
.detalhe-topo .page-titulo{margin-bottom:0;}
.detalhe-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px 36px;}
.detalhe-grid span,.detalhe-observacoes span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--txt2);margin-bottom:5px;}
.detalhe-grid strong{font-size:15px;font-weight:600;overflow-wrap:anywhere;}
.detalhe-observacoes{margin-top:24px;padding-top:20px;border-top:1px solid var(--bord);}
.detalhe-observacoes p{line-height:1.6;overflow-wrap:anywhere;}
.detalhe-acoes{display:flex;align-items:center;justify-content:space-between;gap:20px;}
.detalhe-acoes p{font-size:13px;color:var(--txt2);margin-top:4px;}
.detalhe-acoes form{margin:0;}

.upload-area{border:2px dashed var(--p-bord);border-radius:6px;padding:20px;text-align:center;font-size:13px;color:var(--txt2);cursor:pointer;transition:border-color .2s;}
.upload-area:hover{border-color:var(--p);}
.cor-row{display:flex;align-items:center;gap:10px;}
.cor-hex{flex:1;}

.dias-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;}
.dia-label{display:flex;flex-direction:column;align-items:center;gap:5px;cursor:pointer;}
.dia-label input{display:none;}
.dia-box{width:42px;height:42px;border:1.5px solid var(--bord);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;transition:all .15s;user-select:none;}
.dia-label input:checked+.dia-box{background:var(--p);border-color:var(--p);color:var(--on-p);}
.dia-nome{font-size:11px;color:var(--txt2);}

.acoes{display:flex;gap:8px;}
.vazio{padding:40px 22px;text-align:center;color:var(--txt2);font-size:14px;}

/* ── Topbar mobile do painel ── */
.painel-topbar {
  display: none;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  height: 56px;
  background: var(--p-dark);
  position: sticky;
  top: 0;
  z-index: 50;
  flex-shrink: 0;
}
.painel-topbar-nome {
  font-size: 15px;
  font-weight: 600;
  color: var(--on-p-dark);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.painel-ham {
  width: 38px; height: 38px;
  background: color-mix(in srgb, var(--on-p-dark) 16%, transparent);
  border: none; border-radius: 8px;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 5px; cursor: pointer; flex-shrink: 0;
}
.painel-ham span {
  display: block; width: 18px; height: 2px;
  background: var(--on-p-dark); border-radius: 2px;
}

/* ── Drawer lateral mobile do painel ── */
.painel-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 100;
}
.painel-overlay.aberto { display: block; }
.painel-drawer {
  position: fixed;
  top: 0; left: -260px; bottom: 0;
  width: 260px;
  background: var(--p-dark);
  z-index: 101;
  display: flex; flex-direction: column;
  padding: 28px 18px;
  transition: left .28s cubic-bezier(.4,0,.2,1);
  overflow-y: auto;
}
.painel-drawer.aberto { left: 0; }
.painel-drawer-close {
  position: absolute; top: 14px; right: 14px;
  width: 30px; height: 30px;
  background: color-mix(in srgb, var(--on-p-dark) 16%, transparent); border: none; border-radius: 7px;
  color: var(--on-p-dark); font-size: 16px; line-height: 30px; text-align: center;
  cursor: pointer;
}
.painel-drawer .sb-logo { margin-bottom: 4px; }
.painel-drawer .sb-sub  { margin-bottom: 20px; }
.painel-drawer .sb-link,
.painel-drawer .sb-pub  { display: block; }

@media(max-width:1100px){
  .agenda-filtros{grid-template-columns:1fr 1fr;}
  .filtro-busca{grid-column:1/-1;}
  .stats-row.stats-quatro{grid-template-columns:1fr 1fr;}
}
@media(max-width:900px){
  .sidebar { display: none; }
  .painel-topbar { display: flex; width:100%; }
  .painel-topbar-nome { min-width:0; flex:1; padding-right:12px; }
  .painel-drawer { width:min(260px,86vw); left:min(-260px,-86vw); }
  .main { margin-left: 0; padding: 20px; width:100%; max-width:100%; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .form-row  { grid-template-columns: 1fr; }
  .dias-grid { grid-template-columns: repeat(4,minmax(0,1fr)); }
  .acoes { flex-wrap:wrap; }
}
@media(max-width:520px){
  .main { padding:16px 12px 36px; }
  .painel-topbar { padding:0 12px; }
  .page-titulo { font-size:1.5rem; margin-bottom:20px; }
  .card { padding:20px 16px; }
  .stats-row { grid-template-columns:1fr; gap:10px; }
  .dias-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
  .btn { max-width:100%; }
}
@media (max-width:600px) {
  .agenda-filtros{grid-template-columns:1fr;}
  .filtro-busca{grid-column:auto;}
  .agenda-filtros .btn{width:100%;}
  .detalhe-grid{grid-template-columns:1fr;}
  .detalhe-topo,.detalhe-acoes,.paginacao{align-items:flex-start;flex-direction:column;}
  .detalhe-acoes .acoes{width:100%;flex-direction:column;}
  .detalhe-acoes form,.detalhe-acoes .btn{width:100%;}
}

.conversa-lista{display:flex;flex-direction:column;gap:9px;max-height:420px;overflow-y:auto;padding:2px 2px 14px}.conversa-vazia{text-align:center;padding:18px 0}.conversa-msg{max-width:78%;padding:10px 12px;border:1px solid var(--bord);border-radius:10px;background:var(--surface-2);font-size:14px;line-height:1.5;overflow-wrap:anywhere}.conversa-msg.propria{align-self:flex-end;background:var(--p);border-color:var(--p);color:var(--on-p)}.conversa-msg.cliente{align-self:flex-start}.conversa-msg small{display:block;margin-top:5px;font-size:10px;opacity:.7}.conversa-form{display:flex;align-items:flex-end;gap:10px;padding-top:14px;border-top:1px solid var(--bord)}.conversa-form textarea{min-height:70px}.conversa-form .btn{flex:0 0 auto;margin-bottom:0}@media(max-width:600px){.conversa-msg{max-width:90%}.conversa-form{flex-direction:column;align-items:stretch}.conversa-form .btn{width:100%}}
</style>";
}

function painel_sidebar(string $ativa): void {
    global $tema;
    $nome = htmlspecialchars($_SESSION['salao_nome'] ?? '');
    $slug = htmlspecialchars($_SESSION['salao_slug'] ?? '');
    $url  = BASE_URL . '/index.php?salao=' . $slug;

    $links = [
        'agendamentos' => ['index.php',     'Agendamentos'],
        'aparencia'    => ['aparencia.php',  'Aparência'],
        'servicos'     => ['servicos.php',   'Serviços'],
        'horarios'     => ['horarios.php',   'Horários'],
    ];
    $html  = "<aside class=\"sidebar\">";
    $html .= "<div class=\"sb-logo\">{$nome}</div>";
    $html .= "<div class=\"sb-sub\">painel de gestão</div>";
    $html .= "<a href=\"{$url}\" target=\"_blank\" class=\"sb-pub\"><strong>link público</strong>?salao={$slug}</a>";
    foreach ($links as $key => [$href, $label]) {
        $cls = ($ativa === $key) ? ' ativo' : '';
        $html .= "<a href=\"{$href}\" class=\"sb-link{$cls}\">{$label}</a>";
    }
    $html .= "<div class=\"sb-divider\"></div>";
    $html .= "<a href=\"sair.php\" class=\"sb-link\" style=\"opacity:.4;\">Sair</a>";
    $html .= "<div class=\"sb-footer\">Essenza — Alpha 1.9</div>";
    $html .= "</aside>";

    // ── Topbar mobile (visível só em telas ≤ 900px) ───────────
    $topbar  = "<div class=\"painel-topbar\">";
    $topbar .= "<div class=\"painel-topbar-nome\">{$nome}</div>";
    $topbar .= "<button class=\"painel-ham\" onclick=\"painelAbrirDrawer()\" aria-label=\"Menu\">";
    $topbar .= "<span></span><span></span><span></span>";
    $topbar .= "</button>";
    $topbar .= "</div>";

    // ── Overlay e drawer lateral mobile ───────────────────────
    $drawer  = "<div class=\"painel-overlay\" id=\"painel-overlay\" onclick=\"painelFecharDrawer()\"></div>";
    $drawer .= "<div class=\"painel-drawer\" id=\"painel-drawer\">";
    $drawer .= "<button class=\"painel-drawer-close\" onclick=\"painelFecharDrawer()\">&#x2715;</button>";
    $drawer .= "<div class=\"sb-logo\">{$nome}</div>";
    $drawer .= "<div class=\"sb-sub\">painel de gestão</div>";
    $drawer .= "<a href=\"{$url}\" target=\"_blank\" class=\"sb-pub\"><strong style=\"display:block;font-size:10px;text-transform:uppercase;letter-spacing:.05em;opacity:.5;margin-bottom:2px\">link público</strong>?salao={$slug}</a>";
    foreach ($links as $key => [$href, $label]) {
        $cls = ($ativa === $key) ? ' ativo' : '';
        $drawer .= "<a href=\"{$href}\" class=\"sb-link{$cls}\">{$label}</a>";
    }
    $drawer .= "<div style=\"height:1px;background:color-mix(in srgb, var(--on-p-dark) 12%, transparent);margin:12px 0\"></div>";
    $drawer .= "<a href=\"sair.php\" class=\"sb-link\" style=\"opacity:.4;\">Sair</a>";
    $drawer .= "</div>";

    // ── JS do hambúrguer (inline, sem dependências externas) ──
    $js  = "<script>";
    $js .= "function painelAbrirDrawer(){";
    $js .=   "document.getElementById('painel-drawer').classList.add('aberto');";
    $js .=   "document.getElementById('painel-overlay').classList.add('aberto');";
    $js .=   "document.body.style.overflow='hidden';";
    $js .= "}";
    $js .= "function painelFecharDrawer(){";
    $js .=   "document.getElementById('painel-drawer').classList.remove('aberto');";
    $js .=   "document.getElementById('painel-overlay').classList.remove('aberto');";
    $js .=   "document.body.style.overflow='';";
    $js .= "}";
    $js .= "</script>";

    echo $topbar . $drawer . $js . $html;
}

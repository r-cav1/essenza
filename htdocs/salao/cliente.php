<?php
/**
 * cliente.php — Página pública do salão (interface do cliente)
 *
 * Esta é a página que o cliente acessa via link compartilhado pelo dono.
 * URL: http://localhost/salao/?salao=studio-demo
 *
 * ── FLUXO DE AGENDAMENTO ──────────────────────────────────────────────
 *
 *  [Onboarding]   → 3 slides de boas-vindas (se ativado no painel)
 *      ↓
 *  [Home]         → Grade de serviços + botão "Agendar agora"
 *      ↓
 *  [Serviço]      → Lista completa de serviços com preço e duração
 *      ↓
 *  [Data]         → Calendário mensal com dias disponíveis
 *      ↓
 *  [Horário]      → Grid de horários livres (carregado via AJAX)
 *      ↓
 *  [Dados]        → Formulário: nome, e-mail e telefone opcional
 *      ↓
 *  [Verificação]  → Código de 6 dígitos (exibido na tela em modo dev,
 *                   ou enviado por e-mail quando o SMTP está ativo)
 *      ↓
 *  [Confirmação]  → Resumo do agendamento confirmado
 *
 * ── LAYOUT ────────────────────────────────────────────────────────────
 *
 *  Desktop (> 768px):
 *    ┌─────────────────┬───────────────────────────────────┐
 *    │  Sidebar verde  │  Área de steps (fluxo de agend.)  │
 *    │  320px fixo     │  flex: 1                          │
 *    └─────────────────┴───────────────────────────────────┘
 *
 *  Mobile (≤ 768px):
 *    ┌────────────────────────────────────────────────────┐
 *    │  Topbar verde (nome + botão hambúrguer)            │
 *    ├────────────────────────────────────────────────────┤
 *    │  Área de steps (tela cheia)                        │
 *    └────────────────────────────────────────────────────┘
 *    + Drawer deslizante da esquerda com as infos do salão
 *
 * ── TEMA DINÂMICO ─────────────────────────────────────────────────────
 *
 *  Todas as cores e fontes vêm do banco (tabela saloes).
 *  São injetadas como CSS variables no :root do HTML:
 *    --p      → cor primária (header, botões, seleções)
 *    --bg     → cor de fundo (= cor_secundaria do banco)
 *    --txt    → cor do texto
 *    --f-tit  → fonte dos títulos
 *    --f-bod  → fonte do corpo
 *
 * ── COMPATIBILIDADE ───────────────────────────────────────────────────
 *
 *  PHP 7.4+ (XAMPP padrão) — sem funções exclusivas do PHP 8
 *  Não usa frameworks — HTML/CSS/JS puro
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cores.php';

$slug = isset($_GET['salao']) ? limparEntrada($_GET['salao']) : '';
if (empty($slug)) { http_response_code(400); die('Informe o salão: ?salao=nome-do-salao'); }

$pdo   = conectar();
$stmt  = $pdo->prepare("SELECT * FROM saloes WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$salao = $stmt->fetch();
if (!$salao) { http_response_code(404); die('Salão não encontrado.'); }

$stmtSrv = $pdo->prepare("SELECT * FROM servicos WHERE salao_id = ? AND ativo = 1 ORDER BY ordem, nome");
$stmtSrv->execute([$salao['id']]);
$servicos = $stmtSrv->fetchAll();

$diasNomes  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$diasAtivos = explode(',', $salao['dias_funcionamento']);

// Ícones carregados do arquivo centralizado
require_once __DIR__ . '/includes/icones.php';
$nomeExibido = sanitizar($salao['nome_salao']);
$inicialNome = mb_strtoupper(mb_substr($salao['nome_salao'], 0, 1));

$diasMap = ['1,2,3,4,5'=>'Segunda a Sexta','1,2,3,4,5,6'=>'Segunda a Sábado','0,1,2,3,4,5,6'=>'Todos os dias','2,3,4,5'=>'Terça a Sexta'];
$diasLabel = $diasMap[$salao['dias_funcionamento']] ?? implode(', ', array_map(fn($d)=>$diasNomes[(int)$d], $diasAtivos));
$abHora = date('H:i', strtotime($salao['horario_abertura']));
$feHora = date('H:i', strtotime($salao['horario_fechamento']));

// Superfícies derivadas do tema para manter contraste em temas claros e escuros.
$paletaCliente = paletaTema($salao['cor_primaria'], $salao['cor_secundaria'], $salao['cor_texto']);
$surface = $paletaCliente['superficie'];
$surface2 = $paletaCliente['superficie_2'];
$borderTema = $paletaCliente['borda'];
$txt2Tema = $paletaCliente['texto_secundario'];
$onPrimary = $paletaCliente['sobre_primaria'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $nomeExibido ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=<?= urlencode($salao['fonte_titulo']) ?>:ital,wght@0,300;0,400;0,600;1,300&family=<?= urlencode($salao['fonte_corpo']) ?>:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ═══════ TOKENS ═══════ */
:root {
  --p:      <?= $salao['cor_primaria'] ?>;
  --p-dark: color-mix(in srgb, <?= $salao['cor_primaria'] ?> 78%, #000);
  --p-soft: color-mix(in srgb, <?= $salao['cor_primaria'] ?> 12%, #fff);
  --p-mid:  color-mix(in srgb, <?= $salao['cor_primaria'] ?> 25%, #fff);
  --p-txt:  color-mix(in srgb, <?= $salao['cor_primaria'] ?> 55%, #000);
  --txt:    <?= $salao['cor_texto'] ?>;
  --txt2:   <?= $txt2Tema ?>;
  --bg:     <?= $salao['cor_secundaria'] ?>;
  --white:  <?= $surface ?>;
  --surface-2: <?= $surface2 ?>;
  --bord:   <?= $borderTema ?>;
  --on-p:   <?= $onPrimary ?>;
  --r:      14px;
  --f-tit:  '<?= addslashes($salao['fonte_titulo']) ?>', Georgia, serif;
  --f-bod:  '<?= addslashes($salao['fonte_corpo']) ?>', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; width: 100%; max-width: 100%; }
html { overflow-x: hidden; }
body { font-family: var(--f-bod); color: var(--txt); background: var(--bg); overflow-x: hidden; }
img, svg { max-width: 100%; }
button, input, select, textarea { max-width: 100%; }
.app-wrap, .app-main, .steps-wrap, .step { min-width: 0; }

/* ═══════ LAYOUT DESKTOP — duas colunas ═══════ */
.app-wrap {
  display: grid;
  grid-template-columns: 340px 1fr;
  min-height: 100vh;
}

/* Coluna esquerda — hero fixo */
.app-hero {
  background: var(--p);
  position: sticky;
  top: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  padding: 48px 36px;
  overflow: hidden;
}
.hero-banner-img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 20px;
  opacity: .85;
  flex-shrink: 0;
}
.hero-logo {
  width: 56px; height: 56px;
  border-radius: 16px;
  background: rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--f-tit);
  font-size: 24px; font-weight: 600; color: var(--on-p);
  margin-bottom: 32px;
  overflow: hidden;
  flex-shrink: 0;
}
.hero-logo img { width: 100%; height: 100%; object-fit: cover; }
.hero-titulo {
  font-family: var(--f-tit);
  font-size: clamp(1.8rem, 3vw, 2.6rem);
  font-weight: 300;
  color: var(--on-p);
  line-height: 1.2;
  letter-spacing: -.02em;
}
.hero-desc {
  font-size: 15px;
  color: rgba(255,255,255,.7);
  margin-top: 14px;
  line-height: 1.65;
}
.hero-horario {
  margin-top: 32px;
  padding: 18px;
  background: rgba(255,255,255,.12);
  border-radius: 12px;
}
.hero-hor-tit { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.6); margin-bottom: 10px; }
.hero-hor-linha { font-size: 13px; color: rgba(255,255,255,.82); padding: 3px 0; display: flex; justify-content: space-between; gap: 8px; }
.hero-hor-linha span { color: rgba(255,255,255,.55); }
.hero-hor-linha.contato { display:grid; grid-template-columns:70px minmax(0,1fr); align-items:start; column-gap:10px; }
.hero-hor-linha.contato .info-label { color:rgba(255,255,255,.82); white-space:nowrap; }
.hero-hor-linha.contato .info-value { color:rgba(255,255,255,.55); text-align:right; overflow-wrap:anywhere; word-break:normal; min-width:0; }
.hero-bottom {
  margin-top: auto;
  font-size: 12px;
  color: rgba(255,255,255,.3);
}
.hero-deco {
  position: absolute;
  bottom: -60px; right: -60px;
  width: 260px; height: 260px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
  pointer-events: none;
}
.hero-deco2 {
  position: absolute;
  bottom: 80px; right: 80px;
  width: 120px; height: 120px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
  pointer-events: none;
}

/* Coluna direita — conteúdo / steps */
.app-main {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Progresso no topo */
.progress-bar {
  height: 3px;
  background: var(--bord);
  flex-shrink: 0;
}
.progress-fill {
  height: 100%;
  background: var(--p);
  transition: width .4s cubic-bezier(.4,0,.2,1);
}

/* Steps wrapper */
.steps-wrap {
  flex: 1;
  position: relative;
  overflow: hidden;
}
.step {
  position: absolute; inset: 0;
  display: flex; flex-direction: column;
  transition: opacity .28s ease, transform .28s ease;
  overflow-y: auto;
}
.step::-webkit-scrollbar { width: 4px; }
.step::-webkit-scrollbar-thumb { background: var(--bord); border-radius: 4px; }
.step.hidden     { opacity: 0; pointer-events: none; transform: translateX(40px); }
.step.slide-back { opacity: 0; pointer-events: none; transform: translateX(-40px); transition: none; }

/* ─── Onboarding ─── */
#s-ob { background: var(--p); overflow: hidden; }
#ob-slides {
  flex: 1 1 auto;
  min-height: 0;
  width: 100%;
  position: relative;
  overflow: hidden;
}
.ob-slide {
  position: absolute;
  inset: 0;
  width: 100%;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 58px 40px 24px;
  gap: 18px;
  overflow-y: auto;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transform: translateX(24px);
  transition: opacity .26s ease, transform .26s ease, visibility 0s linear .26s;
}
.ob-slide.ativo {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
  transform: translateX(0);
  transition-delay: 0s;
}
.ob-icon {
  width: 120px; height: 120px;
  background: rgba(255,255,255,.16);
  border-radius: 32px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.ob-titulo {
  font-family: var(--f-tit);
  font-size: clamp(1.6rem, 4vw, 2.2rem);
  font-weight: 400;
  color: var(--on-p);
  letter-spacing: -.02em;
  line-height: 1.2;
}
.ob-texto {
  font-size: 15px;
  color: rgba(255,255,255,.72);
  line-height: 1.65;
  max-width: 300px;
}
.ob-skip-btn {
  position: absolute; top: 18px; right: 18px; z-index: 10;
  background: rgba(255,255,255,.15); border: none; border-radius: 20px;
  padding: 6px 14px;
  font-family: var(--f-bod); font-size: 13px; font-weight: 600;
  color: rgba(255,255,255,.8); cursor: pointer;
}
.ob-footer {
  padding: 20px 28px 32px;
  display: flex; align-items: center; gap: 12px;
  flex-shrink: 0;
}
.ob-dots {
  flex: 1; display: flex; gap: 6px; justify-content: center;
}
.ob-dot {
  width: 8px; height: 8px; border-radius: 4px;
  background: rgba(255,255,255,.3);
  transition: width .3s, background .3s;
}
.ob-dot.ativo { width: 26px; background: var(--white); }
.ob-btn-prim {
  display: flex; align-items: center; gap: 6px;
  padding: 13px 22px;
  background: var(--white); color: var(--p-txt);
  border: none; border-radius: 24px;
  font-family: var(--f-bod); font-size: 15px; font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(0,0,0,.15);
  transition: transform .1s;
}
.ob-btn-prim:active { transform: scale(.95); }
.ob-btn-sec {
  background: none; border: none;
  font-family: var(--f-bod); font-size: 15px; font-weight: 600;
  color: rgba(255,255,255,.7); cursor: pointer;
  padding: 13px 8px;
  display: flex; align-items: center; gap: 4px;
  visibility: hidden; /* ocultado até chegar no slide 2 */
}

/* Step header */
.step-hdr {
  padding: 32px 36px 0;
  flex-shrink: 0;
}
.step-hdr-row { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
.btn-back {
  width: 36px; height: 36px;
  background: var(--bg); border: none; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0; transition: background .15s;
}
.btn-back:hover { background: var(--bord); }
.btn-back svg { width: 20px; height: 20px; stroke: var(--txt2); }
.step-titulo { font-family: var(--f-tit); font-size: 1.7rem; font-weight: 400; letter-spacing: -.02em; }
.step-sub    { font-size: 14px; color: var(--txt2); margin-top: 4px; }

/* Step body */
.step-body { padding: 24px 36px 0; flex: 1; }

/* Step footer */
.step-footer {
  padding: 20px 36px 32px;
  display: flex; gap: 10px;
  flex-shrink: 0;
  border-top: 1px solid var(--bord);
  background: var(--white);
  margin-top: 24px;
}
.btn-p {
  flex: 1; padding: 14px 24px;
  background: var(--p); color: var(--on-p); border: none; border-radius: 12px;
  font-family: var(--f-bod); font-size: 15px; font-weight: 700;
  cursor: pointer; transition: opacity .12s, transform .1s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-p:hover { opacity: .88; }
.btn-p:active { transform: scale(.97); }
.btn-p:disabled { opacity: .35; cursor: default; transform: none; }
.btn-s {
  padding: 14px 20px; background: var(--bg); border: none; border-radius: 12px;
  font-family: var(--f-bod); font-size: 15px; font-weight: 600; color: var(--txt2);
  cursor: pointer; transition: background .12s;
}
.btn-s:hover { background: var(--bord); }

/* ═══════ HOME (step 0) ═══════ */
.home-body { padding: 32px 36px; }
.home-head { display:flex; align-items:flex-start; justify-content:space-between; gap:24px; margin-bottom:28px; }
.home-head-text { min-width:0; }
.home-greeting { font-family: var(--f-tit); font-size: 2rem; font-weight: 300; letter-spacing: -.02em; margin-bottom: 4px; }
.home-sub-txt   { font-size: 15px; color: var(--txt2); }
.btn-meus-home { display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; padding:10px 14px; border:1px solid var(--bord); border-radius:10px; color:var(--txt); text-decoration:none; font-size:13px; font-weight:700; background:transparent; transition:border-color .15s, background .15s; }
.btn-meus-home:hover { border-color:var(--p); background:var(--surface); }
.btn-agendar-home {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%; padding: 17px 24px;
  background: var(--p); color: var(--on-p); border: none; border-radius: 14px;
  font-family: var(--f-bod); font-size: 16px; font-weight: 700;
  cursor: pointer; transition: opacity .15s, transform .12s;
  margin-bottom: 36px;
}
.btn-agendar-home:hover { opacity: .88; }
.btn-agendar-home:active { transform: scale(.98); }
.btn-agendar-home svg { width: 22px; height: 22px; stroke: #fff; }

.sec-tit { font-size: 15px; font-weight: 700; letter-spacing: -.1px; margin-bottom: 14px; }
.srv-grid-home { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 32px; }
.srv-card-home {
  background: var(--white); border: 1.5px solid var(--bord); border-radius: var(--r);
  padding: 20px 16px; display: flex; flex-direction: column; align-items: center; gap: 10px;
  cursor: pointer; transition: border-color .18s, transform .15s, box-shadow .18s;
  text-align: center;
}
.srv-card-home:hover { border-color: var(--p); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.07); }
.srv-ico-wrap { width: 52px; height: 52px; background: var(--p-soft); border-radius: 14px; display: flex; align-items: center; justify-content: center; }
.srv-ico-wrap svg { stroke: var(--p); }
.srv-card-home span { font-size: 13px; font-weight: 600; }

/* ═══════ SERVIÇO (step 1) ═══════ */
.srv-lista { display: flex; flex-direction: column; border-radius: var(--r); overflow: hidden; border: 1px solid var(--bord); }
.srv-item {
  background: var(--white); padding: 16px 20px;
  display: flex; align-items: center; gap: 14px;
  cursor: pointer; transition: background .12s; position: relative;
  border-bottom: 1px solid var(--bord);
}
.srv-item:last-child { border-bottom: none; }
.srv-item:hover { background: var(--surface-2); }
.srv-item.sel  { background: var(--p-soft); }
.srv-item.sel::after {
  content: '';
  position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
  width: 22px; height: 22px; border-radius: 50%; background: var(--p);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E");
  background-size: 12px; background-position: center; background-repeat: no-repeat;
}
.srv-ico { width: 46px; height: 46px; background: var(--p-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.srv-ico svg { stroke: var(--p); }
.srv-info { flex: 1; min-width: 0; }
.srv-nome  { font-size: 15px; font-weight: 700; }
.srv-dur   { font-size: 12px; color: var(--txt2); margin-top: 2px; }
.srv-preco { font-size: 15px; font-weight: 800; color: var(--p-txt); white-space: nowrap; padding-right: 32px; }

/* ═══════ CALENDÁRIO ═══════ */
.cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.cal-mes-label { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.cal-mes-label svg { width: 18px; height: 18px; stroke: var(--p); }
.btn-mes {
  width: 38px; height: 38px; background: var(--p-soft); border: none; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s;
}
.btn-mes:hover { background: var(--p-mid); }
.btn-mes svg { width: 18px; height: 18px; stroke: var(--p); }
.cal-wrap { width: 100%; }
.cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 4px; }
.cal-head { text-align: center; font-size: 11px; font-weight: 700; color: var(--txt2); padding: 6px 0 10px; }
.cal-dia  { height: 44px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; border-radius: 10px; cursor: pointer; transition: all .12s; }
.cal-dia:hover:not(.off):not(.past) { background: var(--p-soft); }
.cal-dia.past { color: #ccc; cursor: default; }
.cal-dia.off  { color: #ddd; cursor: default; }
.cal-dia.hoje { border: 2px solid var(--p); color: var(--p-txt); font-weight: 800; }
.cal-dia.sel  { background: var(--p) !important; color: #fff !important; font-weight: 800; border: none !important; border-radius: 12px; }
.cal-info { margin-top: 16px; padding: 12px 16px; background: var(--p-soft); border-radius: 10px; font-size: 14px; color: var(--p-txt); }
.cal-info strong { font-weight: 700; }

/* ═══════ HORÁRIOS ═══════ */
.hor-sec-tit { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; margin-bottom: 6px; }
.hor-sec-tit svg { width: 17px; height: 17px; stroke: var(--p); }
.hor-sub { font-size: 13px; color: var(--txt2); margin-bottom: 18px; }
.hor-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.hor-btn {
  padding: 13px 6px; background: var(--white); border: 1.5px solid var(--bord);
  border-radius: 12px; font-family: var(--f-bod); font-size: 14px; font-weight: 600;
  color: var(--txt); cursor: pointer; transition: all .12s; text-align: center;
}
.hor-btn:hover { border-color: var(--p); color: var(--p-txt); }
.hor-btn.sel { background: var(--p); border-color: var(--p); color: var(--on-p); }
.hor-msg { text-align: center; padding: 40px 20px; color: var(--txt2); font-size: 14px; }

/* ═══════ DADOS ═══════ */
.resumo-box {
  background: var(--p-soft); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px;
}
.resumo-titulo { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--p-txt); margin-bottom: 12px; }
.resumo-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; padding: 4px 0; }
.resumo-k { color: var(--txt2); }
.resumo-v { font-weight: 700; }
.campo { margin-bottom: 16px; }
.campo-lbl { display: block; font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--txt2); margin-bottom: 6px; }
.campo-inp {
  width: 100%; border: 1.5px solid var(--bord); border-radius: 10px;
  padding: 12px 16px; font-family: var(--f-bod); font-size: 15px; color: var(--txt);
  background: var(--surface-2); outline: none; transition: border-color .15s, background .15s;
}
.campo-inp:focus { border-color: var(--p); background: var(--white); }
.alerta-err { padding: 12px 16px; background: #fef2f2; color: #b91c1c; border-radius: 10px; font-size: 13px; margin-bottom: 12px; display: none; }

/* ═══════ VERIFICAÇÃO ═══════ */
.verif-wrap {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 48px 36px; text-align: center; gap: 16px;
}
.verif-ico { width: 80px; height: 80px; background: var(--p-soft); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.verif-ico svg { width: 38px; height: 38px; stroke: var(--p); }
.verif-titulo { font-family: var(--f-tit); font-size: 1.8rem; font-weight: 400; letter-spacing: -.02em; }
.verif-desc   { font-size: 15px; color: var(--txt2); max-width: 360px; line-height: 1.6; }
.cod-boxes { display: flex; gap: 10px; justify-content: center; margin: 8px 0; cursor: text; }
.cod-box {
  width: 52px; height: 64px; border: 2px solid var(--bord); border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; font-weight: 800; background: var(--surface-2); transition: border-color .15s;
}
.cod-box.cur { border-color: var(--p); background: var(--white); }
.cod-box.ok  { border-color: var(--p); background: var(--p-soft); color: var(--p-txt); }
.cod-real { position: absolute; opacity: 0; pointer-events: none; width: 0; height: 0; }
.verif-re { font-size: 13px; color: var(--txt2); }
.verif-re a { color: var(--p-txt); font-weight: 700; cursor: pointer; }
.verif-alerta { padding: 10px 16px; background: #fef2f2; color: #b91c1c; border-radius: 10px; font-size: 13px; width: 100%; max-width: 360px; display: none; }

/* ═══════ CONFIRMAÇÃO ═══════ */
.conf-wrap {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  padding: 48px 36px 32px; text-align: center; gap: 10px; overflow-y: auto;
}
.conf-check {
  width: 96px; height: 96px; border-radius: 50%;
  background: var(--p-soft); border: 3px solid var(--p);
  display: flex; align-items: center; justify-content: center; margin-bottom: 8px;
}
.conf-check svg { width: 48px; height: 48px; stroke: var(--p); stroke-width: 2.5; }
.conf-titulo { font-family: var(--f-tit); font-size: 2rem; font-weight: 400; letter-spacing: -.02em; line-height: 1.2; }
.conf-sub    { font-size: 15px; color: var(--txt2); margin-bottom: 10px; }
.conf-card {
  width: 100%; max-width: 480px; background: var(--white); border-radius: var(--r); padding: 24px;
  text-align: left; border: 1px solid var(--bord); display: flex; flex-direction: column; gap: 18px;
}
.conf-card-tit { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--txt2); }
.conf-row { display: flex; align-items: center; gap: 14px; }
.conf-ico { width: 40px; height: 40px; background: var(--p-soft); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.conf-ico svg { width: 20px; height: 20px; stroke: var(--p); }
.conf-k { font-size: 12px; color: var(--txt2); font-weight: 500; }
.conf-v { font-size: 15px; font-weight: 700; margin-top: 2px; }
.conf-aviso { width: 100%; max-width: 480px; padding: 14px 18px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; font-size: 14px; color: #92400e; line-height: 1.55; text-align: center; }

/* ═══════ RESPONSIVO MOBILE ═══════ */
/* ── Menu hambúrguer mobile ── */
.mob-topbar {
  display: none;
  align-items: center; justify-content: space-between;
  padding: 14px 20px;
  background: var(--p);
  flex-shrink: 0;
}
.mob-topbar-nome { font-family: var(--f-tit); font-size: 1.1rem; font-weight: 400; color: var(--on-p); }
.mob-topbar-logo {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(255,255,255,.22);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--f-tit); font-size: 16px; font-weight: 600; color: var(--on-p);
  overflow: hidden; flex-shrink: 0;
}
.mob-topbar-logo img { width:100%; height:100%; object-fit:cover; }
.mob-ham-btn {
  width: 38px; height: 38px; background: rgba(255,255,255,.18); border: none;
  border-radius: 10px; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 5px;
  cursor: pointer; flex-shrink: 0;
}
.mob-ham-btn span { display:block; width:18px; height:2px; background:var(--white); border-radius:2px; transition:all .2s; }
/* Drawer lateral mobile */
.mob-drawer-overlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 200;
}
.mob-drawer-overlay.open { display: block; }
.mob-drawer {
  position: fixed; top: 0; left: -280px; bottom: 0; width: 280px;
  background: var(--p-dark);
  display: flex; flex-direction: column; padding: 32px 20px;
  transition: left .28s cubic-bezier(.4,0,.2,1);
  z-index: 201; overflow-y: auto;
}
.mob-drawer.open { left: 0; }
.mob-drawer-close {
  position: absolute; top: 16px; right: 16px;
  width: 32px; height: 32px; background: rgba(255,255,255,.15); border: none;
  border-radius: 8px; cursor: pointer; color: var(--on-p); font-size: 18px; line-height: 32px; text-align: center;
}
.mob-drawer .hero-logo  { margin-bottom: 14px; }
.mob-drawer .hero-titulo { font-size: 1.3rem; }
.mob-drawer .hero-desc   { font-size: 13px; }
.mob-drawer .hero-info   { margin-top: 18px; }

/* ── Card de info mobile ── */
.mob-info-card { display:none; flex-direction:column; gap:1px; background:var(--bord); border-radius:var(--r); overflow:hidden; margin-bottom:24px; }
.mob-info-sec { background:var(--white); padding:16px 18px; }
.mob-info-tit { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--txt2); margin-bottom:8px; }
.mob-info-row { display:flex; justify-content:space-between; align-items:center; gap:8px; padding:2px 0; }
.mob-info-k { font-size:13px; color:var(--txt2); }
.mob-info-v { font-size:13px; font-weight:600; color:var(--txt); }

@media (max-width: 768px) {
  html, body { height: 100%; min-height: 100%; }
  body {
    height: 100dvh;
    min-height: 100svh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  .app-wrap {
    grid-template-columns: minmax(0, 1fr);
    width: 100%;
    min-height: 0;
    flex: 1 1 auto;
    overflow: hidden;
  }
  .app-main { width: 100%; min-height: 0; height: 100%; overflow: hidden; }
  .steps-wrap { min-height: 0; width: 100%; }
  .step { width: 100%; max-width: 100%; }
  .app-hero { display: none; }
  .mob-topbar { display: flex; width: 100%; min-width: 0; flex: 0 0 auto; }
  .mob-topbar-nome { min-width: 0; flex: 1; padding: 0 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center; }
  .mob-info-card { display: flex; }
  .mob-drawer { width: min(280px, 86vw); left: min(-280px, -86vw); }
  .step-hdr { padding: 20px 18px 0; }
  .step-body { padding: 16px 18px 0; min-width: 0; }
  .step-footer { padding: 14px 18px 20px; margin-top: 18px; }
  .home-body { padding: 20px 18px; overflow-x: hidden; }
  .home-head { gap:16px; }
  .hor-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .verif-wrap { padding: 28px 18px; }
  .conf-wrap { padding: 28px 18px; }
  .resumo-row, .mob-info-row { min-width: 0; }
  .resumo-v, .mob-info-v { min-width: 0; overflow-wrap: anywhere; text-align: right; }
  .srv-item { min-width: 0; padding-left: 16px; padding-right: 16px; }
  .srv-preco { padding-right: 28px; }
  #s-ob { min-height: 0; }
  .ob-slide { padding: 48px 22px 18px; }
  .ob-footer { padding: 14px 18px 20px; width: 100%; min-width: 0; }
}

@media (max-width: 420px) {
  .mob-topbar { padding: 10px 12px; }
  .mob-topbar-logo { width: 34px; height: 34px; }
  .mob-ham-btn { width: 36px; height: 36px; }
  .step-hdr { padding: 16px 14px 0; }
  .step-body { padding: 14px 14px 0; }
  .step-footer { padding: 12px 14px 16px; gap: 8px; }
  .home-body { padding: 16px 14px; }
  .step-titulo { font-size: 1.45rem; }
  .home-greeting { font-size: 1.7rem; }
  .home-head { flex-direction:column; gap:12px; margin-bottom:20px; }
  .btn-meus-home { width:100%; }
  .btn-p { padding: 13px 14px; }
  .btn-s { padding: 13px 12px; }
  .srv-grid-home { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .srv-card-home { padding: 16px 10px; }
  .srv-ico { width: 42px; height: 42px; }
  .srv-preco { font-size: 14px; padding-right: 26px; }
  .cal-grid { gap: 2px; }
  .cal-dia { height: 40px; font-size: 13px; }
  .cal-mes-label { font-size: 14px; }
  .hor-grid { gap: 7px; }
  .hor-btn { padding: 12px 4px; font-size: 13px; }
  .ob-footer { display: grid; grid-template-columns: 78px minmax(0,1fr) 96px; gap: 6px; padding-left: 12px; padding-right: 12px; }
  .ob-btn-sec { padding: 11px 2px; font-size: 13px; gap: 2px; }
  .ob-btn-prim { padding: 11px 12px; font-size: 13px; justify-content: center; }
  .ob-dots { gap: 4px; }
  .ob-dot { width: 7px; height: 7px; }
  .ob-dot.ativo { width: 20px; }
  .ob-icon { width: 96px; height: 96px; border-radius: 26px; }
  .ob-icon svg { width: 52px; height: 52px; }
  .ob-titulo { font-size: 1.55rem; }
  .ob-texto { font-size: 14px; }
}

@media (max-height: 650px) and (max-width: 768px) {
  .ob-slide { justify-content: flex-start; padding-top: 54px; gap: 12px; }
  .ob-icon { width: 82px; height: 82px; border-radius: 22px; }
  .ob-icon svg { width: 44px; height: 44px; }
  .ob-titulo { font-size: 1.4rem; }
  .ob-texto { font-size: 13px; line-height: 1.5; }
  .ob-footer { padding-top: 10px; padding-bottom: 12px; }
}
</style>
</head>
<body>
<!-- ── Topbar mobile ── -->
<div class="mob-topbar">
  <div class="mob-topbar-logo">
    <?php if (!empty($salao['logo_url'])): ?>
      <img src="<?= sanitizar($salao['logo_url']) ?>" alt="Logo">
    <?php else: echo $inicialNome; endif; ?>
  </div>
  <div class="mob-topbar-nome"><?= $nomeExibido ?></div>
  <button class="mob-ham-btn" onclick="abrirDrawer()" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</div>

<!-- ── Drawer lateral mobile ── -->
<div class="mob-drawer-overlay" id="drawer-overlay" onclick="fecharDrawer()"></div>
<div class="mob-drawer" id="mob-drawer">
  <button class="mob-drawer-close" onclick="fecharDrawer()">&#x2715;</button>
  <div class="hero-deco"></div><div class="hero-deco2"></div>
  <div class="hero-logo" style="margin-bottom:16px">
    <?php if (!empty($salao['logo_url'])): ?>
      <img src="<?= sanitizar($salao['logo_url']) ?>" alt="Logo">
    <?php else: echo $inicialNome; endif; ?>
  </div>
  <div class="hero-titulo" style="font-size:1.4rem"><?= $nomeExibido ?></div>
  <?php if (!empty($salao['descricao'])): ?>
    <div class="hero-desc" style="font-size:13px"><?= sanitizar($salao['descricao']) ?></div>
  <?php endif; ?>
  <div class="hero-info" style="margin-top:20px">
    <div class="hero-hor-tit">Funcionamento</div>
    <div class="hero-hor-linha"><?= $diasLabel ?> <span><?= $abHora ?>–<?= $feHora ?></span></div>
    <?php if (!in_array(0,$diasAtivos)&&!in_array(6,$diasAtivos)): ?>
      <div class="hero-hor-linha">Sáb e Dom <span>Fechado</span></div>
    <?php elseif (!in_array(0,$diasAtivos)): ?>
      <div class="hero-hor-linha">Domingo <span>Fechado</span></div>
    <?php endif; ?>
  </div>
  <?php if (!empty($salao['whatsapp']) || !empty($salao['email_contato'])): ?>
  <div class="hero-info" style="margin-top:12px">
    <div class="hero-hor-tit">Contato</div>
    <?php if (!empty($salao['whatsapp'])): ?><div class="hero-hor-linha contato"><span class="info-label">WhatsApp</span><span class="info-value">+<?= sanitizar($salao['whatsapp']) ?></span></div><?php endif; ?>
    <?php if (!empty($salao['email_contato'])): ?><div class="hero-hor-linha contato"><span class="info-label">E-mail</span><span class="info-value" style="font-size:11px"><?= sanitizar($salao['email_contato']) ?></span></div><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($salao['endereco'])): ?>
  <div class="hero-info" style="margin-top:12px">
    <div class="hero-hor-tit">Endereço</div>
    <div class="hero-hor-linha" style="flex-direction:column;align-items:flex-start;gap:2px;color:rgba(255,255,255,.8)"><?= sanitizar($salao['endereco']) ?></div>
  </div>
  <?php endif; ?>
</div>

<div class="app-wrap">

<!-- ═══ HERO LATERAL (desktop) ═══ -->
<aside class="app-hero">
  <div class="hero-deco"></div>
  <div class="hero-deco2"></div>
  <div class="hero-logo">
    <?php if (!empty($salao['logo_url'])): ?>
      <img src="<?= sanitizar($salao['logo_url']) ?>" alt="Logo">
    <?php else: echo $inicialNome; endif; ?>
  </div>
  <?php if (!empty($salao['banner_url'])): ?>
  <img src="<?= sanitizar($salao['banner_url']) ?>" alt="Banner" class="hero-banner-img">
  <?php endif; ?>
  <div class="hero-titulo"><?= $nomeExibido ?></div>
  <?php if (!empty($salao['descricao'])): ?>
    <div class="hero-desc"><?= sanitizar($salao['descricao']) ?></div>
  <?php endif; ?>
  <div class="hero-horario">
    <div class="hero-hor-tit">Funcionamento</div>
    <div class="hero-hor-linha"><?= $diasLabel ?> <span><?= $abHora ?> – <?= $feHora ?></span></div>
    <?php if (!in_array(0,$diasAtivos) && !in_array(6,$diasAtivos)): ?>
      <div class="hero-hor-linha">Sáb e Dom <span>Fechado</span></div>
    <?php elseif (!in_array(0,$diasAtivos)): ?>
      <div class="hero-hor-linha">Domingo <span>Fechado</span></div>
    <?php endif; ?>
  </div>
  <?php if (!empty($salao['whatsapp']) || !empty($salao['email_contato'])): ?>
  <div class="hero-horario" style="margin-top:14px">
    <div class="hero-hor-tit">Contato</div>
    <?php if (!empty($salao['whatsapp'])): ?>
    <div class="hero-hor-linha contato"><span class="info-label">WhatsApp</span><span class="info-value">+<?= sanitizar($salao['whatsapp']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($salao['email_contato'])): ?>
    <div class="hero-hor-linha contato"><span class="info-label">E-mail</span><span class="info-value" style="font-size:11px"><?= sanitizar($salao['email_contato']) ?></span></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($salao['endereco'])): ?>
  <div class="hero-horario" style="margin-top:14px">
    <div class="hero-hor-tit">Endereço</div>
    <div class="hero-hor-linha" style="flex-direction:column;align-items:flex-start;gap:2px;color:rgba(255,255,255,.8)"><?= sanitizar($salao['endereco']) ?></div>
  </div>
  <?php endif; ?>
  <div class="hero-bottom"><?= $nomeExibido ?> &mdash; agendamento online</div>
</aside>

<!-- ═══ CONTEÚDO PRINCIPAL ═══ -->
<main class="app-main">
  <!-- Barra de progresso -->
  <div class="progress-bar"><div class="progress-fill" id="prog" style="width:0%"></div></div>

  <div class="steps-wrap">

    <!-- ─── ONBOARDING ─── -->
    <div class="step <?= !empty($salao['show_onboarding']) ? '' : 'hidden' ?>" id="s-ob">
      <!-- Botão pular -->
      <button onclick="irPara('s-home')" class="ob-skip-btn">Pular</button>

      <!-- Slides do onboarding -->
      <div id="ob-slides">
        <!-- Slide 1 -->
        <div class="ob-slide ativo">
          <div class="ob-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="64" height="64"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
          </div>
          <h2 class="ob-titulo"><?= !empty($salao['ob_titulo1']) ? sanitizar($salao['ob_titulo1']) : 'Bem-vindo ao '.$nomeExibido ?></h2>
          <p class="ob-texto"><?= !empty($salao['ob_texto1']) ? sanitizar($salao['ob_texto1']) : (!empty($salao['descricao']) ? sanitizar($salao['descricao']) : 'Agende seus serviços de beleza de forma rápida, fácil e sem complicação.') ?></p>
        </div>
        <!-- Slide 2 -->
        <div class="ob-slide">
          <div class="ob-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="64" height="64"><rect x="3" y="4" width="18" height="18" rx="3"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <h2 class="ob-titulo"><?= !empty($salao['ob_titulo2']) ? sanitizar($salao['ob_titulo2']) : 'Agende com facilidade' ?></h2>
          <p class="ob-texto"><?= !empty($salao['ob_texto2']) ? sanitizar($salao['ob_texto2']) : 'Selecione o melhor dia e horário para você com poucos toques.' ?></p>
        </div>
        <!-- Slide 3 -->
        <div class="ob-slide">
          <div class="ob-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="64" height="64"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 014.11 2h3a2 2 0 012 1.72c.12.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.58 2.81.7A2 2 0 0122 16.92z"/></svg>
          </div>
          <h2 class="ob-titulo"><?= !empty($salao['ob_titulo3']) ? sanitizar($salao['ob_titulo3']) : 'Confirmação instantânea' ?></h2>
          <p class="ob-texto"><?= !empty($salao['ob_texto3']) ? sanitizar($salao['ob_texto3']) : 'Receba a confirmação do agendamento pelo seu e-mail.' ?></p>
        </div>
      </div>

      <!-- Footer: dots + botões anterior/próximo -->
      <div class="ob-footer">
        <button id="ob-btn-ant" class="ob-btn-sec" onclick="obAnterior()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="18" height="18"><polyline points="15 18 9 12 15 6"/></svg>
          Anterior
        </button>
        <div class="ob-dots" id="ob-dots">
          <div class="ob-dot ativo"></div>
          <div class="ob-dot"></div>
          <div class="ob-dot"></div>
        </div>
        <button id="ob-btn-prox" class="ob-btn-prim" onclick="obProximo()">
          Próximo
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="18" height="18"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    <!-- ─── HOME ─── -->
    <div class="step <?= empty($salao['show_onboarding']) ? '' : 'hidden' ?>" id="s-home">
      <div class="home-body">
        <div class="home-head">
          <div class="home-head-text">
        <div class="home-greeting"><?= sanitizar(!empty($salao['home_titulo']) ? $salao['home_titulo'] : 'Bem-vindo') ?></div>
        <div class="home-sub-txt"><?= sanitizar(!empty($salao['home_subtitulo']) ? $salao['home_subtitulo'] : 'Agende seu horário de forma rápida e fácil') ?></div>
          </div>
          <a class="btn-meus-home" href="meus-agendamentos.php?salao=<?= urlencode($slug) ?>">Meus agendamentos</a>
        </div>
        <button class="btn-agendar-home" onclick="irPara('s-servico')">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="3"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          Agendar agora
        </button>
        <div class="sec-tit">Nossos Serviços</div>
        <div class="srv-grid-home">
          <?php foreach (array_slice($servicos, 0, 8) as $s): ?>
          <div class="srv-card-home" onclick="selSrv(<?= $s['id'] ?>,'<?= sanitizar($s['nome']) ?>',<?= $s['duracao_min'] ?>,<?= $s['preco'] ?>)">
            <div class="srv-ico-wrap"><?= getIconeServico($s) ?></div>
            <span><?= sanitizar($s['nome']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Info card mobile (visível só no mobile quando hero some) -->
        <div class="mob-info-card" id="mob-info-card">
          <div class="mob-info-sec">
            <div class="mob-info-tit">Funcionamento</div>
            <div class="mob-info-row"><span class="mob-info-k"><?= $diasLabel ?></span><span class="mob-info-v"><?= $abHora ?>–<?= $feHora ?></span></div>
            <?php if (!in_array(0,$diasAtivos)&&!in_array(6,$diasAtivos)): ?><div class="mob-info-row"><span class="mob-info-k">Sáb e Dom</span><span class="mob-info-v">Fechado</span></div><?php elseif (!in_array(0,$diasAtivos)): ?><div class="mob-info-row"><span class="mob-info-k">Domingo</span><span class="mob-info-v">Fechado</span></div><?php endif; ?>
          </div>
          <?php if (!empty($salao['whatsapp']) || !empty($salao['email_contato'])): ?>
          <div class="mob-info-sec">
            <div class="mob-info-tit">Contato</div>
            <?php if (!empty($salao['whatsapp'])): ?><div class="mob-info-row"><span class="mob-info-k">WhatsApp</span><span class="mob-info-v">+<?= sanitizar($salao['whatsapp']) ?></span></div><?php endif; ?>
            <?php if (!empty($salao['email_contato'])): ?><div class="mob-info-row"><span class="mob-info-k">E-mail</span><span class="mob-info-v"><?= sanitizar($salao['email_contato']) ?></span></div><?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($salao['endereco'])): ?>
          <div class="mob-info-sec">
            <div class="mob-info-tit">Endereço</div>
            <div class="mob-info-row" style="flex-direction:column;align-items:flex-start;gap:2px"><span class="mob-info-v"><?= sanitizar($salao['endereco']) ?></span></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ─── SERVIÇO ─── -->
    <div class="step hidden" id="s-servico">
      <div class="step-hdr">
        <div class="step-hdr-row">
          <button class="btn-back" onclick="irPara('s-home')">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div>
            <div class="step-titulo">Escolha o Serviço</div>
            <div class="step-sub">Selecione o serviço desejado</div>
          </div>
        </div>
      </div>
      <div class="step-body">
        <div class="srv-lista">
          <?php foreach ($servicos as $s): ?>
          <div class="srv-item" id="sri-<?= $s['id'] ?>" onclick="selSrv(<?= $s['id'] ?>,'<?= sanitizar($s['nome']) ?>',<?= $s['duracao_min'] ?>,<?= $s['preco'] ?>)">
            <div class="srv-ico"><?= getIconeServico($s) ?></div>
            <div class="srv-info">
              <div class="srv-nome"><?= sanitizar($s['nome']) ?></div>
              <div class="srv-dur"><?= $s['duracao_min'] ?> min<?php if($s['descricao']): ?> · <?= sanitizar($s['descricao']) ?><?php endif; ?></div>
            </div>
            <div class="srv-preco">R$ <?= number_format($s['preco'],0,',','.') ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="step-footer">
        <button class="btn-s" onclick="irPara('s-home')">Voltar</button>
        <button class="btn-p" id="btn-srv" onclick="irPara('s-data')" disabled>Continuar</button>
      </div>
    </div>

    <!-- ─── DATA ─── -->
    <div class="step hidden" id="s-data">
      <div class="step-hdr">
        <div class="step-hdr-row">
          <button class="btn-back" onclick="irPara('s-servico')">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div>
            <div class="step-titulo">Escolha a Data</div>
            <div class="step-sub">Selecione o dia do agendamento</div>
          </div>
        </div>
      </div>
      <div class="step-body">
        <div class="cal-wrap">
        <div class="cal-nav">
          <button class="btn-mes" onclick="mudaMes(-1)">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div class="cal-mes-label">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="3"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span id="cal-label"></span>
          </div>
          <button class="btn-mes" onclick="mudaMes(1)">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
        <div class="cal-grid" id="cal-grid"></div>
        <div class="cal-info" id="cal-info" style="display:none">Data selecionada: <strong id="cal-txt"></strong></div>
        </div>
      </div>
      <div class="step-footer">
        <button class="btn-s" onclick="irPara('s-servico')">Voltar</button>
        <button class="btn-p" id="btn-data" onclick="irPara('s-horario')" disabled>Continuar</button>
      </div>
    </div>

    <!-- ─── HORÁRIO ─── -->
    <div class="step hidden" id="s-horario">
      <div class="step-hdr">
        <div class="step-hdr-row">
          <button class="btn-back" onclick="irPara('s-data')">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div>
            <div class="step-titulo">Escolha o Horário</div>
            <div class="step-sub">Selecione o melhor horário</div>
          </div>
        </div>
      </div>
      <div class="step-body">
        <div class="hor-sec-tit">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Horários Disponíveis
        </div>
        <div class="hor-sub">Escolha um dos horários disponíveis abaixo</div>
        <div id="hor-wrap"><div class="hor-msg">Carregando...</div></div>
      </div>
      <div class="step-footer">
        <button class="btn-s" onclick="irPara('s-data')">Voltar</button>
        <button class="btn-p" id="btn-hor" onclick="irPara('s-dados')" disabled>Continuar</button>
      </div>
    </div>

    <!-- ─── DADOS ─── -->
    <div class="step hidden" id="s-dados">
      <div class="step-hdr">
        <div class="step-hdr-row">
          <button class="btn-back" onclick="irPara('s-horario')">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div>
            <div class="step-titulo">Seus Dados</div>
            <div class="step-sub">Para confirmar o agendamento</div>
          </div>
        </div>
      </div>
      <div class="step-body">
        <div class="resumo-box">
          <div class="resumo-titulo">Resumo</div>
          <div class="resumo-row"><span class="resumo-k">Serviço</span><span class="resumo-v" id="res-srv">—</span></div>
          <div class="resumo-row"><span class="resumo-k">Data</span><span class="resumo-v" id="res-data">—</span></div>
          <div class="resumo-row"><span class="resumo-k">Horário</span><span class="resumo-v" id="res-hor">—</span></div>
        </div>
        <div class="campo"><label class="campo-lbl">Seu nome</label><input type="text" class="campo-inp" id="inp-nome" placeholder="Nome completo" oninput="valDados()"></div>
        <div class="campo"><label class="campo-lbl">E-mail</label><input type="email" class="campo-inp" id="inp-email" placeholder="seu@email.com" oninput="valDados()"></div>
        <div class="campo"><label class="campo-lbl">WhatsApp / Telefone <span style="font-weight:400;text-transform:none">(opcional)</span></label><input type="tel" class="campo-inp" id="inp-tel" placeholder="(11) 99999-0000" oninput="valDados()"></div>
        <div class="campo"><label class="campo-lbl">Observações <span style="font-weight:400;text-transform:none">(opcional)</span></label><textarea class="campo-inp" id="inp-obs" rows="3" maxlength="500" placeholder="Ex: alergias, preferência de atendimento ou outra informação importante" style="resize:vertical"></textarea></div>
        <div class="alerta-err" id="al-dados"></div>
      </div>
      <div class="step-footer">
        <button class="btn-s" onclick="irPara('s-horario')">Voltar</button>
        <button class="btn-p" id="btn-agendar" onclick="enviar()" disabled>Agendar</button>
      </div>
    </div>

    <!-- ─── VERIFICAÇÃO ─── -->
    <div class="step hidden" id="s-verif">
      <input type="tel" class="cod-real" id="cod-real" maxlength="6" inputmode="numeric" pattern="[0-9]*" oninput="atuBoxes(this.value)" autocomplete="one-time-code">
      <div class="verif-wrap">
        <div class="verif-ico">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 014.11 2h3a2 2 0 012 1.72c.12.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.58 2.81.7A2 2 0 0122 16.92z"/>
          </svg>
        </div>
        <div class="verif-titulo">Confirme o agendamento</div>
        <div class="verif-desc" id="verif-desc">Enviamos um código de 6 dígitos para o seu contato. Insira abaixo para confirmar.</div>
        <div class="cod-boxes" id="cod-boxes" onclick="document.getElementById('cod-real').focus()">
          <div class="cod-box" data-i="0"></div>
          <div class="cod-box" data-i="1"></div>
          <div class="cod-box" data-i="2"></div>
          <div class="cod-box" data-i="3"></div>
          <div class="cod-box" data-i="4"></div>
          <div class="cod-box" data-i="5"></div>
        </div>
        <!-- Caixa de código dev — visível enquanto envio real não está configurado -->
        <div id="verif-dev-box" style="display:none;background:#1a1a2e;color:#a5b4fc;padding:12px 24px;border-radius:12px;text-align:center;font-size:13px;font-weight:600">
          Código de verificação: <span id="verif-code-hint" style="font-size:22px;font-weight:800;letter-spacing:.18em;color:#818cf8;display:block;margin-top:4px"></span>
        </div>
        <div class="verif-alerta" id="al-verif"></div>
        <div class="verif-re">Não recebeu? <a onclick="reenviar()">Reenviar código</a></div>
      </div>
      <div class="step-footer" style="flex-direction:column;gap:8px">
        <button class="btn-p" id="btn-verif" onclick="verificar()" disabled>Confirmar agendamento</button>
        <button class="btn-s" onclick="irPara('s-dados')" style="width:100%;text-align:center">Voltar</button>
      </div>
    </div>

    <!-- ─── CONFIRMAÇÃO ─── -->
    <div class="step hidden" id="s-conf">
      <div class="conf-wrap">
        <div class="conf-check">
          <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="conf-titulo">Agendamento<br>Confirmado!</div>
        <div class="conf-sub">Seu horário foi reservado com sucesso</div>
        <div class="conf-card">
          <div class="conf-card-tit">Detalhes do Agendamento</div>
          <div class="conf-row">
            <div class="conf-ico"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg></div>
            <div><div class="conf-k">Serviço</div><div class="conf-v" id="conf-srv">—</div></div>
          </div>
          <div class="conf-row">
            <div class="conf-ico"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="3"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <div><div class="conf-k">Data</div><div class="conf-v" id="conf-data">—</div></div>
          </div>
          <div class="conf-row">
            <div class="conf-ico"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="conf-k">Horário</div><div class="conf-v" id="conf-hor">—</div></div>
          </div>
        </div>
        <div class="conf-aviso">Seu agendamento está confirmado. Chegue 15 minutos antes do horário agendado.</div>
        <a id="conf-cancelar-link" href="#" style="display:inline-block;margin-top:14px;font-size:13px;color:var(--p);text-decoration:underline">Precisa cancelar? Gerenciar agendamento</a>
      </div>
      <div class="step-footer">
        <button class="btn-p" onclick="irPara('s-home')" style="width:100%">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="18" height="18"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Voltar ao Início
        </button>
      </div>
    </div>

  </div><!-- /steps-wrap -->
</main>
</div><!-- /app-wrap -->

<script>
const SALAO_ID       = <?= (int)$salao['id'] ?>;
const DIAS_ATIVOS    = [<?= implode(',', array_map('intval', $diasAtivos)) ?>];
const SHOW_ONBOARDING = <?= isset($salao['show_onboarding']) && $salao['show_onboarding'] ? 'true' : 'false' ?>;
const MESES       = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const MESES_MIN   = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
const DIAS_SEMANA = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
const DIAS_ABR    = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

// Progresso por step
const STEPS_PROG = { 's-ob':0, 's-home':0, 's-servico':20, 's-data':40, 's-horario':60, 's-dados':80, 's-verif':90, 's-conf':100 };

let telaAtual = SHOW_ONBOARDING ? 's-ob' : 's-home';
let srv = { id:null, nome:'', dur:0, preco:0 };
let dataSel = null, dataTxt = '', horSel = null, agId = null, tokenCliente = null, tokenReenvio = null;
let calAno = new Date().getFullYear(), calMes = new Date().getMonth();

// Info mobile controlada por CSS (@media)
// Inicialização: mostra a tela inicial correta
// O PHP já entrega visível apenas o step inicial correto.
// Inicializa também o estado visual do carrossel quando o onboarding estiver ativo.
if (SHOW_ONBOARDING) {
  setTimeout(atuOb, 0);
}

function irPara(dest) {
  if (dest === 's-horario' && !dataSel) return;
  if (dest === 's-dados'   && !horSel)  return;

  // Esconde tela atual sem setTimeout (evita bug de slide-back persistente)
  const cur = document.getElementById(telaAtual);
  cur.classList.add('hidden');
  cur.classList.remove('slide-back');

  // Mostra próxima tela
  const prox = document.getElementById(dest);
  prox.classList.remove('hidden', 'slide-back');
  telaAtual = dest;

  document.getElementById('prog').style.width = (STEPS_PROG[dest] || 0) + '%';

  if (dest === 's-horario') carregarHorarios();
  if (dest === 's-dados') {
    document.getElementById('res-srv').textContent  = srv.nome;
    document.getElementById('res-data').textContent = dataTxt;
    document.getElementById('res-hor').textContent  = horSel;
  }
  if (dest === 's-data') renderCal();
  if (dest === 's-home') {
    srv = { id:null, nome:'', dur:0, preco:0 };
    dataSel = null; dataTxt = ''; horSel = null; agId = null; tokenCliente = null; tokenReenvio = null;
    document.querySelectorAll('.srv-item').forEach(e => e.classList.remove('sel'));
    const btnIds = ['btn-srv','btn-data','btn-hor','btn-agendar'];
    btnIds.forEach(id => { const b = document.getElementById(id); if (b) b.disabled = true; });
    ['inp-nome','inp-tel','inp-email','inp-obs'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.getElementById('cod-real').value = '';
    atuBoxes('');
    renderCal();
  }
}

// Selecionar serviço
function selSrv(id, nome, dur, preco) {
  srv = { id, nome, dur, preco };
  document.querySelectorAll('.srv-item').forEach(e => e.classList.remove('sel'));
  const el = document.getElementById('sri-' + id);
  if (el) el.classList.add('sel');
  document.getElementById('btn-srv').disabled = false;
  if (telaAtual === 's-home') irPara('s-servico');
}

// Calendário
function mudaMes(d) {
  calMes += d;
  if (calMes > 11) { calMes = 0; calAno++; }
  if (calMes < 0)  { calMes = 11; calAno--; }
  renderCal();
}
function renderCal() {
  document.getElementById('cal-label').textContent = MESES[calMes] + ' ' + calAno;
  const grid = document.getElementById('cal-grid');
  grid.innerHTML = '';
  DIAS_ABR.forEach(d => { const h=document.createElement('div'); h.className='cal-head'; h.textContent=d; grid.appendChild(h); });
  const hoje     = new Date(); hoje.setHours(0,0,0,0);
  const primeiro = new Date(calAno, calMes, 1).getDay();
  const total    = new Date(calAno, calMes + 1, 0).getDate();
  for (let i = 0; i < primeiro; i++) grid.appendChild(document.createElement('div'));
  for (let d = 1; d <= total; d++) {
    const dt  = new Date(calAno, calMes, d);
    const dow = dt.getDay();
    const str = `${calAno}-${String(calMes+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const el  = document.createElement('div');
    el.className = 'cal-dia'; el.textContent = d;
    if (dt < hoje)               el.classList.add('past');
    else if (!DIAS_ATIVOS.includes(dow)) el.classList.add('off');
    else {
      if (dt.getTime() === hoje.getTime()) el.classList.add('hoje');
      if (dataSel === str) el.classList.add('sel');
      el.onclick = () => selData(str, d, dow);
    }
    grid.appendChild(el);
  }
}
function selData(str, d, dow) {
  dataSel = str; horSel = null;
  document.getElementById('btn-data').disabled = false;
  dataTxt = `${DIAS_SEMANA[dow]}, ${d} de ${MESES_MIN[calMes]} de ${calAno}`;
  document.getElementById('cal-txt').textContent = dataTxt;
  document.getElementById('cal-info').style.display = 'block';
  renderCal();
}

// Horários
async function carregarHorarios() {
  const w = document.getElementById('hor-wrap');
  w.innerHTML = '<div class="hor-msg">Carregando...</div>';
  document.getElementById('btn-hor').disabled = true;
  try {
    const r = await fetch(`api/horarios.php?salao_id=${SALAO_ID}&servico_id=${srv.id}&data=${dataSel}`);
    const j = await r.json();
    if (!j.horarios || !j.horarios.length) { w.innerHTML = '<div class="hor-msg">Nenhum horário disponível nesta data.</div>'; return; }
    const g = document.createElement('div'); g.className = 'hor-grid';
    j.horarios.forEach(h => {
      const b = document.createElement('button'); b.className = 'hor-btn'; b.textContent = h;
      b.onclick = () => { document.querySelectorAll('.hor-btn').forEach(x => x.classList.remove('sel')); b.classList.add('sel'); horSel = h; document.getElementById('btn-hor').disabled = false; };
      if (horSel === h) b.classList.add('sel');
      g.appendChild(b);
    });
    w.innerHTML = ''; w.appendChild(g);
  } catch(e) { w.innerHTML = '<div class="hor-msg">Erro ao carregar horários.</div>'; }
}

// Dados do cliente
function valDados() {
  const n = document.getElementById('inp-nome').value.trim();
  const e = document.getElementById('inp-email').value.trim();
  document.getElementById('btn-agendar').disabled = !(n && e);
}

async function enviar() {
  const btn = document.getElementById('btn-agendar');
  btn.disabled = true; btn.textContent = 'Enviando...';
  const f = new FormData();
  f.append('salao_id', SALAO_ID); f.append('servico_id', srv.id);
  f.append('data', dataSel); f.append('horario_selecionado', horSel);
  f.append('cliente_nome',  document.getElementById('inp-nome').value.trim());
  f.append('cliente_tel',   document.getElementById('inp-tel').value.trim());
  f.append('cliente_email', document.getElementById('inp-email').value.trim());
  f.append('observacoes',    document.getElementById('inp-obs').value.trim());
  try {
    const r = await fetch('api/agendar.php', { method:'POST', body:f });
    const j = await r.json();
    btn.disabled = false; btn.textContent = 'Agendar';
    if (j.erro) { const al=document.getElementById('al-dados'); al.textContent=j.erro; al.style.display='block'; return; }
    agId = j.agendamento_id;
    tokenReenvio = j.token_reenvio || null;
    const contato = document.getElementById('inp-email').value.trim();
    // Mostra código de debug na tela enquanto envio real não está configurado
    if (j.codigo_dev) {
      document.getElementById('verif-desc').textContent = 'Envio de e-mail está em modo de teste. Use o código abaixo para continuar:';
      document.getElementById('verif-code-hint').textContent = j.codigo_dev;
      document.getElementById('verif-dev-box').style.display = 'block';
    } else {
      document.getElementById('verif-desc').textContent = `Código enviado para ${contato}.`;
      document.getElementById('verif-dev-box').style.display = 'none';
    }
    irPara('s-verif');
    setTimeout(() => document.getElementById('cod-real').focus(), 300);
  } catch(e) { btn.disabled=false; btn.textContent='Agendar'; }
}

// Verificação
function atuBoxes(v) {
  document.querySelectorAll('.cod-box').forEach((b, i) => {
    b.textContent = v[i] || '';
    b.classList.toggle('ok', i < v.length);
    b.classList.toggle('cur', i === v.length && i < 6);
  });
  document.getElementById('btn-verif').disabled = v.length < 6;
}
async function verificar() {
  const cod = document.getElementById('cod-real').value;
  const btn = document.getElementById('btn-verif');
  btn.disabled = true; btn.textContent = 'Verificando...';
  try {
    const r = await fetch('api/verificar.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({agendamento_id:agId,token:tokenReenvio,codigo:cod}) });
    const j = await r.json();
    btn.disabled = false; btn.textContent = 'Confirmar agendamento';
    if (j.erro) { const al=document.getElementById('al-verif'); al.textContent=j.erro; al.style.display='block'; document.getElementById('cod-real').value=''; atuBoxes(''); return; }
    document.getElementById('conf-srv').textContent  = srv.nome;
    document.getElementById('conf-data').textContent = dataTxt.replace(/^\w/, c=>c.toUpperCase());
    document.getElementById('conf-hor').textContent  = horSel;
    tokenCliente = j.token || null;
    const cancelarLink = document.getElementById('conf-cancelar-link');
    if (cancelarLink && tokenCliente) cancelarLink.href = 'cancelar.php?token=' + encodeURIComponent(tokenCliente);
    irPara('s-conf');
  } catch(e) { btn.disabled=false; btn.textContent='Confirmar agendamento'; }
}
async function reenviar() {
  if (!agId || !tokenReenvio) return;
  const link = document.querySelector('.verif-re a');
  const antigo = link ? link.textContent : '';
  if (link) link.textContent = 'Enviando...';
  try {
    const r = await fetch('api/reenviar.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body:JSON.stringify({agendamento_id:agId, token:tokenReenvio})
    });
    const j = await r.json();
    if (j.erro) { const al=document.getElementById('al-verif'); al.textContent=j.erro; al.style.display='block'; return; }
    document.getElementById('al-verif').style.display='none';
    if (j.codigo_dev) {
      document.getElementById('verif-code-hint').textContent = j.codigo_dev;
      document.getElementById('verif-dev-box').style.display = 'block';
    }
    document.getElementById('cod-real').value=''; atuBoxes('');
  } catch(e) {
    const al=document.getElementById('al-verif'); al.textContent='Erro ao reenviar o código.'; al.style.display='block';
  } finally { if (link) link.textContent = antigo || 'Reenviar código'; }
}

// Init
renderCal();

// ── Onboarding ──
let obSlide = 0;

function obProximo() {
  if (obSlide < 2) { obSlide++; atuOb(); }
  else { irPara('s-home'); }
}
function obAnterior() {
  if (obSlide > 0) { obSlide--; atuOb(); }
}
function atuOb() {
  // Alterna explicitamente o slide visível. Isso evita cálculos de largura/transform
  // que ficavam inconsistentes ao redimensionar a janela.
  document.querySelectorAll('.ob-slide').forEach((slide, i) => {
    slide.classList.toggle('ativo', i === obSlide);
    slide.setAttribute('aria-hidden', i === obSlide ? 'false' : 'true');
    if (i === obSlide) slide.scrollTop = 0;
  });

  // Atualiza os dots
  document.querySelectorAll('.ob-dot').forEach((d, i) => {
    d.classList.toggle('ativo', i === obSlide);
  });

  // Mostra/oculta botão Anterior
  const ant = document.getElementById('ob-btn-ant');
  if (ant) ant.style.visibility = obSlide > 0 ? 'visible' : 'hidden';

  // Muda texto do botão Próximo no último slide
  const prox = document.getElementById('ob-btn-prox');
  if (prox) {
    const svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="18" height="18"><polyline points="9 18 15 12 9 6"/></svg>';
    prox.innerHTML = (obSlide === 2 ? 'Começar' : 'Próximo') + svg;
  }
}

// ── Drawer mobile ──
function abrirDrawer() {
  document.getElementById('mob-drawer').classList.add('open');
  document.getElementById('drawer-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function fecharDrawer() {
  document.getElementById('mob-drawer').classList.remove('open');
  document.getElementById('drawer-overlay').classList.remove('open');
  document.body.style.overflow = '';
}
</script>
</body>
</html>

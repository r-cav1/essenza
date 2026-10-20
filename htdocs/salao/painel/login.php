<?php
// Login do responsável pelo salão.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/cores.php';

if (!empty($_SESSION['salao_id'])) { header('Location: index.php'); exit; }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf($_POST['_csrf'] ?? null);
    $slug  = limparEntrada($_POST['slug']  ?? '');
    $senha = $_POST['senha'] ?? '';
    if ($slug && $senha) {
        // Rate limiting: bloqueia após 5 tentativas erradas em 15 minutos
        $chave_tentativas = 'login_fail_' . md5($slug);
        $tentativas = (int)($_SESSION[$chave_tentativas . '_count'] ?? 0);
        $ultimo     = (int)($_SESSION[$chave_tentativas . '_time']  ?? 0);

        // Reseta contador se passaram mais de 15 minutos
        if (time() - $ultimo > 900) { $tentativas = 0; }

        if ($tentativas >= 5) {
            $restam = 15 - (int)round((time() - $ultimo) / 60);
            $erro = "Muitas tentativas. Tente novamente em {$restam} minuto(s).";
        } else {
            $pdo  = conectar();
            $stmt = $pdo->prepare("SELECT id, nome_salao, senha_painel FROM saloes WHERE slug = ?");
            $stmt->execute([$slug]);
            $s = $stmt->fetch();
            if ($s && password_verify($senha, $s['senha_painel'])) {
                // Login bem-sucedido: limpa contador e cria sessão
                unset($_SESSION[$chave_tentativas . '_count'], $_SESSION[$chave_tentativas . '_time']);
                session_regenerate_id(true);
                $_SESSION['salao_id']   = $s['id'];
                $_SESSION['salao_nome'] = $s['nome_salao'];
                $_SESSION['salao_slug'] = $slug;
                header('Location: index.php'); exit;
            }
            // Login falhou: incrementa contador
            $_SESSION[$chave_tentativas . '_count'] = $tentativas + 1;
            $_SESSION[$chave_tentativas . '_time']  = time();
            $erro = 'Identificador ou senha incorretos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}

// Antes do login ainda não existe um salão na sessão. Usamos a paleta padrão
// e, depois de uma tentativa, reaproveitamos o tema do identificador informado.
$corP = '#4a8c6e';
$corS = '#e8f5ee';
$corTxt = '#1a2e25';
$fTit = 'Georgia';
$fBod = 'sans-serif';

if (!empty($_POST['slug'])) {
    $pdo2 = conectar();
    $s2 = $pdo2->prepare("SELECT cor_primaria, cor_secundaria, cor_texto, fonte_titulo, fonte_corpo FROM saloes WHERE slug = ?");
    $s2->execute([limparEntrada($_POST['slug'])]);
    $row = $s2->fetch();

    if ($row) {
        $corP = $row['cor_primaria'];
        $corS = $row['cor_secundaria'];
        $corTxt = $row['cor_texto'];
        $fTit = $row['fonte_titulo'];
        $fBod = $row['fonte_corpo'];
    }
}

$loginPaleta = paletaTema($corP, $corS, $corTxt);
$loginSurface = $loginPaleta['superficie'];
$loginSurface2 = $loginPaleta['superficie_2'];
$loginBorder = $loginPaleta['borda'];
$loginMuted = $loginPaleta['texto_secundario'];
$loginOnP = $loginPaleta['sobre_primaria'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel — Entrar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=<?= urlencode($fTit) ?>:wght@300;400&family=<?= urlencode($fBod) ?>:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root { --p:<?= $corP ?>; --bg:<?= $corS ?>; --txt:<?= $corTxt ?>; --surface:<?= $loginSurface ?>; --surface2:<?= $loginSurface2 ?>; --bord:<?= $loginBorder ?>; --muted:<?= $loginMuted ?>; --on-p:<?= $loginOnP ?>; --f-tit:'<?= htmlspecialchars($fTit) ?>',Georgia,serif; --f-bod:'<?= htmlspecialchars($fBod) ?>',sans-serif; }
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--bg);font-family:var(--f-bod);display:flex;align-items:center;justify-content:center;padding:24px;color:var(--txt);}
.card{background:var(--surface);padding:34px 34px 32px;border:1px solid var(--bord);border-radius:10px;width:100%;max-width:400px;box-shadow:0 8px 22px rgba(0,0,0,.06);}
.marca{display:flex;align-items:center;gap:10px;margin-bottom:22px;font-size:14px;font-weight:600;color:var(--txt);}
.marca-sinal{width:30px;height:30px;border-radius:7px;background:var(--p);color:var(--on-p);display:flex;align-items:center;justify-content:center;font-family:Georgia,serif;font-size:17px;font-weight:600;}
h1{font-family:var(--f-tit);font-size:1.7rem;font-weight:400;margin-bottom:5px;color:var(--txt);}
.sub{font-size:13px;line-height:1.5;color:var(--muted);margin-bottom:24px;}
label{display:block;font-size:13px;color:var(--txt);margin-bottom:6px;font-weight:500;}
input{width:100%;border:1px solid var(--bord);border-radius:6px;padding:11px 12px;font-family:var(--f-bod);font-size:14px;color:var(--txt);background:var(--surface2);outline:none;transition:border-color .15s;margin-bottom:17px;}
input:focus{border-color:var(--p);}
.btn{width:100%;padding:12px;background:var(--p);color:var(--on-p);border:none;border-radius:6px;font-family:var(--f-bod);font-size:14px;font-weight:600;cursor:pointer;margin-top:2px;}
.btn:hover{filter:brightness(.96);}
@media(max-width:480px){body{padding:18px}.card{padding:32px 26px;box-shadow:none}}
.erro{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:6px;padding:11px 14px;font-size:13px;margin-bottom:18px;}
</style>
</head>
<body>
<div class="card">
  <div class="marca"><span class="marca-sinal">E</span><span>Essenza</span></div>
  <h1>Entrar no painel</h1>
  <p class="sub">Informe o identificador do salão e a senha para continuar.</p>
  <?php if ($erro): ?><div class="erro"><?= sanitizar($erro) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
    <label for="slug">Identificador do salão</label>
    <input type="text" id="slug" name="slug" placeholder="ex: studio-marcia" value="<?= sanitizar($_POST['slug'] ?? '') ?>" required autocomplete="username">
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required autocomplete="current-password">
    <button type="submit" class="btn">Entrar</button>
  </form>
</div>
</body>
</html>

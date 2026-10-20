<?php
// Salva os textos opcionais dos três slides de boas-vindas.
require_once __DIR__ . '/../includes/config.php';

// Este endpoint recebe um formulário POST e redireciona de volta
// Não retorna JSON — não precisa de Content-Type: application/json
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../painel/aparencia.php');
    exit;
}

if (empty($_SESSION['salao_id'])) {
    header('Location: ../painel/login.php');
    exit;
}
exigirCsrf($_POST['_csrf'] ?? null);

$pdo     = conectar();
$salaoId = (int) $_SESSION['salao_id'];

// Coleta e sanitiza os 6 campos (3 slides × título + texto)
$campos = [];
for ($i = 1; $i <= 3; $i++) {
    // Se o campo foi deixado vazio, salva NULL para que o cliente.php use o default
    $titulo = limparEntrada($_POST["ob_titulo{$i}"] ?? '');
    $texto  = limparEntrada($_POST["ob_texto{$i}"]  ?? '');
    if (mb_strlen($titulo) > 200 || mb_strlen($texto) > 300) {
        http_response_code(400);
        die('Os textos do onboarding excedem o tamanho permitido.');
    }
    $campos["ob_titulo{$i}"] = $titulo !== '' ? $titulo : null;
    $campos["ob_texto{$i}"]  = $texto  !== '' ? $texto  : null;
}

$sets = implode(', ', array_map(function($k){ return $k . ' = ?'; }, array_keys($campos)));
$vals = array_values($campos);
$vals[] = $salaoId;

$pdo->prepare("UPDATE saloes SET $sets WHERE id = ?")->execute($vals);

// Redireciona de volta ao painel
header('Location: ../painel/aparencia.php?ob_saved=1');
exit;

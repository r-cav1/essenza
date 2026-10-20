<?php
// Salva os toggles simples do painel (modo de teste e onboarding).
define('API_CALL', true); // suprime headers HTML do config.php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método inválido.']);
    exit;
}

// Exige que o dono esteja logado no painel
if (empty($_SESSION['salao_id'])) {
    echo json_encode(['erro' => 'Não autorizado.']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
exigirCsrf($body['_csrf'] ?? null, true);
$campo = $body['campo'] ?? '';
$valor = $body['valor'] ?? null;

// Whitelist de campos que podem ser alterados por esta API
// (nunca use o campo recebido diretamente na query — risco de SQL injection)
$camposPermitidos = ['modo_dev', 'show_onboarding'];

if (!in_array($campo, $camposPermitidos, true)) {
    echo json_encode(['erro' => 'Campo não permitido.']);
    exit;
}

if (!in_array($valor, [0, 1, '0', '1'], true)) {
    echo json_encode(['erro' => 'Valor de configuração inválido.']);
    exit;
}

$pdo = conectar();
$pdo->prepare("UPDATE saloes SET {$campo} = ? WHERE id = ?")
    ->execute([(int) $valor, (int) $_SESSION['salao_id']]);

echo json_encode(['sucesso' => true]);

<?php
// Pode ser chamado pelo cliente (token) ou pelo painel do salão (sessão).
define('API_CALL', true);
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método inválido.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    $dados = [];
}

$token = preg_replace('/[^a-f0-9]/i', '', $dados['token'] ?? '');
if ($token !== '') {
    echo json_encode(cancelarAgendamentoCliente($token));
    exit;
}

if (!empty($_SESSION['salao_id'])) {
    exigirCsrf($dados['_csrf'] ?? null, true);
    $id = (int)($dados['agendamento_id'] ?? 0);
    echo json_encode(cancelarAgendamentoPainel($id, (int)$_SESSION['salao_id']));
    exit;
}

echo json_encode(['erro' => 'Não autorizado ou agendamento não encontrado.']);

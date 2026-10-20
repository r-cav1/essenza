<?php
// Confirma o agendamento usando o código digitado pelo cliente.
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

echo json_encode(verificarCodigoAgendamento($dados));

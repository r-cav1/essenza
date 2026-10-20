<?php
// Recebe os dados do formulário e entrega a regra para a camada interna.
define('API_CALL', true);
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método inválido.']);
    exit;
}

echo json_encode(criarAgendamento($_POST));

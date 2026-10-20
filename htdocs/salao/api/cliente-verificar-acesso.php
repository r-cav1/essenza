<?php
define('API_CALL', true);
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['erro'=>'Método inválido.']); exit; }
$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) { $dados = []; }
exigirCsrf($dados['csrf'] ?? null, true);
echo json_encode(verificarAcessoCliente($dados));

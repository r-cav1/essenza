<?php
// Retorna os horários livres para a data e o serviço escolhidos.
define('API_CALL', true);
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

echo json_encode(buscarHorariosDisponiveis($_GET));

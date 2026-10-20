<?php
// Configurações gerais do sistema. Este arquivo fica fora do htdocs.

define('DB_HOST', getenv('AGENDA_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('AGENDA_DB_NAME') ?: 'agenda_salao');
define('DB_USER', getenv('AGENDA_DB_USER') ?: 'root');
define('DB_PASS', getenv('AGENDA_DB_PASS') !== false ? getenv('AGENDA_DB_PASS') : '');

// No XAMPP local a URL continua sendo http://localhost/salao.
$baseUrl = getenv('AGENDA_BASE_URL');
if (!$baseUrl) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $protocolo = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/salao/index.php');

    $pos = strpos($script, '/painel/');
    if ($pos === false) {
        $pos = strpos($script, '/api/');
    }

    $caminho = $pos !== false ? substr($script, 0, $pos) : dirname($script);
    if ($caminho === '/' || $caminho === '.') {
        $caminho = '';
    }

    $baseUrl = $protocolo . '://' . $host . rtrim($caminho, '/');
}

define('BASE_URL', rtrim($baseUrl, '/'));

// O bootstrap público informa onde está a pasta acessível pelo navegador.
if (!defined('AGENDA_PUBLIC_DIR')) {
    $publicDir = getenv('AGENDA_PUBLIC_DIR') ?: dirname(__DIR__) . '/htdocs/salao';
    define('AGENDA_PUBLIC_DIR', $publicDir);
}

define('UPLOAD_DIR', AGENDA_PUBLIC_DIR . '/assets/uploads/');

date_default_timezone_set(getenv('AGENDA_TIMEZONE') ?: 'America/Sao_Paulo');

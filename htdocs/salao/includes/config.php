<?php
// Mantido neste caminho para não quebrar páginas das versões anteriores.
define('AGENDA_PUBLIC_DIR', dirname(__DIR__));

$appDir = getenv('AGENDA_APP_DIR');
if (!$appDir) {
    // C:\xampp\htdocs\salao\includes -> C:\xampp\salao_app
    $appDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'salao_app';
}

define('AGENDA_APP_DIR', $appDir);

if (!is_file(AGENDA_APP_DIR . DIRECTORY_SEPARATOR . 'bootstrap.php')) {
    die('Pasta interna do sistema não encontrada. Verifique a instalação em C:\\xampp\\salao_app.');
}

require_once AGENDA_APP_DIR . DIRECTORY_SEPARATOR . 'bootstrap.php';

<?php

// Abre uma única conexão PDO por requisição.
function conectar(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);

        if (defined('API_CALL')) {
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
        }

        die('Não foi possível conectar ao banco de dados. Verifique a configuração do sistema.');
    }

    return $pdo;
}

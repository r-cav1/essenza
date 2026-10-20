<?php

function sanitizar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function limparEntrada(string $valor): string {
    return trim(strip_tags($valor));
}

function dataValida(string $data): bool {
    $dt = DateTime::createFromFormat('!Y-m-d', $data);
    return $dt !== false && $dt->format('Y-m-d') === $data;
}

function horarioValido(string $hora): bool {
    $dt = DateTime::createFromFormat('!H:i', $hora);
    return $dt !== false && $dt->format('H:i') === $hora;
}

function redirecionarSe(bool $condicao, string $url): void {
    if ($condicao) {
        header('Location: ' . $url);
        exit;
    }
}

// Configuração básica da sessão usada pelo painel e pelos formulários.
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfValido(?string $token): bool {
    $salvo = $_SESSION['csrf_token'] ?? '';
    return $salvo !== '' && is_string($token) && hash_equals($salvo, $token);
}

function exigirCsrf(?string $token, bool $json = false): void {
    if (csrfValido($token)) {
        return;
    }

    http_response_code(403);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Sessão expirada ou requisição inválida. Atualize a página e tente novamente.']);
    } else {
        echo 'Sessão expirada ou requisição inválida. Atualize a página e tente novamente.';
    }
    exit;
}

if (!defined('API_CALL')) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

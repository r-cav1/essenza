<?php
/**
 * Ponto de entrada raiz do projeto.
 * - Se vier com ?salao=slug  → mostra a página pública do cliente
 * - Caso contrário           → redireciona para o painel (login)
 */
if (!empty($_GET['salao'])) {
    require __DIR__ . '/cliente.php';
    exit;
}

header('Location: painel/login.php');
exit;

<?php
// Teste manual do SMTP. Execute apenas pelo terminal.
if (PHP_SAPI !== 'cli') {
    exit("Este arquivo só pode ser executado pelo terminal.\n");
}

require_once __DIR__ . '/bootstrap.php';

$email = $argv[1] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Uso:\n";
    echo "C:\\xampp\\php\\php.exe C:\\xampp\\salao_app\\testar_smtp.php seu-email@exemplo.com\n";
    exit(1);
}

if (!smtpConfigurado()) {
    echo "SMTP ainda não está ativo/configurado em C:\\xampp\\salao_app\\config\\email.php\n";
    exit(1);
}

echo "Enviando e-mail de teste...\n";
$ok = enviarEmailSistema($email, 'Teste de e-mail do Essenza', "Se você recebeu esta mensagem, o SMTP do Essenza está funcionando.\n", 'teste_smtp');

echo $ok ? "E-mail enviado.\n" : "Falha no envio. Consulte o log de erros do PHP/Apache.\n";
exit($ok ? 0 : 1);

<?php
// Funções de e-mail do Essenza.
// O restante do sistema só precisa chamar enviarEmailSistema().

function configuracaoEmail(): array {
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $arquivo = __DIR__ . '/config/email.php';
    $config = is_file($arquivo) ? require $arquivo : [];

    return is_array($config) ? $config : [];
}

function smtpConfigurado(): bool {
    $c = configuracaoEmail();

    if (empty($c['ativo'])) {
        return false;
    }

    $obrigatorios = ['host', 'porta', 'usuario', 'senha', 'remetente_email'];
    foreach ($obrigatorios as $campo) {
        if (!isset($c[$campo]) || trim((string)$c[$campo]) === '') {
            return false;
        }
    }

    return filter_var($c['remetente_email'], FILTER_VALIDATE_EMAIL) !== false;
}

// Mesmo que o dono desative o modo de teste, o código só sai da tela quando
// o SMTP global estiver realmente ativado e configurado.
function usarModoTeste(bool $modoTesteSalao): bool {
    return $modoTesteSalao || !smtpConfigurado();
}

function smtpLerResposta($socket): string {
    $resposta = '';

    while (($linha = fgets($socket, 515)) !== false) {
        $resposta .= $linha;
        if (strlen($linha) < 4 || $linha[3] === ' ') {
            break;
        }
    }

    return $resposta;
}

function smtpEsperar($socket, array $codigos): void {
    $resposta = smtpLerResposta($socket);
    $codigo = (int)substr($resposta, 0, 3);

    if (!in_array($codigo, $codigos, true)) {
        throw new RuntimeException('Resposta inesperada do servidor SMTP: ' . trim($resposta));
    }
}

function smtpComando($socket, string $comando, array $codigos): void {
    fwrite($socket, $comando . "\r\n");
    smtpEsperar($socket, $codigos);
}

function smtpCodificarCabecalho(string $texto): string {
    return '=?UTF-8?B?' . base64_encode($texto) . '?=';
}

function registrarLogEmail(string $tipo, string $destino, string $resultado, string $detalhe = ''): void {
    $pasta = __DIR__ . '/logs';
    if (!is_dir($pasta)) {
        @mkdir($pasta, 0775, true);
    }

    $destinoSeguro = filter_var($destino, FILTER_VALIDATE_EMAIL) ? $destino : '(inválido ou vazio)';
    $linha = '[' . date('d/m/Y H:i:s') . '] '
        . 'tipo=' . $tipo
        . ' | destino=' . $destinoSeguro
        . ' | resultado=' . $resultado;

    if ($detalhe !== '') {
        $detalhe = str_replace(["\r", "\n"], ' ', $detalhe);
        $linha .= ' | detalhe=' . trim($detalhe);
    }

    @file_put_contents($pasta . '/email.log', $linha . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function tentarEnviarEmailSistema(string $destino, string $assunto, string $mensagem, string $tipo = 'email', int $tentativa = 1): bool {
    if (!smtpConfigurado()) {
        registrarLogEmail($tipo, $destino, 'ERRO', 'SMTP desativado ou configuração incompleta.');
        return false;
    }
    if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        registrarLogEmail($tipo, $destino, 'ERRO', 'Destinatário inválido.');
        return false;
    }

    $c = configuracaoEmail();
    $seguranca = strtolower(trim((string)($c['seguranca'] ?? 'tls')));
    $host = trim((string)$c['host']);
    $porta = (int)$c['porta'];
    $timeout = max(5, (int)($c['timeout'] ?? 15));

    // SSL abre a conexão já criptografada. TLS começa normal e usa STARTTLS.
    $endereco = ($seguranca === 'ssl' ? 'ssl://' : '') . $host . ':' . $porta;
    $socket = @stream_socket_client($endereco, $erroNumero, $erroTexto, $timeout);

    if (!$socket) {
        registrarLogEmail($tipo, $destino, 'ERRO', 'Tentativa ' . $tentativa . ': conexão não aberta (' . $erroNumero . ' - ' . $erroTexto . ').');
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $etapa = 'conexão';

    try {
        smtpEsperar($socket, [220]);
        $etapa = 'EHLO';
        smtpComando($socket, 'EHLO localhost', [250]);

        if ($seguranca === 'tls') {
            smtpComando($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Não foi possível iniciar a conexão TLS.');
            }
            smtpComando($socket, 'EHLO localhost', [250]);
        }

        $etapa = 'autenticação';
        smtpComando($socket, 'AUTH LOGIN', [334]);
        smtpComando($socket, base64_encode((string)$c['usuario']), [334]);
        smtpComando($socket, base64_encode((string)$c['senha']), [235]);

        $remetente = trim((string)$c['remetente_email']);
        $nome = trim((string)($c['remetente_nome'] ?? 'Essenza'));

        $etapa = 'remetente';
        smtpComando($socket, 'MAIL FROM:<' . $remetente . '>', [250]);
        $etapa = 'destinatário';
        smtpComando($socket, 'RCPT TO:<' . $destino . '>', [250, 251]);
        smtpComando($socket, 'DATA', [354]);
        $etapa = 'conteúdo';

        // Pontos no começo de uma linha precisam ser duplicados no protocolo SMTP.
        $corpo = str_replace(["\r\n", "\r"], "\n", $mensagem);
        $corpo = str_replace("\n.", "\n..", $corpo);
        $corpo = str_replace("\n", "\r\n", $corpo);

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . smtpCodificarCabecalho($nome) . ' <' . $remetente . '>',
            'To: <' . $destino . '>',
            'Subject: ' . smtpCodificarCabecalho($assunto),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $corpo . "\r\n.\r\n");
        smtpEsperar($socket, [250]);
        $etapa = 'encerramento';
        smtpComando($socket, 'QUIT', [221]);

        fclose($socket);
        registrarLogEmail($tipo, $destino, 'OK', 'Tentativa ' . $tentativa . ': mensagem aceita pelo servidor SMTP.');
        return true;
    } catch (Throwable $e) {
        if (is_resource($socket)) {
            fclose($socket);
        }
        registrarLogEmail($tipo, $destino, 'ERRO', 'Tentativa ' . $tentativa . ' na etapa ' . $etapa . ': ' . $e->getMessage());
        error_log('[Essenza SMTP] ' . $e->getMessage());
        return false;
    }
}


// Alguns SMTPs de teste podem recusar uma conexão isolada.
// Faz somente uma nova tentativa curta antes de considerar o envio como falho.
function enviarEmailSistema(string $destino, string $assunto, string $mensagem, string $tipo = 'email'): bool {
    if (tentarEnviarEmailSistema($destino, $assunto, $mensagem, $tipo, 1)) {
        return true;
    }

    usleep(500000);
    return tentarEnviarEmailSistema($destino, $assunto, $mensagem, $tipo, 2);
}

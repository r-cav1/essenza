<?php
/**
 * clientes.php — acesso temporário do cliente aos próprios agendamentos.
 *
 * Não existem contas ou senhas de cliente. O acesso é liberado por um
 * código enviado ao e-mail usado no agendamento e fica restrito ao salão.
 */

function solicitarAcessoCliente(array $dados): array {
    $salaoId = (int)($dados['salao_id'] ?? 0);
    $email = strtolower(trim((string)($dados['email'] ?? '')));

    if (!$salaoId || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['erro' => 'Informe um e-mail válido.'];
    }

    $pdo = conectar();
    $stmt = $pdo->prepare('SELECT id, nome_salao, modo_dev FROM saloes WHERE id=? LIMIT 1');
    $stmt->execute([$salaoId]);
    $salao = $stmt->fetch();
    if (!$salao) {
        return ['erro' => 'Salão não encontrado.'];
    }

    // Só permite iniciar o acesso quando o e-mail realmente pertence a um
    // agendamento deste salão. A mensagem pública continua genérica.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM agendamentos WHERE salao_id=? AND LOWER(cliente_email)=?');
    $stmt->execute([$salaoId, $email]);
    $emailConhecido = (int)$stmt->fetchColumn() > 0;

    $agora = time();
    $ultimoEnvio = (int)($_SESSION['cliente_acesso_ultimo_envio'][$salaoId] ?? 0);
    if ($ultimoEnvio > $agora - 60) {
        return ['erro' => 'Aguarde um minuto antes de solicitar outro código.'];
    }

    $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $modoTeste = usarModoTeste((bool)$salao['modo_dev']);

    if (!$modoTeste && $emailConhecido) {
        $mensagem = "Seu código de acesso ao Essenza é: {$codigo}\n\n"
            . "Ele permite consultar seus agendamentos em {$salao['nome_salao']} e expira em 15 minutos.\n"
            . 'Se você não solicitou este acesso, ignore esta mensagem.';

        if (!enviarEmailSistema($email, 'Código para consultar seus agendamentos', $mensagem, 'acesso_cliente')) {
            return ['erro' => 'Não foi possível enviar o código. Tente novamente em alguns instantes.'];
        }
    }

    // O código fica apenas na sessão do navegador e nunca é salvo em texto puro.
    $_SESSION['cliente_acesso_pendente'] = [
        'salao_id' => $salaoId,
        'email' => $email,
        'codigo_hash' => password_hash($codigo, PASSWORD_DEFAULT),
        'expira_em' => $agora + 15 * 60,
        'tentativas' => 0,
    ];
    $_SESSION['cliente_acesso_ultimo_envio'][$salaoId] = $agora;

    $resposta = ['sucesso' => true, 'mensagem' => $modoTeste ? 'Código gerado.' : 'Código enviado por e-mail.'];
    if ($modoTeste && $emailConhecido) {
        $resposta['codigo_dev'] = $codigo;
    }
    return $resposta;
}

function verificarAcessoCliente(array $dados): array {
    $salaoId = (int)($dados['salao_id'] ?? 0);
    $codigo = trim((string)($dados['codigo'] ?? ''));
    $pendente = $_SESSION['cliente_acesso_pendente'] ?? null;

    if (!$salaoId || !preg_match('/^\d{6}$/', $codigo) || !is_array($pendente)) {
        return ['erro' => 'Código ou sessão inválidos. Solicite um novo código.'];
    }
    if ((int)$pendente['salao_id'] !== $salaoId) {
        return ['erro' => 'Este código pertence a outro salão.'];
    }
    if ((int)$pendente['expira_em'] < time()) {
        unset($_SESSION['cliente_acesso_pendente']);
        return ['erro' => 'O código expirou. Solicite um novo código.'];
    }
    if ((int)$pendente['tentativas'] >= 5) {
        unset($_SESSION['cliente_acesso_pendente']);
        return ['erro' => 'Limite de tentativas atingido. Solicite um novo código.'];
    }

    $_SESSION['cliente_acesso_pendente']['tentativas']++;
    if (!password_verify($codigo, $pendente['codigo_hash'])) {
        $restantes = 5 - $_SESSION['cliente_acesso_pendente']['tentativas'];
        return ['erro' => "Código incorreto. {$restantes} tentativa(s) restante(s)."];
    }

    session_regenerate_id(true);
    $_SESSION['cliente_acesso'] = [
        'salao_id' => $salaoId,
        'email' => $pendente['email'],
        'expira_em' => time() + 60 * 60,
    ];
    unset($_SESSION['cliente_acesso_pendente']);

    return ['sucesso' => true];
}

function acessoClienteAtual(int $salaoId): ?array {
    $acesso = $_SESSION['cliente_acesso'] ?? null;
    if (!is_array($acesso) || (int)($acesso['salao_id'] ?? 0) !== $salaoId) {
        return null;
    }
    if ((int)($acesso['expira_em'] ?? 0) < time()) {
        unset($_SESSION['cliente_acesso']);
        return null;
    }
    return $acesso;
}

function listarAgendamentosCliente(int $salaoId, string $email): array {
    $pdo = conectar();
    expirarPendentes($pdo, $salaoId);
    $stmt = $pdo->prepare(
        'SELECT a.id, a.data_hora, a.status, a.token_cliente, a.observacoes,
                s.nome AS servico_nome, s.duracao_min, s.preco
         FROM agendamentos a
         JOIN servicos s ON s.id=a.servico_id
         WHERE a.salao_id=? AND LOWER(a.cliente_email)=?
         ORDER BY a.data_hora DESC, a.id DESC'
    );
    $stmt->execute([$salaoId, strtolower($email)]);
    return $stmt->fetchAll();
}

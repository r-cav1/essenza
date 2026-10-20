<?php

// Regras de negócio relacionadas ao agendamento.

function expirarPendentes(PDO $pdo, int $salaoId): void {
    $stmt = $pdo->prepare(
        "UPDATE agendamentos SET status='cancelado'
         WHERE salao_id=? AND status='pendente'
           AND codigo_expira_em IS NOT NULL AND codigo_expira_em < NOW()"
    );
    $stmt->execute([$salaoId]);
}

function horarioPertenceAgenda(array $salao, string $data, string $hora, int $duracaoMin): bool {
    $intervalo = (int)($salao['intervalo_minutos'] ?? 0);
    if (!in_array($intervalo, [15, 30, 45, 60, 90, 120], true)) {
        return false;
    }

    $dias = array_map('intval', array_filter(explode(',', $salao['dias_funcionamento'] ?? ''), 'strlen'));
    $diaSemana = (int)date('w', strtotime($data));
    if (!in_array($diaSemana, $dias, true)) {
        return false;
    }

    $inicio = strtotime($data . ' ' . $hora);
    $abertura = strtotime($data . ' ' . $salao['horario_abertura']);
    $fechamento = strtotime($data . ' ' . $salao['horario_fechamento']);
    $passo = $intervalo * 60;

    if ($inicio < $abertura || $inicio + ($duracaoMin * 60) > $fechamento) {
        return false;
    }

    return (($inicio - $abertura) % $passo) === 0;
}

function criarAgendamento(array $dados): array {
    $salaoId = (int)($dados['salao_id'] ?? 0);
    $servicoId = (int)($dados['servico_id'] ?? 0);
    $data = limparEntrada($dados['data'] ?? '');
    $horario = limparEntrada($dados['horario_selecionado'] ?? '');
    $nome = limparEntrada($dados['cliente_nome'] ?? '');
    $tel = limparEntrada($dados['cliente_tel'] ?? '');
    $email = limparEntrada($dados['cliente_email'] ?? '');
    $obs = limparEntrada($dados['observacoes'] ?? '');

    if (!$salaoId || !$servicoId || !$data || !$horario || !$nome) {
        return ['erro' => 'Preencha todos os campos obrigatórios.'];
    }
    if ($email === '') {
        return ['erro' => 'Informe um e-mail para receber o código de verificação.'];
    }
    if (mb_strlen($nome) > 200 || mb_strlen($email) > 200 || mb_strlen($obs) > 2000) {
        return ['erro' => 'Um dos campos excede o tamanho permitido.'];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['erro' => 'E-mail inválido.'];
    }

    if ($tel !== '') {
        $tel = preg_replace('/\D/', '', $tel);
        if (strlen($tel) < 10 || strlen($tel) > 13) {
            return ['erro' => 'Telefone inválido. Use DDD + número (ex: 11999990000).'];
        }
    }

    if (!dataValida($data) || !horarioValido($horario)) {
        return ['erro' => 'Data ou horário inválido.'];
    }

    $dataHora = $data . ' ' . $horario . ':00';
    if (strtotime($dataHora) <= time()) {
        return ['erro' => 'Escolha uma data/hora futura.'];
    }

    $pdo = conectar();
    expirarPendentes($pdo, $salaoId);

    // O e-mail identifica o cliente no fluxo de confirmação.
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos
         WHERE salao_id=? AND cliente_email=?
           AND DATE(criado_em)=CURDATE()
           AND status IN ('pendente','confirmado')"
    );
    $stmt->execute([$salaoId, $email]);
    if ((int)$stmt->fetchColumn() >= 3) {
        return ['erro' => 'Limite de agendamentos por dia atingido. Tente novamente amanhã.'];
    }

    $stmt = $pdo->prepare('SELECT duracao_min FROM servicos WHERE id=? AND salao_id=? AND ativo=1');
    $stmt->execute([$servicoId, $salaoId]);
    $servico = $stmt->fetch();
    if (!$servico) {
        return ['erro' => 'Serviço não encontrado.'];
    }
    $duracaoMin = (int)$servico['duracao_min'];

    $stmt = $pdo->prepare(
        'SELECT horario_abertura, horario_fechamento, dias_funcionamento, intervalo_minutos, modo_dev
         FROM saloes WHERE id=?'
    );
    $stmt->execute([$salaoId]);
    $salao = $stmt->fetch();
    if (!$salao || !horarioPertenceAgenda($salao, $data, $horario, $duracaoMin)) {
        return ['erro' => 'Este horário não pertence à agenda disponível do salão.'];
    }

    try {
        $pdo->beginTransaction();

        // O lock evita duas reservas para o mesmo período quando chegam juntas.
        $stmt = $pdo->prepare(
            "SELECT id FROM agendamentos
             WHERE salao_id=? AND status IN ('pendente','confirmado')
               AND data_hora < DATE_ADD(?, INTERVAL ? MINUTE)
               AND DATE_ADD(data_hora, INTERVAL duracao_min MINUTE) > ?
             LIMIT 1 FOR UPDATE"
        );
        $stmt->execute([$salaoId, $dataHora, $duracaoMin, $dataHora]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            return ['erro' => 'Este horário não está mais disponível. Escolha outro.'];
        }

        $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
        $expira = date('Y-m-d H:i:s', time() + 15 * 60);
        $token = bin2hex(random_bytes(20));

        $stmt = $pdo->prepare(
            "INSERT INTO agendamentos
                (salao_id, servico_id, cliente_nome, cliente_tel, cliente_email,
                 data_hora, duracao_min, codigo_verificacao, codigo_expira_em,
                 status, token_cliente, observacoes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?)"
        );
        $stmt->execute([
            $salaoId, $servicoId, $nome, $tel, $email,
            $dataHora, $duracaoMin, $codigoHash, $expira, $token, $obs
        ]);
        $agendamentoId = (int)$pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['erro' => 'Não foi possível criar o agendamento. Tente novamente.'];
    }

    $modoDev = usarModoTeste((bool)$salao['modo_dev']);
    if (!$modoDev) {
        $mensagem = "Olá, {$nome}!\n\nSeu código de confirmação é: {$codigo}\nEle expira em 15 minutos.\n\n"
            . 'Se você não iniciou este agendamento, ignore esta mensagem.';

        if (!enviarEmailSistema($email, 'Código de confirmação do seu agendamento', $mensagem, 'codigo_confirmacao')) {
            $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=? AND status='pendente'")
                ->execute([$agendamentoId]);
            return ['erro' => 'Não foi possível enviar o código por e-mail. Verifique a configuração de e-mail do servidor ou ative o modo de teste.'];
        }
    }

    $resposta = [
        'sucesso' => true,
        'agendamento_id' => $agendamentoId,
        'token_reenvio' => $token,
        'mensagem' => $modoDev ? 'Código gerado (modo teste).' : 'Código enviado por e-mail. Verifique sua caixa de entrada.',
    ];
    if ($modoDev) {
        $resposta['codigo_dev'] = $codigo;
    }

    return $resposta;
}

function buscarHorariosDisponiveis(array $dados): array {
    $salaoId = (int)($dados['salao_id'] ?? 0);
    $servicoId = (int)($dados['servico_id'] ?? 0);
    $data = limparEntrada($dados['data'] ?? '');

    if (!$salaoId || !$servicoId || !dataValida($data)) {
        return ['erro' => 'Parâmetros inválidos.'];
    }

    $pdo = conectar();
    expirarPendentes($pdo, $salaoId);

    $stmt = $pdo->prepare(
        'SELECT horario_abertura, horario_fechamento, dias_funcionamento, intervalo_minutos
         FROM saloes WHERE id=?'
    );
    $stmt->execute([$salaoId]);
    $salao = $stmt->fetch();
    if (!$salao) {
        return ['erro' => 'Salão não encontrado.'];
    }

    $diasAtivos = array_map('intval', array_filter(explode(',', $salao['dias_funcionamento']), 'strlen'));
    if (!in_array((int)date('w', strtotime($data)), $diasAtivos, true)) {
        return ['horarios' => []];
    }

    $stmt = $pdo->prepare('SELECT duracao_min FROM servicos WHERE id=? AND salao_id=? AND ativo=1');
    $stmt->execute([$servicoId, $salaoId]);
    $servico = $stmt->fetch();
    if (!$servico) {
        return ['erro' => 'Serviço inválido.'];
    }
    $duracaoMin = (int)$servico['duracao_min'];

    $intervalo = (int)$salao['intervalo_minutos'];
    if (!in_array($intervalo, [15, 30, 45, 60, 90, 120], true)) {
        return ['erro' => 'Configuração de intervalo inválida.'];
    }

    $stmt = $pdo->prepare(
        "SELECT data_hora, duracao_min FROM agendamentos
         WHERE salao_id=? AND DATE(data_hora)=? AND status IN ('pendente','confirmado')"
    );
    $stmt->execute([$salaoId, $data]);
    $ocupados = $stmt->fetchAll();

    $abertura = strtotime($data . ' ' . $salao['horario_abertura']);
    $fechamento = strtotime($data . ' ' . $salao['horario_fechamento']);
    $passo = $intervalo * 60;
    $horarios = [];

    for ($slot = $abertura; $slot + ($duracaoMin * 60) <= $fechamento; $slot += $passo) {
        if ($slot < time()) {
            continue;
        }

        $livre = true;
        foreach ($ocupados as $ocupado) {
            $inicioOcupado = strtotime($ocupado['data_hora']);
            $fimOcupado = $inicioOcupado + ((int)$ocupado['duracao_min'] * 60);
            $fimSlot = $slot + ($duracaoMin * 60);

            if ($slot < $fimOcupado && $fimSlot > $inicioOcupado) {
                $livre = false;
                break;
            }
        }

        if ($livre) {
            $horarios[] = date('H:i', $slot);
        }
    }

    return ['horarios' => $horarios];
}

function verificarCodigoAgendamento(array $dados): array {
    $id = (int)($dados['agendamento_id'] ?? 0);
    $codigo = trim($dados['codigo'] ?? '');
    $token = preg_replace('/[^a-f0-9]/i', '', $dados['token'] ?? '');

    if (!$id || strlen($token) !== 40 || !preg_match('/^\d{6}$/', $codigo)) {
        return ['erro' => 'Dados inválidos.'];
    }

    $pdo = conectar();
    $stmt = $pdo->prepare(
        'SELECT id, codigo_verificacao, codigo_expira_em, tentativas, status
         FROM agendamentos WHERE id=? AND token_cliente=? LIMIT 1'
    );
    $stmt->execute([$id, $token]);
    $ag = $stmt->fetch();

    if (!$ag) {
        return ['erro' => 'Agendamento não encontrado.'];
    }
    if ($ag['status'] !== 'pendente') {
        return ['erro' => 'Este agendamento já foi processado.'];
    }
    if ((int)$ag['tentativas'] >= 5) {
        return ['erro' => 'Muitas tentativas incorretas. Faça um novo agendamento.'];
    }
    if (empty($ag['codigo_expira_em']) || strtotime($ag['codigo_expira_em']) < time()) {
        $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=? AND status='pendente'")->execute([$id]);
        return ['erro' => 'Código expirado (15 minutos). Faça um novo agendamento.'];
    }

    $pdo->prepare('UPDATE agendamentos SET tentativas=tentativas+1 WHERE id=?')->execute([$id]);
    if (!password_verify($codigo, $ag['codigo_verificacao'])) {
        $restantes = 5 - ((int)$ag['tentativas'] + 1);
        return ['erro' => "Código incorreto. {$restantes} tentativa(s) restante(s)."];
    }

    $pdo->prepare(
        "UPDATE agendamentos
         SET status='confirmado', codigo_verificacao=NULL, codigo_expira_em=NULL
         WHERE id=?"
    )->execute([$id]);

    $stmt = $pdo->prepare(
        'SELECT a.*, s.nome AS servico_nome, sal.nome_salao, sal.email_contato, sal.email_notificacao, sal.modo_dev
         FROM agendamentos a
         JOIN servicos s ON s.id=a.servico_id
         JOIN saloes sal ON sal.id=a.salao_id
         WHERE a.id=?'
    );
    $stmt->execute([$id]);
    $dadosAgendamento = $stmt->fetch();

    // Depois da confirmação, cliente e salão recebem avisos independentes.
    // Se um envio falhar, o outro ainda é tentado e o agendamento continua confirmado.
    if ($dadosAgendamento) {
        $momento = strtotime($dadosAgendamento['data_hora']);
        $quando = date('d/m/Y', $momento) . ' às ' . date('H:i', $momento);

        // O e-mail de notificações é opcional. Quando não estiver preenchido,
        // o Essenza usa o e-mail de contato do próprio salão.
        $emailSalao = trim((string)($dadosAgendamento['email_notificacao'] ?? ''));
        if ($emailSalao === '') {
            $emailSalao = trim((string)($dadosAgendamento['email_contato'] ?? ''));
        }

        if ($emailSalao !== '' && filter_var($emailSalao, FILTER_VALIDATE_EMAIL)) {
            $contatoCliente = $dadosAgendamento['cliente_email'];
            if (!empty($dadosAgendamento['cliente_tel'])) {
                $contatoCliente .= ' / ' . $dadosAgendamento['cliente_tel'];
            }

            $mensagemSalao = "Novo agendamento confirmado em {$dadosAgendamento['nome_salao']}.\n\n"
                . "Cliente: {$dadosAgendamento['cliente_nome']}\n"
                . "Serviço: {$dadosAgendamento['servico_nome']}\n"
                . "Data/Hora: {$quando}\n"
                . "Contato: {$contatoCliente}\n";

            if (!enviarEmailSistema($emailSalao, 'Novo agendamento confirmado', $mensagemSalao, 'notificacao_salao')) {
                error_log('[Essenza] Não foi possível enviar a confirmação ao salão.');
            }
        }

        // Pequeno intervalo entre os envios para evitar limite de conexões em SMTPs de teste.
        usleep(300000);

        if (!usarModoTeste((bool)$dadosAgendamento['modo_dev']) && !empty($dadosAgendamento['cliente_email'])) {
            $linkGerenciar = BASE_URL . '/cancelar.php?token=' . urlencode($dadosAgendamento['token_cliente']);
            $mensagemCliente = "Olá, {$dadosAgendamento['cliente_nome']}!\n\n"
                . "Seu agendamento em {$dadosAgendamento['nome_salao']} foi confirmado.\n\n"
                . "Serviço: {$dadosAgendamento['servico_nome']}\n"
                . "Data/Hora: {$quando}\n\n"
                . "Para consultar ou cancelar este agendamento, use o link abaixo:\n{$linkGerenciar}\n\n"
                . 'Guarde este link, pois ele é individual para o seu agendamento.';

            if (!enviarEmailSistema($dadosAgendamento['cliente_email'], 'Agendamento confirmado', $mensagemCliente, 'confirmacao_cliente')) {
                error_log('[Essenza] Não foi possível enviar a confirmação ao cliente.');
            }
        }
    }

    return [
        'sucesso' => true,
        'mensagem' => 'Agendamento confirmado com sucesso!',
        'token' => $dadosAgendamento['token_cliente'],
    ];
}

function reenviarCodigoAgendamento(array $dados): array {
    $id = (int)($dados['agendamento_id'] ?? 0);
    $token = preg_replace('/[^a-f0-9]/i', '', $dados['token'] ?? '');
    if (!$id || strlen($token) !== 40) {
        return ['erro' => 'Dados inválidos.'];
    }

    $pdo = conectar();

    // Mantém compatibilidade com bancos criados antes da coluna de reenvios.
    try {
        $pdo->exec('ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS reenvios_codigo TINYINT NOT NULL DEFAULT 0');
    } catch (PDOException $e) {
        // Se a hospedagem não permitir ALTER, o schema atualizado deve ser importado manualmente.
    }

    $stmt = $pdo->prepare(
        'SELECT a.id, a.cliente_nome, a.cliente_email, a.codigo_expira_em, a.status,
                a.reenvios_codigo, a.criado_em, sal.modo_dev
         FROM agendamentos a
         JOIN saloes sal ON sal.id=a.salao_id
         WHERE a.id=? AND a.token_cliente=? LIMIT 1'
    );
    $stmt->execute([$id, $token]);
    $ag = $stmt->fetch();

    if (!$ag || $ag['status'] !== 'pendente') {
        return ['erro' => 'Este agendamento não está mais aguardando confirmação.'];
    }
    if (empty($ag['codigo_expira_em']) || strtotime($ag['codigo_expira_em']) < time()) {
        $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=? AND status='pendente'")->execute([$id]);
        return ['erro' => 'O código expirou. Escolha o horário novamente.'];
    }
    if ((int)$ag['reenvios_codigo'] >= 3) {
        return ['erro' => 'Limite de reenvios atingido. Faça um novo agendamento.'];
    }
    if (strtotime($ag['criado_em']) < time() - 30 * 60) {
        $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=? AND status='pendente'")->execute([$id]);
        return ['erro' => 'O tempo de confirmação terminou. Escolha o horário novamente.'];
    }
    if (strtotime($ag['codigo_expira_em']) > time() + 14 * 60) {
        return ['erro' => 'Aguarde um minuto antes de solicitar outro código.'];
    }

    $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($codigo, PASSWORD_DEFAULT);
    $expira = date('Y-m-d H:i:s', time() + 15 * 60);
    $modoDev = usarModoTeste((bool)$ag['modo_dev']);

    if (!$modoDev) {
        if (empty($ag['cliente_email'])) {
            return ['erro' => 'Este agendamento não possui e-mail para reenvio.'];
        }

        $ok = enviarEmailSistema(
            $ag['cliente_email'],
            'Novo código de confirmação do seu agendamento',
            "Olá, {$ag['cliente_nome']}!\n\nSeu novo código de confirmação é: {$codigo}\nEle expira em 15 minutos."
        );
        if (!$ok) {
            return ['erro' => 'Não foi possível reenviar o código. Verifique a configuração de e-mail do servidor.'];
        }
    }

    $pdo->prepare(
        'UPDATE agendamentos
         SET codigo_verificacao=?, codigo_expira_em=?, tentativas=0, reenvios_codigo=reenvios_codigo+1
         WHERE id=?'
    )->execute([$hash, $expira, $id]);

    $resposta = [
        'sucesso' => true,
        'mensagem' => $modoDev ? 'Novo código gerado.' : 'Novo código enviado por e-mail.',
    ];
    if ($modoDev) {
        $resposta['codigo_dev'] = $codigo;
    }

    return $resposta;
}

function cancelarAgendamentoCliente(string $token): array {
    $token = preg_replace('/[^a-f0-9]/i', '', $token);
    if (strlen($token) !== 40) {
        return ['erro' => 'Token inválido.'];
    }

    $pdo = conectar();
    $stmt = $pdo->prepare(
        "SELECT id FROM agendamentos
         WHERE token_cliente=? AND status='confirmado' AND data_hora>NOW()"
    );
    $stmt->execute([$token]);
    $ag = $stmt->fetch();

    if (!$ag) {
        return ['erro' => 'Agendamento não encontrado, já cancelado ou fora do prazo.'];
    }

    $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=?")->execute([$ag['id']]);
    return ['sucesso' => true];
}

function cancelarAgendamentoPainel(int $id, int $salaoId): array {
    if (!$id || !$salaoId) {
        return ['erro' => 'Agendamento inválido.'];
    }

    $pdo = conectar();
    $stmt = $pdo->prepare(
        "UPDATE agendamentos SET status='cancelado'
         WHERE id=? AND salao_id=? AND status IN ('pendente','confirmado') AND data_hora>NOW()"
    );
    $stmt->execute([$id, $salaoId]);

    if ($stmt->rowCount() < 1) {
        return ['erro' => 'Agendamento não encontrado ou não pode mais ser cancelado.'];
    }

    return ['sucesso' => true];
}

// Funções usadas pelo painel do salão.
function buscarAgendamentoPainel(int $id, int $salaoId): ?array {
    if ($id < 1 || $salaoId < 1) return null;

    $pdo = conectar();
    $stmt = $pdo->prepare(
        "SELECT a.*, s.nome AS servico_nome, s.preco AS servico_preco
         FROM agendamentos a
         JOIN servicos s ON s.id = a.servico_id
         WHERE a.id = ? AND a.salao_id = ?
         LIMIT 1"
    );
    $stmt->execute([$id, $salaoId]);
    $agendamento = $stmt->fetch();

    return $agendamento ?: null;
}

function concluirAgendamentoPainel(int $id, int $salaoId): array {
    if ($id < 1 || $salaoId < 1) return ['erro' => 'Agendamento inválido.'];

    $pdo = conectar();
    $stmt = $pdo->prepare(
        "UPDATE agendamentos SET status='concluido'
         WHERE id=? AND salao_id=? AND status='confirmado'"
    );
    $stmt->execute([$id, $salaoId]);

    if ($stmt->rowCount() < 1) {
        return ['erro' => 'Este agendamento não pode ser marcado como concluído.'];
    }

    return ['sucesso' => true];
}

function resumoAgendaPainel(int $salaoId): array {
    $pdo = conectar();
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT
            SUM(DATE(data_hora)=?) AS hoje,
            SUM(status='confirmado' AND DATE(data_hora)>=?) AS confirmados,
            SUM(status='pendente' AND DATE(data_hora)>=?) AS pendentes,
            SUM(status='concluido' AND DATE(data_hora)=?) AS concluidos
         FROM agendamentos WHERE salao_id=?"
    );
    $stmt->execute([$hoje, $hoje, $hoje, $hoje, $salaoId]);
    $r = $stmt->fetch() ?: [];

    return [
        'hoje' => (int)($r['hoje'] ?? 0),
        'confirmados' => (int)($r['confirmados'] ?? 0),
        'pendentes' => (int)($r['pendentes'] ?? 0),
        'concluidos' => (int)($r['concluidos'] ?? 0),
    ];
}

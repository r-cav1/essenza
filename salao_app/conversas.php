<?php
/**
 * conversas.php — mensagens simples vinculadas a um agendamento.
 *
 * Não existe chat global, contatos ou presença online. Cada conversa pertence
 * a um único agendamento e só pode ser acessada pelo cliente autenticado
 * daquele e-mail ou pelo salão dono do agendamento.
 */

function garantirTabelaMensagens(PDO $pdo): void {
    static $pronta = false;
    if ($pronta) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS mensagens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        agendamento_id INT UNSIGNED NOT NULL,
        remetente ENUM('cliente','salao') NOT NULL,
        mensagem VARCHAR(1000) NOT NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
        INDEX idx_mensagens_agendamento (agendamento_id, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pronta = true;
}

function buscarConversaCliente(int $agendamentoId, int $salaoId, string $email): ?array {
    if ($agendamentoId < 1 || $salaoId < 1 || $email === '') return null;

    $pdo = conectar();
    $stmt = $pdo->prepare(
        'SELECT a.id, a.salao_id, a.cliente_nome, a.cliente_email, a.data_hora, a.status,
                s.nome AS servico_nome
         FROM agendamentos a
         JOIN servicos s ON s.id=a.servico_id
         WHERE a.id=? AND a.salao_id=? AND LOWER(a.cliente_email)=?
         LIMIT 1'
    );
    $stmt->execute([$agendamentoId, $salaoId, strtolower($email)]);
    $agendamento = $stmt->fetch();
    return $agendamento ?: null;
}

function listarMensagensAgendamento(int $agendamentoId, int $salaoId): array {
    if ($agendamentoId < 1 || $salaoId < 1) return [];

    $pdo = conectar();
    garantirTabelaMensagens($pdo);
    $stmt = $pdo->prepare(
        'SELECT m.id, m.remetente, m.mensagem, m.criado_em
         FROM mensagens m
         JOIN agendamentos a ON a.id=m.agendamento_id
         WHERE m.agendamento_id=? AND a.salao_id=?
         ORDER BY m.id ASC'
    );
    $stmt->execute([$agendamentoId, $salaoId]);
    return $stmt->fetchAll();
}

function enviarMensagemAgendamento(int $agendamentoId, int $salaoId, string $remetente, string $mensagem): array {
    $mensagem = trim($mensagem);
    if ($agendamentoId < 1 || $salaoId < 1 || !in_array($remetente, ['cliente', 'salao'], true)) {
        return ['erro' => 'Conversa inválida.'];
    }
    if ($mensagem === '') return ['erro' => 'Digite uma mensagem.'];
    if (mb_strlen($mensagem) > 1000) return ['erro' => 'A mensagem pode ter no máximo 1000 caracteres.'];

    $pdo = conectar();
    garantirTabelaMensagens($pdo);
    $stmt = $pdo->prepare('SELECT id FROM agendamentos WHERE id=? AND salao_id=? LIMIT 1');
    $stmt->execute([$agendamentoId, $salaoId]);
    if (!$stmt->fetch()) return ['erro' => 'Agendamento não encontrado.'];

    $stmt = $pdo->prepare('INSERT INTO mensagens (agendamento_id, remetente, mensagem) VALUES (?, ?, ?)');
    $stmt->execute([$agendamentoId, $remetente, $mensagem]);
    return ['sucesso' => true];
}

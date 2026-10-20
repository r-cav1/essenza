-- ================================================================
--  banco.sql — Schema completo do Essenza
--  Versão: Alpha 1.9
--
--  INSTALAÇÃO NOVA:
--    1. Abra http://localhost/phpmyadmin
--    2. Clique em "Importar"
--    3. Selecione este arquivo e execute
--
--  BANCO JÁ EXISTENTE:
--    O arquivo também contém migrações compatíveis com as versões
--    anteriores. Os dados de demonstração não são duplicados ao
--    importar o arquivo novamente.
-- ================================================================

CREATE DATABASE IF NOT EXISTS agenda_salao
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE agenda_salao;

-- Cada linha representa um salão.
CREATE TABLE IF NOT EXISTS saloes (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug                 VARCHAR(100) NOT NULL UNIQUE,
    nome_salao           VARCHAR(200) NOT NULL,

    cor_primaria         VARCHAR(7)   NOT NULL DEFAULT '#4a8c6e',
    cor_secundaria       VARCHAR(7)   NOT NULL DEFAULT '#e8f5ee',
    cor_texto            VARCHAR(7)   NOT NULL DEFAULT '#1a2e25',
    fonte_titulo         VARCHAR(100) NOT NULL DEFAULT 'Cormorant Garamond',
    fonte_corpo          VARCHAR(100) NOT NULL DEFAULT 'DM Sans',
    logo_url             VARCHAR(500) DEFAULT NULL,
    banner_url           VARCHAR(500) DEFAULT NULL,

    descricao            TEXT         DEFAULT NULL,
    whatsapp             VARCHAR(20)  DEFAULT NULL,
    email_contato        VARCHAR(200) DEFAULT NULL,
    email_notificacao    VARCHAR(200) DEFAULT NULL,
    endereco             VARCHAR(300) DEFAULT NULL,

    horario_abertura     TIME         NOT NULL DEFAULT '09:00:00',
    horario_fechamento   TIME         NOT NULL DEFAULT '18:00:00',
    dias_funcionamento   VARCHAR(20)  NOT NULL DEFAULT '1,2,3,4,5',
    intervalo_minutos    TINYINT      NOT NULL DEFAULT 60,

    senha_painel         VARCHAR(255) NOT NULL,
    modo_dev             TINYINT(1)   NOT NULL DEFAULT 1,
    show_onboarding      TINYINT(1)   NOT NULL DEFAULT 1,

    ob_titulo1           VARCHAR(200) DEFAULT NULL,
    ob_texto1            VARCHAR(300) DEFAULT NULL,
    ob_titulo2           VARCHAR(200) DEFAULT NULL,
    ob_texto2            VARCHAR(300) DEFAULT NULL,
    ob_titulo3           VARCHAR(200) DEFAULT NULL,
    ob_texto3            VARCHAR(300) DEFAULT NULL,

    home_titulo          VARCHAR(120) DEFAULT NULL,
    home_subtitulo       VARCHAR(200) DEFAULT NULL,

    criado_em            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Serviços oferecidos por cada salão.
CREATE TABLE IF NOT EXISTS servicos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salao_id      INT UNSIGNED NOT NULL,
    nome          VARCHAR(200) NOT NULL,
    descricao     TEXT         DEFAULT NULL,
    duracao_min   SMALLINT     NOT NULL DEFAULT 60,
    preco         DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    ativo         TINYINT(1)   NOT NULL DEFAULT 1,
    icone         VARCHAR(30)  DEFAULT NULL,
    ordem         TINYINT      NOT NULL DEFAULT 0,
    criado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (salao_id) REFERENCES saloes(id) ON DELETE CASCADE
);

-- Solicitações e atendimentos agendados pelos clientes.
CREATE TABLE IF NOT EXISTS agendamentos (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salao_id            INT UNSIGNED NOT NULL,
    servico_id          INT UNSIGNED NOT NULL,

    cliente_nome        VARCHAR(200) NOT NULL,
    cliente_tel         VARCHAR(30)  DEFAULT NULL,
    cliente_email       VARCHAR(200) DEFAULT NULL,

    data_hora           DATETIME     NOT NULL,
    duracao_min         SMALLINT     NOT NULL,

    codigo_verificacao  VARCHAR(255) DEFAULT NULL,
    codigo_expira_em    DATETIME     DEFAULT NULL,
    tentativas          TINYINT      NOT NULL DEFAULT 0,
    reenvios_codigo     TINYINT      NOT NULL DEFAULT 0,

    status              ENUM('pendente','confirmado','cancelado','concluido') NOT NULL DEFAULT 'pendente',
    token_cliente       CHAR(40)     NOT NULL,
    observacoes         TEXT         DEFAULT NULL,

    criado_em           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (salao_id)   REFERENCES saloes(id)   ON DELETE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE RESTRICT,

    INDEX idx_salao_data (salao_id, data_hora),
    INDEX idx_token      (token_cliente),
    INDEX idx_status     (status)
);


-- ================================================================
--  MIGRAÇÕES
--  Mantêm bancos de versões anteriores compatíveis com a atual.
-- ================================================================
ALTER TABLE servicos ADD COLUMN IF NOT EXISTS icone VARCHAR(30) DEFAULT NULL;

ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS reenvios_codigo TINYINT NOT NULL DEFAULT 0;
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;
ALTER TABLE agendamentos MODIFY COLUMN status ENUM('pendente','confirmado','cancelado','concluido') NOT NULL DEFAULT 'pendente';

ALTER TABLE saloes ADD COLUMN IF NOT EXISTS modo_dev        TINYINT(1)   NOT NULL DEFAULT 1;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS show_onboarding TINYINT(1)   NOT NULL DEFAULT 1;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS ob_titulo1      VARCHAR(200) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS ob_texto1       VARCHAR(300) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS ob_titulo2      VARCHAR(200) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS ob_texto2       VARCHAR(300) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS ob_titulo3      VARCHAR(200) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS ob_texto3       VARCHAR(300) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS home_titulo     VARCHAR(120) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS home_subtitulo  VARCHAR(200) DEFAULT NULL;
ALTER TABLE saloes ADD COLUMN IF NOT EXISTS email_notificacao VARCHAR(200) DEFAULT NULL;


-- ================================================================
--  DADOS DE DEMONSTRAÇÃO
--
--  Painel:
--    Identificador: studio-demo
--    Senha:         demo123
--
--  Cliente:
--    http://localhost/salao/?salao=studio-demo
-- ================================================================
INSERT INTO saloes (
    slug, nome_salao,
    cor_primaria, cor_secundaria, cor_texto,
    fonte_titulo, fonte_corpo,
    descricao, whatsapp, email_contato,
    horario_abertura, horario_fechamento,
    dias_funcionamento, intervalo_minutos,
    senha_painel
)
SELECT
    'studio-demo',
    'Studio Demo',
    '#4a8c6e', '#e8f5ee', '#1a2e25',
    'Cormorant Garamond', 'DM Sans',
    'Bem-vindo ao Studio Demo. Beleza com cuidado e atenção.',
    '5511999990000', 'contato@studiodemo.com.br',
    '09:00:00', '18:00:00',
    '1,2,3,4,5', 60,
    '$2y$10$lSFHmlgygbCF56vSlkkxhe9.ggAIE6sFQjeDOAKZWq9uSYV3BhAxm'
WHERE NOT EXISTS (
    SELECT 1 FROM saloes WHERE slug = 'studio-demo'
);

INSERT INTO servicos (salao_id, nome, descricao, duracao_min, preco, ordem)
SELECT s.id, d.nome, d.descricao, d.duracao_min, d.preco, d.ordem
FROM saloes s
JOIN (
    SELECT 'Corte feminino' AS nome, 'Inclui lavagem e finalização' AS descricao, 60 AS duracao_min, 85.00 AS preco, 1 AS ordem
    UNION ALL SELECT 'Corte masculino', 'Corte e finalização', 30, 45.00, 2
    UNION ALL SELECT 'Coloração', 'Tintura completa com hidratação', 120, 180.00, 3
    UNION ALL SELECT 'Manicure', 'Unhas das mãos', 40, 35.00, 4
    UNION ALL SELECT 'Pedicure', 'Unhas dos pés', 50, 40.00, 5
    UNION ALL SELECT 'Hidratação capilar', 'Tratamento profundo', 60, 90.00, 6
) d
WHERE s.slug = 'studio-demo'
  AND NOT EXISTS (
      SELECT 1
      FROM servicos existente
      WHERE existente.salao_id = s.id
        AND existente.nome = d.nome
  );

-- Mensagens simples entre cliente e salão, sempre ligadas a um agendamento.
CREATE TABLE IF NOT EXISTS mensagens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id  INT UNSIGNED NOT NULL,
    remetente       ENUM('cliente','salao') NOT NULL,
    mensagem        VARCHAR(1000) NOT NULL,
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    INDEX idx_mensagens_agendamento (agendamento_id, id)
);

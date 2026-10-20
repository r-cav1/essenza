# Essenza — Alpha 1.9

Essenza é um sistema web de agendamento para salões, desenvolvido em PHP e MySQL como projeto acadêmico/TCC. O cliente utiliza uma página pública para escolher serviço, data e horário, confirmar a reserva e gerenciar o cancelamento. O proprietário utiliza um painel protegido para administrar a agenda, serviços, horários e aparência da página pública.

A proposta é manter uma implementação simples de estudar e apresentar: PHP procedural, PDO, HTML/CSS/JavaScript e XAMPP, sem frameworks ou abstrações desnecessárias.

## Estrutura

O ZIP deve ser extraído dentro de `C:\xampp`:

```text
C:\xampp\
├── htdocs\
│   └── salao\
│       ├── index.php
│       ├── cliente.php
│       ├── cancelar.php
│       ├── meus-agendamentos.php
│       ├── conversa.php
│       ├── api\
│       ├── painel\
│       ├── includes\
│       └── assets\
└── salao_app\
    ├── bootstrap.php
    ├── config.php
    ├── banco.php
    ├── seguranca.php
    ├── agendamentos.php
    ├── clientes.php
    ├── conversas.php
    ├── cores.php
    ├── notificacoes.php
    ├── testar_smtp.php
    ├── config\email.php
    ├── logs\
    └── database\banco.sql
```

A lógica sensível fica em `salao_app`, fora do `htdocs`. Os endpoints públicos em `api/` são pontos de entrada pequenos que chamam as regras internas.

## Instalação

1. Extraia o ZIP em `C:\xampp`.
2. Inicie Apache e MySQL no XAMPP.
3. Em uma instalação nova, importe `C:\xampp\salao_app\database\banco.sql` pelo phpMyAdmin.
4. Abra `http://localhost/salao/?salao=studio-demo`.
5. Para o painel, abra `http://localhost/salao/painel/login.php`.

Conta de demonstração:

```text
Identificador: studio-demo
Senha: demo123
```

O SQL contém o schema atual, migrações necessárias e dados de demonstração preparados para não serem duplicados em uma segunda importação.

## Funcionalidades

### Cliente

- listagem de serviços;
- horários calculados conforme funcionamento, duração e conflitos;
- criação do agendamento;
- confirmação por código enviado por e-mail, com expiração e limites de tentativa/reenvio;
- telefone opcional;
- observações;
- e-mail de confirmação com resumo e link individual de gerenciamento/cancelamento no modo SMTP real;
- cancelamento por token;
- página pública e onboarding personalizáveis pelo salão;
- área **Meus agendamentos** com acesso temporário por e-mail;
- conversa de texto simples vinculada a cada agendamento.

### Proprietário

- login protegido;
- indicadores da agenda;
- busca, filtros e paginação de agendamentos;
- detalhes e observações;
- conclusão e cancelamento de atendimentos;
- cadastro e edição de serviços;
- configuração de dias e horários de funcionamento;
- personalização de cores, imagens, contatos e textos públicos;
- resposta às mensagens do cliente dentro dos detalhes do agendamento.

## Banco de dados

O banco padrão é `agenda_salao`. As tabelas principais são `saloes`, `servicos`, `agendamentos` e `mensagens`. A tabela `mensagens` guarda somente textos ligados a um agendamento; ela também é criada automaticamente no primeiro uso da conversa para facilitar a atualização de instalações existentes.

O schema e as migrações ficam em:

```text
C:\xampp\salao_app\database\banco.sql
```

Identificadores técnicos antigos, como `agenda_salao` e variáveis `AGENDA_*`, foram mantidos por compatibilidade após a mudança do nome do produto para Essenza.

## E-mail

O SMTP é opcional e permanece desativado por padrão. A configuração fica fora do `htdocs`:

```text
C:\xampp\salao_app\config\email.php
```

Para ativar o envio real, configure o servidor SMTP, altere `ativo` para `true`, execute `testar_smtp.php` e depois desative **Modo de teste** no painel do salão. O passo a passo está em `CONFIGURAR_EMAIL.md`.

O e-mail é obrigatório para o cliente porque é o canal de confirmação; o telefone é opcional. Depois da confirmação, o cliente recebe o resumo do agendamento e o salão recebe a notificação correspondente.

O salão pode informar um e-mail privado de notificações. Se esse campo ficar vazio, o Essenza usa automaticamente o e-mail de contato. Os resultados dos envios são registrados em `salao_app/logs/email.log` sem gravar senha SMTP, código de confirmação, corpo da mensagem ou token do cliente.

## Segurança

O projeto utiliza PDO com prepared statements, CSRF nas ações protegidas, regeneração de sessão após login, isolamento por `salao_id`, validação server-side de horários, validação de uploads por MIME, códigos de confirmação armazenados como hash e limites de tentativas/reenvios.

A limpeza de entrada e o escape de saída HTML são tratados separadamente. Configurações sensíveis permanecem fora do `htdocs`.

## Testes e documentação

Arquivos de apoio do pacote:

- `INSTALAR_NO_XAMPP.txt`: instalação rápida;
- `CONFIGURAR_EMAIL.md`: configuração e teste do SMTP;
- `ROTEIRO_DE_TESTES.md`: testes manuais e resultados esperados;
- `AGENTS.md`: contexto técnico para manutenção e continuidade do projeto;
- `CHANGELOG.md`: histórico resumido das versões;
- `VERSAO.txt`: identificação da versão do pacote.

Antes de avançar uma versão, execute o roteiro de testes e valide a sintaxe dos arquivos PHP modificados com `php -l`.

## Estado atual

A **Alpha 1.9** mantém a área **Meus agendamentos** sem contas e adiciona uma conversa simples vinculada a cada agendamento. O cliente usa o mesmo acesso temporário por e-mail para enviar mensagens, enquanto o salão responde pela tela de detalhes no painel. Não há chat global, anexos, WebSocket ou novos dados de cadastro.

A etapa de SMTP da série 1.7 permanece preservada: código de confirmação, aviso ao salão e confirmação ao cliente continuam independentes, com fallback do e-mail de notificações e diagnóstico em log.

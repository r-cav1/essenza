# Essenza — Contexto para agentes de IA

## Projeto

Essenza é um sistema web de agendamento para salões desenvolvido como projeto acadêmico/TCC. Clientes fazem agendamentos por uma página pública e o proprietário gerencia serviços, horários, aparência e atendimentos por um painel protegido.

Versão deste pacote: **Alpha 1.9**.

## Tecnologias e estilo

- PHP procedural, compatível com PHP 7.4+;
- MySQL/MariaDB;
- XAMPP como ambiente local principal;
- HTML, CSS e JavaScript sem framework;
- PDO com prepared statements.

Não introduzir framework, MVC, classes, Composer ou abstrações grandes sem necessidade clara. O código deve continuar simples de estudar, testar e explicar em um TCC. Comentários devem explicar decisões e regras importantes, não narrar cada linha.

## Estrutura

```text
C:\xampp\
├── htdocs\salao\   # parte pública
└── salao_app\       # código interno fora do DocumentRoot
```

### Parte pública

`htdocs/salao` contém páginas, painel, assets e endpoints HTTP. Os arquivos em `api/` devem permanecer pequenos; regras de negócio importantes ficam na parte interna.

### Parte interna

- `bootstrap.php`: carrega os componentes internos;
- `config.php`: banco, URL base, caminhos e timezone;
- `banco.php`: conexão PDO;
- `seguranca.php`: sessão, CSRF, validação e escape;
- `agendamentos.php`: disponibilidade e ciclo dos agendamentos;
- `clientes.php`: acesso temporário do cliente aos próprios agendamentos;
- `conversas.php`: mensagens simples vinculadas aos agendamentos;
- `cores.php`: cores derivadas do tema;
- `notificacoes.php`: envio SMTP e diagnóstico de e-mail;
- `config/email.php`: configuração SMTP fora do `htdocs`;
- `database/banco.sql`: schema, migrações e dados de demonstração.

## Banco de dados

Banco padrão: `agenda_salao`.

Tabelas principais:
- `saloes`: salão, tema, contatos, horários e configurações;
- `servicos`: serviços de cada salão;
- `agendamentos`: cliente, serviço, data/hora, confirmação, observações, token e status;
- `mensagens`: textos enviados por cliente ou salão, sempre ligados a `agendamento_id`.

Fluxo de status:

```text
pendente -> confirmado -> concluido
    |            |
    +------------+-> cancelado
```

Não permitir transições sem sentido, como `cancelado -> concluido`. Toda consulta administrativa de agendamento deve considerar o `salao_id` da sessão; nunca confiar apenas no `id` recebido pela URL.

## Fluxo público atual

1. Cliente abre a URL do salão.
2. Escolhe serviço, data e horário disponível.
3. Informa nome, e-mail, telefone opcional e observações opcionais.
4. É criado um agendamento pendente com código de confirmação.
5. Após validar o código, o status passa para `confirmado`.
6. No SMTP real, o cliente recebe o resumo/link e o salão recebe o aviso.
7. O cliente pode cancelar usando seu `token_cliente`.
8. Em **Meus agendamentos**, o cliente pode autenticar o e-mail por código temporário e conversar com o salão dentro de cada agendamento.

Reservas pendentes expiram e não devem bloquear horários indefinidamente.

## Painel atual

O proprietário pode entrar com slug e senha, consultar indicadores, buscar e filtrar agendamentos, abrir detalhes, ler observações, concluir/cancelar atendimentos, gerenciar serviços, configurar funcionamento e personalizar a página pública.

## E-mail

O SMTP é opcional e fica desativado por padrão. A configuração está em `salao_app/config/email.php`. Nunca mover credenciais para a pasta pública nem incluí-las em versões distribuídas.

O envio é centralizado em `salao_app/notificacoes.php`; regras de agendamento apenas chamam `enviarEmailSistema()`. `salao_app/testar_smtp.php` permite teste manual via CLI.

Há duas condições para envio real: SMTP global configurado com `ativo => true` e `modo_dev = 0` para o salão. Caso contrário, o fluxo de teste permanece disponível.

Regras atuais:
- e-mail do cliente é obrigatório e usado para confirmação;
- telefone é opcional e não autentica o cliente;
- `email_contato` é o endereço público do salão;
- `email_notificacao` é opcional e privado;
- para avisos internos, usar `email_notificacao` quando preenchido e, caso contrário, `email_contato`;
- falha no aviso ao salão não deve impedir a tentativa de confirmação ao cliente, e vice-versa;
- `logs/email.log` pode registrar tipo, destino, resultado e erro técnico, mas nunca senha SMTP, código de confirmação, corpo da mensagem ou token do cliente.

## Segurança a preservar

- prepared statements;
- CSRF nas ações autenticadas;
- regeneração de sessão no login;
- isolamento por `salao_id`;
- validação server-side dos horários;
- validação de uploads por MIME;
- códigos de confirmação armazenados como hash;
- limites de tentativas e reenvios;
- escape de saída HTML separado da limpeza de entrada;
- lógica/configurações sensíveis fora do `htdocs`.

## Regras para alterações

- Fazer mudanças pequenas e testáveis.
- Evitar reescrever arquivos estáveis sem necessidade.
- Não alterar schema sem incluir migração correspondente em `banco.sql`.
- Manter instalação nova e atualização de banco antigo funcionando.
- Preservar URLs públicas em `/salao/`.
- Não adicionar dependências externas sem benefício claro.
- Preferir formulários e páginas simples a interfaces excessivamente complexas.
- Executar `ROTEIRO_DE_TESTES.md` e `php -l` antes de empacotar uma versão.
- Atualizar `README.md`, `CHANGELOG.md`, `VERSAO.txt` e este arquivo quando uma versão mudar comportamento ou arquitetura.

## Identidade e compatibilidade

Nome do sistema: **Essenza**. Evitar o nome antigo “Agenda Salão” em textos apresentados ao usuário. Identificadores técnicos antigos, como `agenda_salao` e variáveis `AGENDA_*`, permanecem por compatibilidade.

## Estado da Alpha 1.9

A Alpha 1.9 mantém **Meus agendamentos** sem conta tradicional e adiciona uma conversa simples por agendamento. O cliente usa a sessão temporária já existente; o salão responde na tela de detalhes do agendamento. As mensagens ficam em `mensagens`, sempre ligadas a `agendamento_id`, e a lógica fica em `salao_app/conversas.php`. Não há chat global, WebSocket, anexos, presença online ou contas adicionais.

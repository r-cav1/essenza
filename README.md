# Essenza

O **Essenza** é um sistema web de agendamento para salões de beleza, desenvolvido como projeto acadêmico. Seu objetivo é facilitar a organização dos horários e permitir que clientes realizem agendamentos de forma simples e prática.

O sistema oferece uma página personalizada para cada salão, além de um painel administrativo para gerenciar serviços, horários e agendamentos.

## Funcionalidades

- Agendamento online de serviços.
- Confirmação de agendamentos por e-mail.
- Consulta e cancelamento de agendamentos.
- Área do cliente com acesso temporário por e-mail.
- Conversas entre cliente e salão vinculadas aos agendamentos.
- Painel administrativo para gerenciamento de serviços e reservas.
- Personalização de cores, identidade visual e informações do salão.
- Suporte a múltiplos salões.

## Tecnologias utilizadas

- PHP
- MySQL
- HTML
- CSS
- JavaScript

## Instalação

O projeto pode ser executado localmente utilizando o XAMPP.

1. Copie a pasta `htdocs/salao` para `C:\xampp\htdocs\salao`.
2. Copie a pasta `salao_app` para `C:\xampp\salao_app`.
3. Inicie o Apache e o MySQL pelo XAMPP.
4. Crie o banco de dados e importe o arquivo `salao_app/database/banco.sql` pelo phpMyAdmin.
5. Confira as configurações de conexão em `salao_app/config.php`.
6. Acesse `http://localhost/salao/` pelo navegador.

Para configurar o envio de e-mails, consulte o arquivo `CONFIGURAR_EMAIL.md`.

## Estrutura do projeto

```text
htdocs/
└── salao/          # Páginas e arquivos públicos

salao_app/          # Configurações e lógica interna

README.md
AGENTS.md
CHANGELOG.md
```

## Sobre o projeto

O Essenza está em desenvolvimento e faz parte de um trabalho acadêmico voltado à aplicação prática de conceitos de desenvolvimento web, banco de dados e organização de software.

**Versão atual:** 1.9.3

# Essenza Alpha 1.7.5 — Configurar e-mail SMTP

A Alpha 1.7.5 continua em **modo de teste por padrão**. Não é necessário configurar e-mail para usar o sistema no XAMPP.

## Onde configurar

Edite somente este arquivo:

```text
C:\xampp\salao_app\config\email.php
```

Ele fica fora do `htdocs`, portanto não pode ser aberto diretamente pelo navegador.

## 1. Preencha os dados do seu provedor

No arquivo, informe:

- `host`: servidor SMTP;
- `porta`: normalmente 587 para TLS ou 465 para SSL;
- `seguranca`: `tls`, `ssl` ou vazio;
- `usuario`: conta usada para enviar;
- `senha`: senha SMTP ou senha de aplicativo fornecida pelo provedor;
- `remetente_email`: endereço que aparecerá como remetente;
- `remetente_nome`: nome exibido no e-mail.

Não use a senha de uma conta pessoal no projeto. Para testes, prefira uma conta criada especificamente para o Essenza e o método de autenticação recomendado pelo provedor.

## 2. Ative o SMTP

Depois de preencher os campos, altere:

```php
'ativo' => false,
```

para:

```php
'ativo' => true,
```

Esse é o primeiro gatilho. Se estiver `false`, o Essenza sempre continua no comportamento de teste.

## 3. Teste antes de usar no cliente

Abra o Prompt de Comando e execute:

```text
C:\xampp\php\php.exe C:\xampp\salao_app\testar_smtp.php seu-email@exemplo.com
```

Se o e-mail chegar, a configuração básica está funcionando.

## 4. Ative o envio real para o salão

No painel do proprietário, abra **Aparência > Configurações do cliente** e desative **Modo de teste**.

Esse é o segundo gatilho.

O envio real só acontece quando as duas condições são verdadeiras:

```text
SMTP ativo no arquivo + Modo de teste desativado no painel
```

Se o SMTP estiver desligado ou incompleto, o Essenza mantém o código visível na tela. Isso evita que uma instalação nova pare de funcionar por falta de configuração de e-mail.

## Para voltar ao modo antigo

Basta fazer uma destas duas coisas:

- ativar novamente **Modo de teste** no painel; ou
- trocar `'ativo' => true` para `'ativo' => false` em `config/email.php`.

Nenhuma dessas mudanças apaga agendamentos ou altera o banco.

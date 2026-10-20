# Essenza — Roteiro de testes

Versão de referência: **Alpha 1.9**

Este roteiro serve para testar uma versão antes de continuar o desenvolvimento. Faça os testes em uma instalação separada quando quiser preservar dados importantes.

## 1. Instalação limpa

1. Inicie Apache e MySQL pelo XAMPP.
2. No phpMyAdmin, remova o banco `agenda_salao` caso esteja fazendo um teste totalmente limpo.
3. Importe `C:\xampp\salao_app\database\banco.sql`.
4. Confirme que existem as tabelas `saloes`, `servicos` e `agendamentos`.
5. Abra `http://localhost/salao/?salao=studio-demo`.
6. Confirme que os serviços de demonstração aparecem.
7. Abra `http://localhost/salao/painel/login.php` e entre com `studio-demo` / `demo123`.

**Resultado esperado:** banco criado, página do cliente funcionando e login aceito.

## 2. Importação repetida do SQL

1. Com o banco já criado, importe `banco.sql` novamente.
2. Confira o salão `studio-demo` e os serviços.

**Resultado esperado:** a importação termina sem recriar o salão de demonstração nem duplicar seus serviços.

## 3. Fluxo de agendamento

1. Abra a página do cliente.
2. Escolha um serviço.
3. Selecione uma data e um horário disponível.
4. Preencha nome, e-mail e uma observação curta. Deixe o telefone vazio neste primeiro teste.
5. Finalize o pedido.
6. Em modo de desenvolvimento, use o código mostrado na tela para confirmar.

**Resultado esperado:** o telefone pode ficar vazio, o agendamento passa de `pendente` para `confirmado` e o horário deixa de aparecer como disponível para um agendamento conflitante.

## 4. Validações de horário

Tente agendar:
- em um dia em que o salão não funciona;
- antes da abertura ou depois do fechamento;
- em um horário que já está ocupado;
- um serviço cuja duração ultrapasse o fechamento.

**Resultado esperado:** nenhuma dessas tentativas deve criar um agendamento válido.

## 5. Painel e gerenciamento

1. Entre no painel.
2. Confira os indicadores da página inicial.
3. Pesquise um cliente pelo nome.
4. Teste os filtros de período e status.
5. Abra os detalhes de um agendamento.
6. Confira nome, contato, serviço, data, horário e observações.
7. Marque um agendamento confirmado como concluído.
8. Cancele outro agendamento pelo painel.

**Resultado esperado:** busca e filtros retornam somente registros do salão atual; as alterações de status aparecem corretamente.

## 6. Cancelamento pelo cliente

1. Crie e confirme um novo agendamento.
2. Abra a página de gerenciamento usando o token/link do cliente.
3. Cancele o atendimento.

**Resultado esperado:** o status passa para `cancelado` e o horário volta a ficar disponível quando aplicável.

## 7. Serviços e horários

1. Crie um serviço novo.
2. Edite nome, duração e preço.
3. Desative um serviço e confirme que ele não aparece para o cliente.
4. Altere abertura, fechamento, dias de funcionamento e intervalo.
5. Volte à página pública e consulte os horários.

**Resultado esperado:** as mudanças do painel são refletidas na página do cliente sem quebrar agendamentos existentes.

## 8. Aparência

1. Altere as três cores do tema.
2. Altere título e texto da tela inicial.
3. Altere os textos de onboarding.
4. Salve e recarregue a página.
5. Teste também logo/banner, caso queira validar uploads.

**Resultado esperado:** configurações permanecem salvas após recarregar e a interface continua legível.

## 9. Segurança básica entre salões

Este teste exige dois salões cadastrados.

1. Entre no painel do primeiro salão.
2. Copie o ID de um agendamento pertencente ao segundo salão.
3. Tente abrir `painel/agendamento.php?id=ID` usando a sessão do primeiro.

**Resultado esperado:** o agendamento do outro salão não deve ser exibido nem alterado.

## 10. Responsividade

Teste pelo menos as larguras aproximadas de 360 px, 768 px e desktop.

Confira principalmente:
- página do cliente;
- login;
- dashboard;
- detalhes do agendamento;
- serviços;
- horários;
- aparência.

**Resultado esperado:** sem rolagem horizontal inesperada, campos cortados ou textos importantes sobrepostos.

## Registro rápido

Ao encontrar um problema, anote:

```text
Página:
Ação realizada:
Resultado obtido:
Resultado esperado:
Mensagem de erro (se houver):
```

Isso facilita reproduzir e corrigir o erro na próxima revisão.


## 11. E-mail e modo de teste (Alpha 1.7.5)

### 11.1 Instalação padrão, sem SMTP

1. Confirme que `C:\xampp\salao_app\config\email.php` está com `'ativo' => false`.
2. No painel, deixe **Modo de teste** ativado.
3. Faça um novo agendamento.
4. Resultado esperado: o código aparece na tela e nenhuma configuração SMTP é exigida.

### 11.2 Proteção quando SMTP está desligado

1. Mantenha `'ativo' => false`.
2. Desative **Modo de teste** no painel.
3. Faça um agendamento.
4. Resultado esperado: o código ainda aparece na tela. O sistema não deve perder a confirmação por causa de SMTP ausente.

### 11.3 Teste do SMTP

1. Preencha `salao_app/config/email.php` e altere `ativo` para `true`.
2. Execute `C:\xampp\php\php.exe C:\xampp\salao_app\testar_smtp.php seu-email@exemplo.com`.
3. Resultado esperado: o terminal informa envio e a mensagem chega ao endereço informado.

### 11.4 Confirmação real

1. Com SMTP testado e ativo, desative **Modo de teste** no painel.
2. Faça um agendamento usando um e-mail válido.
3. Resultado esperado: o código não aparece na página e chega por e-mail.
4. Digite o código recebido.
5. Resultado esperado: o agendamento é confirmado normalmente.
6. Confira a caixa de e-mail usada no agendamento.
7. Resultado esperado: chega um segundo e-mail com o resumo do atendimento e o link individual de gerenciamento/cancelamento.
8. Abra o link e confirme que ele mostra somente aquele agendamento.

### 11.5 Reenvio

1. Crie outro agendamento com SMTP ativo e Modo de teste desativado.
2. Solicite um novo código.
3. Resultado esperado: o novo código chega por e-mail e respeita os limites de reenvio já existentes.


## 12. Telefone opcional (Alpha 1.7.5)

1. Faça um agendamento preenchendo nome e e-mail, mas deixe o telefone vazio.
2. Confirme o código normalmente.
3. Resultado esperado: o agendamento é aceito e confirmado.
4. Faça outro teste informando um telefone válido.
5. Resultado esperado: o telefone é salvo apenas como contato adicional e não muda a verificação por e-mail.
6. Tente enviar o formulário sem e-mail.
7. Resultado esperado: o Essenza não permite continuar, pois o e-mail é o canal de verificação.


## 13. Notificações após a confirmação (Alpha 1.7.5)

1. Configure o SMTP e desative o Modo de teste.
2. Faça um agendamento e confirme o código recebido.
3. Confira o Sandbox/caixa usada nos testes.
4. Resultado esperado: o cliente recebe **Agendamento confirmado**, com o link de gerenciamento.
5. Resultado esperado: o e-mail de contato do salão recebe **Novo agendamento confirmado**, com cliente, serviço, data/hora e contato.
6. Confirme que os dois e-mails aparecem como mensagens separadas.

Se apenas um deles chegar, confira também o log de erros do PHP/Apache. Os envios são independentes e uma falha de e-mail não desfaz um agendamento já confirmado.


## 14. E-mail de notificação do salão (Alpha 1.7.5)

1. Abra **Painel > Aparência > Identidade**.
2. Mantenha um endereço em **E-mail de contato** e informe outro endereço em **E-mail para notificações**.
3. Salve as alterações.
4. Com SMTP ativo e Modo de teste desativado, faça e confirme um novo agendamento.
5. Resultado esperado: o cliente recebe **Agendamento confirmado**.
6. Resultado esperado: o endereço de **E-mail para notificações** recebe **Novo agendamento confirmado**.
7. O endereço público de contato não deve receber esse aviso apenas por estar preenchido.
8. Deixe **E-mail para notificações** vazio e repita o teste. O agendamento deve continuar funcionando; apenas o aviso interno do salão não será enviado.


## 15. Diagnóstico de e-mail (Alpha 1.7.5)

1. Limpe ou renomeie `C:\xampp\salao_app\logs\email.log` antes do teste, se ele já existir.
2. Faça um novo agendamento com SMTP real e modo de teste desativado.
3. Confirme o código recebido.
4. Confira no Mailtrap se chegaram a confirmação do cliente e a notificação do salão.
5. Abra `C:\xampp\salao_app\logs\email.log`.
6. Devem existir registros `codigo_confirmacao`, `notificacao_salao` e `confirmacao_cliente`.
7. Um registro `OK` significa que o servidor SMTP aceitou aquela mensagem. Um registro `ERRO` informa em qual etapa o envio parou.

O log não registra senha SMTP, código de confirmação, corpo da mensagem ou token de gerenciamento.


## 16. Fallback do e-mail de notificações (Alpha 1.7.5)

1. Preencha somente o **E-mail de contato** do salão e deixe **E-mail para notificações** vazio.
2. Faça e confirme um novo agendamento com SMTP real habilitado.
3. Confirme que o salão recebeu **Novo agendamento confirmado** no e-mail de contato.
4. Preencha depois um endereço diferente em **E-mail para notificações**.
5. Faça outro agendamento e confirme que a notificação passou a usar esse segundo endereço.
6. Confirme que o cliente continua recebendo o código e a confirmação normalmente.
7. Confira `C:\xampp\salao_app\logs\email.log` caso algum envio falhe.


## 17. Meus agendamentos (Alpha 1.8)

1. Faça pelo menos um agendamento com um e-mail conhecido e confirme-o.
2. Na página inicial do salão, clique em **Meus agendamentos**.
3. Informe o mesmo e-mail usado no agendamento.
4. Em modo de teste, confirme que o código aparece na tela; com SMTP real, confirme o recebimento do e-mail `acesso_cliente`.
5. Digite um código incorreto e confira a redução das tentativas.
6. Digite o código correto e confirme que a lista mostra apenas os agendamentos daquele e-mail e daquele salão.
7. Confira os estados pendente, confirmado, concluído e cancelado quando existirem.
8. Em um agendamento futuro confirmado, abra **Ver detalhes ou cancelar** e valide o fluxo já existente.
9. Clique em **Sair** e confirme que a lista deixa de ser acessível.
10. Teste outro salão com o mesmo e-mail e confirme que os dados não se misturam.
11. Solicite códigos em sequência e confirme o intervalo mínimo de um minuto.
12. Confirme no `email.log` a entrada `tipo=acesso_cliente` quando SMTP estiver ativo.


## 18. Conversa por agendamento — Alpha 1.9

1. Entre em **Meus agendamentos** com o código temporário.
2. Abra **Conversar com o salão** em um agendamento e envie uma mensagem curta.
3. No painel, abra o mesmo agendamento e confirme que a mensagem aparece.
4. Responda pelo painel e atualize a conversa do cliente.
5. Confirme que a resposta aparece com remetente e horário corretos.
6. Tente abrir manualmente a conversa de um agendamento de outro e-mail ou outro salão; o conteúdo não deve ser exibido.
7. Teste mensagem vazia e texto acima de 1000 caracteres; ambos devem ser recusados.
8. Verifique desktop e celular.

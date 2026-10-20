# Histórico de versões — Essenza

## Alpha 1.9
- Conversa simples entre cliente e salão, vinculada a cada agendamento.
- Cliente conversa pela área **Meus agendamentos** usando a autenticação temporária já existente.
- Salão responde diretamente na tela de detalhes do agendamento.
- Nova tabela `mensagens`, criada também automaticamente no primeiro uso para facilitar atualização de bancos existentes.
- Mensagens limitadas a texto de até 1000 caracteres; sem anexos, WebSocket, status online ou sistema de contas.
- Interface do cliente reutiliza `portal.css`; painel mantém a identidade administrativa existente.

## Alpha 1.8.2
- Unificação visual das páginas públicas auxiliares.
- `Meus agendamentos` e `Gerenciar agendamento` agora usam a mesma tipografia, cartões, botões, formulários e navegação do portal público.
- CSS comum movido para `htdocs/salao/assets/css/portal.css`, reduzindo estilos duplicados e facilitando manutenção.
- Nenhuma alteração no banco de dados ou nas regras de negócio.

# Essenza — Histórico de versões

Este arquivo registra as principais mudanças do projeto. Detalhes de instalação e uso ficam no `README.md` e nos demais documentos específicos.

## Alpha 1.8.1

- refinado o posicionamento de **Meus agendamentos** na página pública;
- ação secundária passou a integrar o cabeçalho da home, sem interromper a hierarquia entre **Agendar agora** e **Nossos Serviços**;
- removida a seta textual da navegação da área do cliente;
- navegação passou a usar o texto simples **Voltar ao início**;
- comportamento responsivo do novo cabeçalho ajustado para telas pequenas.

## Alpha 1.8

- criada a área pública **Meus agendamentos**, sem cadastro de conta;
- acesso do cliente por e-mail + código temporário de 6 dígitos;
- sessão temporária limitada ao salão e ao e-mail confirmado;
- listagem de agendamentos pendentes, confirmados, concluídos e cancelados;
- agendamentos confirmados futuros mantêm acesso ao gerenciamento/cancelamento existente;
- modo de teste mostra o código na tela; com SMTP ativo, o código é enviado por e-mail;
- nenhum dado pessoal novo foi adicionado ao banco.

## Alpha 1.7.5

- e-mail privado de notificações tornou-se opcional;
- quando `email_notificacao` está vazio, avisos ao salão usam `email_contato`;
- diagnóstico dos envios mantido em `salao_app/logs/email.log`;
- documentação consolidada e estrutura antiga vazia de PHPMailer removida.

## Alpha 1.7.4

- adicionado diagnóstico dos envios SMTP em arquivo de log;
- log identifica tipo, destino, resultado e erro técnico sem registrar credenciais, códigos ou tokens.

## Alpha 1.7.3

- separados o e-mail público de contato (`email_contato`) e o e-mail privado para notificações (`email_notificacao`);
- adicionada migração correspondente ao banco.

## Alpha 1.7.2

- envios ao cliente e ao salão passaram a ser tratados de forma independente;
- adicionada nova tentativa curta em falhas de SMTP.

## Alpha 1.7.1

- telefone tornou-se opcional no agendamento;
- e-mail permaneceu obrigatório como canal de confirmação;
- cliente passou a receber confirmação com resumo e link individual de gerenciamento/cancelamento no SMTP real.

## Alpha 1.7

- introduzido envio SMTP opcional;
- configuração de e-mail movida para `salao_app/config/email.php`, fora do `htdocs`;
- mantido modo de teste como padrão;
- criado teste SMTP por linha de comando e documentação específica.

## Alpha 1.6.1

- revisão de estabilidade, banco e documentação;
- migrações atualizadas para os campos existentes e status `concluido`;
- dados de demonstração preparados para reimportação;
- adicionados roteiro de testes e contexto técnico para continuidade do projeto.

## Alpha 1.6

- gerenciamento de agendamentos no painel;
- busca, filtros, indicadores, detalhes e paginação;
- ações de concluir e cancelar com isolamento por salão.

## Alpha 1.5.2

- identidade do sistema consolidada como Essenza;
- ajustes na tela de login e aparência;
- título e subtítulo da página inicial configuráveis;
- melhorias no salvamento e edição das cores.

## Alpha 1.5

- separação entre parte pública (`htdocs/salao`) e lógica interna (`salao_app`);
- endpoints críticos transformados em controladores pequenos;
- regras principais centralizadas em arquivos internos.

## Versões anteriores

As versões anteriores concentraram a base do agendamento público, segurança, cancelamento, onboarding, tema, validações de horário e correções de compatibilidade que deram origem à estrutura atual.

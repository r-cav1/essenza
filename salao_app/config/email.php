<?php
// Configuração de e-mail do Essenza.
// Este arquivo fica fora do htdocs e não é acessível pelo navegador.
//
// Por padrão o SMTP fica DESATIVADO. Assim a instalação continua funcionando
// como nas versões anteriores: o código de confirmação aparece na tela.

return [
    // Troque para true somente depois de preencher e testar os dados abaixo.
    'ativo' => false,

    'host' => 'smtp.exemplo.com',
    'porta' => 587,
    'seguranca' => 'tls', // tls (porta 587), ssl (porta 465) ou '' sem criptografia

    'usuario' => 'seu-email@exemplo.com',
    'senha' => 'SUA_SENHA_OU_SENHA_DE_APP',

    // Normalmente o remetente é o mesmo endereço usado no campo usuario.
    'remetente_email' => 'seu-email@exemplo.com',
    'remetente_nome' => 'Essenza',

    // Tempo máximo para abrir a conexão com o servidor SMTP.
    'timeout' => 15,
];

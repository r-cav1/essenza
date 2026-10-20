<?php
/**
 * includes/icones.php — Lista de ícones disponíveis para serviços
 *
 * Cada ícone tem: slug (chave), label (nome legível) e path (conteúdo SVG).
 * O slug é salvo na coluna "icone" da tabela servicos.
 * NULL = detecta automaticamente pelo nome do serviço (comportamento legado).
 */

define('ICONES_SERVICO', [
    'tesoura' => ['label' => 'Tesoura',   'path' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>'],
    'pessoa'  => ['label' => 'Pessoa',    'path' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
    'mao'     => ['label' => 'Mão',       'path' => '<path d="M18 11V6a2 2 0 0 0-4 0v5"/><path d="M14 10V4a2 2 0 0 0-4 0v6"/><path d="M10 10.5V6a2 2 0 0 0-4 0v8a6 6 0 0 0 6 6h1a6 6 0 0 0 6-6v-3.5a2 2 0 0 0-4 0"/>'],
    'gota'    => ['label' => 'Gota',      'path' => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>'],
    'paleta'  => ['label' => 'Paleta',    'path' => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>'],
    'pincel'  => ['label' => 'Pincel',    'path' => '<path d="M4 4l5 5"/><path d="m9 9 9.354-9.354a1 1 0 0 1 1.415 1.415L10.414 10.83a6 6 0 0 0-1.698 3.397l-.745 4.474 4.474-.745a6 6 0 0 0 3.397-1.698l9.354-9.354"/><path d="m6 6-2.5 7.5"/>'],
    'estrela' => ['label' => 'Estrela',   'path' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
    'coracao' => ['label' => 'Coração',   'path' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>'],
    'relogio' => ['label' => 'Relógio',   'path' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
    'sol'     => ['label' => 'Sol',       'path' => '<circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>'],
    'flor'    => ['label' => 'Flor',      'path' => '<circle cx="12" cy="12" r="3"/><path d="M12 1a3 3 0 0 1 3 3c0 1.5-1.5 3-3 3a3 3 0 0 1-3-3 3 3 0 0 1 3-3z"/><path d="M21 12a3 3 0 0 1-3 3c-1.5 0-3-1.5-3-3a3 3 0 0 1 3-3 3 3 0 0 1 3 3z"/><path d="M12 23a3 3 0 0 1-3-3c0-1.5 1.5-3 3-3a3 3 0 0 1 3 3 3 3 0 0 1-3 3z"/><path d="M3 12a3 3 0 0 1 3-3c1.5 0 3 1.5 3 3a3 3 0 0 1-3 3 3 3 0 0 1-3-3z"/>'],
    'raio'    => ['label' => 'Raio',      'path' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>'],
    'sparkle' => ['label' => 'Sparkle',   'path' => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>'],
    'olho'    => ['label' => 'Olho',      'path' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'],
    'perfume' => ['label' => 'Perfume',   'path' => '<path d="M9 3h6m-3 0v3m-6 0h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1z"/><path d="M9 11h6m-6 4h6"/>'],
]);

/**
 * renderIcone(string $slug) — Retorna o SVG completo de um ícone
 * Usado em cliente.php e painel/servicos.php
 */
function renderIcone(string $slug): string {
    $icones = ICONES_SERVICO;
    $path   = $icones[$slug]['path'] ?? $icones['sparkle']['path'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
         . 'stroke-linecap="round" stroke-linejoin="round" width="24" height="24">'
         . $path . '</svg>';
}

/**
 * iconeParaNome(string $nome) — Detecta ícone pelo nome do serviço (fallback legado)
 * Compatível com PHP 7.4+ (usa strpos em vez de str_contains)
 */
function iconeParaNome(string $nome): string {
    $n = mb_strtolower($nome, 'UTF-8');
    if (strpos($n,'corte') !== false || strpos($n,'cabelo') !== false) return 'tesoura';
    if (strpos($n,'barba') !== false || strpos($n,'masculin') !== false) return 'pessoa';
    if (strpos($n,'manicu') !== false || strpos($n,'pedicure') !== false || strpos($n,'unha') !== false) return 'mao';
    if (strpos($n,'hidrat') !== false || strpos($n,'tratam') !== false) return 'gota';
    if (strpos($n,'color') !== false || strpos($n,'tintura') !== false || strpos($n,'mechas') !== false) return 'paleta';
    if (strpos($n,'escova') !== false || strpos($n,'progressiva') !== false) return 'pincel';
    return 'sparkle';
}

/**
 * getIconeServico(array $servico) — Retorna o SVG para um serviço
 * Usa o ícone salvo no banco se existir, senão detecta pelo nome
 */
function getIconeServico(array $servico): string {
    $slug = !empty($servico['icone']) ? $servico['icone'] : iconeParaNome($servico['nome']);
    return renderIcone($slug);
}

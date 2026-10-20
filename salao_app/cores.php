<?php
// Funções usadas para manter contraste entre temas claros e escuros.

function corHexValida(string $hex): bool {
    return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $hex);
}

function corMix(string $a, string $b, float $peso = 0.5): string {
    $a = ltrim($a, '#');
    $b = ltrim($b, '#');
    $peso = max(0, min(1, $peso));
    $rgb = [];

    for ($i = 0; $i < 3; $i++) {
        $valorA = hexdec(substr($a, $i * 2, 2));
        $valorB = hexdec(substr($b, $i * 2, 2));
        $rgb[] = (int)round($valorA * (1 - $peso) + $valorB * $peso);
    }

    return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
}

function corLuma(string $hex): float {
    $hex = ltrim($hex, '#');
    $canais = [];

    for ($i = 0; $i < 3; $i++) {
        $valor = hexdec(substr($hex, $i * 2, 2)) / 255;
        $canais[] = $valor <= 0.03928
            ? $valor / 12.92
            : pow(($valor + 0.055) / 1.055, 2.4);
    }

    return 0.2126 * $canais[0] + 0.7152 * $canais[1] + 0.0722 * $canais[2];
}

function corEscurecer(string $hex, float $fator = 0.18): string {
    return corMix($hex, '#000000', $fator);
}

function corClarear(string $hex, float $fator = 0.88): string {
    return corMix($hex, '#ffffff', $fator);
}

function corContraste(string $hex): string {
    return corLuma($hex) > 0.42 ? '#17201c' : '#ffffff';
}

function paletaTema(string $primaria, string $fundo, string $texto): array {
    $fundoEscuro = corLuma($fundo) < 0.34;

    return [
        'primaria' => $primaria,
        'primaria_escura' => corEscurecer($primaria, 0.20),
        'primaria_suave' => corClarear($primaria, 0.88),
        'fundo' => $fundo,
        'texto' => $texto,
        'superficie' => $fundoEscuro ? corMix($fundo, '#ffffff', 0.10) : corMix($fundo, '#ffffff', 0.82),
        'superficie_2' => $fundoEscuro ? corMix($fundo, '#ffffff', 0.16) : corMix($fundo, '#ffffff', 0.94),
        'borda' => $fundoEscuro ? corMix($fundo, '#ffffff', 0.24) : corMix($fundo, '#000000', 0.11),
        'texto_secundario' => corMix($texto, $fundo, $fundoEscuro ? 0.34 : 0.46),
        'sobre_primaria' => corContraste($primaria),
    ];
}

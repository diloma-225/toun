<?php
function tounMoyenPaiement(?string $mode, string $racine = ''): array
{
    $code = strtoupper(trim((string) $mode));

    $moyens = [
        'WAVE' => ['nom' => 'Wave', 'logo' => 'images/paiement/wave.png'],
        'OM' => ['nom' => 'Orange Money', 'logo' => 'images/paiement/orange-money.png'],
        'MOOV' => ['nom' => 'Moov Money', 'logo' => 'images/paiement/moov-money.png'],
    ];

    if (str_contains($code, 'WAVE')) {
        $moyen = $moyens['WAVE'];
    } elseif ($code === 'OM' || str_contains($code, 'ORANGE')) {
        $moyen = $moyens['OM'];
    } elseif (str_contains($code, 'MOOV')) {
        $moyen = $moyens['MOOV'];
    } else {
        return ['nom' => $code !== '' ? $code : 'Mobile Money', 'logo' => ''];
    }

    $moyen['logo'] = $racine . $moyen['logo'];

    return $moyen;
}

<?php
$chemin = (string) ($_GET['f'] ?? '');
$chemin = str_replace('\\', '/', $chemin);
$chemin = ltrim($chemin, '/');

if ($chemin === '' || str_contains($chemin, '..')) {
    http_response_code(404);
    exit();
}

$dossiersAutorises = [
    'uploads/formations',
    'uploads/profils',
    'uploads/photos',
    'uploads/entreprises',
    'uploads/categories',
    'uploads/lecons',
    'uploads',
    'images/formations',
    'images/profils',
    'images'
];

$autorise = false;

foreach ($dossiersAutorises as $dossier) {
    if ($chemin === $dossier || str_starts_with($chemin, $dossier . '/')) {
        $autorise = true;
        break;
    }
}

if (!$autorise) {
    http_response_code(403);
    exit();
}

$fichier = __DIR__ . '/../' . $chemin;

if (!is_file($fichier)) {
    http_response_code(404);
    exit();
}

$extension = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));

$typesMime = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'mp4' => 'video/mp4',
    'm4v' => 'video/mp4',
    'webm' => 'video/webm',
    'ogg' => 'video/ogg',
    'mov' => 'video/quicktime',
    'mp3' => 'audio/mpeg',
    'pdf' => 'application/pdf'
];

$taille = filesize($fichier);
$debut = 0;
$fin = $taille - 1;

header('Access-Control-Allow-Origin: *');
header('Content-Type: ' . ($typesMime[$extension] ?? 'application/octet-stream'));
header('Cache-Control: public, max-age=86400');
header('Accept-Ranges: bytes');

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $plage)) {
    if ($plage[1] !== '') {
        $debut = (int) $plage[1];
        $fin = $plage[2] !== '' ? min((int) $plage[2], $taille - 1) : $taille - 1;
    } elseif ($plage[2] !== '') {
        $debut = max(0, $taille - (int) $plage[2]);
    }

    if ($debut > $fin || $debut >= $taille) {
        http_response_code(416);
        header('Content-Range: bytes */' . $taille);
        exit();
    }

    http_response_code(206);
    header('Content-Range: bytes ' . $debut . '-' . $fin . '/' . $taille);
}

$longueur = $fin - $debut + 1;
header('Content-Length: ' . $longueur);

$flux = fopen($fichier, 'rb');
fseek($flux, $debut);

while ($longueur > 0 && !feof($flux)) {
    $morceau = fread($flux, (int) min(8192, $longueur));
    echo $morceau;
    flush();
    $longueur -= strlen($morceau);
}

fclose($flux);

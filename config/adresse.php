<?php
function tounAdresseSite(string $secours = ''): string
{
    $hote = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $script = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));

    if ($hote === '' || $script === false) {
        return rtrim($secours, '/');
    }

    $racine = str_replace('\\', '/', dirname(__DIR__));
    $script = str_replace('\\', '/', $script);

    if (stripos($script, $racine . '/') !== 0) {
        return rtrim($secours, '/');
    }

    $relatif = substr($script, strlen($racine));
    $nomScript = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $dossier = substr($nomScript, 0, max(0, strlen($nomScript) - strlen($relatif)));

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    return ($https ? 'https' : 'http') . '://' . $hote . str_replace(' ', '%20', $dossier);
}

<?php
function tounDetecterSysteme(string $userAgent): string
{
    $ua = strtolower($userAgent);

    if (strpos($ua, 'iphone') !== false) {
        return 'iOS';
    }

    if (strpos($ua, 'ipad') !== false) {
        return 'iPadOS';
    }

    if (strpos($ua, 'android') !== false) {
        return 'Android';
    }

    if (strpos($ua, 'windows') !== false) {
        return 'Windows';
    }

    if (strpos($ua, 'mac os') !== false || strpos($ua, 'macintosh') !== false) {
        return 'macOS';
    }

    if (strpos($ua, 'linux') !== false) {
        return 'Linux';
    }

    return 'Inconnu';
}

function tounDetecterNavigateur(string $userAgent): string
{
    $ua = strtolower($userAgent);

    if (strpos($ua, 'edg/') !== false) {
        return 'Microsoft Edge';
    }

    if (strpos($ua, 'opr/') !== false || strpos($ua, 'opera') !== false) {
        return 'Opera';
    }

    if (strpos($ua, 'chrome/') !== false && strpos($ua, 'edg/') === false) {
        return 'Google Chrome';
    }

    if (strpos($ua, 'firefox/') !== false) {
        return 'Mozilla Firefox';
    }

    if (strpos($ua, 'safari/') !== false && strpos($ua, 'chrome/') === false) {
        return 'Safari';
    }

    return 'Navigateur inconnu';
}

function tounDetecterAppareil(string $userAgent): string
{
    $ua = strtolower($userAgent);

    if (strpos($ua, 'ipad') !== false || strpos($ua, 'tablet') !== false) {
        return 'Tablette';
    }

    if (strpos($ua, 'mobile') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'android') !== false) {
        return 'Mobile';
    }

    return 'Ordinateur';
}

function tounEnregistrerConnexion(PDO $con, int $idUser): array
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Inconnue';

    $systeme = tounDetecterSysteme($userAgent);
    $navigateur = tounDetecterNavigateur($userAgent);
    $appareil = tounDetecterAppareil($userAgent);

    $sql = "
        INSERT INTO t_connexion
        (
            id_user,
            adresse_ip,
            appareil,
            systeme,
            navigateur,
            date_connexion,
            statut_connexion
        )
        VALUES (?, ?, ?, ?, ?, NOW(), 'reussie')
    ";

    $stmt = $con->prepare($sql);

    $stmt->execute([$idUser, $ip, $appareil, $systeme, $navigateur]);

    return [
        'id_connexion' => (int) $con->lastInsertId(),
        'ip' => $ip,
        'appareil' => $appareil,
        'systeme' => $systeme,
        'navigateur' => $navigateur,
        'date' => date('d/m/Y à H:i')
    ];
}
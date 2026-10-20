<?php
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../includes/certificats.php';

$jeton = (string) ($_GET['jeton'] ?? '');

$stmt = $con->prepare('SELECT id_user FROM t_api_jetons WHERE jeton = ? AND date_expiration > NOW() LIMIT 1');
$stmt->execute([$jeton]);
$idUser = (int) $stmt->fetchColumn();

$certificat = $idUser > 0 ? tounCertificat($con, (int) ($_GET['id'] ?? 0), $idUser) : null;

if (!$certificat) {
    http_response_code(404);
    echo 'Certificat introuvable.';
    exit();
}

tounAfficherCertificat($certificat);

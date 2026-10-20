<?php
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/certificats.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];
$certificats = [];

if (apiTableExiste($con, 't_certificats')) {
    tounDelivrerCertificats($con, $idUser);

    $s = $con->prepare("
        SELECT c.id_certificat, c.numero_certificat, c.date_obtention,
               f.id_form, f.titre_form, f.img_form, f.niveau_form, f.duree_form
        FROM t_certificats c
        INNER JOIN t_formations f ON f.id_form = c.id_form
        WHERE c.id_user = ?
        ORDER BY c.date_obtention DESC
    ");
    $s->execute([$idUser]);
    $base = tounAdresseSite('http://localhost/application%20toun') . '/api/certificat.php';

    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $certificats[] = [
            'id_certificat' => (int) $ligne['id_certificat'],
            'numero' => (string) ($ligne['numero_certificat'] ?? ''),
            'date' => $ligne['date_obtention'],
            'id_form' => (int) $ligne['id_form'],
            'formation' => $ligne['titre_form'],
            'niveau' => (string) ($ligne['niveau_form'] ?? ''),
            'duree' => (string) ($ligne['duree_form'] ?? ''),
            'image' => apiUrlMedia($ligne['img_form'], ['uploads/formations', 'uploads', 'images/formations', 'images']),
            'apercu' => apiUrlMedia('certificat_miniature.jpg', ['images']),
            'lien' => $base . '?id=' . (int) $ligne['id_certificat'] . '&jeton=' . rawurlencode((string) apiJetonEnvoye())
        ];
    }
}

apiReponse(['certificats' => $certificats]);

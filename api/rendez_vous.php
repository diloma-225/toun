<?php
require __DIR__ . '/_bootstrap.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$con->exec("
    CREATE TABLE IF NOT EXISTS t_rendezvous (
        id_rdv INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_user INT UNSIGNED NOT NULL,
        id_formateur INT UNSIGNED NULL,
        nom_formateur_texte VARCHAR(150) NOT NULL DEFAULT '',
        motif VARCHAR(255) NOT NULL DEFAULT '',
        date_rdv DATE NOT NULL,
        heure_rdv TIME NOT NULL,
        statut_rdv VARCHAR(20) NOT NULL DEFAULT 'en_attente',
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_rdv),
        INDEX idx_rdv_user (id_user)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$formateurs = [];

if (apiTableExiste($con, 't_formateurs') && apiTableExiste($con, 't_utilisateurs')) {
    try {
        $s = $con->query("
            SELECT f.id_formateur, u.prenom_user, u.nom_user
            FROM t_formateurs f
            INNER JOIN t_utilisateurs u ON u.id_user = f.id_user
            ORDER BY u.prenom_user ASC
            LIMIT 50
        ");

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $formateurs[] = [
                'id_formateur' => (int) $ligne['id_formateur'],
                'nom' => trim($ligne['prenom_user'] . ' ' . $ligne['nom_user'])
            ];
        }
    } catch (Throwable $e) {
        $formateurs = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $corps = apiCorpsJson();
    $motif = trim((string) ($corps['motif'] ?? ''));
    $dateRdv = trim((string) ($corps['date_rdv'] ?? ''));
    $heureRdv = trim((string) ($corps['heure_rdv'] ?? ''));
    $idFormateur = (int) ($corps['id_formateur'] ?? 0);
    $nomFormateur = 'Équipe pédagogique TOUN';

    foreach ($formateurs as $formateur) {
        if ($formateur['id_formateur'] === $idFormateur) {
            $nomFormateur = $formateur['nom'];
        }
    }

    if ($motif === '' || $dateRdv === '' || $heureRdv === '') {
        apiErreur('Merci de renseigner le motif, la date et l\'heure souhaités.');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRdv) || !preg_match('/^\d{2}:\d{2}$/', $heureRdv)) {
        apiErreur('La date ou l\'heure n\'est pas valide.');
    }

    if (strtotime($dateRdv) < strtotime(date('Y-m-d'))) {
        apiErreur('La date choisie est déjà passée.');
    }

    $s = $con->prepare('
        INSERT INTO t_rendezvous (id_user, id_formateur, nom_formateur_texte, motif, date_rdv, heure_rdv)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $s->execute([
        $idUser,
        $idFormateur > 0 ? $idFormateur : null,
        $nomFormateur,
        mb_substr($motif, 0, 255),
        $dateRdv,
        $heureRdv
    ]);
}

$s = $con->prepare('SELECT * FROM t_rendezvous WHERE id_user = ? ORDER BY date_rdv DESC, heure_rdv DESC');
$s->execute([$idUser]);
$rendezVous = [];

foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
    $rendezVous[] = [
        'id_rdv' => (int) $ligne['id_rdv'],
        'motif' => $ligne['motif'],
        'formateur' => $ligne['nom_formateur_texte'] ?: 'Équipe pédagogique TOUN',
        'date' => $ligne['date_rdv'],
        'heure' => substr((string) $ligne['heure_rdv'], 0, 5),
        'statut' => strtolower((string) $ligne['statut_rdv'])
    ];
}

apiReponse([
    'rendez_vous' => $rendezVous,
    'formateurs' => $formateurs
]);

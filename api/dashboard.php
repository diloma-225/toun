<?php
require __DIR__ . '/_bootstrap.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$totalFormations = 0;
$totalFormationsDisponibles = 0;
$totalCertificats = 0;
$progressionGlobale = 0;
$formationEnCours = null;

if (apiTableExiste($con, 't_inscriptions') && apiTableExiste($con, 't_formations')) {
    $s = $con->prepare('SELECT COUNT(DISTINCT id_form) FROM t_inscriptions WHERE id_user = ?');
    $s->execute([$idUser]);
    $totalFormations = (int) $s->fetchColumn();

    $s = $con->prepare("
        SELECT
            f.id_form, f.titre_form, f.img_form,
            COALESCE(p.pourcent_avancement, 0) AS progression
        FROM t_inscriptions i
        INNER JOIN t_formations f ON f.id_form = i.id_form
        LEFT JOIN t_progression p ON p.id_user = i.id_user AND p.id_form = i.id_form
        WHERE i.id_user = ? AND i.statut_inscrip = 'active'
        ORDER BY i.date_inscrip_form DESC
        LIMIT 1
    ");
    $s->execute([$idUser]);
    $ligne = $s->fetch(PDO::FETCH_ASSOC);

    if ($ligne) {
        $formationEnCours = [
            'id_form' => (int) $ligne['id_form'],
            'titre' => $ligne['titre_form'],
            'image' => apiUrlMedia($ligne['img_form'], [
                'uploads/formations',
                'uploads',
                'images/formations',
                'images'
            ]),
            'progression' => max(0, min(100, (int) round((float) $ligne['progression'])))
        ];
    }
}

if (apiTableExiste($con, 't_formations')) {
    $s = $con->prepare("SELECT COUNT(*) FROM t_formations WHERE statut_form = 'publie'");
    $s->execute();
    $totalFormationsDisponibles = (int) $s->fetchColumn();
}

if (apiTableExiste($con, 't_certificats')) {
    $s = $con->prepare('SELECT COUNT(*) FROM t_certificats WHERE id_user = ?');
    $s->execute([$idUser]);
    $totalCertificats = (int) $s->fetchColumn();
}

if (apiTableExiste($con, 't_progression')) {
    $s = $con->prepare('SELECT AVG(pourcent_avancement) FROM t_progression WHERE id_user = ?');
    $s->execute([$idUser]);
    $moyenne = $s->fetchColumn();
    $progressionGlobale = $moyenne !== null ? (int) round((float) $moyenne) : 0;
}

$mesFormations = [];

if (apiTableExiste($con, 't_inscriptions') && apiTableExiste($con, 't_formations')) {
    $s = $con->prepare("
        SELECT f.id_form, f.titre_form, f.img_form, c.nom_cat,
               COALESCE(p.pourcent_avancement, 0) AS progression
        FROM t_inscriptions i
        INNER JOIN t_formations f ON f.id_form = i.id_form
        LEFT JOIN t_categories c ON c.id_cat = f.id_cat
        LEFT JOIN t_progression p ON p.id_user = i.id_user AND p.id_form = i.id_form
        WHERE i.id_user = ?
        ORDER BY i.date_inscrip_form DESC
        LIMIT 3
    ");
    $s->execute([$idUser]);

    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $mesFormations[] = [
            'id_form' => (int) $ligne['id_form'],
            'titre' => $ligne['titre_form'],
            'categorie' => $ligne['nom_cat'] ?? 'Formation',
            'image' => apiUrlMedia($ligne['img_form'], ['uploads/formations', 'uploads', 'images/formations', 'images']),
            'progression' => max(0, min(100, (int) round((float) $ligne['progression'])))
        ];
    }
}

$totalCours = 0;

if (apiTableExiste($con, 't_modules') && apiTableExiste($con, 't_lecons') && $totalFormations > 0) {
    $s = $con->prepare("
        SELECT COUNT(*)
        FROM t_lecons l
        INNER JOIN t_modules m ON m.id_module = l.id_module
        INNER JOIN t_inscriptions i ON i.id_form = m.id_form
        WHERE i.id_user = ?
    ");
    $s->execute([$idUser]);
    $totalCours = (int) $s->fetchColumn();
}

$tempsApprentissage = 0;
$colonnesProgression = apiColonnesTable($con, 't_progression');

foreach (['duree_apprentissage', 'temps_apprentissage', 'temps_total'] as $colonne) {
    if (in_array($colonne, $colonnesProgression, true)) {
        $s = $con->prepare("SELECT COALESCE(SUM(`$colonne`), 0) FROM t_progression WHERE id_user = ?");
        $s->execute([$idUser]);
        $tempsApprentissage = (int) $s->fetchColumn();
        break;
    }
}

apiReponse([
    'mes_formations' => $mesFormations,
    'temps_apprentissage' => $tempsApprentissage,
    'total_cours' => $totalCours,
    'utilisateur' => [
        'prenom' => $utilisateur['prenom_user'],
        'nom' => $utilisateur['nom_user'],
        'photo' => apiUrlMedia($utilisateur['photo_user'], ['uploads/profils', 'uploads', 'images/profils', 'images'])
    ],
    'total_formations' => $totalFormations,
    'total_formations_disponibles' => $totalFormationsDisponibles,
    'total_certificats' => $totalCertificats,
    'progression_globale' => $progressionGlobale,
    'formation_en_cours' => $formationEnCours,
    'collaborateur' => apiEstCollaborateur($con, $idUser)
]);
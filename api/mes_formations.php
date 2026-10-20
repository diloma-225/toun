<?php
require __DIR__ . '/_bootstrap.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$s = $con->prepare("
    SELECT
        f.id_form, f.titre_form, f.img_form, f.duree_form,
        c.nom_cat,
        COALESCE(p.pourcent_avancement, 0) AS progression,
        i.statut_inscrip
    FROM t_inscriptions i
    INNER JOIN t_formations f ON f.id_form = i.id_form
    LEFT JOIN t_categories c ON c.id_cat = f.id_cat
    LEFT JOIN t_progression p ON p.id_user = i.id_user AND p.id_form = i.id_form
    WHERE i.id_user = ? AND i.statut_inscrip IN ('active', 'terminee')
    ORDER BY i.date_inscrip_form DESC
");
$s->execute([$idUser]);
$lignes = $s->fetchAll(PDO::FETCH_ASSOC);

$formations = array_map(static function (array $l): array {
    $progression = max(0, min(100, (int) round((float) $l['progression'])));

    if ($progression >= 100) {
        $statut = 'terminee';
    } elseif ($progression > 0) {
        $statut = 'en_cours';
    } else {
        $statut = 'non_commencee';
    }

    return [
        'id_form' => (int) $l['id_form'],
        'titre' => $l['titre_form'],
        'image' => apiUrlMedia($l['img_form'], ['uploads/formations', 'uploads', 'images/formations', 'images']),
        'duree' => $l['duree_form'],
        'categorie' => $l['nom_cat'],
        'progression' => $progression,
        'statut' => $statut
    ];
}, $lignes);

apiReponse(['formations' => $formations]);
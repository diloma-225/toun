<?php
require __DIR__ . '/../_bootstrap.php';

apiUtilisateurCourant($con);

$recherche = trim((string) ($_GET['recherche'] ?? ''));
$categorie = trim((string) ($_GET['categorie'] ?? ''));

$sql = "
    SELECT
        f.id_form, f.titre_form, f.descrip_form, f.img_form,
        f.niveau_form, f.duree_form, f.prix_form,
        c.nom_cat,
        u.nom_user, u.prenom_user
    FROM t_formations f
    LEFT JOIN t_categories c ON c.id_cat = f.id_cat
    LEFT JOIN t_formateurs fo ON fo.id_formateur = f.id_formateur
    LEFT JOIN t_utilisateurs u ON u.id_user = fo.id_user
    WHERE f.statut_form = 'publie'
";
$params = [];

if ($recherche !== '') {
    $sql .= ' AND f.titre_form LIKE ?';
    $params[] = '%' . $recherche . '%';
}

if ($categorie !== '' && strcasecmp($categorie, 'Toutes') !== 0) {
    $sql .= ' AND c.nom_cat = ?';
    $params[] = $categorie;
}

$sql .= ' ORDER BY f.date_creation_form DESC';

$s = $con->prepare($sql);
$s->execute($params);
$formations = $s->fetchAll(PDO::FETCH_ASSOC);

$resultat = array_map(static function (array $f): array {
    return [
        'id_form' => (int) $f['id_form'],
        'titre' => $f['titre_form'],
        'description' => $f['descrip_form'],
        'image' => apiUrlMedia($f['img_form'], ['uploads/formations', 'uploads', 'images/formations', 'images']),
        'niveau' => $f['niveau_form'],
        'duree' => $f['duree_form'],
        'prix' => (float) $f['prix_form'],
        'categorie' => $f['nom_cat'],
        'formateur' => trim((string) ($f['prenom_user'] ?? '') . ' ' . (string) ($f['nom_user'] ?? ''))
    ];
}, $formations);

apiReponse(['formations' => $resultat]);
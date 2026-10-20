<?php
require __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/evaluations.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$idLecon = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idLecon) {
    apiErreur('Leçon invalide.');
}

$s = $con->prepare("
    SELECT l.*, m.id_form, m.titre_module
    FROM t_lecons l
    INNER JOIN t_modules m ON m.id_module = l.id_module
    WHERE l.id_lecon = ?
    LIMIT 1
");
$s->execute([$idLecon]);
$lecon = $s->fetch(PDO::FETCH_ASSOC);

if (!$lecon) {
    apiErreur('Leçon introuvable.', 404);
}

$s = $con->prepare(
    "SELECT id_inscrip FROM t_inscriptions WHERE id_user = ? AND id_form = ? AND statut_inscrip IN ('active', 'terminee') LIMIT 1"
);
$s->execute([$idUser, $lecon['id_form']]);

if (!$s->fetch()) {
    apiErreur('Vous n\'êtes pas inscrit à cette formation.', 403);
}

if (!tounModuleOuvert(tounParcours($con, $idUser, (int) $lecon['id_form']), (int) $lecon['id_module'])) {
    apiErreur('Réussissez le test du module précédent pour débloquer cette leçon.', 403);
}

$type = (string) ($lecon['type_contenu'] ?? 'texte');
$fichier = trim((string) ($lecon['contenu_lecon'] ?? ''));
$texte = trim((string) ($lecon['description_lecon'] ?? ''));
$dossiers = ['uploads/lecons', 'uploads/formations/videos', 'uploads/formations', 'uploads'];

if ($type === 'texte' && $texte === '') {
    $texte = $fichier;
}

$s = $con->prepare("SELECT 1 FROM t_suivi_contenus WHERE id_user = ? AND type_element = 'lecon' AND id_element = ? LIMIT 1");
$s->execute([$idUser, $idLecon]);

apiReponse([
    'lecon' => [
        'id_lecon' => (int) $lecon['id_lecon'],
        'titre' => $lecon['titre_lecon'],
        'module' => $lecon['titre_module'],
        'id_form' => (int) $lecon['id_form'],
        'type' => $type,
        'contenu' => $texte,
        'video' => $type === 'video' ? apiUrlMedia($fichier, $dossiers) : '',
        'document' => $type === 'pdf' ? apiUrlMedia($fichier, $dossiers) : ($type === 'lien' ? $fichier : ''),
        'duree' => $lecon['duree_lecon'] ?? null,
        'terminee' => (bool) $s->fetchColumn()
    ]
]);

<?php
require __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/certificats.php';
require_once __DIR__ . '/../../includes/evaluations.php';
require_once __DIR__ . '/../../includes/ressources.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    apiErreur('Formation invalide.');
}

$s = $con->prepare("
    SELECT
        f.*,
        f.id_form, f.titre_form, f.descrip_form, f.img_form,
        f.niveau_form, f.duree_form, f.prix_form,
        c.nom_cat,
        u.nom_user, u.prenom_user, u.photo_user
    FROM t_formations f
    LEFT JOIN t_categories c ON c.id_cat = f.id_cat
    LEFT JOIN t_formateurs fo ON fo.id_formateur = f.id_formateur
    LEFT JOIN t_utilisateurs u ON u.id_user = fo.id_user
    WHERE f.id_form = ? AND f.statut_form = 'publie'
    LIMIT 1
");
$s->execute([$id]);
$formation = $s->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    apiErreur('Cette formation n\'est plus disponible.', 404);
}

$modules = [];
$parcours = tounParcours($con, $idUser, (int) $id);
$ressourcesParModule = [];

foreach (tounRessourcesFormation($con, (int) $id) as $ressource) {
    $lien = $ressource['lien_ressource'] !== ''
        ? $ressource['lien_ressource']
        : apiUrlMedia($ressource['fichier_ressource'], ['uploads/ressources']);

    if ($lien !== '') {
        $ressourcesParModule[(int) ($ressource['id_module'] ?? 0)][] = [
            'titre' => $ressource['titre_ressource'],
            'type' => $ressource['type_ressource'],
            'lien' => $lien
        ];
    }
}

$s = $con->prepare("SELECT id_element FROM t_suivi_contenus WHERE id_user = ? AND id_form = ? AND type_element = 'lecon'");
$s->execute([$idUser, $id]);
$leconsTerminees = array_flip(array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN)));

if (apiTableExiste($con, 't_modules')) {
    $s = $con->prepare(
        'SELECT id_module, titre_module FROM t_modules WHERE id_form = ? ORDER BY ordre_module ASC, id_module ASC'
    );
    $s->execute([$id]);
    $lignes = $s->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lignes as $ligne) {
        $lecons = [];

        if (apiTableExiste($con, 't_lecons')) {
            $sl = $con->prepare(
                'SELECT id_lecon, titre_lecon, duree_lecon, type_contenu FROM t_lecons WHERE id_module = ? ORDER BY ordre_lecon ASC, id_lecon ASC'
            );
            $sl->execute([$ligne['id_module']]);
            $lecons = array_map(static function (array $l) use ($leconsTerminees): array {
                return [
                    'id_lecon' => (int) $l['id_lecon'],
                    'titre' => $l['titre_lecon'],
                    'duree' => $l['duree_lecon'] ?? null,
                    'type' => $l['type_contenu'] ?? 'texte',
                    'terminee' => isset($leconsTerminees[(int) $l['id_lecon']])
                ];
            }, $sl->fetchAll(PDO::FETCH_ASSOC));
        }

        $test = $parcours['modules'][(int) $ligne['id_module']]['test'] ?? null;

        $modules[] = [
            'id_module' => (int) $ligne['id_module'],
            'titre' => $ligne['titre_module'],
            'ouvert' => tounModuleOuvert($parcours, (int) $ligne['id_module']),
            'lecons' => $lecons,
            'ressources' => array_merge($ressourcesParModule[(int) $ligne['id_module']] ?? [], $ressourcesParModule[0] ?? []),
            'test' => $test ? [
                'id_quiz' => $test['id_quiz'],
                'titre' => $test['titre_quiz'],
                'reussi' => $test['reussi'],
                'disponible' => $test['disponible']
            ] : null
        ];
    }
}

$s = $con->prepare('SELECT id_inscrip, statut_inscrip FROM t_inscriptions WHERE id_user = ? AND id_form = ? LIMIT 1');
$s->execute([$idUser, $id]);
$inscription = $s->fetch(PDO::FETCH_ASSOC);
$estAchete = (bool) $inscription && in_array($inscription['statut_inscrip'], ['active', 'terminee'], true);
$estCollaborateur = apiEstCollaborateur($con, $idUser);

$s = $con->prepare('SELECT pourcent_avancement FROM t_progression WHERE id_user = ? AND id_form = ? LIMIT 1');
$s->execute([$idUser, $id]);
$progression = (int) round((float) $s->fetchColumn());
$coursTermines = tounPourcentCours($con, $idUser, $id) >= 100;

$evaluations = array_map(static fn (array $quiz): array => [
    'id_quiz' => $quiz['id_quiz'],
    'titre' => $quiz['titre_quiz'],
    'reussi' => $quiz['reussi'],
    'disponible' => $quiz['disponible']
], $parcours['finaux']);

$s = $con->prepare('SELECT id_certificat FROM t_certificats WHERE id_user = ? AND id_form = ? LIMIT 1');
$s->execute([$idUser, $id]);
$idCertificat = (int) $s->fetchColumn();

apiReponse([
    'formation' => [
        'id_form' => (int) $formation['id_form'],
        'titre' => $formation['titre_form'],
        'description' => $formation['descrip_form'],
        'video' => apiUrlMedia($formation['video_intro_form'] ?? null, [
            'uploads/formations/videos',
            'uploads/formations',
            'uploads'
        ]),
        'image' => apiUrlMedia($formation['img_form'], [
            'uploads/formations',
            'uploads',
            'images/formations',
            'images'
        ]),
        'niveau' => $formation['niveau_form'],
        'duree' => $formation['duree_form'],
        'prix' => (float) $formation['prix_form'],
        'categorie' => $formation['nom_cat'],
        'formateur' => trim((string) ($formation['prenom_user'] ?? '') . ' ' . (string) ($formation['nom_user'] ?? '')),
        'formateur_photo' => apiUrlMedia($formation['photo_user'], [
            'uploads/photos',
            'uploads/profils',
            'uploads',
            'images/profils',
            'images'
        ]),
        'nombre_modules' => count($modules)
    ],
    'modules' => $modules,
    'deja_achete' => $estAchete,
    'progression' => $progression,
    'cours_termines' => $coursTermines,
    'evaluations' => $evaluations,
    'certificat' => $idCertificat > 0
        ? tounAdresseSite('http://localhost/application%20toun') . '/api/certificat.php?id=' . $idCertificat . '&jeton=' . rawurlencode((string) apiJetonEnvoye())
        : '',
    'collaborateur' => $estCollaborateur
]);
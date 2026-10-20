<?php

require __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/certificats.php';
require_once __DIR__ . '/../../includes/evaluations.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiErreur('Méthode non autorisée.', 405);
}

$corps = apiCorpsJson();
$idForm = (int) ($corps['id_form'] ?? 0);
$idLecon = (int) ($corps['id_lecon'] ?? 0);
$pourcentage = (int) ($corps['pourcentage'] ?? 0);

if ($idForm <= 0 || $pourcentage < 0 || $pourcentage > 100) {
    apiErreur('Données de progression invalides.');
}

$s = $con->prepare("SELECT id_inscrip FROM t_inscriptions WHERE id_user = ? AND id_form = ? AND statut_inscrip IN ('active', 'terminee') LIMIT 1");
$s->execute([$idUser, $idForm]);

if (!$s->fetch()) {
    apiErreur('Vous n\'êtes pas inscrit à cette formation.', 403);
}

if ($idLecon > 0) {
    $s = $con->prepare('
        SELECT l.id_lecon, l.id_module
        FROM t_lecons l
        INNER JOIN t_modules m ON m.id_module = l.id_module
        WHERE l.id_lecon = ? AND m.id_form = ?
        LIMIT 1
    ');
    $s->execute([$idLecon, $idForm]);
    $lecon = $s->fetch(PDO::FETCH_ASSOC);

    if (!$lecon) {
        apiErreur('Leçon introuvable.', 404);
    }

    if (!tounModuleOuvert(tounParcours($con, $idUser, $idForm), (int) $lecon['id_module'])) {
        apiErreur('Réussissez le test du module précédent pour débloquer cette leçon.', 403);
    }

    $s = $con->prepare("INSERT IGNORE INTO t_suivi_contenus (id_user, id_form, type_element, id_element) VALUES (?, ?, 'lecon', ?)");
    $s->execute([$idUser, $idForm, $idLecon]);
}

$etat = tounEnregistrerProgression($con, $idUser, $idForm, max($pourcentage, tounPourcentCours($con, $idUser, $idForm)));

apiReponse([
    'id_form' => $idForm,
    'pourcentage' => $etat['pourcentage'],
    'statut' => $etat['statut'],
    'cours_termines' => $etat['cours_termines'],
    'evaluation_requise' => $etat['evaluation_requise'],
    'evaluation_reussie' => $etat['evaluation_reussie']
]);

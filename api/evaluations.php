<?php
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/certificats.php';
require_once __DIR__ . '/../includes/evaluations.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$s = $con->prepare("
    SELECT f.id_form, f.titre_form
    FROM t_inscriptions i
    INNER JOIN t_formations f ON f.id_form = i.id_form
    WHERE i.id_user = ? AND i.statut_inscrip IN ('active', 'terminee')
    ORDER BY i.date_inscrip_form DESC
");
$s->execute([$idUser]);
$evaluations = [];

foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $formation) {
    $idForm = (int) $formation['id_form'];
    $parcours = tounParcours($con, $idUser, $idForm);

    foreach (tounQuizDeFormation($con, $idUser, $idForm) as $quiz) {
        $dernier = tounDernierResultat($con, $idUser, (int) $quiz['id_quiz']);

        $evaluations[] = [
            'id_quiz' => (int) $quiz['id_quiz'],
            'titre' => $quiz['titre_quiz'],
            'id_form' => $idForm,
            'formation' => $formation['titre_form'],
            'type' => $quiz['type_quiz'] === 'module' ? 'module' : 'final',
            'disponible' => tounQuizOuvert($parcours, (int) $quiz['id_quiz']),
            'reussi' => (bool) (int) $quiz['reussi'],
            'derniere_note' => $dernier ? (float) $dernier['note_obtenue'] : null
        ];
    }
}

apiReponse(['evaluations' => $evaluations]);

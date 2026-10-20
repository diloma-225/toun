<?php
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/certificats.php';
require_once __DIR__ . '/../includes/evaluations.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];
$corps = $_SERVER['REQUEST_METHOD'] === 'POST' ? apiCorpsJson() : [];
$idQuiz = (int) ($corps['id_quiz'] ?? ($_GET['id'] ?? 0));

$quiz = $idQuiz > 0 ? tounQuizApprenant($con, $idQuiz, $idUser) : null;

if (!$quiz) {
    apiErreur('Évaluation introuvable.', 404);
}

$idForm = (int) $quiz['id_form'];
$questions = tounQuizQuestions($con, $idQuiz);
$noteMinimale = tounNoteMinimale($quiz);

$estTestModule = $quiz['type_quiz'] === 'module';

if (!tounQuizOuvert(tounParcours($con, $idUser, $idForm), $idQuiz)) {
    apiErreur($estTestModule
        ? 'Terminez toutes les leçons de ce module pour débloquer son test.'
        : 'Terminez tous les modules et réussissez leurs tests pour débloquer l’évaluation finale.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $dernier = tounDernierResultat($con, $idUser, $idQuiz);

    apiReponse([
        'quiz' => [
            'id_quiz' => $idQuiz,
            'titre' => $quiz['titre_quiz'],
            'type' => $estTestModule ? 'module' : 'final',
            'description' => (string) ($quiz['description_quiz'] ?? ''),
            'formation' => $quiz['titre_form'],
            'id_form' => $idForm,
            'note_minimale' => $noteMinimale,
            'tentatives' => tounNombreTentatives($con, $idUser, $idQuiz),
            'dernier_resultat' => $dernier ? [
                'note' => (float) $dernier['note_obtenue'],
                'reussi' => $dernier['statut_resultat'] === 'reussi'
            ] : null
        ],
        'questions' => array_map(static fn (array $question): array => [
            'id_question' => (int) $question['id_question'],
            'libelle' => $question['libelle_question'],
            'reponses' => array_map(static fn (array $reponse): array => [
                'id_reponse' => (int) $reponse['id_reponse'],
                'libelle' => $reponse['libelle_reponse']
            ], $question['reponses'])
        ], $questions)
    ]);
}

$choix = [];

foreach ((array) ($corps['reponses'] ?? []) as $idQuestion => $idReponse) {
    $choix[(int) $idQuestion] = (int) $idReponse;
}

if (count(array_filter($choix)) < count($questions)) {
    apiErreur('Veuillez répondre à toutes les questions.');
}

$correction = tounCorrigerQuiz($questions, $choix);
$reussi = $correction['note'] >= $noteMinimale;

tounEnregistrerResultat($con, $idUser, $idQuiz, $correction['note'], $reussi);
$etat = tounEnregistrerProgression($con, $idUser, $idForm, tounPourcentCours($con, $idUser, $idForm));

$s = $con->prepare('SELECT id_certificat FROM t_certificats WHERE id_user = ? AND id_form = ? LIMIT 1');
$s->execute([$idUser, $idForm]);
$idCertificat = (int) $s->fetchColumn();

apiReponse([
    'note' => $correction['note'],
    'note_minimale' => $noteMinimale,
    'bonnes' => $correction['bonnes'],
    'total' => $correction['total'],
    'reussi' => $reussi,
    'type' => $estTestModule ? 'module' : 'final',
    'progression' => $etat['pourcentage'],
    'corrections' => $correction['corrections'],
    'certificat' => $idCertificat > 0
        ? tounAdresseSite('http://localhost/application%20toun') . '/api/certificat.php?id=' . $idCertificat . '&jeton=' . rawurlencode((string) apiJetonEnvoye())
        : ''
]);

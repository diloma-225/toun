<?php
require __DIR__ . '/../_bootstrap.php';
require __DIR__ . '/../../paiement_config.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$reference = trim((string) ($_GET['reference'] ?? ''));

if ($reference === '') {
    apiErreur('Référence de paiement manquante.');
}

tounEnsurePaymentTable($con);

$s = $con->prepare("
    SELECT p.*, f.titre_form
    FROM t_paiements p
    LEFT JOIN t_formations f ON f.id_form = p.id_form
    WHERE p.ref_paiement = ? AND p.id_user = ?
    LIMIT 1
");
$s->execute([$reference, $idUser]);
$paiement = $s->fetch(PDO::FETCH_ASSOC);

if (!$paiement) {
    apiErreur('Paiement introuvable.', 404);
}

$paiement['statut_paiement'] = tounVerifierPaiement($con, $paiement);

apiReponse([
    'reference' => $paiement['ref_paiement'],
    'statut' => $paiement['statut_paiement'],
    'formation' => $paiement['titre_form'],
    'id_form' => (int) $paiement['id_form'],
    'montant' => (float) $paiement['montant_total']
]);
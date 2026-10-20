<?php
require __DIR__ . '/../_bootstrap.php';
require __DIR__ . '/../../paiement_config.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiErreur('Méthode non autorisée.', 405);
}

$corps = apiCorpsJson();
$idForm = (int) ($corps['id_form'] ?? 0);
$mode = strtoupper(trim((string) ($corps['mode_paiement'] ?? '')));
$retourApp = trim((string) ($corps['retour_app'] ?? ''));

if (!preg_match('#^http://(localhost|127\.0\.0\.1):[0-9]{2,5}$#', $retourApp)) {
    $retourApp = '';
}

if ($idForm <= 0 || !in_array($mode, ['WAVE', 'OM', 'MOOV'], true)) {
    apiErreur('Formation ou moyen de paiement invalide.');
}

if (apiEstCollaborateur($con, $idUser)) {
    apiErreur('Cette formation doit être attribuée par votre entreprise. Contactez votre administrateur TOUN.', 403);
}

$s = $con->prepare(
    "SELECT id_form, titre_form, prix_form FROM t_formations WHERE id_form = ? AND statut_form = 'publie' LIMIT 1"
);
$s->execute([$idForm]);
$formation = $s->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    apiErreur('Cette formation n\'est plus disponible.', 404);
}

$montant = (int) $formation['prix_form'];

if ($montant < 100 || $montant % 5 !== 0) {
    apiErreur('Le prix de cette formation doit être un montant valide pour le paiement en ligne.');
}

if (tounFormationAcquise($con, $idUser, $idForm)) {
    apiErreur('Vous avez déjà acheté cette formation. Retrouvez-la dans « Mes formations ».', 409);
}

if (!tounPaymentReady() && !tounSimulationAutorisee()) {
    apiErreur('Le paiement en ligne n\'est pas encore activé. Veuillez réessayer plus tard.', 503);
}

tounEnsurePaymentTable($con);

$reference = 'TOUN' . date('YmdHis') . strtoupper(bin2hex(random_bytes(5)));

$s = $con->prepare("
    INSERT INTO t_paiements (id_user, id_form, quantite, ref_paiement, montant_total, mode_paiement, statut_paiement)
    VALUES (?, ?, 1, ?, ?, ?, 'en_attente')
");
$s->execute([$idUser, $idForm, $reference, $montant, $mode]);

if (!tounPaymentReady()) {
    apiReponse([
        'reference' => $reference,
        'lien_paiement' => tounPaymentUrl('paiement-simulation.php?ref=' . urlencode($reference)),
        'montant' => $montant,
        'formation' => $formation['titre_form'],
        'simulation' => true
    ]);
}

$lien = tounCreerLienPaiement($con, [
    'id_paiement' => (int) $con->lastInsertId(),
    'reference' => $reference,
    'montant' => $montant,
    'description' => 'Achat formation TOUN - ' . $formation['titre_form'],
    'id_user' => $idUser,
    'id_form' => $idForm,
    'mode' => $mode,
    'nom' => $utilisateur['nom_user'] ?? '',
    'prenom' => $utilisateur['prenom_user'] ?? '',
    'email' => $utilisateur['email_user'] ?? '',
    'tel' => $utilisateur['tel_user'] ?? '',
    'retour_app' => $retourApp
]);

if (isset($lien['erreur'])) {
    apiErreur($lien['erreur'], 502);
}

apiReponse([
    'reference' => $reference,
    'lien_paiement' => $lien['url'],
    'montant' => $montant,
    'formation' => $formation['titre_form']
]);
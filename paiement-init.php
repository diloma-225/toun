<?php
session_start();
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/paiement_config.php';

function failPage(string $message): never
{
    http_response_code(400);
    echo '<!doctype html><html lang="fr"><meta charset="utf-8">' .
        '<body style="font-family:Arial;padding:50px;background:#f3f8fb;color:#17345b">' .
        '<h2>Paiement non lancé</h2><p>' .
        htmlspecialchars($message, ENT_QUOTES, 'UTF-8') .
        '</p>' .
        '<a href="formation.php">Retour aux formations</a></body></html>';
    exit();
}

if (!isset($_SESSION['id_user'])) {
    failPage('Veuillez vous connecter avant de payer.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failPage('Requête invalide.');
}

if (!hash_equals($_SESSION['csrf_achat'] ?? '', $_POST['csrf_token'] ?? '')) {
    failPage('Session expirée. Veuillez recommencer.');
}

$idForm = (int) ($_POST['id_form'] ?? 0);
$mode = strtoupper(trim($_POST['mode_paiement'] ?? ''));

if ($idForm <= 0 || !in_array($mode, ['WAVE', 'OM', 'MOOV'], true)) {
    failPage('Formation ou moyen de paiement invalide.');
}

$stmt = $con->prepare(
    "SELECT id_form, titre_form, descrip_form, prix_form, statut_form
     FROM t_formations
     WHERE id_form = :id AND statut_form = 'publie'
     LIMIT 1"
);
$stmt->execute([':id' => $idForm]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    failPage('Cette formation n\'est plus disponible.');
}

$amount = (int) $formation['prix_form'];

if ($amount < 100 || $amount % 5 !== 0) {
    failPage('Le prix de cette formation doit être un montant valide pour le paiement en ligne.');
}

if (!tounPaymentReady() && !tounSimulationAutorisee()) {
    failPage('Le paiement en ligne n\'est pas encore activé. Veuillez réessayer plus tard.');
}

$userId = (int) $_SESSION['id_user'];

if (tounFormationAcquise($con, $userId, $idForm)) {
    header('Location: apprenant/cours.php?id=' . $idForm);
    exit();
}

tounEnsurePaymentTable($con);

$ref = 'TOUN' . date('YmdHis') . strtoupper(bin2hex(random_bytes(5)));
$phone = trim((string) ($_SESSION['tel_user'] ?? ''));

$ins = $con->prepare(
    "INSERT INTO t_paiements (id_user, id_form, quantite, ref_paiement, montant_total, mode_paiement, statut_paiement)
     VALUES (:u, :f, 1, :r, :m, :mode, 'en_attente')"
);
$ins->execute([':u' => $userId, ':f' => $idForm, ':r' => $ref, ':m' => $amount, ':mode' => $mode]);

if (!tounPaymentReady()) {
    header('Location: paiement-simulation.php?ref=' . urlencode($ref));
    exit();
}

$lien = tounCreerLienPaiement($con, [
    'id_paiement' => (int) $con->lastInsertId(),
    'reference' => $ref,
    'montant' => $amount,
    'description' => 'Achat formation TOUN - ' . $formation['titre_form'],
    'id_user' => $userId,
    'id_form' => $idForm,
    'mode' => $mode,
    'nom' => $_SESSION['nom_user'] ?? '',
    'prenom' => $_SESSION['prenom_user'] ?? '',
    'email' => $_SESSION['email_user'] ?? '',
    'tel' => $phone
]);

if (isset($lien['erreur'])) {
    failPage($lien['erreur']);
}

header('Location: ' . $lien['url']);
exit();
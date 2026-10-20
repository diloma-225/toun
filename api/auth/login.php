<?php
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiErreur('Méthode non autorisée.', 405);
}

$corps = apiCorpsJson();
$email = strtolower(trim((string) ($corps['email'] ?? '')));
$motpass = (string) ($corps['mot_de_passe'] ?? '');

if ($email === '' || $motpass === '') {
    apiErreur('Veuillez renseigner vos identifiants.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiErreur('Adresse e-mail invalide.');
}

$s = $con->prepare('SELECT * FROM t_utilisateurs WHERE email_user = ? LIMIT 1');
$s->execute([$email]);
$utilisateur = $s->fetch(PDO::FETCH_ASSOC);

if (!$utilisateur || !password_verify($motpass, $utilisateur['motpass_user'] ?? '')) {
    apiErreur('E-mail ou mot de passe incorrect.', 401);
}

if (($utilisateur['statut_user'] ?? '') !== 'actif') {
    apiErreur('Votre compte n\'est pas actif.', 403);
}

if (($utilisateur['role_user'] ?? '') !== 'apprenant') {
    apiErreur('Ce compte n\'est pas un compte apprenant.', 403);
}

$jeton = apiCreerJeton($con, (int) $utilisateur['id_user']);

apiReponse([
    'jeton' => $jeton,
    'utilisateur' => [
        'id_user' => (int) $utilisateur['id_user'],
        'nom' => $utilisateur['nom_user'],
        'prenom' => $utilisateur['prenom_user'],
        'email' => $utilisateur['email_user'],
        'telephone' => $utilisateur['tel_user'],
        'photo' => apiUrlMedia($utilisateur['photo_user'], ['uploads/profils', 'uploads', 'images/profils', 'images'])
    ]
]);
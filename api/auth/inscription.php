<?php
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiErreur('Méthode non autorisée.', 405);
}

$corps = apiCorpsJson();

$nom = trim((string) ($corps['nom'] ?? ''));
$prenom = trim((string) ($corps['prenom'] ?? ''));
$email = strtolower(trim((string) ($corps['email'] ?? '')));
$tel = trim((string) ($corps['telephone'] ?? ''));
$motpass = (string) ($corps['mot_de_passe'] ?? '');
$confirmation = (string) ($corps['confirmation'] ?? '');

if ($nom === '' || $prenom === '' || $email === '' || $motpass === '' || $confirmation === '') {
    apiErreur('Veuillez remplir les champs obligatoires.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiErreur('Adresse e-mail invalide.');
}

if (strlen($motpass) < 8) {
    apiErreur('Le mot de passe doit contenir au moins 8 caractères.');
}

if ($motpass !== $confirmation) {
    apiErreur('Les deux mots de passe ne correspondent pas.');
}

$s = $con->prepare('SELECT id_user FROM t_utilisateurs WHERE email_user = ? LIMIT 1');
$s->execute([$email]);

if ($s->fetch()) {
    apiErreur('Cette adresse e-mail est déjà utilisée.');
}

try {
    $hash = password_hash($motpass, PASSWORD_DEFAULT);

    $s = $con->prepare("
        INSERT INTO t_utilisateurs
            (nom_user, prenom_user, email_user, tel_user, motpass_user, role_user, statut_user, date_inscrip_user)
        VALUES (?, ?, ?, ?, ?, 'apprenant', 'actif', NOW())
    ");
    $s->execute([$nom, $prenom, $email, $tel, $hash]);

    $idUser = (int) $con->lastInsertId();
    $jeton = apiCreerJeton($con, $idUser);

    apiReponse(
        [
            'jeton' => $jeton,
            'utilisateur' => [
                'id_user' => $idUser,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $tel,
                'photo' => ''
            ]
        ],
        201
    );
} catch (Throwable $e) {
    apiErreur('Une erreur est survenue lors de la création du compte.', 500);
}
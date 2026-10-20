<?php
require __DIR__ . '/_bootstrap.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$dateInscription = null;

$s = $con->prepare('SELECT date_inscrip_user FROM t_utilisateurs WHERE id_user = ?');
$s->execute([$idUser]);
$dateInscription = $s->fetchColumn();

apiReponse([
    'id_user' => $idUser,
    'nom' => $utilisateur['nom_user'],
    'prenom' => $utilisateur['prenom_user'],
    'email' => $utilisateur['email_user'],
    'telephone' => $utilisateur['tel_user'],
    'photo' => apiUrlMedia($utilisateur['photo_user'], ['uploads/profils', 'uploads', 'images/profils', 'images']),
    'date_inscription' => $dateInscription ?: null
]);
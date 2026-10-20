<?php
require __DIR__ . '/../_bootstrap.php';

apiUtilisateurCourant($con);

$jeton = apiJetonEnvoye();

if ($jeton) {
    $s = $con->prepare('DELETE FROM t_api_jetons WHERE jeton = ?');
    $s->execute([$jeton]);
}

apiReponse(['deconnecte' => true]);
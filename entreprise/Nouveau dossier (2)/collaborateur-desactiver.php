<?php
require __DIR__.'/entreprise_init.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$s=$con->prepare("UPDATE t_entreprise_collaborateurs SET statut='inactif' WHERE id_collaborateur=? AND id_entreprise=?");$s->execute([$id,$idEntreprise]);
flash('ok','Collaborateur désactivé.');header('Location: collaborateurs.php');exit;
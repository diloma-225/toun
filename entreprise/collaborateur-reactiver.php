<?php
require __DIR__ . '/entreprise_init.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: collaborateurs.php');
    exit();
}

$stmt = $con->prepare(
    'UPDATE t_entreprise_collaborateurs SET statut = ? WHERE id_collaborateur = ? AND id_entreprise = ?'
);
$stmt->execute(['actif', $id, $idEntreprise]);

flash('ok', 'Collaborateur réactivé.');
header('Location: collaborateurs.php');
exit();
<?php
require __DIR__ . '/entreprise_init.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id) {
    $s = $con->prepare("
        DELETE i
        FROM t_inscriptions i
        INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user = i.id_user AND ec.id_entreprise = ?
        WHERE i.id_inscrip = ?
    ");
    $s->execute([$idEntreprise, $id]);
    flash('ok', 'Affectation supprimée.');
}

header('Location: affectations.php');
exit();
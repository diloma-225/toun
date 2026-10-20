<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/connexion.php';
$id = (int) ($_GET['id'] ?? 0);
try {
    $st = $con->prepare('SELECT COUNT(*) FROM t_formations WHERE id_cat=?');
    $st->execute([$id]);
    if ((int) $st->fetchColumn() > 0) {
        $_SESSION['flash_error'] = 'Impossible de supprimer cette catégorie : des formations y sont rattachées.';
        header('Location: categories.php');
        exit();
    }
    $st = $con->prepare('DELETE FROM t_categories WHERE id_cat=?');
    $st->execute([$id]);
    $_SESSION['flash_success'] = 'La catégorie a été supprimée.';
} catch (Throwable $e) {
    $_SESSION['flash_error'] = 'La suppression de la catégorie a échoué.';
}
header('Location: categories.php');
exit();
?>
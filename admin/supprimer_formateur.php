<?php
session_start();
require_once '../config/connexion.php';
if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: formateurs.php');
    exit();
}

try {
    $stmt = $con->prepare(
        'SELECT id_user,photo_user FROM t_formateurs f INNER JOIN t_utilisateurs u ON u.id_user=f.id_user WHERE f.id_formateur=?'
    );
    $stmt->execute([$id]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$f) {
        header('Location: formateurs.php');
        exit();
    }

    $con->beginTransaction();
    $con->prepare('DELETE FROM t_formateurs WHERE id_formateur=?')->execute([$id]);
    $con->prepare("DELETE FROM t_utilisateurs WHERE id_user=? AND role_user='formateur'")->execute([
        (int) $f['id_user']
    ]);
    $con->commit();

    if (!empty($f['photo_user'])) {
        foreach (
            [__DIR__ . '/../uploads/photos/' . $f['photo_user'], __DIR__ . '/../uploads/' . $f['photo_user']]
            as $p
        ) {
            if (is_file($p)) {
                @unlink($p);
            }
        }
    }
    $_SESSION['success_formateur'] = 'Le formateur a été supprimé avec succès.';
} catch (Throwable $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    $_SESSION['error_formateur'] = 'Impossible de supprimer le formateur : ' . $e->getMessage();
}
header('Location: formateurs.php');
exit();
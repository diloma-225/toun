<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<?php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'Module invalide.';
    header('Location: modules.php');
    exit();
}

try {
    $stmt = $con->prepare('SELECT id_form FROM t_modules WHERE id_module=?');
    $stmt->execute([$id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        $_SESSION['error'] = 'Module introuvable.';
        header('Location: modules.php');
        exit();
    }

    $del = $con->prepare('DELETE FROM t_modules WHERE id_module=?');
    $del->execute([$id]);

    $_SESSION['success'] = 'Module supprimé avec succès.';
    header('Location: modules.php?id_form=' . (int) $module['id_form']);
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Impossible de supprimer ce module. Vérifiez qu'aucun contenu ne dépend de ce module.";
    header('Location: modules.php?id_form=' . (int) ($module['id_form'] ?? 0));
    exit();
}
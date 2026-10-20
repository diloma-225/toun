<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: utilisateur.php');
    exit();
}

if ($id === (int) $_SESSION['id_user']) {
    $_SESSION['user_error'] = 'Vous ne pouvez pas supprimer votre propre compte.';
    header('Location: utilisateur.php');
    exit();
}

$stmt = $con->prepare('SELECT id_user, role_user, photo_user FROM t_utilisateurs WHERE id_user = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['user_error'] = 'Utilisateur introuvable.';
    header('Location: utilisateur.php');
    exit();
}

if ($user['role_user'] === 'gest_entreprise') {
    $q = $con->prepare('SELECT COUNT(*) FROM t_entreprises WHERE id_user = ?');
    $q->execute([$id]);

    if ((int) $q->fetchColumn() > 0) {
        $_SESSION['user_error'] =
            "Ce compte gère une entreprise. Supprimez d'abord l'entreprise depuis la page Entreprises.";
        header('Location: utilisateur.php');
        exit();
    }
}

try {
    $con->beginTransaction();

    foreach (['t_entreprise_collaborateurs', 't_parametres_utilisateur', 't_api_jetons', 't_notifications'] as $table) {
        try {
            $con->prepare("DELETE FROM $table WHERE id_user = ?")->execute([$id]);
        } catch (PDOException $e) {
        }
    }

    if ($user['role_user'] === 'formateur') {
        $con->prepare('DELETE FROM t_formateurs WHERE id_user = ?')->execute([$id]);
    }

    $con->prepare('DELETE FROM t_utilisateurs WHERE id_user = ?')->execute([$id]);
    $con->commit();

    if (!empty($user['photo_user'])) {
        $photo = basename(str_replace('\\', '/', $user['photo_user']));

        foreach (['/../uploads/profils/', '/../uploads/photos/', '/../uploads/'] as $dossier) {
            if (is_file(__DIR__ . $dossier . $photo)) {
                @unlink(__DIR__ . $dossier . $photo);
            }
        }
    }

    $_SESSION['user_success'] = "L'utilisateur a été supprimé avec succès.";
} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }

    $con->prepare("UPDATE t_utilisateurs SET statut_user = 'inactif' WHERE id_user = ?")->execute([$id]);

    $_SESSION['user_success'] =
        "Cet utilisateur a des inscriptions ou des paiements enregistrés : son compte a été désactivé au lieu d'être supprimé, pour conserver l'historique.";
}

header('Location: utilisateur.php');
exit();
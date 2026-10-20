<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: entreprises.php');
    exit();
}

try {
    $stmt = $con->prepare("
        SELECT id_entreprise, id_user, logo_entreprise
        FROM t_entreprises
        WHERE id_entreprise = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);
    $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entreprise) {
        $_SESSION['entreprise_error'] = 'Entreprise introuvable.';
        header('Location: entreprises.php');
        exit();
    }

    $responsableId = (int) ($entreprise['id_user'] ?? 0);

    $con->beginTransaction();

    if ($responsableId > 0) {
        $stmtResponsable = $con->prepare("
            UPDATE t_utilisateurs
            SET statut_user = 'inactif'
            WHERE id_user = ?
              AND role_user = 'gest_entreprise'
        ");

        $stmtResponsable->execute([$responsableId]);
    }

    $stmtDelete = $con->prepare("
        DELETE FROM t_entreprises
        WHERE id_entreprise = ?
    ");

    $stmtDelete->execute([$id]);

    $con->commit();

    if (!empty($entreprise['logo_entreprise'])) {
        $logo = basename(str_replace('\\', '/', $entreprise['logo_entreprise']));

        $paths = [
            __DIR__ . '/../uploads/entreprises/' . $logo,
            __DIR__ . '/../uploads/' . $logo,
            __DIR__ . '/../images/entreprises/' . $logo
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    $_SESSION['entreprise_success'] = "L'entreprise a été supprimée et le compte du responsable a été désactivé.";
} catch (Throwable $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }

    $_SESSION['entreprise_error'] = "Impossible de supprimer l'entreprise.";
}

header('Location: entreprises.php');
exit();
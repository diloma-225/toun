<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $con->prepare('DELETE FROM t_messages_contact WHERE id_message = ? LIMIT 1');
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = 'Le message a été supprimé.';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = 'La suppression a échoué. Veuillez réessayer.';
    }
}

header('Location: messages.php');
exit();
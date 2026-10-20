<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/quiz_config.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$meta = quizDetect($con);
$id = $_GET['id'] ?? '';

if ($meta && $id !== '') {
    try {
        $stmt = $con->prepare("DELETE FROM `{$meta['table']}` WHERE `{$meta['id']}` = ? LIMIT 1");
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = 'Le quiz a été supprimé.';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = 'La suppression du quiz a échoué.';
    }
}

header('Location: quiz.php');
exit();
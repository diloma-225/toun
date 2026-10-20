<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../includes/certificats.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

$certificat = tounCertificat($con, (int) ($_GET['id'] ?? 0), (int) $_SESSION['id_user']);

if (!$certificat) {
    header('Location: certificats.php');
    exit();
}

tounAfficherCertificat($certificat, isset($_GET['imprimer']), 'certificats.php');

<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role_user'] ?? '') !== 'gest_entreprise'
) {
    header('Location: ../connexion.php');
    exit;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$userId = (int)$_SESSION['id_user'];

$stmt = $con->prepare("
    SELECT *
    FROM t_entreprises
    WHERE id_user = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    exit('Entreprise introuvable.');
}

$idEntreprise = (int)$entreprise['id_entreprise'];
$nomEntreprise = trim((string)($entreprise['nom_entreprise'] ?? 'Mon entreprise'));

function flash(string $type, string $message): void {
    $_SESSION['entreprise_flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['entreprise_flash'] ?? null;
    unset($_SESSION['entreprise_flash']);
    return $flash;
}
?>
<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'gest_entreprise') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    $_SESSION['entreprise_flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash(): ?array
{
    $v = $_SESSION['entreprise_flash'] ?? null;
    unset($_SESSION['entreprise_flash']);
    return $v;
}

function tableExists(PDO $con, string $table): bool
{
    $s = $con->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ");
    $s->execute([$table]);
    return (bool) $s->fetchColumn();
}

function columnExists(PDO $con, string $table, string $column): bool
{
    $s = $con->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ");
    $s->execute([$table, $column]);
    return (bool) $s->fetchColumn();
}

function uploadImage(?array $file, string $folder, string $prefix = 'photo_'): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('La photo n’a pas pu être envoyée.');
    }

    if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('La photo ne doit pas dépasser 4 Mo.');
    }

    $mime = mime_content_type($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format accepté : JPG, PNG ou WEBP.');
    }

    $dir = __DIR__ . '/../' . trim($folder, '/\\');
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Le dossier photo est inaccessible.');
    }

    $name = $prefix . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException('Impossible d’enregistrer la photo.');
    }

    return trim($folder, '/\\') . '/' . $name;
}

function deleteStoredFile(?string $path): void
{
    if (!$path) {
        return;
    }

    $full = __DIR__ . '/../' . ltrim(str_replace('\\', '/', $path), '/');
    if (is_file($full)) {
        @unlink($full);
    }
}

function paymentSuccess(string $status): bool
{
    $s = mb_strtolower(trim($status), 'UTF-8');
    return in_array(
        $s,
        [
            'success',
            'succes',
            'réussi',
            'reussi',
            'paye',
            'payé',
            'paid',
            'confirmed',
            'confirmé',
            'complete',
            'completed',
            'valide',
            'validé',
            'successfull'
        ],
        true
    );
}

function enterprisePurchasedFormations(PDO $con, int $userId): array
{
    if (!tableExists($con, 't_paiements') || !tableExists($con, 't_formations')) {
        return [];
    }

    $referenceColumn = columnExists($con, 't_paiements', 'ref_paiement')
        ? 'p.ref_paiement'
        : (columnExists($con, 't_paiements', 'reference_paiement')
            ? 'p.reference_paiement'
            : 'NULL');

    $confirmationColumn = columnExists($con, 't_paiements', 'date_confirmation') ? 'p.date_confirmation' : 'NULL';

    $dateColumn = columnExists($con, 't_paiements', 'date_paiement') ? 'p.date_paiement' : 'NULL';

    $orderExpression = columnExists($con, 't_paiements', 'date_confirmation')
        ? "COALESCE(p.date_confirmation, {$dateColumn})"
        : $dateColumn;

    $sql = "SELECT p.*, f.*, {$referenceColumn} AS reference_paiement,
        {$confirmationColumn} AS date_confirmation,
        {$dateColumn} AS payment_date
        FROM t_paiements p
        INNER JOIN t_formations f ON f.id_form = p.id_form
        WHERE p.id_user = ?
        ORDER BY {$orderExpression} DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    $seen = [];

    foreach ($rows as $row) {
        if (!paymentSuccess($row['statut_paiement'] ?? '')) {
            continue;
        }

        $idForm = (int) ($row['id_form'] ?? 0);
        if ($idForm <= 0 || isset($seen[$idForm])) {
            continue;
        }

        $seen[$idForm] = true;
        $out[] = $row;
    }

    return $out;
}

function entrepriseCollaborators(PDO $con, int $idEntreprise): array
{
    $sql = "
        SELECT
            ec.id_collaborateur, ec.id_user, ec.date_ajout, ec.statut,
            u.nom_user, u.prenom_user, u.email_user, u.tel_user, u.photo_user, u.statut_user,
            COUNT(DISTINCT i.id_form) formations
        FROM t_entreprise_collaborateurs ec
        INNER JOIN t_utilisateurs u ON u.id_user = ec.id_user
        LEFT JOIN t_inscriptions i ON i.id_user = u.id_user AND i.statut_inscrip IN ('active', 'terminee', 'terminée')
        WHERE ec.id_entreprise = ?
        GROUP BY
            ec.id_collaborateur, ec.id_user, ec.date_ajout, ec.statut,
            u.nom_user, u.prenom_user, u.email_user, u.tel_user, u.photo_user, u.statut_user
        ORDER BY u.nom_user, u.prenom_user
    ";

    $s = $con->prepare($sql);
    $s->execute([$idEntreprise]);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}

function getProgressColumn(PDO $con): ?string
{
    if (!tableExists($con, 't_progression')) {
        return null;
    }

    foreach (
        ['pourcentage_progression', 'pourcent_avancement', 'progression', 'pourcentage', 'progression_user']
        as $c
    ) {
        if (columnExists($con, 't_progression', $c)) {
            return $c;
        }
    }

    return null;
}

function getTimeColumn(PDO $con): ?string
{
    if (!tableExists($con, 't_progression')) {
        return null;
    }

    foreach (['temps_total', 'temps_apprentissage', 'duree_apprentissage'] as $c) {
        if (columnExists($con, 't_progression', $c)) {
            return $c;
        }
    }

    return null;
}

$userId = (int) $_SESSION['id_user'];

$stmt = $con->prepare('SELECT * FROM t_entreprises WHERE id_user = ? LIMIT 1');
$stmt->execute([$userId]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$entreprise) {
    exit('Aucune entreprise n’est associée à ce compte.');
}

$idEntreprise = (int) $entreprise['id_entreprise'];
<?php
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/adresse.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

function apiReponse(array $donnees, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['ok' => true, 'data' => $donnees], JSON_UNESCAPED_UNICODE);
    exit();
}

function apiErreur(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

function apiCorpsJson(): array
{
    $brut = file_get_contents('php://input');
    $donnees = json_decode($brut, true);
    return is_array($donnees) ? $donnees : [];
}

function apiAssurerTableJetons(PDO $con): void
{
    $con->exec("
        CREATE TABLE IF NOT EXISTS t_api_jetons (
            jeton VARCHAR(64) NOT NULL,
            id_user INT NOT NULL,
            date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_expiration DATETIME NOT NULL,
            PRIMARY KEY (jeton),
            KEY id_user (id_user)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function apiCreerJeton(PDO $con, int $idUser): string
{
    apiAssurerTableJetons($con);

    $jeton = bin2hex(random_bytes(32));

    $s = $con->prepare("
        INSERT INTO t_api_jetons (jeton, id_user, date_expiration)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
    ");
    $s->execute([$jeton, $idUser]);

    return $jeton;
}

function apiJetonEnvoye(): ?string
{
    $entete = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

    if ($entete === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $nom => $valeur) {
            if (strcasecmp($nom, 'Authorization') === 0) {
                $entete = $valeur;
                break;
            }
        }
    }

    if (preg_match('/Bearer\s+(\S+)/i', $entete, $m)) {
        return $m[1];
    }

    return null;
}

function apiUtilisateurCourant(PDO $con): array
{
    apiAssurerTableJetons($con);

    $jeton = apiJetonEnvoye();

    if (!$jeton) {
        apiErreur('Authentification requise.', 401);
    }

    $s = $con->prepare("
        SELECT j.id_user, u.nom_user, u.prenom_user, u.email_user, u.tel_user, u.photo_user, u.role_user, u.statut_user
        FROM t_api_jetons j
        INNER JOIN t_utilisateurs u ON u.id_user = j.id_user
        WHERE j.jeton = ? AND j.date_expiration > NOW()
        LIMIT 1
    ");
    $s->execute([$jeton]);
    $utilisateur = $s->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur) {
        apiErreur('Session expirée, veuillez vous reconnecter.', 401);
    }

    if (($utilisateur['statut_user'] ?? '') !== 'actif') {
        apiErreur('Votre compte n\'est pas actif.', 403);
    }

    return $utilisateur;
}

function apiTableExiste(PDO $con, string $table): bool
{
    try {
        $s = $con->prepare('SHOW TABLES LIKE ?');
        $s->execute([$table]);
        return (bool) $s->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function apiColonnesTable(PDO $con, string $table): array
{
    if (!apiTableExiste($con, $table)) {
        return [];
    }

    try {
        $s = $con->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
        return $s->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function apiEstCollaborateur(PDO $con, int $idUser): bool
{
    if (!apiTableExiste($con, 't_entreprise_collaborateurs')) {
        return false;
    }

    try {
        $s = $con->prepare("
            SELECT 1
            FROM t_entreprise_collaborateurs
            WHERE id_user = ?
              AND LOWER(statut) = 'actif'
            LIMIT 1
        ");
        $s->execute([$idUser]);

        return (bool) $s->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function apiUrlMedia(?string $chemin, array $dossiers): string
{
    $chemin = trim((string) $chemin);

    if ($chemin === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $chemin)) {
        return $chemin;
    }

    $nom = basename(str_replace('\\', '/', $chemin));
    $base = tounAdresseSite('http://localhost/application%20toun') . '/api/media.php?f=';

    foreach ($dossiers as $dossier) {
        $fichier = __DIR__ . '/../' . $dossier . '/' . $nom;
        if (is_file($fichier)) {
            return $base . rawurlencode($dossier . '/' . $nom);
        }
    }

    return '';
}
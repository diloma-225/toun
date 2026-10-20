<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$nom = $_SESSION['nom_user'] ?? '';
$prenom = $_SESSION['prenom_user'] ?? '';
$photo = $_SESSION['photo_user'] ?? '';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function initiales(string $prenom, string $nom): string
{
    $a = mb_substr(trim($prenom), 0, 1, 'UTF-8');
    $b = mb_substr(trim($nom), 0, 1, 'UTF-8');
    return mb_strtoupper($a . $b, 'UTF-8');
}

function resolvePhoto(string $photo): string
{
    if ($photo === '') {
        return '';
    }

    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $name = basename($photo);

    $paths = [
        [__DIR__ . '/' . $photo, $photo],
        [__DIR__ . '/../uploads/profils/' . $name, '../uploads/profils/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/' . $name, '../uploads/' . rawurlencode($name)],
        [__DIR__ . '/../assets/uploads/' . $name, '../assets/uploads/' . rawurlencode($name)],
        [__DIR__ . '/../images/profils/' . $name, '../images/profils/' . rawurlencode($name)],
        [__DIR__ . '/../images/' . $name, '../images/' . rawurlencode($name)]
    ];

    foreach ($paths as $item) {
        if (is_file($item[0])) {
            return $item[1];
        }
    }

    return '';
}

function resolveFormationImage(string $image): string
{
    if ($image === '') {
        return '';
    }

    $image = str_replace('\\', '/', trim($image));
    $image = ltrim($image, '/');

    if (preg_match('#^(https?:)?//#i', $image)) {
        return $image;
    }

    $name = basename($image);

    $paths = [
        [__DIR__ . '/' . $image, $image],
        [__DIR__ . '/../uploads/formations/' . $name, '../uploads/formations/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/' . $name, '../uploads/' . rawurlencode($name)],
        [__DIR__ . '/../assets/uploads/formations/' . $name, '../assets/uploads/formations/' . rawurlencode($name)],
        [__DIR__ . '/../images/formations/' . $name, '../images/formations/' . rawurlencode($name)],
        [__DIR__ . '/../images/' . $name, '../images/' . rawurlencode($name)]
    ];

    foreach ($paths as $item) {
        if (is_file($item[0])) {
            return $item[1];
        }
    }

    return '';
}

function formatDuration($minutes): string
{
    $minutes = (int) $minutes;
    if ($minutes <= 0) {
        return 'Durée non définie';
    }

    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    if ($hours > 0 && $mins > 0) {
        return $hours . ' h ' . $mins . ' min';
    }

    if ($hours > 0) {
        return $hours . ' h';
    }

    return $mins . ' min';
}

function statusLabel(string $status): string
{
    $labels = [
        'publie' => 'Publiée',
        'publiee' => 'Publiée',
        'publiée' => 'Publiée',
        'published' => 'Publiée',
        'brouillon' => 'Brouillon',
        'draft' => 'Brouillon',
        'archive' => 'Archivée',
        'archivée' => 'Archivée',
        'archived' => 'Archivée'
    ];

    return $labels[mb_strtolower(trim($status), 'UTF-8')] ?? ucfirst($status ?: 'Non défini');
}

function statusClass(string $status): string
{
    $status = mb_strtolower(trim($status), 'UTF-8');

    if (in_array($status, ['publie', 'publiee', 'publiée', 'published'], true)) {
        return 'published';
    }

    if (in_array($status, ['archive', 'archivée', 'archived'], true)) {
        return 'archived';
    }

    return 'draft';
}

function tableExists(PDO $con, string $table): bool
{
    try {
        $stmt = $con->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

$photoUrl = resolvePhoto($photo);

$recherche = trim($_GET['q'] ?? '');
$statut = trim($_GET['statut'] ?? '');
$categorie = (int) ($_GET['categorie'] ?? 0);
$niveau = trim($_GET['niveau'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$parPage = 9;

$hasVideoColumn = false;
$hasCategories = tableExists($con, 't_categories');
$hasFormateurs = tableExists($con, 't_formateurs');
$hasModules = tableExists($con, 't_modules');

try {
    $columns = $con->query('SHOW COLUMNS FROM t_formations')->fetchAll(PDO::FETCH_COLUMN);
    $hasVideoColumn = in_array('video_intro_form', $columns, true);
} catch (PDOException $e) {
    $columns = [];
}

$where = [];
$params = [];

if ($recherche !== '') {
    $searchParts = ['f.titre_form LIKE :q', 'f.descrip_form LIKE :q'];

    if ($hasCategories) {
        $searchParts[] = 'c.nom_cat LIKE :q';
    }

    if ($hasFormateurs) {
        $searchParts[] = "CONCAT(COALESCE(u.prenom_user,''),' ',COALESCE(u.nom_user,'')) LIKE :q";
    }

    $where[] = '(' . implode(' OR ', $searchParts) . ')';
    $params['q'] = '%' . $recherche . '%';
}

if ($statut !== '') {
    $where[] = 'f.statut_form = :statut';
    $params['statut'] = $statut;
}

if ($categorie > 0) {
    $where[] = 'f.id_cat = :categorie';
    $params['categorie'] = $categorie;
}

if ($niveau !== '') {
    $where[] = 'f.niveau_form = :niveau';
    $params['niveau'] = $niveau;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$formations = [];
$total = 0;
$totalPages = 1;
$erreur = '';

try {
    $join = '';

    if ($hasCategories) {
        $join .= ' LEFT JOIN t_categories c ON c.id_cat = f.id_cat ';
    }

    if ($hasFormateurs) {
        $join .= ' LEFT JOIN t_formateurs fo ON fo.id_formateur = f.id_formateur ';
        $join .= ' LEFT JOIN t_utilisateurs u ON u.id_user = fo.id_user ';
    }

    $countSql = "SELECT COUNT(*) FROM t_formations f $join $whereSql";
    $stmt = $con->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $parPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $parPage;

    $select = "
        f.id_form,
        f.id_cat,
        f.id_formateur,
        f.titre_form,
        f.descrip_form,
        f.img_form,
        f.niveau_form,
        f.prix_form,
        f.duree_form,
        f.statut_form,
        f.date_creation_form
    ";

    if ($hasVideoColumn) {
        $select .= ', f.video_intro_form';
    }

    if ($hasCategories) {
        $select .= ', c.nom_cat';
    }

    if ($hasFormateurs) {
        $select .= ', u.nom_user AS formateur_nom, u.prenom_user AS formateur_prenom';
    }

    if ($hasModules) {
        $select .= ", (
            SELECT COUNT(*)
            FROM t_modules m
            WHERE m.id_form = f.id_form
        ) AS nombre_modules";
    }

    $sql = "
        SELECT $select
        FROM t_formations f
        $join
        $whereSql
        ORDER BY f.date_creation_form DESC, f.id_form DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $con->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $stmt->bindValue(':limit', $parPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = $hasCategories
        ? $con->query('SELECT id_cat, nom_cat FROM t_categories ORDER BY nom_cat ASC')->fetchAll(PDO::FETCH_ASSOC)
        : [];

    $niveaux = $con
        ->query(
            "
        SELECT DISTINCT niveau_form
        FROM t_formations
        WHERE niveau_form IS NOT NULL AND niveau_form <> ''
        ORDER BY niveau_form ASC
    "
        )
        ->fetchAll(PDO::FETCH_COLUMN);

    $stats = [
        'total' => (int) $con->query('SELECT COUNT(*) FROM t_formations')->fetchColumn(),
        'publiees' => 0,
        'brouillons' => 0,
        'archivees' => 0
    ];

    $statsStmt = $con->query("
        SELECT LOWER(TRIM(statut_form)) AS statut, COUNT(*) AS total
        FROM t_formations
        GROUP BY LOWER(TRIM(statut_form))
    ");

    foreach ($statsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $s = $row['statut'];
        $n = (int) $row['total'];

        if (in_array($s, ['publie', 'publiee', 'publiée', 'published'], true)) {
            $stats['publiees'] += $n;
        } elseif (in_array($s, ['archive', 'archivée', 'archived'], true)) {
            $stats['archivees'] += $n;
        } else {
            $stats['brouillons'] += $n;
        }
    }
} catch (PDOException $e) {
    $erreur = 'Impossible de charger les formations. Vérifiez la structure de la base de données.';
    $formations = [];
    $categories = [];
    $niveaux = [];
    $stats = ['total' => 0, 'publiees' => 0, 'brouillons' => 0, 'archivees' => 0];
}

function queryUrl(array $changes = []): string
{
    $query = $_GET;

    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return 'formation.php' . ($query ? '?' . http_build_query($query) : '');
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formations | TOUN</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            :root {
                --navy: #101a35;
                --navy-2: #18213f;
                --blue: #3f68ae;
                --turquoise: #20a1b8;
                --cyan: #7fe4ef;
                --bg: #e8f1f6;
                --border: #e5eaf1;
                --text: #18213f;
                --muted: #7b879b;
                --white: #ffffff;
            }

            body {
                font-family: "Poppins", sans-serif;
                background: var(--bg);
                color: var(--text);
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            button,
            input,
            select {
                font: inherit;
            }

            .layout {
                min-height: 100vh;
            }

            .content {
                padding: 18px 24px 26px;
            }

            .page-head {
                background: linear-gradient(135deg, #142b4a 0%, #1b5270 58%, #20a1b8 100%);
                padding: 16px 18px;
                border-radius: 16px;
                box-shadow: 0 10px 25px rgba(20, 66, 91, 0.13);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 17px;
            }

            .page-head h1 {
                font-size: 23px;
                line-height: 1.2;
                color: #fff;
            }

            .page-head p {
                margin-top: 5px;
                color: rgba(255, 255, 255, 0.78);
                font-size: 12px;
            }

            .primary-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 40px;
                padding: 0 16px;
                border-radius: 10px;
                background: #fff;
                color: #176c83;
                font-size: 12px;
                font-weight: 700;
                box-shadow: 0 9px 20px rgba(32, 161, 184, 0.2);
                transition: 0.2s ease;
                white-space: nowrap;
            }

            .primary-btn i {
                width: 24px;
                height: 24px;
                border-radius: 7px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, var(--blue), var(--turquoise));
                color: #fff;
                font-size: 11.5px;
            }

            .primary-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 12px 24px rgba(32, 161, 184, 0.27);
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 12px;
            }

            .stat {
                min-height: 76px;
                border-radius: 14px;
                padding: 13px 15px;
                background: linear-gradient(135deg, #f7fbfd, #eaf5f7);
                border: 1px solid #d7e5ec;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: 0 5px 18px rgba(30, 53, 87, 0.04);
            }

            .stat-icon {
                width: 39px;
                height: 39px;
                min-width: 39px;
                border-radius: 11px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 14px;
            }

            .stat.total .stat-icon {
                background: linear-gradient(135deg, #263a68, #3f68ae);
            }

            .stat.published .stat-icon {
                background: linear-gradient(135deg, #1499a9, #20b9b1);
            }

            .stat.draft .stat-icon {
                background: linear-gradient(135deg, #6179a9, #7189c0);
            }

            .stat.archived .stat-icon {
                background: linear-gradient(135deg, #7d8798, #9aa3b2);
            }

            .stat-label {
                color: #8994a7;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.6px;
            }

            .stat-number {
                margin-top: 1px;
                font-size: 19px;
                font-weight: 800;
                color: var(--navy-2);
            }

            .filter-card {
                background: linear-gradient(135deg, #f7fbfd, #e9f4f7);
                border: 1px solid #d7e5ec;
                border-radius: 14px;
                padding: 9px;
                margin-bottom: 13px;
                box-shadow: 0 5px 18px rgba(30, 53, 87, 0.035);
            }

            .filters {
                display: grid;
                grid-template-columns: minmax(220px, 1.7fr) repeat(3, minmax(145px, 0.75fr)) auto;
                gap: 8px;
            }

            .field {
                position: relative;
            }

            .field i {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #9ca7b8;
                font-size: 12px;
                pointer-events: none;
            }

            .field input,
            .field select {
                width: 100%;
                height: 38px;
                border: 1px solid #e5eaf1;
                background: #f8fafd;
                border-radius: 9px;
                outline: none;
                color: var(--text);
                padding: 0 11px;
                font-size: 11.5px;
            }

            .field.has-icon input {
                padding-left: 32px;
            }

            .field input:focus,
            .field select:focus {
                border-color: rgba(32, 161, 184, 0.55);
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .filter-submit {
                height: 38px;
                border: 0;
                border-radius: 9px;
                padding: 0 14px;
                background: var(--navy-2);
                color: #fff;
                cursor: pointer;
                font-size: 11.5px;
                font-weight: 700;
            }

            .reset-btn {
                height: 38px;
                min-width: 38px;
                border: 1px solid #e5eaf1;
                border-radius: 9px;
                background: #fff;
                color: #718096;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .reset-btn:hover {
                color: var(--blue);
                border-color: #c8d5e8;
            }

            .catalog-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 9px;
            }

            .catalog-head strong {
                font-size: 12.5px;
                color: var(--navy-2);
            }

            .catalog-head span {
                color: #929dae;
                font-size: 11px;
            }

            .formation-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
            }

            .formation-card {
                background: #fdfeff;
                border: 1px solid #d7e5ec;
                border-radius: 17px;
                overflow: hidden;
                box-shadow: 0 8px 24px rgba(29, 53, 88, 0.055);
                transition: 0.25s ease;
                min-width: 0;
            }

            .formation-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 14px 32px rgba(29, 53, 88, 0.1);
            }

            .formation-cover {
                height: 154px;
                position: relative;
                background: linear-gradient(135deg, #dceaf4, #eaf4f7);
                overflow: hidden;
            }

            .formation-cover img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .cover-placeholder {
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                background:
                    radial-gradient(circle at 20% 25%, rgba(127, 228, 239, 0.55), transparent 30%),
                    linear-gradient(135deg, #21385e, #20a1b8);
                color: rgba(255, 255, 255, 0.9);
                font-size: 42px;
            }

            .status-badge {
                position: absolute;
                top: 10px;
                left: 10px;
                padding: 6px 9px;
                border-radius: 8px;
                color: #fff;
                font-size: 10.5px;
                font-weight: 700;
                backdrop-filter: blur(7px);
            }

            .status-badge.published {
                background: rgba(24, 157, 145, 0.94);
            }

            .status-badge.draft {
                background: rgba(72, 91, 124, 0.94);
            }

            .status-badge.archived {
                background: rgba(89, 99, 115, 0.94);
            }

            .video-badge {
                position: absolute;
                top: 10px;
                right: 10px;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                background: rgba(16, 26, 53, 0.78);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11.5px;
                backdrop-filter: blur(6px);
            }

            .formation-body {
                padding: 13px 14px 12px;
            }

            .category {
                display: inline-flex;
                align-items: center;
                min-height: 24px;
                padding: 0 9px;
                border-radius: 8px;
                background: #eaf1fc;
                color: var(--blue);
                font-size: 10.5px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                max-width: 100%;
            }

            .formation-title {
                margin-top: 8px;
                font-size: 15px;
                line-height: 1.35;
                font-weight: 800;
                color: #17234a;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                min-height: 40px;
            }

            .description {
                margin-top: 4px;
                color: #8792a5;
                font-size: 11px;
                line-height: 1.55;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                min-height: 28px;
            }

            .formateur {
                margin-top: 8px;
                display: flex;
                align-items: center;
                gap: 7px;
                color: #78859a;
                font-size: 11px;
            }

            .formateur i {
                color: var(--turquoise);
            }

            .formateur strong {
                color: #34415e;
            }

            .meta {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 9px;
            }

            .meta-item {
                min-height: 26px;
                padding: 0 8px;
                border-radius: 8px;
                background: #f6f8fb;
                border: 1px solid #e9edf3;
                color: #66738a;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 10.5px;
                font-weight: 600;
            }

            .meta-item i {
                color: var(--blue);
                font-size: 11px;
            }

            .formation-footer {
                margin-top: 11px;
                padding-top: 10px;
                border-top: 1px solid #edf0f4;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }

            .price {
                color: var(--blue);
                font-size: 15px;
                font-weight: 800;
                white-space: nowrap;
            }

            .price.free {
                color: var(--turquoise);
            }

            .actions {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .action {
                width: 29px;
                height: 29px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11.5px;
                transition: 0.2s ease;
            }

            .action.view {
                color: #168d9d;
                background: #e7f8fa;
            }

            .action.edit {
                color: var(--blue);
                background: #eaf1fc;
            }

            .action.delete {
                color: #d95757;
                background: #fdecec;
            }

            .action:hover {
                transform: translateY(-1px);
                filter: brightness(0.96);
            }

            .empty {
                grid-column: 1 / -1;
                min-height: 250px;
                border: 1px dashed #cdd7e4;
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.72);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 25px;
            }

            .empty i {
                width: 52px;
                height: 52px;
                border-radius: 15px;
                background: #eaf1fc;
                color: var(--blue);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                margin-bottom: 11px;
            }

            .empty strong {
                font-size: 13px;
            }

            .empty p {
                max-width: 430px;
                margin-top: 5px;
                color: #8a96a8;
                font-size: 11px;
                line-height: 1.6;
            }

            .error {
                padding: 12px 14px;
                margin-bottom: 12px;
                border-radius: 11px;
                background: #fff2f2;
                border: 1px solid #f2d0d0;
                color: #a84848;
                font-size: 11.5px;
            }

            .pagination {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                margin-top: 17px;
            }

            .page-link {
                width: 31px;
                height: 31px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                border: 1px solid var(--border);
                color: #758198;
                font-size: 11px;
                font-weight: 700;
            }

            .page-link.active {
                background: linear-gradient(135deg, var(--blue), var(--turquoise));
                border-color: transparent;
                color: #fff;
            }

            .page-link:hover:not(.active) {
                color: var(--blue);
                border-color: #c8d5e8;
            }

            @media (max-width: 1250px) {
                .formation-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .filters {
                    grid-template-columns: 1.5fr repeat(2, 1fr);
                }

                .filters .submit-wrap {
                    grid-column: span 1;
                }

                .stats {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 900px) {
                .sidebar {
                    width: 70px;
                    padding: 8px 7px;
                }

                .logo {
                    height: 45px;
                    min-height: 45px;
                    padding: 0;
                }

                .logo img {
                    width: 45px;
                    max-height: 36px;
                }

                .menu-title,
                .menu-link span {
                    display: none;
                }

                .menu-link {
                    justify-content: center;
                    height: 34px;
                    min-height: 34px;
                    padding: 0;
                }

                .main {
                    width: calc(100% - 70px);
                    margin-left: 70px;
                }

                .topbar {
                    padding: 0 15px;
                }

                .top-search {
                    width: 220px;
                }

                .profile-info {
                    display: none;
                }

                .content {
                    padding: 18px 17px 25px;
                }

                .filters {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .filters .search-field {
                    grid-column: 1 / -1;
                }
            }

            @media (max-width: 650px) {
                .top-search {
                    width: 160px;
                }

                .notification {
                    display: none;
                }

                .page-head {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .primary-btn {
                    width: 100%;
                    justify-content: center;
                }

                .stats,
                .formation-grid,
                .filters {
                    grid-template-columns: 1fr;
                }

                .filters .search-field {
                    grid-column: auto;
                }

                .formation-cover {
                    height: 190px;
                }
            }
        </style>

        <body>

            <div class="layout">
                <?php
$active_page = 'formation.php';
include 'menu.php';
?>
                <main class="main">
                    <section class="content">

                        <div class="page-head">
                            <div>
                                <h1>Formations</h1>
                                <p>Gérez le catalogue, les contenus et la publication des formations TOUN.</p>
                            </div>

                            <a href="ajouter_formation.php" class="primary-btn">
                                <i class="fa-solid fa-plus"></i>
                                Nouvelle formation
                            </a>
                        </div>

                        <?php if ($erreur): ?>
                            <div class="error">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <?= e($erreur) ?>
                            </div>
                        <?php endif; ?>

                        <div class="stats">
                            <div class="stat total">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Total formations</div>
                                    <div class="stat-number"><?= $stats['total'] ?></div>
                                </div>
                            </div>

                            <div class="stat published">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Publiées</div>
                                    <div class="stat-number"><?= $stats['publiees'] ?></div>
                                </div>
                            </div>

                            <div class="stat draft">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-pen-ruler"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Brouillons</div>
                                    <div class="stat-number"><?= $stats['brouillons'] ?></div>
                                </div>
                            </div>

                            <div class="stat archived">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-box-archive"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Archivées</div>
                                    <div class="stat-number"><?= $stats['archivees'] ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="filter-card">
                            <form class="filters" method="get" action="formation.php">

                                <div class="field has-icon search-field">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input
                                        type="search"
                                        name="q"
                                        value="<?= e($recherche) ?>"
                                        placeholder="Titre, description, catégorie ou formateur..."
                                    >
                                </div>

                                <div class="field">
                                    <select name="categorie">
                                        <option value="">Toutes les catégories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option
                                                value="<?= (int) $cat['id_cat'] ?>"
                                                <?= $categorie === (int) $cat['id_cat'] ? 'selected' : '' ?>
                                            >
                                                <?= e($cat['nom_cat']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field">
                                    <select name="niveau">
                                        <option value="">Tous les niveaux</option>
                                        <?php foreach ($niveaux as $niv): ?>
                                            <option value="<?= e($niv) ?>" <?= $niveau === $niv ? 'selected' : '' ?>>
                                                <?= e($niv) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field">
                                    <select name="statut">
                                        <option value="">Tous les statuts</option>
                                        <option value="publie" <?= $statut === 'publie' ? 'selected' : '' ?>>Publiée</option>
                                        <option value="brouillon" <?= $statut === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                                        <option value="archive" <?= $statut === 'archive' ? 'selected' : '' ?>>Archivée</option>
                                    </select>
                                </div>

                                <div class="submit-wrap">
                                    <button type="submit" class="filter-submit">
                                        Filtrer
                                    </button>
                                </div>

                                <?php if ($recherche || $categorie || $niveau || $statut): ?>
                                    <a href="formation.php" class="reset-btn" title="Réinitialiser">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </a>
                                <?php endif; ?>

                            </form>
                        </div>

                        <div class="catalog-head">
                            <strong>Catalogue des formations</strong>
                            <span><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></span>
                        </div>

                        <div class="formation-grid">

                            <?php if ($formations): ?>

                                <?php foreach ($formations as $formation): ?>

                                    <?php
$imageUrl = resolveFormationImage($formation['img_form'] ?? '');
$status = (string) ($formation['statut_form'] ?? '');
$videoIntro = $hasVideoColumn && !empty($formation['video_intro_form']);

$categoryName = $formation['nom_cat'] ?? 'Catégorie non définie';

$formateurName = 'Formateur non défini';
if ($hasFormateurs) {
    $fullName = trim(($formation['formateur_prenom'] ?? '') . ' ' . ($formation['formateur_nom'] ?? ''));
    if ($fullName !== '') {
        $formateurName = $fullName;
    }
}

$modules = isset($formation['nombre_modules']) ? (int) $formation['nombre_modules'] : null;

$price = (float) ($formation['prix_form'] ?? 0);
?>

                                    <article class="formation-card">

                                        <div class="formation-cover">

                                            <?php if ($imageUrl): ?>
                                                <img src="<?= e($imageUrl) ?>" alt="<?= e($formation['titre_form']) ?>">
                                            <?php else: ?>
                                                <div class="cover-placeholder">
                                                    <i class="fa-solid fa-book-open"></i>
                                                </div>
                                            <?php endif; ?>

                                            <span class="status-badge <?= e(statusClass($status)) ?>">
                                                <?= e(statusLabel($status)) ?>
                                            </span>

                                            <?php if ($videoIntro): ?>
                                                <span class="video-badge" title="Vidéo d'introduction disponible">
                                                    <i class="fa-solid fa-play"></i>
                                                </span>
                                            <?php endif; ?>

                                        </div>

                                        <div class="formation-body">

                                            <span class="category">
                                                <?= e($categoryName) ?>
                                            </span>

                                            <h2 class="formation-title">
                                                <?= e($formation['titre_form']) ?>
                                            </h2>

                                            <p class="description">
                                                <?= e($formation['descrip_form']) ?>
                                            </p>

                                            <div class="formateur">
                                                <i class="fa-solid fa-user-tie"></i>
                                                <span>Formateur :</span>
                                                <strong><?= e($formateurName) ?></strong>
                                            </div>

                                            <div class="meta">

                                                <span class="meta-item">
                                                    <i class="fa-regular fa-clock"></i>
                                                    <?= e(formatDuration($formation['duree_form'] ?? 0)) ?>
                                                </span>

                                                <?php if (!empty($formation['niveau_form'])): ?>
                                                    <span class="meta-item">
                                                        <i class="fa-solid fa-signal"></i>
                                                        <?= e($formation['niveau_form']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($modules !== null): ?>
                                                    <span class="meta-item">
                                                        <i class="fa-solid fa-layer-group"></i>
                                                        <?= $modules ?> module<?= $modules > 1 ? 's' : '' ?>
                                                    </span>
                                                <?php endif; ?>

                                            </div>

                                            <div class="formation-footer">

                                                <div class="price <?= $price <= 0 ? 'free' : '' ?>">
                                                    <?= $price > 0 ? number_format($price, 0, ',', ' ') . ' FCFA' : 'Gratuite' ?>
                                                </div>

                                                <div class="actions">

                                                    <a
                                                        href="voir_formation.php?id=<?= (int) $formation['id_form'] ?>"
                                                        class="action view"
                                                        title="Voir"
                                                    >
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>

                                                    <a
                                                        href="modifier_formation.php?id=<?= (int) $formation['id_form'] ?>"
                                                        class="action edit"
                                                        title="Modifier"
                                                    >
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>

                                                    <a
                                                        href="supprimer_formation.php?id=<?= (int) $formation['id_form'] ?>"
                                                        class="action delete"
                                                        title="Supprimer"
                                                        onclick="return confirm('Voulez-vous vraiment supprimer cette formation ?');"
                                                    >
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>

                                                </div>

                                            </div>

                                        </div>
                                    </article>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="empty">
                                    <i class="fa-solid fa-book-open"></i>

                                    <strong>
                                        <?= $recherche || $categorie || $niveau || $statut ? 'Aucune formation ne correspond à vos critères' : 'Aucune formation pour le moment' ?>
                                    </strong>

                                    <p>
                                        <?= $recherche || $categorie || $niveau || $statut ? 'Modifiez vos filtres ou réinitialisez la recherche pour afficher davantage de formations.' : 'Commencez par créer votre première formation TOUN. Vous pourrez ensuite lui ajouter ses modules, leçons, vidéos, documents et évaluations.' ?>
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">

                                <?php if ($page > 1): ?>
                                    <a class="page-link" href="<?= e(queryUrl(['page' => $page - 1])) ?>">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                ?>

                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <a
                                        class="page-link <?= $i === $page ? 'active' : '' ?>"
                                        href="<?= e(queryUrl(['page' => $i])) ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a class="page-link" href="<?= e(queryUrl(['page' => $page + 1])) ?>">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>

                            </div>
                        <?php endif; ?>

                    </section>
                </main>
            </div>
        </body>
    </html>

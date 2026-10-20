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
$photo_url = '';

if (!empty($photo)) {
    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        $photo_url = $photo;
    } else {
        $nom_photo = basename($photo);
        $candidats = [
            [__DIR__ . '/' . $photo, $photo],
            [__DIR__ . '/../uploads/profils/' . $nom_photo, '../uploads/profils/' . rawurlencode($nom_photo)],
            [__DIR__ . '/../uploads/photos/' . $nom_photo, '../uploads/photos/' . rawurlencode($nom_photo)],
            [__DIR__ . '/../uploads/' . $nom_photo, '../uploads/' . rawurlencode($nom_photo)],
            [__DIR__ . '/../assets/uploads/' . $nom_photo, '../assets/uploads/' . rawurlencode($nom_photo)],
            [__DIR__ . '/../images/profils/' . $nom_photo, '../images/profils/' . rawurlencode($nom_photo)],
            [__DIR__ . '/../images/' . $nom_photo, '../images/' . rawurlencode($nom_photo)]
        ];

        foreach ($candidats as $candidat) {
            if (is_file($candidat[0])) {
                $photo_url = $candidat[1];
                break;
            }
        }
    }
}

$recherche = trim($_GET['q'] ?? '');
$role_filtre = trim($_GET['role'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$par_page = 10;
$offset = ($page - 1) * $par_page;

$where = [];
$params = [];

if ($recherche !== '') {
    $where[] = '(nom_user LIKE :q OR prenom_user LIKE :q OR email_user LIKE :q OR tel_user LIKE :q)';
    $params['q'] = '%' . $recherche . '%';
}

if ($role_filtre !== '') {
    $where[] = 'role_user = :role';
    $params['role'] = $role_filtre;
}

$where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

try {
    $stmt = $con->prepare("SELECT COUNT(*) FROM t_utilisateurs $where_sql");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $total_pages = max(1, (int) ceil($total / $par_page));

    $sql = "
        SELECT id_user, nom_user, prenom_user, email_user, tel_user,
               photo_user, role_user, statut_user, date_inscrip_user
        FROM t_utilisateurs
        $where_sql
        ORDER BY date_inscrip_user DESC, id_user DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $con->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $stmt->bindValue(':limit', $par_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $roles = $con
        ->query(
            "
        SELECT DISTINCT role_user
        FROM t_utilisateurs
        WHERE role_user IS NOT NULL AND role_user <> ''
        ORDER BY role_user
    "
        )
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $utilisateurs = [];
    $roles = [];
    $total = 0;
    $total_pages = 1;
    $erreur = 'Impossible de charger les utilisateurs. Vérifiez la connexion à la base de données.';
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function roleLabel($role)
{
    $labels = [
        'admin' => 'Administrateur',
        'formateur' => 'Formateur',
        'gest_entreprise' => 'Entreprise',
        'apprenant' => 'Professionnel'
    ];
    return $labels[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

function initiales($prenom, $nom)
{
    $a = mb_substr(trim($prenom), 0, 1, 'UTF-8');
    $b = mb_substr(trim($nom), 0, 1, 'UTF-8');
    return mb_strtoupper($a . $b, 'UTF-8');
}

function photoUtilisateur($photo)
{
    if (empty($photo)) {
        return '';
    }

    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $nom_photo = basename($photo);

    $candidats = [
        [__DIR__ . '/' . $photo, $photo],
        [__DIR__ . '/../uploads/profils/' . $nom_photo, '../uploads/profils/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../uploads/photos/' . $nom_photo, '../uploads/photos/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../uploads/' . $nom_photo, '../uploads/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../assets/uploads/' . $nom_photo, '../assets/uploads/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../images/profils/' . $nom_photo, '../images/profils/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../images/' . $nom_photo, '../images/' . rawurlencode($nom_photo)]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat[0])) {
            return $candidat[1];
        }
    }

    return '';
}

$query_base = '';
if ($recherche !== '') {
    $query_base .= '&q=' . urlencode($recherche);
}
if ($role_filtre !== '') {
    $query_base .= '&role=' . urlencode($role_filtre);
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Utilisateurs - TOUN</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html,
            body {
                min-height: 100%;
            }

            body {
                font-family: "Poppins", sans-serif;
                background: #f5f8fc;
                color: #18213f;
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

            .main {
                width: calc(100% - 260px);
                min-height: 100vh;
                margin-left: 260px;
                padding-top: 64px;
            }

            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                z-index: 1000;
                width: 260px;
                height: 100vh;
                padding: 8px 11px 7px;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                background: linear-gradient(180deg, #18213f 0%, #24365f 55%, #18213f 100%);
                box-shadow: 6px 0 24px rgba(24, 33, 63, 0.08);
            }

            .logo {
                height: 50px;
                min-height: 50px;
                margin-bottom: 2px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .logo img {
                width: 145px;
                max-height: 42px;
                object-fit: contain;
            }

            .sidebar-menu {
                display: flex;
                flex-direction: column;
                gap: 1px;
            }

            .menu-title {
                display: block;
                padding: 5px 9px 3px;
                color: rgba(255, 255, 255, 0.4);
                font-size: 10.5px;
                font-weight: 600;
                letter-spacing: 0.7px;
                text-transform: uppercase;
            }

            .menu-link {
                height: 32px;
                min-height: 32px;
                padding: 0 10px;
                display: flex;
                align-items: center;
                gap: 10px;
                border-radius: 8px;
                color: rgba(255, 255, 255, 0.74);
                font-size: 12px;
                font-weight: 500;
                transition:
                    background 0.2s ease,
                    color 0.2s ease,
                    transform 0.2s ease;
            }

            .menu-link i {
                width: 16px;
                text-align: center;
                font-size: 12px;
            }

            .menu-link:hover {
                color: #fff;
                background: rgba(255, 255, 255, 0.08);
                transform: translateX(1px);
            }

            .menu-link.active {
                color: #fff;
                background: linear-gradient(90deg, rgba(32, 161, 184, 0.3), rgba(63, 104, 174, 0.44));
                box-shadow: inset 3px 0 #20a1b8;
            }

            .menu-separator {
                height: 1px;
                margin: 4px 7px;
                background: rgba(255, 255, 255, 0.09);
            }

            .sidebar-bottom {
                margin-top: auto;
                padding-top: 4px;
            }

            .logout {
                color: #ffb8bd;
            }

            .logout:hover {
                color: #fff;
                background: rgba(217, 87, 98, 0.12);
            }

            .topbar {
                position: fixed;
                top: 0;
                left: 260px;
                right: 0;
                z-index: 900;
                height: 64px;
                padding: 0 22px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: rgba(255, 255, 255, 0.96);
                border-bottom: 1px solid #e7edf4;
                backdrop-filter: blur(12px);
            }

            .search {
                width: 290px;
                height: 36px;
                padding: 0 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                border: 1px solid #e1e7ef;
                border-radius: 10px;
                background: #fff;
            }

            .search i {
                color: #8d99ab;
                font-size: 12px;
            }

            .search input {
                width: 100%;
                border: 0;
                outline: 0;
                color: #18213f;
                background: transparent;
                font-size: 11.5px;
            }

            .top-right {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            .notification {
                position: relative;
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 9px;
                color: #3f68ae;
                background: #f1f5fa;
            }

            .notification span {
                position: absolute;
                top: 6px;
                right: 6px;
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #20a1b8;
            }

            .profile {
                padding-left: 13px;
                display: flex;
                align-items: center;
                gap: 8px;
                border-left: 1px solid #e5eaf1;
            }

            .profile-info {
                text-align: right;
            }

            .profile-name {
                font-size: 11px;
                font-weight: 600;
            }

            .profile-role {
                margin-top: 1px;
                color: #8994a7;
                font-size: 10px;
            }

            .profile-avatar,
            .user-avatar {
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                font-weight: 700;
            }

            .profile-avatar {
                width: 38px;
                height: 38px;
                border: 2px solid #e2f3f5;
                font-size: 11.5px;
            }

            .profile-avatar img {
                width: 34px;
                height: 34px;
                object-fit: cover;
                border-radius: 50%;
            }

            .content {
                padding: 16px 20px 22px;
            }

            .page-head {
                min-height: 42px;
                margin-bottom: 11px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
            }

            .page-title h1 {
                margin-bottom: 2px;
                font-size: 21px;
                line-height: 1.2;
            }

            .page-title p {
                color: #8995a8;
                font-size: 11px;
            }

            .btn-primary {
                height: 36px;
                padding: 0 14px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 0;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
                box-shadow: 0 6px 15px rgba(63, 104, 174, 0.16);
                font-size: 11px;
                font-weight: 600;
                transition:
                    transform 0.2s ease,
                    box-shadow 0.2s ease;
            }

            .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 9px 18px rgba(63, 104, 174, 0.22);
            }

            .summary-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 9px;
                margin-bottom: 10px;
            }

            .summary-card {
                min-height: 58px;
                padding: 9px 11px;
                display: flex;
                align-items: center;
                gap: 9px;
                border: 1px solid #e7edf4;
                border-radius: 11px;
                background: #fff;
                box-shadow: 0 4px 14px rgba(31, 54, 87, 0.035);
            }

            .summary-icon {
                width: 34px;
                height: 34px;
                min-width: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 9px;
                color: #20a1b8;
                background: #eaf7f8;
                font-size: 13px;
            }

            .summary-card strong {
                display: block;
                color: #18213f;
                font-size: 16px;
                line-height: 1.1;
            }

            .summary-card span {
                color: #8b96a8;
                font-size: 10.5px;
            }

            .filters {
                margin-bottom: 10px;
                padding: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
                border: 1px solid #e7edf4;
                border-radius: 11px;
                background: #fff;
            }

            .filter-search {
                flex: 1;
                min-width: 180px;
                height: 35px;
                padding: 0 10px;
                display: flex;
                align-items: center;
                gap: 7px;
                border: 1px solid #e1e7ef;
                border-radius: 8px;
            }

            .filter-search i {
                color: #9aa5b6;
                font-size: 11.5px;
            }

            .filter-search input {
                width: 100%;
                border: 0;
                outline: 0;
                color: #18213f;
                font-size: 11px;
            }

            .filter-select {
                min-width: 145px;
                height: 35px;
                padding: 0 10px;
                border: 1px solid #e1e7ef;
                border-radius: 8px;
                outline: 0;
                color: #536078;
                background: #fff;
                font-size: 11px;
            }

            .btn-filter,
            .btn-reset {
                height: 35px;
                padding: 0 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
            }

            .btn-filter {
                border: 0;
                color: #fff;
                background: #18213f;
            }

            .btn-filter:hover {
                background: #3f68ae;
            }

            .btn-reset {
                border: 1px solid #e1e7ef;
                color: #6f7b8f;
                background: #fff;
            }

            .btn-reset:hover {
                color: #3f68ae;
                border-color: #bfcde0;
            }

            .table-card {
                overflow: hidden;
                border: 1px solid #e5ebf2;
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 5px 18px rgba(31, 54, 87, 0.04);
            }

            .table-head {
                min-height: 40px;
                padding: 9px 13px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #edf1f5;
            }

            .table-head h2 {
                font-size: 12px;
            }

            .table-head span {
                color: #8a96aa;
                font-size: 10.5px;
            }

            .table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            table {
                width: 100%;
                min-width: 760px;
                border-collapse: collapse;
            }

            th {
                padding: 7px 12px;
                text-align: left;
                color: #7c8799;
                background: #f8fafc;
                border-bottom: 1px solid #e9eef4;
                font-size: 10px;
                font-weight: 600;
                letter-spacing: 0.3px;
                text-transform: uppercase;
            }

            td {
                padding: 7px 12px;
                color: #536078;
                border-bottom: 1px solid #eef2f6;
                font-size: 11px;
            }

            tbody tr {
                transition: background 0.15s ease;
            }

            tbody tr:hover {
                background: #f9fbfd;
            }

            tbody tr:last-child td {
                border-bottom: 0;
            }

            .user-cell {
                min-width: 170px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .user-avatar {
                width: 31px;
                height: 31px;
                min-width: 31px;
                font-size: 10.5px;
            }

            .user-avatar img {
                width: 31px;
                height: 31px;
                object-fit: cover;
                border-radius: 50%;
            }

            .user-name {
                color: #18213f;
                font-size: 11px;
                font-weight: 600;
            }

            .user-email {
                margin-top: 1px;
                color: #929daf;
                font-size: 10px;
            }

            .role,
            .status {
                display: inline-flex;
                align-items: center;
                border-radius: 6px;
                font-size: 10px;
                font-weight: 600;
            }

            .role {
                padding: 4px 7px;
                color: #3f68ae;
                background: #edf3fb;
            }

            .role.role-admin {
                color: #18213f;
                background: #e8eaf1;
            }

            .role.role-formateur {
                color: #7958bc;
                background: #f1ecfa;
            }

            .role.role-gest_entreprise {
                color: #16879b;
                background: #e6f6f9;
            }

            .status {
                gap: 4px;
                padding: 4px 7px;
            }

            .status::before {
                content: "";
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
            }

            .status.active {
                color: #168b61;
                background: #eaf8f2;
            }

            .status.inactive {
                color: #d05763;
                background: #fff0f2;
            }

            .date {
                white-space: nowrap;
                color: #7e8a9e;
            }

            .actions {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .action {
                width: 26px;
                height: 26px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 7px;
                font-size: 11px;
                cursor: pointer;
                transition:
                    transform 0.15s ease,
                    background 0.15s ease,
                    color 0.15s ease;
            }

            .action:hover {
                transform: translateY(-1px);
            }

            .action.view {
                color: #1595a5;
                background: #e8f7f8;
            }

            .action.edit {
                color: #3f68ae;
                background: #eaf0fb;
            }

            .action.delete {
                color: #d55763;
                background: #ffedef;
            }

            .action.view:hover {
                color: #fff;
                background: #20a1b8;
            }

            .action.edit:hover {
                color: #fff;
                background: #3f68ae;
            }

            .action.delete:hover {
                color: #fff;
                background: #d55763;
            }

            .empty {
                padding: 38px 20px;
                text-align: center;
                color: #8b96a8;
            }

            .empty i {
                margin-bottom: 7px;
                color: #b4c0cf;
                font-size: 23px;
            }

            .empty h3 {
                margin-bottom: 3px;
                color: #536078;
                font-size: 12px;
            }

            .empty p {
                font-size: 10.5px;
            }

            .success-box {
                margin-bottom: 10px;
                padding: 9px 12px;
                border: 1px solid #c9ecd9;
                border-radius: 8px;
                color: #168b61;
                background: #eaf8f2;
                font-size: 11px;
            }

            .error-box {
                margin-bottom: 10px;
                padding: 9px 12px;
                border: 1px solid #f3d5d9;
                border-radius: 8px;
                color: #c84e5a;
                background: #fff1f3;
                font-size: 11px;
            }

            .pagination {
                min-height: 42px;
                padding: 7px 13px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-top: 1px solid #edf1f5;
            }

            .pagination-info {
                color: #8b96a8;
                font-size: 10.5px;
            }

            .pages {
                display: flex;
                align-items: center;
                gap: 3px;
            }

            .page {
                width: 26px;
                height: 26px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #e2e8ef;
                border-radius: 6px;
                color: #65728a;
                background: #fff;
                font-size: 10.5px;
            }

            .page:hover {
                color: #3f68ae;
                border-color: #3f68ae;
            }

            .page.active {
                color: #fff;
                border-color: #3f68ae;
                background: #3f68ae;
            }

            @media (max-width: 1100px) {
                .summary-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .filters {
                    flex-wrap: wrap;
                }

                .filter-search {
                    flex-basis: 100%;
                }
            }

            .logo {
                height: 43px;
                min-height: 43px;
            }

            .logo img {
                max-height: 33px;
            }

            .menu-title {
                padding-top: 3px;
                padding-bottom: 2px;
            }

            .menu-link {
                height: 30px;
                min-height: 30px;
                font-size: 11.5px;
            }

            .menu-separator {
                margin: 3px 7px;
            }
        </style>
    </head>

    <body>
        <div class="layout">

            <?php
$active_page = 'utilisateur.php';
include 'menu.php';
?>

            <main class="main">
                <section class="content">

                    <div class="page-head">
                        <div class="page-title">
                            <h1>Utilisateurs</h1>
                            <p>Gérez les comptes et les accès de la plateforme TOUN.</p>
                        </div>

                        <a href="inscription.php" class="btn-primary">
                            <i class="fa-solid fa-user-plus"></i>
                            Ajouter un utilisateur
                        </a>
                    </div>

                    <?php if (isset($erreur)): ?>
                        <div class="error-box">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?= e($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-icon"><i class="fa-solid fa-users"></i></div>
                            <div>
                                <strong><?= number_format($total, 0, ',', ' ') ?></strong>
                                <span>Utilisateurs trouvés</span>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-icon"><i class="fa-solid fa-user-graduate"></i></div>
                            <div>
                                <?php
try {
    $nb_apprenants = (int) $con
        ->query("SELECT COUNT(*) FROM t_utilisateurs WHERE role_user = 'apprenant'")
        ->fetchColumn();
} catch (PDOException $e) {
    $nb_apprenants = 0;
}
?>
                                <strong><?= number_format($nb_apprenants, 0, ',', ' ') ?></strong>
                                <span>Professionnels</span>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-icon"><i class="fa-solid fa-user-tie"></i></div>
                            <div>
                                <?php
try {
    $nb_formateurs = (int) $con
        ->query("SELECT COUNT(*) FROM t_utilisateurs WHERE role_user = 'formateur'")
        ->fetchColumn();
} catch (PDOException $e) {
    $nb_formateurs = 0;
}
?>
                                <strong><?= number_format($nb_formateurs, 0, ',', ' ') ?></strong>
                                <span>Formateurs</span>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-icon"><i class="fa-solid fa-building"></i></div>
                            <div>
                                <?php
try {
    $nb_entreprises = (int) $con
        ->query("SELECT COUNT(*) FROM t_utilisateurs WHERE role_user = 'gest_entreprise'")
        ->fetchColumn();
} catch (PDOException $e) {
    $nb_entreprises = 0;
}
?>
                                <strong><?= number_format($nb_entreprises, 0, ',', ' ') ?></strong>
                                <span>Entreprises</span>
                            </div>
                        </div>
                    </div>

                    <form method="get" class="filters">
                        <div class="filter-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="q" placeholder="Rechercher par nom, prénom, e-mail ou téléphone..." value="<?= e($recherche) ?>">
                        </div>

                        <select name="role" class="filter-select">
                            <option value="">Tous les rôles</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= e($role) ?>" <?= $role_filtre === $role ? 'selected' : '' ?>>
                                    <?= e(roleLabel($role)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn-filter">
                            <i class="fa-solid fa-filter"></i> Filtrer
                        </button>

                        <a href="utilisateur.php" class="btn-reset">Réinitialiser</a>
                    </form>

                    <div class="table-card">
                        <div class="table-head">
                            <div>
                                <h2>Liste des utilisateurs</h2>
                                <span><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></span>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <?php if ($utilisateurs): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Téléphone</th>
                                            <th>Rôle</th>
                                            <th>Statut</th>
                                            <th>Inscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($utilisateurs as $user): ?>
                                            <?php
$photo_user = photoUtilisateur($user['photo_user'] ?? '');
$actif = strtolower((string) ($user['statut_user'] ?? '')) === 'actif';
?>
                                            <tr>
                                                <td>
                                                    <div class="user-cell">
                                                        <div class="user-avatar">
                                                            <?php if ($photo_user): ?>
                                                                <img src="<?= e($photo_user) ?>" alt="">
                                                            <?php else: ?>
                                                                <?= e(initiales($user['prenom_user'], $user['nom_user'])) ?>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div>
                                                            <div class="user-name">
                                                                <?= e(trim($user['prenom_user'] . ' ' . $user['nom_user'])) ?>
                                                            </div>
                                                            <div class="user-email"><?= e($user['email_user']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td><?= e($user['tel_user'] ?: '—') ?></td>

                                                <td>
                                                    <span class="role role-<?= e($user['role_user']) ?>"><?= e(roleLabel($user['role_user'])) ?></span>
                                                </td>

                                                <td>
                                                    <span class="status <?= $actif ? 'active' : 'inactive' ?>">
                                                        <?= $actif ? 'Actif' : 'Inactif' ?>
                                                    </span>
                                                </td>

                                                <td class="date">
                                                    <?= !empty($user['date_inscrip_user']) ? date('d/m/Y', strtotime($user['date_inscrip_user'])) : '—' ?>
                                                </td>

                                                <td>
                                                    <div class="actions">
                                                        <a class="action" href="voir_utilisateur.php?id=<?= (int) $user['id_user'] ?>" title="Voir">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                        <a class="action" href="modifier_utilisateur.php?id=<?= (int) $user['id_user'] ?>" title="Modifier">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </a>
                                                        <a class="action delete" href="supprimer_utilisateur.php?id=<?= (int) $user['id_user'] ?>" title="Supprimer"
                                                            onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');">
                                                            <i class="fa-regular fa-trash-can"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <h3>Aucun utilisateur trouvé</h3>
                                    <p>Modifiez votre recherche ou ajoutez un nouvel utilisateur.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <div class="pagination-info">
                                    Page <?= $page ?> sur <?= $total_pages ?>
                                </div>

                                <div class="pages">
                                    <?php if ($page > 1): ?>
                                        <a class="page" href="?page=<?= $page - 1 . $query_base ?>">
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                                                $debut = max(1, $page - 2);
                                                                $fin = min($total_pages, $page + 2);
                                                                for ($i = $debut; $i <= $fin; $i++):
                                                                ?>
                                        <a class="page <?= $i === $page ? 'active' : '' ?>" href="?page=<?= $i . $query_base ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a class="page" href="?page=<?= $page + 1 . $query_base ?>">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </section>
            </main>
        </div>
    </body>
</html>

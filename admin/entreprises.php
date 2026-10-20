<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$entreprises = [];
$erreur = '';

try {
    $sql = "SELECT
                e.id_entreprise,
                e.id_user,
                e.nom_entreprise,
                e.email_entreprise,
                e.tel_entreprise,
                e.sect_activite,
                e.ad_entreprise,
                e.logo_entreprise,
                e.statut_entreprise,
                e.date_creation_entreprise,
                u.nom_user,
                u.prenom_user
            FROM t_entreprises e
            LEFT JOIN t_utilisateurs u ON e.id_user = u.id_user
            ORDER BY e.date_creation_entreprise DESC";

    $stmt = $con->query($sql);
    $entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erreur = 'Impossible de récupérer les entreprises.';
}

$total = count($entreprises);
$actives = 0;
$inactives = 0;

foreach ($entreprises as $entreprise) {
    if (strtolower(trim($entreprise['statut_entreprise'] ?? '')) === 'actif') {
        $actives++;
    } else {
        $inactives++;
    }
}

$nom = $_SESSION['nom_user'] ?? '';
$prenom = $_SESSION['prenom_user'] ?? '';
$photo = $_SESSION['photo_user'] ?? '';

function initiales(string $prenom, string $nom): string
{
    $a = mb_substr(trim($prenom), 0, 1, 'UTF-8');
    $b = mb_substr(trim($nom), 0, 1, 'UTF-8');
    return mb_strtoupper($a . $b, 'UTF-8');
}

$photo_url = '';

if (!empty($photo)) {
    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');
    $nom_photo = basename($photo);

    $candidats = [
        [__DIR__ . '/' . $photo, $photo],
        [__DIR__ . '/../uploads/profils/' . $nom_photo, '../uploads/profils/' . rawurlencode($nom_photo)],
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
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Entreprises — TOUN</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: Inter, Arial, sans-serif;
                background: #eef4f7;
                color: #172b4d;
                min-height: 100vh;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 260px;
                height: 100vh;
                padding: 10px 12px 8px;
                background: linear-gradient(180deg, #18213f 0%, #24365f 55%, #18213f 100%);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                z-index: 1000;
            }

            .logo {
                height: 50px;
                min-height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding-bottom: 6px;
                margin-bottom: 3px;
                border-bottom: 1px solid rgba(32, 161, 184, 0.18);
            }

            .logo img {
                width: 148px;
                max-height: 38px;
                object-fit: contain;
            }

            .sidebar-menu {
                flex: 1;
                overflow: hidden;
                padding: 1px;
            }

            .menu-title {
                display: block;
                padding: 7px 10px 4px;
                color: #20a1b8;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1.15px;
            }

            .nav-link {
                position: relative;
                display: flex;
                align-items: center;
                gap: 11px;
                width: 100%;
                height: 34px;
                padding: 0 11px;
                margin: 1px 0;
                border-radius: 9px;
                color: rgba(255, 255, 255, 0.84);
                font-size: 12px;
                font-weight: 500;
                transition: 0.2s ease;
            }

            .nav-link i {
                width: 19px;
                min-width: 19px;
                text-align: center;
                font-size: 13.5px;
            }

            .nav-link:hover {
                background: rgba(32, 161, 184, 0.1);
                color: #fff;
            }

            .nav-link.active {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                box-shadow: 0 5px 14px rgba(32, 161, 184, 0.2);
            }

            .nav-link.active::before {
                content: "";
                position: absolute;
                left: -12px;
                top: 6px;
                width: 3px;
                height: 22px;
                border-radius: 0 4px 4px 0;
                background: #20a1b8;
            }

            .separator {
                height: 1px;
                margin: 4px 8px;
                background: rgba(255, 255, 255, 0.08);
            }

            .sidebar-bottom {
                flex-shrink: 0;
                padding-top: 6px;
                margin-top: 3px;
                border-top: 1px solid rgba(32, 161, 184, 0.18);
            }

            .sidebar-bottom .nav-link {
                background: rgba(32, 161, 184, 0.07);
                border: 1px solid rgba(32, 161, 184, 0.14);
            }

            .sidebar-bottom .nav-link i {
                color: #20a1b8;
            }

            .main {
                width: calc(100% - 260px);
                margin-left: 260px;
                min-height: 100vh;
                padding-top: 62px;
            }

            .topbar {
                position: fixed;
                top: 0;
                left: 260px;
                right: 0;
                height: 62px;
                padding: 0 20px;
                background: #fff;
                border-bottom: 1px solid #edf0f5;
                display: flex;
                align-items: center;
                justify-content: space-between;
                z-index: 999;
            }

            .search {
                width: 320px;
                height: 42px;
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 0 13px;
                border: 1px solid #e5ebf1;
                border-radius: 14px;
                background: #f8fafd;
            }

            .search i {
                color: #9aa9bb;
                font-size: 17px;
            }

            .search input {
                width: 100%;
                height: 100%;
                border: 0;
                outline: 0;
                background: transparent;
                color: #52667d;
                font-family: inherit;
                font-size: 12px;
            }

            .search input::placeholder {
                color: #718198;
            }

            .top-right {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-left: auto;
            }

            .notification {
                width: 42px;
                height: 42px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #e5ebf1;
                border-radius: 13px;
                background: #f8fafd;
                color: #18213f;
            }

            .notification i {
                font-size: 18px;
            }

            .notification-badge {
                position: absolute;
                top: 8px;
                right: 8px;
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: #20a1b8;
                border: 1px solid #fff;
            }

            .profile {
                height: 50px;
                min-width: 205px;
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 4px 13px 4px 5px;
                border: 1px solid #e4ebf1;
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 4px 15px rgba(24, 33, 63, 0.05);
            }

            .avatar {
                width: 42px;
                height: 42px;
                min-width: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border-radius: 50%;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
                color: #fff;
                font-size: 12px;
                font-weight: 700;
            }

            .avatar img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 50%;
            }

            .profile strong {
                display: block;
                color: #18213f;
                font-size: 12px;
                font-weight: 700;
            }

            .profile span {
                display: block;
                margin-top: 2px;
                color: #8b96a7;
                font-size: 11px;
            }

            .content {
                max-width: 1280px;
                margin: 0 auto;
                padding: 18px 28px 30px;
            }

            .page-head {
                min-height: 76px;
                margin-bottom: 14px;
                padding: 15px 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                border-radius: 16px;
                background: linear-gradient(120deg, #173e73 0%, #246ca2 55%, #20a1b8 100%);
                color: #fff;
                box-shadow: 0 12px 30px rgba(30, 75, 115, 0.15);
            }

            .eyebrow {
                margin-bottom: 4px;
                color: rgba(255, 255, 255, 0.72);
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 1.5px;
                text-transform: uppercase;
            }

            .page-head h1 {
                margin-bottom: 3px;
                font-size: 21px;
                line-height: 1.2;
            }

            .page-head p {
                color: rgba(255, 255, 255, 0.84);
                font-size: 11px;
            }

            .btn-add {
                height: 34px;
                padding: 0 14px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                border: 1px solid rgba(255, 255, 255, 0.25);
                border-radius: 9px;
                background: #fff;
                color: #24639a;
                font-family: inherit;
                font-size: 11px;
                font-weight: 800;
                transition: 0.2s ease;
            }

            .btn-add:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
                margin-bottom: 14px;
            }

            .stat-card {
                min-height: 78px;
                padding: 13px 15px;
                display: flex;
                align-items: center;
                gap: 12px;
                border: 1px solid #dfe8ed;
                border-radius: 13px;
                background: #fff;
                box-shadow: 0 6px 18px rgba(24, 33, 63, 0.05);
            }

            .stat-icon {
                width: 42px;
                height: 42px;
                min-width: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 11px;
                background: #edf8fa;
                color: #20a1b8;
                font-size: 17px;
            }

            .stat-card:nth-child(2) .stat-icon {
                background: #edf3fb;
                color: #3f68ae;
            }

            .stat-card:nth-child(3) .stat-icon {
                background: #f3f5f7;
                color: #748397;
            }

            .stat-label {
                color: #8190a1;
                font-size: 10.5px;
                font-weight: 600;
                margin-bottom: 3px;
            }

            .stat-value {
                color: #18213f;
                font-size: 19px;
                font-weight: 800;
            }

            .table-card {
                overflow: hidden;
                border: 1px solid #dfe8ed;
                border-radius: 14px;
                background: #fff;
                box-shadow: 0 7px 22px rgba(24, 33, 63, 0.06);
            }

            .table-head {
                min-height: 54px;
                padding: 0 17px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #edf1f4;
            }

            .table-title {
                color: #18213f;
                font-size: 12px;
                font-weight: 800;
            }

            .table-count {
                color: #8795a5;
                font-size: 10.5px;
            }

            .table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                min-width: 850px;
            }

            th {
                padding: 10px 12px;
                background: #f8fafb;
                color: #718198;
                font-size: 10px;
                font-weight: 800;
                text-align: left;
                text-transform: uppercase;
                letter-spacing: 0.45px;
                border-bottom: 1px solid #e9eff2;
            }

            td {
                padding: 9px 12px;
                color: #4d6278;
                font-size: 11px;
                border-bottom: 1px solid #eef2f4;
                vertical-align: middle;
            }

            tbody tr:hover {
                background: #fbfdfe;
            }

            .company {
                display: flex;
                align-items: center;
                gap: 9px;
            }

            .company-logo {
                width: 34px;
                height: 34px;
                min-width: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border-radius: 9px;
                background: linear-gradient(135deg, #eaf7f9, #eaf0fa);
                color: #3f68ae;
                font-size: 12.5px;
                font-weight: 800;
            }

            .company-logo img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .company-name {
                color: #263e5b;
                font-size: 11px;
                font-weight: 800;
            }

            .company-manager {
                margin-top: 2px;
                color: #93a0ae;
                font-size: 10px;
            }

            .email {
                color: #587088;
            }

            .status {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: 800;
            }

            .status::before {
                content: "";
                width: 5px;
                height: 5px;
                border-radius: 50%;
            }

            .status.active {
                background: #eaf8f1;
                color: #21865c;
            }

            .status.active::before {
                background: #2eb879;
            }

            .status.inactive {
                background: #f3f5f7;
                color: #778597;
            }

            .status.inactive::before {
                background: #9aa7b5;
            }

            .actions {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .action {
                width: 27px;
                height: 27px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 7px;
                border: 1px solid #e1e9ee;
                background: #f8fafb;
                color: #60758a;
                font-size: 11px;
                transition: 0.2s ease;
            }

            .action:hover {
                transform: translateY(-1px);
            }

            .action.view:hover {
                color: #20a1b8;
                border-color: #b8dfe5;
                background: #eef9fb;
            }

            .action.edit:hover {
                color: #3f68ae;
                border-color: #c6d5eb;
                background: #f0f5fb;
            }

            .action.delete:hover {
                color: #c45d69;
                border-color: #edcbd0;
                background: #fff3f4;
            }

            .empty {
                padding: 45px 20px;
                text-align: center;
            }

            .empty i {
                margin-bottom: 10px;
                color: #b7c5cf;
                font-size: 27px;
            }

            .empty h3 {
                margin-bottom: 4px;
                color: #536a80;
                font-size: 12px;
            }

            .empty p {
                color: #98a6b3;
                font-size: 10.5px;
            }

            .success {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 12px;
                padding: 10px 12px;
                border: 1px solid #c9eee1;
                border-radius: 9px;
                background: #eaf9f4;
                color: #16805d;
                font-size: 10.5px;
                font-weight: 600;
            }

            .success i {
                font-size: 12.5px;
            }

            .error {
                margin-bottom: 12px;
                padding: 9px 12px;
                border: 1px solid #f1c8ce;
                border-radius: 9px;
                background: #fff1f3;
                color: #a63b4a;
                font-size: 10.5px;
            }

            @media (max-width: 950px) {
                .sidebar {
                    width: 220px;
                }

                .main {
                    width: calc(100% - 220px);
                    margin-left: 220px;
                }

                .topbar {
                    left: 220px;
                }

                .stats {
                    grid-template-columns: 1fr 1fr 1fr;
                }
            }

            @media (max-width: 720px) {
                .sidebar {
                    width: 70px;
                    padding: 8px 7px;
                }

                .logo {
                    height: 45px;
                }

                .logo img {
                    width: 45px;
                }

                .menu-title,
                .nav-link span,
                .separator {
                    display: none;
                }

                .nav-link {
                    justify-content: center;
                    padding: 0;
                }

                .main {
                    width: calc(100% - 70px);
                    margin-left: 70px;
                }

                .topbar {
                    left: 70px;
                    height: 62px;
                    padding: 0 12px;
                }

                .search {
                    width: 210px;
                    height: 38px;
                }

                .profile {
                    min-width: 42px;
                    width: 42px;
                    height: 42px;
                    padding: 0;
                    border: 0;
                    box-shadow: none;
                }

                .profile > div:last-child {
                    display: none;
                }

                .content {
                    padding: 14px;
                }

                .page-head {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .stats {
                    grid-template-columns: 1fr;
                }

                .btn-add {
                    align-self: flex-start;
                }
            }

            @media (max-width: 520px) {
                .topbar {
                    gap: 8px;
                }

                .search {
                    width: 100%;
                    max-width: none;
                }

                .top-right {
                    gap: 7px;
                }
            }

            .sidebar {
                padding: 0 6px 7px;
            }

            .logo {
                height: 66px;
                min-height: 66px;
                margin: 0 12px 3px;
                padding: 0 0 7px;
            }

            .logo img {
                width: 190px;
                max-height: 53px;
            }

            .sidebar-menu {
                padding: 2px 2px 0;
            }

            .menu-title {
                padding: 9px 10px 5px;
                font-size: 11px;
            }

            .nav-link {
                height: 48px;
                min-height: 48px;
                padding: 0 19px;
                margin: 1px 0;
                gap: 15px;
                border-radius: 14px;
                font-size: 13px;
            }

            .nav-link i {
                width: 22px;
                min-width: 22px;
                font-size: 17px;
            }

            .nav-link.active {
                box-shadow: 0 7px 18px rgba(32, 161, 184, 0.2);
            }

            .nav-link.active::before {
                display: none;
            }

            .separator {
                margin: 5px 10px;
            }

            .sidebar-bottom {
                padding: 8px 0 0;
                margin-top: 3px;
            }

            .sidebar-bottom .nav-link {
                height: 48px;
                min-height: 48px;
                padding-left: 19px;
                border-radius: 13px;
            }

            @media (max-width: 720px) {
                .sidebar {
                    padding: 8px 7px;
                }

                .logo {
                    height: 45px;
                    min-height: 45px;
                    margin: 0;
                    padding: 0;
                }

                .logo img {
                    width: 45px;
                    max-height: 36px;
                }

                .nav-link {
                    height: 34px;
                    min-height: 34px;
                    padding: 0;
                    border-radius: 9px;
                }

                .nav-link i {
                    font-size: 13.5px;
                }

                .sidebar-bottom .nav-link {
                    height: 35px;
                    min-height: 35px;
                }
            }

            .sidebar {
                width: 260px;
                padding: 10px 12px 8px;
            }

            .logo {
                height: 50px;
                min-height: 50px;
                margin: 0 0 3px;
                padding: 0 0 6px;
            }

            .logo img {
                width: 148px;
                max-height: 38px;
            }

            .sidebar-menu {
                padding: 1px;
            }

            .menu-title {
                padding: 7px 10px 4px;
                font-size: 11px;
                letter-spacing: 1.15px;
            }

            .nav-link {
                height: 34px;
                min-height: 34px;
                padding: 0 11px;
                gap: 11px;
                border-radius: 9px;
                font-size: 12px;
            }

            .nav-link i {
                width: 19px;
                min-width: 19px;
                font-size: 13.5px;
            }

            .nav-link.active::before {
                display: block;
            }

            .separator {
                margin: 4px 8px;
            }

            .sidebar-bottom {
                padding-top: 6px;
                margin-top: 3px;
            }

            .sidebar-bottom .nav-link {
                height: 35px;
                min-height: 35px;
                border-radius: 9px;
                padding-left: 11px;
            }

            .main {
                width: calc(100% - 260px);
                margin-left: 260px;
                padding-top: 62px;
            }

            .topbar {
                left: 260px;
                height: 62px;
                padding: 0 20px;
            }

            .search {
                width: 320px;
                height: 42px;
                padding: 0 13px;
                gap: 10px;
                border-radius: 14px;
            }

            .search i {
                font-size: 17px;
            }

            .search input {
                font-size: 12px;
            }

            .top-right {
                gap: 12px;
            }

            .notification {
                width: 42px;
                height: 42px;
                border-radius: 13px;
            }

            .notification i {
                font-size: 18px;
            }

            .notification-badge {
                width: 7px;
                height: 7px;
                top: 8px;
                right: 8px;
            }

            .profile {
                height: 50px;
                min-width: 205px;
                padding: 4px 13px 4px 5px;
                gap: 10px;
                border-radius: 16px;
            }

            .avatar {
                width: 42px;
                height: 42px;
                min-width: 42px;
                font-size: 12px;
                border: 0;
            }

            .profile strong {
                font-size: 12px;
            }

            .profile span {
                margin-top: 2px;
                font-size: 11px;
            }

            .content {
                max-width: 1280px;
                padding: 18px 28px 30px;
            }

            .table-card {
                width: 100%;
            }

            @media (max-width: 950px) {
                .sidebar {
                    width: 220px;
                }

                .main {
                    width: calc(100% - 220px);
                    margin-left: 220px;
                }

                .topbar {
                    left: 220px;
                }
            }

            @media (max-width: 720px) {
                .sidebar {
                    width: 70px;
                    padding: 8px 7px;
                }

                .logo {
                    height: 45px;
                    min-height: 45px;
                    margin: 0;
                    padding: 0;
                }

                .logo img {
                    width: 45px;
                    max-height: 36px;
                }

                .menu-title,
                .nav-link span,
                .separator {
                    display: none;
                }

                .nav-link {
                    height: 34px;
                    min-height: 34px;
                    padding: 0;
                    border-radius: 9px;
                    justify-content: center;
                }

                .nav-link i {
                    font-size: 13.5px;
                }

                .sidebar-bottom .nav-link {
                    height: 35px;
                    min-height: 35px;
                }

                .main {
                    width: calc(100% - 70px);
                    margin-left: 70px;
                    padding-top: 62px;
                }

                .topbar {
                    left: 70px;
                    height: 62px;
                    padding: 0 12px;
                }

                .search {
                    width: 210px;
                    height: 38px;
                }

                .notification {
                    width: 38px;
                    height: 38px;
                }

                .profile {
                    min-width: 42px;
                    width: 42px;
                    height: 42px;
                    padding: 0;
                    border: 0;
                    box-shadow: none;
                    border-radius: 50%;
                }

                .profile > div:last-child {
                    display: none;
                }

                .avatar {
                    width: 40px;
                    height: 40px;
                    min-width: 40px;
                    border-width: 1.5px;
                }

                .content {
                    padding: 14px;
                }
            }

            @media (max-width: 520px) {
                .search {
                    width: 100%;
                }

                .top-right {
                    gap: 7px;
                }
            }

            .sidebar {
                width: 260px;
                height: 100vh;
                overflow: hidden;
                padding: 8px 8px 7px;
            }

            .logo {
                flex-shrink: 0;
                height: 54px;
                min-height: 54px;
                margin: 0 10px 3px;
                padding: 0 0 5px;
            }

            .logo img {
                width: 155px;
                max-height: 40px;
            }

            .sidebar-menu {
                flex: 1;
                min-height: 0;
                overflow: hidden;
                padding: 0 2px;
            }

            .menu-title {
                padding: 5px 10px 2px;
                font-size: 11px;
                line-height: 1.15;
            }

            .nav-link {
                height: 34px;
                min-height: 34px;
                margin: 0;
                padding: 0 11px;
                gap: 11px;
                border-radius: 9px;
                font-size: 12px;
            }

            .nav-link i {
                width: 19px;
                min-width: 19px;
                font-size: 13.5px;
            }

            .separator {
                height: 1px;
                margin: 2px 8px;
            }

            .sidebar-bottom {
                flex-shrink: 0;
                padding-top: 4px;
                margin-top: 2px;
            }

            .sidebar-bottom .nav-link {
                height: 38px;
                min-height: 38px;
                padding-left: 11px;
                border-radius: 9px;
            }

            .main {
                width: calc(100% - 260px);
                margin-left: 260px;
                padding-top: 62px;
            }

            .topbar {
                left: 260px;
                height: 62px;
                padding: 0 20px;
            }

            .search {
                width: 320px;
                height: 42px;
            }

            .profile {
                height: 50px;
                min-width: 205px;
            }

            .avatar {
                width: 42px;
                height: 42px;
                min-width: 42px;
            }

            @media (max-width: 720px) {
                .sidebar {
                    width: 70px;
                    padding: 8px 7px;
                }

                .logo {
                    height: 45px;
                    min-height: 45px;
                    margin: 0;
                    padding: 0;
                }

                .logo img {
                    width: 45px;
                    max-height: 36px;
                }

                .sidebar-menu {
                    padding: 0;
                }

                .menu-title,
                .nav-link span,
                .separator {
                    display: none;
                }

                .nav-link {
                    height: 34px;
                    min-height: 34px;
                    padding: 0;
                    justify-content: center;
                }

                .sidebar-bottom .nav-link {
                    height: 35px;
                    min-height: 35px;
                }

                .main {
                    width: calc(100% - 70px);
                    margin-left: 70px;
                    padding-top: 62px;
                }

                .topbar {
                    left: 70px;
                }
            }

            .sidebar {
                width: 260px;
                height: 100vh;
                overflow: hidden;
                padding: 5px 8px 6px;
            }

            .logo {
                height: 50px;
                min-height: 50px;
                margin: 0 10px 2px;
                padding: 0 0 4px;
            }

            .logo img {
                width: 150px;
                max-height: 37px;
            }

            .sidebar-menu {
                flex: 1;
                min-height: 0;
                overflow: hidden !important;
                padding: 0 2px;
            }

            .menu-title {
                padding: 4px 10px 2px;
                font-size: 11px;
                line-height: 1.1;
            }

            .nav-link {
                height: 32px;
                min-height: 32px;
                margin: 0;
                padding: 0 11px;
                gap: 11px;
                border-radius: 9px;
                font-size: 12px;
            }

            .nav-link i {
                width: 19px;
                min-width: 19px;
                font-size: 13.5px;
            }

            .separator {
                height: 1px;
                margin: 2px 8px;
            }

            .sidebar-bottom {
                flex-shrink: 0;
                padding-top: 4px;
                margin-top: 2px;
            }

            .sidebar-bottom .nav-link {
                height: 36px;
                min-height: 36px;
                padding-left: 11px;
                border-radius: 9px;
            }

            .sidebar-menu .nav-link:last-of-type {
                margin-bottom: 0;
            }

            @media (max-width: 720px) {
                .sidebar {
                    width: 70px;
                    padding: 7px 7px 6px;
                }

                .logo {
                    height: 42px;
                    min-height: 42px;
                    margin: 0;
                    padding: 0;
                }

                .logo img {
                    width: 43px;
                    max-height: 34px;
                }

                .sidebar-menu {
                    padding: 0;
                }

                .menu-title,
                .nav-link span,
                .separator {
                    display: none;
                }

                .nav-link {
                    height: 33px;
                    min-height: 33px;
                    padding: 0;
                    justify-content: center;
                }

                .sidebar-bottom .nav-link {
                    height: 34px;
                    min-height: 34px;
                }
            }
        </style>
    </head>

    <body>

        <aside class="sidebar">

            <div class="logo">
                <a href="dashboard.php">
                    <img src="images/logo1.png" alt="TOUN">
                </a>
            </div>

            <nav class="sidebar-menu">

                <span class="menu-title">Principal</span>

                <a href="dashbord.php" class="nav-link">
                    <i class="fa-solid fa-table-cells-large"></i>
                    <span>Dashboard</span>
                </a>

                <a href="utilisateur.php" class="nav-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Utilisateurs</span>
                </a>

                <a href="formation.php" class="nav-link">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Formations</span>
                </a>

                <a href="entreprises.php" class="nav-link active">
                    <i class="fa-solid fa-building"></i>
                    <span>Entreprises</span>
                </a>

                <a href="formateurs.php" class="nav-link">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Formateurs</span>
                </a>

                <div class="separator"></div>

                <span class="menu-title">Gestion pédagogique</span>

                <a href="modules.php" class="nav-link">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Modules</span>
                </a>

                <a href="quiz.php" class="nav-link">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Quiz &amp; évaluations</span>
                </a>

                <a href="progression.php" class="nav-link">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Progression</span>
                </a>

                <a href="certificats.php" class="nav-link">
                    <i class="fa-solid fa-award"></i>
                    <span>Certificats</span>
                </a>

                <div class="separator"></div>

                <span class="menu-title">Gestion financière</span>

                <a href="paiements.php" class="nav-link">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Paiements</span>
                </a>

                <div class="separator"></div>

                <span class="menu-title">Administration</span>

                <a href="statistiques.php" class="nav-link">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Statistiques</span>
                </a>

                <a href="parametres.php" class="nav-link">
                    <i class="fa-solid fa-gear"></i>
                    <span>Paramètres</span>
                </a>

            </nav>

            <div class="sidebar-bottom">
                <a href="../deconnexion.php" class="nav-link">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Déconnexion</span>
                </a>
            </div>

        </aside>

        <main class="main">

            <header class="topbar">

                <div class="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher...">
                </div>

                <div class="top-right">

                    <div class="notification">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notification-badge"></span>
                    </div>

                    <div class="profile">

                        <div class="avatar">
                            <?php if ($photo_url !== ''): ?>
                                <img src="<?= e($photo_url) ?>" alt="Photo de profil">
                            <?php else: ?>
                                <?= e(initiales($prenom, $nom)) ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong><?= e(trim($prenom . ' ' . $nom)) ?: 'Administrateur' ?></strong>
                            <span>Administrateur</span>
                        </div>

                    </div>

                </div>

            </header>

            <div class="content">

                <section class="page-head">
                    <div>
                        <div class="eyebrow">Gestion des entreprises</div>
                        <h1>Entreprises</h1>
                        <p>Gérez les entreprises inscrites sur la plateforme TOUN.</p>
                    </div>

                    <a href="ajouter_entreprise.php" class="btn-add">
                        <i class="fa-solid fa-plus"></i>
                        Ajouter une entreprise
                    </a>
                </section>

                <?php if ($erreur !== ''): ?>
                    <div class="error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?= e($erreur) ?>
                    </div>
                <?php endif; ?>

                <section class="stats">

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div>
                            <div class="stat-label">Total entreprises</div>
                            <div class="stat-value"><?= $total ?></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <div class="stat-label">Entreprises actives</div>
                            <div class="stat-value"><?= $actives ?></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-circle-pause"></i>
                        </div>
                        <div>
                            <div class="stat-label">Entreprises inactives</div>
                            <div class="stat-value"><?= $inactives ?></div>
                        </div>
                    </div>

                </section>

                <section class="table-card">

                    <div class="table-head">
                        <div class="table-title">Liste des entreprises</div>
                        <div class="table-count"><?= $total ?> entreprise(s)</div>
                    </div>

                    <?php if (empty($entreprises) && $erreur === ''): ?>

                        <div class="empty">
                            <i class="fa-solid fa-building"></i>
                            <h3>Aucune entreprise enregistrée</h3>
                            <p>Les entreprises ajoutées apparaîtront ici.</p>
                        </div>

                    <?php else: ?>

                        <div class="table-wrap">
                            <table id="entreprisesTable">
                                <thead>
                                    <tr>
                                        <th>Entreprise</th>
                                        <th>Secteur</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Responsable</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($entreprises as $entreprise): ?>

                                        <?php
$statut = strtolower(trim($entreprise['statut_entreprise'] ?? ''));
$isActive = $statut === 'actif';

$logo = trim((string) ($entreprise['logo_entreprise'] ?? ''));
$logo_url = '';

if ($logo !== '') {
    $logo = str_replace('\\', '/', $logo);
    $logo_name = basename($logo);

    $logo_candidates = [
        [__DIR__ . '/' . $logo, $logo],
        [__DIR__ . '/../uploads/entreprises/' . $logo_name, '../uploads/entreprises/' . rawurlencode($logo_name)],
        [__DIR__ . '/../uploads/' . $logo_name, '../uploads/' . rawurlencode($logo_name)],
        [__DIR__ . '/../images/entreprises/' . $logo_name, '../images/entreprises/' . rawurlencode($logo_name)]
    ];

    foreach ($logo_candidates as $candidate) {
        if (is_file($candidate[0])) {
            $logo_url = $candidate[1];
            break;
        }
    }
}

$initialeEntreprise = mb_strtoupper(mb_substr(trim($entreprise['nom_entreprise'] ?? ''), 0, 1, 'UTF-8'), 'UTF-8');

$responsable = trim(($entreprise['prenom_user'] ?? '') . ' ' . ($entreprise['nom_user'] ?? ''));
?>

                                        <tr data-search="<?= e(($entreprise['nom_entreprise'] ?? '') . ' ' . ($entreprise['email_entreprise'] ?? '') . ' ' . ($entreprise['sect_activite'] ?? '') . ' ' . ($responsable ?? '')) ?>">

                                            <td>
                                                <div class="company">

                                                    <div class="company-logo">
                                                        <?php if ($logo_url !== ''): ?>
                                                            <img src="<?= e($logo_url) ?>" alt="Logo">
                                                        <?php else: ?>
                                                            <?= e($initialeEntreprise ?: 'E') ?>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div>
                                                        <div class="company-name">
                                                            <?= e($entreprise['nom_entreprise']) ?>
                                                        </div>

                                                        <?php if ($responsable !== ''): ?>
                                                            <div class="company-manager">
                                                                Responsable : <?= e($responsable) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                </div>
                                            </td>

                                            <td><?= e($entreprise['sect_activite'] ?: '—') ?></td>

                                            <td class="email">
                                                <?= e($entreprise['email_entreprise'] ?: '—') ?>
                                            </td>

                                            <td><?= e($entreprise['tel_entreprise'] ?: '—') ?></td>

                                            <td><?= e($responsable ?: '—') ?></td>

                                            <td>
                                                <span class="status <?= $isActive ? 'active' : 'inactive' ?>">
                                                    <?= e($entreprise['statut_entreprise'] ?: 'Inactif') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= !empty($entreprise['date_creation_entreprise']) ? e(date('d/m/Y', strtotime($entreprise['date_creation_entreprise']))) : '—' ?>
                                            </td>

                                            <td>
                                                <div class="actions">

                                                    <a class="action view"
                                                        href="voir_entreprise.php?id=<?= (int) $entreprise['id_entreprise'] ?>"
                                                        title="Voir">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>

                                                    <a class="action edit"
                                                        href="modifier_entreprise.php?id=<?= (int) $entreprise['id_entreprise'] ?>"
                                                        title="Modifier">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>

                                                    <a class="action delete"
                                                        href="supprimer_entreprise.php?id=<?= (int) $entreprise['id_entreprise'] ?>"
                                                        title="Supprimer"
                                                        onclick="return confirm('Voulez-vous vraiment supprimer cette entreprise ?');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>

                                                </div>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>

                </section>

            </div>

        </main>

        <script>
            const searchInput = document.getElementById('searchInput');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const search = this.value.toLowerCase().trim();

                    document.querySelectorAll('#entreprisesTable tbody tr').forEach(function (row) {
                        const text = (row.dataset.search || '').toLowerCase();
                        row.style.display = text.includes(search) ? '' : 'none';
                    });
                });
            }
        </script>

    </body>
</html>

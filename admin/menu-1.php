<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_page = $active_page ?? basename($_SERVER['PHP_SELF']);

if (!isset($con) || !$con instanceof PDO) {
    require_once __DIR__ . '/../config/connexion.php';
}

$admin = [];
$id_user = (int) ($_SESSION['id_user'] ?? 0);

if ($con instanceof PDO && $id_user > 0) {
    try {
        $stmt = $con->prepare(
            'SELECT nom_user, prenom_user, email_user, tel_user, photo_user
             FROM t_utilisateurs
             WHERE id_user = ?
             LIMIT 1'
        );
        $stmt->execute([$id_user]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $admin = [];
    }
}

$nom = $admin['nom_user'] ?? ($_SESSION['nom_user'] ?? '');
$prenom = $admin['prenom_user'] ?? ($_SESSION['prenom_user'] ?? '');
$email = $admin['email_user'] ?? ($_SESSION['email_user'] ?? '');
$tel = $admin['tel_user'] ?? ($_SESSION['tel_user'] ?? '');
$photo = $admin['photo_user'] ?? ($_SESSION['photo_user'] ?? '');

$nom_complet = trim($prenom . ' ' . $nom);
$initiales = strtoupper(
    mb_substr($prenom, 0, 1, 'UTF-8') .
    mb_substr($nom, 0, 1, 'UTF-8')
);
$initiales = $initiales ?: 'AD';

$photo_url = '';
if ($photo !== '') {
    $photo = basename(str_replace('\\', '/', trim($photo)));
    $photo_paths = [
        [__DIR__ . '/../uploads/profils/' . $photo, '../uploads/profils/' . rawurlencode($photo)],
        [__DIR__ . '/../uploads/photos/' . $photo, '../uploads/photos/' . rawurlencode($photo)],
        [__DIR__ . '/../uploads/' . $photo, '../uploads/' . rawurlencode($photo)],
        [__DIR__ . '/../assets/uploads/' . $photo, '../assets/uploads/' . rawurlencode($photo)],
        [__DIR__ . '/../images/profils/' . $photo, '../images/profils/' . rawurlencode($photo)],
        [__DIR__ . '/../images/' . $photo, '../images/' . rawurlencode($photo)]
    ];

    foreach ($photo_paths as [$file, $url]) {
        if (is_file($file)) {
            $photo_url = $url;
            break;
        }
    }
}

function menuTableExists(PDO $con, string $table): bool
{
    try {
        $stmt = $con->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$notification_items = [];
$notification_count = 0;
$seen_at = $_SESSION['notifications_seen_at'] ?? '1970-01-01 00:00:00';

try {
    $recentUsers = $con->query(
        "SELECT id_user, prenom_user, nom_user, date_inscrip_user
         FROM t_utilisateurs
         ORDER BY id_user DESC LIMIT 3"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recentUsers as $row) {
        $notification_items[] = [
            'type' => 'user',
            'icon' => 'fa-user-plus',
            'title' => 'Nouvel utilisateur',
            'text' => trim(($row['prenom_user'] ?? '') . ' ' . ($row['nom_user'] ?? '')),
            'date' => $row['date_inscrip_user'] ?? '',
            'link' => 'utilisateurs.php'
        ];
    }
} catch (Throwable $e) {
}

try {
    $recentCerts = $con->query(
        "SELECT c.numero_certificat, c.date_obtention, f.titre_form
         FROM t_certificats c
         LEFT JOIN t_inscriptions i ON i.id_inscrip = c.id_inscrip
         LEFT JOIN t_formations f ON f.id_form = i.id_form
         ORDER BY c.id_certificat DESC LIMIT 2"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recentCerts as $row) {
        $notification_items[] = [
            'type' => 'certificate',
            'icon' => 'fa-award',
            'title' => 'Certificat délivré',
            'text' => $row['titre_form'] ?? 'Nouvelle certification',
            'date' => $row['date_obtention'] ?? '',
            'link' => 'certificats.php'
        ];
    }
} catch (Throwable $e) {
}

try {
    $recentPayments = $con->query(
        "SELECT montant, statut_paiement, date_paiement
         FROM t_paiements
         ORDER BY id_paiement DESC LIMIT 2"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recentPayments as $row) {
        $notification_items[] = [
            'type' => 'payment',
            'icon' => 'fa-credit-card',
            'title' => 'Nouveau paiement',
            'text' => number_format((float) ($row['montant'] ?? 0), 0, ',', ' ') . ' FCFA',
            'date' => $row['date_paiement'] ?? '',
            'link' => 'paiements.php'
        ];
    }
} catch (Throwable $e) {
}

if (menuTableExists($con, 't_messages_contact')) {
    try {
        $messages = $con->query(
            "SELECT id_message, nom_message, sujet_message, date_message
             FROM t_messages_contact
             WHERE LOWER(statut_message) = 'nouveau'
             ORDER BY id_message DESC LIMIT 3"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($messages as $row) {
            $notification_items[] = [
                'type' => 'message',
                'icon' => 'fa-envelope',
                'title' => 'Nouveau message',
                'text' => trim(($row['nom_message'] ?? '') . ' — ' . ($row['sujet_message'] ?? 'Contact')),
                'date' => $row['date_message'] ?? '',
                'link' => 'messages.php'
            ];
        }
    } catch (Throwable $e) {
    }
}

foreach ($notification_items as $item) {
    if ($item['date'] !== '' && strtotime($item['date']) > strtotime($seen_at)) {
        $notification_count++;
    }
}

usort($notification_items, function ($a, $b) {
    return strtotime($b['date'] ?: '1970-01-01') <=> strtotime($a['date'] ?: '1970-01-01');
});
$notification_items = array_slice($notification_items, 0, 5);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background: #EAF3F7;
        color: #18213F;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        height: 100vh;
        padding: 8px 12px 7px;
        background: linear-gradient(180deg, #18213F 0%, #24365F 55%, #18213F 100%);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 1000;
    }

    .logo {
        height: 46px;
        min-height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding-bottom: 6px;
        margin-bottom: 3px;
        border-bottom: 1px solid rgba(32, 161, 184, .18);
    }

    .logo img {
        width: 148px;
        max-height: 38px;
        object-fit: contain;
    }

    .sidebar-menu {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        padding: 1px 1px 0;
    }

    .menu-title {
        display: block;
        padding: 5px 10px 3px;
        color: #20A1B8;
        font-size: 8.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.15px;
        line-height: 1;
    }

    .menu-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: 11px;
        width: 100%;
        height: 32px;
        min-height: 32px;
        padding: 0 11px;
        margin: 1px 0;
        border-radius: 9px;
        color: rgba(255, 255, 255, .84);
        font-size: 11px;
        font-weight: 500;
        text-decoration: none;
        transition: .2s ease;
    }

    .menu-link i {
        width: 19px;
        min-width: 19px;
        text-align: center;
        font-size: 13.5px;
    }

    .menu-link:hover {
        background: rgba(32, 161, 184, .10);
        color: #fff;
    }

    .menu-link.active {
        background: linear-gradient(135deg, #20A1B8, #3F68AE);
        color: #fff;
        box-shadow: 0 5px 14px rgba(32, 161, 184, .20);
    }

    .menu-link.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 5px;
        width: 3px;
        height: 22px;
        border-radius: 0 4px 4px 0;
        background: #20A1B8;
    }

    .menu-separator {
        height: 1px;
        margin: 3px 8px;
        background: rgba(255, 255, 255, .08);
    }

    .sidebar-bottom {
        flex-shrink: 0;
        padding-top: 5px;
        margin-top: 2px;
        border-top: 1px solid rgba(32, 161, 184, .18);
    }

    .sidebar-bottom .menu-link {
        height: 33px;
        min-height: 33px;
        margin: 0;
        background: rgba(32, 161, 184, .07);
        border: 1px solid rgba(32, 161, 184, .14);
    }

    .sidebar-bottom .menu-link i {
        color: #20A1B8;
    }

    .topbar {
        position: fixed;
        top: 0;
        left: 260px;
        right: 0;
        height: 62px;
        padding: 0 22px;
        background: linear-gradient(90deg, #F1F7F9, #F7FAFC);
        border-bottom: 1px solid #DCE7ED;
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
        padding: 0 11px;
        background: #F8FAFD;
        border: 1px solid #E6EDF2;
        border-radius: 10px;
    }

    .search i {
        color: #8C9AAF;
        font-size: 14px;
    }

    .search input {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #18213F;
        font: 10.5px 'Poppins', sans-serif;
    }

    .search input::placeholder {
        color: #8D98A8;
    }

    .top-right {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-left: auto;
    }

    .notification-button {
        position: relative;
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border: 1px solid #E4EBF0;
        border-radius: 11px;
        background: #F9FBFC;
        color: #18213F;
        cursor: pointer;
    }

    .notification-button:hover {
        border-color: #BBDCE3;
        color: #20A1B8;
    }

    .notification-button i {
        font-size: 16px;
    }

    .notification-count {
        position: absolute;
        top: 4px;
        right: 4px;
        min-width: 15px;
        height: 15px;
        padding: 0 4px;
        display: grid;
        place-items: center;
        border-radius: 20px;
        background: #20A1B8;
        color: #fff;
        font-size: 7px;
        font-weight: 700;
    }

    .notification-count.is-hidden {
        display: none;
    }

    .notification-dropdown {
        position: fixed;
        top: 56px;
        right: 210px;
        width: 315px;
        display: none;
        padding: 10px;
        background: #fff;
        border: 1px solid #DDE8EE;
        border-radius: 15px;
        box-shadow: 0 18px 45px rgba(24, 33, 63, .15);
        z-index: 1300;
    }

    .notification-dropdown.show {
        display: block;
    }

    .notification-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 4px 5px 9px;
    }

    .notification-head strong {
        font-size: 11px;
    }

    .notification-head a {
        color: #20A1B8;
        font-size: 8px;
        text-decoration: none;
    }

    .notification-item {
        display: flex;
        gap: 9px;
        padding: 9px 7px;
        border-radius: 9px;
        text-decoration: none;
    }

    .notification-item:hover {
        background: #F1F7F9;
    }

    .notification-icon {
        width: 31px;
        height: 31px;
        min-width: 31px;
        display: grid;
        place-items: center;
        border-radius: 9px;
        background: #E6F5F7;
        color: #20A1B8;
    }

    .notification-item:nth-child(2) .notification-icon {
        background: #EDF1FA;
        color: #3F68AE;
    }

    .notification-item:nth-child(3) .notification-icon {
        background: #F3EAF9;
        color: #875FC3;
    }

    .notification-item[data-type="message"] .notification-icon {
        background: #E5F6F7;
        color: #158090;
    }

    .notification-item strong {
        display: block;
        color: #18213F;
        font-size: 8.5px;
    }

    .notification-item span {
        display: block;
        margin-top: 2px;
        color: #7C8999;
        font-size: 7.5px;
    }

    .notification-empty {
        padding: 22px 10px;
        text-align: center;
        color: #8A96A5;
        font-size: 8.5px;
    }

    .profile-trigger {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 195px;
        height: 48px;
        padding: 4px 9px 4px 5px;
        border: 1px solid #DCE6EC;
        border-radius: 17px;
        background: #fff;
        box-shadow: 0 5px 18px rgba(24, 33, 63, .06);
        cursor: pointer;
        text-align: left;
    }

    .profile-avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;
        display: grid;
        place-items: center;
        padding: 2px;
        overflow: hidden;
        border-radius: 50%;
        background: linear-gradient(135deg, #3F68AE, #20A1B8);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
    }

    .profile-avatar img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }

    .profile-info {
        min-width: 0;
        flex: 1;
    }

    .profile-info strong {
        display: block;
        max-width: 135px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #18213F;
        font-size: 11px;
    }

    .profile-info small {
        display: block;
        margin-top: 2px;
        color: #8995A4;
        font-size: 8.5px;
    }

    .profile-chevron {
        color: #7C8999;
        font-size: 9px;
    }

    .profile-dropdown {
        position: fixed;
        top: 57px;
        right: 22px;
        width: 295px;
        display: none;
        padding: 10px;
        background: #fff;
        border: 1px solid #DDE8EE;
        border-radius: 15px;
        box-shadow: 0 18px 45px rgba(24, 33, 63, .16);
        z-index: 1300;
    }

    .profile-dropdown.show {
        display: block;
    }

    .profile-dropdown-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 7px 9px;
    }

    .profile-dropdown-avatar {
        width: 44px;
        height: 44px;
        min-width: 44px;
        display: grid;
        place-items: center;
        overflow: hidden;
        border-radius: 50%;
        background: linear-gradient(135deg, #3F68AE, #20A1B8);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
    }

    .profile-dropdown-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-dropdown-head strong {
        display: block;
        color: #18213F;
        font-size: 10px;
    }

    .profile-dropdown-head span {
        display: block;
        margin-top: 3px;
        color: #8793A3;
        font-size: 8px;
    }

    .profile-account {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 7px;
        margin: 0 4px 7px;
        padding: 8px;
        background: #F0F7F9;
        border: 1px solid #DDEBEF;
        border-radius: 10px;
    }

    .profile-account div {
        min-width: 0;
    }

    .profile-account small {
        display: block;
        color: #8A96A4;
        font-size: 7px;
        text-transform: uppercase;
    }

    .profile-account strong {
        display: block;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #24365F;
        font-size: 8px;
    }

    .profile-menu-item {
        width: 100%;
        height: 37px;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 0 10px;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: #344257;
        font: 9px 'Poppins', sans-serif;
        text-decoration: none;
        cursor: pointer;
        text-align: left;
    }

    .profile-menu-item:hover {
        background: #EFF7F9;
    }

    .profile-menu-item i {
        width: 16px;
        color: #20A1B8;
        text-align: center;
    }

    .profile-menu-item.logout i {
        color: #C34B4B;
    }

    .profile-menu-item.logout:hover {
        background: #FFF1F1;
        color: #A53A3A;
    }

    .profile-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(24, 33, 63, .48);
        backdrop-filter: blur(3px);
        z-index: 2000;
    }

    .profile-modal.show {
        display: flex;
    }

    .profile-box {
        width: min(520px, 100%);
        overflow: hidden;
        background: #F4F9FB;
        border-radius: 18px;
        box-shadow: 0 25px 70px rgba(24, 33, 63, .24);
    }

    .profile-box-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 19px;
        background: linear-gradient(135deg, #18213F, #3F68AE 68%, #20A1B8);
        color: #fff;
    }

    .profile-box-head h3 {
        margin: 0;
        font-size: 15px;
    }

    .profile-box-head p {
        margin: 3px 0 0;
        color: rgba(255, 255, 255, .68);
        font-size: 8.5px;
    }

    .profile-close {
        width: 30px;
        height: 30px;
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 8px;
        background: rgba(255, 255, 255, .10);
        color: #fff;
        cursor: pointer;
    }

    .profile-form {
        padding: 15px 19px 18px;
    }

    .profile-photo-line {
        display: flex;
        align-items: center;
        gap: 11px;
        margin-bottom: 12px;
        padding: 10px;
        background: #E8F3F6;
        border-radius: 11px;
    }

    .profile-preview {
        width: 55px;
        height: 55px;
        min-width: 55px;
        display: grid;
        place-items: center;
        overflow: hidden;
        border-radius: 50%;
        background: linear-gradient(135deg, #3F68AE, #20A1B8);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
    }

    .profile-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-photo-line label {
        display: block;
        color: #44536A;
        font-size: 8.5px;
        font-weight: 700;
    }

    .profile-photo-line input {
        margin-top: 4px;
        max-width: 280px;
        font-size: 8px;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 9px;
    }

    .profile-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .profile-field.full {
        grid-column: 1 / -1;
    }

    .profile-field label {
        color: #53647A;
        font-size: 8px;
        font-weight: 700;
    }

    .profile-field input {
        width: 100%;
        height: 36px;
        padding: 0 9px;
        border: 1px solid #D3E1E7;
        border-radius: 8px;
        background: #fff;
        color: #18213F;
        outline: 0;
        font: 9px 'Poppins', sans-serif;
    }

    .profile-status {
        min-height: 16px;
        margin-top: 7px;
        font-size: 8px;
    }

    .profile-status.success {
        color: #16805F;
    }

    .profile-status.error {
        color: #B43D3D;
    }

    .profile-actions {
        display: flex;
        justify-content: flex-end;
        gap: 7px;
        margin-top: 5px;
    }

    .profile-actions button {
        height: 35px;
        padding: 0 13px;
        border-radius: 8px;
        font: 600 8.5px 'Poppins', sans-serif;
        cursor: pointer;
    }

    .profile-cancel {
        border: 1px solid #D3E0E6;
        background: #fff;
        color: #5C6B7D;
    }

    .profile-save {
        border: 0;
        background: linear-gradient(135deg, #20A1B8, #3F68AE);
        color: #fff;
    }

    .main {
        width: calc(100% - 260px);
        min-height: 100vh;
        margin-left: 260px;
        padding-top: 68px;
    }

    @media (max-width: 800px) {
        .sidebar {
            width: 70px;
            padding: 8px 7px;
        }

        .logo {
            height: 45px;
            min-height: 45px;
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
            height: 32px;
            min-height: 32px;
            padding: 0;
        }

        .topbar {
            left: 70px;
            height: 62px;
            padding: 0 14px;
        }

        .main {
            width: calc(100% - 70px);
            margin-left: 70px;
            padding-top: 62px;
        }

        .search {
            width: 200px;
        }

        .profile-trigger {
            min-width: auto;
            padding-right: 4px;
            border: 0;
            box-shadow: none;
            background: transparent;
        }

        .profile-trigger .profile-info,
        .profile-chevron {
            display: none;
        }

        .profile-dropdown {
            right: 10px;
        }

        .notification-dropdown {
            right: 65px;
        }
    }

    @media (max-width: 600px) {
        .search {
            width: 150px;
        }

        .profile-grid {
            grid-template-columns: 1fr;
        }

        .profile-field.full {
            grid-column: auto;
        }

        .notification-dropdown {
            right: 10px;
            width: min(310px, calc(100vw - 20px));
        }
    }
</style>

<aside class="sidebar">
    <div class="logo">
        <img src="images/logo1.png" alt="TOUN">
    </div>

    <nav class="sidebar-menu">
        <span class="menu-title">Principal</span>
        <a href="dashbord.php" class="menu-link <?= $active_page === 'dashbord.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Dashboard</span>
        </a>
        <a href="utilisateurs.php" class="menu-link <?= $active_page === 'utilisateurs.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <span>Utilisateurs</span>
        </a>
        <a href="formations.php" class="menu-link <?= $active_page === 'formations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Formations</span>
        </a>
        <a href="entreprises.php" class="menu-link <?= $active_page === 'entreprises.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-building"></i>
            <span>Entreprises</span>
        </a>
        <a href="formateurs.php" class="menu-link <?= $active_page === 'formateurs.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-tie"></i>
            <span>Formateurs</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Gestion pédagogique</span>
        <a href="modules.php" class="menu-link <?= $active_page === 'modules.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Modules</span>
        </a>
        <a href="quiz.php" class="menu-link <?= $active_page === 'quiz.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Quiz & évaluations</span>
        </a>
        <a href="progression.php" class="menu-link <?= $active_page === 'progression.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Progression</span>
        </a>
        <a href="certificats.php" class="menu-link <?= $active_page === 'certificats.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-award"></i>
            <span>Certificats</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Gestion financière</span>
        <a href="paiements.php" class="menu-link <?= $active_page === 'paiements.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-credit-card"></i>
            <span>Paiements</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Administration</span>
        <a href="messages.php" class="menu-link <?= $active_page === 'messages.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-envelope"></i>
            <span>Messages</span>
        </a>
        <a href="messagerie.php" class="menu-link <?= $active_page === 'messagerie.php' ? 'active' : '' ?>">
            <i class="fa-regular fa-comments"></i>
            <span>Messagerie apprenants</span>
        </a>
        <a href="statistiques.php" class="menu-link <?= $active_page === 'statistiques.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Statistiques</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="deconnexion.php" class="menu-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</aside>

<header class="topbar">
    <div class="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Rechercher..." autocomplete="off">
    </div>

    <div class="top-right">
        <button type="button" class="notification-button" id="notificationButton" aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
            <span class="notification-count <?= $notification_count === 0 ? 'is-hidden' : '' ?>" id="notificationCount">
                <?= $notification_count > 9 ? '9+' : $notification_count ?>
            </span>
        </button>

        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-head">
                <strong>Notifications</strong>
                <a href="notifications.php">Voir toutes</a>
            </div>

            <?php if (!$notification_items): ?>
                <div class="notification-empty">
                    <i class="fa-regular fa-bell-slash"></i>
                    <div>Aucune notification pour le moment.</div>
                </div>
            <?php else: ?>
                <?php foreach ($notification_items as $item): ?>
                    <a class="notification-item" data-type="<?= htmlspecialchars($item['type'] ?? '', ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="notification-icon">
                            <i class="fa-solid <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" class="profile-trigger" id="profileTrigger" aria-expanded="false">
            <span class="profile-avatar">
                <?php if ($photo_url): ?>
                    <img src="<?= htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de profil">
                <?php else: ?>
                    <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
            <span class="profile-info">
                <strong><?= htmlspecialchars($nom_complet, ENT_QUOTES, 'UTF-8') ?></strong>
                <small>Administrateur</small>
            </span>
            <i class="fa-solid fa-chevron-down profile-chevron"></i>
        </button>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-dropdown-head">
                <span class="profile-dropdown-avatar">
                    <?php if ($photo_url): ?>
                        <img src="<?= htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de profil">
                    <?php else: ?>
                        <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <div>
                    <strong><?= htmlspecialchars($nom_complet, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="profile-account">
                <div>
                    <small>Rôle</small>
                    <strong>Administrateur</strong>
                </div>
                <div>
                    <small>Téléphone</small>
                    <strong><?= htmlspecialchars($tel ?: 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>

            <button type="button" class="profile-menu-item" id="openProfile">
                <i class="fa-solid fa-user-pen"></i>
                <span>Modifier mes informations</span>
            </button>
            <a href="deconnexion.php" class="profile-menu-item logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </div>
</header>

<div class="profile-modal" id="profileModal" aria-hidden="true">
    <div class="profile-box" role="dialog" aria-modal="true">
        <div class="profile-box-head">
            <div>
                <h3>Mon profil</h3>
                <p>Informations de la personne actuellement connectée.</p>
            </div>
            <button type="button" class="profile-close" id="closeProfile">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form class="profile-form" id="profileForm" action="profil_update.php" method="post" enctype="multipart/form-data">
            <div class="profile-photo-line">
                <div class="profile-preview" id="profilePreview">
                    <?php if ($photo_url): ?>
                        <img src="<?= htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de profil">
                    <?php else: ?>
                        <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="photo_user">Photo de profil</label>
                    <input type="file" name="photo_user" id="photo_user" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>

            <div class="profile-grid">
                <div class="profile-field">
                    <label for="prenom_user">Prénom</label>
                    <input id="prenom_user" name="prenom_user" required value="<?= htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="profile-field">
                    <label for="nom_user">Nom</label>
                    <input id="nom_user" name="nom_user" required value="<?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="profile-field full">
                    <label for="email_user">Adresse e-mail</label>
                    <input id="email_user" type="email" name="email_user" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="profile-field full">
                    <label for="tel_user">Téléphone</label>
                    <input id="tel_user" name="tel_user" value="<?= htmlspecialchars($tel, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="profile-field full">
                    <label for="motdepasse_user">Nouveau mot de passe</label>
                    <input id="motdepasse_user" type="password" name="motdepasse_user" autocomplete="new-password" placeholder="Laisser vide pour conserver l’actuel">
                </div>
            </div>

            <div class="profile-status" id="profileStatus"></div>

            <div class="profile-actions">
                <button type="button" class="profile-cancel" id="cancelProfile">Annuler</button>
                <button type="submit" class="profile-save">
                    <i class="fa-solid fa-check"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationCount = document.getElementById('notificationCount');
    const profileTrigger = document.getElementById('profileTrigger');
    const profileDropdown = document.getElementById('profileDropdown');
    const profileModal = document.getElementById('profileModal');
    const openProfile = document.getElementById('openProfile');
    const closeProfile = document.getElementById('closeProfile');
    const cancelProfile = document.getElementById('cancelProfile');
    const profileForm = document.getElementById('profileForm');
    const profileStatus = document.getElementById('profileStatus');
    const photoInput = document.getElementById('photo_user');
    const profilePreview = document.getElementById('profilePreview');

    function closeMenus() {
        notificationDropdown.classList.remove('show');
        profileDropdown.classList.remove('show');
        profileTrigger.setAttribute('aria-expanded', 'false');
    }

    notificationButton.addEventListener('click', function (event) {
        event.stopPropagation();
        profileDropdown.classList.remove('show');
        const opened = notificationDropdown.classList.toggle('show');
        if (opened) {
            fetch('notifications_lues.php', { method: 'POST' }).then(function () {
                notificationCount.classList.add('is-hidden');
            }).catch(function () {});
        }
    });

    profileTrigger.addEventListener('click', function (event) {
        event.stopPropagation();
        notificationDropdown.classList.remove('show');
        const opened = profileDropdown.classList.toggle('show');
        profileTrigger.setAttribute('aria-expanded', opened ? 'true' : 'false');
    });

    openProfile.addEventListener('click', function () {
        closeMenus();
        profileModal.classList.add('show');
        profileModal.setAttribute('aria-hidden', 'false');
        profileStatus.textContent = '';
        profileStatus.className = 'profile-status';
    });

    function closeProfileModal() {
        profileModal.classList.remove('show');
        profileModal.setAttribute('aria-hidden', 'true');
    }

    closeProfile.addEventListener('click', closeProfileModal);
    cancelProfile.addEventListener('click', closeProfileModal);

    profileModal.addEventListener('click', function (event) {
        if (event.target === profileModal) {
            closeProfileModal();
        }
    });

    document.addEventListener('click', function () {
        closeMenus();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenus();
            closeProfileModal();
        }
    });

    photoInput.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) {
            return;
        }

        const url = URL.createObjectURL(file);
        profilePreview.innerHTML = '<img src="' + url + '" alt="Photo de profil">';
    });

    profileForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        profileStatus.textContent = 'Enregistrement...';
        profileStatus.className = 'profile-status';

        try {
            const response = await fetch(profileForm.action, {
                method: 'POST',
                body: new FormData(profileForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Impossible d’enregistrer les informations.');
            }

            profileStatus.textContent = data.message;
            profileStatus.className = 'profile-status success';

            if (data.nom_complet) {
                document.querySelectorAll('.profile-info strong, .profile-dropdown-head strong').forEach(function (element) {
                    element.textContent = data.nom_complet;
                });
            }

            if (data.email) {
                const emailElement = document.querySelector('.profile-dropdown-head span');
                if (emailElement) {
                    emailElement.textContent = data.email;
                }
            }

            if (data.photo_url) {
                document.querySelectorAll('.profile-avatar img, .profile-dropdown-avatar img').forEach(function (image) {
                    image.src = data.photo_url + '?v=' + Date.now();
                });
            }

            setTimeout(closeProfileModal, 700);
        } catch (error) {
            profileStatus.textContent = error.message;
            profileStatus.className = 'profile-status error';
        }
    });
});
</script>

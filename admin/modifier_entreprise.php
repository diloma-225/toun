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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: entreprises.php');
    exit();
}

$stmt = $con->prepare("
    SELECT 
        e.*,
        u.nom_user,
        u.prenom_user,
        u.email_user
    FROM t_entreprises e
    LEFT JOIN t_utilisateurs u ON e.id_user = u.id_user
    WHERE e.id_entreprise = ?
    LIMIT 1
");
$stmt->execute([$id]);

$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    header('Location: entreprises.php');
    exit();
}

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_entreprise'] ?? '');
    $email = trim($_POST['email_entreprise'] ?? '');
    $tel = trim($_POST['tel_entreprise'] ?? '');
    $secteur = trim($_POST['sect_activite'] ?? '');
    $adresse = trim($_POST['ad_entreprise'] ?? '');
    $statut = trim($_POST['statut_entreprise'] ?? '');
    $date_creation = trim($_POST['date_creation_entreprise'] ?? '');

    if ($nom === '' || $email === '' || $tel === '' || $secteur === '' || $adresse === '') {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Veuillez saisir une adresse e-mail valide.';
    } elseif (!in_array($statut, ['actif', 'inactif'], true)) {
        $erreur = 'Le statut sélectionné est invalide.';
    } else {
        $check = $con->prepare("
            SELECT id_entreprise
            FROM t_entreprises
            WHERE nom_entreprise = ?
            AND id_entreprise != ?
            LIMIT 1
        ");

        $check->execute([$nom, $id]);

        if ($check->fetch()) {
            $erreur = 'Une autre entreprise porte déjà ce nom.';
        } else {
            try {
                $update = $con->prepare("
                    UPDATE t_entreprises
                    SET
                        nom_entreprise = ?,
                        email_entreprise = ?,
                        tel_entreprise = ?,
                        sect_activite = ?,
                        ad_entreprise = ?,
                        statut_entreprise = ?,
                        date_creation_entreprise = ?
                    WHERE id_entreprise = ?
                ");

                $update->execute([
                    $nom,
                    $email,
                    $tel,
                    $secteur,
                    $adresse,
                    $statut,
                    $date_creation !== '' ? $date_creation : null,
                    $id
                ]);

                $_SESSION['entreprise_success'] = "L'entreprise a été modifiée avec succès.";

                header('Location: entreprises.php');
                exit();
            } catch (PDOException $ex) {
                $erreur = 'Une erreur est survenue lors de la modification.';
            }
        }
    }

    $entreprise['nom_entreprise'] = $nom;
    $entreprise['email_entreprise'] = $email;
    $entreprise['tel_entreprise'] = $tel;
    $entreprise['sect_activite'] = $secteur;
    $entreprise['ad_entreprise'] = $adresse;
    $entreprise['statut_entreprise'] = $statut;
    $entreprise['date_creation_entreprise'] = $date_creation;
}

$nomAdmin = $_SESSION['nom_user'] ?? '';
$prenomAdmin = $_SESSION['prenom_user'] ?? '';
$photoAdmin = $_SESSION['photo_user'] ?? '';

$initiales = mb_strtoupper(
    mb_substr(trim($prenomAdmin), 0, 1, 'UTF-8') . mb_substr(trim($nomAdmin), 0, 1, 'UTF-8'),
    'UTF-8'
);

if ($initiales === '') {
    $initiales = 'AD';
}

$photoUrl = '';

if (!empty($photoAdmin)) {
    $photoAdmin = str_replace('\\', '/', trim($photoAdmin));
    $photoAdmin = ltrim($photoAdmin, '/');
    $nomPhoto = basename($photoAdmin);

    $candidats = [
        [__DIR__ . '/' . $photoAdmin, $photoAdmin],
        [__DIR__ . '/../uploads/profils/' . $nomPhoto, '../uploads/profils/' . rawurlencode($nomPhoto)],
        [__DIR__ . '/../uploads/' . $nomPhoto, '../uploads/' . rawurlencode($nomPhoto)],
        [__DIR__ . '/../assets/uploads/' . $nomPhoto, '../assets/uploads/' . rawurlencode($nomPhoto)],
        [__DIR__ . '/../images/profils/' . $nomPhoto, '../images/profils/' . rawurlencode($nomPhoto)],
        [__DIR__ . '/../images/' . $nomPhoto, '../images/' . rawurlencode($nomPhoto)]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat[0])) {
            $photoUrl = $candidat[1];
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

        <title>Modifier l'entreprise — TOUN</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        >

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

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
                font-size: 16px;
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
                font-size: 17px;
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

            .main {
                width: calc(100% - 260px);
                margin-left: 260px;
                min-height: 100vh;
                padding-top: 62px;
            }

            .content {
                max-width: 1180px;
                margin: 0 auto;
                padding: 20px 28px 35px;
            }

            .page-head {
                min-height: 76px;
                margin-bottom: 15px;
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

            .page-title {
                display: flex;
                align-items: center;
                gap: 13px;
            }

            .title-icon {
                width: 42px;
                height: 42px;
                min-width: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.13);
                border: 1px solid rgba(255, 255, 255, 0.18);
            }

            .title-icon i {
                font-size: 18px;
            }

            .eyebrow {
                margin-bottom: 4px;
                color: rgba(255, 255, 255, 0.68);
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 1.5px;
                text-transform: uppercase;
            }

            .page-head h1 {
                font-size: 20px;
                line-height: 1.2;
            }

            .page-head p {
                margin-top: 3px;
                color: rgba(255, 255, 255, 0.8);
                font-size: 11px;
            }

            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                height: 34px;
                padding: 0 12px;
                border: 1px solid rgba(255, 255, 255, 0.22);
                border-radius: 9px;
                background: rgba(255, 255, 255, 0.1);
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                transition: 0.2s ease;
            }

            .back-link:hover {
                background: #fff;
                color: #24639a;
                transform: translateX(-2px);
            }

            .alert {
                display: flex;
                align-items: center;
                gap: 9px;
                margin-bottom: 13px;
                padding: 11px 13px;
                border-radius: 10px;
                font-size: 11px;
            }

            .alert-error {
                border: 1px solid #f0c7cd;
                background: #fff1f3;
                color: #a33e4b;
            }

            .alert i {
                font-size: 13px;
            }

            .form-card {
                overflow: hidden;
                border: 1px solid #dfe8ed;
                border-radius: 15px;
                background: #fff;
                box-shadow: 0 8px 25px rgba(24, 33, 63, 0.06);
            }

            .form-card-head {
                min-height: 58px;
                padding: 0 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #edf1f4;
            }

            .form-card-title {
                display: flex;
                align-items: center;
                gap: 9px;
                color: #18213f;
                font-size: 12px;
                font-weight: 800;
            }

            .form-card-title i {
                color: #20a1b8;
                font-size: 14px;
            }

            .form-card-head span {
                color: #93a0ae;
                font-size: 10.5px;
            }

            .form-body {
                padding: 20px;
            }

            .form-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .form-group {
                display: flex;
                flex-direction: column;
            }

            .form-group.full {
                grid-column: 1 / -1;
            }

            .form-group label {
                margin-bottom: 6px;
                color: #53687c;
                font-size: 11px;
                font-weight: 700;
            }

            .required {
                color: #20a1b8;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                width: 100%;
                border: 1px solid #dfe7ec;
                border-radius: 9px;
                outline: none;
                background: #f9fbfc;
                color: #344d66;
                font-family: inherit;
                font-size: 11.5px;
                transition: 0.2s ease;
            }

            .form-group input,
            .form-group select {
                height: 40px;
                padding: 0 11px;
            }

            .form-group textarea {
                min-height: 80px;
                padding: 10px 11px;
                resize: vertical;
            }

            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                border-color: #20a1b8;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .form-group input::placeholder,
            .form-group textarea::placeholder {
                color: #a5b0bc;
            }

            .form-help {
                margin-top: 5px;
                color: #9aa7b3;
                font-size: 10px;
            }

            .responsable-box {
                margin-top: 18px;
                padding: 15px;
                border: 1px solid #dcebf0;
                border-radius: 12px;
                background: #f7fbfc;
            }

            .responsable-title {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 12px;
                color: #27445d;
                font-size: 11.5px;
                font-weight: 800;
            }

            .responsable-title i {
                color: #20a1b8;
            }

            .responsable-info {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }

            .info-item span {
                display: block;
                margin-bottom: 3px;
                color: #91a0ad;
                font-size: 10px;
            }

            .info-item strong {
                display: block;
                color: #425a70;
                font-size: 11px;
            }

            .form-actions {
                margin-top: 20px;
                padding-top: 15px;
                display: flex;
                justify-content: flex-end;
                gap: 9px;
                border-top: 1px solid #edf1f4;
            }

            .btn {
                height: 37px;
                padding: 0 15px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                border-radius: 9px;
                font-family: inherit;
                font-size: 11px;
                font-weight: 800;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .btn-cancel {
                border: 1px solid #dce5ea;
                background: #fff;
                color: #66798b;
            }

            .btn-cancel:hover {
                background: #f5f8fa;
            }

            .btn-save {
                border: 0;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                box-shadow: 0 6px 15px rgba(32, 161, 184, 0.18);
            }

            .btn-save:hover {
                transform: translateY(-1px);
                box-shadow: 0 8px 18px rgba(32, 161, 184, 0.25);
            }

            @media (max-width: 900px) {
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

                .form-grid {
                    grid-template-columns: 1fr;
                }

                .form-group.full {
                    grid-column: auto;
                }

                .responsable-info {
                    grid-template-columns: 1fr 1fr;
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
                    justify-content: center;
                    padding: 0;
                    height: 34px;
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

                .back-link {
                    align-self: flex-start;
                }

                .responsable-info {
                    grid-template-columns: 1fr;
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

                .notification {
                    display: none;
                }

                .page-head {
                    padding: 15px;
                }

                .page-head h1 {
                    font-size: 17px;
                }

                .form-body {
                    padding: 15px;
                }

                .form-actions {
                    flex-direction: column-reverse;
                }

                .btn {
                    width: 100%;
                }
            }
        </style>

    </head>

    <body>

        <aside class="sidebar">

            <div class="logo">

                <a href="dashboard.php">

                    <img
                        src="../assets/img/logo-toun.png"
                        alt="TOUN"
                    >

                </a>

            </div>

            <nav class="sidebar-menu">

                <span class="menu-title">
                    Principal
                </span>

                <a href="dashboard.php" class="nav-link">

                    <i class="fa-solid fa-table-cells-large"></i>

                    <span>
                        Dashboard
                    </span>

                </a>

                <a href="utilisateur.php" class="nav-link">

                    <i class="fa-solid fa-users"></i>

                    <span>
                        Utilisateurs
                    </span>

                </a>

                <a href="formations_toun_final.php" class="nav-link">

                    <i class="fa-solid fa-book-open"></i>

                    <span>
                        Formations
                    </span>

                </a>

                <a href="entreprises.php" class="nav-link active">

                    <i class="fa-solid fa-building"></i>

                    <span>
                        Entreprises
                    </span>

                </a>

                <a href="formateurs.php" class="nav-link">

                    <i class="fa-solid fa-user-tie"></i>

                    <span>
                        Formateurs
                    </span>

                </a>

                <div class="separator"></div>

                <span class="menu-title">
                    Gestion pédagogique
                </span>

                <a href="modules.php" class="nav-link">

                    <i class="fa-solid fa-layer-group"></i>

                    <span>
                        Modules
                    </span>

                </a>

                <a href="quiz.php" class="nav-link">

                    <i class="fa-solid fa-clipboard-check"></i>

                    <span>
                        Quiz &amp; évaluations
                    </span>

                </a>

                <a href="progression.php" class="nav-link">

                    <i class="fa-solid fa-chart-line"></i>

                    <span>
                        Progression
                    </span>

                </a>

                <a href="certificats.php" class="nav-link">

                    <i class="fa-solid fa-award"></i>

                    <span>
                        Certificats
                    </span>

                </a>

                <div class="separator"></div>

                <span class="menu-title">
                    Gestion financière
                </span>

                <a href="paiements.php" class="nav-link">

                    <i class="fa-solid fa-credit-card"></i>

                    <span>
                        Paiements
                    </span>

                </a>

                <div class="separator"></div>

                <span class="menu-title">
                    Administration
                </span>

                <a href="statistiques.php" class="nav-link">

                    <i class="fa-solid fa-chart-pie"></i>

                    <span>
                        Statistiques
                    </span>

                </a>

            </nav>

            <div class="sidebar-bottom">

                <a href="deconnexion.php" class="nav-link">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>
                        Déconnexion
                    </span>

                </a>

            </div>

        </aside>

        <header class="topbar">

            <div class="search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    placeholder="Rechercher..."
                >

            </div>

            <div class="top-right">

                <a href="notifications.php" class="notification">

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-badge"></span>

                </a>

                <div class="profile">

                    <div class="avatar">

                        <?php if ($photoUrl !== ''): ?>

                            <img
                                src="<?= e($photoUrl) ?>"
                                alt="Profil"
                            >

                        <?php else: ?>

                            <?= e($initiales) ?>

                        <?php endif; ?>

                    </div>

                    <div>

                        <strong>
                            <?= e(trim($prenomAdmin . ' ' . $nomAdmin) ?: 'Administrateur') ?>
                        </strong>

                        <span>
                            Administrateur
                        </span>

                    </div>

                </div>

            </div>

        </header>

        <main class="main">

            <div class="content">

                <div class="page-head">

                    <div class="page-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-building"></i>

                        </div>

                        <div>

                            <div class="eyebrow">
                                Gestion des entreprises
                            </div>

                            <h1>
                                Modifier l'entreprise
                            </h1>

                            <p>
                                Mettez à jour les informations de l'entreprise.
                            </p>

                        </div>

                    </div>

                    <a href="entreprises.php" class="back-link">

                        <i class="fa-solid fa-arrow-left"></i>

                        Retour aux entreprises

                    </a>

                </div>

                <?php if ($erreur !== ''): ?>

                    <div class="alert alert-error">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= e($erreur) ?>

                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    class="form-card"
                >

                    <div class="form-card-head">

                        <div class="form-card-title">

                            <i class="fa-solid fa-pen-to-square"></i>

                            Informations de l'entreprise

                        </div>

                        <span>
                            Champs obligatoires *
                        </span>

                    </div>

                    <div class="form-body">

                        <div class="form-grid">

                            <div class="form-group">

                                <label>
                                    Nom de l'entreprise <span class="required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="nom_entreprise"
                                    value="<?= e($entreprise['nom_entreprise']) ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label>
                                    Adresse e-mail <span class="required">*</span>
                                </label>

                                <input
                                    type="email"
                                    name="email_entreprise"
                                    value="<?= e($entreprise['email_entreprise']) ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label>
                                    Téléphone <span class="required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="tel_entreprise"
                                    value="<?= e($entreprise['tel_entreprise']) ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label>
                                    Secteur d'activité <span class="required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="sect_activite"
                                    value="<?= e($entreprise['sect_activite']) ?>"
                                    required
                                >

                            </div>

                            <div class="form-group full">

                                <label>
                                    Adresse <span class="required">*</span>
                                </label>

                                <textarea
                                    name="ad_entreprise"
                                    required
                                ><?= e($entreprise['ad_entreprise']) ?></textarea>

                            </div>

                            <div class="form-group">

                                <label>
                                    Statut <span class="required">*</span>
                                </label>

                                <select
                                    name="statut_entreprise"
                                    required
                                >

                                    <option
                                        value="actif"
                                        <?= ($entreprise['statut_entreprise'] ?? '') === 'actif' ? 'selected' : '' ?>
                                    >
                                        Actif
                                    </option>

                                    <option
                                        value="inactif"
                                        <?= ($entreprise['statut_entreprise'] ?? '') === 'inactif' ? 'selected' : '' ?>
                                    >
                                        Inactif
                                    </option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>
                                    Date de création
                                </label>

                                <input
                                    type="date"
                                    name="date_creation_entreprise"
                                    value="<?= e($entreprise['date_creation_entreprise'] ?? '') ?>"
                                >

                            </div>

                        </div>

                        <div class="responsable-box">

                            <div class="responsable-title">

                                <i class="fa-solid fa-user-tie"></i>

                                Responsable de l'entreprise

                            </div>

                            <div class="responsable-info">

                                <div class="info-item">

                                    <span>
                                        Nom complet
                                    </span>

                                    <strong>

                                        <?= e(trim(($entreprise['prenom_user'] ?? '') . ' ' . ($entreprise['nom_user'] ?? '')) ?: 'Non renseigné') ?>

                                    </strong>

                                </div>

                                <div class="info-item">

                                    <span>
                                        Adresse e-mail
                                    </span>

                                    <strong>
                                        <?= e($entreprise['email_user'] ?? 'Non renseigné') ?>
                                    </strong>

                                </div>

                                <div class="info-item">

                                    <span>
                                        Accès
                                    </span>

                                    <strong>
                                        Compte responsable entreprise
                                    </strong>

                                </div>

                            </div>

                        </div>

                        <div class="form-actions">

                            <a
                                href="entreprises.php"
                                class="btn btn-cancel"
                            >

                                <i class="fa-solid fa-xmark"></i>

                                Annuler

                            </a>

                            <button
                                type="submit"
                                class="btn btn-save"
                            >

                                <i class="fa-solid fa-check"></i>

                                Enregistrer les modifications

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </main>

    </body>
</html>
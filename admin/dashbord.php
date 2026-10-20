<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] !== 'admin') {
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

    $candidats = [];

    if (preg_match('#^(https?:)?//#i', $photo)) {
        $photo_url = $photo;
    } else {
        $nom_photo = basename($photo);

        $candidats[] = [
            'fichier' => __DIR__ . '/' . $photo,
            'url' => $photo
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../uploads/profils/' . $nom_photo,
            'url' => '../uploads/profils/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../uploads/photos/' . $nom_photo,
            'url' => '../uploads/photos/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../uploads/' . $nom_photo,
            'url' => '../uploads/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../uploads/photos/' . $nom_photo,
            'url' => '../uploads/photos/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../assets/uploads/' . $nom_photo,
            'url' => '../assets/uploads/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../images/profils/' . $nom_photo,
            'url' => '../images/profils/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../images/' . $nom_photo,
            'url' => '../images/' . rawurlencode($nom_photo)
        ];

        foreach ($candidats as $candidat) {
            if (is_file($candidat['fichier'])) {
                $photo_url = $candidat['url'];
                break;
            }
        }
    }
}

function getUserPhotoUrl($photo): string
{
    if (empty($photo)) {
        return '';
    }

    $photo = str_replace('\\', '/', trim((string) $photo));
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
        [__DIR__ . '/../uploads/photos/' . $nom_photo, '../uploads/photos/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../assets/uploads/' . $nom_photo, '../assets/uploads/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../assets/images/' . $nom_photo, '../assets/images/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../images/profils/' . $nom_photo, '../images/profils/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../images/' . $nom_photo, '../images/' . rawurlencode($nom_photo)]
    ];

    foreach ($candidats as [$fichier, $url]) {
        if (is_file($fichier)) {
            return $url;
        }
    }

    return '';
}

$stmt = $con->query('SELECT COUNT(*) FROM t_utilisateurs');
$total_utilisateurs = $stmt->fetchColumn();

$stmt = $con->query("
    SELECT COUNT(*)
    FROM t_utilisateurs
    WHERE role_user = 'apprenant'
");
$total_apprenants = $stmt->fetchColumn();

$stmt = $con->query('SELECT COUNT(*) FROM t_formations');
$total_formations = $stmt->fetchColumn();

$stmt = $con->query('SELECT COUNT(*) FROM t_entreprises');
$total_entreprises = $stmt->fetchColumn();

$stmt = $con->query("SELECT COUNT(*) FROM t_formateurs WHERE statut_formateur = 'actif'");
$total_formateurs = $stmt->fetchColumn();

$stmt = $con->query("SELECT COALESCE(SUM(montant_total), 0) FROM t_paiements WHERE statut_paiement = 'paye'");
$total_recettes = (float) $stmt->fetchColumn();

$stmt = $con->query("
    SELECT 
        nom_user,
        prenom_user,
        email_user,
        role_user,
        photo_user,
        date_inscrip_user
    FROM t_utilisateurs
    ORDER BY date_inscrip_user DESC
    LIMIT 5
");

$derniers_utilisateurs = $stmt->fetchAll();
$resolve_user_photo = function ($photo) {
    if (empty($photo)) {
        return '';
    }

    $photo = str_replace('\\', '/', trim((string) $photo));
    $photo = ltrim($photo, '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $nom_photo = basename($photo);

    $candidats = [
        ['fichier' => __DIR__ . '/' . $photo, 'url' => $photo],
        [
            'fichier' => __DIR__ . '/../uploads/profils/' . $nom_photo,
            'url' => '../uploads/profils/' . rawurlencode($nom_photo)
        ],
        [
            'fichier' => __DIR__ . '/../uploads/photos/' . $nom_photo,
            'url' => '../uploads/photos/' . rawurlencode($nom_photo)
        ],
        ['fichier' => __DIR__ . '/../uploads/' . $nom_photo, 'url' => '../uploads/' . rawurlencode($nom_photo)],
        [
            'fichier' => __DIR__ . '/../assets/uploads/' . $nom_photo,
            'url' => '../assets/uploads/' . rawurlencode($nom_photo)
        ],
        [
            'fichier' => __DIR__ . '/../images/profils/' . $nom_photo,
            'url' => '../images/profils/' . rawurlencode($nom_photo)
        ],
        ['fichier' => __DIR__ . '/../images/' . $nom_photo, 'url' => '../images/' . rawurlencode($nom_photo)]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat['fichier'])) {
            return $candidat['url'];
        }
    }

    return '';
};

$mois = [];
$nombre_inscriptions = [];

for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-01', strtotime("-$i months"));
    $nom_mois = date('M', strtotime($date));

    $mois[] = $nom_mois;

    $stmt = $con->prepare("
        SELECT COUNT(*)
        FROM t_utilisateurs
        WHERE date_inscrip_user >= :debut
        AND date_inscrip_user < DATE_ADD(:debut, INTERVAL 1 MONTH)
    ");

    $stmt->execute([
        'debut' => $date
    ]);

    $nombre_inscriptions[] = (int) $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard - TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <style>
            .dashboard-main {
                width: calc(100% - 260px);
                margin-left: 260px;
                min-height: 100vh;
                padding-top: 62px;
            }

            .dashboard-content {
                padding: 18px 24px 28px;
            }

            .database-status {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 10px;
                padding: 6px 10px;
                border-radius: 18px;
                background: #eaf9f2;
                color: #23915d;
                font-size: 11px;
                font-weight: 600;
            }

            .database-status i {
                font-size: 9.5px;
            }

            .welcome {
                margin-bottom: 13px;
            }

            .welcome h1 {
                font-size: 23px;
                font-weight: 600;
                color: #18213f;
            }

            .welcome p {
                margin-top: 3px;
                color: #8b94a5;
                font-size: 11.5px;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(6, minmax(0, 1fr));
                gap: 14px;
                margin-bottom: 17px;
            }

            .stat-card {
                position: relative;
                height: 128px;
                min-height: 128px;
                padding: 14px 16px;
                overflow: hidden;
                border-radius: 16px;
                color: #fff;
            }

            .stat-card.dark {
                background: #18213f;
            }

            .stat-card.turquoise {
                background: #20a1b8;
            }

            .stat-card.blue {
                background: #416bb0;
            }

            .stat-card.light {
                background: linear-gradient(135deg, #2d80a9, #258cb4);
            }

            .stat-card.formateur {
                background: linear-gradient(135deg, #7958bc, #9770d1);
            }

            .stat-card.recettes {
                background: #1f8a5b;
            }

            .stat-top {
                position: relative;
                z-index: 2;
            }

            .stat-icon {
                width: 39px;
                height: 39px;
                display: grid;
                place-items: center;
                border-radius: 11px;
                background: rgba(255, 255, 255, 0.15);
                font-size: 16px;
            }

            .stat-label {
                position: relative;
                z-index: 2;
                margin-top: 10px;
                font-size: 12px;
                line-height: 1.2;
            }

            .stat-number {
                position: relative;
                z-index: 2;
                margin-top: 2px;
                font-size: 25px;
                line-height: 1.15;
                font-weight: 600;
            }

            .stat-decoration {
                position: absolute;
                right: -12px;
                bottom: -22px;
                font-size: 90px;
                opacity: 0.08;
                transform: rotate(-10deg);
            }

            .books {
                position: absolute;
                right: 12px;
                bottom: 7px;
                display: flex;
                gap: 4px;
                opacity: 0.13;
                transform: rotate(-8deg);
            }

            .books span {
                width: 20px;
                height: 58px;
                border-radius: 4px;
                background: #fff;
            }

            .books span:nth-child(2) {
                height: 68px;
            }

            .books span:nth-child(3) {
                height: 50px;
            }

            .dashboard-grid {
                display: grid;
                grid-template-columns: 1.7fr 1fr;
                gap: 15px;
            }

            .card {
                background: #fff;
                border: 1px solid #e2eaf0;
                border-radius: 16px;
                padding: 16px;
                box-shadow: 0 6px 18px rgba(24, 33, 63, 0.035);
            }

            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 14px;
            }

            .card-header h2 {
                font-size: 14px;
                font-weight: 600;
            }

            .card-header span {
                font-size: 11px;
                color: #8f98a9;
            }

            .chart-container {
                height: 210px;
            }

            .empty-progress {
                min-height: 210px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                color: #9ba3b1;
            }

            .empty-progress i {
                width: 58px;
                height: 58px;
                display: grid;
                place-items: center;
                margin-bottom: 12px;
                border-radius: 50%;
                background: #f1f6fa;
                color: #3f68ae;
                font-size: 21px;
            }

            .empty-progress strong {
                color: #596276;
                font-size: 12px;
            }

            .empty-progress p {
                max-width: 310px;
                margin-top: 5px;
                font-size: 11.5px;
            }

            .activities {
                margin-top: 15px;
            }

            .activity,
            .user-row {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 9px 0;
                border-bottom: 1px solid #f0f2f6;
            }

            .activity:last-child,
            .user-row:last-child {
                border-bottom: 0;
            }

            .activity-icon {
                width: 33px;
                height: 33px;
                display: grid;
                place-items: center;
                border-radius: 9px;
                background: #eef7f9;
                color: #20a1b8;
                font-size: 12.5px;
            }

            .activity-text,
            .user-info {
                flex: 1;
                min-width: 0;
            }

            .activity-text strong,
            .user-info strong {
                display: block;
                font-size: 12px;
            }

            .activity-text small,
            .user-info small {
                display: block;
                margin-top: 2px;
                color: #9ca3b1;
                font-size: 11px;
            }

            .user-avatar {
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border-radius: 50%;
                background: #eaf2fa;
                color: #3f68ae;
                font-size: 11.5px;
                font-weight: 600;
            }

            .user-avatar.user-photo {
                padding: 2px;
                overflow: hidden;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
                color: #fff;
            }

            .user-avatar.user-photo img {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                object-fit: cover;
            }

            .role {
                padding: 4px 7px;
                border-radius: 7px;
                font-size: 10.5px;
                font-weight: 600;
            }

            .role-admin {
                background: #eee9ff;
                color: #7654b8;
            }

            .role-formateur {
                background: #e4f7f3;
                color: #168a78;
            }

            .role-apprenant {
                background: #e7f0ff;
                color: #3f68ae;
            }

            .role-entreprise {
                background: #fff0e6;
                color: #c46b2b;
            }

            .role-default {
                background: #f1f5fa;
                color: #596276;
            }

            .formation-banner {
                position: relative;
                min-height: 145px;
                margin-top: 15px;
                padding: 21px;
                overflow: hidden;
                border-radius: 16px;
                background: linear-gradient(120deg, #18213f, #3f68ae);
                color: #fff;
            }

            .formation-banner-content {
                position: relative;
                z-index: 2;
                width: 55%;
            }

            .formation-banner h2 {
                margin-bottom: 7px;
                font-size: 18px;
            }

            .formation-banner p {
                color: rgba(255, 255, 255, 0.72);
                font-size: 11.5px;
                line-height: 1.6;
            }

            .formation-btn {
                display: inline-flex;
                margin-top: 11px;
                padding: 8px 14px;
                border-radius: 8px;
                background: #fff;
                color: #18213f;
                font-size: 11.5px;
                font-weight: 600;
            }

            .formation-image {
                position: absolute;
                right: 20px;
                bottom: 0;
                width: 210px;
                height: 170px;
                object-fit: contain;
                object-position: bottom;
            }

            @media (max-width: 1100px) {
                .stats {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .dashboard-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 800px) {
                .dashboard-main {
                    width: calc(100% - 70px);
                    margin-left: 70px;
                    padding-top: 62px;
                }

                .dashboard-content {
                    padding: 16px 15px 24px;
                }

                .stats {
                    grid-template-columns: 1fr;
                }
            }
        </style>

    </head>
    <body>
        <?php
$active_page = 'dashbord.php';
include 'menu.php';
?>

        <main class="dashboard-main">
            <section class="dashboard-content">
                <div class="dashboard-inner">
                    <div class="database-status">

                        <i class="fa-solid fa-circle"></i>

                        Vous êtes connecté à la base de donnée

                    </div>
                    <div class="welcome">

                        <div>

                            <h1>
                                Bonjour <?= htmlspecialchars($prenom) ?>
                            </h1>

                            <p>
                                Voici un aperçu de l'activité de votre plateforme TOUN.
                            </p>

                        </div>

                    </div>

                    <div class="stats">

                        <div class="stat-card dark">

                            <div class="stat-top">

                                <div class="stat-icon">
                                    <i class="fa-solid fa-users"></i>
                                </div>

                            </div>

                            <div class="stat-label">
                                Utilisateurs
                            </div>

                            <div class="stat-number">
                                <?= $total_utilisateurs ?>
                            </div>

                            <i class="fa-solid fa-users stat-decoration"></i>

                        </div>

                        <div class="stat-card turquoise">

                            <div class="stat-top">

                                <div class="stat-icon">
                                    <i class="fa-solid fa-user-graduate"></i>
                                </div>

                            </div>

                            <div class="stat-label">
                                Apprenants
                            </div>

                            <div class="stat-number">
                                <?= $total_apprenants ?>
                            </div>

                            <i class="fa-solid fa-user-graduate stat-decoration"></i>

                        </div>

                        <div class="stat-card blue">

                            <div class="stat-top">

                                <div class="stat-icon">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>

                            </div>

                            <div class="stat-label">
                                Formations
                            </div>

                            <div class="stat-number">
                                <?= $total_formations ?>
                            </div>

                            <div class="books">

                                <span></span>
                                <span></span>
                                <span></span>

                            </div>

                        </div>

                        <div class="stat-card light">

                            <div class="stat-top">

                                <div class="stat-icon">
                                    <i class="fa-solid fa-building"></i>
                                </div>

                            </div>

                            <div class="stat-label">
                                Entreprises
                            </div>

                            <div class="stat-number">
                                <?= $total_entreprises ?>
                            </div>

                            <i class="fa-solid fa-building stat-decoration"></i>

                        </div>

                        <div class="stat-card formateur">

                            <div class="stat-top">

                                <div class="stat-icon">
                                    <i class="fa-solid fa-user-tie"></i>
                                </div>

                            </div>

                            <div class="stat-label">
                                Formateurs
                            </div>

                            <div class="stat-number">
                                <?= $total_formateurs ?>
                            </div>

                            <i class="fa-solid fa-chalkboard-user stat-decoration"></i>

                        </div>

                        <div class="stat-card recettes">

                            <div class="stat-top">

                                <div class="stat-icon">
                                    <i class="fa-solid fa-money-bill-wave"></i>
                                </div>

                            </div>

                            <div class="stat-label">
                                Recettes (FCFA)
                            </div>

                            <div class="stat-number">
                                <?= number_format($total_recettes, 0, ',', ' ') ?>
                            </div>

                            <i class="fa-solid fa-money-bill-wave stat-decoration"></i>

                        </div>

                    </div>

                    <div class="dashboard-grid">

                        <div class="card">

                            <div class="card-header">

                                <h2>
                                    Activité des inscriptions
                                </h2>

                                <span>
                                    6 derniers mois
                                </span>

                            </div>

                            <div class="chart-container">

                                <canvas id="inscriptionChart"></canvas>

                            </div>

                        </div>

                        <div class="card">

                            <div class="card-header">

                                <h2>
                                    Progression
                                </h2>

                                <span>
                                    En temps réel
                                </span>

                            </div>

                            <div class="empty-progress">

                                <i class="fa-solid fa-chart-line"></i>

                                <strong>
                                    Aucune progression pour le moment
                                </strong>

                                <p>
                                    Les données apparaîtront lorsque les apprenants
                                    commenceront leurs formations.
                                </p>

                            </div>

                        </div>

                    </div>

                    <div class="dashboard-grid activities">

                        <div class="card">

                            <div class="card-header">

                                <h2>
                                    Dernières activités
                                </h2>

                                <span>
                                    Utilisateurs
                                </span>

                            </div>

                            <?php if (count($derniers_utilisateurs) > 0): ?>

                                <?php foreach ($derniers_utilisateurs as $user): ?>

                                    <div class="activity">

                                        <div class="activity-icon">

                                            <i class="fa-solid fa-user-plus"></i>

                                        </div>

                                        <div class="activity-text">

                                            <strong>

                                                <?= htmlspecialchars($user['prenom_user'] . ' ' . $user['nom_user']) ?>

                                            </strong>

                                            <small>

                                                Nouveau compte
                                                <?= htmlspecialchars($user['role_user']) ?>

                                            </small>

                                        </div>

                                        <small>

                                            <?= date('d/m/Y', strtotime($user['date_inscrip_user'])) ?>

                                        </small>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="empty-progress">

                                    <i class="fa-solid fa-clock"></i>

                                    <strong>
                                        Aucune activité
                                    </strong>

                                    <p>
                                        Les dernières activités apparaîtront ici.
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="card">

                            <div class="card-header">

                                <h2>
                                    Derniers utilisateurs
                                </h2>

                                <a href="utilisateur.php">

                                    <span>
                                        Voir tout
                                    </span>

                                </a>

                            </div>

                            <?php if (count($derniers_utilisateurs) > 0): ?>

                                <?php foreach ($derniers_utilisateurs as $user): ?>

                                    <div class="user-row">

                                        <?php
$user_photo_url = getUserPhotoUrl($user['photo_user'] ?? '');
$initiales = strtoupper(substr($user['prenom_user'] ?? '', 0, 1) . substr($user['nom_user'] ?? '', 0, 1));
$role = strtolower(trim($user['role_user'] ?? ''));
$role_class = 'role-default';

if ($role === 'admin') {
    $role_class = 'role-admin';
} elseif ($role === 'formateur') {
    $role_class = 'role-formateur';
} elseif ($role === 'apprenant') {
    $role_class = 'role-apprenant';
} elseif (in_array($role, ['entreprise', 'gest_entreprise'], true)) {
    $role_class = 'role-entreprise';
}
?>

                                        <div class="user-avatar<?= $user_photo_url ? ' user-photo' : '' ?>">
                                            <?php if ($user_photo_url): ?>
                                                <img
                                                    src="<?= htmlspecialchars($user_photo_url, ENT_QUOTES, 'UTF-8') ?>"
                                                    alt="Photo"
                                                    onerror="this.style.display='none'; this.parentElement.classList.remove('user-photo'); this.parentElement.innerHTML='<?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>';"
                                                >
                                            <?php else: ?>
                                                <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="user-info">

                                            <strong>

                                                <?= htmlspecialchars($user['prenom_user'] . ' ' . $user['nom_user']) ?>

                                            </strong>

                                            <small>

                                                <?= htmlspecialchars($user['email_user']) ?>

                                            </small>

                                        </div>

                                        <span class="role <?= $role_class ?>">
                                            <?= htmlspecialchars($user['role_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="empty-progress">

                                    <i class="fa-solid fa-users"></i>

                                    <strong>
                                        Aucun utilisateur
                                    </strong>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="formation-banner">

                        <div class="formation-banner-content">

                            <h2>
                                Développez les compétences avec TOUN
                            </h2>

                            <p>
                                Gérez les formations, accompagnez les apprenants
                                et suivez l'évolution des compétences depuis votre
                                espace administrateur.
                            </p>

                            <a
                                href="formation.php"
                                class="formation-btn"
                            >
                                Gérer les formations
                            </a>

                        </div>

                        <img
                            src="images/img3.png"
                            class="formation-image"
                            alt="Formation TOUN"
                        >

                    </div>

                </div>
            </section>
        </main>

        <script>
            const ctx = document.getElementById('inscriptionChart');

            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($mois) ?>,
                        datasets: [
                            {
                                label: 'Inscriptions',
                                data: <?= json_encode($nombre_inscriptions) ?>,
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        </script>
    </body>
</html>

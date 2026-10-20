<?php

session_start();

require_once "../config/connexion.php";

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit;
}

$idUser = (int) $_SESSION['id_user'];

$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));

$nomAffiche = trim($prenom . ' ' . $nom);

if ($nomAffiche === '') {
    $nomAffiche = 'Apprenant';
}


/* UTILITAIRES */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


function getPhotoUrl(PDO $con, int $idUser): string
{
    $photo = trim((string) ($_SESSION['photo_user'] ?? ''));

    if ($photo === '') {

        try {

            $stmt = $con->prepare("
                SELECT photo_user
                FROM t_utilisateurs
                WHERE id_user = ?
                LIMIT 1
            ");

            $stmt->execute([$idUser]);

            $photo = trim((string) $stmt->fetchColumn());

        } catch (PDOException $e) {

            $photo = '';
        }
    }

    if ($photo === '') {
        return '';
    }

    $photo = str_replace('\\', '/', $photo);

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $photo = ltrim($photo, '/');

    $nomPhoto = basename($photo);

    $possibles = [

        [
            'file' => __DIR__ . '/' . $photo,
            'url' => $photo
        ],

        [
            'file' => __DIR__ . '/../' . $photo,
            'url' => '../' . $photo
        ],

        [
            'file' => __DIR__ . '/../uploads/profils/' . $nomPhoto,
            'url' => '../uploads/profils/' . rawurlencode($nomPhoto)
        ],

        [
            'file' => __DIR__ . '/../uploads/' . $nomPhoto,
            'url' => '../uploads/' . rawurlencode($nomPhoto)
        ],

        [
            'file' => __DIR__ . '/../images/profils/' . $nomPhoto,
            'url' => '../images/profils/' . rawurlencode($nomPhoto)
        ],

        [
            'file' => __DIR__ . '/../images/' . $nomPhoto,
            'url' => '../images/' . rawurlencode($nomPhoto)
        ],

        [
            'file' => __DIR__ . '/../assets/uploads/' . $nomPhoto,
            'url' => '../assets/uploads/' . rawurlencode($nomPhoto)
        ]

    ];

    foreach ($possibles as $item) {

        if (is_file($item['file'])) {
            return $item['url'];
        }
    }

    return '';
}


function formationImage(?string $image): string
{
    $image = trim((string) $image);

    if ($image === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    $image = str_replace('\\', '/', $image);
    $image = ltrim($image, '/');

    $nomImage = basename($image);

    $possibles = [

        [
            'file' => __DIR__ . '/../' . $image,
            'url' => '../' . $image
        ],

        [
            'file' => __DIR__ . '/../uploads/formations/' . $nomImage,
            'url' => '../uploads/formations/' . rawurlencode($nomImage)
        ],

        [
            'file' => __DIR__ . '/../uploads/' . $nomImage,
            'url' => '../uploads/' . rawurlencode($nomImage)
        ],

        [
            'file' => __DIR__ . '/../images/formations/' . $nomImage,
            'url' => '../images/formations/' . rawurlencode($nomImage)
        ],

        [
            'file' => __DIR__ . '/../images/' . $nomImage,
            'url' => '../images/' . rawurlencode($nomImage)
        ]

    ];

    foreach ($possibles as $item) {

        if (is_file($item['file'])) {
            return $item['url'];
        }
    }

    return '';
}


/* UTILISATEUR */

$photoUrl = getPhotoUrl($con, $idUser);

$initiales = strtoupper(
    substr($prenom, 0, 1) .
    substr($nom, 0, 1)
);

if ($initiales === '') {
    $initiales = 'TO';
}


/* FORMATIONS */

$formations = [];


$sql = "

    SELECT

        f.id_form,
        f.titre_form,
        f.descrip_form,
        f.img_form,
        f.niveau_form,
        f.duree_form,

        c.nom_cat,

        COALESCE(
            p.pourcent_avancement,
            0
        ) AS progression,

        COALESCE(
            p.statut_progression,
            'non_commence'
        ) AS statut_progression

    FROM t_inscriptions i

    INNER JOIN t_formations f
        ON f.id_form = i.id_form

    LEFT JOIN t_categories c
        ON c.id_cat = f.id_cat

    LEFT JOIN t_progression p
        ON p.id_user = i.id_user
        AND p.id_form = i.id_form

    WHERE i.id_user = ?

    AND i.statut_inscrip IN (
        'active',
        'terminee'
    )

    ORDER BY i.date_inscrip_form DESC

";


try {

    $stmt = $con->prepare($sql);

    $stmt->execute([$idUser]);

    $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $formations = [];
}


$totalFormations = count($formations);

$totalProgression = 0;

$totalEnCours = 0;

$totalTerminees = 0;

$totalNonCommencees = 0;


foreach ($formations as &$formation) {

    $progression = (int) round(
        (float) $formation['progression']
    );

    $progression = max(
        0,
        min(100, $progression)
    );

    $formation['progression'] = $progression;

    $formation['image_url'] = formationImage(
        $formation['img_form']
    );

    $totalProgression += $progression;


    if ($progression >= 100) {

        $totalTerminees++;

        $formation['statut_affiche'] = 'Terminée';

        $formation['statut_class'] = 'terminee';

    } elseif ($progression > 0) {

        $totalEnCours++;

        $formation['statut_affiche'] = 'En cours';

        $formation['statut_class'] = 'encours';

    } else {

        $totalNonCommencees++;

        $formation['statut_affiche'] = 'Non commencée';

        $formation['statut_class'] = 'non-commencee';
    }
}

unset($formation);


$progressionGlobale = $totalFormations > 0
    ? round($totalProgression / $totalFormations)
    : 0;

$iaContext = [
    'page' => 'progression',
    'progression_globale' => $progressionGlobale,
    'formations' => $totalFormations,
    'formations_en_cours' => $totalEnCours,
    'formations_terminees' => $totalTerminees,
    'formations_non_commencees' => $totalNonCommencees
];

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ma progression | TOUN</title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="style.css"
    >


    <style>

        .progress-page {
            max-width: 1400px;
        }


        .page-header {
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0 0 5px;
            color: #18213f;
            font-size: 24px;
        }

        .page-header p {
            margin: 0;
            color: #7b899b;
            font-size: 10px;
        }


        /* BANNIERE */

        .global-card {
            position: relative;

            min-height: 190px;

            margin-bottom: 18px;

            padding: 25px 35px;

            display: flex;

            align-items: center;

            gap: 30px;

            overflow: hidden;

            border-radius: 18px;

            background:
                linear-gradient(
                    110deg,
                    #18213f 0%,
                    #1d385c 55%,
                    #23779b 100%
                );

            color: #fff;
        }


        .global-card::after {

            content: "";

            position: absolute;

            width: 260px;
            height: 260px;

            right: -125px;
            top: -135px;

            border:
                32px solid
                rgba(32, 161, 184, .13);

            border-radius: 50%;
        }


        .progress-circle {

            width: 120px;
            height: 120px;

            min-width: 120px;

            position: relative;
            z-index: 2;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                conic-gradient(
                    #5fe0e5
                    <?= $progressionGlobale ?>%,
                    rgba(255,255,255,.13) 0
                );
        }


        .progress-circle::before {

            content: "";

            position: absolute;

            width: 92px;
            height: 92px;

            border-radius: 50%;

            background: #1d385c;
        }


        .progress-circle strong {

            position: relative;

            z-index: 2;

            color: #fff;

            font-size: 28px;

            font-weight: 700;
        }


        .global-info {

            position: relative;

            z-index: 2;

            max-width: 620px;
        }


        .global-info small {

            display: block;

            margin-bottom: 7px;

            color: #5fe0e5;

            font-size: 8px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1.2px;
        }


        .global-info h2 {

            margin: 0 0 8px;

            color: #fff;

            font-size: 20px;

            font-weight: 700;
        }


        .global-info p {

            max-width: 570px;

            margin: 0;

            color: rgba(255,255,255,.72);

            font-size: 9px;

            line-height: 1.7;
        }


        .progress-hero-image {

            position: absolute;

            right: 38px;

            bottom: 15px;

            z-index: 3;

            width: 105px;

            height: 105px;

            display: flex;

            align-items: center;
            justify-content: center;
        }


        .progress-hero-image img {

            width: 100%;
            height: 100%;

            display: block;

            object-fit: contain;
        }


        /* STATISTIQUES */

        .progress-stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 12px;

            margin-bottom: 24px;
        }


        .progress-stat {

            min-height: 105px;

            padding: 15px;

            border:
                1px solid #e5ebf1;

            border-radius: 14px;

            background: #fff;
        }


        .progress-stat-icon {

            width: 31px;
            height: 31px;

            margin-bottom: 8px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #eef8fa;

            color: #20a1b8;

            font-size: 12px;
        }


        .progress-stat:nth-child(2)
        .progress-stat-icon {

            background: #eef3fb;

            color: #3f68ae;
        }


        .progress-stat:nth-child(3)
        .progress-stat-icon {

            background: #eaf8f0;

            color: #28a968;
        }


        .progress-stat:nth-child(4)
        .progress-stat-icon {

            background: #fff8e9;

            color: #c58b19;
        }


        .progress-stat strong {

            display: block;

            color: #18213f;

            font-size: 20px;

            line-height: 1;
        }


        .progress-stat span {

            display: block;

            margin-top: 5px;

            color: #8993a4;

            font-size: 8px;
        }


        /* LISTE */

        .section-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 13px;
        }


        .section-heading h2 {

            margin: 0;

            color: #18213f;

            font-size: 14px;
        }


        .section-heading span {

            color: #8993a4;

            font-size: 8px;
        }


        .progress-list {

            display: grid;

            gap: 11px;
        }


        .progress-card {

            display: grid;

            grid-template-columns:
                155px
                minmax(0, 1fr)
                125px;

            gap: 17px;

            align-items: center;

            padding: 12px;

            border:
                1px solid #e5ebf1;

            border-radius: 15px;

            background: #fff;

            transition: .2s ease;
        }


        .progress-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(24,33,63,.07);
        }


        .progress-image {

            width: 155px;
            height: 95px;

            overflow: hidden;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #18213f,
                    #20a1b8
                );

            display: flex;

            align-items: center;
            justify-content: center;

            color: #fff;
        }


        .progress-image img {

            width: 100%;
            height: 100%;

            display: block;

            object-fit: cover;
        }


        .progress-image i {

            font-size: 25px;

            opacity: .55;
        }


        .progress-category {

            display: inline-flex;

            padding: 4px 7px;

            margin-bottom: 5px;

            border-radius: 7px;

            background: #eaf8fb;

            color: #14729b;

            font-size: 6.5px;

            font-weight: 700;
        }


        .progress-content h3 {

            margin: 0 0 6px;

            color: #18213f;

            font-size: 11px;
        }


        .progress-content p {

            max-width: 650px;

            margin: 0 0 10px;

            color: #8993a4;

            font-size: 8px;

            line-height: 1.55;
        }


        .bar-row {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .bar {

            flex: 1;

            height: 7px;

            overflow: hidden;

            border-radius: 20px;

            background: #edf1f5;
        }


        .bar span {

            display: block;

            height: 100%;

            border-radius: 20px;

            background:
                linear-gradient(
                    90deg,
                    #20a1b8,
                    #3f68ae
                );
        }


        .bar-percent {

            min-width: 32px;

            color: #3f68ae;

            font-size: 8px;

            font-weight: 700;

            text-align: right;
        }


        .progress-right {

            text-align: right;
        }


        .status {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 8px;

            border-radius: 8px;

            font-size: 7px;

            font-weight: 700;
        }


        .status.encours {

            background: #eaf8fb;

            color: #14729b;
        }


        .status.terminee {

            background: #eaf8f0;

            color: #23814e;
        }


        .status.non-commencee {


            background: #fff8df;

            color: #8a7020;
        }


        .continue-btn {

            min-width: 105px;

            height: 32px;

            margin-top: 9px;

            padding: 0 11px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    #20a1b8,
                    #3f68ae
                );

            color: #fff;

            font-size: 8px;

            font-weight: 600;
        }


        /* VIDE */

        .empty-progress {

            padding: 50px 20px;

            border:
                1px solid #e5ebf1;

            border-radius: 17px;

            background: #fff;

            text-align: center;
        }


        .empty-progress-icon {

            width: 58px;
            height: 58px;

            margin: 0 auto 12px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    #e9f8fa,
                    #edf2fb
                );

            color: #20a1b8;

            font-size: 22px;
        }


        .empty-progress h3 {

            margin: 0 0 6px;

            color: #18213f;

            font-size: 14px;
        }


        .empty-progress p {

            max-width: 390px;

            margin: 0 auto 15px;

            color: #8993a4;

            font-size: 9px;

            line-height: 1.7;
        }


        .catalogue-btn {

            min-height: 34px;

            padding: 0 13px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    #20a1b8,
                    #3f68ae
                );

            color: #fff;

            font-size: 8px;

            font-weight: 600;
        }


        /* PHOTO PROFIL */

        .profile-avatar {

            overflow: hidden;
        }


        .profile-avatar img {

            width: 100%;
            height: 100%;

            display: block;

            object-fit: cover;
        }


        /* RESPONSIVE */

        @media (max-width: 1050px) {

            .progress-stats {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .progress-card {

                grid-template-columns:
                    130px
                    minmax(0, 1fr);
            }


            .progress-image {

                width: 130px;
            }


            .progress-right {

                grid-column: 2;

                text-align: left;
            }
        }


        @media (max-width: 800px) {

            .page-header h1 {

                font-size: 20px;
            }


            .global-card {

                min-height: 220px;

                padding: 25px;
            }


            .progress-circle {

                width: 105px;
                height: 105px;

                min-width: 105px;
            }


            .progress-circle::before {

                width: 80px;
                height: 80px;
            }


            .progress-circle strong {

                font-size: 24px;
            }


            .global-info h2 {

                font-size: 18px;
            }


            .global-info {

                max-width: 480px;
            }


            .progress-hero-image {

                width: 85px;
                height: 85px;

                right: 18px;

                bottom: 12px;
            }


            .progress-card {

                grid-template-columns: 1fr;
            }


            .progress-image {

                width: 100%;

                height: 150px;
            }


            .progress-right {

                grid-column: auto;

                text-align: left;
            }
        }


        @media (max-width: 520px) {

            .progress-stats {

                grid-template-columns:
                    repeat(2, 1fr);

                gap: 9px;
            }


            .progress-stat {

                min-height: 100px;

                padding: 13px;
            }


            .global-card {

                min-height: 235px;

                padding: 22px;
            }


            .global-card {

                gap: 15px;
            }


            .progress-circle {

                width: 90px;
                height: 90px;

                min-width: 90px;
            }


            .progress-circle::before {

                width: 68px;
                height: 68px;
            }


            .progress-circle strong {

                font-size: 21px;
            }


            .global-info h2 {

                font-size: 16px;
            }


            .global-info p {

                font-size: 8px;
            }


            .progress-hero-image {

                width: 70px;
                height: 70px;

                right: 12px;
                bottom: 10px;
            }
        }



        /* TOUN AI */

        .toun-ai-button {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 9999;

            width: 148px;
            height: 70px;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;

            border: 0;
            border-radius: 38px;

            background: linear-gradient(135deg, #18213f, #315da5);
            color: #fff;

            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;

            box-shadow: 0 15px 30px rgba(24,33,63,.22);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .toun-ai-button i {
            color: #51dce3;
            font-size: 19px;
        }

        .toun-ai-button:hover {
            color: #fff;
            transform: translateY(-4px);
            box-shadow: 0 18px 35px rgba(24,33,63,.28);
        }

        @media (max-width: 600px) {
            .toun-ai-button {
                right: 16px;
                bottom: 16px;
                width: 132px;
                height: 58px;
                gap: 9px;
                font-size: 10px;
            }

            .toun-ai-button i {
                font-size: 16px;
            }
        }

    </style>

</head>


<body>

<div class="layout">

    <?php include __DIR__ . '/menu.php'; ?>


    <main class="main">

        <header class="topbar">

            <button
                class="mobile-menu"
                type="button"
                id="mobileMenu"
                aria-label="Ouvrir le menu"
            >
                <i class="fa-solid fa-bars"></i>
            </button>


            <div class="search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    placeholder="Rechercher une formation..."
                >

            </div>


            <div class="top-right">

                <a
                    href="rendez-vous.php"
                    class="top-action"
                >

                    <i class="fa-regular fa-calendar-check"></i>

                    <span>
                        Mes rendez-vous
                    </span>

                </a>


                <a
                    href="notifications.php"
                    class="notification"
                    aria-label="Notifications"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span></span>

                </a>


                <a
                    href="profil.php"
                    class="profile"
                >

                    <div class="profile-avatar">

                        <?php if ($photoUrl !== ''): ?>

                            <img
                                src="<?= e($photoUrl) ?>"
                                alt="Photo de profil"
                            >

                        <?php else: ?>

                            <?= e($initiales) ?>

                        <?php endif; ?>

                    </div>


                    <div class="profile-info">

                        <strong>
                            <?= e($nomAffiche) ?>
                        </strong>

                        <small>
                            Apprenant
                        </small>

                    </div>

                </a>

            </div>

        </header>


        <section class="content progress-page">


            <div class="page-header">

                <h1>
                    Ma progression
                </h1>

                <p>
                    Suivez votre évolution et continuez votre apprentissage.
                </p>

            </div>


            <div class="global-card">


                <div class="progress-circle">

                    <strong>
                        <?= $progressionGlobale ?>%
                    </strong>

                </div>


                <div class="global-info">

                    <small>
                        Progression globale
                    </small>

                    <h2>
                        Votre parcours d'apprentissage
                    </h2>

                    <p>
                        Retrouvez ici l'évolution de toutes vos formations
                        suivies sur TOUN et reprenez rapidement votre apprentissage.
                    </p>

                </div>


                <div class="progress-hero-image">

                    <img
                        src="../images/progression.png"
                        alt="Illustration de progression"
                    >

                </div>

            </div>


            <div class="progress-stats">


                <div class="progress-stat">

                    <div class="progress-stat-icon">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>

                    <strong>
                        <?= $progressionGlobale ?>%
                    </strong>

                    <span>
                        Progression globale
                    </span>

                </div>


                <div class="progress-stat">

                    <div class="progress-stat-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>

                    <strong>
                        <?= $totalFormations ?>
                    </strong>

                    <span>
                        Formations suivies
                    </span>

                </div>


                <div class="progress-stat">

                    <div class="progress-stat-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <strong>
                        <?= $totalTerminees ?>
                    </strong>

                    <span>
                        Formations terminées
                    </span>

                </div>


                <div class="progress-stat">

                    <div class="progress-stat-icon">

                        <i class="fa-regular fa-clock"></i>

                    </div>

                    <strong>
                        <?= $totalNonCommencees ?>
                    </strong>

                    <span>
                        Non commencées
                    </span>

                </div>

            </div>


            <div class="section-heading">

                <h2>
                    Mes formations
                </h2>

                <span>
                    <?= $totalFormations ?> formation(s)
                </span>

            </div>


            <?php if (!empty($formations)): ?>


                <div class="progress-list">


                    <?php foreach ($formations as $formation): ?>


                        <article class="progress-card">


                            <div class="progress-image">

                                <?php if ($formation['image_url'] !== ''): ?>

                                    <img
                                        src="<?= e($formation['image_url']) ?>"
                                        alt="<?= e($formation['titre_form']) ?>"
                                    >

                                <?php else: ?>

                                    <i class="fa-solid fa-book-open"></i>

                                <?php endif; ?>

                            </div>


                            <div class="progress-content">


                                <span class="progress-category">

                                    <?= e(
                                        $formation['nom_cat']
                                        ?: 'Formation'
                                    ) ?>

                                </span>


                                <h3>

                                    <?= e(
                                        $formation['titre_form']
                                    ) ?>

                                </h3>


                                <p>

                                    <?= e(
                                        mb_strimwidth(
                                            (string)
                                            $formation['descrip_form'],
                                            0,
                                            150,
                                            '...'
                                        )
                                    ) ?>

                                </p>


                                <div class="bar-row">


                                    <div class="bar">

                                        <span
                                            style="
                                                width:
                                                <?= $formation['progression'] ?>%;
                                            "
                                        ></span>

                                    </div>


                                    <span class="bar-percent">

                                        <?= $formation['progression'] ?>%

                                    </span>

                                </div>

                            </div>


                            <div class="progress-right">


                                <span
                                    class="
                                        status
                                        <?= e(
                                            $formation['statut_class']
                                        ) ?>
                                    "
                                >


                                    <?php if (
                                        $formation['progression'] >= 100
                                    ): ?>

                                        <i
                                            class="fa-solid fa-check"
                                        ></i>

                                    <?php elseif (
                                        $formation['progression'] > 0
                                    ): ?>

                                        <i
                                            class="fa-solid fa-spinner"
                                        ></i>

                                    <?php else: ?>

                                        <i
                                            class="fa-regular fa-clock"
                                        ></i>

                                    <?php endif; ?>


                                    <?= e(
                                        $formation['statut_affiche']
                                    ) ?>

                                </span>


                                <a
                                    href="
                                        ../formation-details.php?id=
                                        <?= (int) $formation['id_form'] ?>
                                    "
                                    class="continue-btn"
                                >


                                    <?php if (
                                        $formation['progression'] >= 100
                                    ): ?>

                                        Voir la formation

                                    <?php elseif (
                                        $formation['progression'] > 0
                                    ): ?>

                                        Continuer

                                    <?php else: ?>

                                        Commencer

                                    <?php endif; ?>


                                    <i
                                        class="fa-solid fa-arrow-right"
                                    ></i>

                                </a>

                            </div>

                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-progress">


                    <div class="empty-progress-icon">

                        <i
                            class="fa-solid fa-chart-line"
                        ></i>

                    </div>


                    <h3>
                        Aucune progression pour le moment
                    </h3>


                    <p>
                        Vous n'avez encore aucune formation dans votre espace.
                        Découvrez le catalogue et commencez votre parcours.
                    </p>


                    <a
                        href="../formation.php"
                        class="catalogue-btn"
                    >

                        <i
                            class="fa-solid fa-magnifying-glass"
                        ></i>

                        Découvrir les formations

                    </a>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

<a
    href="ia.php"
    class="toun-ai-button"
    aria-label="Ouvrir TOUN AI"
>
    <i class="fa-solid fa-wand-magic-sparkles"></i>
    <span>TOUN AI</span>
</a>


<script>

const mobileMenu =
    document.getElementById('mobileMenu');

const sidebar =
    document.getElementById('sidebar');


if (mobileMenu && sidebar) {

    mobileMenu.addEventListener(
        'click',
        function () {

            sidebar.classList.toggle('open');

        }
    );
}


const currentPage =
    window.location.pathname
        .split('/')
        .pop();


document
    .querySelectorAll('.menu-link')
    .forEach(link => {

        const href =
            link.getAttribute('href') || '';

        const linkPage =
            href
                .split('/')
                .pop()
                .split('?')[0];


        if (linkPage === currentPage) {

            document
                .querySelectorAll('.menu-link')
                .forEach(item => {

                    item.classList.remove('active');

                });

            link.classList.add('active');
        }

    });

</script>

</body>

</html>
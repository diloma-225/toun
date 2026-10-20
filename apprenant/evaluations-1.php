<?php

session_start();

require_once "../config/connexion.php";

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header("Location: ../connexion.php");
    exit;
}

$idUser = (int) $_SESSION['id_user'];


function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDate($date): string
{
    return !empty($date)
        ? date('d/m/Y', strtotime($date))
        : '—';
}


$stmtUser = $con->prepare("
    SELECT nom_user, prenom_user, photo_user
    FROM t_utilisateurs
    WHERE id_user = ?
    LIMIT 1
");

$stmtUser->execute([$idUser]);

$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$nom = $user['nom_user'] ?? '';
$prenom = $user['prenom_user'] ?? '';

$nomAffiche = trim($prenom . ' ' . $nom);

if ($nomAffiche === '') {
    $nomAffiche = 'Apprenant';
}

$initiales = strtoupper(
    mb_substr($prenom, 0, 1) .
    mb_substr($nom, 0, 1)
);

if ($initiales === '') {
    $initiales = 'TO';
}


$photoUrl = '';

if (!empty($user['photo_user'])) {

    $photo = str_replace('\\', '/', trim($user['photo_user']));

    if (preg_match('#^(https?:)?//#i', $photo)) {

        $photoUrl = $photo;

    } else {

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
                $photoUrl = $item['url'];
                break;
            }
        }
    }
}


$sql = "

    SELECT

        q.id_quiz,
        q.titre_quiz,
        q.description_quiz,
        q.note_minimale,

        m.id_module,
        m.titre_module,
        m.ordre_module,

        f.id_form,
        f.titre_form,

        r.id_resultat,
        r.note_obtenue,
        r.statut_resultat,
        r.date_passage

    FROM t_inscriptions i

    INNER JOIN t_formations f
        ON f.id_form = i.id_form

    INNER JOIN t_modules m
        ON m.id_form = f.id_form

    INNER JOIN t_quiz q
        ON q.id_module = m.id_module

    LEFT JOIN t_resultats_quiz r
        ON r.id_resultat = (

            SELECT MAX(r2.id_resultat)

            FROM t_resultats_quiz r2

            WHERE r2.id_user = ?
            AND r2.id_quiz = q.id_quiz

        )

    WHERE i.id_user = ?

    AND i.statut_inscrip IN (
        'active',
        'terminee'
    )

    ORDER BY
        f.titre_form ASC,
        m.ordre_module ASC,
        q.id_quiz DESC
";


$stmt = $con->prepare($sql);

$stmt->execute([
    $idUser,
    $idUser
]);

$evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);


$totalEvaluations = count($evaluations);

$reussies = 0;
$aPasser = 0;
$aReprendre = 0;


foreach ($evaluations as $evaluation) {

    $statut = strtolower(
        trim($evaluation['statut_resultat'] ?? '')
    );

    if (empty($evaluation['id_resultat'])) {

        $aPasser++;

    } elseif (
        in_array(
            $statut,
            [
                'réussi',
                'reussi',
                'success',
                'valide',
                'validé',
                'validé'
            ],
            true
        )
    ) {

        $reussies++;

    } else {

        $aReprendre++;
    }
}


$tauxReussite = $totalEvaluations > 0
    ? round(($reussies / $totalEvaluations) * 100)
    : 0;

$iaContext = [
    'page' => 'evaluations',
    'total_evaluations' => $totalEvaluations,
    'evaluations_reussies' => $reussies,
    'evaluations_a_passer' => $aPasser,
    'evaluations_a_reprendre' => $aReprendre,
    'taux_reussite' => $tauxReussite
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

    <title>Mes évaluations | TOUN</title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f8fc;
            color: #18213f;
        }

        a {
            text-decoration: none;
        }

        .evaluation-page {
            max-width: 1400px;
        }

        .page-header {
            margin-bottom: 20px;
        }

        .page-header h1 {
            color: #18213f;
            font-size: 25px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #7c8da1;
            font-size: 11px;
        }

        .evaluation-hero {
            position: relative;
            overflow: hidden;
            min-height: 145px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 25px 28px;
            margin-bottom: 18px;
            border-radius: 18px;
            background: linear-gradient( 135deg, #18213f 0%, #087db7 55%, #20a1b8 100% );
            box-shadow: 0 12px 28px rgba(24,33,63,.13);
        }

        .evaluation-hero::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            right: 25px;
            top: -110px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
        }

        .evaluation-hero::after {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            right: 155px;
            bottom: -85px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 680px;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            margin-bottom: 8px;
            border-radius: 30px;
            background: rgba(255,255,255,.13);
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-content h2 {
            color: #fff;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .hero-content p {
            color: rgba(255,255,255,.83);
            font-size: 9.5px;
            line-height: 1.6;
        }

        .success-circle {
            position: relative;
            z-index: 2;
            width: 88px;
            height: 88px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: conic-gradient( #fff <?= $tauxReussite ?>%, rgba(255,255,255,.18) 0 );
            box-shadow: 0 0 0 9px rgba(255,255,255,.10);
        }

        .success-circle::before {
            content: "";
            position: absolute;
            width: 67px;
            height: 67px;
            border-radius: 50%;
            background: #0879a9;
        }

        .success-circle-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .success-circle strong {
            color: #fff;
            font-size: 16px;
            line-height: 1;
        }

        .success-circle span {
            margin-top: 4px;
            color: rgba(255,255,255,.75);
            font-size: 6px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 22px;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            min-height: 75px;
            padding: 14px 15px;
            background: #fff;
            border: 1px solid #e0eaf2;
            border-radius: 14px;
            box-shadow: 0 5px 16px rgba(24,33,63,.045);
            transition: .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: #20a1b8;
        }

        .stat-card.blue {
            border-left: 4px solid #20a1b8;
        }

        .stat-card.green {
            border-left: 4px solid #12ad91;
        }

        .stat-card.orange {
            border-left: 4px solid #20a1b8;
        }

        .stat-card.red {
            border-left: 4px solid #e95c6d;
        }

        .stat-top {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 13px;
        }

        .stat-card.blue .stat-icon {
            color: #087db7;
            background: #e8f6fb;
        }

        .stat-card.green .stat-icon {
            color: #0b9f84;
            background: #e8f8f4;
        }

        .stat-card.orange .stat-icon {
            color: #20a1b8;
            background: #eaf3f9;
        }

        .stat-card.red .stat-icon {
            color: #e65367;
            background: #fff0f2;
        }

        .stat-label {
            color: #8295a9;
            font-size: 8px;
            margin-bottom: 2px;
        }

        .stat-value {
            color: #18213f;
            font-size: 18px;
            font-weight: 800;
            line-height: 1;
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 11px;
        }

        .section-heading h2 {
            color: #18213f;
            font-size: 15px;
            font-weight: 800;
        }

        .section-count {
            color: #8290a2;
            font-size: 8px;
        }

        .evaluations-list {
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .evaluation-card {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: 52px minmax(0, 1fr) 145px 110px;
            align-items: center;
            gap: 15px;
            padding: 15px 17px;
            background: #fff;
            border: 1px solid #dceaf2;
            border-radius: 15px;
            box-shadow: 0 5px 16px rgba(24,33,63,.04);
            transition: .25s ease;
        }

        .evaluation-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient( 180deg, #18213f, #087db7, #20a1b8 );
        }

        .evaluation-card:hover {
            transform: translateY(-2px);
            border-color: #20a1b8;
            box-shadow: 0 10px 23px rgba(32,161,184,.11);
        }

        .evaluation-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient( 145deg, #e8f7fb, #d9f1f7 );
            border: 1px solid #c7e8f0;
            color: #20a1b8;
            font-size: 18px;
        }

        .evaluation-main {
            min-width: 0;
        }

        .evaluation-main h3 {
            color: #18213f;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .formation-name {
            color: #20a1b8;
            font-size: 8.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .evaluation-description {
            color: #8392a4;
            font-size: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .evaluation-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #8290a2;
            font-size: 7.5px;
        }

        .meta-item i {
            color: #20a1b8;
        }

        .evaluation-result {
            min-width: 0;
        }

        .result-box {
            padding: 8px 10px;
            background: #f8fbfd;
            border: 1px solid #dfebf2;
            border-radius: 10px;
        }

        .result-label {
            color: #8996a7;
            font-size: 7px;
        }

        .result-note {
            color: #087db7;
            font-size: 15px;
            font-weight: 800;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 7px;
            margin-top: 5px;
            border-radius: 30px;
            font-size: 7px;
            font-weight: 700;
        }

        .status-success {
            color: #0a987d;
            background: #e9f8f4;
            border: 1px solid #c9eee5;
        }

        .status-danger {
            color: #d95062;
            background: #fff0f2;
            border: 1px solid #f5d4da;
        }

        .status-neutral {
            color: #087db7;
            background: #eaf7fb;
            border: 1px solid #ccebf2;
        }

        .evaluation-action {
            min-width: 0;
        }

        .btn-evaluation {
            width: 100%;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 10px;
            border-radius: 8px;
            background: linear-gradient( 135deg, #18213f, #20a1b8 );
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            transition: .2s ease;
        }

        .btn-evaluation:hover {
            transform: translateY(-2px);
        }

        .btn-resultat {
            background: #eaf8fb;
            color: #087db7;
            border: 1px solid #ccebf2;
        }

        .empty-state {
            min-height: 190px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
            background: #fff;
            border: 1px solid #dfebf2;
            border-radius: 15px;
            text-align: center;
        }

        .empty-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            border-radius: 14px;
            background: #eaf8fb;
            border: 1px solid #ccebf2;
            color: #20a1b8;
            font-size: 19px;
        }

        .empty-state h3 {
            color: #18213f;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .empty-state p {
            color: #8793a4;
            font-size: 8px;
        }

        .profile-avatar {
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        @media (max-width: 1100px) {
            .evaluation-page {
                padding: 25px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .evaluation-card {
                grid-template-columns: 52px minmax(0, 1fr);
            }

            .evaluation-result,
            .evaluation-action {
                grid-column: 2;
            }
        }

        @media (max-width: 700px) {
            .evaluation-page {
                padding: 20px 15px 30px;
            }

            .evaluation-hero {
                padding: 22px;
            }

            .hero-content h2 {
                font-size: 17px;
            }

            .hero-content p {
                font-size: 8.5px;
            }

            .success-circle {
                width: 72px;
                height: 72px;
            }

            .success-circle::before {
                width: 54px;
                height: 54px;
            }

            .evaluation-card {
                grid-template-columns: 48px minmax(0, 1fr);
                padding: 14px;
            }

            .evaluation-icon {
                width: 48px;
                height: 48px;
            }

            .evaluation-result,
            .evaluation-action {
                grid-column: 1 / -1;
            }

            .btn-evaluation {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .page-header h1 {
                font-size: 21px;
            }

            .evaluation-hero {
                min-height: 130px;
                padding: 18px;
            }

            .hero-content {
                max-width: calc(100% - 70px);
            }

            .hero-content h2 {
                font-size: 15px;
            }

            .hero-content p {
                display: none;
            }

            .success-circle {
                width: 62px;
                height: 62px;
            }

            .success-circle::before {
                width: 46px;
                height: 46px;
            }

            .success-circle strong {
                font-size: 12px;
            }

            .evaluation-main h3 {
                font-size: 11px;
            }

            .evaluation-description {
                display: none;
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


        <section class="content evaluation-page">


            <div class="page-header">

                <h1>
                    Mes évaluations
                </h1>

                <p>
                    Retrouvez vos évaluations et suivez vos résultats de formation.
                </p>

            </div>


            <section class="evaluation-hero">

                <div class="hero-content">

                    <span class="hero-tag">

                        <i class="fa-solid fa-clipboard-check"></i>

                        Évaluations

                    </span>


                    <h2>
                        Évaluez vos connaissances et progressez.
                    </h2>


                    <p>
                        Consultez vos résultats, identifiez les évaluations
                        restantes et continuez votre parcours d’apprentissage
                        avec TOUN.
                    </p>

                </div>


                <div class="success-circle">

                    <div class="success-circle-content">

                        <strong>
                            <?= $tauxReussite ?>%
                        </strong>

                        <span>
                            réussite
                        </span>

                    </div>

                </div>

            </section>


            <section class="stats-grid">


                <div class="stat-card blue">

                    <div class="stat-top">

                        <div class="stat-icon">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </div>

                        <div>

                            <div class="stat-label">
                                Total
                            </div>

                            <div class="stat-value">
                                <?= $totalEvaluations ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="stat-card green">

                    <div class="stat-top">

                        <div class="stat-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>

                        <div>

                            <div class="stat-label">
                                Réussies
                            </div>

                            <div class="stat-value">
                                <?= $reussies ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="stat-card orange">

                    <div class="stat-top">

                        <div class="stat-icon">
                            <i class="fa-solid fa-clock"></i>
                        </div>

                        <div>

                            <div class="stat-label">
                                À passer
                            </div>

                            <div class="stat-value">
                                <?= $aPasser ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="stat-card red">

                    <div class="stat-top">

                        <div class="stat-icon">
                            <i class="fa-solid fa-rotate-right"></i>
                        </div>

                        <div>

                            <div class="stat-label">
                                À reprendre
                            </div>

                            <div class="stat-value">
                                <?= $aReprendre ?>
                            </div>

                        </div>

                    </div>

                </div>


            </section>


            <div class="section-heading">

                <h2>
                    Mes évaluations
                </h2>


                <span class="section-count">

                    <?= $totalEvaluations ?>

                    <?= $totalEvaluations > 1
                        ? 'évaluations'
                        : 'évaluation'
                    ?>

                </span>

            </div>


            <?php if (!empty($evaluations)): ?>


                <div class="evaluations-list">


                    <?php foreach ($evaluations as $evaluation): ?>


                        <?php

                        $hasResult = !empty(
                            $evaluation['id_resultat']
                        );


                        $statut = strtolower(
                            trim(
                                $evaluation['statut_resultat'] ?? ''
                            )
                        );


                        $note = $evaluation['note_obtenue'] !== null
                            ? (float) $evaluation['note_obtenue']
                            : null;


                        $noteMinimale =
                            $evaluation['note_minimale'] !== null
                            ? (float) $evaluation['note_minimale']
                            : null;


                        if (!$hasResult) {

                            $statusClass = 'status-neutral';
                            $statusText = 'À passer';
                            $statusIcon = 'fa-clock';

                        } elseif (
                            in_array(
                                $statut,
                                [
                                    'réussi',
                                    'reussi',
                                    'success',
                                    'valide',
                                    'validé',
                                    'validé'
                                ],
                                true
                            )
                        ) {

                            $statusClass = 'status-success';
                            $statusText = 'Réussi';
                            $statusIcon = 'fa-circle-check';

                        } else {

                            $statusClass = 'status-danger';
                            $statusText = 'À reprendre';
                            $statusIcon = 'fa-rotate-right';

                        }

                        ?>


                        <article class="evaluation-card">


                            <div class="evaluation-icon">

                                <i class="fa-solid fa-clipboard-check"></i>

                            </div>


                            <div class="evaluation-main">


                                <h3>
                                    <?= e($evaluation['titre_quiz']) ?>
                                </h3>


                                <div class="formation-name">

                                    <?= e(
                                        $evaluation['titre_form']
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $evaluation['description_quiz']
                                    )
                                ): ?>

                                    <div class="evaluation-description">

                                        <?= e(
                                            $evaluation['description_quiz']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <div class="evaluation-meta">


                                    <span class="meta-item">

                                        <i class="fa-solid fa-layer-group"></i>

                                        <?= e(
                                            $evaluation['titre_module']
                                        ) ?>

                                    </span>



                                    <?php if (
                                        $noteMinimale !== null
                                    ): ?>

                                        <span class="meta-item">

                                            <i class="fa-solid fa-bullseye"></i>

                                            Minimum
                                            <?= e($noteMinimale) ?>/20

                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        $hasResult &&
                                        !empty(
                                            $evaluation['date_passage']
                                        )
                                    ): ?>

                                        <span class="meta-item">

                                            <i class="fa-regular fa-calendar"></i>

                                            <?= formatDate(
                                                $evaluation['date_passage']
                                            ) ?>

                                        </span>

                                    <?php endif; ?>


                                </div>

                            </div>


                            <div class="evaluation-result">


                                <div class="result-box">

                                    <div class="result-label">
                                        Votre résultat
                                    </div>


                                    <div class="result-note">

                                        <?= $note !== null
                                            ? e($note) . '/20'
                                            : '—'
                                        ?>

                                    </div>

                                </div>


                                <span
                                    class="status-badge <?= e($statusClass) ?>"
                                >

                                    <i
                                        class="fa-solid <?= e($statusIcon) ?>"
                                    ></i>

                                    <?= e($statusText) ?>

                                </span>


                            </div>


                            <div class="evaluation-action">


                                <?php if (!$hasResult): ?>

                                    <a
                                        href="evaluation.php?id=<?= (int) $evaluation['id_quiz'] ?>"
                                        class="btn-evaluation"
                                    >

                                        <i class="fa-solid fa-play"></i>

                                        Passer

                                    </a>


                                <?php else: ?>

                                    <a
                                        href="resultat-evaluation.php?id=<?= (int) $evaluation['id_quiz'] ?>"
                                        class="btn-evaluation btn-resultat"
                                    >

                                        <i class="fa-solid fa-chart-simple"></i>

                                        Résultat

                                    </a>

                                <?php endif; ?>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    <div>

                        <div class="empty-icon">

                            <i class="fa-solid fa-clipboard-check"></i>

                        </div>


                        <h3>
                            Aucune évaluation disponible
                        </h3>


                        <p>
                            Les évaluations de vos formations apparaîtront ici.
                        </p>

                    </div>

                </div>


            <?php endif; ?>


        </section>


    </main>

</div>

<a
    href="ia.php"
    class="ai-button"
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
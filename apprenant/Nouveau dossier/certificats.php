<?php

session_start();

require_once "../config/connexion.php";

if (!isset($_SESSION['id_user']) || (int)$_SESSION['id_user'] <= 0) {
    header("Location: ../connexion.php");
    exit;
}

$idUser = (int)$_SESSION['id_user'];

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDate($date)
{
    return !empty($date) ? date('d/m/Y', strtotime($date)) : '—';
}

function getPhotoUrl(PDO $con, int $idUser): string
{
    $photo = trim((string)($_SESSION['photo_user'] ?? ''));

    if ($photo === '') {
        try {
            $stmt = $con->prepare("
                SELECT photo_user
                FROM t_utilisateurs
                WHERE id_user = ?
                LIMIT 1
            ");
            $stmt->execute([$idUser]);
            $photo = trim((string)$stmt->fetchColumn());
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
    $name = basename($photo);

    $paths = [
        [__DIR__ . '/' . $photo, $photo],
        [__DIR__ . '/../' . $photo, '../' . $photo],
        [__DIR__ . '/../uploads/profils/' . $name, '../uploads/profils/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/' . $name, '../uploads/' . rawurlencode($name)],
        [__DIR__ . '/../images/profils/' . $name, '../images/profils/' . rawurlencode($name)],
        [__DIR__ . '/../images/' . $name, '../images/' . rawurlencode($name)]
    ];

    foreach ($paths as [$file, $url]) {
        if (is_file($file)) {
            return $url;
        }
    }

    return '';
}

function getFormationImage(?string $image): string
{
    $image = trim((string)$image);

    if ($image === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    $image = ltrim(str_replace('\\', '/', $image), '/');
    $name = basename($image);

    $paths = [
        [__DIR__ . '/../' . $image, '../' . $image],
        [__DIR__ . '/../uploads/formations/' . $name, '../uploads/formations/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/' . $name, '../uploads/' . rawurlencode($name)],
        [__DIR__ . '/../images/formations/' . $name, '../images/formations/' . rawurlencode($name)],
        [__DIR__ . '/../images/' . $name, '../images/' . rawurlencode($name)]
    ];

    foreach ($paths as [$file, $url]) {
        if (is_file($file)) {
            return $url;
        }
    }

    return '';
}

function getCertificateLink(?string $link): string
{
    $link = trim((string)$link);

    if ($link === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $link)) {
        return $link;
    }

    $link = ltrim(str_replace('\\', '/', $link), '/');
    $name = basename($link);

    $paths = [
        [__DIR__ . '/../' . $link, '../' . $link],
        [__DIR__ . '/../certificats/' . $name, '../certificats/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/certificats/' . $name, '../uploads/certificats/' . rawurlencode($name)],
        [__DIR__ . '/../documents/certificats/' . $name, '../documents/certificats/' . rawurlencode($name)]
    ];

    foreach ($paths as [$file, $url]) {
        if (is_file($file)) {
            return $url;
        }
    }

    return '';
}

$stmtUser = $con->prepare("
    SELECT nom_user, prenom_user, photo_user
    FROM t_utilisateurs
    WHERE id_user = ?
    LIMIT 1
");
$stmtUser->execute([$idUser]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$nom = trim((string)($user['nom_user'] ?? ''));
$prenom = trim((string)($user['prenom_user'] ?? ''));

$nomAffiche = trim($prenom . ' ' . $nom) ?: 'Apprenant';

$initiales = strtoupper(
    mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)
);

$initiales = $initiales ?: 'TO';

$photoUrl = getPhotoUrl($con, $idUser);

$stmt = $con->prepare("
    SELECT
        c.id_certificat,
        c.id_user,
        c.id_form,
        c.date_obtention,
        c.numero_certificat,
        c.lien_certificat,
        f.titre_form,
        f.img_form,
        f.niveau_form,
        f.duree_form
    FROM t_certificats c
    INNER JOIN t_formations f
        ON f.id_form = c.id_form
    WHERE c.id_user = ?
    ORDER BY c.date_obtention DESC
");

$stmt->execute([$idUser]);
$certificats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCertificats = count($certificats);

$formationsCertifiees = [];

foreach ($certificats as $certificat) {
    $formationsCertifiees[$certificat['id_form']] = true;
}

$totalFormationsCertifiees = count($formationsCertifiees);

$dernierCertificat = $certificats[0] ?? null;

$iaContext = [
    'page' => 'certificats',
    'nombre_certificats' => $totalCertificats,
    'formations_certifiees' => $totalFormationsCertifiees,
    'dernier_certificat' => $dernierCertificat['titre_form'] ?? null
];

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes certificats | TOUN</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link rel="stylesheet" href="style.css">

    <style>

        .certificat-page {
            padding: 28px 30px;
        }

        .page-header {
            margin-bottom: 18px;
        }

        .page-header h1 {
            margin: 0 0 5px;
            color: #18213f;
            font-size: 24px;
            font-weight: 800;
        }

        .page-header p {
            margin: 0;
            color: #7c8da1;
            font-size: 10px;
        }

        .certificat-hero {
            min-height: 150px;
            margin-bottom: 18px;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            background: linear-gradient(135deg, #18213f, #087db7, #20a1b8);
        }

        .certificat-hero-content {
            max-width: 650px;
            position: relative;
            z-index: 2;
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
        }

        .certificat-hero h2 {
            margin: 0 0 6px;
            color: #fff;
            font-size: 20px;
            font-weight: 800;
        }

        .certificat-hero p {
            margin: 0;
            color: rgba(255,255,255,.80);
            font-size: 9px;
            line-height: 1.7;
        }

        .hero-icon {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,.10);
            color: #62e1e5;
            font-size: 42px;
            box-shadow: 0 0 0 12px rgba(255,255,255,.04);
        }

        .certificat-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 11px;
            margin-bottom: 22px;
        }

        .certificat-stat {
            min-height: 88px;
            padding: 14px;
            border: 1px solid #e2ebf1;
            border-radius: 14px;
            background: #fff;
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
            background: #eaf8fb;
            color: #087db7;
            font-size: 13px;
        }

        .stat-label {
            color: #8291a3;
            font-size: 8px;
        }

        .stat-number {
            margin-top: 2px;
            color: #18213f;
            font-size: 19px;
            font-weight: 800;
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 11px;
        }

        .section-heading h2 {
            margin: 0;
            color: #18213f;
            font-size: 14px;
            font-weight: 700;
        }

        .section-heading span {
            color: #8291a3;
            font-size: 8px;
        }

        .certificats-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px;
        }

        .certificat-card {
            position: relative;
            display: grid;
            grid-template-columns: 115px minmax(0, 1fr);
            gap: 15px;
            padding: 12px;
            overflow: hidden;
            border: 1px solid #dce9f0;
            border-radius: 15px;
            background: #fff;
            transition: .2s ease;
        }

        .certificat-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #18213f, #087db7, #20a1b8);
        }

        .certificat-card:hover {
            transform: translateY(-2px);
            border-color: #20a1b8;
            box-shadow: 0 10px 25px rgba(24,33,63,.08);
        }

        .certificat-image {
            width: 115px;
            height: 125px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: linear-gradient(135deg, #18213f, #20a1b8);
            color: #fff;
        }

        .certificat-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .certificat-image i {
            font-size: 30px;
        }

        .certificat-content {
            min-width: 0;
            padding: 2px 3px;
        }

        .certificat-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 7px;
            margin-bottom: 7px;
            border-radius: 7px;
            background: #eaf8fb;
            color: #087db7;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .certificat-content h3 {
            margin: 0 0 5px;
            color: #18213f;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.4;
        }

        .certificat-subtitle {
            margin-bottom: 10px;
            color: #20a1b8;
            font-size: 8px;
            font-weight: 600;
        }

        .certificat-info {
            display: grid;
            gap: 5px;
            margin-bottom: 11px;
        }

        .certificat-info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #8190a2;
            font-size: 7.5px;
        }

        .certificat-info-item i {
            width: 13px;
            color: #20a1b8;
            font-size: 8px;
        }

        .certificat-number {
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .certificat-actions {
            display: flex;
            gap: 7px;
        }

        .certificat-btn {
            min-height: 31px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 7px 11px;
            border-radius: 8px;
            background: linear-gradient(135deg, #18213f, #20a1b8);
            color: #fff;
            font-size: 7.5px;
            font-weight: 700;
        }

        .certificat-btn.secondary {
            background: #eaf8fb;
            color: #087db7;
        }

        .certificats-empty {
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border: 1px solid #dfeaf0;
            border-radius: 15px;
            background: #fff;
            text-align: center;
        }

        .empty-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 17px;
            background: #eaf8fb;
            color: #20a1b8;
            font-size: 22px;
        }

        .certificats-empty h3 {
            margin: 0 0 6px;
            color: #18213f;
            font-size: 14px;
        }

        .certificats-empty p {
            max-width: 430px;
            margin: 0 auto 15px;
            color: #8291a3;
            font-size: 9px;
            line-height: 1.7;
        }

        .catalogue-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 13px;
            border-radius: 8px;
            background: linear-gradient(135deg, #18213f, #20a1b8);
            color: #fff;
            font-size: 8px;
            font-weight: 700;
        }

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
            cursor: pointer;
            box-shadow: 0 15px 30px rgba(24,33,63,.22);
            transition: .25s ease;
        }

        .toun-ai-button i {
            color: #51dce3;
            font-size: 19px;
        }

        .toun-ai-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 35px rgba(24,33,63,.28);
        }

        @media (max-width: 1050px) {
            .certificats-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .certificat-page {
                padding: 22px 17px;
            }

            .certificat-stats {
                grid-template-columns: 1fr;
            }

            .certificat-hero {
                padding: 22px;
            }

            .hero-icon {
                width: 75px;
                height: 75px;
                font-size: 30px;
            }
        }

        @media (max-width: 600px) {
            .certificat-card {
                grid-template-columns: 1fr;
            }

            .certificat-image {
                width: 100%;
                height: 145px;
            }

            .certificat-actions {
                flex-wrap: wrap;
            }

            .certificat-btn {
                flex: 1;
            }

            .certificat-hero {
                min-height: 175px;
            }

            .certificat-hero h2 {
                font-size: 17px;
            }

            .hero-icon {
                position: absolute;
                right: 15px;
                bottom: 15px;
                width: 60px;
                height: 60px;
                font-size: 24px;
            }

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

                <a href="rendez-vous.php" class="top-action">
                    <i class="fa-regular fa-calendar-check"></i>
                    <span>Mes rendez-vous</span>
                </a>

                <a
                    href="notifications.php"
                    class="notification"
                    aria-label="Notifications"
                >
                    <i class="fa-regular fa-bell"></i>
                    <span></span>
                </a>

                <a href="profil.php" class="profile">

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
                        <strong><?= e($nomAffiche) ?></strong>
                        <small>Apprenant</small>
                    </div>

                </a>

            </div>

        </header>

        <section class="content certificat-page">

            <div class="page-header">

                <h1>Mes certificats</h1>

                <p>
                    Retrouvez ici vos certificats obtenus après la réussite
                    de vos formations.
                </p>

            </div>

            <section class="certificat-hero">

                <div class="certificat-hero-content">

                    <span class="hero-tag">
                        <i class="fa-solid fa-award"></i>
                        Certification TOUN
                    </span>

                    <h2>
                        Valorisez vos compétences.
                    </h2>

                    <p>
                        Vos certificats obtenus sont regroupés dans cet espace
                        et peuvent être consultés à tout moment.
                    </p>

                </div>

                <div class="hero-icon">
                    <i class="fa-solid fa-certificate"></i>
                </div>

            </section>

            <section class="certificat-stats">

                <div class="certificat-stat">

                    <div class="stat-top">

                        <div class="stat-icon">
                            <i class="fa-solid fa-award"></i>
                        </div>

                        <div>

                            <div class="stat-label">
                                Certificats obtenus
                            </div>

                            <div class="stat-number">
                                <?= $totalCertificats ?>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="certificat-stat">

                    <div class="stat-top">

                        <div class="stat-icon">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>

                        <div>

                            <div class="stat-label">
                                Formations certifiées
                            </div>

                            <div class="stat-number">
                                <?= $totalFormationsCertifiees ?>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="certificat-stat">

                    <div class="stat-top">

                        <div class="stat-icon">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>

                        <div>

                            <div class="stat-label">
                                Dernier certificat
                            </div>

                            <div class="stat-number">
                                <?= $dernierCertificat
                                    ? formatDate($dernierCertificat['date_obtention'])
                                    : '—'
                                ?>
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <div class="section-heading">

                <h2>Mes certificats</h2>

                <span>
                    <?= $totalCertificats ?>
                    certificat<?= $totalCertificats > 1 ? 's' : '' ?>
                </span>

            </div>

            <?php if (!empty($certificats)): ?>

                <div class="certificats-list">

                    <?php foreach ($certificats as $certificat): ?>

                        <?php
                        $lienCertificat = getCertificateLink(
                            $certificat['lien_certificat']
                        );

                        $imageUrl = getFormationImage(
                            $certificat['img_form']
                        );
                        ?>

                        <article class="certificat-card">

                            <div class="certificat-image">

                                <?php if ($imageUrl !== ''): ?>

                                    <img
                                        src="<?= e($imageUrl) ?>"
                                        alt="<?= e($certificat['titre_form']) ?>"
                                    >

                                <?php else: ?>

                                    <i class="fa-solid fa-certificate"></i>

                                <?php endif; ?>

                            </div>

                            <div class="certificat-content">

                                <span class="certificat-label">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Certificat obtenu
                                </span>

                                <h3>
                                    <?= e($certificat['titre_form']) ?>
                                </h3>

                                <div class="certificat-subtitle">
                                    Certification de réussite TOUN
                                </div>

                                <div class="certificat-info">

                                    <div class="certificat-info-item">
                                        <i class="fa-regular fa-calendar"></i>

                                        <span>
                                            Obtenu le
                                            <?= e(
                                                formatDate(
                                                    $certificat['date_obtention']
                                                )
                                            ) ?>
                                        </span>
                                    </div>

                                    <div class="certificat-info-item">

                                        <i class="fa-solid fa-hashtag"></i>

                                        <span class="certificat-number">
                                            <?= e(
                                                $certificat['numero_certificat']
                                            ) ?>
                                        </span>

                                    </div>

                                    <?php if (!empty($certificat['niveau_form'])): ?>

                                        <div class="certificat-info-item">

                                            <i class="fa-solid fa-signal"></i>

                                            <span>
                                                Niveau :
                                                <?= e(
                                                    $certificat['niveau_form']
                                                ) ?>
                                            </span>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="certificat-actions">

                                    <?php if ($lienCertificat !== ''): ?>

                                        <a
                                            href="<?= e($lienCertificat) ?>"
                                            target="_blank"
                                            class="certificat-btn"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            Voir
                                        </a>

                                        <a
                                            href="<?= e($lienCertificat) ?>"
                                            download
                                            class="certificat-btn secondary"
                                        >
                                            <i class="fa-solid fa-download"></i>
                                            Télécharger
                                        </a>

                                    <?php else: ?>

                                        <span class="certificat-btn secondary">
                                            <i class="fa-solid fa-file-circle-xmark"></i>
                                            Fichier indisponible
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="certificats-empty">

                    <div>

                        <div class="empty-icon">
                            <i class="fa-solid fa-certificate"></i>
                        </div>

                        <h3>
                            Aucun certificat obtenu pour le moment
                        </h3>

                        <p>
                            Terminez vos formations et réussissez les
                            évaluations nécessaires pour obtenir vos
                            certificats TOUN.
                        </p>

                        <a
                            href="../formation.php"
                            class="catalogue-btn"
                        >
                            <i class="fa-solid fa-book-open"></i>
                            Découvrir les formations
                        </a>

                    </div>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<button
    type="button"
    class="toun-ai-button"
    id="tounAiButton"
    aria-label="Ouvrir TOUN AI"
>
    <i class="fa-solid fa-wand-magic-sparkles"></i>
    <span>TOUN AI</span>
</button>

<script>

const mobileMenu = document.getElementById('mobileMenu');
const sidebar = document.getElementById('sidebar');

if (mobileMenu && sidebar) {
    mobileMenu.addEventListener('click', function () {
        sidebar.classList.toggle('open');
    });
}

const tounAiButton = document.getElementById('tounAiButton');

if (tounAiButton) {
    tounAiButton.addEventListener('click', function () {
        window.location.href = 'ia.php';
    });
}

const currentPage = window.location.pathname.split('/').pop();

document.querySelectorAll('.menu-link').forEach(function (link) {

    const href = link.getAttribute('href') || '';

    const linkPage = href
        .split('/')
        .pop()
        .split('?')[0];

    if (linkPage === currentPage) {

        document
            .querySelectorAll('.menu-link')
            .forEach(function (item) {
                item.classList.remove('active');
            });

        link.classList.add('active');
    }
});

</script>

</body>
</html>

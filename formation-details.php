<?php
session_start();

require_once "config/connexion.php";
require_once __DIR__ . "/paiement_config.php";

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formationImage(?string $image): string
{
    $image = trim((string)$image);

    if ($image === '') {
        return 'images/formation-hero.jpg';
    }

    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    $name = basename(str_replace('\\', '/', $image));

    $paths = [
        'uploads/formations/' . $name,
        'uploads/' . $name,
        'images/' . $name
    ];

    foreach ($paths as $path) {
        if (is_file(__DIR__ . '/' . $path)) {
            return $path;
        }
    }

    return 'images/formation-hero.jpg';
}

function formateurImage(?string $photo): string
{
    $photo = trim((string)$photo);

    if ($photo === '') {
        return '';
    }

    $photo = str_replace('\\', '/', $photo);

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $name = basename($photo);

    $candidats = [
        [__DIR__ . '/uploads/photos/' . $name, 'uploads/photos/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/photos/' . $name, '../uploads/photos/' . rawurlencode($name)],
        [__DIR__ . '/uploads/profils/' . $name, 'uploads/profils/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/profils/' . $name, '../uploads/profils/' . rawurlencode($name)],
        [__DIR__ . '/uploads/' . $name, 'uploads/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/' . $name, '../uploads/' . rawurlencode($name)],
        [__DIR__ . '/images/profils/' . $name, 'images/profils/' . rawurlencode($name)],
        [__DIR__ . '/../images/profils/' . $name, '../images/profils/' . rawurlencode($name)],
        [__DIR__ . '/assets/uploads/' . $name, 'assets/uploads/' . rawurlencode($name)],
        [__DIR__ . '/../assets/uploads/' . $name, '../assets/uploads/' . rawurlencode($name)]
    ];

    foreach ($candidats as [$file, $url]) {
        if (is_file($file)) {
            return $url;
        }
    }

    return '';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: formation.php');
    exit;
}

$stmt = $con->prepare("
    SELECT
        f.id_form,
        f.titre_form,
        f.descrip_form,
        f.img_form,
        f.niveau_form,
        f.prix_form,
        f.duree_form,
        f.statut_form,
        f.date_creation_form,
        c.nom_cat,
        fo.id_formateur,
        u.nom_user,
        u.prenom_user,
        u.photo_user,
        u.email_user
    FROM t_formations f
    LEFT JOIN t_categories c
        ON c.id_cat = f.id_cat
    LEFT JOIN t_formateurs fo
        ON fo.id_formateur = f.id_formateur
    LEFT JOIN t_utilisateurs u
        ON u.id_user = fo.id_user
    WHERE f.id_form = :id
      AND f.statut_form = 'publie'
    LIMIT 1
");

$stmt->execute([':id' => $id]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formation introuvable | TOUN</title>
        <style>
            body {
                margin: 0;
                background: #f3f8fb;
                color: #17345b;
                font-family: Arial, sans-serif;
                display: grid;
                place-items: center;
                min-height: 100vh;
            }

            .box {
                width: min(520px, 90%);
                padding: 40px;
                text-align: center;
                background: #fff;
                border: 1px solid #dde8ee;
                border-radius: 18px;
                box-shadow: 0 15px 40px rgba(16, 41, 77, 0.08);
            }

            a {
                display: inline-flex;
                margin-top: 18px;
                padding: 11px 18px;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #078fd2, #20a1b8);
                text-decoration: none;
                font-weight: 700;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Formation introuvable</h2>
            <p>Cette formation n'est plus disponible ou n'existe pas.</p>
            <a href="formation.php">Retour aux formations</a>
        </div>

    </body>
</html>
<?php
    exit;
}

$formationAcquise = isset($_SESSION['id_user'])
    && tounFormationAcquise($con, (int) $_SESSION['id_user'], (int) $formation['id_form']);

 
$videoIntro = '';

try {
    $videoStmt = $con->prepare("
        SELECT video_intro_form
        FROM t_formations
        WHERE id_form = :id
        LIMIT 1
    ");
    $videoStmt->execute([':id' => $id]);
    $videoIntro = (string)$videoStmt->fetchColumn();
} catch (Throwable $e) {
    $videoIntro = '';
}

function mediaPath(?string $path): string
{
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $name = basename(str_replace('\\', '/', $path));

    $candidats = [
        [__DIR__ . '/uploads/lecons/' . $name, 'uploads/lecons/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/lecons/' . $name, '../uploads/lecons/' . rawurlencode($name)],
        [__DIR__ . '/uploads/formations/videos/' . $name, 'uploads/formations/videos/' . rawurlencode($name)],
        [__DIR__ . '/uploads/formations/' . $name, 'uploads/formations/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/formations/' . $name, '../uploads/formations/' . rawurlencode($name)],
        [__DIR__ . '/uploads/' . $name, 'uploads/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/' . $name, '../uploads/' . rawurlencode($name)]
    ];

    foreach ($candidats as [$file, $url]) {
        if (is_file($file)) {
            return $url;
        }
    }

    return '';
}

 
$modules = [];
$moduleTable = null;
$lessonTable = null;

foreach (['t_modules', 'modules'] as $table) {
    try {
        $test = $con->query("SELECT 1 FROM {$table} LIMIT 1");
        if ($test !== false) {
            $moduleTable = $table;
            break;
        }
    } catch (Throwable $e) {
    }
}

if ($moduleTable) {
    try {
        $columns = $con->query("SHOW COLUMNS FROM {$moduleTable}")->fetchAll(PDO::FETCH_COLUMN);

        $moduleId = in_array('id_module', $columns, true) ? 'id_module' : (in_array('id_mod', $columns, true) ? 'id_mod' : null);
        $moduleTitle = in_array('titre_module', $columns, true) ? 'titre_module' : (in_array('nom_module', $columns, true) ? 'nom_module' : null);
        $moduleFormation = in_array('id_form', $columns, true) ? 'id_form' : null;

        if ($moduleId && $moduleTitle && $moduleFormation) {
            $order = in_array('ordre_module', $columns, true) ? 'ordre_module ASC' : $moduleId . ' ASC';

            $moduleStmt = $con->prepare("
                SELECT {$moduleId} AS module_id, {$moduleTitle} AS module_title
                FROM {$moduleTable}
                WHERE {$moduleFormation} = :id
                ORDER BY {$order}
            ");
            $moduleStmt->execute([':id' => $id]);
            $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $modules = [];
    }
}

foreach (['t_lecons', 'lecons'] as $table) {
    try {
        $test = $con->query("SELECT 1 FROM {$table} LIMIT 1");
        if ($test !== false) {
            $lessonTable = $table;
            break;
        }
    } catch (Throwable $e) {
    }
}

if ($modules && $lessonTable) {
    try {
        $columns = $con->query("SHOW COLUMNS FROM {$lessonTable}")->fetchAll(PDO::FETCH_COLUMN);

        $lessonId = in_array('id_lecon', $columns, true) ? 'id_lecon' : (in_array('id_lesson', $columns, true) ? 'id_lesson' : null);
        $lessonTitle = in_array('titre_lecon', $columns, true) ? 'titre_lecon' : (in_array('nom_lecon', $columns, true) ? 'nom_lecon' : null);
        $lessonModule = in_array('id_module', $columns, true) ? 'id_module' : null;

        $lessonDuration = null;
        foreach (['duree_lecon', 'duree_lesson', 'duree'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $lessonDuration = $candidate;
                break;
            }
        }

        $lessonType = null;
        foreach (['type_lecon', 'type_contenu', 'type_lesson'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $lessonType = $candidate;
                break;
            }
        }

        $lessonVideo = null;
        foreach (['video_lecon', 'video_lesson', 'url_video'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $lessonVideo = $candidate;
                break;
            }
        }

        $lessonPdf = null;
        foreach (['pdf_lecon', 'fichier_pdf', 'document_lecon', 'pdf_lesson'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $lessonPdf = $candidate;
                break;
            }
        }

        if ($lessonId && $lessonTitle && $lessonModule) {
            $select = [
                $lessonId . ' AS lesson_id',
                $lessonTitle . ' AS lesson_title',
                $lessonModule . ' AS module_id'
            ];

            if ($lessonDuration) {
                $select[] = $lessonDuration . ' AS lesson_duration';
            }

            if ($lessonType) {
                $select[] = $lessonType . ' AS lesson_type';
            }

            if ($lessonVideo) {
                $select[] = $lessonVideo . ' AS lesson_video';
            }

            if ($lessonPdf) {
                $select[] = $lessonPdf . ' AS lesson_pdf';
            }

            $order = in_array('ordre_lecon', $columns, true) ? 'ordre_lecon ASC' : $lessonId . ' ASC';

            $lessonStmt = $con->prepare("
                SELECT " . implode(', ', $select) . "
                FROM {$lessonTable}
                WHERE {$lessonModule} = :module_id
                ORDER BY {$order}
            ");

            foreach ($modules as &$module) {
                $lessonStmt->execute([':module_id' => $module['module_id']]);
                $module['lessons'] = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($module);
        }
    } catch (Throwable $e) {
        foreach ($modules as &$module) {
            $module['lessons'] = [];
        }
        unset($module);
    }
}

$formateurNom = trim(
    ($formation['prenom_user'] ?? '') . ' ' .
    ($formation['nom_user'] ?? '')
);

$formateurPhoto = formateurImage($formation['photo_user'] ?? '');

$parts = $formateurNom !== '' ? preg_split('/\s+/', $formateurNom) : [];
$initiales = '';

if ($parts) {
    $initiales = strtoupper(
        substr($parts[0] ?? '', 0, 1) .
        substr($parts[count($parts) - 1] ?? '', 0, 1)
    );
}

$duree = (int)$formation['duree_form'];
$dureeAffichee = $duree >= 60
    ? floor($duree / 60) . ' h' . (($duree % 60) ? ' ' . ($duree % 60) . ' min' : '')
    : $duree . ' min';

$moduleCount = count($modules);
$lessonCount = 0;

foreach ($modules as $module) {
    $lessonCount += count($module['lessons'] ?? []);
}

$image = formationImage($formation['img_form']);
$videoUrl = $videoIntro !== '' ? mediaPath($videoIntro) : '';
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            document.documentElement.classList.add('js');
        </script>
        <title><?= e($formation['titre_form']) ?> | TOUN</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap");

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                padding-top: 76px;
                background: #f2f8fb;
                color: #17345b;
                font-family: "Poppins", Arial, sans-serif;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            button {
                font: inherit;
            }

            .detail-hero {
                position: relative;
                overflow: hidden;
                background: linear-gradient(120deg, #082f66 0%, #0b5a91 52%, #10afc1 100%);
                color: #fff;
            }

            .detail-hero::before {
                content: "";
                position: absolute;
                width: 440px;
                height: 440px;
                left: -220px;
                top: -260px;
                border: 60px solid rgba(32, 161, 184, 0.12);
                border-radius: 50%;
            }

            .detail-hero::after {
                content: "";
                position: absolute;
                width: 330px;
                height: 330px;
                right: -170px;
                bottom: -245px;
                border: 45px solid rgba(255, 255, 255, 0.09);
                border-radius: 50%;
            }

            .detail-hero-inner {
                position: relative;
                z-index: 2;
                width: min(1180px, 90%);
                margin: auto;
                padding: 42px 0 44px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) 400px;
                gap: 48px;
                align-items: center;
            }

            .breadcrumb {
                display: flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 14px;
                color: rgba(255, 255, 255, 0.65);
                font-size: 10.5px;
            }

            .breadcrumb a:hover {
                color: #fff;
            }

            .breadcrumb i {
                font-size: 9.5px;
            }

            .detail-category {
                display: inline-flex;
                align-items: center;
                padding: 6px 9px;
                margin-bottom: 12px;
                border: 1px solid rgba(255, 255, 255, 0.22);
                border-radius: 7px;
                color: #ddfcfd;
                background: rgba(255, 255, 255, 0.09);
                font-size: 10px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.7px;
            }

            .detail-copy h1 {
                max-width: 690px;
                margin: 0 0 13px;
                color: #fff;
                font-size: 35px;
                line-height: 1.12;
                letter-spacing: -0.7px;
            }

            .detail-copy > p {
                max-width: 650px;
                margin: 0 0 18px;
                color: rgba(255, 255, 255, 0.78);
                font-size: 11.5px;
                line-height: 1.75;
            }

            .detail-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .detail-meta span {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 7px 9px;
                border: 1px solid rgba(255, 255, 255, 0.16);
                border-radius: 7px;
                color: rgba(255, 255, 255, 0.82);
                background: rgba(255, 255, 255, 0.07);
                font-size: 10px;
            }

            .detail-meta i {
                color: #67e0e6;
            }

            .hero-cover {
                position: relative;
                padding: 7px;
                border: 1px solid rgba(255, 255, 255, 0.22);
                border-radius: 17px;
                background: rgba(255, 255, 255, 0.08);
                box-shadow: 0 22px 45px rgba(3, 27, 57, 0.23);
            }

            .hero-cover img,
            .hero-cover video {
                width: 100%;
                height: 245px;
                display: block;
                object-fit: cover;
                border-radius: 12px;
            }

            .hero-cover video {
                object-fit: contain;
                background: #000;
            }

            .detail-main {
                width: min(1180px, 90%);
                margin: 0 auto;
                padding: 32px 0 50px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) 315px;
                gap: 24px;
                align-items: start;
            }

            .content-card {
                border: 1px solid #dde8ee;
                border-radius: 15px;
                background: #fff;
                box-shadow: 0 8px 24px rgba(20, 54, 88, 0.055);
            }

            .section-block {
                padding: 23px;
            }

            .section-block + .section-block {
                border-top: 1px solid #edf2f5;
            }

            .section-block h2 {
                margin: 0 0 13px;
                color: #17345b;
                font-size: 17px;
            }

            .description {
                color: #64788d;
                font-size: 11px;
                line-height: 1.9;
                white-space: pre-line;
            }

            .stats-row {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-top: 19px;
            }

            .small-stat {
                padding: 13px;
                border: 1px solid #dcebf1;
                border-radius: 10px;
                background: #f6fbfc;
            }

            .small-stat i {
                color: #20a1b8;
                font-size: 13px;
            }

            .small-stat strong {
                display: block;
                margin-top: 7px;
                color: #17345b;
                font-size: 14px;
            }

            .small-stat span {
                color: #8493a3;
                font-size: 10px;
            }

            .trainer-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px;
                border: 1px solid #dde8ee;
                border-radius: 11px;
                background: #f8fbfc;
            }

            .trainer-photo {
                width: 48px;
                height: 48px;
                flex-shrink: 0;
                overflow: hidden;
                display: grid;
                place-items: center;
                border: 2px solid #e0edf2;
                border-radius: 50%;
                background: linear-gradient(135deg, #e8f4f7, #e8eef7);
                color: #078fd2;
                font-size: 12.5px;
                font-weight: 800;
            }

            .trainer-photo img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .trainer-label {
                display: block;
                color: #9aa6b3;
                font-size: 10px;
            }

            .trainer-name {
                display: block;
                margin-top: 3px;
                color: #17345b;
                font-size: 11.5px;
                font-weight: 700;
            }

            .trainer-email {
                display: block;
                margin-top: 2px;
                color: #8493a3;
                font-size: 10px;
            }

            .accordion {
                border: 1px solid #e0ebf0;
                border-radius: 10px;
                overflow: hidden;
            }

            .module {
                border-bottom: 1px solid #e7eef2;
            }

            .module:last-child {
                border-bottom: 0;
            }

            .module-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 13px 14px;
                background: #f8fbfc;
            }

            .module-title {
                display: flex;
                align-items: center;
                gap: 9px;
                min-width: 0;
            }

            .module-number {
                width: 27px;
                height: 27px;
                display: grid;
                place-items: center;
                flex-shrink: 0;
                border-radius: 8px;
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #078fd2);
                font-size: 10.5px;
                font-weight: 800;
            }

            .module-title strong {
                color: #17345b;
                font-size: 11px;
            }

            .module-count {
                color: #8796a5;
                font-size: 10px;
                white-space: nowrap;
            }

            .lessons {
                padding: 5px 12px 9px 48px;
            }

            .lesson {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 8px 0;
                border-bottom: 1px solid #f0f4f6;
            }

            .lesson:last-child {
                border-bottom: 0;
            }

            .lesson i {
                width: 25px;
                height: 25px;
                display: grid;
                place-items: center;
                flex-shrink: 0;
                border-radius: 7px;
                color: #078fd2;
                background: #edf4fb;
                font-size: 10.5px;
            }

            .lesson-info {
                min-width: 0;
                flex: 1;
            }

            .lesson-title {
                display: block;
                overflow: hidden;
                color: #526a81;
                font-size: 10.5px;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .lesson-type {
                display: block;
                margin-top: 2px;
                color: #a0acb8;
                font-size: 10px;
            }

            .lesson-duration {
                color: #8c9aa8;
                font-size: 10px;
                white-space: nowrap;
            }

            .empty-program {
                padding: 20px;
                text-align: center;
                border: 1px dashed #c9dce5;
                border-radius: 10px;
                background: #f9fcfd;
            }

            .empty-program i {
                color: #20a1b8;
                font-size: 22px;
            }

            .empty-program strong {
                display: block;
                margin-top: 8px;
                color: #17345b;
                font-size: 11px;
            }

            .empty-program span {
                display: block;
                margin-top: 4px;
                color: #8796a5;
                font-size: 10px;
            }

            .purchase-card {
                position: sticky;
                top: 96px;
                overflow: hidden;
                border: 1px solid #d7e6ec;
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 12px 32px rgba(20, 54, 88, 0.09);
            }

            .purchase-top {
                padding: 19px 19px 15px;
            }

            .purchase-price {
                color: #0b70b5;
                font-size: 25px;
                font-weight: 800;
                letter-spacing: -0.5px;
            }

            .purchase-label {
                margin-top: 3px;
                color: #8a99a7;
                font-size: 10px;
            }

            .buy-button {
                width: 100%;
                min-height: 43px;
                margin-top: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #078fd2);
                font-size: 11px;
                font-weight: 800;
                box-shadow: 0 9px 20px rgba(32, 161, 184, 0.18);
                transition: 0.2s ease;
            }

            .buy-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 25px rgba(32, 161, 184, 0.25);
            }

            .purchase-note {
                margin: 9px 0 0;
                color: #9aa6b3;
                font-size: 10px;
                text-align: center;
            }

            .purchase-list {
                padding: 0 19px 18px;
                border-top: 1px solid #edf2f5;
            }

            .purchase-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 9px 0;
                color: #62768a;
                font-size: 10px;
            }

            .purchase-item i {
                width: 23px;
                height: 23px;
                display: grid;
                place-items: center;
                border-radius: 7px;
                color: #20a1b8;
                background: #eaf8fa;
                font-size: 10.5px;
            }

            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 15px;
                color: #078fd2;
                font-size: 10.5px;
                font-weight: 700;
            }

            .back-link:hover {
                color: #20a1b8;
            }

            @media (max-width: 1050px) {
                .nav-link {
                    padding: 8px 8px;
                    font-size: 12px;
                }

                .detail-hero-inner {
                    grid-template-columns: minmax(0, 1fr) 340px;
                    gap: 28px;
                }

                .detail-main {
                    grid-template-columns: minmax(0, 1fr) 285px;
                }
            }

            @media (max-width: 850px) {
                .detail-hero-inner,
                .detail-main {
                    grid-template-columns: 1fr;
                }

                .hero-cover {
                    max-width: 540px;
                }

                .purchase-card {
                    position: static;
                }
            }

            @media (max-width: 700px) {
                .detail-hero-inner {
                    padding: 30px 0;
                }

                .detail-copy h1 {
                    font-size: 28px;
                }

                .detail-main {
                    padding-top: 22px;
                }

                .stats-row {
                    grid-template-columns: 1fr;
                }
            }

            .site-header {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 80px;
                display: flex;
                align-items: center;
                background: rgba(255, 255, 255, 0.97);
                backdrop-filter: blur(14px);
                z-index: 1000;
                transition: box-shadow 0.3s ease;
                animation: tounSlideDown 0.7s cubic-bezier(0.22, 0.8, 0.3, 1) both;
            }

            .site-header.scrolled {
                box-shadow: 0 8px 26px rgba(16, 55, 85, 0.1);
            }

            .site-header .nav-wrap {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                gap: 24px;
                padding: 0 3.6%;
            }

            .site-header .brand img {
                width: 150px;
                height: auto;
            }

            .site-header .main-nav {
                flex: 1;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .site-header .nav-links {
                height: 100%;
                display: flex;
                align-items: center;
                margin: 0 auto;
            }

            .site-header .nav-link {
                position: relative;
                height: 100%;
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 0 17px;
                color: #173f70;
                background: transparent;
                border: 0;
                font-family: "Poppins", sans-serif;
                font-size: 15px;
                font-weight: 500;
                white-space: nowrap;
                cursor: pointer;
                transition: color 0.25s ease;
            }

            .site-header .nav-link::after {
                content: "";
                position: absolute;
                left: 17px;
                right: 17px;
                bottom: 0;
                height: 3px;
                border-radius: 3px 3px 0 0;
                background: linear-gradient(90deg, #0c84b8, #20a1b8);
                transform: scaleX(0);
                transition: transform 0.3s cubic-bezier(0.22, 0.8, 0.3, 1);
            }

            .site-header .nav-link:hover,
            .site-header .nav-link.active {
                color: #078fd2;
            }

            .site-header .nav-link:hover::after,
            .site-header .nav-link.active::after {
                transform: scaleX(1);
            }

            .site-header .nav-dropdown {
                position: relative;
                height: 100%;
            }

            .site-header .nav-dropdown-btn i {
                font-size: 11.5px;
                transition: transform 0.3s ease;
            }

            .site-header .nav-dropdown:hover .nav-dropdown-btn i,
            .site-header .nav-dropdown.open .nav-dropdown-btn i {
                transform: rotate(180deg);
            }

            .site-header .dropdown-panel {
                position: absolute;
                top: calc(100% - 8px);
                left: 50%;
                width: 270px;
                padding: 8px;
                background: #fff;
                border: 1px solid #e5edf6;
                border-radius: 16px;
                box-shadow: 0 18px 45px rgba(21, 56, 91, 0.14);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: translate(-50%, 10px);
                transition:
                    opacity 0.25s ease,
                    transform 0.25s ease,
                    visibility 0.25s;
            }

            .site-header .nav-dropdown:hover .dropdown-panel,
            .site-header .nav-dropdown:focus-within .dropdown-panel,
            .site-header .nav-dropdown.open .dropdown-panel {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                transform: translate(-50%, 0);
            }

            .site-header .dropdown-panel > a {
                display: grid;
                grid-template-columns: 40px 1fr 16px;
                align-items: center;
                gap: 12px;
                padding: 12px;
                border-radius: 12px;
                color: #173f70;
                transition: background 0.25s ease;
            }

            .site-header .dropdown-panel > a:hover {
                background: #f1fbfe;
                color: #078fd2;
            }

            .site-header .dropdown-panel b {
                display: block;
                font-size: 13.5px;
            }

            .site-header .dropdown-panel small {
                display: block;
                margin-top: 2px;
                color: #8190a5;
                font-size: 12px;
                font-weight: 400;
            }

            .site-header .dropdown-panel > a > i {
                font-size: 12px;
            }

            .site-header .drop-icon {
                width: 40px;
                height: 40px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                color: #20a1b8;
                background: #e7f7fa;
            }

            .site-header .nav-actions {
                display: flex;
                gap: 12px;
            }

            .site-header .btn-login,
            .site-header .btn-register {
                height: 46px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 24px;
                border-radius: 99px;
                font-family: "Poppins", sans-serif;
                font-size: 14px;
                font-weight: 600;
                transition:
                    transform 0.25s ease,
                    box-shadow 0.25s ease,
                    background 0.25s ease;
            }

            .site-header .btn-login {
                color: #173f70;
                background: #fff;
                border: 1px solid #dfe8f0;
            }

            .site-header .btn-login:hover {
                background: #f2f8fc;
                transform: translateY(-2px);
            }

            .site-header .btn-register {
                color: #fff;
                background: linear-gradient(100deg, #0f3f78, #0980c2);
                box-shadow: 0 8px 20px rgba(9, 128, 194, 0.24);
                border: 0;
            }

            .site-header .btn-register:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 26px rgba(9, 128, 194, 0.34);
            }

            .site-header .menu-toggle {
                display: none;
                width: 42px;
                height: 42px;
                margin-left: auto;
                place-items: center;
                border-radius: 12px;
                color: #078fd2;
                background: #e7f7fa;
                border: 0;
                font-size: 18px;
                cursor: pointer;
            }

            body {
                padding-top: 80px;
            }

            @keyframes tounSlideDown {
                from {
                    transform: translateY(-100%);
                }
            }

            @media (max-width: 960px) {
                .site-header {
                    height: 70px;
                }

                body {
                    padding-top: 70px;
                }

                .site-header .menu-toggle {
                    display: grid;
                }

                .site-header .brand img {
                    width: 115px;
                }

                .site-header .main-nav {
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    height: auto;
                    flex-direction: column;
                    align-items: stretch;
                    gap: 0;
                    padding: 8px 4% 18px;
                    background: #fff;
                    border-bottom: 1px solid #e6eef5;
                    box-shadow: 0 18px 30px rgba(16, 55, 85, 0.1);
                    opacity: 0;
                    visibility: hidden;
                    transform: translateY(-10px);
                    transition:
                        opacity 0.3s ease,
                        transform 0.3s ease,
                        visibility 0.3s;
                }

                .site-header.mobile-open .main-nav {
                    opacity: 1;
                    visibility: visible;
                    transform: none;
                }

                .site-header .nav-links {
                    height: auto;
                    flex-direction: column;
                    align-items: stretch;
                    margin: 0;
                }

                .site-header .nav-link {
                    width: 100%;
                    height: auto;
                    justify-content: space-between;
                    padding: 13px 4px;
                }

                .site-header .nav-link::after {
                    display: none;
                }

                .site-header .nav-dropdown {
                    height: auto;
                }

                .site-header .dropdown-panel {
                    position: static;
                    width: 100%;
                    display: none;
                    padding: 0 0 6px;
                    border: 0;
                    box-shadow: none;
                    opacity: 1;
                    visibility: visible;
                    pointer-events: auto;
                    transform: none;
                }

                .site-header .nav-dropdown.open .dropdown-panel {
                    display: block;
                }

                .site-header .nav-actions {
                    margin-top: 10px;
                }

                .site-header .btn-login,
                .site-header .btn-register {
                    flex: 1;
                }
            }

            .footer {
                padding: 48px 0 20px;
                color: #9daabd;
                background: #102f5d;
                font-family: "Poppins", sans-serif;
            }

            .footer .container {
                width: 90%;
                max-width: 1180px;
                margin: 0 auto;
            }

            .footer-grid {
                display: grid;
                grid-template-columns: 1.4fr 1fr 1fr 0.9fr;
                gap: 40px;
            }

            .footer-logo {
                width: 140px;
                margin-bottom: 14px;
            }

            .footer-brand p {
                max-width: 380px;
                font-size: 13.5px;
                line-height: 1.75;
            }

            .footer h4 {
                margin-bottom: 16px;
                color: #fff;
                font-size: 15px;
                font-weight: 700;
            }

            .footer-grid > div > a,
            .footer-grid > div > span {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 11px;
                color: #9daabd;
                font-size: 13.5px;
            }

            .footer-grid > div > a {
                width: fit-content;
                transition:
                    color 0.2s ease,
                    transform 0.2s ease;
            }

            .footer-grid > div > a:hover {
                color: #fff;
                transform: translateX(4px);
            }

            .footer-grid i {
                width: 14px;
                color: #3dd8ef;
                font-size: 13px;
                text-align: center;
            }

            .footer-bottom {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                margin-top: 26px;
                padding-top: 20px;
                border-top: 1px solid #2d4870;
                color: #71839e;
                font-size: 13px;
            }

            .footer-bottom > div {
                display: flex;
                gap: 8px;
            }

            .footer-bottom a {
                color: #71839e;
                transition: color 0.2s ease;
            }

            .footer-bottom a:hover {
                color: #fff;
            }

            @media (max-width: 960px) {
                .footer-grid {
                    grid-template-columns: 1fr 1fr;
                    gap: 26px;
                }
            }

            @media (max-width: 600px) {
                .footer-grid {
                    grid-template-columns: 1fr;
                }

                .footer-bottom {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }

            .site-header a,
            .footer a {
                text-decoration: none;
            }

            .js .reveal,
            .js .reveal-left,
            .js .reveal-right,
            .js .reveal-zoom {
                opacity: 0;
                transition:
                    opacity 0.8s cubic-bezier(0.22, 0.8, 0.3, 1),
                    transform 0.8s cubic-bezier(0.22, 0.8, 0.3, 1);
            }

            .js .reveal {
                transform: translateY(30px);
            }

            .js .reveal-left {
                transform: translateX(-50px);
            }

            .js .reveal-right {
                transform: translateX(50px);
            }

            .js .reveal-zoom {
                transform: scale(0.94);
            }

            .js .reveal.visible,
            .js .reveal-left.visible,
            .js .reveal-right.visible,
            .js .reveal-zoom.visible {
                opacity: 1;
                transform: none;
            }

            .js .stagger > * {
                opacity: 0;
                transform: translateY(26px) scale(0.97);
                transition:
                    opacity 0.6s cubic-bezier(0.22, 0.8, 0.3, 1),
                    transform 0.6s cubic-bezier(0.22, 0.8, 0.3, 1);
            }

            .js .stagger.visible > * {
                opacity: 1;
                transform: none;
            }

            .js .stagger.done > * {
                transition:
                    transform 0.3s ease,
                    box-shadow 0.3s ease,
                    border-color 0.3s ease,
                    background-color 0.3s ease;
                transition-delay: 0s;
            }
        </style>
    </head>

    <body>

        <header class="site-header">
            <div class="container nav-wrap">
                <a href="index.php" class="brand"><img src="images/logo.png" alt="TOUN"></a>

                <nav class="main-nav" id="main-nav">
                    <div class="nav-links">
                        <a href="index.php" class="nav-link">Accueil</a>
                        <a href="formation.php" class="nav-link">Formation</a>

                        <div class="nav-dropdown">
                            <button class="nav-link nav-dropdown-btn" type="button" aria-expanded="false">Solutions <i class="fa-solid fa-chevron-down"></i></button>
                            <div class="dropdown-panel">
                                <a href="professionnels.php">
                                    <span class="drop-icon"><i class="fa-solid fa-user"></i></span>
                                    <span><b>Professionnels</b><small>Développez vos compétences</small></span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="entreprises.php">
                                    <span class="drop-icon"><i class="fa-solid fa-building"></i></span>
                                    <span><b>Entreprises</b><small>Formez vos équipes</small></span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>

                        <a href="tarif.php" class="nav-link">Tarifs</a>
                        <a href="a-propos.php" class="nav-link">À propos</a>
                        <a href="contact.php" class="nav-link">Contact</a>
                    </div>

                    <div class="nav-actions">
                        <a href="login.php" class="btn-login">Se connecter</a>
                        <a href="inscription.php" class="btn-register">S'inscrire</a>
                    </div>
                </nav>

                <button class="menu-toggle" type="button" aria-label="Menu" aria-controls="main-nav" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
            </div>
        </header>

        <section class="detail-hero">
            <div class="detail-hero-inner">

                <div class="detail-copy reveal-left">

                    <div class="breadcrumb">
                        <a href="formation.php">Formations</a>
                        <i class="fas fa-chevron-right"></i>
                        <span><?= e($formation['nom_cat'] ?: 'Formation') ?></span>
                    </div>

                    <div class="detail-category">
                        <?= e($formation['nom_cat'] ?: 'Formation professionnelle') ?>
                    </div>

                    <h1><?= e($formation['titre_form']) ?></h1>

                    <p>
                        <?= e($formation['descrip_form']) ?>
                    </p>

                    <div class="detail-meta">
                        <span>
                            <i class="far fa-clock"></i>
                            <?= e($dureeAffichee) ?>
                        </span>

                        <span>
                            <i class="fas fa-signal"></i>
                            <?= e($formation['niveau_form']) ?>
                        </span>

                        <span>
                            <i class="fas fa-book-open"></i>
                            <?= $moduleCount ?> module<?= $moduleCount > 1 ? 's' : '' ?>
                        </span>

                        <span>
                            <i class="fas fa-user-tie"></i>
                            Formateur
                        </span>
                    </div>

                </div>

                <div class="hero-cover reveal-right">
                    <?php if ($videoUrl !== ''): ?>
                        <video controls preload="metadata" poster="<?= e($image) ?>">
                            <source src="<?= e($videoUrl) ?>">
                            Votre navigateur ne prend pas en charge la lecture vidéo.
                        </video>
                    <?php else: ?>
                        <img src="<?= e($image) ?>" alt="<?= e($formation['titre_form']) ?>">
                    <?php endif; ?>
                </div>

            </div>
        </section>

        <main class="detail-main">

            <div>

                <a href="formation.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Retour au catalogue
                </a>

                <div class="content-card reveal">

                    <section class="section-block">
                        <h2>À propos de cette formation</h2>

                        <div class="description">
                            <?= e($formation['descrip_form']) ?>
                        </div>

                        <div class="stats-row">
                            <div class="small-stat">
                                <i class="fas fa-book-open"></i>
                                <strong><?= $moduleCount ?></strong>
                                <span>Modules</span>
                            </div>

                            <div class="small-stat">
                                <i class="fas fa-play-circle"></i>
                                <strong><?= $lessonCount ?></strong>
                                <span>Leçons disponibles</span>
                            </div>

                            <div class="small-stat">
                                <i class="fas fa-clock"></i>
                                <strong><?= e($dureeAffichee) ?></strong>
                                <span>Durée totale</span>
                            </div>
                        </div>
                    </section>

                    <section class="section-block">
                        <h2>Programme de la formation</h2>

                        <?php if (!$modules): ?>

                            <div class="empty-program">
                                <i class="fas fa-layer-group"></i>
                                <strong>Programme en cours de préparation</strong>
                                <span>
                                    Le contenu pédagogique détaillé sera bientôt disponible.
                                </span>
                            </div>

                        <?php else: ?>

                            <div class="accordion stagger">

                                <?php foreach ($modules as $index => $module): ?>
                                    <?php $lessons = $module['lessons'] ?? []; ?>

                                    <div class="module">

                                        <div class="module-head">
                                            <div class="module-title">
                                                <span class="module-number">
                                                    <?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?>
                                                </span>

                                                <strong>
                                                    <?= e($module['module_title']) ?>
                                                </strong>
                                            </div>

                                            <span class="module-count">
                                                <?= count($lessons) ?>
                                                leçon<?= count($lessons) > 1 ? 's' : '' ?>
                                            </span>
                                        </div>

                                        <?php if ($lessons): ?>

                                            <div class="lessons">

                                                <?php foreach ($lessons as $lesson): ?>

                                                    <?php
$type = strtolower((string) ($lesson['lesson_type'] ?? ''));
$icon = 'fa-file-lines';

if (str_contains($type, 'video')) {
    $icon = 'fa-circle-play';
} elseif (str_contains($type, 'pdf')) {
    $icon = 'fa-file-pdf';
}
?>

                                                    <div class="lesson">

                                                        <i class="fas <?= $icon ?>"></i>

                                                        <div class="lesson-info">
                                                            <span class="lesson-title">
                                                                <?= e($lesson['lesson_title']) ?>
                                                            </span>

                                                            <span class="lesson-type">
                                                                <?= e($lesson['lesson_type'] ?? 'Leçon') ?>
                                                            </span>
                                                        </div>

                                                        <?php if (!empty($lesson['lesson_duration'])): ?>
                                                            <span class="lesson-duration">
                                                                <?= e($lesson['lesson_duration']) ?>
                                                            </span>
                                                        <?php endif; ?>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>
                    </section>

                    <section class="section-block">

                        <h2>Votre formateur</h2>

                        <div class="trainer-card reveal">

                            <div class="trainer-photo">

                                <?php if ($formateurPhoto !== ''): ?>

                                    <img
                                        src="<?= e($formateurPhoto) ?>"
                                        alt="<?= e($formateurNom) ?>"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
                                    >

                                    <span style="display:none">
                                        <?= e($initiales ?: 'F') ?>
                                    </span>

                                <?php else: ?>

                                    <span><?= e($initiales ?: 'F') ?></span>

                                <?php endif; ?>

                            </div>

                            <div>
                                <span class="trainer-label">Formateur</span>

                                <span class="trainer-name">
                                    <?= e($formateurNom ?: 'Formateur TOUN') ?>
                                </span>

                                <?php if (!empty($formation['email_user'])): ?>
                                    <span class="trainer-email">
                                        <?= e($formation['email_user']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                        </div>

                    </section>

                </div>

            </div>

            <aside class="purchase-card reveal-right">

                <div class="purchase-top">

                    <div class="purchase-price">
                        <?= number_format((float) $formation['prix_form'], 0, ',', ' ') ?> FCFA
                    </div>

                    <?php if ($formationAcquise): ?>

                    <div class="purchase-label">
                        Vous avez déjà accès à cette formation
                    </div>

                    <a href="apprenant/cours.php?id=<?= (int) $formation['id_form'] ?>" class="buy-button">
                        <i class="fas fa-play"></i>
                        Accéder à la formation
                    </a>

                    <p class="purchase-note">
                        Formation déjà achetée • Retrouvez-la dans « Mes formations »
                    </p>

                    <?php else: ?>

                    <div class="purchase-label">
                        Accès à cette formation
                    </div>

                    <a href="acheter-formation.php?id=<?= (int) $formation['id_form'] ?>" class="buy-button">
                        <i class="fas fa-lock"></i>
                        Acheter la formation
                    </a>

                    <p class="purchase-note">
                        Paiement sécurisé • Accès après validation
                    </p>

                    <?php endif; ?>

                </div>

                <div class="purchase-list">

                    <div class="purchase-item">
                        <i class="fas fa-infinity"></i>
                        <span>Accès à votre parcours de formation</span>
                    </div>

                    <div class="purchase-item">
                        <i class="fas fa-video"></i>
                        <span>Vidéos et contenus pédagogiques</span>
                    </div>

                    <div class="purchase-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Ressources et supports de cours</span>
                    </div>

                    <div class="purchase-item">
                        <i class="fas fa-certificate"></i>
                        <span>Certification après validation</span>
                    </div>

                    <div class="purchase-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Suivi de votre progression</span>
                    </div>

                </div>

            </aside>

        </main>

        <footer class="footer">
            <div class="container footer-grid">
                <div class="footer-brand">
                    <img src="images/logo1.png" class="footer-logo" alt="TOUN">
                    <p>Une plateforme dédiée à la formation professionnelle en ligne pour les professionnels et les entreprises.</p>
                </div>
                <div>
                    <h4>Navigation</h4>
                    <a href="index.php">Accueil</a>
                    <a href="formation.php">Formation</a>
                    <a href="tarif.php">Tarifs</a>
                    <a href="a-propos.php">À propos</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div>
                    <h4>Solutions</h4>
                    <a href="professionnels.php">Professionnels</a>
                    <a href="entreprises.php">Entreprises</a>
                    <a href="formation.php">Nos formations</a>
                </div>
                <div>
                    <h4>Contact</h4>
                    <span><i class="fa-solid fa-location-dot"></i>Abidjan, Côte d'Ivoire</span>
                    <a href="mailto:contact@toun.com"><i class="fa-solid fa-envelope"></i>contact@toun.com</a>
                    <a href="tel:+2250102305952"><i class="fa-solid fa-phone"></i>+225 01 02 30 59 52</a>
                </div>
            </div>

            <div class="container footer-bottom">
                <span>© <?= date('Y') ?> TOUN. Tous droits réservés.</span>
                <div>
                    <a href="confidentialite.php">Confidentialité</a>
                    <span>·</span>
                    <a href="conditions.php">Conditions</a>
                    <span>·</span>
                    <a href="mentions-legales.php">Mentions légales</a>
                </div>
            </div>
        </footer>

        <script>
            (function () {
                var header = document.querySelector('.site-header');
                if (!header) {
                    return;
                }
                var toggle = header.querySelector('.menu-toggle');
                var dropdown = header.querySelector('.nav-dropdown');
                var dropdownButton = header.querySelector('.nav-dropdown-btn');

                var onScroll = function () {
                    header.classList.toggle('scrolled', window.scrollY > 10);
                };
                onScroll();
                window.addEventListener('scroll', onScroll, { passive: true });

                var setMenu = function (open) {
                    header.classList.toggle('mobile-open', open);
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', String(open));
                        var icon = toggle.querySelector('i');
                        if (icon) {
                            icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
                        }
                    }
                };

                if (toggle) {
                    toggle.addEventListener('click', function () {
                        setMenu(!header.classList.contains('mobile-open'));
                    });
                }

                header.querySelectorAll('.main-nav > .nav-links > a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        setMenu(false);
                    });
                });

                if (dropdown && dropdownButton) {
                    dropdownButton.addEventListener('click', function (event) {
                        event.stopPropagation();
                        var open = dropdown.classList.toggle('open');
                        dropdownButton.setAttribute('aria-expanded', String(open));
                    });

                    document.addEventListener('click', function (event) {
                        if (!dropdown.contains(event.target)) {
                            dropdown.classList.remove('open');
                            dropdownButton.setAttribute('aria-expanded', 'false');
                        }
                    });
                }

                window.addEventListener('resize', function () {
                    if (window.innerWidth > 960) {
                        setMenu(false);
                    }
                });
            })();
        </script>

        <script>
            (function () {
                var targets = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom, .stagger');
                document.querySelectorAll('.stagger').forEach(function (group) {
                    Array.prototype.forEach.call(group.children, function (child, index) {
                        child.style.transitionDelay = index * 90 + 'ms';
                    });
                });
                var reveal = function (element) {
                    element.classList.add('visible');
                    if (element.classList.contains('stagger')) {
                        var total = element.children.length * 90 + 800;
                        setTimeout(function () {
                            element.classList.add('done');
                            Array.prototype.forEach.call(element.children, function (child) {
                                child.style.transitionDelay = '';
                            });
                        }, total);
                    }
                };
                if (!('IntersectionObserver' in window)) {
                    Array.prototype.forEach.call(targets, reveal);
                } else {
                    var observer = new IntersectionObserver(
                        function (entries) {
                            entries.forEach(function (entry) {
                                if (entry.isIntersecting) {
                                    reveal(entry.target);
                                    observer.unobserve(entry.target);
                                }
                            });
                        },
                        { threshold: 0.15, rootMargin: '0px 0px -6% 0px' }
                    );
                    Array.prototype.forEach.call(targets, function (t) {
                        observer.observe(t);
                    });
                }
            })();
        </script>

    </body>
</html>

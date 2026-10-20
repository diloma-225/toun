<?php
session_start();
require_once '../config/connexion.php';
require_once __DIR__ . '/../includes/certificats.php';
require_once __DIR__ . '/../includes/evaluations.php';
require_once __DIR__ . '/../includes/ressources.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

$idUser = (int) $_SESSION['id_user'];
$idForm = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

function tableExiste(PDO $con, string $table): bool
{
    try {
        $req = $con->prepare('SHOW TABLES LIKE ?');
        $req->execute([$table]);
        return (bool) $req->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function colonnesTable(PDO $con, string $table): array
{
    try {
        return array_column($con->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    } catch (PDOException $e) {
        return [];
    }
}

function premiereColonne(array $colonnes, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $colonnes, true)) {
            return $candidate;
        }
    }

    return null;
}

function urlMedia(?string $fichier, array $dossiers): string
{
    $fichier = trim((string) $fichier);

    if ($fichier === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $fichier)) {
        return $fichier;
    }

    $nom = basename(str_replace('\\', '/', $fichier));

    foreach ($dossiers as $dossier) {
        if (is_file(__DIR__ . '/../' . $dossier . '/' . $nom)) {
            return '../' . $dossier . '/' . rawurlencode($nom);
        }
    }

    return '';
}

function lienYoutube(string $url): string
{
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([\w-]{11})#', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }

    return '';
}

$stmt = $con->prepare("
    SELECT f.*, c.nom_cat
    FROM t_formations f
    LEFT JOIN t_categories c ON c.id_cat = f.id_cat
    WHERE f.id_form = ?
    LIMIT 1
");
$stmt->execute([$idForm]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    header('Location: mes-formations.php');
    exit();
}

$stmt = $con->prepare('SELECT statut_inscrip FROM t_inscriptions WHERE id_user = ? AND id_form = ? LIMIT 1');
$stmt->execute([$idUser, $idForm]);
$inscription = $stmt->fetch(PDO::FETCH_ASSOC);
$statutsRefuses = ['annulee', 'annule', 'refusee', 'refuse', 'en_attente'];

if (!$inscription || in_array(strtolower((string) $inscription['statut_inscrip']), $statutsRefuses, true)) {
    header('Location: mes-formations.php');
    exit();
}

$con->exec("
    CREATE TABLE IF NOT EXISTS t_suivi_contenus (
        id_user INT NOT NULL,
        id_form INT NOT NULL,
        type_element VARCHAR(10) NOT NULL,
        id_element INT NOT NULL,
        date_suivi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_user, type_element, id_element)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$modules = [];

if (tableExiste($con, 't_modules')) {
    $stmt = $con->prepare('SELECT * FROM t_modules WHERE id_form = ? ORDER BY ordre_module ASC, id_module ASC');
    $stmt->execute([$idForm]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$colonnesLecon = tableExiste($con, 't_lecons') ? colonnesTable($con, 't_lecons') : [];
$colContenu = premiereColonne($colonnesLecon, ['contenu_lecon', 'description_lecon']);
$colVideo = premiereColonne($colonnesLecon, ['video_lecon', 'media_lecon', 'url_video']);
$colOrdre = premiereColonne($colonnesLecon, ['ordre_lecon']);
$elements = [];

foreach ($modules as $index => $module) {
    $modules[$index]['lecons'] = [];

    if ($colonnesLecon) {
        $ordre = $colOrdre ? "$colOrdre ASC, id_lecon ASC" : 'id_lecon ASC';
        $stmt = $con->prepare("SELECT * FROM t_lecons WHERE id_module = ? ORDER BY $ordre");
        $stmt->execute([$module['id_module']]);
        $modules[$index]['lecons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($modules[$index]['lecons']) {
        foreach ($modules[$index]['lecons'] as $lecon) {
            $typeLecon = (string) ($lecon['type_contenu'] ?? 'texte');
            $fichierLecon = trim((string) ($lecon['contenu_lecon'] ?? ''));
            $texteLecon = trim((string) ($lecon['description_lecon'] ?? ''));

            if ($typeLecon === 'texte' && $texteLecon === '') {
                $texteLecon = $fichierLecon;
            }

            $elements[] = [
                'type' => 'lecon',
                'id' => (int) $lecon['id_lecon'],
                'id_module' => (int) $module['id_module'],
                'titre' => $lecon['titre_lecon'],
                'module' => $module['titre_module'],
                'contenu' => $texteLecon !== '' ? $texteLecon : ($colContenu ? (string) ($lecon[$colContenu] ?? '') : ''),
                'video' => $typeLecon === 'video' ? $fichierLecon : ($colVideo ? (string) ($lecon[$colVideo] ?? '') : ''),
                'document' => in_array($typeLecon, ['pdf', 'lien'], true) ? $fichierLecon : '',
                'type_contenu' => $typeLecon,
                'duree' => $lecon['duree_lecon'] ?? null
            ];
        }
    } else {
        $elements[] = [
            'type' => 'module',
            'id' => (int) $module['id_module'],
            'id_module' => (int) $module['id_module'],
            'titre' => $module['titre_module'],
            'module' => $module['titre_module'],
            'contenu' => (string) ($module['description_module'] ?? ''),
            'video' => '',
            'duree' => $module['duree_module'] ?? null
        ];
    }
}

$stmt = $con->prepare('SELECT type_element, id_element FROM t_suivi_contenus WHERE id_user = ? AND id_form = ?');
$stmt->execute([$idUser, $idForm]);
$termines = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
    $termines[$ligne['type_element'] . '-' . $ligne['id_element']] = true;
}

$cleElement = fn(array $element): string => $element['type'] . '-' . $element['id'];
$parcours = tounParcours($con, $idUser, $idForm);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminer'])) {
    $type = $_POST['type'] === 'module' ? 'module' : 'lecon';
    $idElement = (int) ($_POST['element'] ?? 0);
    $existe = false;

    foreach ($elements as $element) {
        if ($element['type'] === $type && $element['id'] === $idElement && tounModuleOuvert($parcours, $element['id_module'])) {
            $existe = $element;
        }
    }

    if ($existe) {
        $stmt = $con->prepare(
            'INSERT IGNORE INTO t_suivi_contenus (id_user, id_form, type_element, id_element) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$idUser, $idForm, $type, $idElement]);
        $termines[$type . '-' . $idElement] = true;

        $faits = count(array_filter($elements, fn($el) => isset($termines[$cleElement($el)])));
        $pourcentCours = $elements ? (int) round($faits * 100 / count($elements)) : 0;
        $etat = tounEnregistrerProgression($con, $idUser, $idForm, $pourcentCours);
        $parcours = tounParcours($con, $idUser, $idForm);
        $moduleFini = $parcours['modules'][$existe['id_module']] ?? null;

        $suivant = null;

        foreach ($elements as $element) {
            if (!isset($termines[$cleElement($element)]) && tounModuleOuvert($parcours, $element['id_module'])) {
                $suivant = $element;
                break;
            }
        }

        if ($moduleFini && $moduleFini['termine'] && $moduleFini['test'] && !$moduleFini['test']['reussi']) {
            $_SESSION['cours_message'] = 'Bravo, vous avez terminé ce module ! Passez le test du module pour débloquer la suite.';
            $suivant = $existe;
        } elseif (!$etat['cours_termines']) {
            $_SESSION['cours_message'] = 'Contenu terminé. Vous pouvez passer à la suite.';
        } elseif ($etat['pourcentage'] >= 100) {
            $_SESSION['cours_message'] = 'Félicitations, vous avez terminé cette formation ! Votre certificat est disponible dans « Mes certificats ».';
        } else {
            $_SESSION['cours_message'] = 'Bravo, vous avez terminé tous les cours ! Passez maintenant l’évaluation finale pour obtenir votre certificat.';
        }

        $cible = 'cours.php?id=' . $idForm;

        if ($suivant) {
            $cible .= '&type=' . $suivant['type'] . '&element=' . $suivant['id'];
        }

        header('Location: ' . $cible);
        exit();
    }
}

$faits = count(array_filter($elements, fn($el) => isset($termines[$cleElement($el)])));
$pourcentCours = $elements ? (int) round($faits * 100 / count($elements)) : 0;

$quizFormation = tounQuizDeFormation($con, $idUser, $idForm);
$coursTermines = $elements && $pourcentCours >= 100;
$finauxAPasser = array_values(array_filter($parcours['finaux'], fn($quiz) => !$quiz['reussi']));
$testEnAttente = null;

foreach ($parcours['modules'] as $moduleParcours) {
    if ($moduleParcours['test'] && !$moduleParcours['test']['reussi'] && $moduleParcours['test']['disponible']) {
        $testEnAttente = $moduleParcours;
        break;
    }
}
$formationValidee = $quizFormation && $coursTermines && tounQuizFormationValides($con, $idUser, $idForm);
$progression = $quizFormation ? ($formationValidee ? 100 : (int) floor($pourcentCours * 0.9)) : $pourcentCours;

$actuel = null;
$typeDemande = $_GET['type'] ?? '';
$idDemande = (int) ($_GET['element'] ?? 0);

foreach ($elements as $element) {
    if ($element['type'] === $typeDemande && $element['id'] === $idDemande && tounModuleOuvert($parcours, $element['id_module'])) {
        $actuel = $element;
    }
}

if (!$actuel) {
    foreach ($elements as $element) {
        if (!isset($termines[$cleElement($element)]) && tounModuleOuvert($parcours, $element['id_module'])) {
            $actuel = $element;
            break;
        }
    }
}

if (!$actuel && $elements) {
    $actuel = $elements[0];
}

$videoUrl = '';
$youtube = '';

if ($actuel && $actuel['video'] !== '') {
    $youtube = lienYoutube($actuel['video']);
    $videoUrl = $youtube ? '' : urlMedia($actuel['video'], ['uploads/lecons', 'uploads/formations/videos', 'uploads/formations', 'uploads']);
}

$premierContenu = !$actuel || $cleElement($actuel) === $cleElement($elements[0]);

if ($videoUrl === '' && $youtube === '' && $premierContenu) {
    $videoUrl = urlMedia($formation['video_intro_form'] ?? '', ['uploads/formations/videos', 'uploads/formations', 'uploads']);
}

$ressourcesLecon = $actuel
    ? array_values(array_filter(
        tounRessourcesFormation($con, $idForm),
        fn($ressource) => empty($ressource['id_module']) || (int) $ressource['id_module'] === $actuel['id_module']
    ))
    : [];

$image = urlMedia($formation['img_form'] ?? '', ['uploads/formations', 'uploads', 'images/formations', 'images']);

$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));
$nomAffiche = trim($prenom . ' ' . $nom) ?: 'Apprenant';
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'TO';
$photoUrl = urlMedia($_SESSION['photo_user'] ?? '', ['uploads/profils', 'uploads', 'assets/uploads', 'images/profils', 'images']);

$message = $_SESSION['cours_message'] ?? '';
unset($_SESSION['cours_message']);

$position = 0;

foreach ($elements as $index => $element) {
    if ($actuel && $cleElement($element) === $cleElement($actuel)) {
        $position = $index;
    }
}

$precedent = $elements[$position - 1] ?? null;
$suivant = $elements[$position + 1] ?? null;

if ($suivant && !tounModuleOuvert($parcours, $suivant['id_module'])) {
    $suivant = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($formation['titre_form']) ?> | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            .lecture-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 18px;
            }

            .lecture-back {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 8px;
                color: #3f68ae;
                font-size: 11.5px;
                font-weight: 600;
                text-decoration: none;
            }

            .lecture-head h1 {
                margin-bottom: 4px;
                color: #17223f;
                font-size: 22px;
            }

            .lecture-head p {
                color: #7b899b;
                font-size: 11.5px;
            }

            .lecture-progress {
                min-width: 220px;
                padding: 12px 14px;
                border: 1px solid #e1e9ef;
                border-radius: 12px;
                background: #fff;
            }

            .lecture-progress-top {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                color: #69768a;
                font-size: 11px;
            }

            .lecture-progress-top strong {
                color: #20a1b8;
                font-size: 12.5px;
            }

            .lecture-bar {
                height: 7px;
                overflow: hidden;
                border-radius: 10px;
                background: #e8f0f5;
            }

            .lecture-bar span {
                display: block;
                height: 100%;
                border-radius: 10px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
            }

            .lecture-message {
                display: flex;
                align-items: center;
                gap: 9px;
                margin-bottom: 16px;
                padding: 11px 14px;
                border-radius: 10px;
                background: #e7f7f1;
                color: #15805f;
                font-size: 11.5px;
            }

            .lecture-layout {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 320px;
                gap: 18px;
                align-items: start;
            }

            .lecture-player,
            .lecture-program {
                border: 1px solid #e1e9ef;
                border-radius: 16px;
                background: #fff;
            }

            .lecture-media {
                overflow: hidden;
                border-radius: 16px 16px 0 0;
                background: #10294d;
                aspect-ratio: 16 / 9;
            }

            .lecture-media video,
            .lecture-media iframe,
            .lecture-media img {
                display: block;
                width: 100%;
                height: 100%;
                border: 0;
                object-fit: contain;
            }

            .lecture-media img {
                object-fit: cover;
            }

            .lecture-media-empty {
                display: grid;
                height: 100%;
                place-items: center;
                color: rgba(255, 255, 255, 0.7);
                font-size: 12px;
                text-align: center;
            }

            .lecture-media-empty i {
                display: block;
                margin-bottom: 8px;
                font-size: 28px;
            }

            .lecture-body {
                padding: 20px 22px 22px;
            }

            .lecture-module {
                color: #20a1b8;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.6px;
            }

            .lecture-body h2 {
                margin: 6px 0 12px;
                color: #17223f;
                font-size: 17px;
            }

            .lecture-text {
                color: #5f6d80;
                font-size: 12px;
                line-height: 1.8;
            }

            .lecture-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-top: 20px;
                padding-top: 16px;
                border-top: 1px solid #edf2f5;
            }

            .lecture-nav {
                display: flex;
                gap: 8px;
            }

            .lecture-btn {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 10px 15px;
                border: 1px solid #dfe8ee;
                border-radius: 9px;
                background: #fff;
                color: #3f68ae;
                font-family: inherit;
                font-size: 11.5px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
            }

            .lecture-btn.primary {
                border-color: transparent;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
            }

            .lecture-btn.done {
                border-color: #bfe8d8;
                background: #e7f7f1;
                color: #15805f;
                cursor: default;
            }

            .lecture-program {
                padding: 16px;
            }

            .lecture-program h3 {
                margin-bottom: 12px;
                color: #17223f;
                font-size: 13px;
            }

            .program-module + .program-module {
                margin-top: 12px;
            }

            .program-module-title {
                margin-bottom: 6px;
                color: #17223f;
                font-size: 11.5px;
                font-weight: 600;
            }

            .program-item {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 9px 10px;
                border-radius: 9px;
                color: #5f6d80;
                font-size: 11.5px;
                text-decoration: none;
            }

            .program-item:hover {
                background: #f3f8fb;
            }

            .program-item.active {
                background: #eaf6fa;
                color: #17223f;
                font-weight: 600;
            }

            .program-item i {
                width: 16px;
                color: #b5c2cf;
                text-align: center;
            }

            .program-item.finished i {
                color: #15805f;
            }

            .program-empty {
                color: #7b899b;
                font-size: 11.5px;
                line-height: 1.7;
            }

            @media (max-width: 1000px) {
                .lecture-layout {
                    grid-template-columns: 1fr;
                }

                .lecture-head {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }

            .lecture-final {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                margin-bottom: 16px;
                padding: 14px 16px;
                border: 1px solid #cfe3ee;
                border-radius: 12px;
                background: #eaf3f9;
            }

            .lecture-final strong {
                display: block;
                margin-bottom: 2px;
                color: #18213f;
                font-size: 12.5px;
            }

            .lecture-final span {
                color: #5f6d80;
                font-size: 12px;
            }

            .lecture-document {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 14px;
                padding: 9px 13px;
                border: 1px solid #cfe3ee;
                border-radius: 9px;
                background: #eaf3f9;
                color: #0b5a91;
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
            }

            .lecture-ressources {
                display: flex;
                flex-direction: column;
                gap: 8px;
                margin-top: 18px;
                padding: 14px 16px;
                border: 1px solid #cfe3ee;
                border-radius: 12px;
                background: #f5fafd;
            }

            .lecture-ressources strong {
                color: #18213f;
                font-size: 13px;
            }

            .lecture-ressources a {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #0b5a91;
                font-size: 12.5px;
                font-weight: 500;
                text-decoration: none;
            }

            .lecture-ressources a:hover {
                text-decoration: underline;
            }

            .program-item.locked {
                color: #a3aebb;
                cursor: not-allowed;
            }

            .program-item.program-test {
                color: #0b5a91;
                font-weight: 600;
            }

            .program-module-title .fa-lock {
                margin-right: 6px;
                color: #a3aebb;
                font-size: 11px;
            }

            @media (max-width: 700px) {
                .lecture-final {
                    flex-direction: column;
                    align-items: stretch;
                    text-align: center;
                }
            }
        </style>
    </head>
    <body>
        <div class="layout">
            <?php include __DIR__ . '/menu.php'; ?>
            <main class="main">
                <header class="topbar">
                    <button class="mobile-menu" type="button" id="mobileMenu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Rechercher une formation...">
                    </div>
                    <div class="top-right">
                        <a href="rendez-vous.php" class="top-action">
                            <i class="fa-regular fa-calendar-check"></i>
                            <span>Mes rendez-vous</span>
                        </a>
                        <a href="notifications.php" class="notification">
                            <i class="fa-regular fa-bell"></i>
                            <span></span>
                        </a>
                        <a href="profil.php" class="profile">
                            <div class="profile-avatar">
                                <?php if ($photoUrl !== ''): ?>
                                    <img src="<?= e($photoUrl) ?>" alt="Photo de profil">
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

                <section class="content">
                    <div class="lecture-head">
                        <div>
                            <a href="mes-formations.php" class="lecture-back">
                                <i class="fa-solid fa-arrow-left"></i>
                                Mes formations
                            </a>
                            <h1><?= e($formation['titre_form']) ?></h1>
                            <p><?= e($formation['nom_cat'] ?: 'Formation') ?> · <?= count($elements) ?> contenu<?= count($elements) > 1 ? 's' : '' ?></p>
                        </div>
                        <div class="lecture-progress">
                            <div class="lecture-progress-top">
                                <span>Votre progression</span>
                                <strong><?= $progression ?> %</strong>
                            </div>
                            <div class="lecture-bar">
                                <span style="width: <?= $progression ?>%"></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($message !== ''): ?>
                        <div class="lecture-message">
                            <i class="fa-solid fa-circle-check"></i>
                            <?= e($message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($testEnAttente): ?>
                        <div class="lecture-final">
                            <div>
                                <strong><?= e($testEnAttente['test']['titre_quiz']) ?></strong>
                                <span>Réussissez ce petit test pour vérifier vos acquis et débloquer le module suivant.</span>
                            </div>
                            <a class="lecture-btn primary" href="evaluation.php?id=<?= (int) $testEnAttente['test']['id_quiz'] ?>">
                                <i class="fa-solid fa-list-check"></i>
                                Passer le test du module
                            </a>
                        </div>
                    <?php elseif ($coursTermines && $finauxAPasser && $finauxAPasser[0]['disponible']): ?>
                        <div class="lecture-final">
                            <div>
                                <strong>Évaluation finale</strong>
                                <span>Réussissez l’évaluation pour recevoir votre certificat.</span>
                            </div>
                            <a class="lecture-btn primary" href="evaluation.php?id=<?= (int) $finauxAPasser[0]['id_quiz'] ?>">
                                <i class="fa-solid fa-clipboard-check"></i>
                                Passer l’évaluation
                            </a>
                        </div>
                    <?php elseif ($formationValidee): ?>
                        <div class="lecture-final">
                            <div>
                                <strong>Formation validée</strong>
                                <span>Vous avez réussi l’évaluation. Votre certificat vous attend.</span>
                            </div>
                            <a class="lecture-btn primary" href="certificats.php">
                                <i class="fa-solid fa-award"></i>
                                Voir mon certificat
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="lecture-layout">
                        <article class="lecture-player">
                            <div class="lecture-media">
                                <?php if ($youtube !== ''): ?>
                                    <iframe src="<?= e($youtube) ?>" allowfullscreen></iframe>
                                <?php elseif ($videoUrl !== ''): ?>
                                    <video controls preload="metadata" <?= $image !== '' ? 'poster="' . e($image) . '"' : '' ?>>
                                        <source src="<?= e($videoUrl) ?>">
                                        Votre navigateur ne prend pas en charge la lecture vidéo.
                                    </video>
                                <?php elseif ($image !== ''): ?>
                                    <img src="<?= e($image) ?>" alt="<?= e($formation['titre_form']) ?>">
                                <?php else: ?>
                                    <div class="lecture-media-empty">
                                        <div>
                                            <i class="fa-solid fa-book-open"></i>
                                            Aucun média pour ce contenu
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="lecture-body">
                                <?php if ($actuel): ?>
                                    <span class="lecture-module"><?= e($actuel['module']) ?></span>
                                    <h2><?= e($actuel['titre']) ?></h2>
                                    <div class="lecture-text">
                                        <?= nl2br(e($actuel['contenu'] !== '' ? $actuel['contenu'] : ($formation['descrip_form'] ?? ''))) ?>
                                    </div>

                                    <?php
                                    $documentUrl = ($actuel['document'] ?? '') !== ''
                                        ? urlMedia($actuel['document'], ['uploads/lecons', 'uploads/formations', 'uploads'])
                                        : '';
                                    ?>
                                    <?php if ($documentUrl !== ''): ?>
                                        <a class="lecture-document" href="<?= e($documentUrl) ?>" target="_blank" rel="noopener">
                                            <i class="fa-solid <?= ($actuel['type_contenu'] ?? '') === 'pdf' ? 'fa-file-pdf' : 'fa-link' ?>"></i>
                                            <?= ($actuel['type_contenu'] ?? '') === 'pdf' ? 'Ouvrir le document' : 'Ouvrir le lien' ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($ressourcesLecon): ?>
                                        <div class="lecture-ressources">
                                            <strong>Ressources pour aller plus loin</strong>
                                            <?php foreach ($ressourcesLecon as $ressource): ?>
                                                <a href="<?= e(tounLienRessource($ressource)) ?>" target="_blank" rel="noopener">
                                                    <i class="fa-solid <?= ($ressource['type_ressource'] ?? '') === 'pdf' ? 'fa-file-pdf' : (($ressource['type_ressource'] ?? '') === 'lien' ? 'fa-link' : 'fa-file') ?>"></i>
                                                    <?= e($ressource['titre_ressource']) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="lecture-actions">
                                        <div class="lecture-nav">
                                            <?php if ($precedent): ?>
                                                <a class="lecture-btn" href="cours.php?id=<?= $idForm ?>&type=<?= $precedent['type'] ?>&element=<?= $precedent['id'] ?>">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                    Précédent
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($suivant): ?>
                                                <a class="lecture-btn" href="cours.php?id=<?= $idForm ?>&type=<?= $suivant['type'] ?>&element=<?= $suivant['id'] ?>">
                                                    Suivant
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (isset($termines[$cleElement($actuel)])): ?>
                                            <span class="lecture-btn done">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Terminé
                                            </span>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="type" value="<?= e($actuel['type']) ?>">
                                                <input type="hidden" name="element" value="<?= (int) $actuel['id'] ?>">
                                                <button type="submit" name="terminer" class="lecture-btn primary">
                                                    <i class="fa-solid fa-check"></i>
                                                    Marquer comme terminé
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <h2>Présentation de la formation</h2>
                                    <div class="lecture-text"><?= nl2br(e($formation['descrip_form'] ?? '')) ?></div>
                                <?php endif; ?>
                            </div>
                        </article>

                        <aside class="lecture-program">
                            <h3>Programme</h3>
                            <?php if (!$modules): ?>
                                <p class="program-empty">Le programme de cette formation sera bientôt disponible.</p>
                            <?php endif; ?>

                            <?php foreach ($modules as $module): ?>
                                <?php
                                $etatModule = $parcours['modules'][(int) $module['id_module']] ?? null;
                                $moduleOuvert = $etatModule ? $etatModule['ouvert'] : true;
                                $testModule = $etatModule['test'] ?? null;
                                ?>
                                <div class="program-module">
                                    <div class="program-module-title">
                                        <?php if (!$moduleOuvert): ?>
                                            <i class="fa-solid fa-lock"></i>
                                        <?php endif; ?>
                                        <?= e($module['titre_module']) ?>
                                    </div>
                                    <?php foreach ($elements as $element): ?>
                                        <?php if ($element['id_module'] !== (int) $module['id_module']) continue; ?>
                                        <?php
                                        $estFini = isset($termines[$cleElement($element)]);
                                        $estActif = $actuel && $cleElement($element) === $cleElement($actuel);
                                        ?>
                                        <?php if (!$moduleOuvert): ?>
                                            <span class="program-item locked" title="Réussissez le test du module précédent pour débloquer ce module">
                                                <i class="fa-solid fa-lock"></i>
                                                <span><?= e($element['titre']) ?></span>
                                            </span>
                                        <?php else: ?>
                                            <a
                                                class="program-item<?= $estFini ? ' finished' : '' ?><?= $estActif ? ' active' : '' ?>"
                                                href="cours.php?id=<?= $idForm ?>&type=<?= $element['type'] ?>&element=<?= $element['id'] ?>">
                                                <i class="fa-solid <?= $estFini ? 'fa-circle-check' : 'fa-circle-play' ?>"></i>
                                                <span><?= e($element['titre']) ?></span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if ($testModule): ?>
                                        <?php if ($testModule['reussi']): ?>
                                            <a class="program-item finished" href="resultat-evaluation.php?id=<?= $testModule['id_quiz'] ?>">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span>Test du module</span>
                                            </a>
                                        <?php elseif ($testModule['disponible']): ?>
                                            <a class="program-item program-test" href="evaluation.php?id=<?= $testModule['id_quiz'] ?>">
                                                <i class="fa-solid fa-list-check"></i>
                                                <span>Test du module</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="program-item locked" title="Terminez les leçons du module pour débloquer le test">
                                                <i class="fa-solid fa-lock"></i>
                                                <span>Test du module</span>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($parcours['finaux']): ?>
                                <div class="program-module">
                                    <div class="program-module-title">Évaluation finale</div>
                                    <?php foreach ($parcours['finaux'] as $quiz): ?>
                                        <?php if ($quiz['reussi']): ?>
                                            <a class="program-item finished" href="resultat-evaluation.php?id=<?= (int) $quiz['id_quiz'] ?>">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span><?= e($quiz['titre_quiz']) ?></span>
                                            </a>
                                        <?php elseif ($quiz['disponible']): ?>
                                            <a class="program-item" href="evaluation.php?id=<?= (int) $quiz['id_quiz'] ?>">
                                                <i class="fa-solid fa-clipboard-check"></i>
                                                <span><?= e($quiz['titre_quiz']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="program-item locked" title="Terminez tous les modules et leurs tests pour débloquer l’évaluation">
                                                <i class="fa-solid fa-lock"></i>
                                                <span><?= e($quiz['titre_quiz']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </aside>
                    </div>
                </section>
            </main>
        </div>

        <script>
            const mobileMenu = document.getElementById('mobileMenu');
            const sidebar = document.getElementById('sidebar');

            if (mobileMenu && sidebar) {
                mobileMenu.addEventListener('click', () => {
                    sidebar.classList.toggle('open');
                });
            }
        </script>
    </body>
</html>

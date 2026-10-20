<?php
session_start();

require_once '../config/connexion.php';
require_once '../includes/evaluations.php';
require_once '../includes/certificats.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

$idUser = (int) $_SESSION['id_user'];

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDate($date): string
{
    return !empty($date) ? date('d/m/Y', strtotime($date)) : '—';
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

$initiales = strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

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

$idQuiz = (int) ($_GET['id'] ?? 0);

$quiz = tounQuizApprenant($con, $idQuiz, $idUser);

if (!$quiz) {
    header('Location: evaluations.php');
    exit();
}

$resultat = tounDernierResultat($con, $idUser, $idQuiz);

if (!$resultat) {
    header('Location: evaluation.php?id=' . $idQuiz);
    exit();
}

$noteMinimale = tounNoteMinimale($quiz);
$note = (float) $resultat['note_obtenue'];
$reussi = $resultat['statut_resultat'] === 'reussi';
$tentatives = tounNombreTentatives($con, $idUser, $idQuiz);
$correction = $_SESSION['correction_quiz'][$idQuiz] ?? null;
$pourcentage = (int) round(($note / 20) * 100);

$stmtCertificat = $con->prepare("SELECT id_certificat FROM t_certificats WHERE id_user = ? AND id_form = ? LIMIT 1");
$stmtCertificat->execute([$idUser, (int) $quiz['id_form']]);
$idCertificat = (int) $stmtCertificat->fetchColumn();
$estTestModule = $quiz['type_quiz'] === 'module';
?>

<!DOCTYPE html>
<html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Résultat | <?= e($quiz['titre_quiz']) ?> | TOUN</title>

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
            href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>"
        >

        <style>
            :root {
                --bleu-nuit: #18213f;
                --bleu-toun: #0b5a91;
                --turquoise: #20a1b8;
                --bleu-pale: #eaf3f9;
                --fond: #f5f8fc;
                --bordure: #dfe8ef;
                --texte-doux: #7c8da1;
                --vert: #1e8e5a;
                --vert-pale: #eaf7f0;
                --rouge: #c0392b;
                --rouge-pale: #fdf0ee;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: "Poppins", sans-serif;
                background: var(--fond);
                color: var(--bleu-nuit);
            }

            a {
                text-decoration: none;
            }

            .resultat {
                max-width: 900px;
            }

            .resultat__retour {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 16px;
                color: var(--bleu-toun);
                font-size: 12px;
                font-weight: 600;
            }

            .bilan {
                display: flex;
                align-items: center;
                gap: 24px;
                padding: 24px;
                margin-bottom: 18px;
                border: 1px solid var(--bordure);
                border-radius: 16px;
                background: #fff;
            }

            .bilan__cercle {
                width: 118px;
                height: 118px;
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                border: 8px solid var(--bleu-pale);
            }

            .bilan__cercle--reussi {
                border-color: var(--vert);
            }

            .bilan__cercle--echoue {
                border-color: var(--rouge);
            }

            .bilan__note {
                font-size: 24px;
                font-weight: 800;
                line-height: 1;
            }

            .bilan__sur {
                color: var(--texte-doux);
                font-size: 11.5px;
            }

            .bilan__statut {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 5px 10px;
                margin-bottom: 8px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 700;
            }

            .bilan__statut--reussi {
                background: var(--vert-pale);
                color: var(--vert);
            }

            .bilan__statut--echoue {
                background: var(--rouge-pale);
                color: var(--rouge);
            }

            .bilan__titre {
                margin-bottom: 4px;
                font-size: 18px;
                font-weight: 800;
            }

            .bilan__formation {
                margin-bottom: 12px;
                color: var(--texte-doux);
                font-size: 12px;
            }

            .bilan__message {
                margin-bottom: 14px;
                font-size: 12px;
                line-height: 1.6;
            }

            .bilan__infos {
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
                color: var(--texte-doux);
                font-size: 12px;
            }

            .bilan__infos i {
                margin-right: 5px;
                color: var(--turquoise);
            }

            .correction__titre {
                margin-bottom: 12px;
                font-size: 15px;
                font-weight: 700;
            }

            .ligne {
                padding: 15px 18px;
                margin-bottom: 10px;
                border: 1px solid var(--bordure);
                border-left: 4px solid var(--vert);
                border-radius: 12px;
                background: #fff;
            }

            .ligne--fausse {
                border-left-color: var(--rouge);
            }

            .ligne__question {
                margin-bottom: 8px;
                font-size: 12.5px;
                font-weight: 600;
                line-height: 1.5;
            }

            .ligne__reponse {
                font-size: 12px;
                line-height: 1.7;
            }

            .ligne__reponse i {
                width: 16px;
            }

            .ligne__reponse--juste {
                color: var(--vert);
            }

            .ligne__reponse--fausse {
                color: var(--rouge);
            }

            .resultat__actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }

            .bouton {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                height: 42px;
                padding: 0 20px;
                border-radius: 10px;
                font-family: inherit;
                font-size: 12px;
                font-weight: 600;
            }

            .bouton--principal {
                background: var(--bleu-toun);
                color: #fff;
            }

            .bouton--principal:hover {
                background: var(--bleu-nuit);
            }

            .bouton--secondaire {
                border: 1px solid var(--bordure);
                background: #fff;
                color: var(--bleu-nuit);
            }

            .profile-avatar img {
                width: 100%;
                height: 100%;
                display: block;
                object-fit: cover;
            }

            @media (max-width: 700px) {
                .bilan {
                    flex-direction: column;
                    text-align: center;
                    padding: 20px 16px;
                }

                .bilan__infos {
                    justify-content: center;
                }

                .resultat__actions {
                    flex-direction: column;
                }

                .bouton {
                    width: 100%;
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

                <section class="content resultat">

                    <a href="evaluations.php" class="resultat__retour">
                        <i class="fa-solid fa-arrow-left"></i>
                        Mes évaluations
                    </a>

                    <section class="bilan">

                        <div class="bilan__cercle <?= $reussi ? 'bilan__cercle--reussi' : 'bilan__cercle--echoue' ?>">
                            <span class="bilan__note"><?= e(tounFormatNote($note)) ?></span>
                            <span class="bilan__sur">sur 20</span>
                        </div>

                        <div>

                            <span class="bilan__statut <?= $reussi ? 'bilan__statut--reussi' : 'bilan__statut--echoue' ?>">
                                <i class="fa-solid <?= $reussi ? 'fa-circle-check' : 'fa-rotate-right' ?>"></i>
                                <?= $reussi ? 'Réussi' : 'À reprendre' ?>
                            </span>

                            <h1 class="bilan__titre">
                                <?= e($quiz['titre_quiz']) ?>
                            </h1>

                            <div class="bilan__formation">
                                <?= e($quiz['titre_form']) ?> · <?= e($quiz['titre_module']) ?>
                            </div>

                            <p class="bilan__message">
                                <?php if ($reussi && $estTestModule): ?>
                                    Bravo, vous avez réussi ce test avec <?= $pourcentage ?> % de bonnes réponses. Le module suivant est débloqué.
                                <?php elseif ($reussi): ?>
                                    Félicitations, vous avez validé cette évaluation avec <?= $pourcentage ?> % de réussite.<?= $idCertificat > 0 ? ' Votre certificat est prêt.' : '' ?>
                                <?php else: ?>
                                    Il vous faut au moins <?= e(tounFormatNote($noteMinimale)) ?>/20 pour valider. Revoyez le module puis tentez à nouveau.
                                <?php endif; ?>
                            </p>

                            <div class="bilan__infos">
                                <span><i class="fa-solid fa-bullseye"></i>Minimum : <?= e(tounFormatNote($noteMinimale)) ?>/20</span>
                                <?php if ($correction): ?>
                                    <span><i class="fa-solid fa-check-double"></i><?= (int) $correction['bonnes'] ?> bonne(s) réponse(s) sur <?= (int) $correction['total'] ?></span>
                                <?php endif; ?>
                                <span><i class="fa-regular fa-calendar"></i><?= e(date('d/m/Y à H:i', strtotime($resultat['date_passage']))) ?></span>
                                <span><i class="fa-solid fa-repeat"></i><?= $tentatives ?> tentative(s)</span>
                            </div>

                        </div>

                    </section>

                    <?php if ($correction && $correction['corrections']): ?>

                        <section>

                            <h2 class="correction__titre">
                                Correction
                            </h2>

                            <?php foreach ($correction['corrections'] as $index => $ligne): ?>

                                <article class="ligne <?= $ligne['juste'] ? '' : 'ligne--fausse' ?>">

                                    <div class="ligne__question">
                                        <?= $index + 1 ?>. <?= e($ligne['question']) ?>
                                    </div>

                                    <div class="ligne__reponse <?= $ligne['juste'] ? 'ligne__reponse--juste' : 'ligne__reponse--fausse' ?>">
                                        <i class="fa-solid <?= $ligne['juste'] ? 'fa-check' : 'fa-xmark' ?>"></i>
                                        Votre réponse : <?= e($ligne['choisie'] !== '' ? $ligne['choisie'] : 'Aucune') ?>
                                    </div>

                                    <?php if (!$ligne['juste'] && $ligne['bonne'] !== ''): ?>
                                        <div class="ligne__reponse ligne__reponse--juste">
                                            <i class="fa-solid fa-check"></i>
                                            Bonne réponse : <?= e($ligne['bonne']) ?>
                                        </div>
                                    <?php endif; ?>

                                </article>

                            <?php endforeach; ?>

                        </section>

                    <?php endif; ?>

                    <div class="resultat__actions">

                        <a href="evaluations.php" class="bouton bouton--secondaire">
                            <i class="fa-solid fa-list-check"></i>
                            Mes évaluations
                        </a>

                        <?php if (!$reussi): ?>
                            <a href="evaluation.php?id=<?= (int) $idQuiz ?>" class="bouton bouton--principal">
                                <i class="fa-solid fa-rotate-right"></i>
                                <?= $estTestModule ? 'Repasser le test' : 'Repasser l’évaluation' ?>
                            </a>
                        <?php elseif ($idCertificat > 0 && !$estTestModule): ?>
                            <a href="certificat.php?id=<?= $idCertificat ?>" class="bouton bouton--principal">
                                <i class="fa-solid fa-award"></i>
                                Voir mon certificat
                            </a>
                        <?php else: ?>
                            <a href="cours.php?id=<?= (int) $quiz['id_form'] ?>" class="bouton bouton--principal">
                                <i class="fa-solid fa-book-open"></i>
                                Continuer ma formation
                            </a>
                        <?php endif; ?>

                    </div>

                </section>

            </main>

        </div>

        <script>
            const mobileMenu = document.getElementById('mobileMenu');
            const sidebar = document.getElementById('sidebar');

            if (mobileMenu && sidebar) {
                mobileMenu.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                });
            }

            document.querySelectorAll('.menu-link').forEach(link => {
                const page = (link.getAttribute('href') || '').split('/').pop().split('?')[0];

                if (page === 'evaluations.php') {
                    link.classList.add('active');
                }
            });
        </script>

    </body>

</html>

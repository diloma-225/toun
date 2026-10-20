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

if (!tounQuizOuvert(tounParcours($con, $idUser, (int) $quiz['id_form']), $idQuiz)) {
    $_SESSION['cours_message'] = $quiz['type_quiz'] === 'module'
        ? 'Terminez toutes les leçons de ce module pour débloquer son test.'
        : 'Terminez tous les modules et réussissez leurs tests pour débloquer l’évaluation finale.';
    header('Location: cours.php?id=' . (int) $quiz['id_form']);
    exit();
}

$questions = tounQuizQuestions($con, $idQuiz);
$noteMinimale = tounNoteMinimale($quiz);
$erreur = '';

if (empty($_SESSION['jeton_quiz'])) {
    $_SESSION['jeton_quiz'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $questions) {
    $jeton = (string) ($_POST['jeton'] ?? '');
    $choix = array_map('intval', (array) ($_POST['reponses'] ?? []));

    if (!hash_equals($_SESSION['jeton_quiz'], $jeton)) {
        $erreur = 'La session a expiré. Veuillez recommencer l’évaluation.';
    } elseif (count(array_filter($choix)) < count($questions)) {
        $erreur = 'Veuillez répondre à toutes les questions avant de valider.';
    } else {
        $correction = tounCorrigerQuiz($questions, $choix);
        $reussi = $correction['note'] >= $noteMinimale;

        tounEnregistrerResultat($con, $idUser, $idQuiz, $correction['note'], $reussi);

        $idFormQuiz = (int) $quiz['id_form'];
        tounEnregistrerProgression($con, $idUser, $idFormQuiz, tounPourcentCours($con, $idUser, $idFormQuiz));

        $_SESSION['correction_quiz'][$idQuiz] = $correction;

        header('Location: resultat-evaluation.php?id=' . $idQuiz);
        exit();
    }
}

$choixPrecedents = array_map('intval', (array) ($_POST['reponses'] ?? []));
?>

<!DOCTYPE html>
<html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title><?= e($quiz['titre_quiz']) ?> | TOUN</title>

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

            .quiz {
                max-width: 900px;
            }

            .quiz__retour {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 16px;
                color: var(--bleu-toun);
                font-size: 12px;
                font-weight: 600;
            }

            .quiz__entete {
                padding: 22px 24px;
                margin-bottom: 18px;
                border-radius: 16px;
                background: var(--bleu-nuit);
                color: #fff;
            }

            .quiz__formation {
                margin-bottom: 6px;
                color: #9fd8e3;
                font-size: 11.5px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .quiz__titre {
                margin-bottom: 8px;
                font-size: 21px;
                font-weight: 800;
                line-height: 1.3;
            }

            .quiz__description {
                margin-bottom: 14px;
                color: rgba(255, 255, 255, 0.75);
                font-size: 12px;
                line-height: 1.6;
            }

            .quiz__infos {
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
                color: rgba(255, 255, 255, 0.85);
                font-size: 12px;
            }

            .quiz__infos i {
                margin-right: 5px;
                color: var(--turquoise);
            }

            .quiz__alerte {
                padding: 12px 14px;
                margin-bottom: 16px;
                border: 1px solid #f1cfc9;
                border-radius: 10px;
                background: var(--rouge-pale);
                color: var(--rouge);
                font-size: 12px;
            }

            .question {
                padding: 18px 20px;
                margin-bottom: 14px;
                border: 1px solid var(--bordure);
                border-radius: 14px;
                background: #fff;
            }

            .question__numero {
                margin-bottom: 6px;
                color: var(--turquoise);
                font-size: 11.5px;
                font-weight: 700;
            }

            .question__libelle {
                margin-bottom: 14px;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.5;
            }

            .question__choix {
                display: grid;
                gap: 9px;
            }

            .choix {
                display: flex;
                align-items: center;
                gap: 11px;
                padding: 11px 13px;
                border: 1px solid var(--bordure);
                border-radius: 10px;
                background: #fbfdfe;
                font-size: 12px;
                cursor: pointer;
                transition: border-color 0.2s ease, background 0.2s ease;
            }

            .choix:hover {
                border-color: var(--turquoise);
            }

            .choix input {
                width: 16px;
                height: 16px;
                flex-shrink: 0;
                accent-color: var(--bleu-toun);
            }

            .choix:has(input:checked) {
                border-color: var(--bleu-toun);
                background: var(--bleu-pale);
            }

            .quiz__actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-top: 20px;
            }

            .quiz__avancement {
                color: var(--texte-doux);
                font-size: 12px;
            }

            .bouton {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                height: 42px;
                padding: 0 20px;
                border: 0;
                border-radius: 10px;
                font-family: inherit;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
            }

            .bouton--principal {
                background: var(--bleu-toun);
                color: #fff;
            }

            .bouton--principal:hover {
                background: var(--bleu-nuit);
            }

            .vide {
                padding: 40px 20px;
                border: 1px solid var(--bordure);
                border-radius: 14px;
                background: #fff;
                text-align: center;
            }

            .vide i {
                margin-bottom: 12px;
                color: var(--turquoise);
                font-size: 28px;
            }

            .vide h3 {
                margin-bottom: 6px;
                font-size: 14px;
            }

            .vide p {
                color: var(--texte-doux);
                font-size: 12px;
            }

            .profile-avatar img {
                width: 100%;
                height: 100%;
                display: block;
                object-fit: cover;
            }

            @media (max-width: 700px) {
                .quiz__entete {
                    padding: 18px;
                }

                .quiz__titre {
                    font-size: 17px;
                }

                .question {
                    padding: 15px;
                }

                .quiz__actions {
                    flex-direction: column-reverse;
                    align-items: stretch;
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

                <section class="content quiz">

                    <a href="evaluations.php" class="quiz__retour">
                        <i class="fa-solid fa-arrow-left"></i>
                        Mes évaluations
                    </a>

                    <header class="quiz__entete">

                        <div class="quiz__formation">
                            <?= e($quiz['titre_form']) ?>
                        </div>

                        <h1 class="quiz__titre">
                            <?= e($quiz['titre_quiz']) ?>
                        </h1>

                        <?php if (!empty($quiz['description_quiz'])): ?>
                            <p class="quiz__description">
                                <?= e($quiz['description_quiz']) ?>
                            </p>
                        <?php endif; ?>

                        <div class="quiz__infos">
                            <span><i class="fa-solid fa-layer-group"></i><?= e($quiz['titre_module']) ?></span>
                            <span><i class="fa-solid fa-circle-question"></i><?= count($questions) ?> question<?= count($questions) > 1 ? 's' : '' ?></span>
                            <span><i class="fa-solid fa-bullseye"></i>Note minimale : <?= e(tounFormatNote($noteMinimale)) ?>/20</span>
                        </div>

                    </header>

                    <?php if ($erreur !== ''): ?>
                        <div class="quiz__alerte">
                            <?= e($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$questions): ?>

                        <div class="vide">
                            <i class="fa-solid fa-hourglass-half"></i>
                            <h3>Évaluation en préparation</h3>
                            <p>Les questions de cette évaluation seront bientôt disponibles.</p>
                        </div>

                    <?php else: ?>

                        <form method="post" id="formQuiz">

                            <input type="hidden" name="jeton" value="<?= e($_SESSION['jeton_quiz']) ?>">

                            <?php foreach ($questions as $index => $question): ?>

                                <article class="question">

                                    <div class="question__numero">
                                        Question <?= $index + 1 ?> sur <?= count($questions) ?>
                                    </div>

                                    <h2 class="question__libelle">
                                        <?= e($question['libelle_question']) ?>
                                    </h2>

                                    <div class="question__choix">

                                        <?php foreach ($question['reponses'] as $reponse): ?>

                                            <label class="choix">
                                                <input
                                                    type="radio"
                                                    name="reponses[<?= (int) $question['id_question'] ?>]"
                                                    value="<?= (int) $reponse['id_reponse'] ?>"
                                                    required
                                                    <?= ($choixPrecedents[(int) $question['id_question']] ?? 0) === (int) $reponse['id_reponse'] ? 'checked' : '' ?>
                                                >
                                                <span><?= e($reponse['libelle_reponse']) ?></span>
                                            </label>

                                        <?php endforeach; ?>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                            <div class="quiz__actions">

                                <span class="quiz__avancement" id="avancement">
                                    0 / <?= count($questions) ?> réponse(s)
                                </span>

                                <button type="submit" class="bouton bouton--principal">
                                    <i class="fa-solid fa-check"></i>
                                    Valider mes réponses
                                </button>

                            </div>

                        </form>

                    <?php endif; ?>

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

            const formQuiz = document.getElementById('formQuiz');
            const avancement = document.getElementById('avancement');

            if (formQuiz && avancement) {
                const total = formQuiz.querySelectorAll('.question').length;

                const compter = function () {
                    const repondues = formQuiz.querySelectorAll('input[type="radio"]:checked').length;
                    avancement.textContent = repondues + ' / ' + total + ' réponse(s)';
                };

                formQuiz.addEventListener('change', compter);
                compter();
            }
        </script>

    </body>

</html>

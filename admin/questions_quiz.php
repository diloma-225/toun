<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$idQuiz = (int) ($_GET['id'] ?? 0);

$stmtQuiz = $con->prepare("
    SELECT q.id_quiz, q.titre_quiz, m.titre_module, f.titre_form
    FROM t_quiz q
    LEFT JOIN t_modules m ON m.id_module = q.id_module
    LEFT JOIN t_formations f ON f.id_form = m.id_form
    WHERE q.id_quiz = ?
    LIMIT 1
");

$stmtQuiz->execute([$idQuiz]);
$quiz = $stmtQuiz->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: quiz.php');
    exit();
}

$erreur = '';
$saisie = ['question' => '', 'reponses' => ['', '', '', ''], 'bonne' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'supprimer') {
        $idQuestion = (int) ($_POST['id_question'] ?? 0);

        $stmt = $con->prepare("DELETE FROM t_reponses WHERE id_question = ?");
        $stmt->execute([$idQuestion]);

        $stmt = $con->prepare("DELETE FROM t_questions WHERE id_question = ? AND id_quiz = ?");
        $stmt->execute([$idQuestion, $idQuiz]);

        $_SESSION['flash_success'] = 'La question a été supprimée.';
        header('Location: questions_quiz.php?id=' . $idQuiz);
        exit();
    }

    if ($action === 'ajouter') {
        $saisie['question'] = trim((string) ($_POST['question'] ?? ''));
        $saisie['reponses'] = array_map(fn ($texte) => trim((string) $texte), array_pad((array) ($_POST['reponses'] ?? []), 4, ''));
        $saisie['bonne'] = (int) ($_POST['bonne'] ?? -1);

        $reponsesRemplies = array_filter($saisie['reponses'], fn ($texte) => $texte !== '');

        if ($saisie['question'] === '') {
            $erreur = 'Veuillez écrire la question.';
        } elseif (count($reponsesRemplies) < 2) {
            $erreur = 'Veuillez écrire au moins deux réponses.';
        } elseif (!isset($reponsesRemplies[$saisie['bonne']])) {
            $erreur = 'Veuillez cocher la bonne réponse.';
        } else {
            $stmtOrdre = $con->prepare("SELECT COALESCE(MAX(ordre_question), 0) + 1 FROM t_questions WHERE id_quiz = ?");
            $stmtOrdre->execute([$idQuiz]);
            $ordre = (int) $stmtOrdre->fetchColumn();

            $stmt = $con->prepare("INSERT INTO t_questions (id_quiz, libelle_question, ordre_question) VALUES (?, ?, ?)");
            $stmt->execute([$idQuiz, $saisie['question'], $ordre]);
            $idQuestion = (int) $con->lastInsertId();

            $stmtReponse = $con->prepare("INSERT INTO t_reponses (id_question, libelle_reponse, est_correcte) VALUES (?, ?, ?)");

            foreach ($reponsesRemplies as $index => $texte) {
                $stmtReponse->execute([$idQuestion, $texte, $index === $saisie['bonne'] ? 1 : 0]);
            }

            $_SESSION['flash_success'] = 'La question a été ajoutée.';
            header('Location: questions_quiz.php?id=' . $idQuiz);
            exit();
        }
    }
}

$stmtQuestions = $con->prepare("
    SELECT id_question, libelle_question, ordre_question
    FROM t_questions
    WHERE id_quiz = ?
    ORDER BY ordre_question ASC, id_question ASC
");

$stmtQuestions->execute([$idQuiz]);
$questions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);

$stmtReponses = $con->prepare("
    SELECT id_reponse, libelle_reponse, est_correcte
    FROM t_reponses
    WHERE id_question = ?
    ORDER BY id_reponse ASC
");

foreach ($questions as &$question) {
    $stmtReponses->execute([$question['id_question']]);
    $question['reponses'] = $stmtReponses->fetchAll(PDO::FETCH_ASSOC);
}

unset($question);
?>

<style>
    :root {
        --qq-nuit: #18213f;
        --qq-bleu: #0b5a91;
        --qq-turquoise: #20a1b8;
        --qq-pale: #eaf3f9;
        --qq-bordure: #dce7ed;
        --qq-doux: #6f8094;
        --qq-vert: #1e8e5a;
        --qq-rouge: #b43d3d;
    }

    .qq-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .qq-entete {
        padding: 16px 21px;
        margin-bottom: 14px;
        border-radius: 15px;
        background: var(--qq-nuit);
        color: #fff;
    }

    .qq-entete h1 {
        margin: 0;
        font-size: 18px;
    }

    .qq-entete p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 11.5px;
    }

    .qq-grille {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 14px;
        align-items: start;
    }

    .qq-carte {
        padding: 16px;
        border: 1px solid var(--qq-bordure);
        border-radius: 14px;
        background: #fff;
    }

    .qq-carte h2 {
        margin: 0 0 12px;
        color: var(--qq-nuit);
        font-size: 13px;
    }

    .qq-vide {
        padding: 20px;
        color: var(--qq-doux);
        font-size: 11.5px;
        text-align: center;
    }

    .qq-question {
        padding: 12px 0;
        border-top: 1px solid var(--qq-bordure);
    }

    .qq-question:first-of-type {
        border-top: 0;
        padding-top: 0;
    }

    .qq-question__haut {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .qq-question__libelle {
        margin: 0 0 8px;
        color: var(--qq-nuit);
        font-size: 12px;
        font-weight: 600;
    }

    .qq-reponse {
        margin: 0 0 4px;
        color: var(--qq-doux);
        font-size: 11.5px;
    }

    .qq-reponse--bonne {
        color: var(--qq-vert);
        font-weight: 600;
    }

    .qq-reponse i {
        width: 14px;
    }

    .qq-supprimer {
        flex-shrink: 0;
        width: 30px;
        height: 30px;
        border: 1px solid #f1d0d0;
        border-radius: 8px;
        background: #fff6f6;
        color: var(--qq-rouge);
        cursor: pointer;
    }

    .qq-alerte {
        padding: 9px 11px;
        margin-bottom: 11px;
        border: 1px solid #f1d0d0;
        border-radius: 8px;
        background: #fff1f1;
        color: var(--qq-rouge);
        font-size: 11.5px;
    }

    .qq-champ {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 11px;
    }

    .qq-champ label,
    .qq-aide {
        color: #53647a;
        font-size: 11px;
        font-weight: 600;
    }

    .qq-champ textarea,
    .qq-choix input[type="text"] {
        width: 100%;
        padding: 9px 10px;
        border: 1px solid #d3e1e7;
        border-radius: 8px;
        outline: 0;
        background: #f9fcfd;
        color: var(--qq-nuit);
        font: 11.5px "Poppins", sans-serif;
        box-sizing: border-box;
    }

    .qq-champ textarea {
        min-height: 70px;
        resize: vertical;
    }

    .qq-choix {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 8px;
    }

    .qq-choix input[type="radio"] {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        accent-color: var(--qq-vert);
    }

    .qq-aide {
        display: block;
        margin-bottom: 8px;
        font-weight: 400;
    }

    .qq-actions {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-top: 14px;
    }

    .qq-bouton {
        height: 36px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 13px;
        border-radius: 8px;
        font: 600 11px "Poppins", sans-serif;
        text-decoration: none;
        cursor: pointer;
    }

    .qq-bouton--retour {
        border: 1px solid #d3e0e6;
        background: #fff;
        color: #5b6a7d;
    }

    .qq-bouton--ajouter {
        border: 0;
        background: var(--qq-bleu);
        color: #fff;
    }

    @media (max-width: 900px) {
        .qq-page {
            padding: 13px 10px 20px;
        }

        .qq-grille {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main">
    <section class="qq-page">
        <header class="qq-entete">
            <h1>Questions : <?= e($quiz['titre_quiz']) ?></h1>
            <p><?= e(trim(($quiz['titre_form'] ?? '') . ' · ' . ($quiz['titre_module'] ?? ''), ' ·')) ?></p>
        </header>

        <div class="qq-grille">
            <section class="qq-carte">
                <h2><?= count($questions) ?> question<?= count($questions) > 1 ? 's' : '' ?></h2>

                <?php if (!$questions): ?>
                    <div class="qq-vide">Aucune question pour le moment. Ajoutez la première avec le formulaire.</div>
                <?php endif; ?>

                <?php foreach ($questions as $index => $question): ?>
                    <article class="qq-question">
                        <div class="qq-question__haut">
                            <p class="qq-question__libelle"><?= $index + 1 ?>. <?= e($question['libelle_question']) ?></p>

                            <form method="post" onsubmit="return confirm('Supprimer cette question ?');">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id_question" value="<?= (int) $question['id_question'] ?>">
                                <button type="submit" class="qq-supprimer" aria-label="Supprimer la question">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>

                        <?php foreach ($question['reponses'] as $reponse): ?>
                            <p class="qq-reponse <?= (int) $reponse['est_correcte'] === 1 ? 'qq-reponse--bonne' : '' ?>">
                                <i class="fa-solid <?= (int) $reponse['est_correcte'] === 1 ? 'fa-check' : 'fa-minus' ?>"></i>
                                <?= e($reponse['libelle_reponse']) ?>
                            </p>
                        <?php endforeach; ?>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="qq-carte">
                <h2>Ajouter une question</h2>

                <?php if ($erreur !== ''): ?>
                    <div class="qq-alerte"><?= e($erreur) ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="action" value="ajouter">

                    <div class="qq-champ">
                        <label for="question">Question</label>
                        <textarea id="question" name="question" required><?= e($saisie['question']) ?></textarea>
                    </div>

                    <span class="qq-aide">Réponses : cochez la bonne réponse (deux réponses minimum).</span>

                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="qq-choix">
                            <input
                                type="radio"
                                name="bonne"
                                value="<?= $i ?>"
                                aria-label="Bonne réponse <?= $i + 1 ?>"
                                <?= $saisie['bonne'] === $i ? 'checked' : '' ?>
                            >
                            <input
                                type="text"
                                name="reponses[<?= $i ?>]"
                                placeholder="Réponse <?= $i + 1 ?>"
                                value="<?= e($saisie['reponses'][$i] ?? '') ?>"
                                <?= $i < 2 ? 'required' : '' ?>
                            >
                        </div>
                    <?php endfor; ?>

                    <div class="qq-actions">
                        <a class="qq-bouton qq-bouton--retour" href="voir_quiz.php?id=<?= (int) $idQuiz ?>">
                            <i class="fa-solid fa-arrow-left"></i>
                            Retour
                        </a>
                        <button type="submit" class="qq-bouton qq-bouton--ajouter">
                            <i class="fa-solid fa-plus"></i>
                            Ajouter la question
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</div>

<?php
$active_page = 'quiz.php';
include 'menu.php';
?>

<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/quiz_config.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$meta = quizDetect($con);
if (!$meta) {
    header('Location: quiz.php');
    exit();
}

$formations = [];
try {
    $formations = $con
        ->query('SELECT id_form, titre_form FROM t_formations ORDER BY titre_form')
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$modulesQuiz = [];
try {
    $modulesQuiz = $con
        ->query('SELECT m.id_module, m.titre_module, f.titre_form FROM t_modules m INNER JOIN t_formations f ON f.id_form = m.id_form ORDER BY f.titre_form, m.ordre_module')
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$skip = ['id', 'id_quiz', 'id_quizzes'];
$fields = [];

foreach ($meta['cols'] as $column) {
    $name = $column['Field'];
    if (in_array(strtolower($name), $skip, true) || quizAuto($column)) {
        continue;
    }
    $fields[] = $column;
}

function inputForQuiz(string $name, $value, array $meta, array $formations, array $modules = []): string
{
    $safe = e($value);

    if ($name === 'id_module' && $modules) {
        $html = '<select name="id_module">';
        $html .= '<option value="">Choisir un module</option>';
        foreach ($modules as $module) {
            $selected = (string) $value === (string) $module['id_module'] ? ' selected' : '';
            $html .= '<option value="' . e($module['id_module']) . '"' . $selected . '>';
            $html .= e($module['titre_form'] . ' — ' . $module['titre_module']);
            $html .= '</option>';
        }
        return $html . '</select>';
    }

    if ($name === $meta['form']) {
        $html = '<select name="' . e($name) . '">';
        $html .= '<option value="">Choisir une formation</option>';
        foreach ($formations as $formation) {
            $selected = (string) $value === (string) $formation['id_form'] ? ' selected' : '';
            $html .= '<option value="' . e($formation['id_form']) . '"' . $selected . '>';
            $html .= e($formation['titre_form']);
            $html .= '</option>';
        }
        return $html . '</select>';
    }

    if (stripos($name, 'description') !== false || stripos($name, 'descrip') !== false) {
        return '<textarea name="' . e($name) . '">' . $safe . '</textarea>';
    }

    if (stripos($name, 'statut') !== false) {
        $options = ['actif' => 'Actif', 'brouillon' => 'Brouillon', 'archive' => 'Archivé'];
        $html = '<select name="' . e($name) . '">';
        foreach ($options as $option => $label) {
            $selected = strtolower((string) $value) === $option ? ' selected' : '';
            $html .= '<option value="' . e($option) . '"' . $selected . '>' . e($label) . '</option>';
        }
        return $html . '</select>';
    }

    $number =
        stripos($name, 'duree') !== false ||
        stripos($name, 'score') !== false ||
        stripos($name, 'note') !== false ||
        stripos($name, 'point') !== false;

    return '<input type="' . ($number ? 'number' : 'text') . '" name="' . e($name) . '" value="' . $safe . '">';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [];

        foreach ($fields as $column) {
            $name = $column['Field'];
            $value = trim((string) ($_POST[$name] ?? ''));

            if (($column['Null'] ?? 'YES') === 'NO' && ($column['Default'] ?? null) === null && $value === '') {
                throw new RuntimeException('Le champ ' . quizLabel($name) . ' est obligatoire.');
            }

            $data[$name] = $value === '' ? null : $value;
        }

        if (!$data) {
            throw new RuntimeException('Aucun champ à enregistrer.');
        }

        $columns = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql =
            'INSERT INTO `' . $meta['table'] . '` (`' . implode('`,`', $columns) . '`) VALUES (' . $placeholders . ')';

        $stmt = $con->prepare($sql);
        $stmt->execute(array_values($data));

        $_SESSION['flash_success'] = 'Le quiz a été ajouté avec succès.';
        header('Location: quiz.php');
        exit();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>

<style>
    .quiz-form-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .quiz-form-hero {
        padding: 16px 21px;
        margin-bottom: 13px;
        border-radius: 17px;
        background: linear-gradient(110deg, #18213f, #3f68ae 65%, #20a1b8);
        color: #fff;
    }

    .quiz-form-hero h1 {
        margin: 0;
        font-size: 20px;
    }

    .quiz-form-hero p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .quiz-form-card {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px;
        border: 1px solid #dce7ed;
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .quiz-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 11px;
    }

    .quiz-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .quiz-field.full {
        grid-column: 1 / -1;
    }

    .quiz-field label {
        color: #53647a;
        font-size: 10.5px;
        font-weight: 700;
    }

    .quiz-field input,
    .quiz-field select,
    .quiz-field textarea {
        width: 100%;
        min-height: 37px;
        padding: 0 10px;
        border: 1px solid #d3e1e7;
        border-radius: 8px;
        outline: 0;
        background: #f9fcfd;
        color: #18213f;
        font:
            11px "Poppins",
            sans-serif;
    }

    .quiz-field textarea {
        min-height: 82px;
        padding-top: 9px;
        resize: vertical;
    }

    .quiz-form-alert {
        padding: 9px 11px;
        margin-bottom: 11px;
        border: 1px solid #f1d0d0;
        border-radius: 8px;
        background: #fff1f1;
        color: #b43d3d;
        font-size: 11px;
    }

    .quiz-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 7px;
        margin-top: 14px;
    }

    .quiz-form-button {
        height: 36px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 13px;
        border-radius: 8px;
        font:
            600 11px "Poppins",
            sans-serif;
        text-decoration: none;
        cursor: pointer;
    }

    .quiz-form-cancel {
        border: 1px solid #d3e0e6;
        background: #fff;
        color: #5b6a7d;
    }

    .quiz-form-save {
        border: 0;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
    }

    @media (max-width: 650px) {
        .quiz-form-page {
            padding: 13px 10px 20px;
        }

        .quiz-form-grid {
            grid-template-columns: 1fr;
        }

        .quiz-field.full {
            grid-column: auto;
        }
    }
</style>

<div class="main">
    <section class="quiz-form-page">
        <header class="quiz-form-hero">
            <h1>Nouveau quiz</h1>
            <p>Créez une évaluation liée à une formation TOUN.</p>
        </header>

        <section class="quiz-form-card">
            <?php if ($error): ?>
                <div class="quiz-form-alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="quiz-form-grid">
                    <?php foreach ($fields as $column): ?>
                        <?php $full = $column['Field'] === $meta['desc']; ?>
                        <div class="quiz-field <?= $full ? 'full' : '' ?>">
                            <label>
                                <?= e(quizLabel($column['Field'])) ?>
                                <?php if (($column['Null'] ?? 'YES') === 'NO' && ($column['Default'] ?? null) === null): ?>
                                    *
                                <?php endif; ?>
                            </label>
                            <?= inputForQuiz($column['Field'], $_POST[$column['Field']] ?? '', $meta, $formations, $modulesQuiz) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="quiz-form-actions">
                    <a class="quiz-form-button quiz-form-cancel" href="quiz.php">
                        <i class="fa-solid fa-arrow-left"></i>
                        Annuler
                    </a>
                    <button class="quiz-form-button quiz-form-save" type="submit">
                        <i class="fa-solid fa-check"></i>
                        Créer le quiz
                    </button>
                </div>
            </form>
        </section>
    </section>
</div>

<?php
$active_page = 'quiz.php';
include 'menu.php';
?>

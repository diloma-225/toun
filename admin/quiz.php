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

function countRows(PDO $con, string $sql): int
{
    try {
        $stmt = $con->query($sql);
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

$meta = quizDetect($con);
$rows = [];
$total = 0;

if ($meta) {
    $total = countRows($con, "SELECT COUNT(*) FROM `{$meta['table']}`");

    try {
        $rows = $con
            ->query(
                "SELECT * FROM `{$meta['table']}`
             ORDER BY `{$meta['id']}` DESC
             LIMIT 100"
            )
            ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows = [];
    }
}

$formations = countRows($con, 'SELECT COUNT(*) FROM t_formations');
?>

<style>
    .quiz-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .quiz-hero {
        min-height: 94px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 16px 21px;
        margin-bottom: 13px;
        border-radius: 18px;
        background: linear-gradient(110deg, #18213f, #3f68ae 65%, #20a1b8);
        color: #fff;
        box-shadow: 0 9px 22px rgba(24, 33, 63, 0.1);
    }

    .quiz-hero h1 {
        margin: 0;
        font-size: 21px;
    }

    .quiz-hero p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .quiz-new {
        height: 37px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 13px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
    }

    .quiz-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 11px;
        margin-bottom: 13px;
    }

    .quiz-stat {
        height: 82px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 12px;
        border: 1px solid #dce7ed;
        border-left: 4px solid #20a1b8;
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(24, 33, 63, 0.055);
    }

    .quiz-stat:nth-child(2) {
        border-left-color: #3f68ae;
    }

    .quiz-stat:nth-child(3) {
        border-left-color: #8b61c7;
    }

    .quiz-stat:nth-child(4) {
        border-left-color: #20a1b8;
    }

    .quiz-stat-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #e5f5f7;
        color: #20a1b8;
    }

    .quiz-stat:nth-child(2) .quiz-stat-icon {
        background: #eaf0fa;
        color: #3f68ae;
    }

    .quiz-stat:nth-child(3) .quiz-stat-icon {
        background: #f1eaf9;
        color: #8b61c7;
    }

    .quiz-stat:nth-child(4) .quiz-stat-icon {
        background: #eaf3f9;
        color: #0b5a91;
    }

    .quiz-stat small {
        display: block;
        color: #7d8b9b;
        font-size: 10.5px;
    }

    .quiz-stat strong {
        display: block;
        margin-top: 2px;
        color: #18213f;
        font-size: 18px;
    }

    .quiz-panel {
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .quiz-panel-head {
        height: 52px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 15px;
        border-bottom: 1px solid #dce7ed;
        background: #f1f7f9;
    }

    .quiz-panel-head strong {
        font-size: 12px;
    }

    .quiz-panel-head span {
        color: #8290a0;
        font-size: 10.5px;
    }

    .quiz-search {
        width: 300px;
        height: 36px;
        margin: 11px 14px;
        padding: 0 10px;
        border: 1px solid #d2e0e7;
        border-radius: 9px;
        outline: 0;
        background: #fff;
        color: #18213f;
        font:
            11px "Poppins",
            sans-serif;
    }

    .quiz-table-wrap {
        overflow-x: auto;
    }

    .quiz-table {
        width: 100%;
        border-collapse: collapse;
    }

    .quiz-table th {
        padding: 10px 13px;
        background: #e5eff3;
        color: #536a7f;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .quiz-table td {
        padding: 10px 13px;
        border-top: 1px solid #ecf1f4;
        color: #45566a;
        font-size: 11px;
    }

    .quiz-table td strong {
        color: #18213f;
    }

    .quiz-badge {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        background: #e5f5f7;
        color: #168192;
        font-size: 10px;
        font-weight: 700;
    }

    .quiz-actions {
        display: flex;
        gap: 5px;
    }

    .quiz-action {
        width: 28px;
        height: 28px;
        display: grid;
        place-items: center;
        border-radius: 7px;
        text-decoration: none;
    }

    .quiz-action.view {
        background: #e6f6f7;
        color: #20a1b8;
    }

    .quiz-action.edit {
        background: #eaf0fa;
        color: #3f68ae;
    }

    .quiz-action.delete {
        background: #fdebec;
        color: #c34a4a;
    }

    .quiz-empty {
        padding: 45px 20px;
        text-align: center;
        color: #8a97a6;
        font-size: 11px;
        background: #f7fafb;
    }

    .quiz-empty i {
        display: block;
        margin-bottom: 8px;
        color: #20a1b8;
        font-size: 27px;
    }

    @media (max-width: 900px) {
        .quiz-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .quiz-page {
            padding: 13px 10px 20px;
        }

        .quiz-hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .quiz-stats {
            grid-template-columns: 1fr;
        }

        .quiz-search {
            width: calc(100% - 28px);
        }
    }
</style>

<div class="main">
    <section class="quiz-page">
        <header class="quiz-hero">
            <div>
                <h1>Quiz & évaluations</h1>
                <p>Gérez les évaluations associées aux formations TOUN.</p>
            </div>
            <a class="quiz-new" href="ajouter_quiz.php">
                <i class="fa-solid fa-plus"></i>
                Nouveau quiz
            </a>
        </header>

        <div class="quiz-stats">
            <div class="quiz-stat">
                <div class="quiz-stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                <div>
                    <small>Total quiz</small>
                    <strong><?= $total ?></strong>
                </div>
            </div>
            <div class="quiz-stat">
                <div class="quiz-stat-icon"><i class="fa-solid fa-circle-question"></i></div>
                <div>
                    <small>Évaluations</small>
                    <strong><?= $total ?></strong>
                </div>
            </div>
            <div class="quiz-stat">
                <div class="quiz-stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <div>
                    <small>Formations</small>
                    <strong><?= $formations ?></strong>
                </div>
            </div>
            <div class="quiz-stat">
                <div class="quiz-stat-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <small>État</small>
                    <strong><?= $meta ? 'Actif' : 'À configurer' ?></strong>
                </div>
            </div>
        </div>

        <section class="quiz-panel">
            <div class="quiz-panel-head">
                <strong>Liste des quiz</strong>
                <span><?= $total ?> élément(s)</span>
            </div>

            <input
                id="quizSearch"
                class="quiz-search"
                type="search"
                placeholder="Rechercher un quiz..."
                autocomplete="off"
            >

            <?php if (!$meta): ?>
                <div class="quiz-empty">
                    <i class="fa-solid fa-clipboard-check"></i>
                    Aucune table de quiz détectée dans la base.
                </div>
            <?php elseif (!$rows): ?>
                <div class="quiz-empty">
                    <i class="fa-solid fa-clipboard-check"></i>
                    Aucun quiz enregistré pour le moment.
                </div>
            <?php else: ?>
                <div class="quiz-table-wrap">
                    <table class="quiz-table" id="quizTable">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Formation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <?php
$title = quizValue($row, $meta['title']);
$formation = quizValue($row, $meta['form']);
$status = quizValue($row, $meta['status']);
?>
                                <tr>
                                    <td>
                                        <strong><?= e($title ?: 'Quiz #' . $row[$meta['id']]) ?></strong>
                                    </td>
                                    <td><?= e($formation ?: '—') ?></td>
                                    <td>
                                        <span class="quiz-badge">
                                            <?= e($status ?: 'Actif') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="quiz-actions">
                                            <a class="quiz-action view" href="voir_quiz.php?id=<?= urlencode($row[$meta['id']]) ?>" title="Voir">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a class="quiz-action edit" href="modifier_quiz.php?id=<?= urlencode($row[$meta['id']]) ?>" title="Modifier">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <a class="quiz-action delete" href="supprimer_quiz.php?id=<?= urlencode($row[$meta['id']]) ?>" title="Supprimer" onclick="return confirm('Supprimer ce quiz ?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </section>
</div>

<script>
    document.getElementById('quizSearch')?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();

        document.querySelectorAll('#quizTable tbody tr').forEach(function (row) {
            row.style.display = !query || row.innerText.toLowerCase().includes(query) ? '' : 'none';
        });
    });
</script>

<?php
$active_page = 'quiz.php';
include 'menu.php';
?>

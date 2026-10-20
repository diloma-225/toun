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

try {
    $con->exec(
        "CREATE TABLE IF NOT EXISTS t_lives (
            id_live INT UNSIGNED NOT NULL AUTO_INCREMENT,
            titre VARCHAR(180) NOT NULL,
            id_formation INT UNSIGNED NULL,
            formation VARCHAR(150) NULL,
            formateur VARCHAR(150) NULL,
            date_live DATE NOT NULL,
            heure_live TIME NOT NULL,
            duree VARCHAR(30) NULL,
            lien_live VARCHAR(255) NULL,
            id_user INT UNSIGNED NULL,
            date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_live),
            INDEX idx_live_date (date_live)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
}

$rows = [];
try {
    $rows = $con->query('SELECT * FROM t_lives ORDER BY date_live ASC, heure_live ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$aujourdhui = date('Y-m-d');
$aVenir = count(array_filter($rows, fn($row) => ($row['date_live'] ?? '') >= $aujourdhui));
?>

<style>
    .live-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .live-hero {
        min-height: 94px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 17px 21px;
        margin-bottom: 13px;
        border-radius: 18px;
        background: linear-gradient(110deg, #18213f, #3f68ae 60%, #20a1b8);
        color: #fff;
        box-shadow: 0 9px 22px rgba(24, 33, 63, 0.1);
    }

    .live-hero h1 {
        margin: 0;
        font-size: 21px;
    }

    .live-hero p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .live-hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .live-count {
        min-width: 31px;
        height: 31px;
        display: grid;
        place-items: center;
        padding: 0 9px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.12);
        font-size: 11px;
    }

    .live-add {
        height: 35px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 14px;
        border-radius: 9px;
        background: #fff;
        color: #18213f;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
    }

    .live-panel {
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .live-table-wrap {
        overflow-x: auto;
    }

    .live-table {
        width: 100%;
        border-collapse: collapse;
    }

    .live-table th {
        padding: 10px 13px;
        background: #e5eff3;
        color: #536a7f;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .live-table td {
        padding: 11px 13px;
        border-top: 1px solid #ecf1f4;
        color: #45566a;
        font-size: 11px;
    }

    .live-table td strong {
        color: #18213f;
    }

    .live-statut {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        background: #e5f6f7;
        color: #158090;
        font-size: 10px;
        font-weight: 700;
    }

    .live-statut.passe {
        background: #edf1f6;
        color: #708096;
    }

    .live-lien {
        color: #3f68ae;
        font-size: 11px;
        text-decoration: none;
    }

    .live-action {
        width: 28px;
        height: 28px;
        display: inline-grid;
        place-items: center;
        border-radius: 7px;
        background: #eaf0fa;
        color: #3f68ae;
        text-decoration: none;
    }

    .live-action.delete {
        background: #fdebec;
        color: #c34a4a;
    }

    .live-empty {
        padding: 45px 20px;
        text-align: center;
        color: #8996a5;
        font-size: 11px;
        background: #f7fafb;
    }

    .live-empty i {
        display: block;
        margin-bottom: 8px;
        color: #20a1b8;
        font-size: 27px;
    }

    @media (max-width: 600px) {
        .live-page {
            padding: 13px 10px 20px;
        }

        .live-hero {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
</style>

<div class="main">
    <section class="live-page">
        <header class="live-hero">
            <div>
                <h1>Sessions live</h1>
                <p>Programmez et gérez les classes en direct visibles par les apprenants dans "Mes Lives".</p>
            </div>
            <div class="live-hero-actions">
                <span class="live-count"><?= $aVenir ?> à venir</span>
                <a class="live-add" href="ajouter_live.php">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter une session
                </a>
            </div>
        </header>

        <section class="live-panel">
            <div class="live-table-wrap">
                <table class="live-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Formation</th>
                            <th>Formateur</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Lien</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="live-empty">
                                        <i class="fa-solid fa-video"></i>
                                        Aucune session live programmée pour le moment.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php $estPasse = ($row['date_live'] ?? '') < $aujourdhui; ?>
                                <tr>
                                    <td><strong><?= e($row['titre'] ?? '—') ?></strong></td>
                                    <td><?= e($row['formation'] ?? '—') ?></td>
                                    <td><?= e($row['formateur'] ?? '—') ?></td>
                                    <td><?= !empty($row['date_live']) ? e(date('d/m/Y', strtotime($row['date_live']))) : '—' ?></td>
                                    <td><?= !empty($row['heure_live']) ? e(substr($row['heure_live'], 0, 5)) : '—' ?></td>
                                    <td>
                                        <?php if (!empty($row['lien_live'])): ?>
                                            <a class="live-lien" href="<?= e($row['lien_live']) ?>" target="_blank" rel="noopener">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Lien
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="live-statut <?= $estPasse ? 'passe' : '' ?>">
                                            <?= $estPasse ? 'terminé' : 'à venir' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="live-action" href="modifier_live.php?id=<?= (int) $row['id_live'] ?>" title="Modifier">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a class="live-action delete" href="supprimer_live.php?id=<?= (int) $row['id_live'] ?>" title="Supprimer" onclick="return confirm('Supprimer cette session live ?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>

<?php
$active_page = 'lives.php';
include 'menu.php';
?>

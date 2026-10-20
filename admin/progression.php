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

function query(PDO $con, string $sql, array $params = []): ?PDOStatement
{
    try {
        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (Throwable $e) {
        return null;
    }
}

function countRows(PDO $con, string $sql): int
{
    $stmt = query($con, $sql);
    return $stmt ? (int) $stmt->fetchColumn() : 0;
}

$total = countRows($con, 'SELECT COUNT(*) FROM t_progression');
$completed = countRows($con, 'SELECT COUNT(*) FROM t_progression WHERE pourcent_avancement >= 100');

$stmt = query($con, 'SELECT COALESCE(AVG(pourcent_avancement), 0) FROM t_progression');
$average = $stmt ? (float) $stmt->fetchColumn() : 0;

$rows = [];
$stmt = query(
    $con,
    "SELECT
        p.id_progression,
        p.pourcent_avancement,
        p.date_debut,
        p.date_derniere_connexion,
        p.statut_progression,
        u.nom_user,
        u.prenom_user,
        f.titre_form
     FROM t_progression p
     LEFT JOIN t_inscriptions i ON i.id_inscrip = p.id_inscrip
     LEFT JOIN t_utilisateurs u ON u.id_user = i.id_user
     LEFT JOIN t_formations f ON f.id_form = i.id_form
     ORDER BY p.date_derniere_connexion DESC, p.id_progression DESC
     LIMIT 100"
);

if ($stmt) {
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$learners = count(
    array_unique(
        array_filter(array_map(fn($row) => trim(($row['prenom_user'] ?? '') . ' ' . ($row['nom_user'] ?? '')), $rows))
    )
);
?>

<style>
    .progress-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eaf5f5;
        font-family: "Poppins", Arial, sans-serif;
    }

    .progress-head {
        padding: 17px 20px;
        margin-bottom: 13px;
        border-left: 6px solid #20a1b8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .progress-head h1 {
        margin: 0;
        color: #18213f;
        font-size: 21px;
    }

    .progress-head p {
        margin: 4px 0 0;
        color: #7b899b;
        font-size: 11px;
    }

    .progress-stats {
        display: grid;
        grid-template-columns: 1.1fr 1fr 1fr 1.1fr;
        gap: 11px;
        margin-bottom: 13px;
    }

    .progress-stat {
        height: 82px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 12px;
        border: 1px solid #dde8ed;
        border-bottom: 3px solid #20a1b8;
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(24, 33, 63, 0.055);
    }

    .progress-stat:nth-child(2) {
        border-bottom-color: #3f68ae;
    }

    .progress-stat:nth-child(3) {
        border-bottom-color: #8b61c7;
    }

    .progress-stat:nth-child(4) {
        border-bottom-color: #20a1b8;
    }

    .progress-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #e4f5f6;
        color: #20a1b8;
    }

    .progress-stat:nth-child(2) .progress-icon {
        background: #eaf0fa;
        color: #3f68ae;
    }

    .progress-stat:nth-child(3) .progress-icon {
        background: #f1eaf9;
        color: #8b61c7;
    }

    .progress-stat:nth-child(4) .progress-icon {
        background: #eaf3f9;
        color: #0b5a91;
    }

    .progress-stat small {
        display: block;
        color: #7c8999;
        font-size: 10.5px;
    }

    .progress-stat strong {
        display: block;
        margin-top: 2px;
        color: #18213f;
        font-size: 18px;
    }

    .progress-panel {
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .progress-toolbar {
        min-height: 58px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: #f1f7f9;
        border-bottom: 1px solid #dce7ed;
    }

    .progress-search {
        width: 300px;
        height: 36px;
        padding: 0 10px;
        border: 1px solid #d2e0e7;
        border-radius: 9px;
        outline: 0;
        background: #fff;
        font:
            11px "Poppins",
            sans-serif;
    }

    .progress-table-wrap {
        overflow-x: auto;
    }

    .progress-table {
        width: 100%;
        border-collapse: collapse;
    }

    .progress-table th {
        padding: 10px 13px;
        background: #18213f;
        color: #fff;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .progress-table td {
        padding: 10px 13px;
        border-top: 1px solid #ecf1f4;
        color: #45566a;
        font-size: 11px;
    }

    .progress-table td strong {
        color: #18213f;
    }

    .progress-bar {
        width: 150px;
        height: 7px;
        overflow: hidden;
        border-radius: 20px;
        background: #e4edf1;
    }

    .progress-bar i {
        display: block;
        height: 100%;
        border-radius: 20px;
        background: linear-gradient(90deg, #20a1b8, #3f68ae);
    }

    .progress-badge {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        background: #e5f6f7;
        color: #158090;
        font-size: 10px;
        font-weight: 700;
    }

    .progress-empty {
        padding: 45px 20px;
        text-align: center;
        color: #8996a5;
        font-size: 11px;
        background: #f7fafb;
    }

    .progress-empty i {
        display: block;
        margin-bottom: 8px;
        color: #20a1b8;
        font-size: 27px;
    }

    @media (max-width: 900px) {
        .progress-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .progress-page {
            padding: 13px 10px 20px;
        }

        .progress-stats {
            grid-template-columns: 1fr;
        }

        .progress-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .progress-search {
            width: 100%;
        }
    }
</style>

<div class="main">
    <section class="progress-page">
        <header class="progress-head">
            <h1>Progression</h1>
            <p>Suivez l’avancement des apprenants dans leurs formations.</p>
        </header>

        <div class="progress-stats">
            <div class="progress-stat">
                <div class="progress-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div><small>Suivis</small><strong><?= $total ?></strong></div>
            </div>
            <div class="progress-stat">
                <div class="progress-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div><small>Terminées</small><strong><?= $completed ?></strong></div>
            </div>
            <div class="progress-stat">
                <div class="progress-icon"><i class="fa-solid fa-percent"></i></div>
                <div><small>Moyenne</small><strong><?= number_format($average, 0) ?>%</strong></div>
            </div>
            <div class="progress-stat">
                <div class="progress-icon"><i class="fa-solid fa-users"></i></div>
                <div><small>Apprenants</small><strong><?= $learners ?></strong></div>
            </div>
        </div>

        <section class="progress-panel">
            <div class="progress-toolbar">
                <input id="progressSearch" class="progress-search" type="search" placeholder="Rechercher un apprenant ou une formation...">
            </div>

            <div class="progress-table-wrap">
                <table class="progress-table" id="progressTable">
                    <thead>
                        <tr>
                            <th>Apprenant</th>
                            <th>Formation</th>
                            <th>Avancement</th>
                            <th>Statut</th>
                            <th>Dernière activité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="progress-empty">
                                        <i class="fa-solid fa-chart-line"></i>
                                        Aucune progression enregistrée pour le moment.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php $percent = max(0, min(100, (float) ($row['pourcent_avancement'] ?? 0))); ?>
                                <tr>
                                    <td><strong><?= e(trim(($row['prenom_user'] ?? '') . ' ' . ($row['nom_user'] ?? ''))) ?></strong></td>
                                    <td><?= e($row['titre_form'] ?? '—') ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <i style="width: <?= $percent ?>%"></i>
                                        </div>
                                        <small><?= number_format($percent, 0) ?>%</small>
                                    </td>
                                    <td><span class="progress-badge"><?= e($row['statut_progression'] ?? 'En cours') ?></span></td>
                                    <td><?= e($row['date_derniere_connexion'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>

<script>
    document.getElementById('progressSearch')?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();

        document.querySelectorAll('#progressTable tbody tr').forEach(function (row) {
            row.style.display = !query || row.innerText.toLowerCase().includes(query) ? '' : 'none';
        });
    });
</script>

<?php
$active_page = 'progression.php';
include 'menu.php';
?>

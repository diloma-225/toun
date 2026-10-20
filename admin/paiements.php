<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../includes/moyens_paiement.php';

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

$total = countRows($con, 'SELECT COUNT(*) FROM t_paiements');
$pending = countRows(
    $con,
    "SELECT COUNT(*) FROM t_paiements WHERE LOWER(statut_paiement) IN ('en_attente', 'en attente', 'pending')"
);

$stmt = query(
    $con,
    "SELECT COALESCE(SUM(montant_total), 0) FROM t_paiements WHERE LOWER(statut_paiement) IN ('paye', 'payé', 'valide', 'validé', 'success', 'paid')"
);
$revenue = $stmt ? (float) $stmt->fetchColumn() : 0;

$rows = [];
$stmt = query(
    $con,
    "SELECT
        p.id_paiement,
        p.montant_total AS montant,
        p.mode_paiement,
        p.ref_paiement,
        p.statut_paiement,
        p.date_paiement,
        u.nom_user,
        u.prenom_user,
        f.titre_form
     FROM t_paiements p
     LEFT JOIN t_utilisateurs u ON u.id_user = p.id_user
     LEFT JOIN t_formations f ON f.id_form = p.id_form
     ORDER BY p.date_paiement DESC, p.id_paiement DESC
     LIMIT 100"
);

if ($stmt) {
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$references = count(array_filter($rows, fn($row) => !empty($row['ref_paiement'])));
?>

<style>
    .payment-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #edf4f7;
        font-family: "Poppins", Arial, sans-serif;
    }

    .payment-hero {
        min-height: 94px;
        display: flex;
        align-items: center;
        padding: 17px 21px;
        margin-bottom: 13px;
        border-radius: 18px;
        background: linear-gradient(110deg, #18213f, #2b4e89 58%, #20a1b8);
        color: #fff;
        box-shadow: 0 9px 22px rgba(24, 33, 63, 0.1);
    }

    .payment-hero h1 {
        margin: 0;
        font-size: 21px;
    }

    .payment-hero p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .payment-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 11px;
        margin-bottom: 13px;
    }

    .payment-stat {
        position: relative;
        height: 82px;
        display: flex;
        align-items: center;
        gap: 10px;
        overflow: hidden;
        padding: 11px 12px;
        border: 1px solid #dce7ed;
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(24, 33, 63, 0.055);
    }

    .payment-stat::after {
        content: "";
        position: absolute;
        width: 65px;
        height: 65px;
        right: -27px;
        bottom: -30px;
        border-radius: 50%;
        background: #eaf5f7;
    }

    .payment-icon {
        position: relative;
        z-index: 1;
        width: 36px;
        height: 36px;
        min-width: 36px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #e7f4f7;
        color: #20a1b8;
    }

    .payment-stat:nth-child(2) .payment-icon {
        background: #eaf0fa;
        color: #3f68ae;
    }

    .payment-stat:nth-child(3) .payment-icon {
        background: #eaf3f9;
        color: #0b5a91;
    }

    .payment-stat:nth-child(4) .payment-icon {
        background: #f1eaf9;
        color: #8b61c7;
    }

    .payment-stat small,
    .payment-stat strong {
        position: relative;
        z-index: 1;
    }

    .payment-stat small {
        display: block;
        color: #7d8b9b;
        font-size: 10.5px;
    }

    .payment-stat strong {
        display: block;
        margin-top: 2px;
        color: #18213f;
        font-size: 17px;
    }

    .payment-panel {
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .payment-toolbar {
        min-height: 58px;
        display: flex;
        align-items: center;
        padding: 10px 14px;
        background: #f1f7f9;
        border-bottom: 1px solid #dce7ed;
    }

    .payment-search {
        width: 320px;
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

    .payment-table-wrap {
        overflow-x: auto;
    }

    .payment-table {
        width: 100%;
        border-collapse: collapse;
    }

    .payment-table th {
        padding: 10px 13px;
        background: #e5eff3;
        color: #536a7f;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .payment-table td {
        padding: 10px 13px;
        border-top: 1px solid #ecf1f4;
        color: #45566a;
        font-size: 11px;
    }

    .payment-method {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .payment-method img {
        width: 22px;
        height: 22px;
        border-radius: 6px;
        border: 1px solid #e3e9ef;
        object-fit: contain;
    }

    .payment-table td strong {
        color: #18213f;
    }

    .payment-badge {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .payment-badge.success {
        background: #e5f6ef;
        color: #16805f;
    }

    .payment-badge.pending {
        background: #eaf3f9;
        color: #0b5a91;
    }

    .payment-badge.danger {
        background: #fdebec;
        color: #b53f3f;
    }

    .payment-empty {
        padding: 45px 20px;
        text-align: center;
        color: #8996a5;
        font-size: 11px;
        background: #f7fafb;
    }

    .payment-empty i {
        display: block;
        margin-bottom: 8px;
        color: #20a1b8;
        font-size: 27px;
    }

    @media (max-width: 900px) {
        .payment-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .payment-page {
            padding: 13px 10px 20px;
        }

        .payment-stats {
            grid-template-columns: 1fr;
        }

        .payment-search {
            width: 100%;
        }
    }
</style>

<div class="main">
    <section class="payment-page">
        <header class="payment-hero">
            <div>
                <h1>Paiements</h1>
                <p>Suivez les paiements liés aux inscriptions aux formations.</p>
            </div>
        </header>

        <div class="payment-stats">
            <div class="payment-stat">
                <div class="payment-icon"><i class="fa-solid fa-credit-card"></i></div>
                <div><small>Paiements</small><strong><?= $total ?></strong></div>
            </div>
            <div class="payment-stat">
                <div class="payment-icon"><i class="fa-solid fa-coins"></i></div>
                <div><small>Recettes encaissées</small><strong><?= number_format($revenue, 0, ',', ' ') ?> FCFA</strong></div>
            </div>
            <div class="payment-stat">
                <div class="payment-icon"><i class="fa-solid fa-clock"></i></div>
                <div><small>En attente</small><strong><?= $pending ?></strong></div>
            </div>
            <div class="payment-stat">
                <div class="payment-icon"><i class="fa-solid fa-receipt"></i></div>
                <div><small>Références</small><strong><?= $references ?></strong></div>
            </div>
        </div>

        <section class="payment-panel">
            <div class="payment-toolbar">
                <input id="paymentSearch" class="payment-search" type="search" placeholder="Rechercher un paiement...">
            </div>

            <div class="payment-table-wrap">
                <table class="payment-table" id="paymentTable">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Formation</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Référence</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="payment-empty">
                                        <i class="fa-solid fa-credit-card"></i>
                                        Aucun paiement enregistré.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
$status = strtolower(trim((string) ($row['statut_paiement'] ?? '')));
$isSuccess = in_array($status, ['paye', 'payé', 'valide', 'validé', 'success', 'paid'], true);
$isPending = in_array($status, ['en_attente', 'en attente', 'pending'], true);
?>
                                <tr>
                                    <td><strong><?= e(trim(($row['prenom_user'] ?? '') . ' ' . ($row['nom_user'] ?? ''))) ?></strong></td>
                                    <td><?= e($row['titre_form'] ?? '—') ?></td>
                                    <td><?= number_format((float) ($row['montant'] ?? 0), 0, ',', ' ') ?> FCFA</td>
                                    <td>
                                        <?php $moyen = tounMoyenPaiement($row['mode_paiement'] ?? '', '../'); ?>
                                        <span class="payment-method">
                                            <?php if ($moyen['logo'] !== ''): ?>
                                                <img src="<?= e($moyen['logo']) ?>" alt="<?= e($moyen['nom']) ?>">
                                            <?php endif; ?>
                                            <?= e($moyen['nom']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($row['ref_paiement'] ?? '—') ?></td>
                                    <td>
                                        <span class="payment-badge <?= $isSuccess ? 'success' : ($isPending ? 'pending' : 'danger') ?>">
                                            <?= e($row['statut_paiement'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td><?= e($row['date_paiement'] ?? '—') ?></td>
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
    document.getElementById('paymentSearch')?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();

        document.querySelectorAll('#paymentTable tbody tr').forEach(function (row) {
            row.style.display = !query || row.innerText.toLowerCase().includes(query) ? '' : 'none';
        });
    });
</script>

<?php
$active_page = 'paiements.php';
include 'menu.php';
?>

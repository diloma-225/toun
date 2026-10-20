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

function countRows(PDO $con, string $sql): int
{
    try {
        $stmt = $con->query($sql);
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

function scalar(PDO $con, string $sql): float
{
    try {
        $stmt = $con->query($sql);
        return $stmt ? (float) $stmt->fetchColumn() : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

$users = countRows($con, 'SELECT COUNT(*) FROM t_utilisateurs');
$learners = countRows(
    $con,
    "SELECT COUNT(*) FROM t_utilisateurs WHERE LOWER(role_user) IN ('apprenant', 'professionnel')"
);
$formations = countRows($con, 'SELECT COUNT(*) FROM t_formations');
$entreprises = countRows($con, 'SELECT COUNT(*) FROM t_entreprises');
$formateurs = countRows($con, 'SELECT COUNT(*) FROM t_formateurs');
$inscriptions = countRows($con, 'SELECT COUNT(*) FROM t_inscriptions');
$certificats = countRows($con, 'SELECT COUNT(*) FROM t_certificats');
$revenus = scalar(
    $con,
    "SELECT COALESCE(SUM(montant_total), 0) FROM t_paiements WHERE LOWER(statut_paiement) IN ('paye', 'payé', 'valide', 'validé', 'success', 'paid')"
);

$activeUsers = countRows($con, "SELECT COUNT(*) FROM t_utilisateurs WHERE LOWER(statut_user) = 'actif'");
?>

<style>
    .stats-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .stats-title {
        margin: 0 0 11px;
        color: #18213f;
    }

    .stats-title h1 {
        margin: 0;
        font-size: 19px;
    }

    .stats-title p {
        margin: 3px 0 0;
        color: #758397;
        font-size: 11px;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
        margin-bottom: 14px;
    }

    .stats-card {
        position: relative;
        height: 128px;
        overflow: hidden;
        padding: 14px 16px;
        border-radius: 16px;
        color: #fff;
    }

    .stats-card::after {
        content: "";
        position: absolute;
        width: 105px;
        height: 105px;
        right: -30px;
        bottom: -40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .stats-card::before {
        content: "";
        position: absolute;
        width: 62px;
        height: 62px;
        right: 25px;
        bottom: 9px;
        border-radius: 50%;
        border: 14px solid rgba(255, 255, 255, 0.07);
    }

    .stats-card.users {
        background: #18213f;
    }

    .stats-card.learners {
        background: #20a1b8;
    }

    .stats-card.forms {
        background: #3f68ae;
    }

    .stats-card.companies {
        background: linear-gradient(135deg, #3f68ae, #20a1b8);
    }

    .stats-card.trainers {
        background: linear-gradient(135deg, #7653b5, #9a68d4);
    }

    .stats-icon {
        position: relative;
        z-index: 2;
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        margin-bottom: 9px;
        border-radius: 11px;
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        font-size: 15px;
    }

    .stats-label,
    .stats-value {
        position: relative;
        z-index: 2;
    }

    .stats-label {
        display: block;
        font-size: 12px;
        font-weight: 500;
    }

    .stats-value {
        display: block;
        margin-top: 2px;
        font-size: 25px;
        line-height: 1.05;
        font-weight: 700;
    }

    .stats-lower {
        display: grid;
        grid-template-columns: 1.4fr 0.8fr;
        gap: 18px;
    }

    .stats-panel {
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(24, 33, 63, 0.06);
    }

    .stats-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 16px;
        border-bottom: 1px solid #e4edf1;
        background: #f3f8fa;
    }

    .stats-panel-head strong {
        font-size: 12px;
    }

    .stats-panel-head span {
        color: #8995a4;
        font-size: 10.5px;
    }

    .stats-mini-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 9px;
        padding: 13px;
    }

    .stats-mini {
        padding: 12px;
        border-radius: 11px;
        background: #f0f7f9;
        border-left: 4px solid #20a1b8;
    }

    .stats-mini:nth-child(2) {
        border-left-color: #3f68ae;
    }

    .stats-mini:nth-child(3) {
        border-left-color: #8a63c7;
    }

    .stats-mini:nth-child(4) {
        border-left-color: #20a1b8;
    }

    .stats-mini small {
        display: block;
        color: #7c8999;
        font-size: 10.5px;
    }

    .stats-mini strong {
        display: block;
        margin-top: 3px;
        color: #18213f;
        font-size: 14px;
    }

    .stats-ratios {
        padding: 14px;
    }

    .ratio-line {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        color: #627286;
        font-size: 11px;
    }

    .ratio-bar {
        height: 8px;
        overflow: hidden;
        margin-bottom: 12px;
        border-radius: 20px;
        background: #e5eef2;
    }

    .ratio-bar i {
        display: block;
        height: 100%;
        border-radius: 20px;
        background: linear-gradient(90deg, #20a1b8, #3f68ae);
    }

    @media (max-width: 1150px) {
        .stats-cards {
            grid-template-columns: repeat(3, 1fr);
        }

        .stats-lower {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .stats-page {
            padding: 14px 12px 22px;
        }

        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .stats-card {
            height: 128px;
            padding: 14px 16px;
            border-radius: 16px;
        }

        .stats-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 9px;
            font-size: 15px;
        }

        .stats-label {
            font-size: 12px;
        }

        .stats-value {
            font-size: 25px;
        }

        .stats-mini-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 450px) {
        .stats-cards,
        .stats-mini-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main">
    <section class="stats-page">
        <div class="stats-title">
            <h1>Statistiques</h1>
            <p>Vue globale de l’activité de la plateforme TOUN.</p>
        </div>

        <div class="stats-cards">
            <article class="stats-card users">
                <div class="stats-icon"><i class="fa-solid fa-users"></i></div>
                <span class="stats-label">Utilisateurs</span>
                <strong class="stats-value"><?= $users ?></strong>
            </article>

            <article class="stats-card learners">
                <div class="stats-icon"><i class="fa-solid fa-user-graduate"></i></div>
                <span class="stats-label">Apprenants</span>
                <strong class="stats-value"><?= $learners ?></strong>
            </article>

            <article class="stats-card forms">
                <div class="stats-icon"><i class="fa-solid fa-book-open"></i></div>
                <span class="stats-label">Formations</span>
                <strong class="stats-value"><?= $formations ?></strong>
            </article>

            <article class="stats-card companies">
                <div class="stats-icon"><i class="fa-solid fa-building"></i></div>
                <span class="stats-label">Entreprises</span>
                <strong class="stats-value"><?= $entreprises ?></strong>
            </article>

            <article class="stats-card trainers">
                <div class="stats-icon"><i class="fa-solid fa-user-tie"></i></div>
                <span class="stats-label">Formateurs</span>
                <strong class="stats-value"><?= $formateurs ?></strong>
            </article>
        </div>

        <div class="stats-lower">
            <section class="stats-panel">
                <div class="stats-panel-head">
                    <strong>Indicateurs complémentaires</strong>
                    <span>Données actuelles</span>
                </div>
                <div class="stats-mini-grid">
                    <div class="stats-mini">
                        <small>Inscriptions</small>
                        <strong><?= $inscriptions ?></strong>
                    </div>
                    <div class="stats-mini">
                        <small>Certificats</small>
                        <strong><?= $certificats ?></strong>
                    </div>
                    <div class="stats-mini">
                        <small>Comptes actifs</small>
                        <strong><?= $activeUsers ?></strong>
                    </div>
                    <div class="stats-mini">
                        <small>Recettes</small>
                        <strong><?= number_format($revenus, 0, ',', ' ') ?> F</strong>
                    </div>
                </div>
            </section>

            <section class="stats-panel">
                <div class="stats-panel-head">
                    <strong>Suivi</strong>
                    <span>Pourcentage</span>
                </div>
                <div class="stats-ratios">
                    <?php $activeRate = $users ? min(100, round(($activeUsers / $users) * 100)) : 0; ?>
                    <div class="ratio-line">
                        <span>Comptes actifs</span>
                        <strong><?= $activeRate ?>%</strong>
                    </div>
                    <div class="ratio-bar"><i style="width: <?= $activeRate ?>%"></i></div>

                    <?php $certRate = $inscriptions ? min(100, round(($certificats / $inscriptions) * 100)) : 0; ?>
                    <div class="ratio-line">
                        <span>Certification</span>
                        <strong><?= $certRate ?>%</strong>
                    </div>
                    <div class="ratio-bar"><i style="width: <?= $certRate ?>%"></i></div>
                </div>
            </section>
        </div>
    </section>
</div>

<?php
$active_page = 'statistiques.php';
include 'menu.php';
?>

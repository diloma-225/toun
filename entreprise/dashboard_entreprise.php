<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'dashboard_entreprise.php';
$flash = getFlash();

$collabs = entrepriseCollaborators($con, $idEntreprise);
$totalCollabs = count($collabs);
$activeCollabs = count(array_filter($collabs, fn($r) => strtolower((string) $r['statut']) === 'actif'));
$inactiveCollabs = max(0, $totalCollabs - $activeCollabs);

$purchased = enterprisePurchasedFormations($con, $userId);
$totalPurchased = count($purchased);

$assigned = 0;
$completed = 0;
$avgProgress = 0;
$evalTotal = 0;
$evalSuccess = 0;
$paymentTotal = 0;

foreach ($purchased as $formation) {
    $paymentTotal += (float) ($formation['montant_total'] ?? 0);
}

if ($totalCollabs) {
    $userIds = array_map('intval', array_column($collabs, 'id_user'));
    $phUsers = implode(',', array_fill(0, count($userIds), '?'));
    $params = $userIds;
    $stmt = $con->prepare(
        "SELECT COUNT(*) FROM t_inscriptions WHERE id_user IN ($phUsers) AND statut_inscrip IN ('active','terminee','terminée')"
    );
    $stmt->execute($params);
    $assigned = (int) $stmt->fetchColumn();

    $stmt = $con->prepare(
        "SELECT COUNT(*) FROM t_inscriptions WHERE id_user IN ($phUsers) AND statut_inscrip IN ('terminee','terminée')"
    );
    $stmt->execute($params);
    $completed = (int) $stmt->fetchColumn();
}

$progressCol = getProgressColumn($con);
if ($progressCol && $totalCollabs) {
    $userIds = array_map('intval', array_column($collabs, 'id_user'));
    $phUsers = implode(',', array_fill(0, count($userIds), '?'));

    if (columnExists($con, 't_progression', 'id_user')) {
        $stmt = $con->prepare("SELECT AVG(p.$progressCol) FROM t_progression p WHERE p.id_user IN ($phUsers)");
        $stmt->execute($userIds);
        $avgProgress = (float) $stmt->fetchColumn();
    } elseif (columnExists($con, 't_progression', 'id_inscrip') && tableExists($con, 't_inscriptions')) {
        $stmt = $con->prepare(
            "SELECT AVG(p.$progressCol) FROM t_progression p INNER JOIN t_inscriptions i ON i.id_inscrip=p.id_inscrip WHERE i.id_user IN ($phUsers)"
        );
        $stmt->execute($userIds);
        $avgProgress = (float) $stmt->fetchColumn();
    }
}

if (tableExists($con, 't_resultats_quiz') && $totalCollabs) {
    $userIds = array_map('intval', array_column($collabs, 'id_user'));
    $phUsers = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $con->prepare(
        "SELECT COUNT(*), SUM(CASE WHEN LOWER(COALESCE(statut_resultat,'')) IN ('réussi','reussi','success','valide','validé') THEN 1 ELSE 0 END) FROM t_resultats_quiz WHERE id_user IN ($phUsers)"
    );
    $stmt->execute($userIds);
    [$evalTotal, $evalSuccess] = $stmt->fetch(PDO::FETCH_NUM) ?: [0, 0];
}

$completionRate = $assigned > 0 ? round(($completed / $assigned) * 100) : 0;
$successRate = $evalTotal > 0 ? round(((int) $evalSuccess / (int) $evalTotal) * 100) : 0;
$recentPurchases = array_slice($purchased, 0, 4);

$activites = [];

foreach ($collabs as $collaborateur) {
    if (empty($collaborateur['date_ajout'])) {
        continue;
    }
    $activites[] = [
        'date' => $collaborateur['date_ajout'],
        'icone' => 'fa-user-plus',
        'couleur' => 'blue',
        'texte' => trim($collaborateur['prenom_user'] . ' ' . $collaborateur['nom_user']) . ' a rejoint votre équipe'
    ];
}

foreach ($purchased as $formation) {
    $dateAchat = $formation['payment_date'] ?? ($formation['date_paiement'] ?? null);
    if (empty($dateAchat)) {
        continue;
    }
    $activites[] = [
        'date' => $dateAchat,
        'icone' => 'fa-circle-check',
        'couleur' => 'green',
        'texte' => 'Formation « ' . $formation['titre_form'] . ' » achetée'
    ];
}

usort($activites, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
$activites = array_slice($activites, 0, 5);
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Tableau de bord entreprise | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: Poppins, sans-serif;
                background: #f3f7fa;
                color: #173d62;
            }

            a {
                text-decoration: none;
            }

            .dashboard {
                padding: 20px 22px 30px;
            }

            .dashboard-head {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 15px;
            }

            .eyebrow {
                display: block;
                color: #0d8197;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 2px;
                text-transform: uppercase;
                margin-bottom: 5px;
            }

            .dashboard-head h1 {
                font-size: 20px;
                color: #163b60;
                font-weight: 800;
                line-height: 1.2;
            }

            .dashboard-head p {
                font-size: 10.5px;
                color: #899aa8;
                margin-top: 5px;
            }

            .head-actions {
                display: flex;
                gap: 7px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                min-height: 32px;
                padding: 0 12px;
                border: 0;
                border-radius: 9px;
                background: linear-gradient(135deg, #164e79, #20a1b8);
                color: #fff;
                font: 700 10px Poppins;
                box-shadow: 0 7px 16px rgba(21, 90, 122, 0.14);
                transition: 0.2s;
            }

            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(21, 90, 122, 0.19);
            }

            .btn.light {
                background: #fff;
                color: #176e91;
                border: 1px solid #d9e9ee;
                box-shadow: none;
            }

            .alert {
                padding: 9px 12px;
                border-radius: 9px;
                margin-bottom: 12px;
                font-size: 10px;
                background: #e9f8f5;
                color: #177866;
                border: 1px solid #ccece4;
            }

            .alert.err {
                background: #fff1f2;
                color: #bd5864;
                border-color: #f1d4d8;
            }

            .stats {
                display: grid;
                grid-template-columns: 1.15fr repeat(3, 1fr);
                gap: 10px;
                margin-bottom: 12px;
            }

            .stat-card {
                min-height: 88px;
                border-radius: 12px;
                background: #fff;
                border: 1px solid #dce9ee;
                position: relative;
                padding: 12px;
                display: flex;
                align-items: center;
                gap: 10px;
                overflow: hidden;
                transition: 0.2s;
                box-shadow: 0 5px 14px rgba(25, 64, 91, 0.035);
            }

            .stat-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 11px 22px rgba(25, 64, 91, 0.09);
                border-color: #bddde5;
            }

            .stat-card.primary {
                background: linear-gradient(135deg, #18213f, #0b5a91);
                border-color: #0b5a91;
                color: #fff;
                box-shadow: 0 9px 20px rgba(11, 90, 145, 0.2);
            }

            .stat-card.primary:after {
                content: "";
                position: absolute;
                width: 90px;
                height: 90px;
                border-radius: 50%;
                right: -30px;
                top: -35px;
                background: rgba(255, 255, 255, 0.08);
            }

            .stat-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                background: #eef7fb;
                color: #1687a0;
                display: grid;
                place-items: center;
                font-size: 12.5px;
                flex: 0 0 auto;
            }

            .primary .stat-icon {
                background: rgba(255, 255, 255, 0.16);
                color: #fff;
            }

            .stat-card .label {
                display: block;
                color: #8b9aa6;
                font-size: 10px;
            }

            .primary .label {
                color: rgba(255, 255, 255, 0.78);
            }

            .stat-card strong {
                display: block;
                color: #173d62;
                font-size: 17px;
                line-height: 1.15;
                margin-top: 3px;
            }

            .primary strong {
                color: #fff;
            }

            .stat-card small {
                display: block;
                color: #9aa9b4;
                font-size: 9.5px;
                margin-top: 4px;
            }

            .primary small {
                color: rgba(255, 255, 255, 0.65);
            }

            .accent-blue {
                border-bottom: 3px solid #0b5a91;
            }

            .accent-turquoise {
                border-bottom: 3px solid #20a1b8;
            }

            .accent-green {
                border-bottom: 3px solid #1c9a82;
            }

            .dashboard-main {
                display: grid;
                grid-template-columns: 1.2fr 0.8fr;
                gap: 12px;
                margin-bottom: 12px;
            }

            .panel {
                background: #fff;
                border: 1px solid #dce9ee;
                border-radius: 13px;
                box-shadow: 0 5px 15px rgba(25, 64, 91, 0.035);
                overflow: hidden;
            }

            .panel-head {
                padding: 12px 14px;
                border-bottom: 1px solid #edf2f4;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .panel-head h2 {
                font-size: 11.5px;
                color: #234866;
            }

            .panel-head p {
                font-size: 10px;
                color: #96a5af;
                margin-top: 3px;
            }

            .panel-head > a {
                font-size: 10px;
                font-weight: 700;
                color: #13869b;
            }

            .panel.hero {
                min-height: 190px;
                padding: 20px;
                background: linear-gradient(120deg, #143e65 0%, #17648d 60%, #20a1b8 100%);
                color: #fff;
                position: relative;
                overflow: hidden;
            }

            .hero:before {
                content: "";
                position: absolute;
                width: 210px;
                height: 210px;
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 50%;
                right: -75px;
                top: -80px;
            }

            .hero:after {
                content: "";
                position: absolute;
                width: 140px;
                height: 140px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                right: -35px;
                top: -45px;
            }

            .hero-body {
                position: relative;
                z-index: 1;
                display: flex;
                align-items: stretch;
                gap: 18px;
            }

            .hero-content {
                flex: 1;
                min-width: 0;
                max-width: 78%;
            }

            .hero .eyebrow {
                display: inline-block;
                padding: 3px 9px;
                border-radius: 20px;
                background: rgba(117, 225, 227, 0.16);
                color: #8ff0f1;
                letter-spacing: 1.6px;
            }

            .hero h2 {
                font-size: 15px;
                font-weight: 700;
                line-height: 1.35;
                color: #fff;
                margin-top: 9px;
            }

            .hero p {
                font-size: 10.5px;
                line-height: 1.75;
                color: rgba(255, 255, 255, 0.82);
                margin: 8px 0 14px;
            }

            .hero-actions {
                display: flex;
                gap: 7px;
            }

            .hero .btn {
                background: #fff;
                color: #235c88;
                box-shadow: none;
            }

            .hero .btn.light {
                background: rgba(255, 255, 255, 0.14);
                color: #fff;
                border-color: rgba(255, 255, 255, 0.28);
            }

            .hero-image {
                width: 170px;
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.12);
                border: 1.5px dashed rgba(255, 255, 255, 0.4);
                display: grid;
                place-items: center;
                color: rgba(255, 255, 255, 0.55);
                font-size: 22px;
                flex: 0 0 auto;
                overflow: hidden;
            }

            .hero-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 14px;
            }

            .purchase-list {
                padding: 10px 12px 12px;
            }

            .purchase-row {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px;
                border: 1px solid #e4edf0;
                border-radius: 9px;
                margin-top: 6px;
                transition: 0.2s;
            }

            .purchase-row:hover {
                border-color: #acd8df;
                transform: translateX(2px);
            }

            .purchase-icon {
                width: 31px;
                height: 31px;
                border-radius: 9px;
                background: #eef8fa;
                color: #168ca0;
                display: grid;
                place-items: center;
                font-size: 12px;
            }

            .purchase-info {
                flex: 1;
                min-width: 0;
            }

            .purchase-info strong {
                display: block;
                font-size: 10px;
                color: #294b67;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .purchase-info span {
                display: block;
                font-size: 9.5px;
                color: #9aa8b1;
                margin-top: 2px;
            }

            .purchase-check {
                color: #1d9981;
                font-size: 11.5px;
            }

            .purchase-total {
                margin-top: 9px;
                padding: 9px 11px;
                border-radius: 9px;
                background: linear-gradient(135deg, #164e79, #20a1b8);
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 10px;
                color: rgba(255, 255, 255, 0.8);
                box-shadow: 0 7px 16px rgba(21, 90, 122, 0.18);
            }

            .purchase-total strong {
                font-size: 11.5px;
                color: #fff;
            }

            .empty {
                padding: 24px 16px;
                text-align: center;
            }

            .empty i {
                font-size: 22px;
                color: #20a1b8;
            }

            .empty strong {
                display: block;
                font-size: 10.5px;
                color: #567084;
                margin-top: 8px;
            }

            .empty p {
                font-size: 10px;
                line-height: 1.6;
                color: #98a7b1;
                max-width: 230px;
                margin: 4px auto 10px;
            }

            .bottom-grid {
                display: grid;
                grid-template-columns: 1.05fr 0.95fr;
                gap: 12px;
            }

            .panel-body {
                padding: 12px 14px;
            }

            .performance-box {
                display: flex;
                align-items: center;
                gap: 18px;
            }

            .performance-chart {
                position: relative;
                flex: 0 0 auto;
            }

            .performance-chart svg {
                transform: rotate(-90deg);
            }

            .gauge-track {
                fill: none;
                stroke: #edf3f5;
                stroke-width: 7;
            }

            .gauge-value {
                fill: none;
                stroke-width: 7;
                stroke-linecap: round;
                transition: stroke-dashoffset 0.6s ease;
            }

            .gauge-value.blue {
                stroke: #0b5a91;
            }

            .gauge-value.turquoise {
                stroke: #20a1b8;
            }

            .performance-center {
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .performance-center strong {
                font-size: 15px;
                color: #173d62;
                line-height: 1;
            }

            .performance-center span {
                font-size: 9px;
                color: #96a6b1;
                margin-top: 3px;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }

            .performance-legend {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .performance-legend .item {
                display: flex;
                align-items: center;
                gap: 7px;
                font-size: 10px;
                color: #5b7186;
            }

            .performance-legend .dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex: 0 0 auto;
            }

            .performance-legend .dot.turquoise {
                background: #20a1b8;
            }

            .performance-legend .dot.blue {
                background: #0b5a91;
            }

            .performance-legend strong {
                margin-left: auto;
                color: #173d62;
                font-size: 11px;
            }

            .activity-list {
                display: flex;
                flex-direction: column;
                gap: 7px;
            }

            .activity-row {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 9px 8px;
                border: 1px solid #e1ebee;
                border-radius: 9px;
                background: #fff;
                transition: 0.2s;
            }

            .activity-row:hover {
                transform: translateY(-1px);
                border-color: #c7e2e8;
            }

            .activity-icon {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                display: grid;
                place-items: center;
                font-size: 11.5px;
                flex: 0 0 auto;
            }

            .activity-icon.blue {
                background: #eaf3f9;
                color: #0b5a91;
            }

            .activity-icon.green {
                background: #e9f8f5;
                color: #1c9a82;
            }

            .activity-text {
                flex: 1;
                min-width: 0;
                font-size: 10px;
                color: #3a5670;
                line-height: 1.5;
            }

            .activity-time {
                font-size: 9.5px;
                color: #a1b0ba;
                white-space: nowrap;
            }

            @media (max-width: 1050px) {
                .stats {
                    grid-template-columns: repeat(2, 1fr);
                }

                .dashboard-main,
                .bottom-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 650px) {
                .dashboard {
                    padding: 15px;
                }

                .dashboard-head {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .head-actions {
                    width: 100%;
                }

                .head-actions .btn {
                    flex: 1;
                }

                .stats {
                    grid-template-columns: 1fr 1fr;
                }

                .performance-box {
                    flex-direction: column;
                    text-align: center;
                }

                .hero-body {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .hero-content {
                    max-width: 100%;
                }

                .hero-image {
                    width: 70px;
                    height: 70px;
                }

                .hero {
                    min-height: 210px;
                }

                .hero-actions {
                    flex-wrap: wrap;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="dashboard">

                <div class="dashboard-head">
                    <div>
                        <span class="eyebrow">ESPACE ENTREPRISE</span>
                        <h1>Bonjour <?= e($_SESSION['prenom_user'] ?? 'Responsable') ?></h1>
                        <p>Pilotez vos formations, vos collaborateurs et leurs parcours depuis un seul espace.</p>
                    </div>
                    <div class="head-actions">
                        <a class="btn light" href="collaborateurs.php"><i class="fa-solid fa-users"></i> Mon équipe</a>
                        <a class="btn" href="collaborateur-ajouter.php"><i class="fa-solid fa-user-plus"></i> Ajouter</a>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>

                <section class="stats">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div><span class="label">Tous les collaborateurs</span><strong><?= $totalCollabs ?></strong><small>Total enregistré dans votre équipe</small></div>
                    </div>
                    <div class="stat-card accent-turquoise">
                        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div><span class="label">Collaborateurs actifs</span><strong><?= $activeCollabs ?></strong><small><?= $inactiveCollabs ?> inactif<?= $inactiveCollabs > 1 ? 's' : '' ?></small></div>
                    </div>
                    <div class="stat-card accent-blue">
                        <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
                        <div><span class="label">Formations achetées</span><strong><?= $totalPurchased ?></strong><small>Disponibles pour affectation</small></div>
                    </div>
                    <div class="stat-card accent-green">
                        <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
                        <div><span class="label">Progression moyenne</span><strong><?= number_format($avgProgress, 0) ?>%</strong><small><?= $completed ?> formation<?= $completed > 1 ? 's' : '' ?> terminée<?= $completed > 1 ? 's' : '' ?></small></div>
                    </div>
                </section>

                <section class="dashboard-main">
                    <div class="panel hero">
                        <div class="hero-body">
                            <div class="hero-content">
                                <span class="eyebrow">PILOTAGE DE VOTRE ÉQUIPE</span>
                                <h2>Formez vos collaborateurs au bon moment.</h2>
                                <p>Une formation n'apparaît dans votre espace qu'après confirmation du paiement. Vous pouvez ensuite l'affecter aux collaborateurs actifs et suivre leur progression.</p>
                                <div class="hero-actions">
                                    <a class="btn" href="../formation.php"><i class="fa-solid fa-store"></i> Voir le catalogue</a>
                                    <a class="btn light" href="affectations.php"><i class="fa-solid fa-user-check"></i> Affecter une formation</a>
                                </div>
                            </div>
                            <div class="hero-image"><img src="../images/4.png" alt="Formation des collaborateurs"></div>
                        </div>
                    </div>

                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <span class="eyebrow">ACHATS</span>
                                <h2>Formations de l'entreprise</h2>
                                <p>Vos formations déjà payées</p>
                            </div>
                            <a href="formations.php">Voir tout</a>
                        </div>
                        <div class="purchase-list">
                            <?php if (!$recentPurchases): ?>
                                <div class="empty">
                                    <i class="fa-solid fa-cart-shopping"></i><strong>Aucune formation achetée</strong>
                                    <p>Accédez au catalogue pour effectuer votre premier achat.</p>
                                    <a class="btn" href="../formation.php">Accéder au catalogue</a>
                                </div>
                            <?php else: foreach ($recentPurchases as $formation): ?>
                                <div class="purchase-row">
                                    <div class="purchase-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                                    <div class="purchase-info"><strong><?= e($formation['titre_form']) ?></strong><span><?= number_format((float) ($formation['montant_total'] ?? 0), 0, ' ', ' ') ?> FCFA · Paiement confirmé</span></div>
                                    <div class="purchase-check"><i class="fa-solid fa-circle-check"></i></div>
                                </div>
                            <?php endforeach; endif; ?>
                            <?php if ($recentPurchases): ?>
                                <div class="purchase-total">
                                    <span>Total des achats confirmés</span>
                                    <strong><?= number_format($paymentTotal, 0, ' ', ' ') ?> FCFA</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </section>

                <section class="bottom-grid">
                    <?php
                    $rayonExt = 36;
                    $circExt = 2 * M_PI * $rayonExt;
                    $offsetExt = $circExt * (1 - max(0, min(100, $completionRate)) / 100);
                    $rayonInt = 24;
                    $circInt = 2 * M_PI * $rayonInt;
                    $offsetInt = $circInt * (1 - max(0, min(100, $successRate)) / 100);
                    ?>
                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <span class="eyebrow">SUIVI</span>
                                <h2>Indicateurs de performance</h2>
                            </div>
                            <a href="progression.php">Voir le suivi</a>
                        </div>
                        <div class="panel-body">
                            <div class="performance-box">
                                <div class="performance-chart">
                                    <svg width="88" height="88" viewBox="0 0 88 88">
                                        <circle class="gauge-track" cx="44" cy="44" r="<?= $rayonExt ?>"></circle>
                                        <circle class="gauge-value turquoise" cx="44" cy="44" r="<?= $rayonExt ?>" stroke-dasharray="<?= $circExt ?>" stroke-dashoffset="<?= $offsetExt ?>"></circle>
                                        <circle class="gauge-track" cx="44" cy="44" r="<?= $rayonInt ?>"></circle>
                                        <circle class="gauge-value blue" cx="44" cy="44" r="<?= $rayonInt ?>" stroke-dasharray="<?= $circInt ?>" stroke-dashoffset="<?= $offsetInt ?>"></circle>
                                    </svg>
                                    <div class="performance-center"><strong><?= $completionRate ?>%</strong><span>Complété</span></div>
                                </div>
                                <div class="performance-legend">
                                    <div class="item"><span class="dot turquoise"></span> Formations affectées <strong><?= $assigned ?></strong></div>
                                    <div class="item"><span class="dot blue"></span> Formations terminées <strong><?= $completed ?></strong></div>
                                    <div class="item"><span class="dot turquoise"></span> Taux de complétion <strong><?= $completionRate ?>%</strong></div>
                                    <div class="item"><span class="dot blue"></span> Réussite évaluations <strong><?= $successRate ?>%</strong></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <span class="eyebrow">ACTIVITÉ</span>
                                <h2>Dernière activité</h2>
                            </div>
                        </div>
                        <div class="panel-body">
                            <?php if (!$activites): ?>
                                <div class="empty">
                                    <i class="fa-solid fa-clock-rotate-left"></i><strong>Aucune activité récente</strong>
                                    <p>Les ajouts de collaborateurs et les achats de formations apparaîtront ici.</p>
                                </div>
                            <?php else: ?>
                                <div class="activity-list">
                                    <?php foreach ($activites as $activite): ?>
                                        <div class="activity-row">
                                            <div class="activity-icon <?= e($activite['couleur']) ?>"><i class="fa-solid <?= e($activite['icone']) ?>"></i></div>
                                            <div class="activity-text"><?= e($activite['texte']) ?></div>
                                            <div class="activity-time"><?= e(date('d/m/Y', strtotime($activite['date']))) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </section>

            </div>
        </main>
    </body>
</html>

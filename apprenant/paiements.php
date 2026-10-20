<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../paiement_config.php';
require_once __DIR__ . '/../includes/moyens_paiement.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

try {
    $controleCollaborateur = $con->prepare("
        SELECT 1
        FROM t_entreprise_collaborateurs
        WHERE id_user = ?
          AND LOWER(statut) = 'actif'
        LIMIT 1
    ");
    $controleCollaborateur->execute([(int) $_SESSION['id_user']]);

    if ($controleCollaborateur->fetchColumn()) {
        header('Location: dashboard.php');
        exit();
    }
} catch (Throwable $e) {
    header('Location: dashboard.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['id_user'];
tounEnsurePaymentTable($con);

$stmt = $con->prepare(
    'SELECT p.id_paiement, p.ref_paiement, p.numero_recu, p.lien_recu, p.montant_total,
            p.mode_paiement, p.statut_paiement, p.date_paiement, f.titre_form
     FROM t_paiements p
     LEFT JOIN t_formations f ON f.id_form = p.id_form
     WHERE p.id_user = ?
     ORDER BY p.date_paiement DESC, p.id_paiement DESC'
);
$stmt->execute([$userId]);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$totalPaiements = count($paiements);
$totalDepense = 0;
$totalSucces = 0;
$totalAttente = 0;

foreach ($paiements as $p) {
    $statut = strtolower((string) $p['statut_paiement']);

    if ($statut === 'paye') {
        $totalDepense += (float) $p['montant_total'];
        $totalSucces++;
    } elseif ($statut === 'en_attente') {
        $totalAttente++;
    }
}

$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));
$nomAffiche = trim($prenom . ' ' . $nom);
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
if ($initiales === '') {
    $initiales = 'TO';
}

$statutLabels = [
    'paye' => ['classe' => 'success', 'icone' => 'fa-circle-check', 'texte' => 'Confirmé'],
    'en_attente' => ['classe' => 'wait', 'icone' => 'fa-clock', 'texte' => 'En attente'],
    'rembourse' => ['classe' => 'wait', 'icone' => 'fa-rotate-left', 'texte' => 'Remboursé'],
    'echoue' => ['classe' => 'failed', 'icone' => 'fa-circle-xmark', 'texte' => 'Échec']
];
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mes paiements | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: #f4f8fc;
                color: #18213f;
                font-family: "Poppins", sans-serif;
            }

            .main {
                min-height: 100vh;
            }

            .topbar {
                height: 74px;
                padding: 0 34px;
                background: #fff;
                border-bottom: 1px solid #e7edf4;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 25px;
            }

            .mobile-menu {
                display: none;
                border: 0;
                background: #edf7fa;
                color: #197fa0;
                width: 40px;
                height: 40px;
                border-radius: 10px;
                cursor: pointer;
            }

            .search {
                width: min(430px, 45%);
                height: 42px;
                border-radius: 12px;
                background: #f4f7fa;
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 0 15px;
                color: #91a0b2;
            }

            .search input {
                width: 100%;
                border: 0;
                outline: 0;
                background: transparent;
                font: inherit;
                font-size: 12.5px;
            }

            .top-right {
                display: flex;
                align-items: center;
                gap: 18px;
            }

            .notification {
                position: relative;
                width: 38px;
                height: 38px;
                border-radius: 11px;
                background: #f3f7fa;
                display: grid;
                place-items: center;
                color: #426080;
                text-decoration: none;
            }

            .notification span {
                position: absolute;
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: #1bb7c8;
                right: 8px;
                top: 7px;
            }

            .profile {
                display: flex;
                align-items: center;
                gap: 10px;
                text-decoration: none;
                color: #18213f;
            }

            .profile-avatar {
                width: 38px;
                height: 38px;
                border-radius: 50%;
                display: grid;
                place-items: center;
                background: linear-gradient(135deg, #2457a6, #16b8c5);
                color: #fff;
                font-size: 12px;
                font-weight: 700;
            }

            .profile-info {
                display: flex;
                flex-direction: column;
                line-height: 1.25;
            }

            .profile-info strong {
                font-size: 12px;
            }

            .profile-info small {
                color: #8a98a9;
                font-size: 11px;
            }

            .content {
                padding: 28px 34px 45px;
            }

            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: end;
                gap: 20px;
                margin-bottom: 22px;
            }

            .eyebrow {
                margin: 0 0 5px;
                color: #19a9bb;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 1.5px;
                text-transform: uppercase;
            }

            .page-header h1 {
                margin: 0;
                color: #18213f;
                font-size: 27px;
                line-height: 1.2;
            }

            .page-header p {
                margin: 7px 0 0;
                color: #8090a4;
                font-size: 12px;
            }

            .breadcrumb {
                color: #8291a3;
                font-size: 11.5px;
                white-space: nowrap;
            }

            .breadcrumb i {
                color: #16a9ba;
                margin-right: 5px;
            }

            .payment-hero {
                position: relative;
                overflow: hidden;
                min-height: 175px;
                padding: 28px 32px;
                border-radius: 20px;
                background: linear-gradient(120deg, #18213f 0%, #24558b 52%, #159daf 100%);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 30px;
                margin-bottom: 20px;
            }

            .payment-hero::before {
                content: "";
                position: absolute;
                width: 310px;
                height: 310px;
                border: 1px solid rgba(255, 255, 255, 0.09);
                border-radius: 50%;
                right: -80px;
                top: -145px;
            }

            .payment-hero::after {
                content: "";
                position: absolute;
                width: 210px;
                height: 210px;
                border: 1px solid rgba(91, 225, 229, 0.12);
                border-radius: 50%;
                right: 50px;
                bottom: -160px;
            }

            .hero-copy {
                position: relative;
                z-index: 2;
                max-width: 590px;
            }

            .hero-label {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 6px 10px;
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.1);
                color: #70e1e6;
                font-size: 10.5px;
                font-weight: 700;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            .hero-copy h2 {
                margin: 12px 0 7px;
                font-size: 22px;
                line-height: 1.3;
            }

            .hero-copy p {
                margin: 0;
                max-width: 540px;
                color: rgba(255, 255, 255, 0.78);
                font-size: 11.5px;
                line-height: 1.7;
            }

            .hero-visual {
                position: relative;
                z-index: 2;
                width: 150px;
                height: 150px;
                flex: 0 0 auto;
                display: grid;
                place-items: center;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 14px;
                margin-bottom: 25px;
            }

            .stat {
                min-height: 105px;
                background: #fff;
                border: 1px solid #e5edf3;
                border-radius: 15px;
                padding: 15px 16px;
                position: relative;
                overflow: hidden;
            }

            .stat-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                display: grid;
                place-items: center;
                background: #eaf8fa;
                color: #13a6b8;
                font-size: 13px;
                margin-bottom: 9px;
            }

            .stat strong {
                display: block;
                font-size: 20px;
                line-height: 1;
                color: #18213f;
            }

            .stat span {
                display: block;
                margin-top: 5px;
                color: #8a98a9;
                font-size: 11px;
            }

            .stat.total .stat-icon {
                background: #edf3ff;
                color: #3768ae;
            }

            .stat.success .stat-icon {
                background: #e9f8f0;
                color: #20a16d;
            }

            .stat.wait .stat-icon {
                background: #eaf3f9;
                color: #20a1b8;
            }

            .stat.amount .stat-icon {
                background: #fff0f0;
                color: #d76565;
            }

            .section-title {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 12px;
            }

            .section-title h2 {
                margin: 0;
                font-size: 15px;
                color: #18213f;
            }

            .section-title span {
                color: #8b98a8;
                font-size: 11px;
            }

            .table-card {
                background: #fff;
                border: 1px solid #e5edf3;
                border-radius: 16px;
                overflow: hidden;
            }

            .payment-table {
                width: 100%;
                border-collapse: collapse;
            }

            .payment-table th {
                padding: 13px 15px;
                background: #f7fafc;
                color: #7b8a9d;
                text-align: left;
                font-size: 10.5px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.6px;
            }

            .payment-table td {
                padding: 14px 15px;
                border-top: 1px solid #edf2f6;
                color: #506177;
                font-size: 11.5px;
                vertical-align: middle;
            }

            .payment-table td:first-child {
                color: #18213f;
                font-weight: 600;
            }

            .formation-name {
                max-width: 270px;
            }

            .reference {
                color: #8b98a8 !important;
                font-size: 10.5px !important;
            }

            .amount {
                color: #18213f !important;
                font-weight: 700;
                white-space: nowrap;
            }

            .method {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-weight: 600;
            }

            .method-dot {
                width: 24px;
                height: 24px;
                border-radius: 7px;
                display: grid;
                place-items: center;
                color: #fff;
                font-size: 10.5px;
                background: #20a99f;
            }

            .method-dot.other {
                background: #3768ae;
            }

            .method-logo {
                width: 24px;
                height: 24px;
                border-radius: 7px;
                border: 1px solid #e3e9ef;
                background: #fff;
                object-fit: contain;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 8px;
                border-radius: 20px;
                font-size: 10.5px;
                font-weight: 600;
                white-space: nowrap;
            }

            .badge.success {
                color: #188257;
                background: #e9f8f0;
            }

            .badge.wait {
                color: #0b5a91;
                background: #eaf3f9;
            }

            .badge.failed {
                color: #bd5353;
                background: #fff0f0;
            }

            .recu-link {
                color: #20a1b8;
                font-size: 11px;
                font-weight: 700;
                text-decoration: none;
            }

            .recu-empty {
                color: #c3ccd6;
            }

            .empty {
                padding: 55px 20px;
                text-align: center;
            }

            .empty-icon {
                width: 55px;
                height: 55px;
                margin: 0 auto 12px;
                border-radius: 16px;
                background: #eaf8fa;
                color: #16a7b8;
                display: grid;
                place-items: center;
                font-size: 20px;
            }

            .empty h3 {
                margin: 0;
                color: #18213f;
                font-size: 14px;
            }

            .empty p {
                max-width: 390px;
                margin: 6px auto 15px;
                color: #8b98a8;
                font-size: 11px;
                line-height: 1.7;
            }

            .empty a {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 9px 14px;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                text-decoration: none;
                font-size: 11px;
                font-weight: 600;
            }

            @media (min-width: 1001px) {
                .main {
                    margin-left: 250px;
                }
            }

            @media (max-width: 1000px) {
                .sidebar {
                    transform: translateX(-100%);
                    transition: 0.25s ease;
                }

                .sidebar.open {
                    transform: translateX(0);
                }

                .main {
                    margin-left: 0;
                }

                .mobile-menu {
                    display: block;
                }

                .topbar {
                    padding: 0 18px;
                }

                .content {
                    padding: 24px 18px 40px;
                }
            }

            @media (max-width: 760px) {
                .search {
                    display: none;
                }

                .top-right {
                    margin-left: auto;
                }

                .profile-info {
                    display: none;
                }

                .page-header {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .stats {
                    grid-template-columns: repeat(2, 1fr);
                }

                .payment-hero {
                    min-height: 160px;
                    padding: 23px;
                }

                .hero-visual {
                    width: 100px;
                    height: 100px;
                }

                .payment-table {
                    min-width: 760px;
                }

                .table-card {
                    overflow-x: auto;
                }
            }

            @media (max-width: 500px) {
                .stats {
                    grid-template-columns: 1fr 1fr;
                    gap: 9px;
                }

                .stat {
                    padding: 12px;
                }

                .payment-hero {
                    display: block;
                }

                .hero-visual {
                    display: none;
                }

                .page-header h1 {
                    font-size: 23px;
                }
            }
        </style>
    </head>
    <body>
        <div class="layout">
            <?php include __DIR__ . '/menu.php'; ?>

            <main class="main">
                <header class="topbar">
                    <button class="mobile-menu" type="button" id="mobileMenu" aria-label="Ouvrir le menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Rechercher une formation...">
                    </div>
                    <div class="top-right">
                        <a href="notifications.php" class="notification" aria-label="Notifications">
                            <i class="fa-regular fa-bell"></i>
                            <span></span>
                        </a>
                        <a href="profil.php" class="profile">
                            <div class="profile-avatar"><?= e($initiales) ?></div>
                            <div class="profile-info">
                                <strong><?= e($nomAffiche !== '' ? $nomAffiche : 'Mon compte') ?></strong>
                                <small>Apprenant</small>
                            </div>
                        </a>
                    </div>
                </header>

                <section class="content">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Services</p>
                            <h1>Mes paiements</h1>
                            <p>Retrouvez l'historique de vos achats et l'état de vos transactions.</p>
                        </div>
                        <div class="breadcrumb">
                            <i class="fa-solid fa-house"></i>
                            Apprenant&nbsp;›&nbsp;Mes paiements
                        </div>
                    </div>

                    <section class="payment-hero">
                        <div class="hero-copy">
                            <span class="hero-label"><i class="fa-solid fa-wallet"></i> Historique des paiements</span>
                            <h2>Gérez facilement vos achats de formations</h2>
                            <p>Consultez vos transactions, vérifiez leur statut et retrouvez les formations associées à chaque paiement.</p>
                        </div>
                        <div class="hero-visual">
                            <svg viewBox="0 0 140 140" width="140" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Illustration de paiement en ligne sécurisé">
                                <circle cx="70" cy="70" r="66" fill="rgba(255,255,255,.06)"/>
                                <circle cx="70" cy="70" r="66" fill="none" stroke="rgba(255,255,255,.18)" stroke-width="1.5"/>
                                <g transform="translate(18,42) rotate(-8)">
                                    <rect x="0" y="0" width="88" height="56" rx="9" fill="#ffffff"/>
                                    <rect x="0" y="14" width="88" height="12" fill="#18213f"/>
                                    <rect x="8" y="34" width="20" height="14" rx="3" fill="#20a1b8"/>
                                    <rect x="8" y="42" width="40" height="4" rx="2" fill="#c8d4e0"/>
                                </g>
                                <circle cx="102" cy="94" r="20" fill="#20e08a"/>
                                <path d="M93 94l6 6 12-13" fill="none" stroke="#0c3d24" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </section>

                    <section class="stats">
                        <div class="stat total">
                            <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
                            <strong><?= $totalPaiements ?></strong>
                            <span>Paiements effectués</span>
                        </div>
                        <div class="stat success">
                            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                            <strong><?= $totalSucces ?></strong>
                            <span>Paiements confirmés</span>
                        </div>
                        <div class="stat wait">
                            <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                            <strong><?= $totalAttente ?></strong>
                            <span>En attente</span>
                        </div>
                        <div class="stat amount">
                            <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
                            <strong><?= number_format($totalDepense, 0, ',', ' ') ?></strong>
                            <span>FCFA dépensés</span>
                        </div>
                    </section>

                    <div class="section-title">
                        <h2>Historique des transactions</h2>
                        <span><?= $totalPaiements ?> transaction<?= $totalPaiements > 1 ? 's' : '' ?></span>
                    </div>

                    <div class="table-card">
                        <?php if ($paiements): ?>
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>Formation</th>
                                        <th>Référence</th>
                                        <th>Montant</th>
                                        <th>Moyen</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Reçu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paiements as $p): ?>
                                        <?php
$statut = strtolower((string) $p['statut_paiement']);
$info = $statutLabels[$statut] ?? $statutLabels['echoue'];

$moyen = tounMoyenPaiement($p['mode_paiement'] ?? '', '../');
?>
                                        <tr>
                                            <td class="formation-name"><?= e($p['titre_form'] ?: 'Formation TOUN') ?></td>
                                            <td class="reference"><?= e($p['ref_paiement']) ?></td>
                                            <td class="amount"><?= number_format((float) $p['montant_total'], 0, ',', ' ') ?> FCFA</td>
                                            <td>
                                                <span class="method">
                                                    <?php if ($moyen['logo'] !== ''): ?>
                                                        <img class="method-logo" src="<?= e($moyen['logo']) ?>" alt="<?= e($moyen['nom']) ?>">
                                                    <?php else: ?>
                                                        <span class="method-dot other"><i class="fa-solid fa-wallet"></i></span>
                                                    <?php endif; ?>
                                                    <?= e($moyen['nom']) ?>
                                                </span>
                                            </td>
                                            <td><?= !empty($p['date_paiement']) ? date('d/m/Y', strtotime($p['date_paiement'])) : '—' ?></td>
                                            <td>
                                                <span class="badge <?= $info['classe'] ?>">
                                                    <i class="fa-solid <?= $info['icone'] ?>"></i>
                                                    <?= $info['texte'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($statut === 'paye' && !empty($p['lien_recu'])): ?>
                                                    <a href="../<?= e($p['lien_recu']) ?>" target="_blank" rel="noopener" class="recu-link">
                                                        <i class="fa-solid fa-receipt"></i> Voir
                                                    </a>
                                                <?php else: ?>
                                                    <span class="recu-empty">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty">
                                <div class="empty-icon"><i class="fa-solid fa-wallet"></i></div>
                                <h3>Aucun paiement pour le moment</h3>
                                <p>Lorsque vous achèterez une formation, vos transactions et leur statut apparaîtront ici.</p>
                                <a href="../formation.php"><i class="fa-solid fa-book-open"></i> Découvrir les formations</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>

        <script>
            const mobileMenu = document.getElementById('mobileMenu');
            const sidebar = document.getElementById('sidebar');
            if (mobileMenu && sidebar) {
                mobileMenu.addEventListener('click', () => sidebar.classList.toggle('open'));
            }
        </script>
    </body>
</html>

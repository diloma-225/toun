<?php
require __DIR__ . '/entreprise_init.php';
require_once __DIR__ . '/../includes/moyens_paiement.php';
$active_page = 'paiements.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Paiements — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .payment-stats {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-bottom: 12px;
            }

            .payment-stats > div {
                padding: 12px;
                border: 1px solid #dce9ef;
                border-radius: 12px;
                background: #fff;
                transition: 0.2s;
            }

            .payment-stats > div:hover {
                transform: translateY(-2px);
            }

            .payment-stats span {
                display: block;
                color: #8c9da9;
                font-size: 10px;
            }

            .payment-stats strong {
                display: block;
                color: #173d62;
                font-size: 15px;
                margin-top: 3px;
            }

            .payment-stats .money {
                position: relative;
                overflow: hidden;
                background: linear-gradient(135deg, #164e79, #20a1b8);
                border-color: #164e79;
                box-shadow: 0 9px 20px rgba(21, 90, 122, 0.2);
            }

            .payment-stats .money:after {
                content: "";
                position: absolute;
                width: 80px;
                height: 80px;
                border-radius: 50%;
                right: -25px;
                top: -30px;
                background: rgba(255, 255, 255, 0.09);
            }

            .payment-stats .money span {
                color: rgba(255, 255, 255, 0.78);
            }

            .payment-stats .money strong {
                color: #fff;
                font-size: 18px;
                position: relative;
                z-index: 1;
            }

            .payment-stats .money i {
                position: absolute;
                right: 12px;
                bottom: 10px;
                font-size: 20px;
                color: rgba(255, 255, 255, 0.22);
            }

            .table-wrap {
                overflow: auto;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
            }

            .table th {
                padding: 9px 12px;
                background: linear-gradient(135deg, #eaf1ff, #eef8fa);
                color: #2c5680;
                font-size: 10px;
                font-weight: 800;
                text-align: left;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                border-bottom: 2px solid #cfe3ea;
            }

            .table tbody tr {
                border-left: 3px solid transparent;
                transition: 0.15s;
            }

            .table tbody tr:hover {
                background: #f8fcfd;
                border-left-color: #20a1b8;
            }

            .table td {
                padding: 9px 12px;
                border-top: 1px solid #edf2f4;
                font-size: 10.5px;
            }

            .moyen {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .moyen img {
                width: 22px;
                height: 22px;
                border-radius: 6px;
                border: 1px solid #e3e9ef;
                object-fit: contain;
            }

            .status {
                display: inline-flex;
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: 800;
            }

            .status.ok {
                background: #e9f8f5;
                color: #23866e;
            }

            .status.wait {
                background: #eef8fa;
                color: #168b9a;
            }

            .empty {
                text-align: center;
                padding: 40px;
                color: #8fa0ac;
                font-size: 10.5px;
            }

            @media (max-width: 650px) {
                .payment-stats {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">
                <?php
$rows = [];
$total = 0;
$confirmed = 0;
if (tableExists($con, 't_paiements')) {
    $s = $con->prepare(
        'SELECT p.*,f.titre_form FROM t_paiements p LEFT JOIN t_formations f ON f.id_form=p.id_form WHERE p.id_user=? ORDER BY p.date_paiement DESC'
    );
    $s->execute([$userId]);
    $rows = $s->fetchAll();
    foreach ($rows as $r) {
        if (paymentSuccess($r['statut_paiement'] ?? '')) {
            $confirmed++;
            $total += (float) ($r['montant_total'] ?? 0);
        }
    }
}
?>

                <div class="page-head">
                    <div>
                        <span class="eyebrow">GESTION</span>
                        <h1>Paiements</h1>
                        <p>Suivez les achats de formations réalisés par votre entreprise.</p>
                    </div>
                    <a class="btn" href="../formation.php"><i class="fa-solid fa-store"></i> Acheter une formation</a>
                </div>

                <section class="payment-stats">
                    <div><span>Transactions</span><strong><?= count($rows) ?></strong></div>
                    <div><span>Confirmées</span><strong><?= $confirmed ?></strong></div>
                    <div class="money"><span>Total payé</span><strong><?= number_format($total, 0, ' ', ' ') ?> FCFA</strong><i class="fa-solid fa-sack-dollar"></i></div>
                </section>

                <section class="panel">
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Formation</th>
                                    <th>Montant</th>
                                    <th>Mode</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty">Aucun paiement. Utilisez le catalogue pour acheter une formation.</div>
                                        </td>
                                    </tr>
                                <?php else: foreach ($rows as $r): $ok = paymentSuccess($r['statut_paiement'] ?? ''); ?>
                                    <tr>
                                        <td><?= e($r['reference_paiement'] ?? ($r['ref_paiement'] ?? '—')) ?></td>
                                        <td><?= e($r['titre_form'] ?? '—') ?></td>
                                        <td><?= number_format((float) ($r['montant_total'] ?? 0), 0, ' ', ' ') ?> FCFA</td>
                                        <td>
                                            <?php $moyen = tounMoyenPaiement($r['mode_paiement'] ?? '', '../'); ?>
                                            <span class="moyen">
                                                <?php if ($moyen['logo'] !== ''): ?>
                                                    <img src="<?= e($moyen['logo']) ?>" alt="<?= e($moyen['nom']) ?>">
                                                <?php endif; ?>
                                                <?= e($moyen['nom']) ?>
                                            </span>
                                        </td>
                                        <td><?= !empty($r['date_paiement']) ? e(date('d/m/Y', strtotime($r['date_paiement']))) : '—' ?></td>
                                        <td><span class="status <?= $ok ? 'ok' : 'wait' ?>"><?= e($r['statut_paiement'] ?? '—') ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </main>
    </body>
</html>

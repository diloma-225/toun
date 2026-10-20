<?php
session_start();
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/paiement_config.php';
require_once __DIR__ . '/includes/moyens_paiement.php';

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['id_user'];
$ref = trim((string) ($_GET['ref'] ?? ''));
$payment = null;

if ($ref !== '') {
    try {
        tounEnsurePaymentTable($con);
        $stmt = $con->prepare(
            'SELECT p.*, f.titre_form, f.duree_form
             FROM t_paiements p
             LEFT JOIN t_formations f ON f.id_form = p.id_form
             WHERE p.ref_paiement = :r AND p.id_user = :u AND p.statut_paiement = "paye"
             LIMIT 1'
        );
        $stmt->execute([':r' => $ref, ':u' => $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $payment = null;
    }
}

$nomAffiche = trim(($_SESSION['prenom_user'] ?? '') . ' ' . ($_SESSION['nom_user'] ?? ''));
$moyen = tounMoyenPaiement($payment['mode_paiement'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reçu de paiement | TOUN</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: #f3f8fb;
                font-family: Arial, sans-serif;
                color: #18213f;
                display: flex;
                justify-content: center;
                padding: 40px 16px;
            }

            .recu {
                width: min(560px, 100%);
                background: #fff;
                border: 1px solid #dce8ee;
                border-radius: 18px;
                box-shadow: 0 15px 40px rgba(20, 54, 88, 0.08);
                overflow: hidden;
            }

            .recu-head {
                padding: 28px 32px;
                background: linear-gradient(120deg, #18213f 0%, #24558b 52%, #20a1b8 100%);
                color: #fff;
            }

            .recu-head .brand {
                font-weight: 800;
                letter-spacing: 1px;
                font-size: 14px;
            }

            .recu-head h1 {
                margin: 10px 0 4px;
                font-size: 20px;
            }

            .recu-head span {
                color: rgba(255, 255, 255, 0.8);
                font-size: 11px;
            }

            .recu-body {
                padding: 26px 32px;
            }

            .row {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                padding: 11px 0;
                border-bottom: 1px solid #eef3f7;
                font-size: 12px;
            }

            .row span:first-child {
                color: #8090a4;
            }

            .row span:last-child {
                color: #18213f;
                font-weight: 600;
                text-align: right;
            }

            .moyen {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .moyen img {
                width: 26px;
                height: 26px;
                border-radius: 7px;
                border: 1px solid #e3e9ef;
                object-fit: contain;
            }

            .row.total {
                border-bottom: 0;
                padding-top: 16px;
                font-size: 16px;
            }

            .row.total span:last-child {
                color: #20a1b8;
                font-weight: 800;
            }

            .actions {
                padding: 0 32px 30px;
                display: flex;
                gap: 10px;
            }

            .btn {
                flex: 1;
                text-align: center;
                padding: 12px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                border: 0;
            }

            .btn.primary {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
            }

            .btn.ghost {
                background: #f4f8fc;
                color: #18213f;
                border: 1px solid #dce8ee;
            }

            .empty {
                padding: 60px 32px;
                text-align: center;
                color: #8090a4;
                font-size: 12px;
                line-height: 1.8;
            }

            @media print {
                body {
                    background: #fff;
                    padding: 0;
                }

                .recu {
                    box-shadow: none;
                    border: 0;
                }

                .actions {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="recu">
            <?php if ($payment): ?>
                <div class="recu-head">
                    <div class="brand"><i class="fa-solid fa-graduation-cap"></i> TOUN</div>
                    <h1>Reçu de paiement</h1>
                    <span>N° <?= e($payment['numero_recu'] ?: $payment['ref_paiement']) ?></span>
                </div>
                <div class="recu-body">
                    <div class="row"><span>Client</span><span><?= e($nomAffiche !== '' ? $nomAffiche : 'Apprenant TOUN') ?></span></div>
                    <div class="row"><span>Formation</span><span><?= e($payment['titre_form'] ?? 'Formation TOUN') ?></span></div>
                    <div class="row"><span>Référence</span><span><?= e($payment['ref_paiement']) ?></span></div>
                    <div class="row">
                        <span>Moyen de paiement</span>
                        <span class="moyen">
                            <?php if ($moyen['logo'] !== ''): ?>
                                <img src="<?= e($moyen['logo']) ?>" alt="<?= e($moyen['nom']) ?>">
                            <?php endif; ?>
                            <?= e($moyen['nom']) ?>
                        </span>
                    </div>
                    <div class="row">
                        <span>Date</span>
                        <span><?= !empty($payment['date_paiement']) ? date('d/m/Y à H:i', strtotime($payment['date_paiement'])) : '—' ?></span>
                    </div>
                    <div class="row total">
                        <span>Montant payé</span>
                        <span><?= number_format((float) $payment['montant_total'], 0, ',', ' ') ?> FCFA</span>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn ghost" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer</button>
                    <a class="btn primary" href="apprenant/mes-formations.php"><i class="fa-solid fa-arrow-right"></i> Accéder à la formation</a>
                </div>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-solid fa-file-circle-xmark" style="font-size:26px;color:#20a1b8"></i>
                    <p>Ce reçu est introuvable, ou ne correspond pas à un paiement confirmé sur votre compte.</p>
                </div>
            <?php endif; ?>
        </div>
    </body>
</html>

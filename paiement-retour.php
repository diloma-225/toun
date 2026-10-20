<?php
if (($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1') {
    header('Location: http://localhost' . ($_SERVER['REQUEST_URI'] ?? '/'));
    exit();
}

session_start();
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/paiement_config.php';

function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

$reference = trim((string) ($_GET['transaction_id'] ?? ($_POST['transaction_id'] ?? ($_GET['cpm_trans_id'] ?? ''))));
$paiement = null;

$idFournisseur = trim((string) ($_GET['id'] ?? ''));
if ($reference === '' && $idFournisseur !== '') {
    try {
        tounEnsurePaymentTable($con);
        $s = $con->prepare('SELECT ref_paiement FROM t_paiements WHERE ref_fournisseur = ? LIMIT 1');
        $s->execute([$idFournisseur]);
        $reference = (string) ($s->fetchColumn() ?: '');
    } catch (Throwable $e) {
    }
}

if ($reference !== '') {
    try {
        tounEnsurePaymentTable($con);

        $s = $con->prepare(
            "SELECT p.*, f.titre_form
             FROM t_paiements p
             LEFT JOIN t_formations f ON f.id_form = p.id_form
             WHERE p.ref_paiement = :r
             LIMIT 1"
        );
        $s->execute([':r' => $reference]);
        $paiement = $s->fetch(PDO::FETCH_ASSOC);

        if ($paiement) {
            $paiement['statut_paiement'] = tounVerifierPaiement($con, $paiement);
        }
    } catch (Throwable $e) {
    }
}

$succes = $paiement && $paiement['statut_paiement'] === 'paye';

$sessionWebActive = $succes && isset($_SESSION['id_user']) && (int) $_SESSION['id_user'] === (int) $paiement['id_user'];

$retourApp = trim((string) ($_GET['app'] ?? ''));
$lienApp = '';
if ($paiement && preg_match('#^http://(localhost|127\.0\.0\.1):[0-9]{2,5}$#', $retourApp)) {
    $lienApp = $retourApp . '/?paiement=' . rawurlencode($paiement['ref_paiement']);
}
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $succes ? 'Paiement confirmé' : 'Paiement en cours' ?> | TOUN</title>
        <?php if ($lienApp !== ''): ?>
            <meta http-equiv="refresh" content="2;url=<?= e($lienApp) ?>">
        <?php endif; ?>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                background: #f3f8fb;
                font-family: Arial, sans-serif;
                color: #17345b;
            }

            .box {
                width: min(520px, 90%);
                padding: 38px;
                text-align: center;
                background: #fff;
                border: 1px solid #dce8ee;
                border-radius: 18px;
                box-shadow: 0 15px 40px rgba(20, 54, 88, 0.08);
            }

            .icon {
                width: 60px;
                height: 60px;
                margin: auto;
                border-radius: 50%;
                display: grid;
                place-items: center;
                background: <?= $succes ? '#E8F8F1' : '#eaf3f9' ?>;
                color: <?= $succes ? '#22A06B' : '#20a1b8' ?>;
                font-size: 25px;
            }

            .box h1 {
                font-size: 23px;
            }

            .box p {
                color: #718397;
                font-size: 12.5px;
                line-height: 1.7;
            }

            .btn {
                display: inline-flex;
                margin-top: 12px;
                margin-right: 8px;
                padding: 11px 18px;
                border-radius: 9px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                text-decoration: none;
                font-weight: 700;
                font-size: 12px;
            }

            .btn.secondaire {
                background: #fff;
                color: #3f68ae;
                border: 1px solid #dce8ee;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="icon"><?= $succes ? '✓' : '…' ?></div>
            <h1><?= $succes ? 'Paiement confirmé' : 'Paiement en cours de confirmation' ?></h1>
            <p>
                <?php if ($lienApp !== ''): ?>
                    <?= $succes ? 'Votre achat a été confirmé.' : 'Votre paiement est en cours de vérification.' ?>
                    Retour automatique à l'application TOUN…
                <?php elseif ($succes && $sessionWebActive): ?>
                    Votre achat de <strong><?= e($paiement['titre_form'] ?? 'la formation') ?></strong> a été confirmé.
                    La formation est maintenant associée à votre compte.
                <?php elseif ($succes): ?>
                    Votre achat de <strong><?= e($paiement['titre_form'] ?? 'la formation') ?></strong> a été confirmé.
                    Retournez sur l'application TOUN et appuyez sur « J'ai terminé mon paiement »
                    pour continuer.
                <?php else: ?>
                    Votre paiement a été reçu par le guichet ou est encore en cours de traitement.
                    TOUN vérifie automatiquement la transaction avant d'ouvrir l'accès.
                <?php endif; ?>
            </p>

            <?php if ($lienApp !== ''): ?>
                <a class="btn" href="<?= e($lienApp) ?>">Retour à l'application</a>
            <?php elseif ($succes && $sessionWebActive): ?>
                <a class="btn" href="apprenant/mes-formations.php">Accéder à la formation</a>
                <a class="btn secondaire" href="recu.php?ref=<?= urlencode($paiement['ref_paiement']) ?>">Voir le reçu</a>
            <?php elseif (!$succes): ?>
                <a class="btn" href="formation.php">Retour aux formations</a>
            <?php endif; ?>
        </div>
    </body>
</html>

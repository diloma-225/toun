<?php
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/paiement_config.php';

function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

if (!tounSimulationAutorisee()) {
    http_response_code(403);
    echo 'Page indisponible.';
    exit();
}

$reference = trim((string) ($_GET['ref'] ?? ''));
$paiement = null;

if ($reference !== '') {
    tounEnsurePaymentTable($con);

    $s = $con->prepare(
        "SELECT p.*, f.titre_form
         FROM t_paiements p
         LEFT JOIN t_formations f ON f.id_form = p.id_form
         WHERE p.ref_paiement = ?
         LIMIT 1"
    );
    $s->execute([$reference]);
    $paiement = $s->fetch(PDO::FETCH_ASSOC);
}

if (!$paiement) {
    http_response_code(404);
    echo 'Paiement introuvable.';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $paiement['statut_paiement'] === 'en_attente') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'confirmer') {
        $numeroRecu = tounGenererRecu((int) $paiement['id_paiement']);

        $u = $con->prepare("UPDATE t_paiements SET statut_paiement = 'paye', numero_recu = ? WHERE id_paiement = ?");
        $u->execute([$numeroRecu, $paiement['id_paiement']]);

        tounCreateInscription(
            $con,
            (int) $paiement['id_user'],
            (int) $paiement['id_form'],
            (int) $paiement['id_paiement']
        );
    } elseif ($action === 'echouer') {
        $u = $con->prepare("UPDATE t_paiements SET statut_paiement = 'echoue' WHERE id_paiement = ?");
        $u->execute([$paiement['id_paiement']]);
    }

    header('Location: paiement-retour.php?transaction_id=' . urlencode($reference));
    exit();
}

$dejaTraite = $paiement['statut_paiement'] !== 'en_attente';
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Simulation de paiement | TOUN</title>
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

            .carte {
                width: min(420px, 90%);
                padding: 32px;
                background: #fff;
                border: 1px solid #dce8ee;
                border-radius: 18px;
                box-shadow: 0 15px 40px rgba(20, 54, 88, 0.08);
            }

            .badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 20px;
                background: #eaf3f9;
                color: #20a1b8;
                font-size: 12px;
                font-weight: 700;
                margin-bottom: 14px;
            }

            h1 {
                font-size: 19px;
                margin: 0 0 6px;
            }

            p {
                color: #718397;
                font-size: 13px;
                line-height: 1.6;
            }

            .ligne {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #eef3f6;
                font-size: 13px;
            }

            .ligne strong {
                color: #17345b;
            }

            .btn {
                display: block;
                width: 100%;
                margin-top: 10px;
                padding: 13px;
                border: 0;
                border-radius: 10px;
                font-weight: 700;
                font-size: 13px;
                cursor: pointer;
                text-decoration: none;
                text-align: center;
                box-sizing: border-box;
            }

            .btn-confirmer {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
            }

            .btn-echouer {
                background: #fff;
                color: #b3261e;
                border: 1px solid #f3d3d0;
            }
        </style>
    </head>
    <body>
        <div class="carte">
            <span class="badge">Mode démonstration</span>
            <h1>Simulation de paiement mobile money</h1>
            <p>
                Aucune passerelle de paiement réelle n'est encore configurée pour TOUN
                (clé API CinetPay à renseigner dans <code>paiement_config.php</code>).
                Cette page simule la confirmation envoyée par l'opérateur pour permettre
                de tester le parcours d'achat complet.
            </p>

            <div class="ligne">
                <span>Formation</span>
                <strong><?= e($paiement['titre_form'] ?? '') ?></strong>
            </div>
            <div class="ligne">
                <span>Moyen choisi</span>
                <strong><?= e($paiement['mode_paiement'] ?? '') ?></strong>
            </div>
            <div class="ligne">
                <span>Référence</span>
                <strong><?= e($paiement['ref_paiement']) ?></strong>
            </div>
            <div class="ligne">
                <span>Montant</span>
                <strong><?= number_format((float) $paiement['montant_total'], 0, ',', ' ') ?> FCFA</strong>
            </div>

            <?php if ($dejaTraite): ?>
                <p>Ce paiement a déjà été traité (statut : <?= e($paiement['statut_paiement']) ?>).</p>
                <a class="btn btn-confirmer" href="paiement-retour.php?transaction_id=<?= urlencode($reference) ?>">
                    Voir le résultat
                </a>
            <?php else: ?>
                <form method="post">
                    <button type="submit" name="action" value="confirmer" class="btn btn-confirmer">
                        Simuler un paiement réussi
                    </button>
                    <button type="submit" name="action" value="echouer" class="btn btn-echouer">
                        Simuler un échec
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </body>
</html>

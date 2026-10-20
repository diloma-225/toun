<?php
session_start();
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/paiement_config.php';

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function imageFormation(?string $image): string
{
    $nom = basename(str_replace('\\', '/', trim((string) $image)));

    if ($nom === '') {
        return '';
    }

    foreach (['uploads/formations/', 'uploads/', 'images/'] as $dossier) {
        if (is_file(__DIR__ . '/' . $dossier . $nom)) {
            return $dossier . rawurlencode($nom);
        }
    }

    return '';
}

function dureeFormation(int $minutes): string
{
    if ($minutes >= 60) {
        $heures = intdiv($minutes, 60);
        $reste = $minutes % 60;
        return $heures . ' h' . ($reste > 0 ? ' ' . str_pad((string) $reste, 2, '0', STR_PAD_LEFT) : '');
    }

    return $minutes . ' min';
}

if (!isset($_SESSION['id_user'])) {
    $id = (int) ($_GET['id'] ?? 0);
    header('Location: login.php?redirect=' . rawurlencode('acheter-formation.php?id=' . $id));
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: formation.php');
    exit();
}

$stmt = $con->prepare("SELECT f.*, c.nom_cat, u.nom_user, u.prenom_user, u.photo_user
    FROM t_formations f
    LEFT JOIN t_categories c ON c.id_cat=f.id_cat
    LEFT JOIN t_formateurs fo ON fo.id_formateur=f.id_formateur
    LEFT JOIN t_utilisateurs u ON u.id_user=fo.id_user
    WHERE f.id_form=:id AND f.statut_form='publie' LIMIT 1");
$stmt->execute([':id' => $id]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$formation) {
    http_response_code(404);
    exit('Formation introuvable.');
}

$userId = (int) $_SESSION['id_user'];

if (tounFormationAcquise($con, $userId, (int) $id)) {
    header('Location: apprenant/cours.php?id=' . (int) $id);
    exit();
}

$prix = (int) $formation['prix_form'];
$prixAffiche = number_format($prix, 0, ',', ' ') . ' FCFA';
$image = imageFormation($formation['img_form'] ?? '');
$formateur = trim(($formation['prenom_user'] ?? '') . ' ' . ($formation['nom_user'] ?? ''));

$moyens = [
    'WAVE' => ['nom' => 'Wave', 'logo' => 'images/paiement/wave.png'],
    'OM' => ['nom' => 'Orange Money', 'logo' => 'images/paiement/orange-money.png'],
    'MOOV' => ['nom' => 'Moov Money', 'logo' => 'images/paiement/moov-money.png']
];

if (empty($_SESSION['csrf_achat'])) {
    $_SESSION['csrf_achat'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_achat'];
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Paiement - <?= e($formation['titre_form']) ?> | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            :root {
                --bleu-nuit: #17345b;
                --bleu: #0b5a91;
                --bleu-fonce: #08497a;
                --turquoise: #16a2ba;
                --texte: #17345b;
                --texte-doux: #6b7f93;
                --bordure: #dfe8ee;
                --fond: #f4f8fb;
                --blanc: #ffffff;
                --rayon: 14px;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: var(--fond);
                color: var(--texte);
                font-family: "Poppins", Arial, sans-serif;
                font-size: 15px;
                line-height: 1.5;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .checkout-container {
                width: min(1040px, calc(100% - 32px));
                margin: 0 auto;
            }

            .checkout-header {
                background: var(--blanc);
                border-bottom: 1px solid var(--bordure);
            }

            .checkout-header__inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                height: 72px;
            }

            .checkout-header__logo img {
                display: block;
                height: 38px;
            }

            .checkout-header__back {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: var(--texte-doux);
                font-size: 14px;
                font-weight: 500;
            }

            .checkout-header__back:hover {
                color: var(--bleu);
            }

            .checkout-main {
                padding: 40px 0 64px;
            }

            .checkout-title {
                margin: 0 0 24px;
                font-size: 28px;
                font-weight: 700;
            }

            .checkout-grid {
                display: grid;
                grid-template-columns: 1fr 380px;
                gap: 24px;
                align-items: start;
            }

            .panel {
                padding: 28px;
                background: var(--blanc);
                border: 1px solid var(--bordure);
                border-radius: var(--rayon);
            }

            .panel__title {
                margin: 0 0 4px;
                font-size: 18px;
                font-weight: 600;
            }

            .panel__text {
                margin: 0 0 20px;
                color: var(--texte-doux);
                font-size: 14px;
            }

            .panel__info {
                margin: 20px 0 0;
                padding-top: 20px;
                border-top: 1px solid var(--bordure);
                color: var(--texte-doux);
                font-size: 13px;
            }

            .methods {
                display: grid;
                gap: 12px;
            }

            .method {
                position: relative;
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 14px 18px;
                border: 1px solid var(--bordure);
                border-radius: 12px;
                cursor: pointer;
                transition: border-color 0.15s;
            }

            .method:hover {
                border-color: #b9cddb;
            }

            .method:has(.method__input:checked) {
                border-color: var(--bleu);
                box-shadow: 0 0 0 1px var(--bleu);
            }

            .method__input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .method__logo {
                display: grid;
                place-items: center;
                width: 52px;
                height: 52px;
                flex-shrink: 0;
                padding: 6px;
                border: 1px solid var(--bordure);
                border-radius: 10px;
                background: var(--blanc);
            }

            .method__logo img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }

            .method__name {
                flex: 1;
                font-weight: 600;
            }

            .method__check {
                width: 20px;
                height: 20px;
                flex-shrink: 0;
                border: 2px solid #c3d2dd;
                border-radius: 50%;
            }

            .method__input:checked ~ .method__check {
                border: 6px solid var(--bleu);
            }

            .method__input:focus-visible ~ .method__check {
                outline: 2px solid var(--turquoise);
                outline-offset: 2px;
            }

            .summary__product {
                display: flex;
                gap: 14px;
                margin: 18px 0;
                padding-bottom: 18px;
                border-bottom: 1px solid var(--bordure);
            }

            .summary__image {
                width: 84px;
                height: 60px;
                flex-shrink: 0;
                border-radius: 8px;
                object-fit: cover;
            }

            .summary__name {
                margin: 0;
                font-weight: 600;
                line-height: 1.35;
            }

            .summary__meta {
                margin: 4px 0 0;
                color: var(--texte-doux);
                font-size: 13px;
            }

            .summary__lines {
                margin: 0;
            }

            .summary__line {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 8px 0;
                font-size: 14px;
            }

            .summary__line dt {
                color: var(--texte-doux);
            }

            .summary__line dd {
                margin: 0;
                font-weight: 500;
                text-align: right;
            }

            .summary__line--total {
                margin-top: 8px;
                padding-top: 16px;
                border-top: 1px solid var(--bordure);
                font-size: 17px;
            }

            .summary__line--total dt,
            .summary__line--total dd {
                color: var(--texte);
                font-weight: 700;
            }

            .summary__button {
                width: 100%;
                margin-top: 20px;
                padding: 15px;
                border: 0;
                border-radius: 10px;
                background: var(--bleu);
                color: var(--blanc);
                font: inherit;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.15s;
            }

            .summary__button:hover {
                background: var(--bleu-fonce);
            }

            .summary__button:disabled {
                background: #7c9cb8;
                cursor: wait;
            }

            .summary__secure {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                margin: 14px 0 0;
                color: var(--texte-doux);
                font-size: 12.5px;
                text-align: center;
            }

            @media (max-width: 860px) {
                .checkout-grid {
                    grid-template-columns: 1fr;
                }

                .checkout-main {
                    padding: 28px 0 48px;
                }

                .panel {
                    padding: 22px;
                }
            }
        </style>
    </head>
    <body class="checkout">
        <header class="checkout-header">
            <div class="checkout-container checkout-header__inner">
                <a href="index.php" class="checkout-header__logo">
                    <img src="images/logo.png" alt="TOUN">
                </a>
                <a href="formation-details.php?id=<?= (int) $id ?>" class="checkout-header__back">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour à la formation
                </a>
            </div>
        </header>

        <main class="checkout-container checkout-main">
            <h1 class="checkout-title">Paiement</h1>

            <form method="POST" action="paiement-init.php" class="checkout-grid" id="formulaire-paiement">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id_form" value="<?= (int) $id ?>">

                <section class="panel">
                    <h2 class="panel__title">Moyen de paiement</h2>
                    <p class="panel__text">Choisissez l'opérateur avec lequel vous souhaitez payer.</p>

                    <div class="methods">
                        <?php foreach ($moyens as $code => $moyen): ?>
                            <label class="method">
                                <input
                                    type="radio"
                                    name="mode_paiement"
                                    value="<?= e($code) ?>"
                                    class="method__input"
                                    <?= $code === 'WAVE' ? 'checked' : '' ?>
                                >
                                <span class="method__logo">
                                    <img src="<?= e($moyen['logo']) ?>" alt="">
                                </span>
                                <span class="method__name"><?= e($moyen['nom']) ?></span>
                                <span class="method__check"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <p class="panel__info">
                        Vous serez redirigé vers la page de paiement de notre partenaire pour valider l'opération sur votre téléphone.
                    </p>
                </section>

                <aside class="panel summary">
                    <h2 class="panel__title">Votre commande</h2>

                    <div class="summary__product">
                        <?php if ($image !== ''): ?>
                            <img src="<?= e($image) ?>" alt="" class="summary__image">
                        <?php endif; ?>
                        <div>
                            <p class="summary__name"><?= e($formation['titre_form']) ?></p>
                            <?php if ($formateur !== ''): ?>
                                <p class="summary__meta">Par <?= e($formateur) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <dl class="summary__lines">
                        <div class="summary__line">
                            <dt>Catégorie</dt>
                            <dd><?= e($formation['nom_cat'] ?: 'Formation') ?></dd>
                        </div>
                        <div class="summary__line">
                            <dt>Durée</dt>
                            <dd><?= e(dureeFormation((int) $formation['duree_form'])) ?></dd>
                        </div>
                        <div class="summary__line summary__line--total">
                            <dt>Total</dt>
                            <dd><?= e($prixAffiche) ?></dd>
                        </div>
                    </dl>

                    <button type="submit" class="summary__button">
                        Payer <?= e($prixAffiche) ?>
                    </button>

                    <p class="summary__secure">
                        <i class="fa-solid fa-lock"></i>
                        Paiement sécurisé. TOUN n'a jamais accès à votre code secret.
                    </p>
                </aside>
            </form>
        </main>

        <script>
            document.getElementById('formulaire-paiement').addEventListener('submit', function (evenement) {
                const bouton = this.querySelector('.summary__button');

                if (bouton.disabled) {
                    evenement.preventDefault();
                    return;
                }

                bouton.disabled = true;
                bouton.textContent = 'Redirection vers le paiement...';
            });
        </script>
    </body>
</html>

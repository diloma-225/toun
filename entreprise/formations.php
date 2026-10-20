<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'formations.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Mes formations — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .formation-toolbar {
                padding: 10px 13px;
                border-bottom: 1px solid #edf2f4;
                display: flex;
                justify-content: flex-end;
            }

            .search {
                width: 300px;
                height: 32px;
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 0 10px;
                border: 1px solid #dce8ed;
                border-radius: 9px;
                background: #fafcfd;
            }

            .search i {
                font-size: 11px;
                color: #8ca4b2;
            }

            .search input {
                width: 100%;
                border: 0;
                outline: 0;
                background: transparent;
                font: 10.5px Poppins;
                color: #405d76;
            }

            .formation-grid {
                padding: 13px;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 11px;
            }

            .formation-card {
                border: 1px solid #dce9ef;
                border-radius: 12px;
                background: #fff;
                overflow: hidden;
                transition: 0.2s;
            }

            .formation-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 25px rgba(19, 64, 96, 0.1);
                border-color: #a8d5de;
            }

            .formation-image {
                height: 100px;
                margin: 7px;
                border-radius: 9px;
                background: linear-gradient(135deg, #edf8fa, #edf2fc);
                display: grid;
                place-items: center;
                color: #20a1b8;
                font-size: 22px;
                overflow: hidden;
            }

            .formation-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .formation-body {
                padding: 5px 11px 11px;
            }

            .paid {
                display: inline-flex;
                gap: 4px;
                align-items: center;
                padding: 3px 6px;
                border-radius: 5px;
                background: #e9f8f5;
                color: #23866e;
                font-size: 9.5px;
                font-weight: 800;
            }

            .formation-body h2 {
                font-size: 11.5px;
                margin: 7px 0 4px;
            }

            .formation-body p {
                color: #8799a6;
                font-size: 10px;
                line-height: 1.5;
                min-height: 31px;
            }

            .formation-bottom {
                display: flex;
                justify-content: space-between;
                margin-top: 8px;
                color: #81929f;
                font-size: 10px;
            }

            .formation-bottom strong {
                color: #173d62;
                font-size: 10.5px;
            }

            .assign-link {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 9px;
                padding-top: 8px;
                border-top: 1px solid #edf2f4;
                color: #168b9a;
                font-size: 10px;
                font-weight: 700;
            }

            .empty-purchase {
                min-height: 300px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px dashed #b9dfe5;
                border-radius: 15px;
                background: linear-gradient(135deg, #fbfeff, #eef9fa);
                text-align: center;
            }

            .empty-purchase > div {
                max-width: 360px;
            }

            .empty-purchase i {
                font-size: 28px;
                color: #20a1b8;
            }

            .empty-purchase h2 {
                margin: 9px 0 5px;
            }

            .empty-purchase p {
                color: #8a9ca9;
                font-size: 10.5px;
                line-height: 1.7;
                margin-bottom: 12px;
            }

            @media (max-width: 900px) {
                .formation-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 600px) {
                .formation-grid {
                    grid-template-columns: 1fr;
                }

                .search {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">
                <?php
$q = trim($_GET['q'] ?? '');
$purchased = enterprisePurchasedFormations($con, $userId);
if ($q !== '') {
    $purchased = array_values(
        array_filter($purchased, fn($f) => stripos($f['titre_form'] . ' ' . ($f['descrip_form'] ?? ''), $q) !== false)
    );
}
?>

                <div class="page-head">
                    <div>
                        <span class="eyebrow">FORMATION</span>
                        <h1>Formations de l’entreprise</h1>
                        <p>Seules les formations dont le paiement est confirmé apparaissent ici.</p>
                    </div>
                    <a class="btn" href="../formation.php"><i class="fa-solid fa-store"></i> Accéder au catalogue</a>
                </div>

                <?php if (!$purchased): ?>
                    <section class="empty-purchase">
                        <div>
                            <i class="fa-solid fa-cart-shopping"></i>
                            <h2>Aucune formation achetée</h2>
                            <p>Votre entreprise doit d’abord acheter une formation avant de pouvoir l’affecter à un collaborateur.</p>
                            <a class="btn" href="../formation.php">Voir le catalogue et acheter</a>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="panel">
                        <div class="formation-toolbar">
                            <form class="search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input name="q" value="<?= e($q) ?>" placeholder="Rechercher dans mes formations...">
                            </form>
                        </div>
                        <div class="formation-grid">
                            <?php foreach ($purchased as $f): ?>
                                <article class="formation-card">
                                    <div class="formation-image">
                                        <?php $img = trim(str_replace('\\', '/', (string)($f['img_form'] ?? ''))); if ($img): ?>
                                            <img src="<?= e('../' . (strpos($img, '/') === false ? 'uploads/formations/' . $img : ltrim($img, '/'))) ?>" alt="">
                                        <?php else: ?>
                                            <i class="fa-solid fa-book-open"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="formation-body">
                                        <span class="paid"><i class="fa-solid fa-circle-check"></i> Achetée</span>
                                        <h2><?= e($f['titre_form']) ?></h2>
                                        <p><?= e(mb_substr(strip_tags($f['descrip_form'] ?? ''), 0, 120, 'UTF-8')) ?><?= mb_strlen(strip_tags($f['descrip_form'] ?? ''), 'UTF-8') > 120 ? '…' : '' ?></p>
                                        <div class="formation-bottom">
                                            <span><?= e($f['duree_form'] ?? 'Durée non précisée') ?></span>
                                            <strong><?= number_format((float) ($f['montant_total'] ?? 0), 0, ' ', ' ') ?> FCFA</strong>
                                        </div>
                                        <a class="assign-link" href="affectations.php?formation=<?= $f['id_form'] ?>">Affecter à des collaborateurs <i class="fa-solid fa-arrow-right"></i></a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

            </div>
        </main>
    </body>
</html>

<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';

$pdo = toun_db();

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'categories' => array_map('intval', $_GET['cat'] ?? []),
    'niveaux' => array_values(
        array_intersect((array) ($_GET['niveau'] ?? []), ['debutant', 'intermediaire', 'avance'])
    ),
    'durees' => array_values(array_intersect((array) ($_GET['duree'] ?? []), array_keys(DUREE_BORNES))),
    'prix' => array_values(array_intersect((array) ($_GET['prix'] ?? []), ['gratuit', 'payant']))
];

$tri = isset(TRIS_AUTORISES[$_GET['tri'] ?? '']) ? $_GET['tri'] : 'populaire';
$page = max(1, (int) ($_GET['page'] ?? 1));
$parPage = 12;

$categories = get_categories_with_counts($pdo);
$facets = get_facet_counts($pdo);
$heroStats = get_hero_stats($pdo);
$userWidgets = get_user_widgets($pdo, $_SESSION['id_user'] ?? null);

$resultat = get_formations($pdo, $filters, $tri, $page, $parPage);
$formations = $resultat['rows'];
$totalResultats = $resultat['total'];
$totalPages = max(1, (int) ceil($totalResultats / $parPage));

$totalFormationsToutesCategories = array_sum(array_column($categories, 'total'));

function url_avec(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nos formations — TOUN</title>
        <link rel="stylesheet" href="assets/css/formations.css">
    </head>
    <body>

        <?php ?>
        <header class="site-header">
            <div class="header-inner">
                <a href="index.php" class="logo">
                    <img src="assets/img/logo-toun.png" alt="TOUN">
                </a>
                <nav class="main-nav">
                    <a href="index.php">Accueil</a>
                    <a href="formations.php" class="active">Formations</a>
                    <a href="solutions.php">Solutions</a>
                    <a href="campus.php">Campus</a>
                    <a href="tarifs.php">Tarifs</a>
                    <a href="a-propos.php">À propos</a>
                    <a href="contact.php">Contact</a>
                </nav>
                <form class="header-search" action="formations.php" method="get">
                    <input type="search" name="q" value="<?= h($filters['q']) ?>" placeholder="Rechercher une formation...">
                    <button type="submit" aria-label="Rechercher">🔍</button>
                </form>
                <div class="header-actions">
                    <?php if (!empty($_SESSION['id_user'])): ?>
                        <a href="mon-compte.php" class="btn-ghost">Mon compte</a>
                    <?php else: ?>
                        <a href="connexion.php" class="btn-ghost">Se connecter</a>
                    <?php endif; ?>
                    <a href="inscription.php" class="btn-primary">Commencer gratuitement</a>
                </div>
            </div>
        </header>

        <div class="breadcrumb">
            <div class="breadcrumb-inner">
                <a href="index.php">Accueil</a> <span>›</span> <span>Formations</span>
            </div>
        </div>

        <?php ?>
        <section class="hero">
            <div class="hero-inner">
                <div class="hero-text">
                    <h1>Nos formations</h1>
                    <p>Découvrez des formations conçues par des experts pour développer vos compétences et booster votre carrière.</p>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <strong><?= h((string) $heroStats['formations']) ?>+</strong>
                            <span>Formations</span>
                        </div>
                        <div class="hero-stat">
                            <strong><?= h(number_format($heroStats['apprenants'], 0, ',', ' ')) ?>+</strong>
                            <span>Apprenants</span>
                        </div>
                        <div class="hero-stat">
                            <strong><?= h((string) $heroStats['taux_reussite']) ?>%</strong>
                            <span>Taux de réussite aux quiz</span>
                        </div>
                    </div>
                </div>
                <div class="hero-media">
                    <img src="assets/img/hero-formations.jpg" alt="">
                    <?php if ($userWidgets !== null): ?>
                        <div class="floating-card floating-progression">
                            <span class="floating-label">Progression globale</span>
                            <strong><?= h((string) $userWidgets['progression_globale']) ?>%</strong>
                            <div class="progress-bar">
                                <div style="width:<?= (int) $userWidgets['progression_globale'] ?>%"></div>
                            </div>
                        </div>
                        <div class="floating-card floating-certificats">
                            <span class="floating-label">Certificats obtenus</span>
                            <strong>🏆 <?= h((string) $userWidgets['certificats_obtenus']) ?></strong>
                            <a href="mes-certificats.php">Voir mes certificats →</a>
                        </div>
                    <?php else: ?>
                        <div class="floating-card floating-cta">
                            <span class="floating-label">Suivez votre progression</span>
                            <a href="inscription.php" class="btn-primary btn-small">Créer un compte gratuit</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php ?>
        <section class="catalogue">
            <div class="catalogue-inner">

                <?php ?>
                <aside class="filtres">
                    <form method="get" action="formations.php" id="form-filtres">
                        <?php if ($filters['q'] !== ''): ?>
                            <input type="hidden" name="q" value="<?= h($filters['q']) ?>">
                        <?php endif; ?>

                        <h2>☰ Filtres</h2>

                        <div class="filtre-groupe">
                            <h3>Catégories</h3>
                            <label class="filtre-item">
                                <input type="radio" name="cat_radio" value="" <?= empty($filters['categories']) ? 'checked' : '' ?> onclick="document.querySelectorAll('input[name=\'cat[]\']').forEach(c=>c.checked=false); document.getElementById('form-filtres').submit();">
                                Tous les domaines
                                <span class="count"><?= h((string) $totalFormationsToutesCategories) ?></span>
                            </label>
                            <?php foreach ($categories as $cat): ?>
                                <label class="filtre-item">
                                    <input type="checkbox" name="cat[]" value="<?= (int) $cat['id_cat'] ?>"
                                        <?= in_array((int) $cat['id_cat'], $filters['categories'], true) ? 'checked' : '' ?>
                                        onchange="document.getElementById('form-filtres').submit();">
                                    <?= h($cat['nom_cat']) ?>
                                    <span class="count"><?= h((string) $cat['total']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filtre-groupe">
                            <h3>Niveau</h3>
                            <?php
                    $labelsNiveau = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];
                    foreach ($labelsNiveau as $val => $label): ?>
                                <label class="filtre-item">
                                    <input type="checkbox" name="niveau[]" value="<?= $val ?>"
                                        <?= in_array($val, $filters['niveaux'], true) ? 'checked' : '' ?>
                                        onchange="document.getElementById('form-filtres').submit();">
                                    <?= $label ?>
                                    <span class="count"><?= h((string) $facets['niveau'][$val]) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filtre-groupe">
                            <h3>Durée</h3>
                            <?php
                    $labelsDuree = [
                        'moins_2h' => 'Moins de 2h', 'de_2h_a_10h' => '2h à 10h',
                        'de_10h_a_30h' => '10h à 30h', 'plus_30h' => 'Plus de 30h',
                    ];
                    foreach ($labelsDuree as $val => $label): ?>
                                <label class="filtre-item">
                                    <input type="checkbox" name="duree[]" value="<?= $val ?>"
                                        <?= in_array($val, $filters['durees'], true) ? 'checked' : '' ?>
                                        onchange="document.getElementById('form-filtres').submit();">
                                    <?= $label ?>
                                    <span class="count"><?= h((string) $facets['duree'][$val]) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filtre-groupe">
                            <h3>Prix</h3>
                            <?php foreach (['gratuit' => 'Gratuit', 'payant' => 'Payant'] as $val => $label): ?>
                                <label class="filtre-item">
                                    <input type="checkbox" name="prix[]" value="<?= $val ?>"
                                        <?= in_array($val, $filters['prix'], true) ? 'checked' : '' ?>
                                        onchange="document.getElementById('form-filtres').submit();">
                                    <?= $label ?>
                                    <span class="count"><?= h((string) $facets['prix'][$val]) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <a href="formations.php" class="btn-reset">↺ Réinitialiser les filtres</a>
                    </form>
                </aside>

                <?php ?>
                <div class="resultats">
                    <div class="resultats-header">
                        <span><?= h((string) $totalResultats) ?> formation<?= $totalResultats > 1 ? 's' : '' ?> disponible<?= $totalResultats > 1 ? 's' : '' ?></span>
                        <form method="get" action="formations.php" class="tri-form">
                            <?php foreach (['q','cat','niveau','duree','prix'] as $keep): if (isset($_GET[$keep])): foreach ((array)$_GET[$keep] as $v): ?>
                                <input type="hidden" name="<?= h($keep) ?><?= is_array($_GET[$keep]) ? '[]' : '' ?>" value="<?= h((string) $v) ?>">
                            <?php endforeach; endif; endforeach; ?>
                            <label>Trier par :
                                <select name="tri" onchange="this.form.submit()">
                                    <option value="populaire" <?= $tri === 'populaire' ? 'selected' : '' ?>>Les plus populaires</option>
                                    <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>>Les plus récentes</option>
                                    <option value="prix_asc" <?= $tri === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                                    <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                                </select>
                            </label>
                        </form>
                    </div>

                    <?php if (empty($formations)): ?>
                        <p class="aucun-resultat">Aucune formation ne correspond à ces critères pour le moment.</p>
                    <?php else: ?>
                        <div class="grille-formations">
                            <?php foreach ($formations as $f): ?>
                                <a class="carte-formation" href="formation.php?id=<?= (int) $f['id_form'] ?>">
                                    <div class="carte-image">
                                        <img src="<?= h($f['img_form'] ?: 'assets/img/formation-default.jpg') ?>" alt="<?= h($f['titre_form']) ?>" loading="lazy">
                                        <span class="badge-categorie"><?= h($f['nom_cat']) ?></span>
                                        <button type="button" class="btn-favori" aria-label="Ajouter aux favoris" onclick="event.preventDefault(); this.classList.toggle('actif');">🔖</button>
                                    </div>
                                    <div class="carte-corps">
                                        <h3><?= h($f['titre_form']) ?></h3>
                                        <p><?= h($f['descrip_form']) ?></p>
                                        <div class="carte-meta">
                                            <span>👥 <?= h(number_format((int) $f['nb_inscrits'], 0, ',', ' ')) ?> inscrits</span>
                                            <span>🕒 <?= h(format_duration((int) $f['duree_totale'])) ?></span>
                                        </div>
                                        <div class="carte-prix"><?= h(format_price((int) $f['prix_form'])) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="pagination" aria-label="Pagination">
                                <a href="<?= url_avec(['page' => max(1, $page - 1)]) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">‹</a>
                                <?php
                        $debut = max(1, $page - 2);
                        $fin   = min($totalPages, $page + 2);
                        if ($debut > 1) echo '<a href="' . url_avec(['page' => 1]) . '">1</a><span class="ellipsis">…</span>';
                        for ($p = $debut; $p <= $fin; $p++):
                        ?>
                                    <a href="<?= url_avec(['page' => $p]) ?>" class="<?= $p === $page ? 'actif' : '' ?>"><?= $p ?></a>
                                <?php endfor;
                        if ($fin < $totalPages) echo '<span class="ellipsis">…</span><a href="' . url_avec(['page' => $totalPages]) . '">' . $totalPages . '</a>';
                        ?>
                                <a href="<?= url_avec(['page' => min($totalPages, $page + 1)]) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">›</a>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php ?>
        <section class="cta-bandeau">
            <div class="cta-inner">
                <div class="cta-illustration">🎓</div>
                <div class="cta-texte">
                    <h2>Développez vos compétences et obtenez des certificats reconnus</h2>
                    <p>Rejoignez des milliers d'apprenants et accédez à des formations de qualité conçues par des experts.</p>
                    <a href="inscription.php" class="btn-primary">Commencer gratuitement →</a>
                </div>
                <div class="cta-avantages">
                    <div><strong>📋 Apprentissage flexible</strong><span>Apprenez à votre rythme, 24h/24 et 7j/7.</span></div>
                    <div><strong>🏅 Certificats reconnus</strong><span>Obtenez des certificats appréciés par les entreprises.</span></div>
                    <div><strong>🎯 Contenu de qualité</strong><span>Des formations conçues par des experts.</span></div>
                    <div><strong>📈 Suivi de progression</strong><span>Suivez votre évolution et atteignez vos objectifs.</span></div>
                </div>
            </div>
        </section>

        <?php require __DIR__ . '/inc/footer.php'; ?>
    </body>
</html>

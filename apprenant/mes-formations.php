<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

$idUser = (int) $_SESSION['id_user'];

$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));
$photo = trim((string) ($_SESSION['photo_user'] ?? ''));

$nomAffiche = trim($prenom . ' ' . $nom);
if ($nomAffiche === '') {
    $nomAffiche = 'Apprenant';
}

function photoUrl(string $photo): string
{
    if ($photo === '') {
        return '';
    }

    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $nomPhoto = basename($photo);

    $candidats = [
        [
            'fichier' => __DIR__ . '/' . $photo,
            'url' => $photo
        ],
        [
            'fichier' => __DIR__ . '/../uploads/profils/' . $nomPhoto,
            'url' => '../uploads/profils/' . rawurlencode($nomPhoto)
        ],
        [
            'fichier' => __DIR__ . '/../uploads/' . $nomPhoto,
            'url' => '../uploads/' . rawurlencode($nomPhoto)
        ],
        [
            'fichier' => __DIR__ . '/../assets/uploads/' . $nomPhoto,
            'url' => '../assets/uploads/' . rawurlencode($nomPhoto)
        ],
        [
            'fichier' => __DIR__ . '/../images/profils/' . $nomPhoto,
            'url' => '../images/profils/' . rawurlencode($nomPhoto)
        ],
        [
            'fichier' => __DIR__ . '/../images/' . $nomPhoto,
            'url' => '../images/' . rawurlencode($nomPhoto)
        ]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat['fichier'])) {
            return $candidat['url'];
        }
    }

    return '';
}

function formationImageUrl(string $image): string
{
    if ($image === '') {
        return '';
    }

    $image = str_replace('\\', '/', trim($image));
    $image = ltrim($image, '/');
    $nomImage = basename($image);

    $candidats = [
        [
            'fichier' => __DIR__ . '/../' . $image,
            'url' => '../' . $image
        ],
        [
            'fichier' => __DIR__ . '/../images/formations/' . $nomImage,
            'url' => '../images/formations/' . rawurlencode($nomImage)
        ],
        [
            'fichier' => __DIR__ . '/../uploads/formations/' . $nomImage,
            'url' => '../uploads/formations/' . rawurlencode($nomImage)
        ],
        [
            'fichier' => __DIR__ . '/../uploads/' . $nomImage,
            'url' => '../uploads/' . rawurlencode($nomImage)
        ]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat['fichier'])) {
            return $candidat['url'];
        }
    }

    return '';
}

$photoUrl = photoUrl($photo);

$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));

if ($initiales === '') {
    $initiales = 'TO';
}

$formations = [];

$sql = "
    SELECT
        f.id_form,
        f.titre_form,
        f.descrip_form,
        f.img_form,
        f.niveau_form,
        f.duree_form,
        c.nom_cat,
        i.statut_inscrip,
        i.date_inscrip_form,
        COALESCE(p.pourcent_avancement, 0) AS progression,
        COALESCE(p.statut_progression, 'non_commence') AS statut_progression
    FROM t_inscriptions i
    INNER JOIN t_formations f
        ON f.id_form = i.id_form
    LEFT JOIN t_categories c
        ON c.id_cat = f.id_cat
    LEFT JOIN t_progression p
        ON p.id_user = i.id_user
        AND p.id_form = i.id_form
    WHERE i.id_user = ?
    ORDER BY i.date_inscrip_form DESC
";

try {
    $req = $con->prepare($sql);
    $req->execute([$idUser]);
    $formations = $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $formations = [];
}

$totalFormations = count($formations);
$enCours = 0;
$terminees = 0;

foreach ($formations as $formation) {
    $progression = (float) $formation['progression'];

    if (
        $progression >= 100 ||
        $formation['statut_progression'] === 'termine' ||
        $formation['statut_inscrip'] === 'terminee'
    ) {
        $terminees++;
    } else {
        $enCours++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Mes formations | TOUN</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
            rel="stylesheet">

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">

        <style>
            .page-header {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 20px;
            }

            .page-title h1 {
                font-size: 24px;
                color: #17223f;
                margin-bottom: 5px;
            }

            .page-title p {
                font-size: 11.5px;
                color: #7b899b;
            }

            .formation-summary {
                display: flex;
                gap: 8px;
            }

            .summary-item {
                padding: 8px 12px;
                border-radius: 10px;
                background: #fff;
                border: 1px solid #e1e9ef;
                font-size: 11px;
                color: #69768a;
            }

            .summary-item strong {
                color: #20a1b8;
                font-size: 12.5px;
                margin-right: 3px;
            }

            .formation-tabs {
                display: flex;
                gap: 7px;
                margin-bottom: 18px;
            }

            .formation-tab {
                border: 1px solid #dfe8ee;
                background: #fff;
                color: #718096;
                padding: 8px 14px;
                border-radius: 9px;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
            }

            .formation-tab.active {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                border-color: transparent;
                color: #fff;
            }

            .formations-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .my-formation-card {
                background: #fff;
                border: 1px solid #e1e9ef;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 9px 28px rgba(24, 33, 63, 0.045);
                transition: 0.22s ease;
            }

            .my-formation-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 15px 30px rgba(24, 33, 63, 0.09);
            }

            .formation-image {
                height: 145px;
                position: relative;
                overflow: hidden;
                background: linear-gradient(135deg, #172443, #20a1b8);
            }

            .formation-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .formation-image > i {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: rgba(255, 255, 255, 0.6);
                font-size: 30px;
            }

            .formation-status {
                position: absolute;
                top: 10px;
                left: 10px;
                padding: 5px 8px;
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.94);
                color: #20a1b8;
                font-size: 10px;
                font-weight: 700;
            }

            .formation-card-body {
                padding: 14px;
            }

            .formation-category {
                font-size: 10.5px;
                color: #20a1b8;
                font-weight: 700;
                text-transform: uppercase;
                margin-bottom: 5px;
            }

            .formation-card-body h3 {
                font-size: 12.5px;
                line-height: 1.4;
                color: #18213f;
                margin-bottom: 6px;
            }

            .formation-description {
                font-size: 11px;
                line-height: 1.55;
                color: #8993a4;
                height: 39px;
                overflow: hidden;
                margin-bottom: 10px;
            }

            .formation-meta {
                display: flex;
                gap: 12px;
                margin-bottom: 12px;
            }

            .formation-meta span {
                font-size: 10.5px;
                color: #7c8798;
            }

            .formation-meta i {
                color: #20a1b8;
                margin-right: 3px;
            }

            .progress-info {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }

            .progress-info span {
                font-size: 10.5px;
                color: #8993a4;
            }

            .progress-info strong {
                font-size: 11px;
                color: #20a1b8;
            }

            .formation-progress {
                height: 6px;
                background: #edf1f5;
                border-radius: 20px;
                overflow: hidden;
                margin-bottom: 12px;
            }

            .formation-progress span {
                display: block;
                height: 100%;
                background: linear-gradient(90deg, #20a1b8, #3f68ae);
                border-radius: 20px;
            }

            .formation-card-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }

            .formation-level {
                font-size: 10.5px;
                color: #8993a4;
            }

            .continue-link {
                height: 31px;
                padding: 0 11px;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                font-size: 10.5px;
                font-weight: 600;
            }

            .formations-empty {
                grid-column: 1 / -1;
                background: #fff;
                border: 1px solid #e1e9ef;
                border-radius: 18px;
                padding: 55px 20px;
                text-align: center;
            }

            .formations-empty-icon {
                width: 60px;
                height: 60px;
                margin: 0 auto 13px;
                border-radius: 18px;
                background: linear-gradient(135deg, #e9f8fa, #edf2fb);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #20a1b8;
                font-size: 22px;
            }

            .formations-empty h2 {
                font-size: 14px;
                color: #18213f;
                margin-bottom: 6px;
            }

            .formations-empty p {
                max-width: 400px;
                margin: 0 auto 16px;
                font-size: 11px;
                line-height: 1.7;
                color: #8993a4;
            }

            @media (max-width: 1100px) {
                .formations-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 800px) {
                .page-header {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .formation-summary {
                    width: 100%;
                }

                .summary-item {
                    flex: 1;
                }

                .formations-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 520px) {
                .page-title h1 {
                    font-size: 20px;
                }

                .formation-tabs {
                    overflow-x: auto;
                }

                .formation-tab {
                    white-space: nowrap;
                }
            }
        </style>
    </head>

    <body>

        <div class="layout">

            <?php include __DIR__ . '/menu.php'; ?>

            <main class="main">

                <header class="topbar">

                    <button class="mobile-menu" type="button" id="mobileMenu">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Rechercher une formation...">
                    </div>

                    <div class="top-right">

                        <a href="rendez-vous.php" class="top-action">
                            <i class="fa-regular fa-calendar-check"></i>
                            <span>Mes rendez-vous</span>
                        </a>

                        <a href="notifications.php" class="notification">
                            <i class="fa-regular fa-bell"></i>
                            <span></span>
                        </a>

                        <a href="profil.php" class="profile">

                            <div class="profile-avatar">
                                <?php if ($photoUrl !== ''): ?>
                                    <img
                                        src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Photo de profil">
                                <?php else: ?>
                                    <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>

                            <div class="profile-info">
                                <strong><?= htmlspecialchars($nomAffiche, ENT_QUOTES, 'UTF-8') ?></strong>
                                <small>Apprenant</small>
                            </div>

                        </a>

                    </div>
                </header>

                <section class="content">

                    <div class="page-header">

                        <div class="page-title">
                            <h1>Mes formations</h1>
                            <p>Retrouvez toutes les formations auxquelles vous avez accès.</p>
                        </div>

                        <div class="formation-summary">

                            <div class="summary-item">
                                <strong><?= $totalFormations ?></strong>
                                formation<?= $totalFormations > 1 ? 's' : '' ?>
                            </div>

                            <div class="summary-item">
                                <strong><?= $enCours ?></strong>
                                en cours
                            </div>

                            <div class="summary-item">
                                <strong><?= $terminees ?></strong>
                                terminée<?= $terminees > 1 ? 's' : '' ?>
                            </div>

                        </div>

                    </div>

                    <div class="formation-tabs">

                        <button class="formation-tab active" data-filter="all">
                            Toutes
                        </button>

                        <button class="formation-tab" data-filter="cours">
                            En cours
                        </button>

                        <button class="formation-tab" data-filter="termine">
                            Terminées
                        </button>

                    </div>

                    <div class="formations-grid">

                        <?php if (!empty($formations)): ?>

                            <?php foreach ($formations as $formation): ?>

                                <?php
$progression = max(0, min(100, round((float) $formation['progression'])));

$estTerminee =
    $progression >= 100 ||
    $formation['statut_progression'] === 'termine' ||
    $formation['statut_inscrip'] === 'terminee';

$imageUrl = formationImageUrl((string) ($formation['img_form'] ?? ''));
?>

                                <article
                                    class="my-formation-card"
                                    data-status="<?= $estTerminee ? 'termine' : 'cours' ?>">

                                    <div class="formation-image">

                                        <?php if ($imageUrl !== ''): ?>

                                            <img
                                                src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                alt="<?= htmlspecialchars($formation['titre_form'], ENT_QUOTES, 'UTF-8') ?>">

                                        <?php else: ?>

                                            <i class="fa-solid fa-book-open"></i>

                                        <?php endif; ?>

                                        <span class="formation-status">

                                            <?php if ($estTerminee): ?>
                                                <i class="fa-solid fa-check"></i> Terminée
                                            <?php elseif ($progression > 0): ?>
                                                <i class="fa-solid fa-play"></i> En cours
                                            <?php else: ?>
                                                <i class="fa-solid fa-circle-play"></i> À commencer
                                            <?php endif; ?>

                                        </span>

                                    </div>

                                    <div class="formation-card-body">

                                        <?php if (!empty($formation['nom_cat'])): ?>
                                            <div class="formation-category">
                                                <?= htmlspecialchars($formation['nom_cat'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>

                                        <h3>
                                            <?= htmlspecialchars($formation['titre_form'], ENT_QUOTES, 'UTF-8') ?>
                                        </h3>

                                        <p class="formation-description">
                                            <?= htmlspecialchars(mb_strimwidth(strip_tags((string) $formation['descrip_form']), 0, 115, '...'), ENT_QUOTES, 'UTF-8') ?>
                                        </p>

                                        <div class="formation-meta">

                                            <span>
                                                <i class="fa-regular fa-clock"></i>
                                                <?= htmlspecialchars($formation['duree_form'] ?: '--', ENT_QUOTES, 'UTF-8') ?>
                                            </span>

                                            <span>
                                                <i class="fa-solid fa-layer-group"></i>
                                                <?= htmlspecialchars($formation['niveau_form'] ?: '--', ENT_QUOTES, 'UTF-8') ?>
                                            </span>

                                        </div>

                                        <div class="progress-info">
                                            <span>Progression</span>
                                            <strong><?= $progression ?>%</strong>
                                        </div>

                                        <div class="formation-progress">
                                            <span style="width: <?= $progression ?>%"></span>
                                        </div>

                                        <div class="formation-card-footer">

                                            <span class="formation-level">

                                                <?php if ($estTerminee): ?>
                                                    Formation terminée
                                                <?php elseif ($progression > 0): ?>
                                                    Continuez votre apprentissage
                                                <?php else: ?>
                                                    Commencez votre formation
                                                <?php endif; ?>

                                            </span>

                                            <a
                                                href="cours.php?id=<?= (int) $formation['id_form'] ?>"
                                                class="continue-link">

                                                <?= $estTerminee ? 'Revoir' : ($progression > 0 ? 'Continuer' : 'Commencer') ?>

                                                <i class="fa-solid fa-arrow-right"></i>
                                            </a>

                                        </div>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="formations-empty">

                                <div class="formations-empty-icon">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </div>

                                <h2>Vous n'avez encore aucune formation</h2>

                                <p>
                                    Après votre achat ou votre inscription à une formation,
                                    celle-ci apparaîtra automatiquement dans votre espace.
                                </p>

                                <a href="../formation.php" class="primary-btn">
                                    Découvrir les formations
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>

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
                mobileMenu.addEventListener('click', () => {
                    sidebar.classList.toggle('open');
                });
            }

            const tabs = document.querySelectorAll('.formation-tab');
            const cards = document.querySelectorAll('.my-formation-card');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(item => item.classList.remove('active'));
                    tab.classList.add('active');

                    const filter = tab.dataset.filter;

                    cards.forEach(card => {
                        card.style.display = filter === 'all' || card.dataset.status === filter ? '' : 'none';
                    });
                });
            });
        </script>

    </body>
</html>
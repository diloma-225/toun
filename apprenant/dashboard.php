<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

$idUser = (int) $_SESSION['id_user'];

$estCollaborateur = false;
$entrepriseNom = '';
$entrepriseId = 0;

if (tableExiste($con, 't_entreprise_collaborateurs') && tableExiste($con, 't_entreprises')) {
    try {
        $reqEntreprise = $con->prepare("
            SELECT ec.id_entreprise, e.nom_entreprise
            FROM t_entreprise_collaborateurs ec
            INNER JOIN t_entreprises e
                ON e.id_entreprise = ec.id_entreprise
            WHERE ec.id_user = ?
              AND LOWER(ec.statut) = 'actif'
            LIMIT 1
        ");
        $reqEntreprise->execute([$idUser]);
        $entreprise = $reqEntreprise->fetch(PDO::FETCH_ASSOC);

        if ($entreprise) {
            $estCollaborateur = true;
            $entrepriseId = (int) $entreprise['id_entreprise'];
            $entrepriseNom = trim((string) $entreprise['nom_entreprise']);
        }
    } catch (Throwable $e) {
        $estCollaborateur = false;
    }
}

$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));
$email = trim((string) ($_SESSION['email_user'] ?? ''));
$photo = trim((string) ($_SESSION['photo_user'] ?? ''));

function tableExiste(PDO $con, string $table): bool
{
    try {
        $req = $con->prepare('SHOW TABLES LIKE ?');
        $req->execute([$table]);
        return (bool) $req->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function colonnesTable(PDO $con, string $table): array
{
    if (!tableExiste($con, $table)) {
        return [];
    }

    try {
        $req = $con->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
        return $req->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function compter(PDO $con, string $sql, array $params = []): int
{
    try {
        $req = $con->prepare($sql);
        $req->execute($params);
        return (int) $req->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function photoUrlDashboard(string $photo): string
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
        ['fichier' => __DIR__ . '/' . $photo, 'url' => $photo],
        [
            'fichier' => __DIR__ . '/../uploads/profils/' . $nomPhoto,
            'url' => '../uploads/profils/' . rawurlencode($nomPhoto)
        ],
        ['fichier' => __DIR__ . '/../uploads/' . $nomPhoto, 'url' => '../uploads/' . rawurlencode($nomPhoto)],
        [
            'fichier' => __DIR__ . '/../assets/uploads/' . $nomPhoto,
            'url' => '../assets/uploads/' . rawurlencode($nomPhoto)
        ],
        [
            'fichier' => __DIR__ . '/../images/profils/' . $nomPhoto,
            'url' => '../images/profils/' . rawurlencode($nomPhoto)
        ],
        ['fichier' => __DIR__ . '/../images/' . $nomPhoto, 'url' => '../images/' . rawurlencode($nomPhoto)]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat['fichier'])) {
            return $candidat['url'];
        }
    }

    return '';
}

$photoUrl = photoUrlDashboard($photo);
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
if ($initiales === '') {
    $initiales = 'TO';
}

$totalFormations = 0;
$totalCours = 0;
$totalCertificats = 0;
$progressionGlobale = 0;
$tempsApprentissage = 0;
$formationEnCours = null;
$mesFormations = [];

if (tableExiste($con, 't_inscriptions')) {
    $cols = colonnesTable($con, 't_inscriptions');

    if (in_array('id_user', $cols, true) && in_array('id_form', $cols, true) && tableExiste($con, 't_formations')) {
        $totalFormations = compter($con, 'SELECT COUNT(DISTINCT i.id_form) FROM t_inscriptions i WHERE i.id_user = ?', [
            $idUser
        ]);

        try {
            $req = $con->prepare(
                'SELECT f.id_form, f.titre_form, f.descrip_form, f.img_form, f.niveau_form, f.duree_form
                 FROM t_inscriptions i
                 INNER JOIN t_formations f ON f.id_form = i.id_form
                 WHERE i.id_user = ?
                 ORDER BY i.id_form DESC
                 LIMIT 3'
            );
            $req->execute([$idUser]);
            $mesFormations = $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $mesFormations = [];
        }
    }
}

if (tableExiste($con, 't_certificats')) {
    $cols = colonnesTable($con, 't_certificats');
    if (in_array('id_user', $cols, true)) {
        $totalCertificats = compter($con, 'SELECT COUNT(*) FROM t_certificats WHERE id_user = ?', [$idUser]);
    }
}

if (tableExiste($con, 't_progression')) {
    $cols = colonnesTable($con, 't_progression');

    if (in_array('id_user', $cols, true)) {
        $colProgression = null;
        foreach (['pourcentage_progression', 'progression', 'pourcentage', 'progression_user'] as $candidate) {
            if (in_array($candidate, $cols, true)) {
                $colProgression = $candidate;
                break;
            }
        }

        if ($colProgression !== null) {
            try {
                $sql = "SELECT COALESCE(AVG(`$colProgression`), 0) FROM t_progression WHERE id_user = ?";
                $progressionGlobale = (float) compter($con, $sql, [$idUser]);
            } catch (Throwable $e) {
                $progressionGlobale = 0;
            }
        }
    }
}

if (tableExiste($con, 't_modules') && tableExiste($con, 't_lecons') && $totalFormations > 0) {
    $totalCours = compter(
        $con,
        'SELECT COUNT(*)
         FROM t_lecons l
         INNER JOIN t_modules m ON m.id_module = l.id_module
         INNER JOIN t_inscriptions i ON i.id_form = m.id_form
         WHERE i.id_user = ?',
        [$idUser]
    );
}

if (tableExiste($con, 't_progression')) {
    $cols = colonnesTable($con, 't_progression');
    foreach (['duree_apprentissage', 'temps_apprentissage', 'temps_total'] as $candidate) {
        if (in_array($candidate, $cols, true) && in_array('id_user', $cols, true)) {
            $tempsApprentissage = compter(
                $con,
                "SELECT COALESCE(SUM(`$candidate`), 0) FROM t_progression WHERE id_user = ?",
                [$idUser]
            );
            break;
        }
    }
}

if (!empty($mesFormations)) {
    $formationEnCours = $mesFormations[0];
}

$nomAffiche = trim($prenom . ' ' . $nom);
if ($nomAffiche === '') {
    $nomAffiche = 'Apprenant';
}

$progressionGlobale = max(0, min(100, round($progressionGlobale)));
$tempsAffiche = $tempsApprentissage > 0 ? number_format($tempsApprentissage, 0, ',', ' ') . ' h' : '0 h';

$formationImage = '';
if ($formationEnCours && !empty($formationEnCours['img_form'])) {
    $img = str_replace('\\', '/', trim((string) $formationEnCours['img_form']));
    $nomImg = basename($img);
    $candidats = [
        ['fichier' => __DIR__ . '/../' . ltrim($img, '/'), 'url' => '../' . ltrim($img, '/')],
        [
            'fichier' => __DIR__ . '/../images/formations/' . $nomImg,
            'url' => '../images/formations/' . rawurlencode($nomImg)
        ],
        [
            'fichier' => __DIR__ . '/../uploads/formations/' . $nomImg,
            'url' => '../uploads/formations/' . rawurlencode($nomImg)
        ],
        ['fichier' => __DIR__ . '/../uploads/' . $nomImg, 'url' => '../uploads/' . rawurlencode($nomImg)]
    ];
    foreach ($candidats as $candidat) {
        if (is_file($candidat['fichier'])) {
            $formationImage = $candidat['url'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tableau de bord | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            .company-badge {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-top: 8px;
                padding: 7px 11px;
                border: 1px solid #d8edf1;
                border-radius: 20px;
                background: #edf8fa;
                color: #16839d;
                font-size: 11px;
                font-weight: 600;
            }

            .company-course-note {
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 0 0 9px;
                color: #16839d;
                font-size: 11px;
                font-weight: 600;
            }

            .course-company {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                margin: -2px 0 7px;
                padding: 4px 7px;
                border-radius: 12px;
                background: #edf8fa;
                color: #16839d;
                font-size: 10px;
                font-weight: 600;
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
                        <a href="rendez-vous.php" class="top-action">
                            <i class="fa-regular fa-calendar-check"></i>
                            <span>Mes rendez-vous</span>
                        </a>

                        <a href="notifications.php" class="notification" aria-label="Notifications">
                            <i class="fa-regular fa-bell"></i>
                            <span></span>
                        </a>

                        <a href="profil.php" class="profile">
                            <div class="profile-avatar">
                                <?php if ($photoUrl !== ''): ?>
                                    <img src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de profil">
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
                    <div class="welcome">
                        <div>
                            <h1>Bonjour <?= htmlspecialchars($prenom !== '' ? $prenom : $nomAffiche, ENT_QUOTES, 'UTF-8') ?> </h1>
                            <p>Votre espace d'apprentissage TOUN.</p>
                            <?php if ($estCollaborateur && $entrepriseNom !== ''): ?>
                                <div class="company-badge">
                                    <i class="fa-solid fa-building"></i>
                                    <span>Collaborateur de <?= htmlspecialchars($entrepriseNom, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-chip">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Compte actif</span>
                        </div>
                    </div>

                    <?php
                    $ringCirconf = 87.96;
                    $pctFormations = max(0, min(100, $totalFormations * 20));
                    $pctCours = max(0, min(100, $totalCours * 10));
                    $pctProgression = max(0, min(100, $progressionGlobale));
                    $pctCertificats = max(0, min(100, $totalCertificats * 25));
                    ?>

                    <div class="stats">
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>
                                <svg class="stat-ring" width="30" height="30" viewBox="0 0 34 34">
                                    <circle class="stat-ring-track" cx="17" cy="17" r="14"></circle>
                                    <circle class="stat-ring-value" cx="17" cy="17" r="14" style="stroke-dasharray:<?= round(($pctFormations / 100) * $ringCirconf, 1) ?> <?= $ringCirconf ?>"></circle>
                                </svg>
                            </div>
                            <div class="stat-label">Mes formations</div>
                            <div class="stat-number counter" data-value="<?= $totalFormations ?>"><?= $totalFormations ?></div>
                        </div>

                        <div class="stat-card blue">
                            <div class="stat-top">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                                <svg class="stat-ring" width="30" height="30" viewBox="0 0 34 34">
                                    <circle class="stat-ring-track" cx="17" cy="17" r="14"></circle>
                                    <circle class="stat-ring-value" cx="17" cy="17" r="14" style="stroke-dasharray:<?= round(($pctCours / 100) * $ringCirconf, 1) ?> <?= $ringCirconf ?>"></circle>
                                </svg>
                            </div>
                            <div class="stat-label">Cours disponibles</div>
                            <div class="stat-number counter" data-value="<?= $totalCours ?>"><?= $totalCours ?></div>
                        </div>

                        <div class="stat-card gold">
                            <div class="stat-top">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <svg class="stat-ring" width="30" height="30" viewBox="0 0 34 34">
                                    <circle class="stat-ring-track" cx="17" cy="17" r="14"></circle>
                                    <circle class="stat-ring-value" cx="17" cy="17" r="14" style="stroke-dasharray:<?= round(($pctProgression / 100) * $ringCirconf, 1) ?> <?= $ringCirconf ?>"></circle>
                                </svg>
                            </div>
                            <div class="stat-label">Progression globale</div>
                            <div class="stat-number counter" data-value="<?= $progressionGlobale ?>"><?= round($progressionGlobale) ?><span class="percent">%</span></div>
                        </div>

                        <div class="stat-card purple">
                            <div class="stat-top">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-award"></i>
                                </div>
                                <svg class="stat-ring" width="30" height="30" viewBox="0 0 34 34">
                                    <circle class="stat-ring-track" cx="17" cy="17" r="14"></circle>
                                    <circle class="stat-ring-value" cx="17" cy="17" r="14" style="stroke-dasharray:<?= round(($pctCertificats / 100) * $ringCirconf, 1) ?> <?= $ringCirconf ?>"></circle>
                                </svg>
                            </div>
                            <div class="stat-label">Certificats</div>
                            <div class="stat-number counter" data-value="<?= $totalCertificats ?>"><?= $totalCertificats ?></div>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="card">
                            <div class="card-header">
                                <h2>Continuer ma formation</h2>
                                <a href="mes-formations.php">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
                            </div>

                            <?php if ($formationEnCours): ?>
                                <div class="continue-card">
                                    <div class="formation-cover">
                                        <?php if ($formationImage !== ''): ?>
                                            <img src="<?= htmlspecialchars($formationImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($formationEnCours['titre_form'] ?? 'Formation', ENT_QUOTES, 'UTF-8') ?>">
                                        <?php else: ?>
                                            <i class="fa-solid fa-book-open"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="formation-body">
                                        <span class="formation-tag">
                                            <?= $estCollaborateur ? 'Formation entreprise' : 'Formation en cours' ?>
                                        </span>
                                        <h3><?= htmlspecialchars($formationEnCours['titre_form'] ?? 'Ma formation', ENT_QUOTES, 'UTF-8') ?></h3>
                                        <?php if ($estCollaborateur && $entrepriseNom !== ''): ?>
                                            <div class="company-course-note">
                                                <i class="fa-solid fa-building"></i>
                                                <span>Attribuée par <?= htmlspecialchars($entrepriseNom, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <p><?= htmlspecialchars(mb_strimwidth(strip_tags((string) ($formationEnCours['descrip_form'] ?? '')), 0, 150, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="progress-line"><span style="width:<?= $progressionGlobale ?>%"></span></div>
                                        <div class="progress-meta"><span>Progression</span><strong><?= $progressionGlobale ?>%</strong></div>
                                        <a href="mes-formations.php" class="primary-btn">Continuer <i class="fa-solid fa-arrow-right"></i></a>
                                    </div>
                                </div>
                            <?php elseif ($estCollaborateur): ?>
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
                                    <h3>Aucune formation attribuée pour le moment</h3>
                                    <p>
                                        <?= $entrepriseNom !== '' ? htmlspecialchars($entrepriseNom, ENT_QUOTES, 'UTF-8') : 'Votre entreprise' ?>
                                        se charge de vous attribuer vos formations. Vous les retrouverez ici dès qu'elles seront disponibles.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-book-open"></i></div>
                                    <h3>Votre parcours commence ici</h3>
                                    <p>Vous n'avez encore aucune formation. Découvrez le catalogue et choisissez la formation qui correspond à vos objectifs.</p>
                                    <a href="../formation.php" class="primary-btn">Découvrir les formations <i class="fa-solid fa-arrow-right"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card side-card">
                            <div class="card-header">
                                <h2>Accès rapides</h2>
                                <span>TOUN</span>
                            </div>
                            <div class="quick-list">
                                <?php if (!$estCollaborateur): ?>
                                    <a href="../formation.php" class="quick-item">
                                        <div class="quick-icon"><i class="fa-solid fa-compass"></i></div>
                                        <div><strong>Explorer les formations</strong><span>Trouvez votre prochain parcours</span></div>
                                    </a>
                                <?php endif; ?>
                                <a href="mes-formations.php" class="quick-item">
                                    <div class="quick-icon"><i class="fa-solid fa-play"></i></div>
                                    <div><strong>Reprendre mon apprentissage</strong><span>Retrouvez vos formations</span></div>
                                </a>
                                <a href="profil.php" class="quick-item">
                                    <div class="quick-icon"><i class="fa-regular fa-user"></i></div>
                                    <div><strong>Mon profil</strong><span>Gérez vos informations</span></div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card formations-card section-space">
                        <div class="card-header">
                            <h2>Mes formations</h2>
                            <a href="mes-formations.php">Voir toutes <i class="fa-solid fa-arrow-right"></i></a>
                        </div>

                        <?php if (!empty($mesFormations)): ?>
                            <div class="courses">
                                <?php foreach ($mesFormations as $formation): ?>
                                    <article class="course-card">
                                        <div class="course-image">
                                            <?php
$imgCourse = '';
if (!empty($formation['img_form'])) {
    $img = str_replace('\\', '/', trim((string) $formation['img_form']));
    $nomImg = basename($img);
    $possibles = [
        ['fichier' => __DIR__ . '/../' . ltrim($img, '/'), 'url' => '../' . ltrim($img, '/')],
        [
            'fichier' => __DIR__ . '/../images/formations/' . $nomImg,
            'url' => '../images/formations/' . rawurlencode($nomImg)
        ],
        [
            'fichier' => __DIR__ . '/../uploads/formations/' . $nomImg,
            'url' => '../uploads/formations/' . rawurlencode($nomImg)
        ]
    ];
    foreach ($possibles as $p) {
        if (is_file($p['fichier'])) {
            $imgCourse = $p['url'];
            break;
        }
    }
}
?>
                                            <?php if ($imgCourse !== ''): ?>
                                                <img src="<?= htmlspecialchars($imgCourse, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($formation['titre_form'] ?? 'Formation', ENT_QUOTES, 'UTF-8') ?>">
                                            <?php else: ?>
                                                <i class="fa-solid fa-book-open"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="course-body">
                                            <h3><?= htmlspecialchars($formation['titre_form'] ?? 'Formation', ENT_QUOTES, 'UTF-8') ?></h3>
                                            <?php if ($estCollaborateur && $entrepriseNom !== ''): ?>
                                                <span class="course-company">
                                                    <i class="fa-solid fa-building"></i>
                                                    Formation attribuée
                                                </span>
                                            <?php endif; ?>
                                            <div class="course-progress"><span style="width:<?= $progressionGlobale ?>%"></span></div>
                                            <div class="course-footer">
                                                <span><?= $progressionGlobale ?>% terminé</span>
                                                <a href="cours.php?id=<?= (int) $formation['id_form'] ?>">Continuer</a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state formations-empty">
                                <div class="empty-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                                <h3>Aucune formation pour le moment</h3>
                                <p>
                                    <?= $estCollaborateur ? 'Votre entreprise ne vous a encore attribué aucune formation.' : 'Après votre premier achat, vos formations apparaîtront automatiquement ici.' ?>
                                </p>
                                <?php if (!$estCollaborateur): ?>
                                    <a href="../formation.php" class="primary-btn">
                                        Voir le catalogue <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bottom-grid section-space">
                        <div class="card">
                            <div class="card-header">
                                <h2>Mon apprentissage</h2>
                                <span>Vue d'ensemble</span>
                            </div>
                            <div class="info-box">
                                <div class="info-box-left">
                                    <div class="info-box-icon"><i class="fa-regular fa-clock"></i></div>
                                    <div><strong>Temps d'apprentissage</strong><span>Votre temps cumulé sur TOUN</span></div>
                                </div>
                                <div class="info-value"><?= htmlspecialchars($tempsAffiche, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2>Besoin d'aide ?</h2>
                                <span>Équipe pédagogique</span>
                            </div>
                            <div class="info-box">
                                <div class="info-box-left">
                                    <div class="info-box-icon"><i class="fa-solid fa-user-tie"></i></div>
                                    <div><strong>Parler à un formateur</strong><span>Un accompagnement sera bientôt disponible</span></div>
                                </div>
                                <a href="rendez-vous.php" class="primary-btn">Rendez-vous</a>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <script>
            const mobileMenu = document.getElementById('mobileMenu');
            const sidebar = document.getElementById('sidebar');

            if (mobileMenu && sidebar) {
                mobileMenu.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                });
            }
        </script>
    </body>
</html>

<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../includes/ressources.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

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

tounPreparerRessources($con);

$idUser = (int) $_SESSION['id_user'];
$ressources = [];

if (tableExiste($con, 't_inscriptions') && tableExiste($con, 't_formations')) {
    try {
        $req = $con->prepare(
            'SELECT r.id_ressource, r.titre_ressource, r.type_ressource, r.fichier_ressource, r.lien_ressource, r.date_ajout, f.titre_form, m.titre_module, m.ordre_module
             FROM t_ressources r
             INNER JOIN t_formations f ON f.id_form = r.id_form
             INNER JOIN t_inscriptions i ON i.id_form = r.id_form AND i.id_user = ?
             LEFT JOIN t_modules m ON m.id_module = r.id_module
             ORDER BY f.titre_form ASC, m.ordre_module ASC, r.date_ajout DESC'
        );
        $req->execute([$idUser]);
        $ressources = $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $ressources = [];
    }
}

if (tableExiste($con, 't_lecons') && tableExiste($con, 't_modules')) {
    try {
        $req = $con->prepare(
            "SELECT l.titre_lecon, l.contenu_lecon, f.titre_form, m.titre_module, m.ordre_module
             FROM t_lecons l
             INNER JOIN t_modules m ON m.id_module = l.id_module
             INNER JOIN t_formations f ON f.id_form = m.id_form
             INNER JOIN t_inscriptions i ON i.id_form = f.id_form AND i.id_user = ?
             WHERE l.type_contenu = 'pdf' AND l.contenu_lecon <> '' AND i.statut_inscrip IN ('active', 'terminee')
             ORDER BY f.titre_form ASC, m.ordre_module ASC, l.ordre_lecon ASC"
        );
        $req->execute([$idUser]);

        foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $support) {
            $nomFichier = basename(str_replace('\\', '/', (string) $support['contenu_lecon']));

            if (is_file(__DIR__ . '/../uploads/lecons/' . $nomFichier)) {
                $ressources[] = [
                    'titre_ressource' => $support['titre_lecon'],
                    'type_ressource' => 'pdf',
                    'fichier_ressource' => '',
                    'lien_ressource' => '../uploads/lecons/' . rawurlencode($nomFichier),
                    'titre_form' => $support['titre_form'],
                    'titre_module' => $support['titre_module'],
                    'ordre_module' => $support['ordre_module'],
                ];
            }
        }
    } catch (Throwable $e) {
    }
}

$iconParType = [
    'pdf' => 'fa-file-pdf',
    'video' => 'fa-file-video',
    'doc' => 'fa-file-word',
    'lien' => 'fa-link',
    'autre' => 'fa-file'
];

$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));
$nomAffiche = trim($prenom . ' ' . $nom) ?: 'Apprenant';
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'TO';
$photoUrl = '';
$photoBrute = trim((string) ($_SESSION['photo_user'] ?? ''));
if ($photoBrute !== '') {
    $nomPhoto = basename(str_replace('\\', '/', $photoBrute));
    foreach (
        [
            __DIR__ . '/../uploads/profils/' . $nomPhoto => '../uploads/profils/' . rawurlencode($nomPhoto),
            __DIR__ . '/../images/' . $nomPhoto => '../images/' . rawurlencode($nomPhoto)
        ]
        as $fichier => $url
    ) {
        if (is_file($fichier)) {
            $photoUrl = $url;
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
        <title>Ressources | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            .res-page {
                padding: 24px 30px 30px;
                max-width: 1300px;
            }

            .res-hero {
                position: relative;
                overflow: hidden;
                padding: 26px 28px;
                margin-bottom: 20px;
                border-radius: 18px;
                background: linear-gradient(120deg, #18213f 0%, #24558b 52%, #20a1b8 100%);
                color: #fff;
            }

            .res-hero span {
                display: inline-flex;
                padding: 6px 10px;
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.12);
                color: #70e1e6;
                font-size: 10.5px;
                font-weight: 700;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            .res-hero h1 {
                margin: 11px 0 6px;
                font-size: 20px;
            }

            .res-hero p {
                margin: 0;
                max-width: 560px;
                color: rgba(255, 255, 255, 0.75);
                font-size: 11.5px;
                line-height: 1.7;
            }

            .res-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 14px;
            }

            .res-card {
                display: flex;
                gap: 12px;
                padding: 16px;
                border-radius: 15px;
                background: #fff;
                border: 1px solid #e3eaf0;
                transition: 0.2s ease;
            }

            .res-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 13px 28px rgba(24, 33, 63, 0.08);
            }

            .res-icon {
                width: 42px;
                height: 42px;
                min-width: 42px;
                border-radius: 12px;
                display: grid;
                place-items: center;
                background: #edf8fa;
                color: #20a1b8;
                font-size: 16px;
            }

            .res-body h3 {
                margin: 0 0 4px;
                font-size: 12px;
                color: #18213f;
                line-height: 1.4;
            }

            .res-body span {
                display: block;
                color: #8993a4;
                font-size: 11px;
                margin-bottom: 8px;
            }

            .res-body a {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #20a1b8;
                font-size: 11px;
                font-weight: 700;
            }

            @media (max-width: 700px) {
                .res-page {
                    padding: 18px;
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
                            <div class="profile-avatar">
                                <?php if ($photoUrl !== ''): ?>
                                    <img src="<?= e($photoUrl) ?>" alt="Photo de profil">
                                <?php else: ?>
                                    <?= e($initiales) ?>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <strong><?= e($nomAffiche) ?></strong>
                                <small>Apprenant</small>
                            </div>
                        </a>
                    </div>
                </header>

                <section class="res-page">
                    <div class="res-hero">
                        <span><i class="fa-solid fa-folder-open"></i> Ressources</span>
                        <h1>Les documents de vos formations</h1>
                        <p>Retrouvez ici les supports de cours, fiches et liens complémentaires partagés pour les formations que vous suivez.</p>
                    </div>

                    <?php if (!$ressources): ?>
                        <div class="empty-state" style="background:#fff;border:1px solid #e1e9ef;border-radius:18px">
                            <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                            <h3>Aucune ressource pour le moment</h3>
                            <p>Dès que des supports seront ajoutés pour vos formations, ils apparaîtront automatiquement ici.</p>
                            <a href="mes-formations.php" class="primary-btn">Voir mes formations <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    <?php else: ?>
                        <div class="res-grid">
                            <?php foreach ($ressources as $r): ?>
                                <?php
$type = strtolower((string) ($r['type_ressource'] ?? 'autre'));
$icon = $iconParType[$type] ?? $iconParType['autre'];
$lien = $r['lien_ressource'] ?: '../uploads/ressources/' . rawurlencode((string) $r['fichier_ressource']);
?>
                                <div class="res-card">
                                    <div class="res-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                                    <div class="res-body">
                                        <h3><?= e($r['titre_ressource']) ?></h3>
                                        <span><?= e($r['titre_form'] ?? 'Formation TOUN') ?><?= !empty($r['titre_module']) ? ' · Module ' . (int) $r['ordre_module'] . ' : ' . e($r['titre_module']) : '' ?></span>
                                        <a href="<?= e($lien) ?>" target="_blank" rel="noopener">Ouvrir <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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

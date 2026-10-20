<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

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

$idUser = (int) $_SESSION['id_user'];
$items = [];

if (tableExiste($con, 't_inscriptions') && tableExiste($con, 't_formations')) {
    try {
        $req = $con->prepare(
            'SELECT f.titre_form, i.id_form
             FROM t_inscriptions i
             INNER JOIN t_formations f ON f.id_form = i.id_form
             WHERE i.id_user = ?
             ORDER BY i.id_inscrip DESC LIMIT 8'
        );
        $req->execute([$idUser]);
        foreach ($req->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $items[] = [
                'icon' => 'fa-book-open',
                'color' => 'blue',
                'title' => 'Formation rejointe',
                'text' => $row['titre_form'] ?? 'Formation TOUN',
                'date' => null,
                'link' => 'mes-formations.php'
            ];
        }
    } catch (Throwable $e) {
    }
}

if (tableExiste($con, 't_certificats')) {
    try {
        $req = $con->prepare(
            "SELECT c.date_obtention, f.titre_form
             FROM t_certificats c
             LEFT JOIN t_inscriptions i ON i.id_inscrip = c.id_inscrip
             LEFT JOIN t_formations f ON f.id_form = i.id_form
             WHERE c.id_user = ?
             ORDER BY c.id_certificat DESC LIMIT 8"
        );
        $req->execute([$idUser]);
        foreach ($req->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $items[] = [
                'icon' => 'fa-award',
                'color' => 'gold',
                'title' => 'Certificat obtenu',
                'text' => $row['titre_form'] ?? 'Certification TOUN',
                'date' => $row['date_obtention'] ?? null,
                'link' => 'certificats.php'
            ];
        }
    } catch (Throwable $e) {
    }
}

if (tableExiste($con, 't_paiements')) {
    try {
        $req = $con->prepare(
            "SELECT montant_total, date_paiement, statut_paiement
             FROM t_paiements
             WHERE id_user = ? AND LOWER(statut_paiement) = 'paye'
             ORDER BY id_paiement DESC LIMIT 8"
        );
        $req->execute([$idUser]);
        foreach ($req->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $items[] = [
                'icon' => 'fa-circle-check',
                'color' => 'green',
                'title' => 'Paiement confirmé',
                'text' => number_format((float) ($row['montant_total'] ?? 0), 0, ',', ' ') . ' FCFA',
                'date' => $row['date_paiement'] ?? null,
                'link' => 'paiements.php'
            ];
        }
    } catch (Throwable $e) {
    }
}

if (tableExiste($con, 't_messages')) {
    try {
        $req = $con->prepare(
            "SELECT contenu, date_message
             FROM t_messages
             WHERE id_user = ? AND expediteur = 'admin'
             ORDER BY id_message DESC LIMIT 8"
        );
        $req->execute([$idUser]);
        foreach ($req->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $items[] = [
                'icon' => 'fa-envelope',
                'color' => 'purple',
                'title' => "Message de l'équipe TOUN",
                'text' => mb_strimwidth((string) ($row['contenu'] ?? ''), 0, 80, '...'),
                'date' => $row['date_message'] ?? null,
                'link' => 'messages.php'
            ];
        }
    } catch (Throwable $e) {
    }
}

usort($items, function ($a, $b) {
    return strtotime($b['date'] ?: '1970-01-01') <=> strtotime($a['date'] ?: '1970-01-01');
});
$items = array_slice($items, 0, 25);

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
        <title>Notifications | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            .notif-page {
                padding: 24px 30px 30px;
                max-width: 900px;
            }

            .notif-card {
                border: 1px solid #e1e9ef;
                border-radius: 18px;
                background: #fff;
                box-shadow: 0 9px 28px rgba(24, 33, 63, 0.045);
                overflow: hidden;
            }

            .notif-head {
                padding: 18px 22px;
                background: linear-gradient(120deg, #18213f 0%, #24558b 55%, #20a1b8 100%);
                color: #fff;
            }

            .notif-head h1 {
                margin: 0;
                font-size: 15px;
            }

            .notif-head p {
                margin: 4px 0 0;
                color: rgba(255, 255, 255, 0.72);
                font-size: 11px;
            }

            .notif-item {
                display: flex;
                gap: 13px;
                padding: 15px 20px;
                border-top: 1px solid #eef2f6;
                text-decoration: none;
                color: inherit;
            }

            .notif-item:hover {
                background: #f7fafc;
            }

            .notif-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
                border-radius: 11px;
                display: grid;
                place-items: center;
                font-size: 13px;
                background: #edf8fa;
                color: #20a1b8;
            }

            .notif-item.gold .notif-icon {
                background: #eaf3f9;
                color: #20a1b8;
            }

            .notif-item.green .notif-icon {
                background: #e9f8f0;
                color: #20a16d;
            }

            .notif-item.purple .notif-icon {
                background: #eef2fb;
                color: #3f68ae;
            }

            .notif-item strong {
                display: block;
                color: #18213f;
                font-size: 11.5px;
            }

            .notif-item span {
                display: block;
                margin-top: 3px;
                color: #7b899b;
                font-size: 11px;
            }

            .notif-empty {
                padding: 55px 20px;
                text-align: center;
                color: #8b98a8;
            }

            .notif-empty i {
                font-size: 26px;
                color: #20a1b8;
                margin-bottom: 9px;
                display: block;
            }

            .notif-empty p {
                font-size: 11px;
            }

            @media (max-width: 700px) {
                .notif-page {
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

                <section class="notif-page">
                    <article class="notif-card">
                        <header class="notif-head">
                            <h1>Notifications</h1>
                            <p>L'essentiel de votre activité sur TOUN.</p>
                        </header>

                        <?php if (!$items): ?>
                            <div class="notif-empty">
                                <i class="fa-regular fa-bell"></i>
                                <p>Aucune notification pour le moment. Elle apparaîtra ici dès que vous rejoignez une formation, recevez un message ou obtenez un certificat.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <a class="notif-item <?= e($item['color']) ?>" href="<?= e($item['link']) ?>">
                                    <div class="notif-icon"><i class="fa-solid <?= e($item['icon']) ?>"></i></div>
                                    <div>
                                        <strong><?= e($item['title']) ?></strong>
                                        <span><?= e($item['text']) ?><?= $item['date'] ? ' · ' . date('d/m/Y', strtotime($item['date'])) : '' ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </article>
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

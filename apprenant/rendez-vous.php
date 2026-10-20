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

try {
    $con->exec(
        "CREATE TABLE IF NOT EXISTS t_rendezvous (
            id_rdv INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_user INT UNSIGNED NOT NULL,
            id_formateur INT UNSIGNED NULL,
            nom_formateur_texte VARCHAR(150) NOT NULL DEFAULT '',
            motif VARCHAR(255) NOT NULL DEFAULT '',
            date_rdv DATE NOT NULL,
            heure_rdv TIME NOT NULL,
            statut_rdv VARCHAR(20) NOT NULL DEFAULT 'en_attente',
            date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_rdv),
            INDEX idx_rdv_user (id_user)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
}

$idUser = (int) $_SESSION['id_user'];

$formateurs = [];
if (tableExiste($con, 't_formateurs')) {
    $colsFormateur = colonnesTable($con, 't_formateurs');
    $pk = in_array('id_formateur', $colsFormateur, true) ? 'id_formateur' : $colsFormateur[0] ?? null;
    $fk = null;
    foreach (['id_user', 'id_utilisateur', 'utilisateur_id', 'user_id'] as $candidat) {
        if (in_array($candidat, $colsFormateur, true)) {
            $fk = $candidat;
            break;
        }
    }
    if ($pk !== null && $fk !== null && tableExiste($con, 't_utilisateurs')) {
        try {
            $req = $con->query(
                "SELECT f.`$pk` AS id_formateur, u.prenom_user, u.nom_user
                 FROM t_formateurs f
                 INNER JOIN t_utilisateurs u ON u.id_user = f.`$fk`
                 ORDER BY u.prenom_user ASC
                 LIMIT 50"
            );
            $formateurs = $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $formateurs = [];
        }
    }
}

$erreur = '';
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motif = trim((string) ($_POST['motif'] ?? ''));
    $dateRdv = trim((string) ($_POST['date_rdv'] ?? ''));
    $heureRdv = trim((string) ($_POST['heure_rdv'] ?? ''));
    $idFormateurPost = (int) ($_POST['id_formateur'] ?? 0);
    $nomFormateurTexte = '';

    foreach ($formateurs as $f) {
        if ((int) $f['id_formateur'] === $idFormateurPost) {
            $nomFormateurTexte = trim(($f['prenom_user'] ?? '') . ' ' . ($f['nom_user'] ?? ''));
            break;
        }
    }
    if ($idFormateurPost <= 0) {
        $nomFormateurTexte = 'Équipe pédagogique TOUN';
    }

    if ($motif === '' || $dateRdv === '' || $heureRdv === '') {
        $erreur = 'Merci de renseigner le motif, la date et l\'heure souhaités.';
    } elseif (strtotime($dateRdv) < strtotime(date('Y-m-d'))) {
        $erreur = 'La date choisie est déjà passée.';
    } else {
        try {
            $req = $con->prepare(
                'INSERT INTO t_rendezvous (id_user, id_formateur, nom_formateur_texte, motif, date_rdv, heure_rdv)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $req->execute([
                $idUser,
                $idFormateurPost > 0 ? $idFormateurPost : null,
                $nomFormateurTexte,
                mb_substr($motif, 0, 255),
                $dateRdv,
                $heureRdv
            ]);
            $succes = true;
        } catch (Throwable $e) {
            $erreur = 'Une erreur est survenue, merci de réessayer.';
        }
    }
}

$rdvs = [];
try {
    $req = $con->prepare('SELECT * FROM t_rendezvous WHERE id_user = ? ORDER BY date_rdv DESC, heure_rdv DESC');
    $req->execute([$idUser]);
    $rdvs = $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
}

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

$badges = [
    'en_attente' => ['label' => 'En attente', 'class' => 'wait', 'icon' => 'fa-clock'],
    'confirme' => ['label' => 'Confirmé', 'class' => 'success', 'icon' => 'fa-circle-check'],
    'annule' => ['label' => 'Annulé', 'class' => 'failed', 'icon' => 'fa-circle-xmark']
];

$moisCourt = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mes rendez-vous | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            .rdv-page {
                padding: 24px 30px 30px;
                max-width: 1300px;
            }

            .rdv-grid {
                display: grid;
                grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
                gap: 18px;
                align-items: start;
            }

            .rdv-form-field {
                margin-bottom: 13px;
            }

            .rdv-form-field label {
                display: block;
                margin-bottom: 6px;
                font-size: 11px;
                font-weight: 600;
                color: #5c6a7d;
            }

            .rdv-form-field input,
            .rdv-form-field select,
            .rdv-form-field textarea {
                width: 100%;
                height: 40px;
                border: 1px solid #dfe7ee;
                border-radius: 10px;
                padding: 0 12px;
                font: inherit;
                font-size: 11.5px;
                outline: 0;
                background: #f8fafc;
            }

            .rdv-form-field textarea {
                height: 70px;
                padding: 10px 12px;
                resize: none;
            }

            .rdv-form-field input:focus,
            .rdv-form-field select:focus,
            .rdv-form-field textarea:focus {
                border-color: #8fc7d4;
            }

            .rdv-row2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .rdv-alert {
                padding: 11px 14px;
                border-radius: 11px;
                font-size: 11px;
                margin-bottom: 15px;
            }

            .rdv-alert.error {
                background: #fff0f0;
                color: #bd5353;
                border: 1px solid #f7d3d3;
            }

            .rdv-alert.success {
                background: #e9f8f0;
                color: #188257;
                border: 1px solid #cdeedb;
            }

            .rdv-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .rdv-item {
                display: flex;
                align-items: center;
                gap: 13px;
                padding: 14px;
                border-radius: 13px;
                border: 1px solid #e6edf3;
                background: #fff;
            }

            .rdv-date-box {
                width: 52px;
                height: 52px;
                min-width: 52px;
                border-radius: 12px;
                background: linear-gradient(135deg, #18213f, #3f68ae);
                color: #fff;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .rdv-date-box strong {
                font-size: 15px;
                line-height: 1;
            }

            .rdv-date-box span {
                font-size: 10px;
                text-transform: uppercase;
                margin-top: 2px;
            }

            .rdv-item-body {
                flex: 1;
                min-width: 0;
            }

            .rdv-item-body strong {
                display: block;
                font-size: 11.5px;
                color: #18213f;
            }

            .rdv-item-body span {
                display: block;
                margin-top: 2px;
                font-size: 11px;
                color: #8993a4;
            }

            .rdv-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 9px;
                border-radius: 20px;
                font-size: 10.5px;
                font-weight: 600;
                white-space: nowrap;
            }

            .rdv-badge.wait {
                color: #0b5a91;
                background: #eaf3f9;
            }

            .rdv-badge.success {
                color: #188257;
                background: #e9f8f0;
            }

            .rdv-badge.failed {
                color: #bd5353;
                background: #fff0f0;
            }

            @media (max-width: 1000px) {
                .rdv-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 700px) {
                .rdv-page {
                    padding: 18px;
                }

                .rdv-row2 {
                    grid-template-columns: 1fr;
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

                <section class="rdv-page">
                    <div class="rdv-grid">
                        <div class="card">
                            <div class="card-header">
                                <h2>Prendre rendez-vous</h2>
                            </div>

                            <?php if ($erreur): ?>
                                <div class="rdv-alert error"><?= e($erreur) ?></div>
                            <?php elseif ($succes): ?>
                                <div class="rdv-alert success">Votre demande a bien été envoyée. Vous serez notifié dès qu'elle sera confirmée.</div>
                            <?php endif; ?>

                            <form method="post" action="rendez-vous.php">
                                <div class="rdv-form-field">
                                    <label>Avec qui ?</label>
                                    <select name="id_formateur">
                                        <option value="0">Équipe pédagogique TOUN</option>
                                        <?php foreach ($formateurs as $f): ?>
                                            <option value="<?= (int) $f['id_formateur'] ?>"><?= e(trim(($f['prenom_user'] ?? '') . ' ' . ($f['nom_user'] ?? ''))) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="rdv-form-field">
                                    <label>Motif du rendez-vous</label>
                                    <textarea name="motif" placeholder="Ex : accompagnement sur le module 3, questions sur mon parcours..." required></textarea>
                                </div>
                                <div class="rdv-row2">
                                    <div class="rdv-form-field">
                                        <label>Date souhaitée</label>
                                        <input type="date" name="date_rdv" min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="rdv-form-field">
                                        <label>Heure souhaitée</label>
                                        <input type="time" name="heure_rdv" required>
                                    </div>
                                </div>
                                <button type="submit" class="primary-btn" style="width:100%;margin-top:5px">Envoyer la demande <i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2>Mes rendez-vous</h2>
                                <span><?= count($rdvs) ?> au total</span>
                            </div>

                            <?php if (!$rdvs): ?>
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-regular fa-calendar-check"></i></div>
                                    <h3>Aucun rendez-vous pour le moment</h3>
                                    <p>Utilisez le formulaire pour planifier un échange avec l'équipe pédagogique ou un formateur.</p>
                                </div>
                            <?php else: ?>
                                <div class="rdv-list">
                                    <?php foreach ($rdvs as $r): ?>
                                        <?php
$statut = strtolower((string) ($r['statut_rdv'] ?? 'en_attente'));
$badge = $badges[$statut] ?? $badges['en_attente'];
$ts = strtotime($r['date_rdv'] ?? 'now');
?>
                                        <div class="rdv-item">
                                            <div class="rdv-date-box">
                                                <strong><?= date('d', $ts) ?></strong>
                                                <span><?= e($moisCourt[(int) date('n', $ts) - 1] ?? date('M', $ts)) ?></span>
                                            </div>
                                            <div class="rdv-item-body">
                                                <strong><?= e($r['nom_formateur_texte'] ?: 'Équipe TOUN') ?></strong>
                                                <span><?= e($r['motif']) ?> · <?= e(substr((string) $r['heure_rdv'], 0, 5)) ?></span>
                                            </div>
                                            <span class="rdv-badge <?= $badge['class'] ?>">
                                                <i class="fa-solid <?= $badge['icon'] ?>"></i>
                                                <?= $badge['label'] ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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

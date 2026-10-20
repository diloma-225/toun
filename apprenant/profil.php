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

$idUser = (int) $_SESSION['id_user'];

try {
    $con->exec(
        "CREATE TABLE IF NOT EXISTS t_parametres_utilisateur (
            id_user INT NOT NULL,
            notif_email TINYINT(1) NOT NULL DEFAULT 1,
            notif_formations TINYINT(1) NOT NULL DEFAULT 1,
            notif_messages TINYINT(1) NOT NULL DEFAULT 1,
            notif_promotions TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id_user)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
}

$req = $con->prepare('SELECT * FROM t_utilisateurs WHERE id_user = ? LIMIT 1');
$req->execute([$idUser]);
$utilisateur = $req->fetch(PDO::FETCH_ASSOC) ?: [];

$messageInfo = '';
$messageErreur = '';
$onglet = 'infos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'infos') {
    $onglet = 'infos';
    $prenom = trim((string) ($_POST['prenom_user'] ?? ''));
    $nom = trim((string) ($_POST['nom_user'] ?? ''));
    $email = trim((string) ($_POST['email_user'] ?? ''));
    $tel = trim((string) ($_POST['tel_user'] ?? ''));

    if ($prenom === '' || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messageErreur = 'Merci de renseigner un prénom, un nom et un e-mail valides.';
    } else {
        $champs = ['prenom_user' => $prenom, 'nom_user' => $nom, 'email_user' => $email, 'tel_user' => $tel];

        if (!empty($_FILES['photo']['name']) && ($_FILES['photo']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $tailleOk = $_FILES['photo']['size'] <= 3 * 1024 * 1024;
            $infosImage = @getimagesize($_FILES['photo']['tmp_name']);
            if ($tailleOk && $infosImage !== false) {
                $extensions = [
                    IMAGETYPE_JPEG => 'jpg',
                    IMAGETYPE_PNG => 'png',
                    IMAGETYPE_GIF => 'gif',
                    IMAGETYPE_WEBP => 'webp'
                ];
                $ext = $extensions[$infosImage[2]] ?? null;
                if ($ext !== null) {
                    $dossier = __DIR__ . '/../uploads/profils/';
                    if (!is_dir($dossier)) {
                        @mkdir($dossier, 0775, true);
                    }
                    $nomFichier = 'profil_' . $idUser . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dossier . $nomFichier)) {
                        $champs['photo_user'] = $nomFichier;
                    }
                } else {
                    $messageErreur = "Le format de l'image n'est pas pris en charge (jpg, png, gif, webp uniquement).";
                }
            } else {
                $messageErreur = "L'image doit peser moins de 3 Mo et être une image valide.";
            }
        }

        if ($messageErreur === '') {
            try {
                $emailExiste = $con->prepare(
                    'SELECT COUNT(*) FROM t_utilisateurs WHERE email_user = ? AND id_user != ?'
                );
                $emailExiste->execute([$email, $idUser]);
                if ((int) $emailExiste->fetchColumn() > 0) {
                    $messageErreur = 'Cet e-mail est déjà utilisé par un autre compte.';
                } else {
                    $sets = [];
                    $valeurs = [];
                    foreach ($champs as $col => $val) {
                        $sets[] = "`$col` = ?";
                        $valeurs[] = $val;
                    }
                    $valeurs[] = $idUser;
                    $con->prepare('UPDATE t_utilisateurs SET ' . implode(', ', $sets) . ' WHERE id_user = ?')->execute(
                        $valeurs
                    );

                    $_SESSION['prenom_user'] = $prenom;
                    $_SESSION['nom_user'] = $nom;
                    $_SESSION['email_user'] = $email;
                    $_SESSION['tel_user'] = $tel;
                    if (isset($champs['photo_user'])) {
                        $_SESSION['photo_user'] = $champs['photo_user'];
                    }

                    $req = $con->prepare('SELECT * FROM t_utilisateurs WHERE id_user = ? LIMIT 1');
                    $req->execute([$idUser]);
                    $utilisateur = $req->fetch(PDO::FETCH_ASSOC) ?: $utilisateur;

                    $messageInfo = 'Vos informations ont été mises à jour.';
                }
            } catch (Throwable $e) {
                $messageErreur = 'Une erreur est survenue, merci de réessayer.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $onglet = 'securite';
    $ancien = (string) ($_POST['ancien_mdp'] ?? '');
    $nouveau = (string) ($_POST['nouveau_mdp'] ?? '');
    $confirmation = (string) ($_POST['confirmation_mdp'] ?? '');

    if ($nouveau === '' || strlen($nouveau) < 6) {
        $messageErreur = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
    } elseif ($nouveau !== $confirmation) {
        $messageErreur = 'La confirmation ne correspond pas au nouveau mot de passe.';
    } elseif (!password_verify($ancien, (string) ($utilisateur['motpass_user'] ?? ''))) {
        $messageErreur = 'Le mot de passe actuel est incorrect.';
    } else {
        try {
            $con->prepare('UPDATE t_utilisateurs SET motpass_user = ? WHERE id_user = ?')->execute([
                password_hash($nouveau, PASSWORD_DEFAULT),
                $idUser
            ]);
            $messageInfo = 'Votre mot de passe a été modifié.';
        } catch (Throwable $e) {
            $messageErreur = 'Une erreur est survenue, merci de réessayer.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'notifications') {
    $onglet = 'parametres';
    $notifEmail = isset($_POST['notif_email']) ? 1 : 0;
    $notifFormations = isset($_POST['notif_formations']) ? 1 : 0;
    $notifMessages = isset($_POST['notif_messages']) ? 1 : 0;
    $notifPromos = isset($_POST['notif_promotions']) ? 1 : 0;
    try {
        $req = $con->prepare(
            'INSERT INTO t_parametres_utilisateur (id_user, notif_email, notif_formations, notif_messages, notif_promotions)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE notif_email = VALUES(notif_email), notif_formations = VALUES(notif_formations),
                notif_messages = VALUES(notif_messages), notif_promotions = VALUES(notif_promotions)'
        );
        $req->execute([$idUser, $notifEmail, $notifFormations, $notifMessages, $notifPromos]);
        $messageInfo = 'Vos préférences ont été enregistrées.';
    } catch (Throwable $e) {
        $messageErreur = 'Une erreur est survenue, merci de réessayer.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'desactiver') {
    try {
        $con->prepare("UPDATE t_utilisateurs SET statut_user = 'inactif' WHERE id_user = ?")->execute([$idUser]);
        session_destroy();
        header('Location: ../login.php?desactive=1');
        exit();
    } catch (Throwable $e) {
        $onglet = 'parametres';
        $messageErreur = 'Une erreur est survenue, merci de réessayer.';
    }
}

$parametres = ['notif_email' => 1, 'notif_formations' => 1, 'notif_messages' => 1, 'notif_promotions' => 0];
try {
    $req = $con->prepare('SELECT * FROM t_parametres_utilisateur WHERE id_user = ?');
    $req->execute([$idUser]);
    $existant = $req->fetch(PDO::FETCH_ASSOC);
    if ($existant) {
        $parametres = $existant;
    }
} catch (Throwable $e) {
}

$prenom = (string) ($utilisateur['prenom_user'] ?? '');
$nom = (string) ($utilisateur['nom_user'] ?? '');
$email = (string) ($utilisateur['email_user'] ?? '');
$tel = (string) ($utilisateur['tel_user'] ?? '');
$nomAffiche = trim($prenom . ' ' . $nom) ?: 'Apprenant';
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'TO';
$dateInscription = $utilisateur['date_inscrip_user'] ?? null;

$photoUrl = '';
$photoBrute = trim((string) ($utilisateur['photo_user'] ?? ''));
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
        <title>Mon profil | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            .prof-page {
                padding: 24px 30px 30px;
                max-width: 1200px;
            }

            .prof-grid {
                display: grid;
                grid-template-columns: 280px minmax(0, 1fr);
                gap: 18px;
                align-items: start;
            }

            .prof-card {
                padding: 26px 22px;
                text-align: center;
            }

            .prof-avatar-wrap {
                position: relative;
                width: 96px;
                height: 96px;
                margin: 0 auto 14px;
            }

            .prof-avatar-big {
                width: 96px;
                height: 96px;
                border-radius: 50%;
                overflow: hidden;
                background: linear-gradient(135deg, #18213f, #20a1b8);
                color: #fff;
                display: grid;
                place-items: center;
                font-size: 26px;
                font-weight: 700;
            }

            .prof-avatar-big img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .prof-avatar-edit {
                position: absolute;
                right: 0;
                bottom: 0;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                background: #20a1b8;
                color: #fff;
                display: grid;
                place-items: center;
                font-size: 12px;
                border: 3px solid #fff;
                cursor: pointer;
            }

            .prof-card h2 {
                margin: 0 0 3px;
                font-size: 13px;
                color: #18213f;
            }

            .prof-card span {
                display: block;
                color: #8993a4;
                font-size: 11px;
                margin-bottom: 14px;
            }

            .prof-meta {
                display: flex;
                flex-direction: column;
                gap: 8px;
                text-align: left;
                margin-top: 10px;
            }

            .prof-meta div {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 9px;
                border-radius: 10px;
                background: #f7fafc;
                font-size: 11px;
                color: #5c6a7d;
            }

            .prof-meta i {
                color: #20a1b8;
                width: 14px;
            }

            .prof-tabs {
                display: flex;
                gap: 8px;
                margin-bottom: 16px;
                flex-wrap: wrap;
            }

            .prof-tab {
                padding: 9px 15px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 600;
                color: #657188;
                background: #eef2f6;
                cursor: pointer;
                border: 1px solid transparent;
            }

            .prof-tab.active {
                background: #fff;
                color: #18213f;
                border-color: #e1e9ef;
                box-shadow: 0 6px 16px rgba(24, 33, 63, 0.06);
            }

            .prof-section {
                display: none;
            }

            .prof-section.active {
                display: block;
            }

            .prof-field {
                margin-bottom: 14px;
            }

            .prof-field label {
                display: block;
                margin-bottom: 6px;
                font-size: 11px;
                font-weight: 600;
                color: #5c6a7d;
            }

            .prof-field input {
                width: 100%;
                height: 41px;
                border: 1px solid #dfe7ee;
                border-radius: 10px;
                padding: 0 13px;
                font: inherit;
                font-size: 11.5px;
                outline: 0;
                background: #f8fafc;
            }

            .prof-field input:focus {
                border-color: #8fc7d4;
            }

            .prof-row2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 13px;
            }

            .prof-alert {
                padding: 11px 14px;
                border-radius: 11px;
                font-size: 11px;
                margin-bottom: 15px;
            }

            .prof-alert.error {
                background: #fff0f0;
                color: #bd5353;
                border: 1px solid #f7d3d3;
            }

            .prof-alert.success {
                background: #e9f8f0;
                color: #188257;
                border: 1px solid #cdeedb;
            }

            .param-section-title {
                font-size: 12px;
                color: #18213f;
                margin: 0 0 3px;
            }

            .param-section-desc {
                font-size: 11px;
                color: #8993a4;
                margin: 0 0 15px;
            }

            .param-toggle-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
                padding: 13px 0;
                border-top: 1px solid #eef2f6;
            }

            .param-toggle-row:first-of-type {
                border-top: 0;
            }

            .param-toggle-row strong {
                display: block;
                font-size: 11px;
                color: #18213f;
            }

            .param-toggle-row span {
                display: block;
                margin-top: 2px;
                font-size: 11px;
                color: #8993a4;
            }

            .switch {
                position: relative;
                width: 40px;
                height: 22px;
                flex-shrink: 0;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .switch-track {
                position: absolute;
                inset: 0;
                background: #dbe3ea;
                border-radius: 20px;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .switch-track::before {
                content: "";
                position: absolute;
                width: 16px;
                height: 16px;
                left: 3px;
                top: 3px;
                background: #fff;
                border-radius: 50%;
                transition: 0.2s ease;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
            }

            .switch input:checked + .switch-track {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
            }

            .switch input:checked + .switch-track::before {
                transform: translateX(18px);
            }

            .param-danger {
                margin-top: 18px;
                padding: 16px;
                border-radius: 14px;
                background: #fff6f6;
                border: 1px solid #f7d3d3;
            }

            .param-danger strong {
                display: block;
                color: #bd5353;
                font-size: 11.5px;
                margin-bottom: 4px;
            }

            .param-danger p {
                margin: 0 0 12px;
                font-size: 11px;
                color: #a56666;
                line-height: 1.6;
            }

            .param-danger button {
                padding: 9px 15px;
                border-radius: 9px;
                border: 1px solid #e6a9a9;
                background: #fff;
                color: #bd5353;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
            }

            .param-danger button:hover {
                background: #fdeaea;
            }

            @media (max-width: 900px) {
                .prof-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 700px) {
                .prof-page {
                    padding: 18px;
                }

                .prof-row2 {
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

                <section class="prof-page">
                    <div class="prof-grid">
                        <div class="card prof-card">
                            <form id="photoForm" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="infos">
                                <input type="hidden" name="prenom_user" value="<?= e($prenom) ?>">
                                <input type="hidden" name="nom_user" value="<?= e($nom) ?>">
                                <input type="hidden" name="email_user" value="<?= e($email) ?>">
                                <input type="hidden" name="tel_user" value="<?= e($tel) ?>">
                                <div class="prof-avatar-wrap">
                                    <div class="prof-avatar-big">
                                        <?php if ($photoUrl !== ''): ?>
                                            <img src="<?= e($photoUrl) ?>" alt="Photo de profil">
                                        <?php else: ?>
                                            <?= e($initiales) ?>
                                        <?php endif; ?>
                                    </div>
                                    <label class="prof-avatar-edit" for="photoInput"><i class="fa-solid fa-camera"></i></label>
                                    <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none" onchange="document.getElementById('photoForm').submit()">
                                </div>
                            </form>
                            <h2><?= e($nomAffiche) ?></h2>
                            <span><?= e($email) ?></span>
                            <div class="prof-meta">
                                <div><i class="fa-solid fa-graduation-cap"></i> Apprenant TOUN</div>
                                <?php if ($dateInscription): ?>
                                    <div><i class="fa-regular fa-calendar"></i> Membre depuis le <?= date('d/m/Y', strtotime($dateInscription)) ?></div>
                                <?php endif; ?>
                                <?php if ($tel !== ''): ?>
                                    <div><i class="fa-solid fa-phone"></i> <?= e($tel) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="prof-tabs">
                                <div class="prof-tab <?= $onglet === 'infos' ? 'active' : '' ?>" data-tab="infos">Informations personnelles</div>
                                <div class="prof-tab <?= $onglet === 'securite' ? 'active' : '' ?>" data-tab="securite">Sécurité</div>
                                <div class="prof-tab <?= $onglet === 'parametres' ? 'active' : '' ?>" data-tab="parametres">Paramètres</div>
                            </div>

                            <?php if ($messageErreur): ?>
                                <div class="prof-alert error"><?= e($messageErreur) ?></div>
                            <?php endif; ?>
                            <?php if ($messageInfo): ?>
                                <div class="prof-alert success"><?= e($messageInfo) ?></div>
                            <?php endif; ?>

                            <div class="prof-section <?= $onglet === 'infos' ? 'active' : '' ?>" id="tab-infos">
                                <form method="post" action="profil.php">
                                    <input type="hidden" name="action" value="infos">
                                    <div class="prof-row2">
                                        <div class="prof-field"><label>Prénom</label><input type="text" name="prenom_user" value="<?= e($prenom) ?>" required></div>
                                        <div class="prof-field"><label>Nom</label><input type="text" name="nom_user" value="<?= e($nom) ?>" required></div>
                                    </div>
                                    <div class="prof-field"><label>E-mail</label><input type="email" name="email_user" value="<?= e($email) ?>" required></div>
                                    <div class="prof-field"><label>Téléphone</label><input type="text" name="tel_user" value="<?= e($tel) ?>"></div>
                                    <button type="submit" class="primary-btn">Enregistrer <i class="fa-solid fa-check"></i></button>
                                </form>
                            </div>

                            <div class="prof-section <?= $onglet === 'securite' ? 'active' : '' ?>" id="tab-securite">
                                <form method="post" action="profil.php">
                                    <input type="hidden" name="action" value="password">
                                    <div class="prof-field"><label>Mot de passe actuel</label><input type="password" name="ancien_mdp" required></div>
                                    <div class="prof-row2">
                                        <div class="prof-field"><label>Nouveau mot de passe</label><input type="password" name="nouveau_mdp" minlength="6" required></div>
                                        <div class="prof-field"><label>Confirmer</label><input type="password" name="confirmation_mdp" minlength="6" required></div>
                                    </div>
                                    <button type="submit" class="primary-btn">Modifier le mot de passe <i class="fa-solid fa-lock"></i></button>
                                </form>
                            </div>

                            <div class="prof-section <?= $onglet === 'parametres' ? 'active' : '' ?>" id="tab-parametres">
                                <h2 class="param-section-title">Notifications</h2>
                                <p class="param-section-desc">Choisissez les notifications que vous souhaitez recevoir sur TOUN.</p>
                                <form method="post" action="profil.php">
                                    <input type="hidden" name="action" value="notifications">

                                    <div class="param-toggle-row">
                                        <div><strong>Notifications par e-mail</strong><span>Recevoir un résumé de votre activité par e-mail</span></div>
                                        <label class="switch"><input type="checkbox" name="notif_email" <?= !empty($parametres['notif_email']) ? 'checked' : '' ?>><span class="switch-track"></span></label>
                                    </div>
                                    <div class="param-toggle-row">
                                        <div><strong>Mises à jour de formations</strong><span>Nouveaux modules, contenus ajoutés à vos formations</span></div>
                                        <label class="switch"><input type="checkbox" name="notif_formations" <?= !empty($parametres['notif_formations']) ? 'checked' : '' ?>><span class="switch-track"></span></label>
                                    </div>
                                    <div class="param-toggle-row">
                                        <div><strong>Messages de l'équipe TOUN</strong><span>Être notifié des nouvelles réponses reçues</span></div>
                                        <label class="switch"><input type="checkbox" name="notif_messages" <?= !empty($parametres['notif_messages']) ? 'checked' : '' ?>><span class="switch-track"></span></label>
                                    </div>
                                    <div class="param-toggle-row">
                                        <div><strong>Offres et promotions</strong><span>Nouvelles formations, réductions et actualités TOUN</span></div>
                                        <label class="switch"><input type="checkbox" name="notif_promotions" <?= !empty($parametres['notif_promotions']) ? 'checked' : '' ?>><span class="switch-track"></span></label>
                                    </div>

                                    <button type="submit" class="primary-btn" style="margin-top:16px">Enregistrer <i class="fa-solid fa-check"></i></button>
                                </form>

                                <div class="param-danger">
                                    <strong>Désactiver mon compte</strong>
                                    <p>Votre compte sera désactivé et vous serez déconnecté. Contactez l'équipe TOUN pour le réactiver.</p>
                                    <form method="post" action="profil.php" onsubmit="return confirm('Confirmer la désactivation de votre compte ?');">
                                        <input type="hidden" name="action" value="desactiver">
                                        <button type="submit">Désactiver mon compte</button>
                                    </form>
                                </div>
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
                mobileMenu.addEventListener('click', () => sidebar.classList.toggle('open'));
            }

            document.querySelectorAll('.prof-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.prof-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.prof-section').forEach(s => s.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
                });
            });
        </script>
    </body>
</html>

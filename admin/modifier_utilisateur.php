<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function roleLabel($role)
{
    $labels = [
        'admin' => 'Administrateur',
        'formateur' => 'Formateur',
        'gest_entreprise' => 'Entreprise',
        'apprenant' => 'Professionnel'
    ];
    return $labels[$role] ?? ucfirst(str_replace('_', ' ', (string) $role));
}

function initiales($prenom, $nom)
{
    $a = mb_substr(trim((string) $prenom), 0, 1, 'UTF-8');
    $b = mb_substr(trim((string) $nom), 0, 1, 'UTF-8');
    return mb_strtoupper($a . $b, 'UTF-8');
}

function photoUtilisateur($photo)
{
    if (empty($photo)) {
        return '';
    }

    $photo = ltrim(str_replace('\\', '/', trim($photo)), '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $nom_photo = basename($photo);

    $candidats = [
        [__DIR__ . '/../uploads/profils/' . $nom_photo, '../uploads/profils/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../uploads/photos/' . $nom_photo, '../uploads/photos/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../uploads/' . $nom_photo, '../uploads/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../images/' . $nom_photo, '../images/' . rawurlencode($nom_photo)]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat[0])) {
            return $candidat[1];
        }
    }

    return '';
}

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));

$stmt = $con->prepare("
    SELECT id_user, nom_user, prenom_user, email_user, tel_user,
           photo_user, role_user, statut_user, date_inscrip_user
    FROM t_utilisateurs
    WHERE id_user = ?
    LIMIT 1
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['user_error'] = 'Utilisateur introuvable.';
    header('Location: utilisateur.php');
    exit();
}

$erreurs = [];
$form = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['prenom_user'] = trim($_POST['prenom_user'] ?? '');
    $form['nom_user'] = trim($_POST['nom_user'] ?? '');
    $form['email_user'] = trim($_POST['email_user'] ?? '');
    $form['tel_user'] = trim($_POST['tel_user'] ?? '');
    $form['role_user'] = $_POST['role_user'] ?? $user['role_user'];
    $form['statut_user'] = $_POST['statut_user'] ?? $user['statut_user'];
    $motpass = $_POST['motpass_user'] ?? '';
    $confirmation = $_POST['confirmation_motpass'] ?? '';

    $roles_autorises = ['admin', 'formateur', 'gest_entreprise', 'apprenant'];
    $statuts_autorises = ['actif', 'inactif'];

    if ($form['prenom_user'] === '' || $form['nom_user'] === '') {
        $erreurs[] = 'Le nom et le prénom sont obligatoires.';
    }

    if (!filter_var($form['email_user'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse e-mail n'est pas valide.";
    } else {
        $q = $con->prepare('SELECT COUNT(*) FROM t_utilisateurs WHERE email_user = ? AND id_user <> ?');
        $q->execute([$form['email_user'], $id]);
        if ((int) $q->fetchColumn() > 0) {
            $erreurs[] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        }
    }

    if (!in_array($form['role_user'], $roles_autorises, true)) {
        $erreurs[] = "Le rôle choisi n'est pas valide.";
    }

    if (!in_array($form['statut_user'], $statuts_autorises, true)) {
        $erreurs[] = "Le statut choisi n'est pas valide.";
    }

    if ($id === (int) $_SESSION['id_user'] && ($form['role_user'] !== 'admin' || $form['statut_user'] !== 'actif')) {
        $erreurs[] = "Vous ne pouvez pas retirer vos propres droits d'administrateur ni désactiver votre compte.";
    }

    if ($motpass !== '' && strlen($motpass) < 8) {
        $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } elseif ($motpass !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    $nouvelle_photo = null;

    if (isset($_FILES['photo_user']) && $_FILES['photo_user']['error'] !== UPLOAD_ERR_NO_FILE) {
        $extension = strtolower(pathinfo($_FILES['photo_user']['name'], PATHINFO_EXTENSION));

        if ($_FILES['photo_user']['error'] !== UPLOAD_ERR_OK) {
            $erreurs[] = 'Le téléchargement de la photo a échoué.';
        } elseif (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $erreurs[] = 'La photo doit être au format JPG, PNG ou WEBP.';
        } elseif ($_FILES['photo_user']['size'] > 2 * 1024 * 1024) {
            $erreurs[] = 'La photo ne doit pas dépasser 2 Mo.';
        } elseif (!$erreurs) {
            $dossier = __DIR__ . '/../uploads/profils/';
            if (!is_dir($dossier)) {
                mkdir($dossier, 0777, true);
            }
            $nouvelle_photo = uniqid('profil_', true) . '.' . $extension;
            if (!move_uploaded_file($_FILES['photo_user']['tmp_name'], $dossier . $nouvelle_photo)) {
                $erreurs[] = "Impossible d'enregistrer la photo.";
                $nouvelle_photo = null;
            }
        }
    }

    if (!$erreurs) {
        $champs = 'nom_user = ?, prenom_user = ?, email_user = ?, tel_user = ?, role_user = ?, statut_user = ?';
        $valeurs = [
            $form['nom_user'],
            $form['prenom_user'],
            $form['email_user'],
            $form['tel_user'],
            $form['role_user'],
            $form['statut_user']
        ];

        if ($motpass !== '') {
            $champs .= ', motpass_user = ?';
            $valeurs[] = password_hash($motpass, PASSWORD_DEFAULT);
        }

        if ($nouvelle_photo) {
            $champs .= ', photo_user = ?';
            $valeurs[] = $nouvelle_photo;
        }

        $valeurs[] = $id;

        try {
            $con->prepare("UPDATE t_utilisateurs SET $champs WHERE id_user = ?")->execute($valeurs);

            if ($nouvelle_photo && !empty($user['photo_user'])) {
                $ancienne = __DIR__ . '/../uploads/profils/' . basename($user['photo_user']);
                if (is_file($ancienne)) {
                    @unlink($ancienne);
                }
            }

            $_SESSION['user_success'] = "Les informations de l'utilisateur ont été mises à jour.";
            header('Location: voir_utilisateur.php?id=' . $id);
            exit();
        } catch (PDOException $e) {
            $erreurs[] = 'La mise à jour a échoué. Veuillez réessayer.';
        }
    }
}

$photo_user = photoUtilisateur($user['photo_user'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Modifier un utilisateur - TOUN</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html,
            body {
                min-height: 100%;
            }

            body {
                font-family: "Poppins", sans-serif;
                background: #f5f8fc;
                color: #18213f;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            button,
            input,
            select {
                font: inherit;
            }

            .layout {
                min-height: 100vh;
            }

            .main {
                width: calc(100% - 260px);
                min-height: 100vh;
                margin-left: 260px;
                padding-top: 64px;
            }

            .content {
                padding: 16px 20px 22px;
            }

            .page-head {
                min-height: 42px;
                margin-bottom: 11px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
            }

            .page-title h1 {
                margin-bottom: 2px;
                font-size: 21px;
                line-height: 1.2;
            }

            .page-title p {
                color: #8995a8;
                font-size: 11px;
            }

            .btn-primary {
                height: 36px;
                padding: 0 14px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 0;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
                box-shadow: 0 6px 15px rgba(63, 104, 174, 0.16);
                font-size: 11px;
                font-weight: 600;
                transition:
                    transform 0.2s ease,
                    box-shadow 0.2s ease;
            }

            .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 9px 18px rgba(63, 104, 174, 0.22);
            }

            .role,
            .status {
                display: inline-flex;
                align-items: center;
                border-radius: 6px;
                font-size: 10px;
                font-weight: 600;
            }

            .role {
                padding: 4px 7px;
                color: #3f68ae;
                background: #edf3fb;
            }

            .status {
                gap: 4px;
                padding: 4px 7px;
            }

            .status::before {
                content: "";
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
            }

            .status.active {
                color: #168b61;
                background: #eaf8f2;
            }

            .status.inactive {
                color: #d05763;
                background: #fff0f2;
            }

            .error-box {
                margin-bottom: 10px;
                padding: 9px 12px;
                border: 1px solid #f3d5d9;
                border-radius: 8px;
                color: #c84e5a;
                background: #fff1f3;
                font-size: 11px;
            }

            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 4px;
                color: #8995a8;
                font-size: 11px;
            }

            .back-link:hover {
                color: #3f68ae;
            }

            .card {
                padding: 20px;
                border: 1px solid #e7edf4;
                border-radius: 14px;
                background: #fff;
                box-shadow: 0 6px 18px rgba(24, 33, 63, 0.04);
            }

            .form-card {
                max-width: 820px;
            }

            .photo-row {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 18px;
                padding-bottom: 18px;
                border-bottom: 1px solid #eef2f7;
            }

            .big-avatar {
                width: 70px;
                height: 70px;
                flex-shrink: 0;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 3px solid #e2f3f5;
                border-radius: 50%;
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                font-size: 20px;
                font-weight: 700;
            }

            .big-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .form-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .field {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .field label {
                color: #3c465d;
                font-size: 11px;
                font-weight: 600;
            }

            .field input,
            .field select {
                height: 38px;
                padding: 0 11px;
                border: 1px solid #dfe5ec;
                border-radius: 9px;
                outline: none;
                color: #18213f;
                background: #fff;
                font-size: 11.5px;
                transition:
                    border-color 0.2s ease,
                    box-shadow 0.2s ease;
            }

            .field input[type="file"] {
                height: auto;
                padding: 8px 10px;
            }

            .field input:focus,
            .field select:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .field small {
                color: #8995a8;
                font-size: 10.5px;
            }

            .form-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin-top: 20px;
            }

            .btn-light {
                height: 36px;
                padding: 0 16px;
                display: inline-flex;
                align-items: center;
                border: 1px solid #dfe5ec;
                border-radius: 9px;
                color: #3c465d;
                background: #fff;
                font-size: 11px;
                font-weight: 600;
            }

            .btn-light:hover {
                background: #f5f8fc;
            }

            .error-box div + div {
                margin-top: 4px;
            }

            @media (max-width: 700px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }

                .photo-row {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }
        </style>
    </head>

    <body>
        <div class="layout">

            <?php
$active_page = 'utilisateur.php';
include 'menu.php';
?>

            <main class="main">
                <section class="content">

                    <div class="page-head">
                        <div class="page-title">
                            <a href="utilisateur.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Retour aux utilisateurs</a>
                            <h1>Modifier l'utilisateur</h1>
                            <p>Mettez à jour les informations et les accès du compte.</p>
                        </div>
                    </div>

                    <?php if ($erreurs): ?>
                        <div class="error-box">
                            <?php foreach ($erreurs as $message): ?>
                                <div><i class="fa-solid fa-circle-exclamation"></i> <?= e($message) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" class="card form-card">
                        <input type="hidden" name="id" value="<?= (int) $user['id_user'] ?>">

                        <div class="photo-row">
                            <div class="big-avatar">
                                <?php if ($photo_user): ?>
                                    <img src="<?= e($photo_user) ?>" alt="Photo de profil">
                                <?php else: ?>
                                    <?= e(initiales($user['prenom_user'], $user['nom_user'])) ?>
                                <?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="u_photo_user">Photo de profil</label>
                                <input id="u_photo_user" type="file" name="photo_user" accept=".jpg,.jpeg,.png,.webp">
                                <small>JPG, PNG ou WEBP, 2 Mo maximum. Laissez vide pour garder la photo actuelle.</small>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="u_prenom_user">Prénom</label>
                                <input id="u_prenom_user" type="text" name="prenom_user" value="<?= e($form['prenom_user']) ?>" required>
                            </div>

                            <div class="field">
                                <label for="u_nom_user">Nom</label>
                                <input id="u_nom_user" type="text" name="nom_user" value="<?= e($form['nom_user']) ?>" required>
                            </div>

                            <div class="field">
                                <label for="u_email_user">E-mail</label>
                                <input id="u_email_user" type="email" name="email_user" value="<?= e($form['email_user']) ?>" required>
                            </div>

                            <div class="field">
                                <label for="u_tel_user">Téléphone</label>
                                <input id="u_tel_user" type="text" name="tel_user" value="<?= e($form['tel_user']) ?>">
                            </div>

                            <div class="field">
                                <label for="u_role_user">Rôle</label>
                                <select id="u_role_user" name="role_user">
                                    <?php foreach (['apprenant', 'gest_entreprise', 'formateur', 'admin'] as $option): ?>
                                        <option value="<?= $option ?>" <?= $form['role_user'] === $option ? 'selected' : '' ?>><?= e(roleLabel($option)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="u_statut_user">Statut</label>
                                <select id="u_statut_user" name="statut_user">
                                    <option value="actif" <?= $form['statut_user'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactif" <?= $form['statut_user'] !== 'actif' ? 'selected' : '' ?>>Inactif</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="u_motpass_user">Nouveau mot de passe</label>
                                <input id="u_motpass_user" type="password" name="motpass_user" minlength="8" autocomplete="new-password" placeholder="Laisser vide pour ne pas changer">
                            </div>

                            <div class="field">
                                <label for="u_confirmation_motpass">Confirmation</label>
                                <input id="u_confirmation_motpass" type="password" name="confirmation_motpass" minlength="8" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="voir_utilisateur.php?id=<?= (int) $user['id_user'] ?>" class="btn-light">Annuler</a>
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid fa-check"></i>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>

                </section>
            </main>
        </div>
    </body>
</html>

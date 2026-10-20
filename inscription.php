<?php
session_start();
require_once 'config/connexion.php';

$message = '';

function enregistrerPhotoProfil(array $fichier, ?string &$erreur): ?string
{
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $erreur = "La photo n'a pas pu être envoyée.";
        return null;
    }

    $extension = strtolower(pathinfo($fichier['name'] ?? '', PATHINFO_EXTENSION));
    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $extensionsAutorisees, true)) {
        $erreur = 'Format de photo non autorisé.';
        return null;
    }

    if (($fichier['size'] ?? 0) > 5 * 1024 * 1024) {
        $erreur = 'La photo ne doit pas dépasser 5 Mo.';
        return null;
    }

    $dossier = __DIR__ . '/uploads/profils';

    if (!is_dir($dossier) && !mkdir($dossier, 0777, true) && !is_dir($dossier)) {
        $erreur = 'Impossible de créer le dossier des photos.';
        return null;
    }

    try {
        $nomFichier = 'profil_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    } catch (Throwable $e) {
        $erreur = 'Impossible de générer le nom de la photo.';
        return null;
    }

    $destination = $dossier . '/' . $nomFichier;

    if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
        $erreur = "Impossible d'enregistrer la photo.";
        return null;
    }

    return $nomFichier;
}

function v(string $champ): string
{
    return htmlspecialchars($_POST[$champ] ?? '', ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $message = 'Le fichier envoyé est trop volumineux.';
    }

    $nom = trim($_POST['nom_user'] ?? '');
    $prenom = trim($_POST['prenom_user'] ?? '');
    $email = strtolower(trim($_POST['email_user'] ?? ''));
    $tel = trim($_POST['tel_user'] ?? '');
    $motpass = $_POST['motpass_user'] ?? '';
    $confirmation = $_POST['confirmation_motpass'] ?? '';

    if (
        $message === '' &&
        ($nom === '' || $prenom === '' || $email === '' || $motpass === '' || $confirmation === '')
    ) {
        $message = 'Veuillez remplir les champs obligatoires.';
    } elseif ($message === '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse e-mail invalide.';
    } elseif ($message === '' && strlen($motpass) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($message === '' && $motpass !== $confirmation) {
        $message = 'Les deux mots de passe ne correspondent pas.';
    }

    if ($message === '') {
        try {
            $req = $con->prepare('SELECT id_user FROM t_utilisateurs WHERE email_user = ? LIMIT 1');
            $req->execute([$email]);

            if ($req->fetch()) {
                $message = 'Cette adresse e-mail est déjà utilisée.';
            }
        } catch (PDOException $e) {
            $message = "Impossible de vérifier l'adresse e-mail.";
        }
    }

    $photoNom = null;

    if (
        $message === '' &&
        isset($_FILES['photo_user']) &&
        ($_FILES['photo_user']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ) {
        $photoNom = enregistrerPhotoProfil($_FILES['photo_user'], $message);
    }

    if ($message === '') {
        try {
            $hash = password_hash($motpass, PASSWORD_DEFAULT);

            if ($hash === false) {
                throw new RuntimeException('Impossible de sécuriser le mot de passe.');
            }

            $sql = "INSERT INTO t_utilisateurs
                    (nom_user, prenom_user, email_user, tel_user, photo_user, motpass_user, role_user, statut_user, date_inscrip_user)
                    VALUES (?, ?, ?, ?, ?, ?, 'apprenant', 'actif', NOW())";

            $req = $con->prepare($sql);
            $req->execute([$nom, $prenom, $email, $tel, $photoNom, $hash]);

            header('Location: login.php?inscription=success');
            exit();
        } catch (PDOException $e) {
            if ($photoNom !== null && is_file(__DIR__ . '/uploads/profils/' . $photoNom)) {
                unlink(__DIR__ . '/uploads/profils/' . $photoNom);
            }
            $message = 'Une erreur est survenue lors de la création du compte.';
        } catch (Throwable $e) {
            if ($photoNom !== null && is_file(__DIR__ . '/uploads/profils/' . $photoNom)) {
                unlink(__DIR__ . '/uploads/profils/' . $photoNom);
            }
            $message = 'Une erreur est survenue lors de la création du compte.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>S'inscrire | TOUN</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                padding: 24px;
                font-family: "Poppins", sans-serif;
                background: #f3f7fb;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .auth-card {
                width: 100%;
                max-width: 880px;
                background: #fff;
                border-radius: 18px;
                overflow: hidden;
                display: grid;
                grid-template-columns: 44% 56%;
                box-shadow: 0 18px 45px rgba(24, 33, 63, 0.12);
            }

            .auth-visual {
                position: relative;
                min-height: 500px;
                padding: 34px;
                background: #073e73;
                color: #fff;
                overflow: hidden;
            }

            .brand {
                width: 112px;
                height: 32px;
                object-fit: contain;
                object-position: left center;
                position: relative;
                z-index: 2;
            }

            .visual-content {
                position: relative;
                z-index: 2;
                margin-top: 72px;
                max-width: 245px;
            }

            .eyebrow {
                display: inline-block;
                margin-bottom: 10px;
                color: #62d4df;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: 1.1px;
                text-transform: uppercase;
            }

            .visual-content h1 {
                margin-bottom: 12px;
                font-size: 25px;
                line-height: 1.28;
            }

            .visual-content p {
                color: rgba(255, 255, 255, 0.76);
                font-size: 12px;
                line-height: 1.75;
            }

            .visual-image {
                position: absolute;
                right: -18px;
                bottom: -18px;
                width: 245px;
                height: 245px;
                object-fit: contain;
                filter: drop-shadow(0 18px 25px rgba(0, 0, 0, 0.14));
            }

            .shape {
                position: absolute;
                top: 100px;
                right: -85px;
                width: 180px;
                height: 180px;
                border-radius: 50%;
                background: rgba(32, 161, 184, 0.22);
            }

            .shape-two {
                position: absolute;
                left: -50px;
                bottom: 45px;
                width: 95px;
                height: 95px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.06);
            }

            .auth-form {
                width: 100%;
                max-width: 420px;
                margin: auto;
                padding: 30px 45px;
            }

            .form-header {
                margin-bottom: 17px;
                text-align: center;
            }

            .form-header h2 {
                margin-bottom: 4px;
                color: #073e73;
                font-size: 21px;
            }

            .form-header p {
                color: #8a93a4;
                font-size: 11.5px;
            }

            .alert {
                margin-bottom: 12px;
                padding: 8px 10px;
                border-radius: 8px;
                background: #fff1f1;
                color: #c43d3d;
                font-size: 11px;
            }

            .grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px 12px;
            }

            .field {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .field.full {
                grid-column: 1/-1;
            }

            .field label {
                color: #3c465d;
                font-size: 11px;
                font-weight: 600;
            }

            .field input {
                width: 100%;
                height: 38px;
                padding: 0 10px;
                border: 1px solid #dfe4eb;
                border-radius: 8px;
                outline: none;
                color: #18213f;
                font-family: "Poppins", sans-serif;
                font-size: 11.5px;
            }

            .field input:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .photo {
                height: 38px;
                padding: 0 9px;
                display: flex;
                align-items: center;
                border: 1px dashed #cbd5e1;
                border-radius: 8px;
                background: #f9fbfd;
            }

            .photo input {
                height: auto;
                padding: 0;
                border: 0;
                background: transparent;
                font-size: 11px;
            }

            .btn {
                width: 100%;
                height: 41px;
                margin-top: 15px;
                border: 0;
                border-radius: 8px;
                background: #073e73;
                color: #fff;
                font-family: "Poppins", sans-serif;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .btn:hover {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                transform: translateY(-1px);
            }

            .bottom {
                margin-top: 14px;
                padding-top: 13px;
                border-top: 1px solid #edf0f4;
                text-align: center;
                color: #8a93a4;
                font-size: 11px;
            }

            .bottom a {
                color: #073e73;
                font-weight: 600;
                text-decoration: none;
            }

            @media (max-width: 720px) {
                body {
                    padding: 14px;
                }

                .auth-card {
                    grid-template-columns: 1fr;
                }

                .auth-visual {
                    min-height: 245px;
                    padding: 27px;
                }

                .visual-content {
                    margin-top: 35px;
                }

                .visual-content h1 {
                    font-size: 21px;
                }

                .visual-image {
                    width: 190px;
                    height: 190px;
                    right: -15px;
                    bottom: -18px;
                }

                .auth-form {
                    padding: 30px 27px;
                }
            }

            @media (max-width: 470px) {
                .grid {
                    grid-template-columns: 1fr;
                }

                .field.full {
                    grid-column: auto;
                }
            }
        </style>
    </head>
    <body>
        <main class="auth-card">
            <section class="auth-visual">
                <img class="brand" src="images/logo1.png" alt="TOUN">
                <div class="visual-content">
                    <span class="eyebrow">Espace professionnel</span>
                    <h1>Votre parcours commence ici.</h1>
                    <p>Créez votre compte et accédez simplement à vos formations professionnelles.</p>
                </div>
                <div class="shape"></div>
                <div class="shape-two"></div>
                <img class="visual-image" src="images/img.png" alt="">
            </section>
            <section class="auth-form">
                <div class="form-header">
                    <h2>Créer un compte</h2>
                    <p>Inscrivez-vous en tant que professionnel.</p>
                </div>
                <?php if ($message !== ""): ?>
                    <div class="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid">
                        <div class="field">
                            <label for="nom_user">Nom *</label>
                            <input id="nom_user" type="text" name="nom_user" value="<?= v('nom_user') ?>" autocomplete="family-name" required>
                        </div>
                        <div class="field">
                            <label for="prenom_user">Prénom *</label>
                            <input id="prenom_user" type="text" name="prenom_user" value="<?= v('prenom_user') ?>" autocomplete="given-name" required>
                        </div>
                        <div class="field">
                            <label for="email_user">Email *</label>
                            <input id="email_user" type="email" name="email_user" value="<?= v('email_user') ?>" autocomplete="email" required>
                        </div>
                        <div class="field">
                            <label for="tel_user">Téléphone</label>
                            <input id="tel_user" type="tel" name="tel_user" value="<?= v('tel_user') ?>" autocomplete="tel">
                        </div>
                        <div class="field">
                            <label for="motpass_user">Mot de passe *</label>
                            <input id="motpass_user" type="password" name="motpass_user" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="field">
                            <label for="confirmation_motpass">Confirmation *</label>
                            <input id="confirmation_motpass" type="password" name="confirmation_motpass" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="field full">
                            <label for="photo_user">Photo de profil</label>
                            <div class="photo"><input id="photo_user" type="file" name="photo_user" accept=".jpg,.jpeg,.png,.webp"></div>
                        </div>
                    </div>
                    <button class="btn" type="submit">Créer mon compte</button>
                </form>
                <div class="bottom">Vous avez déjà un compte ? <a href="login.php">Se connecter</a></div>
            </section>
        </main>
    </body>
</html>

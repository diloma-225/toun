<?php
session_start();

require_once '../config/connexion.php';

$message = '';
$type_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom_user'] ?? '');
    $prenom = trim($_POST['prenom_user'] ?? '');
    $email = trim($_POST['email_user'] ?? '');
    $tel = trim($_POST['tel_user'] ?? '');
    $motpass = $_POST['motpass_user'] ?? '';
    $confirmation = $_POST['confirmation_motpass'] ?? '';

    if (empty($nom) || empty($prenom) || empty($email) || empty($motpass) || empty($confirmation)) {
        $message = 'Veuillez remplir tous les champs obligatoires.';
        $type_message = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse e-mail invalide.';
        $type_message = 'error';
    } elseif ($motpass !== $confirmation) {
        $message = 'Les deux mots de passe sont différents.';
        $type_message = 'error';
    } elseif (strlen($motpass) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        $type_message = 'error';
    } else {
        $sql = "SELECT id_user
                FROM t_utilisateurs
                WHERE email_user = ?";

        $req = $con->prepare($sql);
        $req->execute([$email]);

        if ($req->fetch()) {
            $message = 'Cette adresse e-mail existe déjà.';
            $type_message = 'error';
        } else {
            $photo_nom = null;

            if (isset($_FILES['photo_user']) && $_FILES['photo_user']['error'] != UPLOAD_ERR_NO_FILE) {
                if ($_FILES['photo_user']['error'] !== UPLOAD_ERR_OK) {
                    $message = "Une erreur est survenue lors de l'envoi de la photo.";
                    $type_message = 'error';
                } else {
                    $photo = $_FILES['photo_user'];

                    $extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));

                    $extensions_autorisees = ['jpg', 'jpeg', 'png', 'webp'];

                    if (!in_array($extension, $extensions_autorisees, true)) {
                        $message = 'Format de photo non autorisé.';
                        $type_message = 'error';
                    } elseif ($photo['size'] > 5 * 1024 * 1024) {
                        $message = 'La photo ne doit pas dépasser 5 Mo.';
                        $type_message = 'error';
                    } else {
                        $dossier = __DIR__ . '/../uploads/profils/';

                        if (!is_dir($dossier)) {
                            if (!mkdir($dossier, 0777, true)) {
                                $message = 'Impossible de créer le dossier des photos.';
                                $type_message = 'error';
                            }
                        }

                        if ($message == '') {
                            $photo_nom = uniqid('admin_', true) . '.' . $extension;

                            $chemin_photo = $dossier . $photo_nom;

                            if (!move_uploaded_file($photo['tmp_name'], $chemin_photo)) {
                                $message = "Impossible d'enregistrer la photo.";
                                $type_message = 'error';

                                $photo_nom = null;
                            }
                        }
                    }
                }
            }

            if ($message == '') {
                $motpass_hash = password_hash($motpass, PASSWORD_DEFAULT);

                $sql = "INSERT INTO t_utilisateurs (
                            nom_user,
                            prenom_user,
                            email_user,
                            tel_user,
                            photo_user,
                            motpass_user,
                            role_user,
                            statut_user
                        )
                        VALUES (?, ?, ?, ?, ?, ?, 'admin', 'actif')";

                $req = $con->prepare($sql);

                $rep = $req->execute([$nom, $prenom, $email, $tel, $photo_nom, $motpass_hash]);
                if ($rep) {
                    if (($_SESSION['role_user'] ?? '') === 'admin') {
                        $_SESSION['flash_success'] = "L'utilisateur a été ajouté avec succès.";
                        header('Location: utilisateur.php');
                    } else {
                        header('Location: ../login.php');
                    }
                    exit();
                } else {
                    $message = "Échec de l'inscription.";
                    $type_message = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Inscription administrateur - TOUN</title>

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        >

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                font-family: "Poppins", sans-serif;
                background: linear-gradient(120deg, #ffffff 0%, #f9ffff 22%, #e9fafa 45%, #f7ffff 68%, #ffffff 100%);
                background-size: 300% 300%;
                animation: backgroundMove 14s ease-in-out infinite;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 30px 15px;
                color: #18213f;
                overflow-x: hidden;
                position: relative;
            }

            @keyframes backgroundMove {
                0% {
                    background-position: 0% 50%;
                }

                50% {
                    background-position: 100% 50%;
                }

                100% {
                    background-position: 0% 50%;
                }
            }

            body::before {
                content: "";
                position: fixed;
                width: 400px;
                height: 400px;
                background: rgba(32, 161, 184, 0.1);
                border-radius: 50%;
                filter: blur(90px);
                top: -180px;
                left: -120px;
                pointer-events: none;
            }

            body::after {
                content: "";
                position: fixed;
                width: 350px;
                height: 350px;
                background: rgba(63, 104, 174, 0.1);
                border-radius: 50%;
                filter: blur(90px);
                bottom: -180px;
                right: -100px;
                pointer-events: none;
            }

            .inscription-container {
                position: relative;
                width: 100%;
                max-width: 550px;
                padding: 20px 30px 15px;
                border-radius: 18px;
                background: linear-gradient(145deg, #074b82 0%, #294b80 48%, #027da3 100%);
                box-shadow: 0 25px 70px rgba(24, 33, 63, 0.18);
                overflow: hidden;
                z-index: 2;
            }

            .inscription-container::before {
                content: "";
                position: absolute;
                width: 260px;
                height: 260px;
                border-radius: 50%;
                background: rgba(32, 161, 184, 0.2);
                filter: blur(35px);
                top: -130px;
                right: -100px;
                animation: floatLight 7s ease-in-out infinite;
            }

            .inscription-container::after {
                content: "";
                position: absolute;
                width: 220px;
                height: 220px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.08);
                filter: blur(35px);
                bottom: -130px;
                left: -100px;
                animation: floatLight 8s ease-in-out infinite reverse;
            }

            @keyframes floatLight {
                0%,
                100% {
                    transform: translate(0, 0);
                }

                50% {
                    transform: translate(20px, 15px);
                }
            }

            .content {
                position: relative;
                z-index: 3;
            }

            .logo {
                text-align: center;
                margin-bottom: 4px;
            }

            .logo img {
                width: 75px;
                height: auto;
                display: block;
                margin: 0 auto;
            }

            .title {
                text-align: center;
                color: white;
                font-size: 22px;
                font-weight: 700;
                margin-bottom: 3px;
            }

            .subtitle {
                text-align: center;
                color: rgba(255, 255, 255, 0.82);
                font-size: 12.5px;
                margin-bottom: 16px;
            }

            .message {
                padding: 10px 12px;
                border-radius: 8px;
                font-size: 12.5px;
                margin-bottom: 14px;
                text-align: center;
            }

            .message.success {
                background: rgba(46, 204, 113, 0.15);
                border: 1px solid rgba(46, 204, 113, 0.45);
                color: #d9ffe8;
            }

            .message.error {
                background: rgba(231, 76, 60, 0.16);
                border: 1px solid rgba(231, 76, 60, 0.45);
                color: #ffe0dc;
            }

            .photo-section {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 15px;
                padding: 10px 12px;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.12);
            }

            .photo-preview {
                width: 62px;
                height: 62px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.15);
                border: 2px solid rgba(255, 255, 255, 0.65);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                flex-shrink: 0;
            }

            .photo-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: none;
            }

            .photo-icon {
                font-size: 23px;
                color: white;
            }

            .photo-info {
                flex: 1;
            }

            .photo-info label {
                display: block;
                color: white;
                font-size: 12.5px;
                font-weight: 600;
                margin-bottom: 3px;
            }

            .photo-info small {
                display: block;
                color: rgba(255, 255, 255, 0.7);
                font-size: 11px;
                line-height: 1.4;
                margin-bottom: 7px;
            }

            .photo-input {
                width: 100%;
                color: white;
                font-size: 11.5px;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 14px;
                margin-bottom: 11px;
            }

            .form-group {
                margin-bottom: 11px;
            }

            .form-row .form-group {
                margin-bottom: 0;
            }

            label {
                display: block;
                color: white;
                font-size: 12px;
                font-weight: 500;
                margin-bottom: 5px;
            }

            .required {
                color: #ffb4b4;
                font-weight: 700;
            }

            .input-box {
                position: relative;
            }

            .input-box input {
                width: 100%;
                height: 38px;
                border: 1px solid rgba(255, 255, 255, 0.35);
                border-radius: 8px;
                outline: none;
                background: rgba(255, 255, 255, 0.96);
                color: #18213f;
                padding: 0 12px;
                font-family: "Poppins", sans-serif;
                font-size: 12px;
                transition: 0.25s;
            }

            .input-box input:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.18);
            }

            .input-box input::placeholder {
                color: #9aa4b6;
            }

            .password-box input {
                padding-right: 40px;
            }

            .toggle-password {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #60708c;
                cursor: pointer;
                font-size: 13px;
                user-select: none;
            }

            .btn-inscription {
                width: 100%;
                height: 40px;
                border: none;
                border-radius: 8px;
                margin-top: 4px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: white;
                font-family: "Poppins", sans-serif;
                font-size: 12.5px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.3s;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }

            .btn-inscription:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
            }

            .bottom-text {
                text-align: center;
                margin-top: 12px;
                color: rgba(255, 255, 255, 0.8);
                font-size: 11.5px;
            }

            .bottom-text a {
                color: white;
                font-weight: 600;
                text-decoration: none;
            }

            .bottom-text a:hover {
                text-decoration: underline;
            }

            @media (max-width: 600px) {
                body {
                    padding: 15px;
                }

                .inscription-container {
                    max-width: 100%;
                    padding: 20px 18px 15px;
                    border-radius: 15px;
                }

                .form-row {
                    grid-template-columns: 1fr;
                    gap: 0;
                }

                .form-row .form-group {
                    margin-bottom: 11px;
                }

                .title {
                    font-size: 20px;
                }
            }
        </style>

    </head>

    <body>

        <div class="inscription-container">

            <div class="content">

                <h1 class="title">
                    Créer votre compte
                </h1>

                <p class="subtitle">
                    Création du compte administrateur TOUN
                </p>

                <?php if ($message != ""): ?>

                    <div class="message <?= $type_message ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="photo-section">

                        <div class="photo-preview">

                            <span
                                class="photo-icon"
                                id="photoIcon"
                            >
                                👤
                            </span>

                            <img
                                id="photoPreview"
                                src=""
                                alt="Aperçu"
                            >

                        </div>

                        <div class="photo-info">

                            <label for="photo_user">
                                Photo de profil
                            </label>

                            <small>
                                JPG, JPEG, PNG ou WEBP — 5 Mo maximum
                            </small>

                            <input
                                class="photo-input"
                                type="file"
                                name="photo_user"
                                id="photo_user"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                        </div>

                    </div>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="prenom_user">
                                Prénom <span class="required">*</span>
                            </label>

                            <div class="input-box">

                                <input
                                    type="text"
                                    name="prenom_user"
                                    id="prenom_user"
                                    placeholder="Votre prénom"
                                    value="<?= htmlspecialchars($_POST['prenom_user'] ?? '') ?>"
                                    required
                                >

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="nom_user">
                                Nom <span class="required">*</span>
                            </label>

                            <div class="input-box">

                                <input
                                    type="text"
                                    name="nom_user"
                                    id="nom_user"
                                    placeholder="Votre nom"
                                    value="<?= htmlspecialchars($_POST['nom_user'] ?? '') ?>"
                                    required
                                >

                            </div>

                        </div>

                    </div>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="email_user">
                                Adresse e-mail <span class="required">*</span>
                            </label>

                            <div class="input-box">

                                <input
                                    type="email"
                                    name="email_user"
                                    id="email_user"
                                    placeholder="exemple@email.com"
                                    value="<?= htmlspecialchars($_POST['email_user'] ?? '') ?>"
                                    required
                                >

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="tel_user">
                                Téléphone
                            </label>

                            <div class="input-box">

                                <input
                                    type="tel"
                                    name="tel_user"
                                    id="tel_user"
                                    placeholder="Votre numéro"
                                    value="<?= htmlspecialchars($_POST['tel_user'] ?? '') ?>"
                                >

                            </div>

                        </div>

                    </div>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="motpass_user">
                                Mot de passe <span class="required">*</span>
                            </label>

                            <div class="input-box password-box">

                                <input
                                    type="password"
                                    name="motpass_user"
                                    id="motpass_user"
                                    placeholder="Minimum 8 caractères"
                                    required
                                >

                                <span
                                    class="toggle-password"
                                    onclick="togglePassword('motpass_user', this)"
                                >
                                    👁
                                </span>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="confirmation_motpass">
                                Confirmation <span class="required">*</span>
                            </label>

                            <div class="input-box password-box">

                                <input
                                    type="password"
                                    name="confirmation_motpass"
                                    id="confirmation_motpass"
                                    placeholder="Confirmer le mot de passe"
                                    required
                                >

                                <span
                                    class="toggle-password"
                                    onclick="togglePassword('confirmation_motpass', this)"
                                >
                                    👁
                                </span>

                            </div>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn-inscription"
                    >
                        Créer le compte administrateur
                    </button>

                </form>

                <div class="bottom-text">

                    Vous avez déjà un compte ?

                    <a href="controle.php">
                        Se connecter
                    </a>

                </div>

            </div>

        </div>

        <script>
            function togglePassword(id, element) {
                const input = document.getElementById(id);

                if (input.type === 'password') {
                    input.type = 'text';
                    element.textContent = '🙈';
                } else {
                    input.type = 'password';
                    element.textContent = '👁';
                }
            }

            const photoInput = document.getElementById('photo_user');
            const photoPreview = document.getElementById('photoPreview');
            const photoIcon = document.getElementById('photoIcon');

            photoInput.addEventListener('change', function () {
                const file = this.files[0];

                if (file) {
                    const reader = new FileReader();

                    reader.onload = function (event) {
                        photoPreview.src = event.target.result;

                        photoPreview.style.display = 'block';

                        photoIcon.style.display = 'none';
                    };

                    reader.readAsDataURL(file);
                } else {
                    photoPreview.style.display = 'none';

                    photoIcon.style.display = 'block';
                }
            });
        </script>

    </body>

</html>
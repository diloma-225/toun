<?php
session_start();

require_once 'config/connexion.php';

$message = '';
$success = isset($_GET['inscription']) && $_GET['inscription'] === 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email_user'] ?? ''));
    $motpass = $_POST['motpass_user'] ?? '';

    if ($email === '' || $motpass === '') {
        $message = 'Veuillez renseigner vos identifiants.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse e-mail invalide.';
    } else {
        try {
            $req = $con->prepare("
                SELECT *
                FROM t_utilisateurs
                WHERE email_user = ?
                LIMIT 1
            ");

            $req->execute([$email]);

            $utilisateur = $req->fetch(PDO::FETCH_ASSOC);

            if (!$utilisateur || !password_verify($motpass, $utilisateur['motpass_user'] ?? '')) {
                $message = 'E-mail ou mot de passe incorrect.';
            } elseif (($utilisateur['statut_user'] ?? '') !== 'actif') {
                $message = "Votre compte n'est pas actif.";
            } else {
                session_regenerate_id(true);

                $_SESSION['id_user'] = (int) $utilisateur['id_user'];
                $_SESSION['nom_user'] = $utilisateur['nom_user'] ?? '';
                $_SESSION['prenom_user'] = $utilisateur['prenom_user'] ?? '';
                $_SESSION['email_user'] = $utilisateur['email_user'] ?? '';
                $_SESSION['tel_user'] = $utilisateur['tel_user'] ?? '';
                $_SESSION['photo_user'] = $utilisateur['photo_user'] ?? '';
                $_SESSION['role_user'] = $utilisateur['role_user'] ?? '';

                switch ($_SESSION['role_user']) {
                    case 'admin':
                        header('Location: admin/dashbord.php');
                        exit();

                    case 'formateur':
                        header('Location: formateur/dashboard.php');
                        exit();

                    case 'gest_entreprise':
                        header('Location: entreprise/dashboard_entreprise.php');
                        exit();

                    case 'apprenant':
                        header('Location: apprenant/dashboard.php');
                        exit();

                    default:
                        session_unset();
                        session_destroy();

                        $message = 'Rôle utilisateur non reconnu.';
                        break;
                }
            }
        } catch (PDOException $e) {
            $message = 'Impossible de se connecter à la base de données.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Se connecter | TOUN</title>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                font-family: Arial, Helvetica, sans-serif;
                background: linear-gradient(135deg, #3f68ae 0%, #20a1b8 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 30px;
            }

            .login-container {
                width: 100%;
                max-width: 430px;
            }

            .login-card {
                background: #ffffff;
                border-radius: 22px;
                padding: 35px;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
            }

            .logo {
                text-align: center;
                margin-bottom: 25px;
            }

            .logo img {
                width: 145px;
                height: auto;
                object-fit: contain;
            }

            .login-title {
                text-align: center;
                margin-bottom: 8px;
                color: #18213f;
                font-size: 25px;
                font-weight: 700;
            }

            .login-subtitle {
                text-align: center;
                color: #777;
                font-size: 13px;
                margin-bottom: 25px;
            }

            .success-message,
            .error-message {
                padding: 12px 14px;
                border-radius: 10px;
                font-size: 13px;
                margin-bottom: 18px;
                display: flex;
                align-items: center;
                gap: 9px;
            }

            .success-message {
                background: #e8f8f1;
                color: #15805b;
                border: 1px solid #c5eadb;
            }

            .error-message {
                background: #fff0f0;
                color: #c0392b;
                border: 1px solid #f1cccc;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #26324d;
                font-size: 13px;
                font-weight: 600;
            }

            .input-wrapper {
                position: relative;
            }

            .input-wrapper i {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #20a1b8;
                font-size: 14px;
            }

            .input-wrapper input {
                width: 100%;
                height: 48px;
                padding: 0 15px 0 43px;
                border: 1px solid #dce2eb;
                border-radius: 11px;
                outline: none;
                font-size: 13px;
                color: #26324d;
                transition: 0.2s ease;
            }

            .input-wrapper input:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.1);
            }

            .login-button {
                width: 100%;
                height: 48px;
                border: none;
                border-radius: 11px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #ffffff;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                transition: 0.2s ease;
                margin-top: 5px;
            }

            .login-button:hover {
                transform: translateY(-1px);
                box-shadow: 0 8px 20px rgba(32, 161, 184, 0.25);
            }

            .register-text {
                text-align: center;
                margin-top: 22px;
                color: #777;
                font-size: 13px;
            }

            .register-text a {
                color: #20a1b8;
                font-weight: 700;
                text-decoration: none;
            }

            .register-text a:hover {
                text-decoration: underline;
            }

            .back-home {
                text-align: center;
                margin-top: 18px;
            }

            .back-home a {
                color: rgba(255, 255, 255, 0.9);
                font-size: 12.5px;
                text-decoration: none;
            }

            .back-home a:hover {
                color: #ffffff;
            }

            @media (max-width: 500px) {
                body {
                    padding: 18px;
                }

                .login-card {
                    padding: 28px 22px;
                }

                .login-title {
                    font-size: 22px;
                }
            }
        </style>
    </head>

    <body>

        <div class="login-container">

            <div class="login-card">

                <div class="logo">
                    <img src="images/logo.png" alt="Logo TOUN">
                </div>

                <h1 class="login-title">Bienvenue sur TOUN</h1>

                <p class="login-subtitle">
                    Connectez-vous à votre espace de formation
                </p>

                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-circle-check"></i>
                        <span>Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.</span>
                    </div>
                <?php endif; ?>

                <?php if ($message !== ""): ?>
                    <div class="error-message">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">

                    <div class="form-group">

                        <label for="email_user">
                            Adresse e-mail
                        </label>

                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>

                            <input
                                type="email"
                                id="email_user"
                                name="email_user"
                                placeholder="exemple@email.com"
                                value="<?= htmlspecialchars($_POST['email_user'] ?? '') ?>"
                                required
                            >
                        </div>

                    </div>

                    <div class="form-group">

                        <label for="motpass_user">
                            Mot de passe
                        </label>

                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>

                            <input
                                type="password"
                                id="motpass_user"
                                name="motpass_user"
                                placeholder="Votre mot de passe"
                                required
                            >
                        </div>

                    </div>

                    <button type="submit" class="login-button">
                        <i class="fas fa-right-to-bracket"></i>
                        Se connecter
                    </button>

                </form>

                <div class="register-text">
                    Vous n'avez pas encore de compte ?
                    <a href="inscription.php">Créer un compte</a>
                </div>

            </div>

            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                    Retour à l'accueil
                </a>
            </div>

        </div>

    </body>

</html>
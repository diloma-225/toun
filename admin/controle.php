<?php
session_start();

require_once '../config/connexion.php';

$message = '';
if (isset($_GET['inscription']) && $_GET['inscription'] == 'success') {
    $message = 'Inscription réussie. Vous pouvez maintenant vous connecter.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email_user'] ?? '');
    $motpass = $_POST['motpass_user'] ?? '';

    if (empty($email) || empty($motpass)) {
        $message = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse e-mail invalide.';
    } else {
        $sql = "SELECT *
                FROM t_utilisateurs
                WHERE email_user = ?";

        $req = $con->prepare($sql);
        $req->execute([$email]);

        $utilisateur = $req->fetch();

        if (!$utilisateur) {
            $message = 'Adresse e-mail ou mot de passe incorrect.';
        } elseif (!password_verify($motpass, $utilisateur['motpass_user'])) {
            $message = 'Adresse e-mail ou mot de passe incorrect.';
        } elseif ($utilisateur['statut_user'] != 'actif') {
            $message = "Votre compte n'est pas actif.";
        } else {
            session_regenerate_id(true);

            $_SESSION['id_user'] = $utilisateur['id_user'];
            $_SESSION['nom_user'] = $utilisateur['nom_user'];
            $_SESSION['prenom_user'] = $utilisateur['prenom_user'];
            $_SESSION['email_user'] = $utilisateur['email_user'];
            $_SESSION['role_user'] = $utilisateur['role_user'];
            $_SESSION['photo_user'] = $utilisateur['photo_user'];

            if ($utilisateur['role_user'] == 'admin') {
                header('Location: dashbord.php');
                exit();
            } elseif ($utilisateur['role_user'] == 'formateur') {
                header('Location: ../formateur/dashboard.php');
                exit();
            } elseif ($utilisateur['role_user'] == 'gest_entreprise') {
                header('Location: ../entreprise/dashboard.php');
                exit();
            } elseif ($utilisateur['role_user'] == 'apprenant') {
                header('Location: ../apprenant/dashboard.php');
                exit();
            } else {
                $message = 'Rôle utilisateur non reconnu.';
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

        <title>Connexion | TOUN</title>

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
                background: linear-gradient( 115deg, #ffffff 0%, #faffff 18%, #e8fafa 38%, #d9f5f6 50%, #eefbfb 65%, #ffffff 82%, #f7ffff 100% );
                background-size: 300% 300%;
                animation: backgroundMove 14s ease-in-out infinite;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                color: #18213f;
                overflow: hidden;
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

            .login-card {
                position: relative;
                z-index: 2;
                width: 100%;
                max-width: 780px;
                min-height: 460px;
                display: grid;
                grid-template-columns: 43% 57%;
                background:
                    linear-gradient(#ffffff, #ffffff) padding-box,
                    linear-gradient(135deg, #3f68ae, #20a1b8, rgba(63, 104, 174, 0.2)) border-box;
                border: 1px solid transparent;
                border-radius: 18px;
                box-shadow: 0 25px 70px rgba(24, 33, 63, 0.13);
                overflow: hidden;
            }

            .login-card::before {
                content: "";
                position: absolute;
                top: 0;
                left: 28px;
                width: 65px;
                height: 3px;
                background: linear-gradient(90deg, #3f68ae, #20a1b8);
                border-radius: 0 0 5px 5px;
                z-index: 5;
            }

            .login-card::after {
                content: "";
                position: absolute;
                right: 20px;
                bottom: 18px;
                width: 35px;
                height: 35px;
                border-right: 2px solid rgba(32, 161, 184, 0.25);
                border-bottom: 2px solid rgba(32, 161, 184, 0.25);
                border-radius: 0 0 7px 0;
                z-index: 5;
            }

            .image-side {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 25px;
                background: linear-gradient(145deg, #f8fbfd, #edf6fa);
                overflow: hidden;
            }

            .image-side::before {
                content: "";
                position: absolute;
                width: 190px;
                height: 190px;
                border: 1px solid rgba(63, 104, 174, 0.1);
                border-radius: 50%;
                top: -85px;
                left: -80px;
            }

            .image-side::after {
                content: "";
                position: absolute;
                width: 130px;
                height: 130px;
                border: 1px solid rgba(32, 161, 184, 0.13);
                border-radius: 50%;
                bottom: -65px;
                right: -50px;
            }

            .image-content {
                position: relative;
                z-index: 2;
                width: 100%;
                text-align: center;
            }

            .image-box {
                width: 245px;
                height: 260px;
                margin: 0 auto 8px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .image-box img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
            }

            .image-title {
                text-align: center;
            }

            .image-title h2 {
                color: #18213f;
                font-size: 15px;
                line-height: 1.4;
                font-weight: 600;
                margin-bottom: 4px;
            }

            .image-title p {
                max-width: 230px;
                margin: auto;
                color: #7f8997;
                font-size: 11px;
                line-height: 1.6;
            }

            .form-side {
                position: relative;
                display: flex;
                align-items: center;
                padding: 35px 48px;
                background:
                    radial-gradient(circle at 100% 0%, rgba(32, 161, 184, 0.2), transparent 35%),
                    radial-gradient(circle at 0% 100%, rgba(63, 104, 174, 0.18), transparent 40%),
                    linear-gradient(145deg, #18213f 0%, #20365f 55%, #174f68 100%);
                overflow: hidden;
            }

            .form-side::before {
                content: "";
                position: absolute;
                width: 180px;
                height: 180px;
                border-radius: 50%;
                right: -100px;
                top: -90px;
                border: 1px solid rgba(255, 255, 255, 0.08);
            }

            .form-side::after {
                content: "";
                position: absolute;
                width: 100px;
                height: 100px;
                border-radius: 50%;
                left: -60px;
                bottom: -45px;
                background: rgba(32, 161, 184, 0.08);
            }

            .form-content {
                width: 100%;
                max-width: 320px;
                margin: auto;
                position: relative;
                z-index: 2;
            }

            .login-header {
                margin-bottom: 25px;
                text-align: center;
            }

            .login-header h1 {
                color: #ffffff;
                font-size: 25px;
                line-height: 1.3;
                font-weight: 600;
                margin-bottom: 7px;
            }

            .login-header p {
                color: rgba(255, 255, 255, 0.75);
                font-size: 11.5px;
                font-weight: 400;
                line-height: 1.7;
            }

            .message {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 9px 11px;
                margin-bottom: 16px;
                border-radius: 6px;
                background: #fff5f4;
                border: 1px solid #f0d7d4;
                color: #d6453d;
                font-size: 11px;
            }

            .message-icon {
                width: 17px;
                height: 17px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: #d6453d;
                color: #ffffff;
                font-size: 11px;
                font-weight: 600;
            }

            .form-group {
                margin-bottom: 19px;
            }

            .form-label {
                display: block;
                margin-bottom: 6px;
                color: #ffffff;
                font-size: 11.5px;
                font-weight: 500;
            }

            .required {
                color: #ff5b5b;
                font-weight: 700;
            }

            .password-line {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 6px;
            }

            .password-line .form-label {
                margin-bottom: 0;
            }

            .forgot-password {
                color: rgba(255, 255, 255, 0.7);
                font-size: 10.5px;
                font-weight: 500;
                text-decoration: none;
                transition: 0.2s;
            }

            .forgot-password:hover {
                color: #20a1b8;
            }

            .input-wrapper {
                position: relative;
                height: 43px;
                display: flex;
                align-items: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.55);
                transition: 0.25s ease;
            }

            .input-wrapper:hover {
                border-bottom-color: rgba(255, 255, 255, 0.85);
            }

            .input-wrapper:focus-within {
                border-bottom-color: #20a1b8;
            }

            .input-icon {
                width: 27px;
                height: 100%;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }

            .input-icon svg {
                width: 15px;
                height: 15px;
                fill: none;
                stroke: rgba(255, 255, 255, 0.65);
                stroke-width: 1.5;
                stroke-linecap: round;
                stroke-linejoin: round;
                transition: 0.2s;
            }

            .input-wrapper:focus-within .input-icon svg {
                stroke: #20a1b8;
            }

            .input-wrapper input {
                flex: 1;
                width: 100%;
                height: 100%;
                border: none;
                outline: none;
                background: transparent;
                color: #ffffff;
                font-family: "Poppins", sans-serif;
                font-size: 11.5px;
            }

            .input-wrapper input::placeholder {
                color: rgba(255, 255, 255, 0.5);
            }

            .password-toggle {
                width: 27px;
                height: 100%;
                border: none;
                background: transparent;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                cursor: pointer;
            }

            .password-toggle svg {
                width: 15px;
                height: 15px;
                fill: none;
                stroke: rgba(255, 255, 255, 0.65);
                stroke-width: 1.5;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            .password-toggle:hover svg {
                stroke: #20a1b8;
            }

            .form-options {
                margin: -1px 0 21px;
            }

            .remember {
                display: flex;
                align-items: center;
                gap: 7px;
                cursor: pointer;
                color: rgba(255, 255, 255, 0.7);
                font-size: 10.5px;
            }

            .remember input {
                width: 13px;
                height: 13px;
                accent-color: #20a1b8;
                cursor: pointer;
            }

            .submit-button {
                width: 100%;
                height: 43px;
                border: none;
                border-radius: 7px;
                background: linear-gradient(90deg, #3f68ae, #20a1b8);
                color: #ffffff;
                font-family: "Poppins", sans-serif;
                font-size: 11.5px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.25s ease;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }

            .submit-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 30px rgba(32, 161, 184, 0.25);
            }

            .register {
                margin-top: 20px;
                padding-top: 16px;
                border-top: 1px solid rgba(255, 255, 255, 0.12);
                text-align: center;
                color: rgba(255, 255, 255, 0.55);
                font-size: 10.5px;
            }

            .register a {
                margin-left: 3px;
                color: #20a1b8;
                font-weight: 600;
                text-decoration: none;
                transition: 0.2s;
            }

            .register a:hover {
                color: #ffffff;
            }

            @media (max-width: 760px) {
                .login-card {
                    max-width: 440px;
                    grid-template-columns: 1fr;
                }

                .image-side {
                    display: none;
                }

                .form-side {
                    min-height: 480px;
                    padding: 40px 45px;
                }

                .bubble-3,
                .bubble-4 {
                    display: none;
                }
            }

            @media (max-width: 480px) {
                body {
                    padding: 15px;
                }

                .login-card {
                    border-radius: 13px;
                }

                .form-side {
                    padding: 32px 25px;
                }

                .login-header h1 {
                    font-size: 23px;
                }

                .bubble-1,
                .bubble-2,
                .bubble-5,
                .bubble-6 {
                    opacity: 0.5;
                }
            }
        </style>

    </head>

    <body>

        <div class="background-shapes">

            <span class="bubble bubble-1"></span>

            <span class="bubble bubble-2"></span>

            <span class="bubble bubble-3"></span>

            <span class="bubble bubble-4"></span>

            <span class="bubble bubble-5"></span>

            <span class="bubble bubble-6"></span>

        </div>

        <main class="login-card">

            <section class="image-side">

                <div class="image-content">

                    <div class="image-box">

                        <img
                            src="../admin/images/img3.png"
                            alt="Formation TOUN"
                        >

                    </div>

                    <div class="image-title">

                        <h2>
                            Apprenez. Évoluez. Réussissez.
                        </h2>

                        <p>
                            Développez vos compétences et
                            avancez dans votre parcours professionnel.
                        </p>

                    </div>

                </div>

            </section>

            <section class="form-side">

                <div class="form-content">

                    <div class="login-header">

                        <h1>
                            Bienvenue sur TOUN
                        </h1>

                        <p>
                            Connectez-vous à votre espace
                            et poursuivez votre parcours.
                        </p>

                    </div>

                    <?php if ($message != "") { ?>

                        <div class="message">

                            <span class="message-icon">
                                !
                            </span>

                            <span>
                                <?php echo htmlspecialchars($message); ?>
                            </span>

                        </div>

                    <?php } ?>

                    <form
                        action=""
                        method="POST"
                    >

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="email_user"
                            >
                                Adresse e-mail
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <span class="input-icon">

                                    <svg viewBox="0 0 24 24">

                                        <rect
                                            x="3"
                                            y="5"
                                            width="18"
                                            height="14"
                                            rx="2"
                                        />

                                        <path
                                            d="m3 7 9 6 9-6"
                                        />

                                    </svg>

                                </span>

                                <input
                                    type="email"
                                    id="email_user"
                                    name="email_user"
                                    placeholder="Votre adresse e-mail"
                                    autocomplete="email"
                                    required
                                >

                            </div>

                        </div>

                        <div class="form-group">

                            <div class="password-line">

                                <label
                                    class="form-label"
                                    for="motpass_user"
                                >
                                    Mot de passe
                                    <span class="required">*</span>
                                </label>

                                <a
                                    href="#"
                                    class="forgot-password"
                                >
                                    Mot de passe oublié ?
                                </a>

                            </div>

                            <div class="input-wrapper">

                                <span class="input-icon">

                                    <svg viewBox="0 0 24 24">

                                        <rect
                                            x="4"
                                            y="10"
                                            width="16"
                                            height="10"
                                            rx="2"
                                        />

                                        <path
                                            d="M8 10V7a4 4 0 0 1 8 0v3"
                                        />

                                    </svg>

                                </span>

                                <input
                                    type="password"
                                    id="motpass_user"
                                    name="motpass_user"
                                    placeholder="Votre mot de passe"
                                    autocomplete="current-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    id="passwordToggle"
                                    aria-label="Afficher le mot de passe"
                                >

                                    <svg viewBox="0 0 24 24">

                                        <path
                                            d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"
                                        />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="2.5"
                                        />

                                    </svg>

                                </button>

                            </div>

                        </div>

                        <div class="form-options">

                            <label class="remember">

                                <input
                                    type="checkbox"
                                    name="remember"
                                >

                                <span>
                                    Se souvenir de moi
                                </span>

                            </label>

                        </div>

                        <button
                            type="submit"
                            class="submit-button"
                        >
                            Se connecter
                        </button>

                    </form>

                    <div class="register">

                        Vous n'avez pas encore de compte ?

                        <a href="inscription.php">
                            Créer un compte
                        </a>

                    </div>

                </div>

            </section>

        </main>

        <script>
            const passwordInput = document.getElementById('motpass_user');

            const passwordToggle = document.getElementById('passwordToggle');

            passwordToggle.addEventListener('click', function () {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';

                    passwordToggle.setAttribute('aria-label', 'Masquer le mot de passe');
                } else {
                    passwordInput.type = 'password';

                    passwordToggle.setAttribute('aria-label', 'Afficher le mot de passe');
                }
            });
        </script>

    </body>

</html>
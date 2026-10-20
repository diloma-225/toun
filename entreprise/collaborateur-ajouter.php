<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'collaborateur-ajouter.php';

$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $pass = $_POST['motpass'] ?? '';
    $confirm = $_POST['confirmation'] ?? '';

    if ($nom === '' || $prenom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Renseignez correctement le nom, le prénom et l’e-mail.';
    }

    if (strlen($pass) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }

    if ($pass !== $confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        try {
            $s = $con->prepare('SELECT id_user FROM t_utilisateurs WHERE email_user = ? LIMIT 1');
            $s->execute([$email]);
            if ($s->fetch()) {
                throw new RuntimeException('Cette adresse e-mail est déjà utilisée.');
            }

            $photo = uploadImage($_FILES['photo'] ?? null, 'uploads/profils', 'collab_');

            $con->beginTransaction();

            $s = $con->prepare('
                INSERT INTO t_utilisateurs
                    (nom_user, prenom_user, email_user, tel_user, photo_user, motpass_user, role_user, statut_user, date_inscrip_user)
                VALUES (?, ?, ?, ?, ?, ?, \'apprenant\', \'actif\', NOW())
            ');
            $s->execute([$nom, $prenom, $email, $tel, $photo, password_hash($pass, PASSWORD_DEFAULT)]);
            $uid = (int) $con->lastInsertId();

            $s = $con->prepare(
                "INSERT INTO t_entreprise_collaborateurs (id_entreprise, id_user, statut) VALUES (?, ?, 'actif')"
            );
            $s->execute([$idEntreprise, $uid]);

            $con->commit();

            flash('ok', 'Collaborateur ajouté avec succès.');
            header('Location: collaborateurs.php');
            exit();
        } catch (Throwable $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }

            if (!empty($photo ?? null)) {
                deleteStoredFile($photo);
            }

            $errors[] = $e->getMessage();
        }
    }
}

$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Ajouter un collaborateur — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .form-panel {
                max-width: 820px;
                margin: 20px auto;
                padding: 18px;
                border-color: #b9dfe5;
            }

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 11px;
            }

            .field {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .field label {
                font-size: 10px;
                font-weight: 700;
                color: #536d83;
            }

            .field input {
                height: 34px;
                width: 100%;
                border: 1px solid #cfe2e8;
                border-radius: 8px;
                background: #fbfdfe;
                padding: 0 10px;
                outline: 0;
                font: 10.5px Poppins;
                color: #294965;
            }

            .field input:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .photo-zone {
                display: flex;
                align-items: center;
                gap: 13px;
                padding: 12px;
                margin-bottom: 14px;
                border: 1px solid #d6e9ed;
                border-radius: 10px;
                background: linear-gradient(135deg, #f8fcfd, #eef9fa);
            }

            .photo-preview {
                width: 62px;
                height: 62px;
                border-radius: 14px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                display: grid;
                place-items: center;
                font-size: 18px;
                overflow: hidden;
            }

            .photo-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .photo-zone small {
                display: block;
                margin-top: 5px;
                color: #8a9ca9;
                font-size: 10px;
            }

            .photo-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 7px 9px;
                border-radius: 7px;
                background: #eaf8fa;
                color: #168b9a;
                font-size: 10px;
                font-weight: 700;
                cursor: pointer;
            }

            .form-actions {
                display: flex;
                justify-content: flex-end;
                gap: 7px;
                margin-top: 15px;
            }

            @media (max-width: 650px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">

                <div class="page-head">
                    <div>
                        <span class="eyebrow">ÉQUIPE</span>
                        <h1>Ajouter un collaborateur</h1>
                        <p>Créez son compte et ajoutez sa photo.</p>
                    </div>
                    <a class="btn btn-light" href="collaborateurs.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
                </div>

                <?php foreach ($errors as $error): ?>
                    <div class="alert err"><?= e($error) ?></div>
                <?php endforeach; ?>

                <section class="panel form-panel">
                    <form method="post" enctype="multipart/form-data">
                        <div class="photo-zone">
                            <div class="photo-preview" id="photoPreview">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <label class="photo-btn" for="photo"><i class="fa-solid fa-camera"></i> Choisir une photo</label>
                                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                                <small>JPG, PNG ou WEBP — 4 Mo maximum.</small>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label>Nom</label>
                                <input name="nom" value="<?= e($old['nom'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Prénom</label>
                                <input name="prenom" value="<?= e($old['prenom'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>E-mail</label>
                                <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Téléphone</label>
                                <input name="tel" value="<?= e($old['tel'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label>Mot de passe</label>
                                <input type="password" name="motpass" minlength="6" required>
                            </div>
                            <div class="field">
                                <label>Confirmation</label>
                                <input type="password" name="confirmation" minlength="6" required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a class="btn btn-light" href="collaborateurs.php">Annuler</a>
                            <button class="btn" type="submit"><i class="fa-solid fa-check"></i> Ajouter</button>
                        </div>
                    </form>
                </section>

                <script>
                    document.getElementById('photo')?.addEventListener('change', function () {
                        const f = this.files[0];
                        const p = document.getElementById('photoPreview');
                        if (f) {
                            const r = new FileReader();
                            r.onload = e => (p.innerHTML = '<img src="' + e.target.result + '" alt="">');
                            r.readAsDataURL(f);
                        }
                    });
                </script>

            </div>
        </main>
    </body>
</html>

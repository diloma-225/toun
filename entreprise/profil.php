<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'profil.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Mon profil — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .profile-panel {
                max-width: 820px;
                margin: 0 auto;
                padding: 18px;
                border-color: #b9dfe5;
            }

            .profile-top {
                display: flex;
                align-items: center;
                gap: 13px;
                padding: 12px;
                margin-bottom: 14px;
                border: 1px solid #d6e9ed;
                border-radius: 10px;
                background: linear-gradient(135deg, #f8fcfd, #eef9fa);
            }

            .profile-preview {
                width: 70px;
                height: 70px;
                border-radius: 17px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                display: grid;
                place-items: center;
                color: #fff;
                font-size: 20px;
                overflow: hidden;
            }

            .profile-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
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

            .profile-top small {
                display: block;
                margin-top: 5px;
                color: #8a9ca9;
                font-size: 10px;
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
                border: 1px solid #cfe2e8;
                border-radius: 8px;
                background: #fbfdfe;
                padding: 0 10px;
                outline: 0;
                font: 10.5px Poppins;
                color: #294965;
            }

            .form-actions {
                display: flex;
                justify-content: flex-end;
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

                <?php
$errors = [];
$success = null;
$st = $con->prepare('SELECT * FROM t_utilisateurs WHERE id_user=? LIMIT 1');
$st->execute([$userId]);
$user = $st->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    try {
        if (!$nom || !$prenom || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Vérifiez vos informations.');
        }
        $st = $con->prepare('SELECT id_user FROM t_utilisateurs WHERE email_user=? AND id_user<>? LIMIT 1');
        $st->execute([$email, $userId]);
        if ($st->fetch()) {
            throw new RuntimeException('Cette adresse e-mail est déjà utilisée.');
        }
        $photo = uploadImage($_FILES['photo'] ?? null, 'uploads/profils', 'profil_');
        if ($photo) {
            $st = $con->prepare(
                'UPDATE t_utilisateurs SET nom_user=?,prenom_user=?,email_user=?,tel_user=?,photo_user=? WHERE id_user=?'
            );
            $st->execute([$nom, $prenom, $email, $tel, $photo, $userId]);
            deleteStoredFile($user['photo_user'] ?? null);
        } else {
            $st = $con->prepare(
                'UPDATE t_utilisateurs SET nom_user=?,prenom_user=?,email_user=?,tel_user=? WHERE id_user=?'
            );
            $st->execute([$nom, $prenom, $email, $tel, $userId]);
        }
        $_SESSION['nom_user'] = $nom;
        $_SESSION['prenom_user'] = $prenom;
        $_SESSION['photo_user'] = $photo ?? ($user['photo_user'] ?? '');
        flash('ok', 'Profil mis à jour.');
        header('Location: profil.php');
        exit();
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
                <div class="page-head">
                    <div>
                        <span class="eyebrow">COMPTE</span>
                        <h1>Mon profil</h1>
                        <p>Modifiez vos informations personnelles et votre photo.</p>
                    </div>
                </div>
                <?php foreach ($errors as $error): ?>
                    <div class="alert err"><?= e($error) ?></div>
                <?php endforeach; ?>
                <?php if ($flash): ?>
                    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>
                <section class="panel profile-panel">
                    <form method="post" enctype="multipart/form-data">
                        <div class="profile-top">
                            <div class="profile-preview" id="profilePreview">
                                <?php if(!empty($user['photo_user'])): ?>
                                    <img src="<?= e('../' . ltrim(str_replace('\\', '/', $user['photo_user']), '/')) ?>" alt="">
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div><label class="photo-btn" for="photo"><i class="fa-solid fa-camera"></i> Changer la photo</label><input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden><small>JPG, PNG ou WEBP — 4 Mo maximum.</small></div>
                        </div>
                        <div class="form-grid">
                            <div class="field"><label>Nom</label><input name="nom" value="<?= e($user['nom_user'] ?? '') ?>" required></div>
                            <div class="field"><label>Prénom</label><input name="prenom" value="<?= e($user['prenom_user'] ?? '') ?>" required></div>
                            <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= e($user['email_user'] ?? '') ?>" required></div>
                            <div class="field"><label>Téléphone</label><input name="tel" value="<?= e($user['tel_user'] ?? '') ?>"></div>
                        </div>
                        <div class="form-actions"><button class="btn">Enregistrer</button></div>
                    </form>
                </section>
                <script>
                    document.getElementById('photo')?.addEventListener('change', function () {
                        const f = this.files[0],
                            p = document.getElementById('profilePreview');
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

<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'collaborateur-modifier.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$s = $con->prepare("
    SELECT ec.*, u.id_user, u.nom_user, u.prenom_user, u.email_user, u.tel_user, u.photo_user
    FROM t_entreprise_collaborateurs ec
    INNER JOIN t_utilisateurs u ON u.id_user = ec.id_user
    WHERE ec.id_collaborateur = ? AND ec.id_entreprise = ?
    LIMIT 1
");
$s->execute([$id, $idEntreprise]);
$r = $s->fetch();

if (!$r) {
    exit('Collaborateur introuvable.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['tel'] ?? '');

    if (!$nom || !$prenom || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vérifiez les informations.';
    }

    try {
        if (!$errors) {
            $s = $con->prepare('SELECT id_user FROM t_utilisateurs WHERE email_user = ? AND id_user <> ? LIMIT 1');
            $s->execute([$email, $r['id_user']]);

            if ($s->fetch()) {
                throw new RuntimeException('Cette adresse e-mail est déjà utilisée.');
            }

            $photo = uploadImage($_FILES['photo'] ?? null, 'uploads/profils', 'collab_');

            if ($photo) {
                $s = $con->prepare("
                    UPDATE t_utilisateurs
                    SET nom_user = ?, prenom_user = ?, email_user = ?, tel_user = ?, photo_user = ?
                    WHERE id_user = ?
                ");
                $s->execute([$nom, $prenom, $email, $tel, $photo, $r['id_user']]);
                deleteStoredFile($r['photo_user']);
            } else {
                $s = $con->prepare("
                    UPDATE t_utilisateurs
                    SET nom_user = ?, prenom_user = ?, email_user = ?, tel_user = ?
                    WHERE id_user = ?
                ");
                $s->execute([$nom, $prenom, $email, $tel, $r['id_user']]);
            }

            flash('ok', 'Collaborateur modifié avec succès.');
            header('Location: collaborateurs.php');
            exit();
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Modifier un collaborateur — TOUN</title>
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
                        <h1>Modifier le collaborateur</h1>
                        <p>Actualisez ses informations et sa photo.</p>
                    </div>
                    <a class="btn btn-light" href="collaborateurs.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
                </div>

                <?php foreach ($errors as $error): ?>
                    <div class="alert err"><?= e($error) ?></div>
                <?php endforeach; ?>

                <section class="panel form-panel">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $id ?>">

                        <div class="photo-zone">
                            <div class="photo-preview" id="photoPreview">
                                <?php if (!empty($r['photo_user'])): ?>
                                    <img src="<?= e('../' . ltrim(str_replace('\\', '/', $r['photo_user']), '/')) ?>" alt="">
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="photo-btn" for="photo"><i class="fa-solid fa-camera"></i> Changer la photo</label>
                                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                                <small>JPG, PNG ou WEBP — 4 Mo maximum.</small>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label>Nom</label>
                                <input name="nom" value="<?= e($r['nom_user']) ?>" required>
                            </div>
                            <div class="field">
                                <label>Prénom</label>
                                <input name="prenom" value="<?= e($r['prenom_user']) ?>" required>
                            </div>
                            <div class="field">
                                <label>E-mail</label>
                                <input type="email" name="email" value="<?= e($r['email_user']) ?>" required>
                            </div>
                            <div class="field">
                                <label>Téléphone</label>
                                <input name="tel" value="<?= e($r['tel_user']) ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <a class="btn btn-light" href="collaborateurs.php">Annuler</a>
                            <button class="btn">Enregistrer</button>
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

<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function uploadLogo(array $file, string $directory): string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Une erreur est survenue pendant l'envoi du logo.");
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Le logo ne doit pas dépasser 5 Mo.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Le logo doit être au format JPG, PNG ou WEBP.');
    }
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('Impossible de créer le dossier des logos.');
    }

    $filename = 'entreprise_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    if (
        !move_uploaded_file($file['tmp_name'], rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename)
    ) {
        throw new RuntimeException("Impossible d'enregistrer le logo.");
    }
    return $filename;
}

if (empty($_SESSION['csrf_entreprise'])) {
    $_SESSION['csrf_entreprise'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_entreprise'];

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_entreprise'] ?? '');
    $email = strtolower(trim($_POST['email_entreprise'] ?? ''));
    $tel = trim($_POST['tel_entreprise'] ?? '');
    $secteur = trim($_POST['sect_activite'] ?? '');
    $adresse = trim($_POST['ad_entreprise'] ?? '');
    $statut = trim($_POST['statut_entreprise'] ?? 'actif');
    $dateCreation = trim($_POST['date_creation_entreprise'] ?? date('Y-m-d'));
    $responsableNom = trim($_POST['responsable_nom'] ?? '');
    $responsablePrenom = trim($_POST['responsable_prenom'] ?? '');
    $responsableEmail = strtolower(trim($_POST['responsable_email'] ?? ''));
    $responsableTel = trim($_POST['responsable_tel'] ?? '');
    $responsableMotpass = $_POST['responsable_motpass'] ?? '';
    $responsableConfirmation = $_POST['responsable_confirmation'] ?? '';
    $logo = '';

    try {
        if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
            throw new RuntimeException('Session expirée. Veuillez actualiser la page.');
        }
        if ($nom === '') {
            throw new RuntimeException("Le nom de l'entreprise est obligatoire.");
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("L'adresse email de l'entreprise est invalide.");
        }
        if ($tel === '') {
            throw new RuntimeException("Le téléphone de l'entreprise est obligatoire.");
        }
        if ($secteur === '') {
            throw new RuntimeException("Le secteur d'activité est obligatoire.");
        }
        if ($adresse === '') {
            throw new RuntimeException("L'adresse est obligatoire.");
        }
        if (!in_array($statut, ['actif', 'inactif'], true)) {
            throw new RuntimeException('Le statut sélectionné est invalide.');
        }
        if ($dateCreation === '') {
            throw new RuntimeException('La date de création est obligatoire.');
        }
        if ($responsableNom === '' || $responsablePrenom === '') {
            throw new RuntimeException('Le nom et le prénom du responsable sont obligatoires.');
        }
        if (!filter_var($responsableEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("L'adresse email du responsable est invalide.");
        }
        if (strlen($responsableMotpass) < 8) {
            throw new RuntimeException('Le mot de passe du responsable doit contenir au moins 8 caractères.');
        }
        if ($responsableMotpass !== $responsableConfirmation) {
            throw new RuntimeException('Les deux mots de passe du responsable ne correspondent pas.');
        }

        $checkEntreprise = $con->prepare('SELECT id_entreprise FROM t_entreprises WHERE nom_entreprise = ? LIMIT 1');
        $checkEntreprise->execute([$nom]);
        if ($checkEntreprise->fetch()) {
            throw new RuntimeException('Cette entreprise existe déjà.');
        }

        $checkUser = $con->prepare('SELECT id_user FROM t_utilisateurs WHERE email_user = ? LIMIT 1');
        $checkUser->execute([$responsableEmail]);
        if ($checkUser->fetch()) {
            throw new RuntimeException('Cette adresse email est déjà utilisée par un compte utilisateur.');
        }

        $logo = uploadLogo($_FILES['logo_entreprise'] ?? [], __DIR__ . '/../uploads/entreprises');
        $con->beginTransaction();

        $hash = password_hash($responsableMotpass, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('Impossible de sécuriser le mot de passe.');
        }

        $stmtUser = $con->prepare(
            "INSERT INTO t_utilisateurs (nom_user, prenom_user, email_user, tel_user, photo_user, motpass_user, role_user, statut_user, date_inscrip_user) VALUES (:nom, :prenom, :email, :tel, NULL, :motpass, 'gest_entreprise', :statut, NOW())"
        );
        $stmtUser->execute([
            ':nom' => $responsableNom,
            ':prenom' => $responsablePrenom,
            ':email' => $responsableEmail,
            ':tel' => $responsableTel !== '' ? $responsableTel : null,
            ':motpass' => $hash,
            ':statut' => $statut === 'actif' ? 'actif' : 'inactif'
        ]);
        $responsableId = (int) $con->lastInsertId();

        $stmtEntreprise = $con->prepare(
            'INSERT INTO t_entreprises (id_user, nom_entreprise, email_entreprise, tel_entreprise, sect_activite, ad_entreprise, logo_entreprise, statut_entreprise, date_creation_entreprise) VALUES (:id_user, :nom, :email, :tel, :secteur, :adresse, :logo, :statut, :date_creation)'
        );
        $stmtEntreprise->execute([
            ':id_user' => $responsableId,
            ':nom' => $nom,
            ':email' => $email !== '' ? $email : null,
            ':tel' => $tel,
            ':secteur' => $secteur,
            ':adresse' => $adresse,
            ':logo' => $logo !== '' ? $logo : null,
            ':statut' => $statut,
            ':date_creation' => $dateCreation
        ]);

        $con->commit();
        $_SESSION['entreprise_success'] = "L'entreprise et le compte du responsable ont été créés avec succès.";
        header('Location: entreprises.php');
        exit();
    } catch (Throwable $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $erreur = $e->getMessage();
        if ($logo !== '') {
            $path = __DIR__ . '/../uploads/entreprises/' . $logo;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ajouter une entreprise — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: Inter, Arial, sans-serif;
                background: #eef4f7;
                color: #172b4d;
                min-height: 100vh;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            .main {
                width: calc(100% - 260px);
                margin-left: 260px;
                min-height: 100vh;
                padding-top: 62px;
            }

            .content {
                max-width: 900px;
                margin: 0 auto;
                padding: 12px 26px 20px;
            }

            .page-head {
                min-height: 72px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                margin-bottom: 10px;
                padding: 12px 18px;
                border-radius: 15px;
                background: linear-gradient(120deg, #173e73 0%, #246ca2 55%, #20a1b8 100%);
                color: #fff;
                box-shadow: 0 12px 28px rgba(30, 75, 115, 0.16);
            }

            .page-head .eyebrow {
                margin-bottom: 4px;
                color: rgba(255, 255, 255, 0.7);
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 1.5px;
                text-transform: uppercase;
            }

            .page-head h1 {
                margin-bottom: 3px;
                font-size: 21px;
                line-height: 1.2;
            }

            .page-head p {
                color: rgba(255, 255, 255, 0.82);
                font-size: 11px;
            }

            .back {
                height: 32px;
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 0 12px;
                border: 1px solid rgba(255, 255, 255, 0.22);
                border-radius: 9px;
                background: rgba(255, 255, 255, 0.13);
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                white-space: nowrap;
            }

            .alert {
                max-width: 650px;
                margin: 0 auto 8px;
                padding: 8px 10px;
                border-radius: 8px;
                font-size: 11px;
            }

            .alert.error {
                border: 1px solid #f1c8ce;
                background: #fff1f3;
                color: #a63b4a;
            }

            .form-card {
                width: 100%;
                max-width: 750px;
                margin: 0 auto;
                padding: 14px 18px 12px;
                border: 1px solid #dbe6ed;
                border-radius: 15px;
                background: #fff;
                box-shadow: 0 8px 22px rgba(24, 33, 63, 0.08);
            }

            .form-top {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 12px;
                padding-bottom: 10px;
                border-bottom: 1px solid #e9eff3;
            }

            .form-icon {
                width: 35px;
                height: 35px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                font-size: 13px;
                box-shadow: 0 5px 12px rgba(32, 161, 184, 0.18);
            }

            .form-top h2 {
                margin-bottom: 2px;
                font-size: 12.5px;
                color: #19385f;
            }

            .form-top p {
                font-size: 10.5px;
                color: #8b9aaa;
            }

            .form-row {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 7px;
            }

            .form-row:last-child {
                margin-bottom: 0;
            }

            .field {
                min-width: 0;
            }

            .field.full {
                grid-column: 1/-1;
            }

            .field label {
                display: block;
                margin-bottom: 3px;
                color: #263e5b;
                font-size: 11px;
                font-weight: 700;
            }

            .required {
                color: #e35b68;
            }

            .field input,
            .field select {
                width: 100%;
                height: 31px;
                padding: 0 9px;
                border: 1px solid #d7e2e9;
                border-radius: 7px;
                outline: 0;
                background: #f9fbfc;
                color: #1e3552;
                font-family: inherit;
                font-size: 11px;
                transition: 0.2s;
            }

            .field input:focus,
            .field select:focus {
                border-color: #20a1b8;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .logo-box {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 6px;
                border: 1px dashed #b8d2dd;
                border-radius: 8px;
                background: #f7fbfc;
            }

            .logo-preview {
                width: 38px;
                height: 38px;
                flex: 0 0 38px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border-radius: 7px;
                background: #eaf4f7;
                color: #5d8baa;
                font-size: 12.5px;
            }

            .logo-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .logo-info {
                flex: 1;
            }

            .logo-info strong {
                display: block;
                color: #415a73;
                font-size: 11px;
            }

            .logo-info span {
                font-size: 10px;
                color: #8999a8;
            }

            .logo-btn {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                height: 27px;
                padding: 0 9px;
                border-radius: 7px;
                background: #edf5f8;
                color: #3f68ae;
                font-size: 10.5px;
                font-weight: 700;
                cursor: pointer;
            }

            .logo-btn input {
                position: absolute;
                inset: 0;
                opacity: 0;
                cursor: pointer;
            }

            .actions {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                margin-top: 9px;
                padding-top: 9px;
                border-top: 1px solid #edf1f4;
            }

            .btn {
                min-width: 125px;
                height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 0 14px;
                border-radius: 8px;
                font-family: inherit;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
                transition: 0.2s;
            }

            .btn:hover {
                transform: translateY(-1px);
            }

            .btn-secondary {
                border: 1px solid #d6e1e7;
                background: #f5f8fa;
                color: #536a80;
            }

            .btn-primary {
                border: 1px solid #20a1b8;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                box-shadow: 0 5px 12px rgba(32, 161, 184, 0.2);
            }

            @media (max-width: 800px) {
                .main {
                    width: calc(100% - 70px);
                    margin-left: 70px;
                }

                .content {
                    padding: 10px 15px;
                }

                .page-head {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .form-row {
                    grid-template-columns: 1fr;
                }

                .field.full {
                    grid-column: auto;
                }

                .actions {
                    justify-content: center;
                }

                .btn {
                    min-width: 120px;
                }
            }
        </style>
    </head>
    <body>
        <?php
$active_page = 'entreprises.php';
include 'menu.php';
?>
        <main class="main">
            <section class="content">
                <div class="page-head">
                    <div>
                        <div class="eyebrow">GESTION DES ENTREPRISES</div>
                        <h1>Ajouter une entreprise</h1>
                        <p>Renseignez les informations nécessaires à la création.</p>
                    </div>
                    <a class="back" href="entreprises.php"><i class="fa-solid fa-arrow-left"></i> Retour aux entreprises</a>
                </div>

                <?php if ($erreur !== ''): ?>
                    <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($erreur) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <div class="form-card">
                        <div class="form-row">
                            <div class="field">
                                <label for="nom_entreprise">Nom de l'entreprise <span class="required">*</span></label>
                                <input type="text" id="nom_entreprise" name="nom_entreprise" value="<?= e($_POST['nom_entreprise'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label for="sect_activite">Secteur d'activité <span class="required">*</span></label>
                                <input type="text" id="sect_activite" name="sect_activite" value="<?= e($_POST['sect_activite'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label for="email_entreprise">Email</label>
                                <input type="email" id="email_entreprise" name="email_entreprise" value="<?= e($_POST['email_entreprise'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label for="tel_entreprise">Téléphone <span class="required">*</span></label>
                                <input type="text" id="tel_entreprise" name="tel_entreprise" value="<?= e($_POST['tel_entreprise'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field full">
                                <label for="ad_entreprise">Adresse <span class="required">*</span></label>
                                <input type="text" id="ad_entreprise" name="ad_entreprise" value="<?= e($_POST['ad_entreprise'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label for="date_creation_entreprise">Date de création <span class="required">*</span></label>
                                <input type="date" id="date_creation_entreprise" name="date_creation_entreprise" value="<?= e($_POST['date_creation_entreprise'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div class="field">
                                <label for="statut_entreprise">Statut <span class="required">*</span></label>
                                <select id="statut_entreprise" name="statut_entreprise" required>
                                    <option value="actif" <?= ($_POST['statut_entreprise'] ?? 'actif') === 'actif' ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactif" <?= ($_POST['statut_entreprise'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label for="responsable_nom">Nom du responsable <span class="required">*</span></label>
                                <input type="text" id="responsable_nom" name="responsable_nom" value="<?= e($_POST['responsable_nom'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label for="responsable_prenom">Prénom du responsable <span class="required">*</span></label>
                                <input type="text" id="responsable_prenom" name="responsable_prenom" value="<?= e($_POST['responsable_prenom'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label for="responsable_email">Email de connexion <span class="required">*</span></label>
                                <input type="email" id="responsable_email" name="responsable_email" value="<?= e($_POST['responsable_email'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label for="responsable_tel">Téléphone</label>
                                <input type="text" id="responsable_tel" name="responsable_tel" value="<?= e($_POST['responsable_tel'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label for="responsable_motpass">Mot de passe <span class="required">*</span></label>
                                <input type="password" id="responsable_motpass" name="responsable_motpass" minlength="8" required>
                            </div>
                            <div class="field">
                                <label for="responsable_confirmation">Confirmation <span class="required">*</span></label>
                                <input type="password" id="responsable_confirmation" name="responsable_confirmation" minlength="8" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="field full">
                                <label>Logo de l'entreprise</label>
                                <div class="logo-box">
                                    <div class="logo-preview" id="logoPreview"><i class="fa-solid fa-building"></i></div>
                                    <div class="logo-info"><strong>Ajouter le logo</strong><span>JPG, PNG ou WEBP — 5 Mo max.</span></div>
                                    <label class="logo-btn"><i class="fa-solid fa-cloud-arrow-up"></i> Choisir<input type="file" id="logo_entreprise" name="logo_entreprise" accept="image/jpeg,image/png,image/webp"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-secondary" href="entreprises.php"><i class="fa-solid fa-xmark"></i> Annuler</a>
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-building-circle-check"></i> Créer l'entreprise</button>
                    </div>
                </form>
            </section>
        </main>
        <script>
            const logoInput = document.getElementById('logo_entreprise'),
                logoPreview = document.getElementById('logoPreview');
            logoInput?.addEventListener('change', () => {
                const file = logoInput.files?.[0];
                if (!file) {
                    logoPreview.innerHTML = '<i class="fa-solid fa-building"></i>';
                    return;
                }
                const r = new FileReader();
                r.onload = e => (logoPreview.innerHTML = '<img src="' + e.target.result + '" alt="Aperçu du logo">');
                r.readAsDataURL(file);
            });
        </script>
    </body>
</html>

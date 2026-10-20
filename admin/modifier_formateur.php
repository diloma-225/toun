<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: formateurs.php');
    exit();
}

$stmt = $con->prepare("
    SELECT 
        f.*,
        u.nom_user,
        u.prenom_user,
        u.email_user,
        u.tel_user,
        u.photo_user,
        u.motpass_user
    FROM t_formateurs f
    INNER JOIN t_utilisateurs u ON u.id_user = f.id_user
    WHERE f.id_formateur = ?
");

$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    header('Location: formateurs.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_user'] ?? '');
    $prenom = trim($_POST['prenom_user'] ?? '');
    $email = trim($_POST['email_user'] ?? '');
    $tel = trim($_POST['tel_user'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $biographie = trim($_POST['biographie'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $diplome = trim($_POST['diplome'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');

    $statut = ($_POST['statut_formateur'] ?? 'actif') === 'inactif' ? 'inactif' : 'actif';

    $newPassword = $_POST['motpass_user'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '' || $tel === '' || $specialite === '') {
        $errors[] = 'Veuillez remplir tous les champs obligatoires.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail est invalide.";
    }

    $photoName = $data['photo_user'];
    $newPhoto = false;

    if (!empty($_FILES['photo_user']['name'])) {
        if ($_FILES['photo_user']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "La photo n'a pas pu être téléversée.";
        } else {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            $ext = strtolower(pathinfo($_FILES['photo_user']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Format de photo non autorisé.';
            }

            if ($_FILES['photo_user']['size'] > 4 * 1024 * 1024) {
                $errors[] = 'La photo ne doit pas dépasser 4 Mo.';
            }

            if (!$errors) {
                $dir = __DIR__ . '/../uploads/photos';

                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                $photoName = uniqid('formateur_', true) . '.' . $ext;

                if (!move_uploaded_file($_FILES['photo_user']['tmp_name'], $dir . '/' . $photoName)) {
                    $errors[] = "Impossible d'enregistrer la photo.";
                    $photoName = $data['photo_user'];
                } else {
                    $newPhoto = true;
                }
            }
        }
    }

    if (!$errors) {
        try {
            $con->beginTransaction();

            $check = $con->prepare("
                SELECT id_user
                FROM t_utilisateurs
                WHERE email_user = ?
                AND id_user <> ?
            ");

            $check->execute([$email, (int) $data['id_user']]);

            if ($check->fetch()) {
                throw new Exception('Cette adresse e-mail est déjà utilisée.');
            }

            $sql = "
                UPDATE t_utilisateurs
                SET
                    nom_user = ?,
                    prenom_user = ?,
                    email_user = ?,
                    tel_user = ?,
                    photo_user = ?,
                    statut_user = ?
            ";

            $params = [$nom, $prenom, $email, $tel, $photoName, $statut];

            if ($newPassword !== '') {
                if (strlen($newPassword) < 6) {
                    throw new Exception('Le nouveau mot de passe doit contenir au moins 6 caractères.');
                }

                $sql .= ', motpass_user = ?';
                $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $sql .= ' WHERE id_user = ?';
            $params[] = (int) $data['id_user'];

            $con->prepare($sql)->execute($params);

            $stmt = $con->prepare("
                UPDATE t_formateurs
                SET
                    specialite = ?,
                    biographie = ?,
                    experience = ?,
                    diplome = ?,
                    linkedin = ?,
                    statut_formateur = ?
                WHERE id_formateur = ?
            ");

            $stmt->execute([
                $specialite,
                $biographie ?: null,
                $experience ?: null,
                $diplome ?: null,
                $linkedin ?: null,
                $statut,
                $id
            ]);

            $con->commit();

            if ($newPhoto && !empty($data['photo_user'])) {
                $oldCandidates = [
                    __DIR__ . '/../uploads/photos/' . $data['photo_user'],
                    __DIR__ . '/../uploads/' . $data['photo_user']
                ];

                foreach ($oldCandidates as $old) {
                    if (is_file($old)) {
                        @unlink($old);
                    }
                }
            }

            $_SESSION['success_formateur'] = 'Le formateur a été modifié avec succès.';

            header('Location: formateurs.php');
            exit();
        } catch (Throwable $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }

            if ($newPhoto && is_file(__DIR__ . '/../uploads/photos/' . $photoName)) {
                @unlink(__DIR__ . '/../uploads/photos/' . $photoName);
            }

            $errors[] = $e->getMessage();
        }
    }

    $data = array_merge($data, [
        'nom_user' => $nom,
        'prenom_user' => $prenom,
        'email_user' => $email,
        'tel_user' => $tel,
        'specialite' => $specialite,
        'biographie' => $biographie,
        'experience' => $experience,
        'diplome' => $diplome,
        'linkedin' => $linkedin,
        'statut_formateur' => $statut,
        'photo_user' => $photoName
    ]);
}
?>

<!DOCTYPE html>
<html lang="fr">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Modifier un formateur - TOUN</title>

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        >

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: #eef4f8;
                font-family: "Poppins", sans-serif;
                color: #26384c;
            }

            .main {
                margin-left: 260px;
                padding: 90px 25px 35px;
                min-height: 100vh;
            }

            .page-title {
                width: 620px;
                max-width: 100%;
                margin: 0 auto 17px;
            }

            .page-title h1 {
                margin: 0;
                color: #183b66;
                font-size: 21px;
                font-weight: 700;
                line-height: 1.3;
            }

            .page-title p {
                margin: 4px 0 0;
                color: #7d8c9a;
                font-size: 12px;
                line-height: 1.5;
            }

            .form-container {
                width: 620px;
                max-width: 100%;
                margin: 0 auto;
                padding: 20px 22px 18px;
                background: #ffffff;
                border: 1px solid #d6e6ed;
                border-radius: 13px;
                box-shadow: 0 5px 18px rgba(63, 104, 174, 0.07);
            }

            .alert {
                margin-bottom: 14px;
                padding: 9px 11px;
                background: #fff1f3;
                border: 1px solid #f0c8ce;
                border-radius: 7px;
                color: #ad3f4c;
                font-size: 11px;
                line-height: 1.6;
            }

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 11px 13px;
            }

            .field label {
                display: block;
                margin-bottom: 5px;
                color: #52667a;
                font-size: 11px;
                font-weight: 600;
            }

            .required {
                color: #d9535f;
            }

            input,
            select,
            textarea {
                width: 100%;
                border: 1px solid #d8e3e9;
                border-radius: 7px;
                background: #fbfdfe;
                color: #26384c;
                font-family: "Poppins", sans-serif;
                font-size: 11px;
                outline: none;
                transition: 0.2s ease;
            }

            input,
            select {
                height: 34px;
                padding: 0 9px;
            }

            textarea {
                min-height: 72px;
                padding: 8px 9px;
                resize: vertical;
                line-height: 1.5;
            }

            input:focus,
            select:focus,
            textarea:focus {
                background: #ffffff;
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.07);
            }

            input::placeholder,
            textarea::placeholder {
                color: #a5b0ba;
            }

            .full {
                grid-column: 1 / -1;
            }

            .file-input {
                padding: 7px 8px;
                font-size: 11px;
            }

            .actions {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                gap: 8px;
                margin-top: 17px;
                padding-top: 14px;
                border-top: 1px solid #edf2f5;
            }

            .btn {
                height: 34px;
                padding: 0 15px;
                border-radius: 7px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                font-family: "Poppins", sans-serif;
                font-size: 11px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .cancel {
                color: #5e7184;
                background: #edf2f5;
                border: 1px solid #dce5ea;
            }

            .cancel:hover {
                background: #e4ebef;
            }

            .save {
                min-width: 140px;
                color: #ffffff;
                border: none;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                box-shadow: 0 4px 10px rgba(32, 161, 184, 0.18);
            }

            .save:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 13px rgba(63, 104, 174, 0.22);
            }

            @media (max-width: 850px) {
                .main {
                    margin-left: 0;
                    padding: 85px 16px 30px;
                }

                .page-title,
                .form-container {
                    width: 100%;
                }
            }

            @media (max-width: 600px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }

                .full {
                    grid-column: auto;
                }

                .form-container {
                    padding: 17px;
                }

                .actions {
                    justify-content: stretch;
                }

                .btn {
                    flex: 1;
                }
            }
        </style>

    </head>

    <body>

        <?php include 'menu.php'; ?>

        <main class="main">

            <div class="page-title">
                <h1>Modifier le formateur</h1>
                <p>
                    Mettez à jour les informations du formateur.
                </p>
            </div>

            <div class="form-container">

                <?php if ($errors): ?>

                    <div class="alert">

                        <?php foreach ($errors as $err): ?>

                            <div>
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <?= e($err) ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">

                    <div class="form-grid">

                        <div class="field">

                            <label>
                                Nom <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="nom_user"
                                required
                                value="<?= e($data['nom_user']) ?>"
                                placeholder="Nom"
                            >

                        </div>

                        <div class="field">

                            <label>
                                Prénom <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="prenom_user"
                                required
                                value="<?= e($data['prenom_user']) ?>"
                                placeholder="Prénom"
                            >

                        </div>

                        <div class="field">

                            <label>
                                Email <span class="required">*</span>
                            </label>

                            <input
                                type="email"
                                name="email_user"
                                required
                                value="<?= e($data['email_user']) ?>"
                                placeholder="exemple@email.com"
                            >

                        </div>

                        <div class="field">

                            <label>
                                Téléphone <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="tel_user"
                                required
                                value="<?= e($data['tel_user']) ?>"
                                placeholder="07 XX XX XX XX"
                            >

                        </div>

                        <div class="field">

                            <label>Nouveau mot de passe</label>

                            <input
                                type="password"
                                name="motpass_user"
                                placeholder="Laisser vide pour conserver"
                            >

                        </div>

                        <div class="field">

                            <label>Nouvelle photo</label>

                            <input
                                class="file-input"
                                type="file"
                                name="photo_user"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                        </div>

                        <div class="field">

                            <label>
                                Spécialité <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="specialite"
                                required
                                value="<?= e($data['specialite']) ?>"
                                placeholder="Ex. Ressources humaines"
                            >

                        </div>

                        <div class="field">

                            <label>Expérience</label>

                            <input
                                type="text"
                                name="experience"
                                value="<?= e($data['experience']) ?>"
                                placeholder="Ex. 5 ans"
                            >

                        </div>

                        <div class="field">

                            <label>Diplôme / qualification</label>

                            <input
                                type="text"
                                name="diplome"
                                value="<?= e($data['diplome']) ?>"
                                placeholder="Ex. Master"
                            >

                        </div>

                        <div class="field">

                            <label>
                                LinkedIn
                                <span style="font-weight:400;color:#9aa6b1">
                                    (facultatif)
                                </span>
                            </label>

                            <input
                                type="url"
                                name="linkedin"
                                value="<?= e($data['linkedin']) ?>"
                                placeholder="https://linkedin.com/..."
                            >

                        </div>

                        <div class="field">

                            <label>Statut</label>

                            <select name="statut_formateur">

                                <option
                                    value="actif"
                                    <?= $data['statut_formateur'] === 'actif' ? 'selected' : '' ?>
                                >
                                    Actif
                                </option>

                                <option
                                    value="inactif"
                                    <?= $data['statut_formateur'] === 'inactif' ? 'selected' : '' ?>
                                >
                                    Inactif
                                </option>

                            </select>

                        </div>

                        <div class="field full">

                            <label>Biographie</label>

                            <textarea
                                name="biographie"
                                placeholder="Présentez brièvement le parcours et l'expertise du formateur..."
                            ><?= e($data['biographie']) ?></textarea>

                        </div>

                    </div>

                    <div class="actions">

                        <a
                            href="formateurs.php"
                            class="btn cancel"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Annuler
                        </a>

                        <button
                            type="submit"
                            class="btn save"
                        >
                            <i class="fa-solid fa-check"></i>
                            Enregistrer
                        </button>

                    </div>

                </form>

            </div>

        </main>

    </body>
</html>
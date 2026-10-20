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

function tableExists(PDO $con, string $table): bool
{
    try {
        $stmt = $con->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function uploadFile(array $file, string $directory, array $allowedMimes, int $maxSize): string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException(
            'Le fichier dépasse la taille autorisée par le serveur (' . ini_get('upload_max_filesize') . ').'
        );
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Une erreur est survenue pendant l'envoi du fichier.");
    }

    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Le fichier est trop volumineux.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedMimes[$mime])) {
        throw new RuntimeException("Le type de fichier n'est pas autorisé.");
    }

    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException("Impossible de créer le dossier d'enregistrement.");
    }

    $extension = $allowedMimes[$mime];
    $filename = 'formation_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
    $destination = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException("Impossible d'enregistrer le fichier.");
    }

    return $filename;
}

$nom = $_SESSION['nom_user'] ?? '';
$prenom = $_SESSION['prenom_user'] ?? '';
$photo = $_SESSION['photo_user'] ?? '';
$photo_url = '';

if (!empty($photo)) {
    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');

    $candidats = [];

    if (preg_match('#^(https?:)?//#i', $photo)) {
        $photo_url = $photo;
    } else {
        $nom_photo = basename($photo);

        $candidats[] = [
            'fichier' => __DIR__ . '/' . $photo,
            'url' => $photo
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../uploads/profils/' . $nom_photo,
            'url' => '../uploads/profils/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../uploads/' . $nom_photo,
            'url' => '../uploads/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../assets/uploads/' . $nom_photo,
            'url' => '../assets/uploads/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../images/profils/' . $nom_photo,
            'url' => '../images/profils/' . rawurlencode($nom_photo)
        ];

        $candidats[] = [
            'fichier' => __DIR__ . '/../images/' . $nom_photo,
            'url' => '../images/' . rawurlencode($nom_photo)
        ];

        foreach ($candidats as $candidat) {
            if (is_file($candidat['fichier'])) {
                $photo_url = $candidat['url'];
                break;
            }
        }
    }
}

$categories = [];
$formateurs = [];
$erreur = '';
$succes = '';

if (tableExists($con, 't_categories')) {
    try {
        $categories = $con
            ->query('SELECT id_cat, nom_cat FROM t_categories ORDER BY nom_cat ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $categories = [];
    }
}

if (tableExists($con, 't_formateurs')) {
    try {
        $stmt = $con->query("SELECT fo.id_formateur, fo.id_user, u.nom_user, u.prenom_user
                             FROM t_formateurs fo
                             LEFT JOIN t_utilisateurs u ON u.id_user = fo.id_user
                             ORDER BY u.nom_user ASC, u.prenom_user ASC");
        $formateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $formateurs = [];
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($_POST) &&
    empty($_FILES) &&
    (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
) {
    $erreur =
        'Le fichier envoyé est trop volumineux pour le serveur (limite post_max_size dépassée). ' .
        "Réduisez la taille de l'image ou de la vidéo, ou contactez l'administrateur pour augmenter cette limite.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erreur === '') {
    $titreBrut = $_POST['titre_form'] ?? '';

    if (is_array($titreBrut)) {
        $titreBrut = '';
    }

    $titreBrut = (string) $titreBrut;
    $titre = preg_replace('/\\s+/u', ' ', trim($titreBrut));

    if ($titre === null) {
        $titre = trim($titreBrut);
    }

    $titre = trim($titre);

    $idCat = (int) ($_POST['id_cat'] ?? 0);
    $idFormateur = (int) ($_POST['id_formateur'] ?? 0);
    $description = trim($_POST['descrip_form'] ?? '');
    $niveau = trim($_POST['niveau_form'] ?? '');
    $prix = str_replace(',', '.', trim($_POST['prix_form'] ?? '0'));
    $duree = (int) ($_POST['duree_form'] ?? 0);
    $statut = trim($_POST['statut_form'] ?? 'brouillon');

    try {
        if ($titre === '') {
            throw new RuntimeException('Le titre de la formation est obligatoire.');
        }

        if ($idCat <= 0) {
            throw new RuntimeException('Veuillez sélectionner une catégorie.');
        }

        if ($idFormateur <= 0) {
            throw new RuntimeException('Veuillez sélectionner un formateur.');
        }

        if ($description === '') {
            throw new RuntimeException('La description de la formation est obligatoire.');
        }

        if (!is_numeric($prix) || (float) $prix < 0) {
            throw new RuntimeException('Le prix est invalide.');
        }

        if ($duree <= 0) {
            throw new RuntimeException('La durée doit être supérieure à 0 minute.');
        }

        $statutsAutorises = ['brouillon', 'publie', 'archive'];
        if (!in_array($statut, $statutsAutorises, true)) {
            throw new RuntimeException('Le statut sélectionné est invalide.');
        }

        $columns = $con->query('SHOW COLUMNS FROM t_formations')->fetchAll(PDO::FETCH_COLUMN);
        $hasVideo = in_array('video_intro_form', $columns, true);

        $imageName = uploadFile(
            $_FILES['img_form'] ?? [],
            __DIR__ . '/../uploads/formations',
            ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
            5 * 1024 * 1024
        );

        if ($imageName === '') {
            throw new RuntimeException("L'image de couverture est obligatoire.");
        }

        $videoName = '';
        if ($hasVideo && isset($_FILES['video_intro_form'])) {
            $videoName = uploadFile(
                $_FILES['video_intro_form'],
                __DIR__ . '/../uploads/formations/videos',
                [
                    'video/mp4' => 'mp4',
                    'video/x-m4v' => 'mp4',
                    'application/mp4' => 'mp4',
                    'video/webm' => 'webm',
                    'video/ogg' => 'ogg',
                    'video/quicktime' => 'mov'
                ],
                100 * 1024 * 1024
            );
        }

        if ($hasVideo) {
            $sql = "INSERT INTO t_formations
                    (id_cat, id_formateur, titre_form, descrip_form, img_form, video_intro_form, niveau_form, prix_form, duree_form, statut_form, date_creation_form)
                    VALUES (:id_cat, :id_formateur, :titre, :description, :image, :video, :niveau, :prix, :duree, :statut, NOW())";
        } else {
            $sql = "INSERT INTO t_formations
                    (id_cat, id_formateur, titre_form, descrip_form, img_form, niveau_form, prix_form, duree_form, statut_form, date_creation_form)
                    VALUES (:id_cat, :id_formateur, :titre, :description, :image, :niveau, :prix, :duree, :statut, NOW())";
        }

        $stmt = $con->prepare($sql);
        $params = [
            ':id_cat' => $idCat,
            ':id_formateur' => $idFormateur,
            ':titre' => $titre,
            ':description' => $description,
            ':image' => $imageName,
            ':niveau' => $niveau,
            ':prix' => (float) $prix,
            ':duree' => $duree,
            ':statut' => $statut
        ];

        if ($hasVideo) {
            $params[':video'] = $videoName !== '' ? $videoName : null;
        }

        $stmt->execute($params);

        $_SESSION['formation_success'] = 'La formation a été créée avec succès.';
        header('Location: formation.php');
        exit();
    } catch (Throwable $e) {
        $erreur = $e->getMessage();
    }
}

function initiales(string $prenom, string $nom): string
{
    $a = mb_substr(trim($prenom), 0, 1, 'UTF-8');
    $b = mb_substr(trim($nom), 0, 1, 'UTF-8');
    return mb_strtoupper($a . $b, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ajouter une formation — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            * {
                box-sizing: border-box;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                min-height: 100%;
                font-family: Inter, Arial, sans-serif;
                background: #eef4f8;
                color: #172b4d;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            .formation-main {
                margin-left: 260px;
                min-height: 100vh;
                padding-top: 62px;
            }

            .formation-content {
                width: 100%;
                max-width: 1120px;
                margin: 0 auto;
                padding: 14px 24px 28px;
            }

            .page-head {
                min-height: 72px;
                margin-bottom: 10px;
                padding: 12px 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                border-radius: 15px;
                background: linear-gradient(120deg, #173e73 0%, #246ca2 55%, #20a1b8 100%);
                color: #fff;
                box-shadow: 0 12px 28px rgba(30, 75, 115, 0.14);
            }

            .page-head h1 {
                margin: 0 0 3px;
                font-size: 21px;
                line-height: 1.2;
            }

            .page-head p {
                margin: 0;
                color: rgba(255, 255, 255, 0.84);
                font-size: 11px;
            }

            .back {
                height: 34px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                flex-shrink: 0;
                padding: 0 13px;
                border: 1px solid rgba(255, 255, 255, 0.25);
                border-radius: 9px;
                background: rgba(255, 255, 255, 0.13);
                color: #fff;
                font-size: 11px;
                font-weight: 700;
            }

            .back:hover {
                background: rgba(255, 255, 255, 0.21);
            }

            .alert {
                width: 100%;
                max-width: 750px;
                margin: 0 auto 10px;
                padding: 9px 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                border: 1px solid #f1c8ce;
                border-radius: 9px;
                background: #fff1f3;
                color: #a63b4a;
                font-size: 11px;
                line-height: 1.4;
            }

            .form {
                width: 100%;
            }

            .form-card {
                width: 100%;
                max-width: 750px;
                margin: 0 auto;
                padding: 15px 20px 14px;
                border: 1px solid #dbe6ed;
                border-radius: 15px;
                background: #fff;
                box-shadow: 0 8px 24px rgba(31, 73, 105, 0.08);
            }

            .form-section {
                margin-bottom: 7px;
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
            .field select,
            .field textarea {
                width: 100%;
                border: 1px solid #d7e2e9;
                border-radius: 7px;
                outline: 0;
                background: #f9fbfc;
                color: #1e3552;
                font-family: inherit;
                font-size: 11px;
                transition: 0.2s ease;
            }

            .field input,
            .field select {
                height: 31px;
                padding: 0 9px;
            }

            .field textarea {
                height: 55px;
                min-height: 55px;
                padding: 7px 9px;
                line-height: 1.45;
                resize: vertical;
            }

            .field input:hover,
            .field select:hover,
            .field textarea:hover {
                border-color: #b9d1dc;
            }

            .field input:focus,
            .field select:focus,
            .field textarea:focus {
                border-color: #20a1b8;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .hint {
                margin-top: 3px;
                color: #8999a8;
                font-size: 10px;
                line-height: 1.4;
            }

            .upload {
                padding: 5px;
                border: 1px dashed #b8d2dd;
                border-radius: 7px;
                background: #f7fbfc;
            }

            .upload input[type="file"] {
                width: 100%;
                height: 25px;
                padding: 2px;
                border: 0;
                border-radius: 6px;
                background: #fff;
                font-size: 10px;
            }

            .preview {
                display: none;
                height: 42px;
                margin-top: 4px;
                overflow: hidden;
                border-radius: 7px;
                background: #edf5f8;
            }

            .preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .video-preview {
                display: none;
                margin-top: 4px;
                padding: 5px 6px;
                overflow: hidden;
                border-radius: 7px;
                background: #edf7fa;
                color: #41637d;
                font-size: 10px;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .actions {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 9px;
                margin-top: 9px;
                padding-top: 9px;
                border-top: 1px solid #e7edf1;
            }

            .btn {
                min-width: 125px;
                height: 36px;
                padding: 0 15px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                border-radius: 9px;
                font-family: inherit;
                font-size: 11px;
                font-weight: 700;
                line-height: 1;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .btn:hover {
                transform: translateY(-1px);
            }

            .btn-secondary {
                border: 1px solid #cbdbe4;
                background: #f5f8fa;
                color: #536a80;
            }

            .btn-secondary:hover {
                background: #edf2f5;
            }

            .btn-primary {
                border: 1px solid #20a1b8;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                box-shadow: 0 5px 12px rgba(32, 161, 184, 0.2);
            }

            .btn-primary:hover {
                box-shadow: 0 7px 16px rgba(32, 161, 184, 0.27);
            }

            @media (max-width: 800px) {
                .formation-main {
                    margin-left: 70px;
                    padding-top: 62px;
                }

                .formation-content {
                    padding: 10px 14px 22px;
                }

                .page-head {
                    align-items: flex-start;
                    flex-direction: column;
                    min-height: 64px;
                    padding: 12px 15px;
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
            }

            @media (max-width: 720px) {
                .form-card {
                    padding: 14px;
                }

                .btn {
                    min-width: 120px;
                }
            }

            @media (max-height: 800px) and (min-width: 801px) {
                .formation-content {
                    padding-top: 7px;
                    padding-bottom: 14px;
                }

                .page-head {
                    min-height: 60px;
                    padding: 9px 15px;
                    margin-bottom: 7px;
                }

                .page-head h1 {
                    font-size: 19px;
                }

                .page-head p {
                    font-size: 10.5px;
                }

                .form-card {
                    padding: 11px 17px 10px;
                }

                .form-section {
                    margin-bottom: 5px;
                }

                .form-row {
                    gap: 7px;
                    margin-bottom: 5px;
                }

                .field label {
                    margin-bottom: 2px;
                }

                .field input,
                .field select {
                    height: 28px;
                }

                .field textarea {
                    height: 48px;
                    min-height: 48px;
                }

                .upload {
                    padding: 4px;
                }

                .upload input[type="file"] {
                    height: 23px;
                }

                .preview {
                    height: 36px;
                }

                .actions {
                    margin-top: 6px;
                    padding-top: 7px;
                }

                .btn {
                    height: 32px;
                    min-width: 112px;
                }
            }
        </style>
    </head>
    <body>

        <?php
$active_page = 'formation.php';
include 'menu.php';
?>

        <main class="formation-main">
            <section class="formation-content">

                <div class="page-head">
                    <div>
                        <h1>Ajouter une formation</h1>
                        <p>Créez la fiche de votre formation avant d’ajouter ses modules et ses leçons.</p>
                    </div>
                    <a class="back" href="formation.php">
                        <i class="fa-solid fa-arrow-left"></i>
                        Retour aux formations
                    </a>
                </div>

                <?php if ($erreur !== ''): ?>
                    <div class="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($erreur) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="" enctype="multipart/form-data" id="formationForm" class="form">

                    <div class="form-card">

                        <div class="form-section">
                            <div class="form-row">
                                <div class="field full">
                                    <label for="titre_form">Titre de la formation <span class="required">*</span></label>
                                    <input
                                        type="text"
                                        id="titre_form"
                                        name="titre_form"
                                        maxlength="255"
                                        autocomplete="off"
                                        value="<?= e((string) ($_POST['titre_form'] ?? '')) ?>"
                                        placeholder="Ex. Excel Professionnel"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field">
                                    <label for="id_cat">Catégorie <span class="required">*</span></label>
                                    <select id="id_cat" name="id_cat" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int) $cat['id_cat'] ?>"
                                                <?= (int) ($_POST['id_cat'] ?? 0) === (int) $cat['id_cat'] ? 'selected' : '' ?>>
                                                <?= e($cat['nom_cat']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!$categories): ?>
                                        <div class="hint">Aucune catégorie disponible.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="field">
                                    <label for="id_formateur">Formateur <span class="required">*</span></label>
                                    <select id="id_formateur" name="id_formateur" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($formateurs as $fo): ?>
                                            <option value="<?= (int) $fo['id_formateur'] ?>"
                                                <?= (int) ($_POST['id_formateur'] ?? 0) === (int) $fo['id_formateur'] ? 'selected' : '' ?>>
                                                <?= e(trim(($fo['prenom_user'] ?? '') . ' ' . ($fo['nom_user'] ?? ''))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!$formateurs): ?>
                                        <div class="hint">Aucun formateur disponible.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field full">
                                    <label for="descrip_form">Description <span class="required">*</span></label>
                                    <textarea
                                        id="descrip_form"
                                        name="descrip_form"
                                        maxlength="5000"
                                        placeholder="Présentez la formation."
                                        required><?= e($_POST['descrip_form'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-row">
                                <div class="field">
                                    <label for="niveau_form">Niveau <span class="required">*</span></label>
                                    <select id="niveau_form" name="niveau_form" required>
                                        <option value="">Choisir</option>
                                        <?php foreach (['Débutant','Intermédiaire','Avancé','Tous niveaux'] as $niv): ?>
                                            <option value="<?= e($niv) ?>"
                                                <?= ($_POST['niveau_form'] ?? '') === $niv ? 'selected' : '' ?>>
                                                <?= e($niv) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field">
                                    <label for="duree_form">Durée (min) <span class="required">*</span></label>
                                    <input
                                        type="number"
                                        id="duree_form"
                                        name="duree_form"
                                        min="1"
                                        step="1"
                                        value="<?= e($_POST['duree_form'] ?? '') ?>"
                                        placeholder="480"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field">
                                    <label for="prix_form">Prix (FCFA) <span class="required">*</span></label>
                                    <input
                                        type="number"
                                        id="prix_form"
                                        name="prix_form"
                                        min="0"
                                        step="1"
                                        value="<?= e($_POST['prix_form'] ?? '0') ?>"
                                        placeholder="50000"
                                        required
                                    >
                                </div>

                                <div class="field">
                                    <label for="statut_form">Statut <span class="required">*</span></label>
                                    <select id="statut_form" name="statut_form" required>
                                        <option value="brouillon" <?= ($_POST['statut_form'] ?? 'brouillon') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                                        <option value="publie" <?= ($_POST['statut_form'] ?? '') === 'publie' ? 'selected' : '' ?>>Publiée</option>
                                        <option value="archive" <?= ($_POST['statut_form'] ?? '') === 'archive' ? 'selected' : '' ?>>Archivée</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-row">
                                <div class="field">
                                    <label for="img_form">Image de couverture <span class="required">*</span></label>
                                    <div class="upload">
                                        <input type="file" id="img_form" name="img_form" accept="image/jpeg,image/png,image/webp" required>
                                        <div class="hint">JPG, PNG ou WEBP — 5 Mo max.</div>
                                        <div class="preview" id="imagePreview">
                                            <img id="previewImg" src="" alt="Aperçu">
                                        </div>
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="video_intro_form">Vidéo d'introduction <span style="font-weight:500;color:#8b9aaa">(facultatif)</span></label>
                                    <div class="upload">
                                        <input type="file" id="video_intro_form" name="video_intro_form" accept="video/mp4,video/webm,video/quicktime">
                                        <div class="hint">MP4, WEBM ou MOV — 100 Mo max.</div>
                                        <div class="video-preview" id="videoName"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="actions">
                            <a class="btn btn-secondary" href="formation.php">
                                <i class="fa-solid fa-arrow-left"></i>
                                Annuler
                            </a>
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>
                                Créer la formation
                            </button>
                        </div>

                    </div>
                </form>
            </section>
        </main>

        <script>
            const form = document.getElementById('formationForm');
            const title = document.getElementById('titre_form');
            const imageInput = document.getElementById('img_form');
            const imagePreview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const videoInput = document.getElementById('video_intro_form');
            const videoName = document.getElementById('videoName');

            form?.addEventListener('submit', function (e) {
                const valeur = title.value.replace(/\s+/g, ' ').trim();
                title.value = valeur;

                if (valeur === '') {
                    e.preventDefault();
                    title.focus();
                    title.setCustomValidity('Veuillez saisir le titre de la formation.');
                    title.reportValidity();
                    return;
                }

                title.setCustomValidity('');
            });

            title?.addEventListener('input', function () {
                this.setCustomValidity('');
            });

            imageInput?.addEventListener('change', function () {
                const file = this.files?.[0];
                if (!file) {
                    imagePreview.style.display = 'none';
                    return;
                }
                previewImg.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block';
            });

            videoInput?.addEventListener('change', function () {
                const file = this.files?.[0];
                if (!file) {
                    videoName.style.display = 'none';
                    return;
                }
                videoName.textContent = 'Vidéo sélectionnée : ' + file.name;
                videoName.style.display = 'block';
            });
        </script>

    </body>
</html>
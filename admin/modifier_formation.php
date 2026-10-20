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
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: formation.php');
    exit();
}
$categories = $con->query('SELECT id_cat,nom_cat FROM t_categories ORDER BY nom_cat')->fetchAll(PDO::FETCH_ASSOC);
$formateurs = $con
    ->query(
        "SELECT f.id_formateur,u.nom_user,u.prenom_user,f.specialite FROM t_formateurs f JOIN t_utilisateurs u ON u.id_user=f.id_user WHERE f.statut_formateur='actif' ORDER BY u.nom_user"
    )
    ->fetchAll(PDO::FETCH_ASSOC);
$q = $con->prepare('SELECT * FROM t_formations WHERE id_form=:id');
$q->execute([':id' => $id]);
$f = $q->fetch(PDO::FETCH_ASSOC);
if (!$f) {
    header('Location: formation.php');
    exit();
}
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = array_merge($f, $_POST);
    $f['id_cat'] = (int) ($_POST['id_cat'] ?? 0);
    $f['id_formateur'] = (int) ($_POST['id_formateur'] ?? 0);
    $err = [];

    if (trim((string) ($f['titre_form'] ?? '')) === '') {
        $err[] = 'Le titre est obligatoire.';
    }
    if (!$f['id_cat']) {
        $err[] = 'Sélectionnez une catégorie.';
    }
    if (!$f['id_formateur']) {
        $err[] = 'Sélectionnez un formateur.';
    }
    if (!is_numeric($f['prix_form'] ?? '') || $f['prix_form'] < 0) {
        $err[] = 'Prix invalide.';
    }

    $img = $f['img_form'] ?? null;
    $video = $f['video_intro_form'] ?? null;

    if (isset($_FILES['img_form']) && $_FILES['img_form']['error'] !== 4) {
        $ext = strtolower(pathinfo($_FILES['img_form']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && $_FILES['img_form']['size'] <= 5242880) {
            $dir = '../uploads/formations/';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $new = uniqid('formation_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['img_form']['tmp_name'], $dir . $new)) {
                $old = $dir . basename((string) $img);
                if ($img && is_file($old)) {
                    @unlink($old);
                }
                $img = $new;
            } else {
                $err[] = "Impossible d'enregistrer la nouvelle image.";
            }
        } else {
            $err[] = 'Image invalide ou trop lourde. Formats acceptés : JPG, PNG, WEBP, 5 Mo maximum.';
        }
    }

    if (isset($_FILES['video_form']) && $_FILES['video_form']['error'] !== 4) {
        $videoExt = strtolower(pathinfo($_FILES['video_form']['name'], PATHINFO_EXTENSION));
        $videoAllowed = ['mp4', 'webm', 'ogg', 'mov'];
        if (!in_array($videoExt, $videoAllowed, true)) {
            $err[] = 'Format vidéo invalide. Utilisez MP4, WEBM, OGG ou MOV.';
        } elseif (in_array($_FILES['video_form']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $err[] = 'La vidéo dépasse la taille autorisée par le serveur (' . ini_get('upload_max_filesize') . ').';
        } elseif ($_FILES['video_form']['size'] > 100 * 1024 * 1024) {
            $err[] = 'La vidéo ne doit pas dépasser 100 Mo.';
        } elseif ($_FILES['video_form']['error'] !== UPLOAD_ERR_OK) {
            $err[] = 'Le téléchargement de la vidéo a échoué.';
        } else {
            $videoDir = '../uploads/formations/videos/';
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0775, true);
            }
            $newVideo = uniqid('video_', true) . '.' . $videoExt;
            if (move_uploaded_file($_FILES['video_form']['tmp_name'], $videoDir . $newVideo)) {
                $oldVideo = (string) ($video ?? '');
                if ($oldVideo !== '') {
                    $oldVideoFile = $videoDir . basename($oldVideo);
                    if (is_file($oldVideoFile)) {
                        @unlink($oldVideoFile);
                    }
                }
                $video = $newVideo;
            } else {
                $err[] = "Impossible d'enregistrer la nouvelle vidéo.";
            }
        }
    }

    if (!$err) {
        $u = $con->prepare(
            'UPDATE t_formations SET id_cat=?,id_formateur=?,titre_form=?,descrip_form=?,img_form=?,video_intro_form=?,niveau_form=?,prix_form=?,duree_form=?,statut_form=? WHERE id_form=?'
        );
        $u->execute([
            $f['id_cat'],
            $f['id_formateur'],
            trim((string) $f['titre_form']),
            trim((string) ($f['descrip_form'] ?? '')),
            $img,
            $video,
            $f['niveau_form'],
            (float) $f['prix_form'],
            trim((string) ($f['duree_form'] ?? '')) ?: null,
            $f['statut_form'],
            $id
        ]);
        $_SESSION['flash_success'] = 'La formation a été modifiée avec succès.';
        header('Location: formation.php');
        exit();
    }
}
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Modifier une formation | TOUN</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap">
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: #f4f8fb;
                color: #10294d;
                font-family: Poppins, Arial, sans-serif;
            }

            .page {
                margin-left: 260px;
                padding: 95px 34px 35px;
            }

            .card {
                background: #fff;
                border: 1px solid #dde8ee;
                border-radius: 16px;
                box-shadow: 0 8px 25px rgba(16, 41, 77, 0.06);
            }

            .head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 22px;
            }

            .head h1 {
                margin: 0;
                font-size: 24px;
            }

            .head p {
                margin: 5px 0;
                color: #7b8b9d;
                font-size: 12px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 15px;
                border-radius: 9px;
                border: 0;
                text-decoration: none;
                font-size: 11.5px;
                font-weight: 700;
                cursor: pointer;
            }

            .primary {
                color: #fff;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
            }

            .light {
                color: #3f68ae;
                background: #eef5fa;
                border: 1px solid #d9e6ed;
            }

            .danger {
                color: #fff;
                background: #d9534f;
            }

            .form {
                padding: 24px;
            }

            .grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 17px;
            }

            .full {
                grid-column: 1/-1;
            }

            label {
                display: block;
                margin-bottom: 7px;
                font-size: 11.5px;
                font-weight: 700;
            }

            input,
            select,
            textarea {
                width: 100%;
                padding: 11px;
                border: 1px solid #d7e3ea;
                border-radius: 9px;
                background: #fbfdfe;
                outline: 0;
                font-size: 11.5px;
                color: #17345b;
            }

            textarea {
                min-height: 120px;
                resize: vertical;
            }

            input:focus,
            select:focus,
            textarea:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin-top: 20px;
                padding-top: 17px;
                border-top: 1px solid #e9eff3;
            }

            .alert {
                padding: 12px;
                margin-bottom: 18px;
                border-radius: 9px;
                font-size: 11.5px;
                background: #fff0ef;
                color: #9c2f2b;
            }

            .preview {
                width: 190px;
                height: 110px;
                object-fit: cover;
                border-radius: 10px;
            }

            .table {
                overflow: hidden;
            }

            .table table {
                width: 100%;
                border-collapse: collapse;
            }

            .table th,
            .table td {
                padding: 12px;
                border-bottom: 1px solid #edf2f5;
                text-align: left;
                font-size: 11.5px;
            }

            .table th {
                background: #f7fafc;
                color: #61748a;
                font-size: 10.5px;
            }

            .thumb {
                width: 58px;
                height: 42px;
                object-fit: cover;
                border-radius: 7px;
            }

            .badge {
                padding: 5px 8px;
                border-radius: 20px;
                font-size: 10.5px;
                font-weight: 700;
            }

            .b1 {
                color: #28609a;
                background: #eaf3fb;
            }

            .b2 {
                color: #16745d;
                background: #e9f8f3;
            }

            .b3 {
                color: #657487;
                background: #eef2f5;
            }

            .mini {
                display: flex;
                gap: 5px;
            }

            .ico {
                width: 30px;
                height: 30px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                background: #eef5fa;
                color: #3f68ae;
                text-decoration: none;
            }

            .ico.del {
                background: #fff0ef;
                color: #c13c37;
            }

            .detail {
                padding: 24px;
            }

            .detail-grid {
                display: grid;
                grid-template-columns: 280px 1fr;
                gap: 25px;
            }

            .detail-img {
                width: 100%;
                height: 190px;
                object-fit: cover;
                border-radius: 13px;
            }

            .detail h2 {
                font-size: 22px;
                margin: 10px 0;
            }

            .detail p {
                font-size: 12px;
                line-height: 1.8;
                color: #718398;
            }

            .meta {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-top: 18px;
            }

            .meta div {
                padding: 12px;
                border: 1px solid #dde8ee;
                border-radius: 10px;
                background: #f8fbfc;
            }

            .meta span {
                display: block;
                color: #8291a3;
                font-size: 10.5px;
                margin-bottom: 4px;
            }

            .meta strong {
                font-size: 11.5px;
            }

            @media (max-width: 900px) {
                .page {
                    margin-left: 0;
                    padding: 90px 18px 25px;
                }

                .grid,
                .detail-grid,
                .meta {
                    grid-template-columns: 1fr;
                }

                .full {
                    grid-column: auto;
                }
            }
        </style>
    </head>
    <body>
        <?php include 'menu.php'; ?>
        <main class="page">
            <div class="head">
                <div>
                    <h1>Modifier la formation</h1>
                    <p>Mettez à jour les informations.</p>
                </div>
                <a href="formation.php" class="btn light">Retour</a>
            </div>
            <?php if ($err): ?>
                <div class="alert"><?= e(implode(' ', $err)) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="card form">
                <div class="grid">
                    <div><label>Titre *</label><input name="titre_form" value="<?= e($f['titre_form']) ?>" required></div>
                    <div>
                        <label>Catégorie *</label>
                        <select name="id_cat">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id_cat'] ?>" <?= $f['id_cat'] == $c['id_cat'] ? 'selected' : '' ?>><?= e($c['nom_cat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Formateur *</label>
                        <select name="id_formateur">
                            <?php foreach ($formateurs as $x): ?>
                                <option value="<?= $x['id_formateur'] ?>" <?= $f['id_formateur'] == $x['id_formateur'] ? 'selected' : '' ?>><?= e($x['prenom_user'] . ' ' . $x['nom_user']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Niveau</label>
                        <select name="niveau_form">
                            <?php foreach(["Débutant","Intermédiaire","Avancé","Tous niveaux"] as $n): ?>
                                <option <?= $f['niveau_form'] === $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Prix (FCFA) *</label><input type="number" name="prix_form" value="<?= e($f['prix_form']) ?>" required></div>
                    <div><label>Durée</label><input name="duree_form" value="<?= e($f['duree_form']) ?>"></div>
                    <div>
                        <label>Statut</label>
                        <select name="statut_form">
                            <option value="brouillon" <?= $f['statut_form'] === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="publie" <?= $f['statut_form'] === 'publie' ? 'selected' : '' ?>>Publié</option>
                        </select>
                    </div>
                    <div>
                        <label for="video_form">Vidéo de la formation</label>
                        <input id="video_form" type="file" name="video_form" accept=".mp4,.webm,.ogg,.mov">
                        <div style="margin-top:6px;color:#8291A3;font-size:10.5px">Formats : MP4, WEBM, OGG, MOV — 100 Mo maximum.</div>
                        <?php if(!empty($f["video_intro_form"])): ?>
                            <div style="margin-top:12px">
                                <video controls style="width:100%;max-width:420px;height:190px;border-radius:10px;background:#10294D">
                                    <source src="../uploads/formations/videos/<?= e(basename($f['video_intro_form'])) ?>">
                                    Votre navigateur ne prend pas en charge la lecture vidéo.
                                </video>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="full"><label>Description</label><textarea name="descrip_form"><?= e($f['descrip_form']) ?></textarea></div>
                    <div class="full">
                        <?php if($f["img_form"]): ?>
                            <img class="preview" src="../uploads/formations/<?= e(basename($f['img_form'])) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="full"><label>Nouvelle image</label><input type="file" name="img_form" accept=".jpg,.jpeg,.png,.webp"></div>
                </div>
                <div class="actions"><a href="formation.php" class="btn light">Annuler</a><button class="btn primary">Enregistrer</button></div>
            </form>
        </main>
    </body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/connexion.php';
function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
$active_page = basename($_SERVER['PHP_SELF']);
?>
<?php
$id = (int) ($_GET['id'] ?? 0);
$st = $con->prepare('SELECT * FROM t_categories WHERE id_cat=?');
$st->execute([$id]);
$cat = $st->fetch(PDO::FETCH_ASSOC);
if (!$cat) {
    header('Location: categories.php');
    exit();
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_cat'] ?? '');
    $desc = trim($_POST['descrip_cat'] ?? '');
    $image = $cat['image_cat'] ?? '';
    if ($nom === '') {
        $errors[] = 'Le nom de la catégorie est obligatoire.';
    }
    if (!$errors && isset($_FILES['image_cat']) && $_FILES['image_cat']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['image_cat'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Format image non autorisé.';
        } elseif ($f['size'] > 5 * 1024 * 1024) {
            $errors[] = 'L’image ne doit pas dépasser 5 Mo.';
        } else {
            $dir = __DIR__ . '/../uploads/categories';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $image = 'cat_' . uniqid() . '.' . $ext;
            move_uploaded_file($f['tmp_name'], $dir . '/' . $image);
        }
    }
    if (!$errors) {
        $st = $con->prepare('UPDATE t_categories SET nom_cat=?,descrip_cat=?,image_cat=? WHERE id_cat=?');
        $st->execute([$nom, $desc, $image, $id]);
        $_SESSION['flash_success'] = 'La catégorie a été modifiée avec succès.';
        header('Location: categories.php');
        exit();
    }
}
?>
<?php include 'menu.php'; ?>

<style>
    .cat-page {
        font-family: "Poppins", Arial, sans-serif;
        background: #f6f8fc;
        min-height: 100vh;
        padding: 94px 28px 30px 288px;
        color: #18213f;
    }

    .cat-wrap {
        max-width: 1180px;
        margin: auto;
    }

    .cat-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
    }

    .cat-head h1 {
        margin: 0;
        font-size: 23px;
        font-weight: 700;
    }

    .cat-head p {
        margin: 5px 0 0;
        color: #8b96a8;
        font-size: 12px;
    }

    .cat-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        border: 0;
        border-radius: 10px;
        text-decoration: none;
        font: 600 12px "Poppins";
        cursor: pointer;
    }

    .cat-btn-primary {
        color: #fff;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        box-shadow: 0 7px 18px rgba(32, 161, 184, 0.2);
    }

    .cat-btn-secondary {
        color: #3f68ae;
        background: #eef4fa;
        border: 1px solid #dbe7f0;
    }

    .cat-btn-danger {
        color: #fff;
        background: #d95368;
    }

    .cat-card {
        background: #fff;
        border: 1px solid #e4ebf2;
        border-radius: 14px;
        box-shadow: 0 8px 25px rgba(24, 33, 63, 0.05);
        overflow: hidden;
    }

    .cat-toolbar {
        padding: 15px 17px;
        border-bottom: 1px solid #edf1f5;
        display: flex;
        justify-content: space-between;
        gap: 15px;
    }

    .cat-search {
        width: 280px;
        height: 38px;
        border: 1px solid #dfe8ef;
        border-radius: 9px;
        padding: 0 12px;
        outline: 0;
        font: 11.5px "Poppins";
    }

    .cat-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cat-table th {
        background: #eaf3f7;
        color: #526278;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 14px;
    }

    .cat-table td {
        padding: 12px 14px;
        border-top: 1px solid #edf1f5;
        font-size: 11.5px;
        vertical-align: middle;
    }

    .cat-table tr:hover td {
        background: #f8fbfd;
    }

    .cat-img {
        width: 48px;
        height: 38px;
        border-radius: 8px;
        object-fit: cover;
        background: #edf5f8;
    }

    .cat-empty {
        text-align: center;
        padding: 45px;
        color: #8b96a8;
        font-size: 12px;
    }

    .cat-actions {
        display: flex;
        gap: 6px;
    }

    .cat-action {
        width: 31px;
        height: 31px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        text-decoration: none;
    }

    .cat-view {
        background: #eaf7f8;
        color: #16899a;
    }

    .cat-edit {
        background: #edf2fb;
        color: #3f68ae;
    }

    .cat-delete {
        background: #fff0f2;
        color: #d95368;
    }

    .cat-form-card {
        max-width: 760px;
        margin: auto;
        padding: 25px;
    }

    .cat-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 17px;
    }

    .cat-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .cat-field.full {
        grid-column: 1/-1;
    }

    .cat-field label {
        font-size: 11.5px;
        font-weight: 600;
        color: #34435b;
    }

    .cat-field input,
    .cat-field textarea {
        width: 100%;
        border: 1px solid #dce6ee;
        border-radius: 9px;
        padding: 10px 11px;
        outline: 0;
        font: 11.5px "Poppins";
        background: #fbfdff;
    }

    .cat-field textarea {
        min-height: 125px;
        resize: vertical;
    }

    .cat-field input:focus,
    .cat-field textarea:focus {
        border-color: #20a1b8;
        box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
    }

    .cat-required {
        color: #d95368;
    }

    .cat-preview {
        width: 100px;
        height: 75px;
        border-radius: 9px;
        object-fit: cover;
        margin-top: 5px;
    }

    .cat-alert {
        padding: 11px 13px;
        border-radius: 9px;
        margin-bottom: 15px;
        font-size: 11.5px;
    }

    .cat-error {
        background: #fff0f2;
        color: #b83d52;
    }

    .cat-success {
        background: #eaf8f3;
        color: #16806d;
    }

    .cat-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        margin-top: 22px;
    }

    .cat-detail {
        padding: 24px;
    }

    .cat-detail-top {
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }

    .cat-detail-img {
        width: 180px;
        height: 135px;
        border-radius: 12px;
        object-fit: cover;
        background: #edf5f8;
    }

    .cat-detail h2 {
        margin: 0 0 8px;
        font-size: 19px;
    }

    .cat-detail p {
        font-size: 12px;
        line-height: 1.7;
        color: #69778b;
    }

    .cat-badge {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 7px;
        background: #e7f6f8;
        color: #16899a;
        font-size: 11px;
        font-weight: 600;
    }

    @media (max-width: 900px) {
        .cat-page {
            padding-left: 95px;
        }

        .cat-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="cat-page">
    <div class="cat-wrap">
        <div class="cat-head">
            <div>
                <h1>Modifier une catégorie</h1>
                <p>Mettez à jour les informations de la catégorie.</p>
            </div>
        </div>
        <div class="cat-card cat-form-card">
            <?php if ($errors): ?>
                <div class="cat-alert cat-error"><?= e(implode(' ', $errors)) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="cat-form-grid">
                    <div class="cat-field">
                        <label>Nom de la catégorie <span class="cat-required">*</span></label>
                        <input name="nom_cat" value="<?= e($_POST['nom_cat'] ?? $cat['nom_cat']) ?>" required>
                    </div>
                    <div class="cat-field"><label>Nouvelle image</label><input type="file" name="image_cat" accept="image/jpeg,image/png,image/webp"></div>
                    <div class="cat-field full">
                        <label>Description</label>
                        <textarea name="descrip_cat"><?= e($_POST['descrip_cat'] ?? $cat['descrip_cat']) ?></textarea>
                    </div>
                </div>
                <div class="cat-form-actions">
                    <a class="cat-btn cat-btn-secondary" href="categories.php">Annuler</a>
                    <button class="cat-btn cat-btn-primary" type="submit"><i class="fa-solid fa-check"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</main>

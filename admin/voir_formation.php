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
$q = $con->prepare(
    'SELECT f.*,c.nom_cat,u.nom_user,u.prenom_user,fo.specialite FROM t_formations f LEFT JOIN t_categories c ON c.id_cat=f.id_cat LEFT JOIN t_formateurs fo ON fo.id_formateur=f.id_formateur LEFT JOIN t_utilisateurs u ON u.id_user=fo.id_user WHERE f.id_form=:id'
);
$q->execute([':id' => $id]);
$f = $q->fetch(PDO::FETCH_ASSOC);
if (!$f) {
    header('Location: formation.php');
    exit();
}
$q = $con->prepare('SELECT COUNT(*) FROM t_modules WHERE id_form=:id');
$q->execute([':id' => $id]);
$modules = $q->fetchColumn();
$img = $f['img_form'] ? '../uploads/formations/' . basename($f['img_form']) : '../images/formation-hero.jpg';
$video = !empty($f['video_intro_form'])
    ? '../uploads/formations/videos/' . rawurlencode(basename($f['video_intro_form']))
    : '';
if ($video !== '' && !is_file(__DIR__ . '/../uploads/formations/videos/' . basename($f['video_intro_form']))) {
    $video = '';
}
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Détails formation | TOUN</title>
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

            .detail-video {
                display: block;
                object-fit: contain;
                background: #000;
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
                    <h1>Détails de la formation</h1>
                    <p>Informations complètes.</p>
                </div>
                <div><a href="modifier_formation.php?id=<?= $id ?>" class="btn primary">Modifier</a><a href="formation.php" class="btn light">Retour</a></div>
            </div>
            <div class="card detail-grid detail">
                <div>
                    <?php if ($video): ?>
                        <video src="<?= e($video) ?>" class="detail-img detail-video" controls preload="metadata" poster="<?= e($img) ?>"></video>
                    <?php else: ?>
                        <img src="<?= e($img) ?>" class="detail-img" alt="">
                    <?php endif; ?>
                    <div class="meta">
                        <div><span>Prix</span><strong><?= number_format((float) $f['prix_form'], 0, ',', ' ') ?> FCFA</strong></div>
                        <div><span>Modules</span><strong><?= $modules ?></strong></div>
                        <div><span>Niveau</span><strong><?= e($f['niveau_form']) ?></strong></div>
                    </div>
                </div>
                <div>
                    <span class="badge b1"><?= e($f['nom_cat'] ?: 'Sans catégorie') ?></span>
                    <h2><?= e($f['titre_form']) ?></h2>
                    <p><?= nl2br(e($f['descrip_form'] ?: 'Aucune description.')) ?></p>
                    <div class="meta">
                        <div><span>Formateur</span><strong><?= e(trim(($f['prenom_user'] ?? '') . ' ' . ($f['nom_user'] ?? ''))) ?></strong></div>
                        <div><span>Durée</span><strong><?= e($f['duree_form'] ?: 'Non renseignée') ?></strong></div>
                        <div><span>Statut</span><strong><?= e($f['statut_form']) ?></strong></div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
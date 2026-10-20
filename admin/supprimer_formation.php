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
$q = $con->prepare('SELECT * FROM t_formations WHERE id_form=:id');
$q->execute([':id' => $id]);
$f = $q->fetch(PDO::FETCH_ASSOC);
if (!$f) {
    header('Location: formation.php');
    exit();
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $q = $con->prepare('SELECT COUNT(*) FROM t_modules WHERE id_form=:id');
        $q->execute([':id' => $id]);
        if ($q->fetchColumn() > 0) {
            throw new Exception("Cette formation possède des modules. Supprimez d'abord les modules associés.");
        }
        $q = $con->prepare('DELETE FROM t_formations WHERE id_form=:id');
        $q->execute([':id' => $id]);
        if ($f['img_form'] && is_file('../uploads/formations/' . basename($f['img_form']))) {
            @unlink('../uploads/formations/' . basename($f['img_form']));
        }
        $_SESSION['flash_success'] = 'La formation a été supprimée.';
        header('Location: formation.php');
        exit();
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Supprimer | TOUN</title>
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
                font-size: 11px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 15px;
                border-radius: 9px;
                border: 0;
                text-decoration: none;
                font-size: 10px;
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
                font-size: 10px;
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
                font-size: 10px;
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
                font-size: 10px;
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
                font-size: 10px;
            }

            .table th {
                background: #f7fafc;
                color: #61748a;
                font-size: 8px;
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
                font-size: 8px;
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
                font-size: 11px;
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
                font-size: 8px;
                margin-bottom: 4px;
            }

            .meta strong {
                font-size: 10px;
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
            <div class="card detail" style="max-width:650px;margin:50px auto;text-align:center">
                <h2>Supprimer cette formation ?</h2>
                <p>Vous allez supprimer <strong><?= e($f['titre_form']) ?></strong>.</p>
                <?php if ($err): ?>
                    <div class="alert"><?= e($err) ?></div>
                <?php endif; ?>
                <form method="post" class="actions" style="justify-content:center">
                    <a href="formation.php" class="btn light">Annuler</a>
                    <button class="btn danger">Confirmer la suppression</button>
                </form>
            </div>
        </main>
    </body>
</html>
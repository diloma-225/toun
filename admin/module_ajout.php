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
?>
<?php
$id_form_pre = filter_input(INPUT_GET, 'id_form', FILTER_VALIDATE_INT) ?: 0;
$formations = $con
    ->query('SELECT id_form,titre_form FROM t_formations ORDER BY titre_form ASC')
    ->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_form = (int) ($_POST['id_form'] ?? 0);
    $titre = trim($_POST['titre_module'] ?? '');
    $description = trim($_POST['description_module'] ?? '');
    $ordre = (int) ($_POST['ordre_module'] ?? 1);
    $duree = ($_POST['duree_module'] ?? '') === '' ? null : (int) $_POST['duree_module'];

    if ($id_form <= 0 || $titre === '' || $ordre < 1) {
        $error = 'Veuillez renseigner la formation, le titre et un ordre valide.';
    } else {
        $check = $con->prepare('SELECT COUNT(*) FROM t_formations WHERE id_form=?');
        $check->execute([$id_form]);
        if (!$check->fetchColumn()) {
            $error = "La formation sélectionnée n'existe pas.";
        } else {
            try {
                $stmt = $con->prepare(
                    'INSERT INTO t_modules (id_form,titre_module,description_module,ordre_module,duree_module) VALUES (?,?,?,?,?)'
                );
                $stmt->execute([$id_form, $titre, $description, $ordre, $duree]);
                $_SESSION['success'] = 'Module ajouté avec succès.';
                header('Location: modules.php?id_form=' . $id_form);
                exit();
            } catch (PDOException $e) {
                $error = "Impossible d'ajouter le module.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Ajouter un module | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: "Poppins", Arial, sans-serif;
                background: #eef4f8;
                color: #17324d;
            }

            .main {
                margin-left: 260px;
                padding: 88px 25px 35px;
                min-height: 100vh;
            }

            .page-head {
                max-width: 1050px;
                margin: 0 auto 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
            }

            .page-head h1 {
                margin: 0;
                font-size: 23px;
                font-weight: 700;
                color: #173b5b;
            }

            .page-head p {
                margin: 4px 0 0;
                color: #71869a;
                font-size: 12px;
            }

            .btn {
                border: 0;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                border-radius: 9px;
                padding: 9px 13px;
                font:
                    600 11.5px Poppins,
                    Arial;
                cursor: pointer;
                transition: 0.2s;
            }

            .btn-primary {
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                box-shadow: 0 6px 15px rgba(32, 161, 184, 0.18);
            }

            .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 9px 18px rgba(63, 104, 174, 0.22);
            }

            .btn-light {
                color: #496276;
                background: #fff;
                border: 1px solid #d8e6ed;
            }

            .btn-danger {
                color: #fff;
                background: #d95367;
            }

            .card {
                max-width: 1050px;
                margin: 0 auto;
                background: #fff;
                border: 1px solid #d9e7ee;
                border-radius: 14px;
                box-shadow: 0 8px 25px rgba(34, 67, 92, 0.06);
            }

            .filter {
                padding: 14px 16px;
                background: linear-gradient(135deg, #eaf8f9, #edf2fa);
                border-radius: 14px 14px 0 0;
                border-bottom: 1px solid #dbe9ef;
            }

            .filter form {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            select,
            input,
            textarea {
                width: 100%;
                border: 1px solid #d3e2e9;
                background: #fff;
                border-radius: 8px;
                outline: none;
                font:
                    500 11.5px Poppins,
                    Arial;
                color: #29445c;
                transition: 0.2s;
            }

            select,
            input {
                height: 35px;
                padding: 0 10px;
            }

            textarea {
                padding: 9px 10px;
                resize: vertical;
                min-height: 80px;
            }

            select:focus,
            input:focus,
            textarea:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.09);
            }

            .filter select {
                max-width: 560px;
            }

            .table-wrap {
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th {
                background: #e7f1f6;
                color: #496579;
                text-align: left;
                font-size: 11px;
                font-weight: 700;
                padding: 11px 13px;
            }

            td {
                padding: 11px 13px;
                border-top: 1px solid #edf3f6;
                font-size: 11px;
                color: #526b7e;
                background: #fbfdfe;
                vertical-align: middle;
            }

            tr:nth-child(even) td {
                background: #f7fafc;
            }

            tr:hover td {
                background: #eaf7f9;
            }

            .module-title {
                font-weight: 700;
                color: #244c6b;
                font-size: 11.5px;
            }

            .module-desc {
                font-size: 11px;
                color: #71869a;
                margin-top: 3px;
                line-height: 1.45;
                max-width: 450px;
            }

            .badge {
                display: inline-flex;
                padding: 5px 8px;
                border-radius: 999px;
                background: #e7f6f8;
                color: #17879b;
                font-size: 11px;
                font-weight: 700;
            }

            .actions {
                display: flex;
                gap: 5px;
                justify-content: flex-end;
            }

            .action {
                width: 29px;
                height: 29px;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                border: 1px solid #d9e7ee;
                background: #fff;
                color: #4e7188;
                font-size: 11.5px;
            }

            .action:hover {
                border-color: #20a1b8;
                color: #20a1b8;
                background: #f1fbfc;
            }

            .action.delete:hover {
                border-color: #e26a78;
                color: #d95367;
                background: #fff5f6;
            }

            .empty {
                text-align: center;
                padding: 35px 20px;
                color: #7a8e9f;
                font-size: 11.5px;
            }

            .alert {
                max-width: 1050px;
                margin: 0 auto 14px;
                padding: 10px 13px;
                border-radius: 9px;
                font-size: 11px;
            }

            .alert-success {
                background: #eaf8f1;
                color: #19734b;
                border: 1px solid #ccebdc;
            }

            .alert-error {
                background: #fff0f2;
                color: #a83b4c;
                border: 1px solid #f2ccd3;
            }

            .form-card {
                max-width: 650px;
                margin: 0 auto;
                background: #fff;
                border: 1px solid #d8e7ee;
                border-radius: 14px;
                box-shadow: 0 10px 28px rgba(31, 66, 91, 0.07);
                padding: 20px;
            }

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .field {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .field.full {
                grid-column: 1/-1;
            }

            .field label {
                font-size: 11px;
                font-weight: 700;
                color: #4c687d;
            }

            .actions-form {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin-top: 16px;
                padding-top: 14px;
                border-top: 1px solid #e9f0f3;
            }

            @media (max-width: 800px) {
                .main {
                    margin-left: 0;
                    padding: 80px 15px 25px;
                }

                .page-head {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .form-grid {
                    grid-template-columns: 1fr;
                }

                .field.full {
                    grid-column: auto;
                }
            }
        </style>

    </head>
    <body>
        <?php include 'menu.php'; ?>
        <main class="main">
            <div class="page-head" style="max-width:650px">
                <div>
                    <h1>Ajouter un module</h1>
                    <p>Ajoutez un nouveau module à une formation.</p>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="form-card">
                <div class="form-grid">
                    <div class="field full"><label>Formation *</label>
                        <select name="id_form" required>
                            <option value="">Sélectionner une formation</option>
                            <?php foreach ($formations as $f): ?>
                                <option value="<?= e($f['id_form']) ?>" <?= (int) ($id_form_pre ?: $_POST['id_form'] ?? 0) === $f['id_form'] ? 'selected' : '' ?>><?= e($f['titre_form']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field full">
                        <label>Titre du module *</label>
                        <input type="text" name="titre_module" maxlength="200" required value="<?= e($_POST['titre_module'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Ordre du module *</label>
                        <input type="number" name="ordre_module" min="1" required value="<?= e($_POST['ordre_module'] ?? 1) ?>">
                    </div>
                    <div class="field">
                        <label>Durée (minutes)</label>
                        <input type="number" name="duree_module" min="0" value="<?= e($_POST['duree_module'] ?? '') ?>">
                    </div>
                    <div class="field full">
                        <label>Description</label>
                        <textarea name="description_module" maxlength="1000"><?= e($_POST['description_module'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="actions-form">
                    <a href="modules.php<?= $id_form_pre ? '?id_form=' . $id_form_pre : '' ?>" class="btn btn-light">Annuler</a>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> Enregistrer</button>
                </div>
            </form>
        </main>
    </body>
</html>

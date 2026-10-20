<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$id_form = filter_input(INPUT_GET, 'id_form', FILTER_VALIDATE_INT) ?: 0;

$formations = $con
    ->query(
        "
    SELECT id_form, titre_form
    FROM t_formations
    ORDER BY titre_form ASC
"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$modules = [];

if ($id_form > 0) {
    $stmt = $con->prepare("
        SELECT
            id_module,
            id_form,
            titre_module,
            description_module,
            ordre_module,
            duree_module
        FROM t_modules
        WHERE id_form = ?
        ORDER BY ordre_module ASC, id_module ASC
    ");

    $stmt->execute([$id_form]);

    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'menu.php';
?>

<!DOCTYPE html>
<html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <title>Modules | TOUN</title>

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
        >

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
                font-family: "Poppins", Arial, sans-serif;
                background: #eef4f8;
                color: #17324d;
            }

            .main {
                margin-left: 260px;
                padding: 95px 30px 40px;
                min-height: 100vh;
            }

            .page-container {
                width: 100%;
                max-width: 1150px;
                margin: 0 auto;
            }

            .page-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 25px;
                margin-bottom: 28px;
            }

            .page-title h1 {
                margin: 0;
                font-size: 25px;
                font-weight: 700;
                color: #173b5b;
            }

            .page-title p {
                margin: 7px 0 0;
                font-size: 12px;
                color: #71869a;
            }

            .btn {
                min-height: 40px;
                padding: 0 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border-radius: 10px;
                border: 0;
                text-decoration: none;
                font:
                    600 11.5px "Poppins",
                    Arial,
                    sans-serif;
                cursor: pointer;
                transition: 0.2s ease;
                white-space: nowrap;
            }

            .btn-primary {
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                box-shadow: 0 7px 18px rgba(32, 161, 184, 0.18);
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 22px rgba(63, 104, 174, 0.22);
            }

            .btn-light {
                color: #496276;
                background: #fff;
                border: 1px solid #d8e6ed;
            }

            .btn-light:hover {
                border-color: #20a1b8;
                color: #20a1b8;
            }

            .modules-card {
                background: #fff;
                border: 1px solid #d9e7ee;
                border-radius: 16px;
                box-shadow: 0 10px 28px rgba(34, 67, 92, 0.06);
                overflow: hidden;
            }

            .filter {
                padding: 20px;
                background: linear-gradient(135deg, #eaf8f9, #edf2fa);
                border-bottom: 1px solid #dbe9ef;
            }

            .filter-content {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .filter-label {
                display: flex;
                align-items: center;
                gap: 9px;
                min-width: 150px;
                font-size: 11.5px;
                font-weight: 600;
                color: #496579;
            }

            .filter-label i {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                background: #fff;
                color: #20a1b8;
                box-shadow: 0 3px 8px rgba(32, 161, 184, 0.08);
            }

            .filter-select {
                flex: 1;
                max-width: 650px;
            }

            select {
                width: 100%;
                height: 40px;
                padding: 0 12px;
                border: 1px solid #d3e2e9;
                border-radius: 9px;
                background: #fff;
                outline: none;
                font:
                    500 11.5px "Poppins",
                    Arial,
                    sans-serif;
                color: #29445c;
                cursor: pointer;
            }

            select:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.09);
            }

            .table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                min-width: 800px;
            }

            th {
                padding: 15px 18px;
                background: #e7f1f6;
                color: #496579;
                text-align: left;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.45px;
            }

            td {
                padding: 17px 18px;
                border-top: 1px solid #edf3f6;
                background: #fff;
                color: #526b7e;
                font-size: 11.5px;
                vertical-align: middle;
            }

            tbody tr:nth-child(even) td {
                background: #fbfdfe;
            }

            tbody tr:hover td {
                background: #f0fafb;
            }

            .order-badge {
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 9px;
                background: #e7f6f8;
                color: #17879b;
                font-size: 11.5px;
                font-weight: 700;
            }

            .module-title {
                font-size: 12px;
                font-weight: 700;
                color: #244c6b;
            }

            .module-desc {
                max-width: 520px;
                margin-top: 5px;
                font-size: 11px;
                line-height: 1.55;
                color: #71869a;
            }

            .duration {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 11px;
                color: #60778a;
            }

            .duration i {
                color: #20a1b8;
            }

            .actions {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                gap: 7px;
            }

            .action {
                width: 34px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 9px;
                border: 1px solid #dce8ee;
                background: #fff;
                color: #4e7188;
                text-decoration: none;
                font-size: 11.5px;
                transition: 0.2s ease;
            }

            .action:hover {
                border-color: #20a1b8;
                color: #20a1b8;
                background: #f1fbfc;
                transform: translateY(-2px);
            }

            .action.delete:hover {
                border-color: #e26a78;
                color: #d95367;
                background: #fff5f6;
            }

            .empty {
                padding: 65px 25px;
                text-align: center;
                color: #7a8e9f;
                font-size: 11.5px;
            }

            .empty-icon {
                width: 55px;
                height: 55px;
                margin: 0 auto 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 14px;
                background: #eaf7f9;
                color: #20a1b8;
                font-size: 20px;
            }

            .empty-title {
                margin-bottom: 6px;
                font-size: 12px;
                font-weight: 600;
                color: #526b7e;
            }

            .empty-text {
                margin-bottom: 20px;
                color: #8a9aaa;
                font-size: 11px;
            }

            .alert {
                margin-bottom: 18px;
                padding: 11px 14px;
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

            @media (max-width: 900px) {
                .main {
                    margin-left: 0;
                    padding: 90px 20px 30px;
                }

                .page-head {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .filter-content {
                    align-items: stretch;
                    flex-direction: column;
                }

                .filter-label {
                    min-width: auto;
                }

                .filter-select {
                    max-width: none;
                }
            }
        </style>

    </head>

    <body>

        <main class="main">

            <div class="page-container">

                <div class="page-head">

                    <div class="page-title">

                        <h1>Gestion des modules</h1>

                        <p>
                            Organisez les modules de chaque formation,
                            définissez leur ordre et leur durée.
                        </p>

                    </div>

                    <a
                        href="module_ajout.php"
                        class="btn btn-primary"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Ajouter un module
                    </a>

                </div>

                <section class="modules-card">

                    <div class="filter">

                        <div class="filter-content">

                            <div class="filter-label">

                                <i class="fa-solid fa-book-open"></i>

                                <span>
                                    Choisir une formation
                                </span>

                            </div>

                            <div class="filter-select">

                                <form method="get">

                                    <select
                                        name="id_form"
                                        onchange="this.form.submit()"
                                        aria-label="Choisir une formation"
                                    >

                                        <option value="0">
                                            Sélectionner une formation
                                        </option>

                                        <?php foreach ($formations as $formation): ?>

                                            <option
                                                value="<?= e($formation['id_form']) ?>"
                                                <?= $id_form == $formation['id_form'] ? 'selected' : '' ?>
                                            >
                                                <?= e($formation['titre_form']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </form>

                            </div>

                            <?php if ($id_form): ?>

                                <a
                                    href="modules.php"
                                    class="btn btn-light"
                                >
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Réinitialiser
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                    <?php if (!$id_form): ?>

                        <div class="empty">

                            <div class="empty-icon">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>

                            <div class="empty-title">
                                Sélectionnez une formation
                            </div>

                            <div class="empty-text">
                                Choisissez une formation ci-dessus
                                pour afficher ses modules.
                            </div>

                        </div>

                    <?php elseif (!$modules): ?>

                        <div class="empty">

                            <div class="empty-icon">
                                <i class="fa-regular fa-folder-open"></i>
                            </div>

                            <div class="empty-title">
                                Aucun module
                            </div>

                            <div class="empty-text">
                                Aucun module n'est encore associé
                                à cette formation.
                            </div>

                            <a
                                href="module_ajout.php?id_form=<?= e($id_form) ?>"
                                class="btn btn-primary"
                            >
                                <i class="fa-solid fa-plus"></i>
                                Créer le premier module
                            </a>

                        </div>

                    <?php else: ?>

                        <div class="table-wrap">

                            <table>

                                <thead>

                                    <tr>

                                        <th style="width: 80px;">
                                            Ordre
                                        </th>

                                        <th>
                                            Module
                                        </th>

                                        <th style="width: 140px;">
                                            Durée
                                        </th>

                                        <th style="width: 130px; text-align: right;">
                                            Actions
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($modules as $module): ?>

                                        <tr>

                                            <td>

                                                <div class="order-badge">
                                                    <?= e($module['ordre_module']) ?>
                                                </div>

                                            </td>

                                            <td>

                                                <div class="module-title">
                                                    <?= e($module['titre_module']) ?>
                                                </div>

                                                <div class="module-desc">

                                                    <?= e($module['description_module'] ?: 'Aucune description renseignée.') ?>

                                                </div>

                                            </td>

                                            <td>

                                                <?php if (
                                        $module['duree_module'] !== null
                                        && $module['duree_module'] !== ''
                                    ): ?>

                                                    <div class="duration">

                                                        <i class="fa-regular fa-clock"></i>

                                                        <?= e($module['duree_module']) ?>
                                                        min

                                                    </div>

                                                <?php else: ?>

                                                    <span>—</span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <div class="actions">

                                                    <a
                                                        href="lecons.php?module=<?= e($module['id_module']) ?>"
                                                        class="action"
                                                        title="Leçons"
                                                    >
                                                        <i class="fa-solid fa-list-ul"></i>
                                                    </a>

                                                    <a
                                                        href="modifier_module.php?id=<?= e($module['id_module']) ?>"
                                                        class="action"
                                                        title="Modifier"
                                                    >
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>

                                                    <a
                                                        href="supprimer_module.php?id=<?= e($module['id_module']) ?>"
                                                        class="action delete"
                                                        title="Supprimer"
                                                        onclick="return confirm('Supprimer ce module ? Cette action peut supprimer son contenu associé si les clés étrangères sont configurées en cascade.');"
                                                    >
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </section>

            </div>

        </main>

    </body>
</html>
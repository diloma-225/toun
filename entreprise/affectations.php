<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'affectations.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Affectations — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .assign-panel {
                max-width: 820px;
                margin: 0 auto 12px;
                padding: 16px;
                border: 1px solid #b9dfe5;
                background: linear-gradient(135deg, #fff, #f5fbfc);
            }

            .assign-panel form {
                display: flex;
                align-items: flex-end;
                gap: 10px;
            }

            .assign-grid {
                flex: 1;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
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

            .field select {
                height: 34px;
                border: 1px solid #cfe2e8;
                border-radius: 8px;
                background: #fff;
                padding: 0 10px;
                outline: 0;
                font: 10.5px Poppins;
                color: #294965;
            }

            .list-panel {
                margin-top: 12px;
            }

            .section-head {
                padding: 12px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #edf2f4;
            }

            .count-pill {
                display: grid;
                place-items: center;
                width: 27px;
                height: 23px;
                border-radius: 7px;
                background: #eef8fa;
                color: #168b9a;
                font-size: 10px;
                font-weight: 800;
            }

            .table-wrap {
                overflow: auto;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
            }

            .table th {
                padding: 9px 12px;
                background: linear-gradient(135deg, #eaf1ff, #eef8fa);
                color: #2c5680;
                font-size: 10px;
                font-weight: 800;
                text-align: left;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                border-bottom: 2px solid #cfe3ea;
            }

            .table tbody tr {
                border-left: 3px solid transparent;
                transition: 0.15s;
            }

            .table tbody tr:hover {
                background: #f8fcfd;
                border-left-color: #20a1b8;
            }

            .table td {
                padding: 9px 12px;
                border-top: 1px solid #edf2f4;
                font-size: 10.5px;
            }

            .status {
                display: inline-flex;
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: 800;
            }

            .status.ok {
                background: #e9f8f5;
                color: #23866e;
            }

            .actions {
                display: flex;
                gap: 4px;
            }

            .action {
                width: 27px;
                height: 27px;
                border: 1px solid #e2ebef;
                border-radius: 8px;
                background: #fff;
                color: #708696;
                display: grid;
                place-items: center;
                font-size: 10.5px;
            }

            .action:hover {
                background: #eef8fa;
                color: #168b9a;
            }

            .action.danger:hover {
                background: #fff1f2;
                color: #c35b66;
            }

            .empty {
                text-align: center;
                padding: 35px;
                color: #8fa0ac;
                font-size: 10.5px;
            }

            .empty-purchase {
                min-height: 220px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px dashed #b9dfe5;
                border-radius: 14px;
                background: #f7fcfd;
                text-align: center;
            }

            .empty-purchase i {
                font-size: 24px;
                color: #20a1b8;
            }

            .empty-purchase h2 {
                margin: 8px 0 4px;
            }

            .empty-purchase p {
                max-width: 350px;
                color: #8a9ca9;
                font-size: 10px;
                line-height: 1.6;
                margin: 0 auto 10px;
            }

            @media (max-width: 700px) {
                .assign-panel form {
                    display: block;
                }

                .assign-grid {
                    grid-template-columns: 1fr;
                }

                .assign-panel .btn {
                    margin-top: 10px;
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">
                <?php
$errors = [];
$collabs = entrepriseCollaborators($con, $idEntreprise);
$purchased = enterprisePurchasedFormations($con, $userId);
$selectedForm = (int) ($_GET['formation'] ?? ($_POST['id_form'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int) ($_POST['id_user'] ?? 0);
    $fid = (int) ($_POST['id_form'] ?? 0);
    try {
        if (!$uid || !$fid) {
            throw new RuntimeException('Sélectionnez un collaborateur et une formation.');
        }

        $validForm = false;
        $paymentId = null;
        foreach ($purchased as $f) {
            if ((int) $f['id_form'] === $fid) {
                $validForm = true;
                $paymentId = (int) $f['id_paiement'];
                break;
            }
        }
        if (!$validForm) {
            throw new RuntimeException('Cette formation n’a pas encore été achetée par l’entreprise.');
        }

        $s = $con->prepare(
            "SELECT id_collaborateur FROM t_entreprise_collaborateurs WHERE id_entreprise=? AND id_user=? AND statut='actif' LIMIT 1"
        );
        $s->execute([$idEntreprise, $uid]);
        if (!$s->fetch()) {
            throw new RuntimeException('Collaborateur non autorisé.');
        }

        $s = $con->prepare('SELECT id_inscrip FROM t_inscriptions WHERE id_user=? AND id_form=? LIMIT 1');
        $s->execute([$uid, $fid]);
        if ($s->fetch()) {
            throw new RuntimeException('Cette formation est déjà affectée à ce collaborateur.');
        }

        $s = $con->prepare(
            'INSERT INTO t_inscriptions(id_user,id_form,id_paiement,date_inscrip_form,statut_inscrip) VALUES(?,?,?,NOW(),\'active\')'
        );
        $s->execute([$uid, $fid, $paymentId ?: null]);

        flash('ok', 'Formation affectée avec succès.');
        header('Location: affectations.php');
        exit();
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$st = $con->prepare("SELECT i.id_inscrip,i.id_user,i.id_form,i.date_inscrip_form,i.statut_inscrip,u.nom_user,u.prenom_user,u.photo_user,f.titre_form
                      FROM t_inscriptions i
                      INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user=i.id_user AND ec.id_entreprise=?
                      INNER JOIN t_utilisateurs u ON u.id_user=i.id_user
                      INNER JOIN t_formations f ON f.id_form=i.id_form
                      ORDER BY i.date_inscrip_form DESC");
$st->execute([$idEntreprise]);
$rows = $st->fetchAll();
?>

                <div class="page-head">
                    <div>
                        <span class="eyebrow">GESTION</span>
                        <h1>Affectations</h1>
                        <p>Attribuez uniquement les formations déjà achetées à vos collaborateurs.</p>
                    </div>
                    <?php if (!$purchased): ?>
                        <a class="btn" href="../formation.php"><i class="fa-solid fa-store"></i> Acheter une formation</a>
                    <?php endif; ?>
                </div>

                <?php foreach ($errors as $er): ?>
                    <div class="alert err"><?= e($er) ?></div>
                <?php endforeach; ?>

                <?php if ($flash): ?>
                    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>

                <?php if ($purchased): ?>
                    <section class="panel assign-panel">
                        <form method="post">
                            <div class="assign-grid">
                                <div class="field">
                                    <label>Collaborateur</label>
                                    <select name="id_user" required>
                                        <option value="">Choisir un collaborateur</option>
                                        <?php foreach ($collabs as $c): if ($c['statut'] === 'actif'): ?>
                                            <option value="<?= $c['id_user'] ?>"><?= e($c['prenom_user'] . ' ' . $c['nom_user']) ?></option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Formation achetée</label>
                                    <select name="id_form" required>
                                        <option value="">Choisir une formation</option>
                                        <?php foreach ($purchased as $f): ?>
                                            <option value="<?= $f['id_form'] ?>" <?= $selectedForm === (int) $f['id_form'] ? 'selected' : '' ?>><?= e($f['titre_form']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button class="btn"><i class="fa-solid fa-user-check"></i> Affecter la formation</button>
                        </form>
                    </section>
                <?php else: ?>
                    <section class="empty-purchase">
                        <div>
                            <i class="fa-solid fa-lock"></i>
                            <h2>Aucune formation disponible</h2>
                            <p>Achetez d’abord une formation dans le catalogue. Elle apparaîtra ensuite automatiquement ici.</p>
                            <a class="btn" href="../formation.php">Accéder au catalogue</a>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="panel list-panel">
                    <div class="section-head">
                        <div>
                            <span class="eyebrow">ACCÈS</span>
                            <h2>Collaborateurs affectés</h2>
                        </div>
                        <span class="count-pill"><?= count($rows) ?></span>
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th>Formation</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty">Aucune affectation.</div>
                                        </td>
                                    </tr>
                                <?php else: foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= e($r['prenom_user'] . ' ' . $r['nom_user']) ?></td>
                                        <td><?= e($r['titre_form']) ?></td>
                                        <td><?= e(date('d/m/Y', strtotime($r['date_inscrip_form']))) ?></td>
                                        <td><span class="status ok"><?= e($r['statut_inscrip']) ?></span></td>
                                        <td>
                                            <div class="actions">
                                                <a class="action" href="affectation-modifier.php?id=<?= $r['id_inscrip'] ?>"><i class="fa-solid fa-pen"></i></a>
                                                <a class="action danger" href="affectation-supprimer.php?id=<?= $r['id_inscrip'] ?>" onclick="return confirm('Supprimer cette affectation ?')"><i class="fa-solid fa-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </main>
    </body>
</html>

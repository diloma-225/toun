<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'affectation-modifier.php';

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
$purchased = enterprisePurchasedFormations($con, $userId);
$collabs = entrepriseCollaborators($con, $idEntreprise);

$s = $con->prepare("
    SELECT i.*
    FROM t_inscriptions i
    INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user = i.id_user AND ec.id_entreprise = ?
    WHERE i.id_inscrip = ?
    LIMIT 1
");
$s->execute([$idEntreprise, $id]);
$row = $s->fetch();

if (!$row) {
    exit('Affectation introuvable.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uid = (int) $_POST['id_user'];
        $fid = (int) $_POST['id_form'];
        $ok = false;

        foreach ($purchased as $f) {
            if ((int) $f['id_form'] === $fid) {
                $ok = true;
            }
        }

        if (!$ok) {
            throw new RuntimeException('La formation sélectionnée n’est pas achetée par l’entreprise.');
        }

        $s = $con->prepare(
            "SELECT id_collaborateur FROM t_entreprise_collaborateurs WHERE id_entreprise = ? AND id_user = ? AND statut = 'actif'"
        );
        $s->execute([$idEntreprise, $uid]);

        if (!$s->fetch()) {
            throw new RuntimeException('Collaborateur non autorisé.');
        }

        $s = $con->prepare(
            'SELECT id_inscrip FROM t_inscriptions WHERE id_user = ? AND id_form = ? AND id_inscrip <> ? LIMIT 1'
        );
        $s->execute([$uid, $fid, $id]);

        if ($s->fetch()) {
            throw new RuntimeException('Cette affectation existe déjà.');
        }

        $s = $con->prepare('UPDATE t_inscriptions SET id_user = ?, id_form = ? WHERE id_inscrip = ?');
        $s->execute([$uid, $fid, $id]);

        flash('ok', 'Affectation modifiée.');
        header('Location: affectations.php');
        exit();
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Modifier l’affectation — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .form-panel {
                max-width: 760px;
                margin: 25px auto;
                padding: 18px;
                border: 1px solid #b9dfe5;
                background: linear-gradient(135deg, #fff, #f5fbfc);
            }

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 11px;
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
                height: 35px;
                border: 1px solid #cfe2e8;
                border-radius: 8px;
                background: #fff;
                padding: 0 10px;
                outline: 0;
                font: 10.5px Poppins;
                color: #294965;
            }

            .form-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 14px;
            }

            @media (max-width: 650px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">

                <div class="page-head">
                    <div>
                        <span class="eyebrow">AFFECTATION</span>
                        <h1>Modifier l’affectation</h1>
                        <p>Changez le collaborateur ou la formation attribuée.</p>
                    </div>
                    <a class="btn btn-light" href="affectations.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
                </div>

                <?php foreach ($errors as $er): ?>
                    <div class="alert err"><?= e($er) ?></div>
                <?php endforeach; ?>

                <section class="panel form-panel">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $id ?>">

                        <div class="form-grid">
                            <div class="field">
                                <label>Collaborateur</label>
                                <select name="id_user" required>
                                    <?php foreach ($collabs as $c): ?>
                                        <?php if ($c['statut'] === 'actif'): ?>
                                            <option value="<?= $c['id_user'] ?>" <?= $row['id_user'] == $c['id_user'] ? 'selected' : '' ?>><?= e($c['prenom_user'] . ' ' . $c['nom_user']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>Formation achetée</label>
                                <select name="id_form" required>
                                    <?php foreach ($purchased as $f): ?>
                                        <option value="<?= $f['id_form'] ?>" <?= $row['id_form'] == $f['id_form'] ? 'selected' : '' ?>><?= e($f['titre_form']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="btn">Enregistrer</button>
                        </div>
                    </form>
                </section>

            </div>
        </main>
    </body>
</html>

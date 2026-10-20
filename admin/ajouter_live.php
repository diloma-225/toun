<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

try {
    $con->exec(
        "CREATE TABLE IF NOT EXISTS t_lives (
            id_live INT UNSIGNED NOT NULL AUTO_INCREMENT,
            titre VARCHAR(180) NOT NULL,
            id_formation INT UNSIGNED NULL,
            formation VARCHAR(150) NULL,
            formateur VARCHAR(150) NULL,
            date_live DATE NOT NULL,
            heure_live TIME NOT NULL,
            duree VARCHAR(30) NULL,
            lien_live VARCHAR(255) NULL,
            id_user INT UNSIGNED NULL,
            date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_live),
            INDEX idx_live_date (date_live)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
}

$formations = [];
try {
    $formations = $con
        ->query('SELECT id_form, titre_form FROM t_formations ORDER BY titre_form ASC')
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim((string) ($_POST['titre'] ?? ''));
    $idFormation = (int) ($_POST['id_formation'] ?? 0);
    $formateur = trim((string) ($_POST['formateur'] ?? ''));
    $dateLive = trim((string) ($_POST['date_live'] ?? ''));
    $heureLive = trim((string) ($_POST['heure_live'] ?? ''));
    $duree = trim((string) ($_POST['duree'] ?? ''));
    $lienLive = trim((string) ($_POST['lien_live'] ?? ''));

    $nomFormation = '';
    foreach ($formations as $f) {
        if ((int) $f['id_form'] === $idFormation) {
            $nomFormation = $f['titre_form'];
            break;
        }
    }

    if ($titre === '' || $dateLive === '' || $heureLive === '') {
        $erreur = 'Le titre, la date et l\'heure sont obligatoires.';
    } else {
        try {
            $stmt = $con->prepare(
                'INSERT INTO t_lives (titre, id_formation, formation, formateur, date_live, heure_live, duree, lien_live)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $titre,
                $idFormation > 0 ? $idFormation : null,
                $nomFormation !== '' ? $nomFormation : null,
                $formateur !== '' ? $formateur : null,
                $dateLive,
                $heureLive,
                $duree !== '' ? $duree : null,
                $lienLive !== '' ? $lienLive : null
            ]);
            $_SESSION['flash_success'] = 'Le live a été ajouté avec succès.';
            header('Location: lives.php');
            exit();
        } catch (Throwable $e) {
            $erreur = 'Une erreur est survenue lors de l\'enregistrement.';
        }
    }
}
?>

<style>
    .live-form-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .live-form-card {
        max-width: 640px;
        margin: 0 auto;
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 17px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(24, 33, 63, 0.07);
    }

    .live-form-head {
        padding: 18px 20px;
        background: linear-gradient(110deg, #18213f, #3f68ae 60%, #20a1b8);
        color: #fff;
    }

    .live-form-head h1 {
        margin: 0;
        font-size: 19px;
    }

    .live-form-head p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .live-form-body {
        padding: 20px;
    }

    .live-form-erreur {
        padding: 10px 13px;
        margin-bottom: 14px;
        border-radius: 10px;
        background: #fdebec;
        color: #b53f3f;
        font-size: 11px;
    }

    .live-champ {
        margin-bottom: 14px;
    }

    .live-champ label {
        display: block;
        margin-bottom: 6px;
        color: #536a7f;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .live-champ input,
    .live-champ select {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border: 1px solid #dfe7ee;
        border-radius: 10px;
        font:
            11px "Poppins",
            sans-serif;
        outline: 0;
    }

    .live-champs-duo {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .live-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 18px;
    }

    .live-form-actions a,
    .live-form-actions button {
        height: 37px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 16px;
        border-radius: 9px;
        border: 0;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .live-btn-annuler {
        background: #fff;
        border: 1px solid #d3e0e6 !important;
        color: #56667a;
    }

    .live-btn-valider {
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
    }

    @media (max-width: 600px) {
        .live-form-page {
            padding: 13px 10px 20px;
        }

        .live-champs-duo {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main">
    <section class="live-form-page">
        <article class="live-form-card">
            <header class="live-form-head">
                <h1>Ajouter une session live</h1>
                <p>Cette session apparaîtra dans "Mes Lives" côté apprenant.</p>
            </header>

            <div class="live-form-body">
                <?php if ($erreur !== ''): ?>
                    <div class="live-form-erreur">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?= e($erreur) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="ajouter_live.php">
                    <div class="live-champ">
                        <label for="titre">Titre de la session</label>
                        <input id="titre" type="text" name="titre" value="<?= e($_POST['titre'] ?? '') ?>" required>
                    </div>

                    <div class="live-champ">
                        <label for="id_formation">Formation liée</label>
                        <select id="id_formation" name="id_formation">
                            <option value="0">Aucune formation spécifique</option>
                            <?php foreach ($formations as $f): ?>
                                <option value="<?= (int) $f['id_form'] ?>" <?= (int) ($_POST['id_formation'] ?? 0) === (int) $f['id_form'] ? 'selected' : '' ?>>
                                    <?= e($f['titre_form']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="live-champ">
                        <label for="formateur">Formateur / animateur</label>
                        <input id="formateur" type="text" name="formateur" value="<?= e($_POST['formateur'] ?? '') ?>" placeholder="Ex : Session avec votre formateur">
                    </div>

                    <div class="live-champs-duo">
                        <div class="live-champ">
                            <label for="date_live">Date</label>
                            <input id="date_live" type="date" name="date_live" value="<?= e($_POST['date_live'] ?? '') ?>" required>
                        </div>
                        <div class="live-champ">
                            <label for="heure_live">Heure</label>
                            <input id="heure_live" type="time" name="heure_live" value="<?= e($_POST['heure_live'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="live-champs-duo">
                        <div class="live-champ">
                            <label for="duree">Durée</label>
                            <input id="duree" type="text" name="duree" value="<?= e($_POST['duree'] ?? '') ?>" placeholder="Ex : 1 h 30">
                        </div>
                        <div class="live-champ">
                            <label for="lien_live">Lien de la session</label>
                            <input id="lien_live" type="url" name="lien_live" value="<?= e($_POST['lien_live'] ?? '') ?>" placeholder="https://meet...">
                        </div>
                    </div>

                    <div class="live-form-actions">
                        <a class="live-btn-annuler" href="lives.php">Annuler</a>
                        <button class="live-btn-valider" type="submit">
                            <i class="fa-solid fa-check"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </article>
    </section>
</div>

<?php
$active_page = 'lives.php';
include 'menu.php';
?>

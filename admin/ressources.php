<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../includes/ressources.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tounSansAccents(string $texte): string
{
    $texte = mb_strtolower($texte, 'UTF-8');

    return strtr($texte, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c']);
}

tounPreparerRessources($con);

$formations = $con->query('SELECT id_form, titre_form FROM t_formations ORDER BY titre_form')->fetchAll(PDO::FETCH_KEY_PAIR);
$modules = [];

foreach ($con->query('SELECT id_module, id_form, titre_module, ordre_module FROM t_modules ORDER BY id_form, ordre_module, id_module')->fetchAll(PDO::FETCH_ASSOC) as $module) {
    $modules[(int) $module['id_form']][(int) $module['id_module']] = $module;
}
$types = ['pdf' => 'PDF', 'doc' => 'Document Word / Excel / PowerPoint', 'video' => 'Vidéo', 'lien' => 'Lien internet', 'autre' => 'Autre'];
$extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'mp4', 'zip', 'png', 'jpg', 'jpeg'];
$dossier = __DIR__ . '/../uploads/ressources';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $idForm = (int) ($_POST['id_form'] ?? 0);
    $idModule = (int) ($_POST['id_module'] ?? 0);
    $titre = trim((string) ($_POST['titre'] ?? ''));
    $type = array_key_exists($_POST['type'] ?? '', $types) ? $_POST['type'] : 'autre';
    $lien = trim((string) ($_POST['lien'] ?? ''));
    $fichier = '';

    if (!isset($formations[$idForm]) || $titre === '') {
        $erreur = 'Choisissez une formation et donnez un titre à la ressource.';
    } elseif ($idModule > 0 && !isset($modules[$idForm][$idModule])) {
        $erreur = 'Ce module n’appartient pas à la formation choisie.';
    } elseif (!empty($_FILES['fichier']['name'])) {
        $extension = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));

        if ($_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $erreur = 'Le fichier n’a pas pu être envoyé. Vérifiez sa taille.';
        } elseif (!in_array($extension, $extensions, true)) {
            $erreur = 'Ce type de fichier n’est pas accepté.';
        } else {
            if (!is_dir($dossier)) {
                mkdir($dossier, 0775, true);
            }

            $fichier = 'ressource_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

            if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $dossier . '/' . $fichier)) {
                $erreur = 'Le fichier n’a pas pu être enregistré.';
            }
        }
    } elseif (!preg_match('#^https?://#i', $lien)) {
        $erreur = 'Ajoutez un fichier ou un lien commençant par http.';
    }

    if ($erreur === '') {
        $stmt = $con->prepare('INSERT INTO t_ressources (id_form, id_module, titre_ressource, type_ressource, fichier_ressource, lien_ressource) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$idForm, $idModule > 0 ? $idModule : null, $titre, $type, $fichier, $fichier !== '' ? '' : $lien]);
        $_SESSION['flash_success'] = 'Ressource ajoutée. Les apprenants de cette formation la voient dans « Ressources ».';
        header('Location: ressources.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer'])) {
    $stmt = $con->prepare('SELECT fichier_ressource FROM t_ressources WHERE id_ressource = ?');
    $stmt->execute([(int) $_POST['supprimer']]);
    $fichier = (string) $stmt->fetchColumn();

    if ($fichier !== '' && is_file($dossier . '/' . basename($fichier))) {
        unlink($dossier . '/' . basename($fichier));
    }

    $con->prepare('DELETE FROM t_ressources WHERE id_ressource = ?')->execute([(int) $_POST['supprimer']]);
    $_SESSION['flash_success'] = 'Ressource supprimée.';
    header('Location: ressources.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conseillees'])) {
    $conseillees = json_decode((string) @file_get_contents(__DIR__ . '/../includes/ressources_conseillees.json'), true)['ressources'] ?? [];
    $existe = $con->prepare('SELECT id_ressource, id_module FROM t_ressources WHERE id_form = ? AND lien_ressource = ? LIMIT 1');
    $ajout = $con->prepare('INSERT INTO t_ressources (id_form, id_module, titre_ressource, type_ressource, lien_ressource) VALUES (?, ?, ?, ?, ?)');
    $rattache = $con->prepare('UPDATE t_ressources SET id_module = ? WHERE id_ressource = ?');
    $ajoutees = 0;
    $rattachees = 0;

    foreach ($conseillees as $ressource) {
        foreach ($formations as $idForm => $titreForm) {
            if (mb_strpos(tounSansAccents($titreForm), tounSansAccents($ressource['formation'])) === false) {
                continue;
            }

            $idModule = null;

            foreach ($modules[(int) $idForm] ?? [] as $module) {
                if ((int) $module['ordre_module'] === (int) ($ressource['module'] ?? 0)) {
                    $idModule = (int) $module['id_module'];
                }
            }

            $existe->execute([$idForm, $ressource['lien']]);
            $deja = $existe->fetch(PDO::FETCH_ASSOC);

            if (!$deja) {
                $ajout->execute([$idForm, $idModule, $ressource['titre'], $ressource['type'], $ressource['lien']]);
                $ajoutees++;
            } elseif ($idModule !== null && empty($deja['id_module'])) {
                $rattache->execute([$idModule, $deja['id_ressource']]);
                $rattachees++;
            }
        }
    }

    $_SESSION['flash_success'] = $ajoutees > 0
        ? $ajoutees . ' document(s) conseillé(s) ajouté(s) dans les modules des cours.'
        : 'Les documents conseillés étaient déjà ajoutés.';

    if ($rattachees > 0) {
        $_SESSION['flash_success'] .= ' ' . $rattachees . ' document(s) rattaché(s) à leur module.';
    }
    header('Location: ressources.php');
    exit();
}

$rows = $con->query('
    SELECT r.*, f.titre_form, m.titre_module, m.ordre_module
    FROM t_ressources r
    LEFT JOIN t_formations f ON f.id_form = r.id_form
    LEFT JOIN t_modules m ON m.id_module = r.id_module
    ORDER BY f.titre_form ASC, m.ordre_module ASC, r.date_ajout DESC
')->fetchAll(PDO::FETCH_ASSOC);

$message = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
?>

<style>
    .res-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .res-hero {
        min-height: 94px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 17px 21px;
        margin-bottom: 13px;
        border-radius: 18px;
        background: linear-gradient(110deg, #18213f, #0b5a91 60%, #20a1b8);
        color: #fff;
        box-shadow: 0 9px 22px rgba(24, 33, 63, 0.1);
    }

    .res-hero h1 {
        margin: 0;
        font-size: 21px;
    }

    .res-hero p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.72);
        font-size: 11px;
    }

    .res-hero form {
        margin: 0;
    }

    .res-bouton {
        height: 35px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 14px;
        border: 0;
        border-radius: 9px;
        background: #fff;
        color: #18213f;
        font-family: inherit;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }

    .res-message,
    .res-erreur {
        margin-bottom: 13px;
        padding: 11px 14px;
        border-radius: 11px;
        font-size: 11.5px;
    }

    .res-message {
        background: #e5f6f7;
        color: #158090;
    }

    .res-erreur {
        background: #fdebec;
        color: #c34a4a;
    }

    .res-panel {
        margin-bottom: 13px;
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .res-panel h2 {
        margin: 0;
        padding: 14px 16px 0;
        color: #18213f;
        font-size: 14px;
    }

    .res-form {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        padding: 14px 16px 16px;
    }

    .res-champ {
        display: flex;
        flex-direction: column;
        gap: 5px;
        color: #536a7f;
        font-size: 10.5px;
        font-weight: 600;
    }

    .res-champ input,
    .res-champ select {
        height: 36px;
        padding: 0 10px;
        border: 1px solid #dce7ed;
        border-radius: 9px;
        background: #f7fafb;
        color: #18213f;
        font-family: inherit;
        font-size: 11.5px;
    }

    .res-champ input[type="file"] {
        padding: 7px 10px;
    }

    .res-champ small {
        color: #8996a5;
        font-weight: 400;
    }

    .res-valider {
        align-self: end;
        height: 36px;
        border: 0;
        border-radius: 9px;
        background: #0b5a91;
        color: #fff;
        font-family: inherit;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
    }

    .res-table-wrap {
        overflow-x: auto;
    }

    .res-table {
        width: 100%;
        border-collapse: collapse;
    }

    .res-table th {
        padding: 10px 13px;
        background: #e5eff3;
        color: #536a7f;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .res-table td {
        padding: 11px 13px;
        border-top: 1px solid #ecf1f4;
        color: #45566a;
        font-size: 11px;
    }

    .res-table td strong {
        color: #18213f;
    }

    .res-lien {
        color: #0b5a91;
        text-decoration: none;
    }

    .res-supprimer {
        width: 28px;
        height: 28px;
        border: 0;
        border-radius: 7px;
        background: #fdebec;
        color: #c34a4a;
        cursor: pointer;
    }

    .res-vide {
        padding: 45px 20px;
        background: #f7fafb;
        color: #8996a5;
        text-align: center;
        font-size: 11px;
    }

    .res-vide i {
        display: block;
        margin-bottom: 8px;
        color: #20a1b8;
        font-size: 27px;
    }

    @media (max-width: 800px) {
        .res-page {
            padding: 13px 10px 20px;
        }

        .res-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .res-form {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main">
    <section class="res-page">
        <header class="res-hero">
            <div>
                <h1>Ressources</h1>
                <p>Ajoutez des documents et des liens utiles. Les apprenants inscrits les retrouvent dans « Ressources ».</p>
            </div>
            <form method="post">
                <button type="submit" name="conseillees" class="res-bouton">
                    <i class="fa-solid fa-book-bookmark"></i>
                    Ajouter les documents conseillés
                </button>
            </form>
        </header>

        <?php if ($message !== ''): ?>
            <div class="res-message"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($erreur !== ''): ?>
            <div class="res-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <section class="res-panel">
            <h2>Nouvelle ressource</h2>
            <form method="post" enctype="multipart/form-data" class="res-form">
                <label class="res-champ">
                    Formation
                    <select name="id_form" id="resFormation" required>
                        <option value="">Choisir…</option>
                        <?php foreach ($formations as $idForm => $titreForm): ?>
                            <option value="<?= (int) $idForm ?>"><?= e($titreForm) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="res-champ">
                    Module du cours
                    <select name="id_module" id="resModule">
                        <option value="">Toute la formation</option>
                        <?php foreach ($modules as $idFormModule => $listeModules): ?>
                            <?php foreach ($listeModules as $module): ?>
                                <option value="<?= (int) $module['id_module'] ?>" data-formation="<?= (int) $idFormModule ?>" hidden>
                                    Module <?= (int) $module['ordre_module'] ?> : <?= e($module['titre_module']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="res-champ">
                    Titre
                    <input type="text" name="titre" maxlength="180" required>
                </label>
                <label class="res-champ">
                    Type
                    <select name="type">
                        <?php foreach ($types as $valeur => $libelle): ?>
                            <option value="<?= e($valeur) ?>"><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="res-champ">
                    Fichier
                    <input type="file" name="fichier">
                    <small>PDF, Word, Excel, PowerPoint, vidéo…</small>
                </label>
                <label class="res-champ">
                    Ou lien internet
                    <input type="url" name="lien" placeholder="https://">
                </label>
                <button type="submit" name="ajouter" class="res-valider">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter la ressource
                </button>
            </form>
        </section>

        <section class="res-panel">
            <div class="res-table-wrap">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Formation</th>
                            <th>Module</th>
                            <th>Type</th>
                            <th>Ajoutée le</th>
                            <th>Ouvrir</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="res-vide">
                                        <i class="fa-solid fa-folder-open"></i>
                                        Aucune ressource pour le moment.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php $lien = tounLienRessource($row); ?>
                                <tr>
                                    <td><strong><?= e($row['titre_ressource']) ?></strong></td>
                                    <td><?= e($row['titre_form'] ?? '—') ?></td>
                                    <td><?= $row['titre_module'] ? 'Module ' . (int) $row['ordre_module'] . ' : ' . e($row['titre_module']) : 'Toute la formation' ?></td>
                                    <td><?= e($types[$row['type_ressource']] ?? $row['type_ressource']) ?></td>
                                    <td><?= e(date('d/m/Y', strtotime($row['date_ajout']))) ?></td>
                                    <td>
                                        <a class="res-lien" href="<?= e($lien) ?>" target="_blank" rel="noopener">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Ouvrir
                                        </a>
                                    </td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Supprimer cette ressource ?');">
                                            <button type="submit" name="supprimer" value="<?= (int) $row['id_ressource'] ?>" class="res-supprimer" title="Supprimer">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>

<script>
    const resFormation = document.getElementById('resFormation');
    const resModule = document.getElementById('resModule');

    resFormation.addEventListener('change', function () {
        resModule.value = '';
        resModule.querySelectorAll('option[data-formation]').forEach(function (option) {
            option.hidden = option.dataset.formation !== resFormation.value;
        });
    });
</script>

<?php
$active_page = 'ressources.php';
include 'menu.php';
?>

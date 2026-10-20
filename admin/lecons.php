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

$idModule = (int) ($_GET['module'] ?? 0);

$stmtModule = $con->prepare("
    SELECT m.id_module, m.id_form, m.titre_module, f.titre_form
    FROM t_modules m
    INNER JOIN t_formations f ON f.id_form = m.id_form
    WHERE m.id_module = ?
    LIMIT 1
");

$stmtModule->execute([$idModule]);
$module = $stmtModule->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    header('Location: modules.php');
    exit();
}

$types = [
    'texte' => 'Texte',
    'video' => 'Vidéo',
    'pdf' => 'Document PDF',
    'lien' => 'Lien internet',
];

$formats = [
    'video' => ['mp4' => 'video/mp4', 'webm' => 'video/webm'],
    'pdf' => ['pdf' => 'application/pdf'],
];

$erreur = '';
$saisie = ['titre' => '', 'description' => '', 'type' => 'texte', 'lien' => '', 'duree' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'supprimer') {
        $idLecon = (int) ($_POST['id_lecon'] ?? 0);

        $stmt = $con->prepare("SELECT type_contenu, contenu_lecon FROM t_lecons WHERE id_lecon = ? AND id_module = ?");
        $stmt->execute([$idLecon, $idModule]);
        $lecon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lecon) {
            $stmt = $con->prepare("DELETE FROM t_lecons WHERE id_lecon = ?");
            $stmt->execute([$idLecon]);

            $chemin = __DIR__ . '/../' . ltrim((string) $lecon['contenu_lecon'], '/');

            if (in_array($lecon['type_contenu'], ['video', 'pdf'], true) && strpos((string) $lecon['contenu_lecon'], 'uploads/lecons/') === 0 && is_file($chemin)) {
                unlink($chemin);
            }

            $_SESSION['flash_success'] = 'La leçon a été supprimée.';
        }

        header('Location: lecons.php?module=' . $idModule);
        exit();
    }

    if ($action === 'ajouter') {
        $saisie = [
            'titre' => trim((string) ($_POST['titre'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'type' => array_key_exists($_POST['type'] ?? '', $types) ? $_POST['type'] : 'texte',
            'lien' => trim((string) ($_POST['lien'] ?? '')),
            'duree' => trim((string) ($_POST['duree'] ?? '')),
        ];

        $contenu = null;

        try {
            if ($saisie['titre'] === '') {
                throw new RuntimeException('Veuillez donner un titre à la leçon.');
            }

            if ($saisie['type'] === 'texte' && $saisie['description'] === '') {
                throw new RuntimeException('Veuillez écrire le texte de la leçon.');
            }

            if ($saisie['type'] === 'lien') {
                if (!filter_var($saisie['lien'], FILTER_VALIDATE_URL)) {
                    throw new RuntimeException('Veuillez saisir un lien valide (qui commence par https://).');
                }

                $contenu = mb_substr($saisie['lien'], 0, 255);
            }

            if (isset($formats[$saisie['type']])) {
                $fichier = $_FILES['fichier'] ?? null;

                if (!$fichier || ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Veuillez choisir le fichier de la leçon.');
                }

                $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

                if (!isset($formats[$saisie['type']][$extension])) {
                    throw new RuntimeException('Format accepté : ' . strtoupper(implode(', ', array_keys($formats[$saisie['type']]))) . '.');
                }

                $dossier = __DIR__ . '/../uploads/lecons';

                if (!is_dir($dossier) && !mkdir($dossier, 0775, true)) {
                    throw new RuntimeException('Le dossier des leçons est inaccessible.');
                }

                $nom = 'lecon_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

                if (!move_uploaded_file($fichier['tmp_name'], $dossier . '/' . $nom)) {
                    throw new RuntimeException('Impossible d’enregistrer le fichier.');
                }

                $contenu = 'uploads/lecons/' . $nom;
            }

            $stmtOrdre = $con->prepare("SELECT COALESCE(MAX(ordre_lecon), 0) + 1 FROM t_lecons WHERE id_module = ?");
            $stmtOrdre->execute([$idModule]);
            $ordre = (int) $stmtOrdre->fetchColumn();

            $stmt = $con->prepare("
                INSERT INTO t_lecons (id_module, titre_lecon, description_lecon, type_contenu, contenu_lecon, ordre_lecon, duree_lecon)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $idModule,
                $saisie['titre'],
                $saisie['description'] !== '' ? $saisie['description'] : null,
                $saisie['type'],
                $contenu,
                $ordre,
                $saisie['duree'] !== '' ? $saisie['duree'] : null,
            ]);

            $_SESSION['flash_success'] = 'La leçon a été ajoutée.';
            header('Location: lecons.php?module=' . $idModule);
            exit();
        } catch (Throwable $e) {
            $erreur = $e->getMessage();
        }
    }
}

$stmtLecons = $con->prepare("
    SELECT id_lecon, titre_lecon, type_contenu, ordre_lecon, duree_lecon
    FROM t_lecons
    WHERE id_module = ?
    ORDER BY ordre_lecon ASC, id_lecon ASC
");

$stmtLecons->execute([$idModule]);
$lecons = $stmtLecons->fetchAll(PDO::FETCH_ASSOC);

$icones = [
    'texte' => 'fa-file-lines',
    'video' => 'fa-circle-play',
    'pdf' => 'fa-file-pdf',
    'lien' => 'fa-link',
];
?>

<style>
    :root {
        --lc-nuit: #18213f;
        --lc-bleu: #0b5a91;
        --lc-turquoise: #20a1b8;
        --lc-pale: #eaf3f9;
        --lc-bordure: #dce7ed;
        --lc-doux: #6f8094;
        --lc-rouge: #b43d3d;
    }

    .lc-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .lc-entete {
        padding: 16px 21px;
        margin-bottom: 14px;
        border-radius: 15px;
        background: var(--lc-nuit);
        color: #fff;
    }

    .lc-entete h1 {
        margin: 0;
        font-size: 18px;
    }

    .lc-entete p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 11.5px;
    }

    .lc-grille {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 14px;
        align-items: start;
    }

    .lc-carte {
        padding: 16px;
        border: 1px solid var(--lc-bordure);
        border-radius: 14px;
        background: #fff;
    }

    .lc-carte h2 {
        margin: 0 0 12px;
        color: var(--lc-nuit);
        font-size: 13px;
    }

    .lc-vide {
        padding: 20px;
        color: var(--lc-doux);
        font-size: 11.5px;
        text-align: center;
    }

    .lc-lecon {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 10px 0;
        border-top: 1px solid var(--lc-bordure);
    }

    .lc-lecon:first-of-type {
        border-top: 0;
    }

    .lc-lecon__icone {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        display: grid;
        place-items: center;
        border-radius: 8px;
        background: var(--lc-pale);
        color: var(--lc-bleu);
    }

    .lc-lecon__texte {
        flex: 1;
        min-width: 0;
    }

    .lc-lecon__titre {
        margin: 0;
        color: var(--lc-nuit);
        font-size: 12px;
        font-weight: 600;
    }

    .lc-lecon__infos {
        margin: 2px 0 0;
        color: var(--lc-doux);
        font-size: 11px;
    }

    .lc-supprimer {
        width: 30px;
        height: 30px;
        border: 1px solid #f1d0d0;
        border-radius: 8px;
        background: #fff6f6;
        color: var(--lc-rouge);
        cursor: pointer;
    }

    .lc-alerte {
        padding: 9px 11px;
        margin-bottom: 11px;
        border: 1px solid #f1d0d0;
        border-radius: 8px;
        background: #fff1f1;
        color: var(--lc-rouge);
        font-size: 11.5px;
    }

    .lc-champ {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 11px;
    }

    .lc-champ label {
        color: #53647a;
        font-size: 11px;
        font-weight: 600;
    }

    .lc-champ input,
    .lc-champ select,
    .lc-champ textarea {
        width: 100%;
        min-height: 37px;
        padding: 8px 10px;
        border: 1px solid #d3e1e7;
        border-radius: 8px;
        outline: 0;
        background: #f9fcfd;
        color: var(--lc-nuit);
        font: 11.5px "Poppins", sans-serif;
        box-sizing: border-box;
    }

    .lc-champ textarea {
        min-height: 120px;
        resize: vertical;
    }

    .lc-champ--cache {
        display: none;
    }

    .lc-actions {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-top: 14px;
    }

    .lc-bouton {
        height: 36px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 13px;
        border-radius: 8px;
        font: 600 11px "Poppins", sans-serif;
        text-decoration: none;
        cursor: pointer;
    }

    .lc-bouton--retour {
        border: 1px solid #d3e0e6;
        background: #fff;
        color: #5b6a7d;
    }

    .lc-bouton--ajouter {
        border: 0;
        background: var(--lc-bleu);
        color: #fff;
    }

    @media (max-width: 900px) {
        .lc-page {
            padding: 13px 10px 20px;
        }

        .lc-grille {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main">
    <section class="lc-page">
        <header class="lc-entete">
            <h1>Leçons : <?= e($module['titre_module']) ?></h1>
            <p><?= e($module['titre_form']) ?></p>
        </header>

        <div class="lc-grille">
            <section class="lc-carte">
                <h2><?= count($lecons) ?> leçon<?= count($lecons) > 1 ? 's' : '' ?></h2>

                <?php if (!$lecons): ?>
                    <div class="lc-vide">Aucune leçon pour ce module. Ajoutez la première avec le formulaire.</div>
                <?php endif; ?>

                <?php foreach ($lecons as $lecon): ?>
                    <article class="lc-lecon">
                        <div class="lc-lecon__icone">
                            <i class="fa-solid <?= e($icones[$lecon['type_contenu']] ?? 'fa-file-lines') ?>"></i>
                        </div>

                        <div class="lc-lecon__texte">
                            <p class="lc-lecon__titre"><?= (int) $lecon['ordre_lecon'] ?>. <?= e($lecon['titre_lecon']) ?></p>
                            <p class="lc-lecon__infos">
                                <?= e($types[$lecon['type_contenu']] ?? 'Texte') ?>
                                <?= !empty($lecon['duree_lecon']) ? ' · ' . e($lecon['duree_lecon']) : '' ?>
                            </p>
                        </div>

                        <form method="post" onsubmit="return confirm('Supprimer cette leçon ?');">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id_lecon" value="<?= (int) $lecon['id_lecon'] ?>">
                            <button type="submit" class="lc-supprimer" aria-label="Supprimer la leçon">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="lc-carte">
                <h2>Ajouter une leçon</h2>

                <?php if ($erreur !== ''): ?>
                    <div class="lc-alerte"><?= e($erreur) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="ajouter">

                    <div class="lc-champ">
                        <label for="titre">Titre de la leçon</label>
                        <input id="titre" type="text" name="titre" value="<?= e($saisie['titre']) ?>" required>
                    </div>

                    <div class="lc-champ">
                        <label for="type">Type de contenu</label>
                        <select id="type" name="type">
                            <?php foreach ($types as $valeur => $libelle): ?>
                                <option value="<?= e($valeur) ?>" <?= $saisie['type'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-champ">
                        <label for="description">Texte de la leçon</label>
                        <textarea id="description" name="description"><?= e($saisie['description']) ?></textarea>
                    </div>

                    <div class="lc-champ lc-champ--cache" id="champFichier">
                        <label for="fichier">Fichier (vidéo MP4 ou document PDF)</label>
                        <input id="fichier" type="file" name="fichier" accept=".mp4,.webm,.pdf">
                    </div>

                    <div class="lc-champ lc-champ--cache" id="champLien">
                        <label for="lien">Lien</label>
                        <input id="lien" type="url" name="lien" placeholder="https://" value="<?= e($saisie['lien']) ?>">
                    </div>

                    <div class="lc-champ">
                        <label for="duree">Durée</label>
                        <input id="duree" type="text" name="duree" placeholder="Ex. 10 min" value="<?= e($saisie['duree']) ?>">
                    </div>

                    <div class="lc-actions">
                        <a class="lc-bouton lc-bouton--retour" href="modules.php?id_form=<?= (int) $module['id_form'] ?>">
                            <i class="fa-solid fa-arrow-left"></i>
                            Retour aux modules
                        </a>
                        <button type="submit" class="lc-bouton lc-bouton--ajouter">
                            <i class="fa-solid fa-plus"></i>
                            Ajouter la leçon
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</div>

<script>
    const choixType = document.getElementById('type');
    const champFichier = document.getElementById('champFichier');
    const champLien = document.getElementById('champLien');

    const afficherChamps = function () {
        champFichier.style.display = ['video', 'pdf'].includes(choixType.value) ? 'flex' : 'none';
        champLien.style.display = choixType.value === 'lien' ? 'flex' : 'none';
    };

    choixType.addEventListener('change', afficherChamps);
    afficherChamps();
</script>

<?php
$active_page = 'modules.php';
include 'menu.php';
?>

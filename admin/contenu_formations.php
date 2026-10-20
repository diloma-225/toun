<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../includes/certificats.php';

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

    return strtr($texte, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c', '’' => "'"]);
}

function tounChargerProgrammes(): array
{
    $programmes = [];

    foreach (glob(__DIR__ . '/../includes/contenu_cours/*.json') ?: [] as $fichier) {
        $programme = json_decode((string) file_get_contents($fichier), true);

        if (is_array($programme) && !empty($programme['cle'])) {
            $programmes[] = $programme;
        }
    }

    return $programmes;
}

function tounProgrammePour(string $titre, array $programmes): ?array
{
    $titre = tounSansAccents($titre);

    foreach ($programmes as $programme) {
        if (mb_strpos($titre, tounSansAccents($programme['cle'])) !== false) {
            return $programme;
        }
    }

    return null;
}

function tounModulesFormation(PDO $con, int $idForm): array
{
    $stmt = $con->prepare('SELECT id_module, titre_module FROM t_modules WHERE id_form = ? ORDER BY ordre_module ASC, id_module ASC');
    $stmt->execute([$idForm]);

    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function tounRemplacable(array $modulesActuels, array $programme): bool
{
    if (!$modulesActuels) {
        return true;
    }

    $titresConnus = array_map('tounSansAccents', array_merge(
        $programme['anciens_titres'] ?? [],
        array_column($programme['modules'], 'titre')
    ));

    foreach ($modulesActuels as $titre) {
        if (!in_array(tounSansAccents((string) $titre), $titresConnus, true)) {
            return false;
        }
    }

    return true;
}

function tounSupprimerContenu(PDO $con, int $idForm, array $idsModules): void
{
    if (!$idsModules) {
        return;
    }

    $marques = implode(',', array_fill(0, count($idsModules), '?'));

    $stmt = $con->prepare("SELECT id_quiz FROM t_quiz WHERE id_module IN ($marques)");
    $stmt->execute($idsModules);
    $idsQuiz = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($idsQuiz) {
        $marquesQuiz = implode(',', array_fill(0, count($idsQuiz), '?'));
        $con->prepare("DELETE FROM t_reponses WHERE id_question IN (SELECT id_question FROM t_questions WHERE id_quiz IN ($marquesQuiz))")->execute($idsQuiz);
        $con->prepare("DELETE FROM t_questions WHERE id_quiz IN ($marquesQuiz)")->execute($idsQuiz);
        $con->prepare("DELETE FROM t_resultats_quiz WHERE id_quiz IN ($marquesQuiz)")->execute($idsQuiz);
        $con->prepare("DELETE FROM t_quiz WHERE id_quiz IN ($marquesQuiz)")->execute($idsQuiz);
    }

    $con->prepare("DELETE FROM t_suivi_contenus WHERE id_form = ?")->execute([$idForm]);
    $con->prepare("DELETE FROM t_certificats WHERE id_form = ?")->execute([$idForm]);
    $con->prepare("DELETE FROM t_lecons WHERE id_module IN ($marques)")->execute($idsModules);
    $con->prepare("DELETE FROM t_modules WHERE id_module IN ($marques)")->execute($idsModules);
}

function tounPreparerTypeQuiz(PDO $con): void
{
    if (!in_array('type_quiz', tounColonnes($con, 't_quiz'), true)) {
        $con->exec("ALTER TABLE t_quiz ADD type_quiz VARCHAR(10) NOT NULL DEFAULT 'final'");
    }
}

function tounAjouterQuiz(PDO $con, int $idModule, string $titre, string $description, array $questions, string $type): void
{
    $stmt = $con->prepare('INSERT INTO t_quiz (id_module, titre_quiz, description_quiz, note_minimale, type_quiz) VALUES (?, ?, ?, 10, ?)');
    $stmt->execute([$idModule, $titre, $description, $type]);
    $idQuiz = (int) $con->lastInsertId();

    $stmtQuestion = $con->prepare('INSERT INTO t_questions (id_quiz, libelle_question, ordre_question) VALUES (?, ?, ?)');
    $stmtReponse = $con->prepare('INSERT INTO t_reponses (id_question, libelle_reponse, est_correcte) VALUES (?, ?, ?)');

    foreach ($questions as $indexQuestion => $question) {
        $stmtQuestion->execute([$idQuiz, $question['q'], $indexQuestion + 1]);
        $idQuestion = (int) $con->lastInsertId();

        foreach ($question['reponses'] as $indexReponse => $reponse) {
            $stmtReponse->execute([$idQuestion, $reponse, $indexReponse === (int) $question['bonne'] ? 1 : 0]);
        }
    }
}

function tounAjouterTestModule(PDO $con, int $idModule, int $numero, array $module): void
{
    if (empty($module['test'])) {
        return;
    }

    tounAjouterQuiz(
        $con,
        $idModule,
        'Test du module ' . $numero . ' : ' . $module['titre'],
        'Vérifiez vos acquis sur ce module avant de passer au suivant.',
        $module['test'],
        'module'
    );
}

function tounTestsManquants(PDO $con, int $idForm, array $programme): array
{
    $manquants = [];

    if (!in_array('type_quiz', tounColonnes($con, 't_quiz'), true)) {
        foreach (array_keys(tounModulesFormation($con, $idForm)) as $index => $idModule) {
            if (!empty($programme['modules'][$index]['test'])) {
                $manquants[(int) $idModule] = $index;
            }
        }

        return $manquants;
    }

    $stmt = $con->prepare("SELECT COUNT(*) FROM t_quiz WHERE id_module = ? AND type_quiz = 'module'");

    foreach (array_keys(tounModulesFormation($con, $idForm)) as $index => $idModule) {
        if (empty($programme['modules'][$index]['test'])) {
            continue;
        }

        $stmt->execute([$idModule]);

        if ((int) $stmt->fetchColumn() === 0) {
            $manquants[(int) $idModule] = $index;
        }
    }

    return $manquants;
}

function tounInstallerProgramme(PDO $con, int $idForm, array $programme): array
{
    $stmtModule = $con->prepare('INSERT INTO t_modules (id_form, titre_module, description_module, ordre_module, duree_module) VALUES (?, ?, ?, ?, ?)');
    $stmtLecon = $con->prepare('INSERT INTO t_lecons (id_module, titre_lecon, description_lecon, type_contenu, contenu_lecon, ordre_lecon, duree_lecon) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $idsLecons = [];
    $dernierModule = 0;

    foreach ($programme['modules'] as $indexModule => $module) {
        $stmtModule->execute([$idForm, $module['titre'], $module['description'], $indexModule + 1, $module['duree']]);
        $dernierModule = (int) $con->lastInsertId();
        tounAjouterTestModule($con, $dernierModule, $indexModule + 1, $module);

        foreach ($module['lecons'] as $indexLecon => $lecon) {
            $stmtLecon->execute([
                $dernierModule,
                $lecon['titre'],
                $lecon['texte'],
                $lecon['type'],
                $lecon['fichier'] ?: null,
                $indexLecon + 1,
                $lecon['duree'],
            ]);
            $idsLecons[] = (int) $con->lastInsertId();
        }
    }

    tounAjouterQuiz($con, $dernierModule, $programme['titre_quiz'], $programme['description_quiz'], $programme['questions'], 'final');

    return $idsLecons;
}

function tounReporterAvancement(PDO $con, int $idForm, array $avancements, array $idsLecons): void
{
    $stmt = $con->prepare("INSERT IGNORE INTO t_suivi_contenus (id_user, id_form, type_element, id_element) VALUES (?, ?, 'lecon', ?)");

    foreach ($avancements as $idUser => $pourcent) {
        $nombre = (int) round(count($idsLecons) * $pourcent / 100);

        foreach (array_slice($idsLecons, 0, $nombre) as $idLecon) {
            $stmt->execute([$idUser, $idForm, $idLecon]);
        }

        tounEnregistrerProgression($con, (int) $idUser, $idForm, tounPourcentCours($con, (int) $idUser, $idForm));
    }
}

$programmes = tounChargerProgrammes();

$formations = $con->query("
    SELECT
        f.id_form,
        f.titre_form,
        (SELECT COUNT(*) FROM t_modules m WHERE m.id_form = f.id_form) AS nb_modules,
        (SELECT COUNT(*) FROM t_lecons l INNER JOIN t_modules m ON m.id_module = l.id_module WHERE m.id_form = f.id_form) AS nb_lecons,
        (SELECT COUNT(*) FROM t_questions qu INNER JOIN t_quiz q ON q.id_quiz = qu.id_quiz INNER JOIN t_modules m ON m.id_module = q.id_module WHERE m.id_form = f.id_form) AS nb_questions
    FROM t_formations f
    ORDER BY f.titre_form
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($formations as &$formation) {
    $programme = tounProgrammePour($formation['titre_form'], $programmes);
    $modulesActuels = tounModulesFormation($con, (int) $formation['id_form']);
    $formation['programme'] = $programme;
    $formation['etat'] = !$programme
        ? 'Aucun cours prévu'
        : (!tounRemplacable($modulesActuels, $programme)
            ? 'Contenu personnalisé conservé'
            : (count($modulesActuels) === count($programme['modules']) && (int) $formation['nb_lecons'] > 0 && array_values($modulesActuels) === array_column($programme['modules'], 'titre')
                ? (tounTestsManquants($con, (int) $formation['id_form'], $programme) ? 'Tests de module à ajouter' : 'Cours TOUN installés')
                : 'Prêt à installer'));
}

unset($formation);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['installer'])) {
    $installees = 0;
    $testsAjoutes = 0;
    tounPreparerTypeQuiz($con);

    foreach ($formations as $formation) {
        if ($formation['etat'] === 'Tests de module à ajouter') {
            foreach (tounTestsManquants($con, (int) $formation['id_form'], $formation['programme']) as $idModule => $index) {
                tounAjouterTestModule($con, $idModule, $index + 1, $formation['programme']['modules'][$index]);
                $testsAjoutes++;
            }

            continue;
        }

        if ($formation['etat'] !== 'Prêt à installer') {
            continue;
        }

        $idForm = (int) $formation['id_form'];
        $modulesActuels = tounModulesFormation($con, $idForm);

        $stmt = $con->prepare('SELECT id_user FROM t_inscriptions WHERE id_form = ?');
        $stmt->execute([$idForm]);
        $avancements = [];

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idUser) {
            $pourcent = tounPourcentCours($con, (int) $idUser, $idForm);

            if ($pourcent === 0) {
                $stmtP = $con->prepare('SELECT pourcent_avancement FROM t_progression WHERE id_user = ? AND id_form = ? LIMIT 1');
                $stmtP->execute([$idUser, $idForm]);
                $pourcent = (int) $stmtP->fetchColumn();
            }

            $avancements[(int) $idUser] = min(100, $pourcent);
        }

        tounSupprimerContenu($con, $idForm, array_map('intval', array_keys($modulesActuels)));
        $idsLecons = tounInstallerProgramme($con, $idForm, $formation['programme']);
        tounReporterAvancement($con, $idForm, $avancements, $idsLecons);
        $installees++;
    }

    $corrigees = tounCorrigerProgressions($con);

    $_SESSION['flash_success'] = $installees > 0
        ? $installees . ' formation(s) remplie(s) avec les cours TOUN et leurs tests de module.'
        : 'Les cours étaient déjà installés.';

    if ($testsAjoutes > 0) {
        $_SESSION['flash_success'] .= ' ' . $testsAjoutes . ' test(s) de module ajouté(s).';
    }

    if ($corrigees > 0) {
        $_SESSION['flash_success'] .= ' ' . $corrigees . ' progression(s) remise(s) à 90 % en attendant l’évaluation.';
    }

    header('Location: contenu_formations.php');
    exit();
}

$message = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
?>

<style>
    .cf-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .cf-entete {
        padding: 16px 21px;
        margin-bottom: 14px;
        border-radius: 15px;
        background: #18213f;
        color: #fff;
    }

    .cf-entete h1 {
        margin: 0;
        font-size: 18px;
    }

    .cf-entete p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 11.5px;
    }

    .cf-carte {
        max-width: 900px;
        padding: 16px;
        border: 1px solid #dce7ed;
        border-radius: 14px;
        background: #fff;
    }

    .cf-message {
        padding: 10px 12px;
        margin-bottom: 12px;
        border-radius: 8px;
        background: #e7f7f1;
        color: #15805f;
        font-size: 12px;
    }

    .cf-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .cf-table th,
    .cf-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #e6eef2;
        text-align: left;
    }

    .cf-table th {
        color: #6f8094;
        font-size: 11px;
        font-weight: 600;
    }

    .cf-table td {
        color: #18213f;
    }

    .cf-ok {
        color: #1e8e5a;
        font-weight: 600;
    }

    .cf-manque {
        color: #b43d3d;
        font-weight: 600;
    }

    .cf-note {
        margin: 12px 0 0;
        color: #6f8094;
        font-size: 11.5px;
        line-height: 1.6;
    }

    .cf-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 14px;
    }

    .cf-bouton {
        height: 38px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 15px;
        border: 0;
        border-radius: 8px;
        background: #0b5a91;
        color: #fff;
        font: 600 11.5px "Poppins", sans-serif;
        cursor: pointer;
    }

    @media (max-width: 700px) {
        .cf-page {
            padding: 13px 10px 20px;
        }

        .cf-carte {
            overflow-x: auto;
        }
    }
</style>

<div class="main">
    <section class="cf-page">
        <header class="cf-entete">
            <h1>Contenu des formations</h1>
            <p>Modules, leçons, supports PDF et évaluation finale de chaque formation.</p>
        </header>

        <section class="cf-carte">
            <?php if ($message !== ''): ?>
                <div class="cf-message"><?= e($message) ?></div>
            <?php endif; ?>

            <table class="cf-table">
                <thead>
                    <tr>
                        <th>Formation</th>
                        <th>Modules</th>
                        <th>Leçons</th>
                        <th>Questions</th>
                        <th>État</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formations as $formation): ?>
                        <tr>
                            <td><?= e($formation['titre_form']) ?></td>
                            <td><?= (int) $formation['nb_modules'] ?></td>
                            <td><?= (int) $formation['nb_lecons'] ?></td>
                            <td><?= (int) $formation['nb_questions'] ?></td>
                            <td class="<?= in_array($formation['etat'], ['Prêt à installer', 'Tests de module à ajouter'], true) ? 'cf-manque' : 'cf-ok' ?>"><?= e($formation['etat']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="cf-note">
                Le bouton remplace uniquement le contenu de démonstration. Une formation dont vous avez créé vous-même les modules n’est jamais modifiée.
                L’avancement des apprenants déjà inscrits est conservé.
            </p>

            <form method="post" class="cf-actions">
                <button type="submit" name="installer" class="cf-bouton">
                    <i class="fa-solid fa-book-open"></i>
                    Installer les cours TOUN
                </button>
            </form>
        </section>
    </section>
</div>

<?php
$active_page = 'modules.php';
include 'menu.php';
?>

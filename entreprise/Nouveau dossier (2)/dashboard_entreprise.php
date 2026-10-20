<?php
session_start();

require_once "../config/connexion.php";

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'gest_entreprise') {
    header("Location: ../connexion.php");
    exit;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$userId = (int)$_SESSION['id_user'];

$stmtEntreprise = $con->prepare("SELECT * FROM t_entreprises WHERE id_user = ? LIMIT 1");
$stmtEntreprise->execute([$userId]);
$entreprise = $stmtEntreprise->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    die("Entreprise introuvable.");
}

$idEntreprise = (int)$entreprise['id_entreprise'];

$stats = [
    'collaborateurs' => 0,
    'formations' => 0,
    'affectations' => 0,
    'termines' => 0,
    'progression' => 0,
];

/* Collaborateurs actifs rattachés à l'entreprise */
$stmt = $con->prepare("
    SELECT COUNT(*)
    FROM t_entreprise_collaborateurs
    WHERE id_entreprise = ? AND statut = 'actif'
");
$stmt->execute([$idEntreprise]);
$stats['collaborateurs'] = (int)$stmt->fetchColumn();

/* Formations distinctes suivies par l'équipe */
$stmt = $con->prepare("
    SELECT COUNT(DISTINCT i.id_form)
    FROM t_inscriptions i
    INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user = i.id_user
    WHERE ec.id_entreprise = ? AND ec.statut = 'actif'
");
$stmt->execute([$idEntreprise]);
$stats['formations'] = (int)$stmt->fetchColumn();

/* Total des affectations (une ligne par inscription) */
$stmt = $con->prepare("
    SELECT COUNT(*)
    FROM t_inscriptions i
    INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user = i.id_user
    WHERE ec.id_entreprise = ? AND ec.statut = 'actif'
");
$stmt->execute([$idEntreprise]);
$stats['affectations'] = (int)$stmt->fetchColumn();

/* Formations terminées (t_inscriptions.statut_inscrip : active | terminee | annulee) */
$stmt = $con->prepare("
    SELECT COUNT(*)
    FROM t_inscriptions i
    INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user = i.id_user
    WHERE ec.id_entreprise = ? AND ec.statut = 'actif' AND i.statut_inscrip = 'terminee'
");
$stmt->execute([$idEntreprise]);
$stats['termines'] = (int)$stmt->fetchColumn();

/* Progression moyenne de l'équipe (t_progression.pourcent_avancement) */
$stmtProgression = $con->prepare("
    SELECT AVG(p.pourcent_avancement)
    FROM t_progression p
    INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user = p.id_user
    WHERE ec.id_entreprise = ? AND ec.statut = 'actif'
");
$stmtProgression->execute([$idEntreprise]);
$stats['progression'] = max(0, min(100, (int)round((float)$stmtProgression->fetchColumn())));

/* Aperçu des collaborateurs pour le tableau de suivi */
$stmtCollaborateurs = $con->prepare("
    SELECT
        u.id_user,
        u.nom_user,
        u.prenom_user,
        u.email_user,
        COUNT(DISTINCT i.id_form) AS formations,
        COALESCE(AVG(p.pourcent_avancement), 0) AS progression
    FROM t_entreprise_collaborateurs ec
    INNER JOIN t_utilisateurs u ON u.id_user = ec.id_user
    LEFT JOIN t_inscriptions i ON i.id_user = u.id_user
    LEFT JOIN t_progression p ON p.id_user = u.id_user
    WHERE ec.id_entreprise = ? AND ec.statut = 'actif'
    GROUP BY u.id_user, u.nom_user, u.prenom_user, u.email_user
    ORDER BY u.nom_user ASC
    LIMIT 6
");
$stmtCollaborateurs->execute([$idEntreprise]);
$collaborateurs = $stmtCollaborateurs->fetchAll(PDO::FETCH_ASSOC);

$active_page = 'dashboard_entreprise.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord entreprise — TOUN</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="entreprise.css">
</head>
<body>

<?php include __DIR__ . '/menu_entreprise.php'; ?>

<main class="main-content">
    <div class="dashboard">

        <!-- EN-TÊTE -->
        <section class="page-heading reveal">
            <div class="heading-copy">
                <span class="eyebrow">TABLEAU DE BORD</span>
                <h1>Bonjour <?= e($_SESSION['prenom_user'] ?? 'Responsable') ?></h1>
                <p>Pilotez les formations et suivez l'évolution de vos collaborateurs.</p>
            </div>
            <div class="quick-actions">
                <a href="collaborateurs.php" class="quick-btn secondary">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Collaborateur</span>
                </a>
                <a href="affectations.php" class="quick-btn">
                    <i class="fa-solid fa-user-check"></i>
                    <span>Affecter une formation</span>
                </a>
            </div>
        </section>

        <!-- STATISTIQUES -->
        <section class="stats-grid reveal">
            <article class="stat-card stat-blue">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                    <span>Collaborateurs</span>
                    <strong data-count="<?= $stats['collaborateurs'] ?>">0</strong>
                </div>
            </article>
            <article class="stat-card stat-turquoise">
                <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
                <div>
                    <span>Formations suivies</span>
                    <strong data-count="<?= $stats['formations'] ?>">0</strong>
                </div>
            </article>
            <article class="stat-card stat-navy">
                <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                <div>
                    <span>Affectations</span>
                    <strong data-count="<?= $stats['affectations'] ?>">0</strong>
                </div>
            </article>
            <article class="stat-card stat-green">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <span>Formations terminées</span>
                    <strong data-count="<?= $stats['termines'] ?>">0</strong>
                </div>
            </article>
        </section>

        <!-- PILOTAGE -->
        <section class="pilotage reveal">

            <div class="progress-panel">
                <div class="panel-top">
                    <div>
                        <span class="section-kicker">SUIVI GLOBAL</span>
                        <h2>Progression de vos équipes</h2>
                    </div>
                    <a href="progression.php" class="panel-link">
                        Voir le suivi
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div class="progress-main">
                    <div class="progress-circle" style="--progress: <?= $stats['progression'] ?>%;">
                        <div>
                            <strong><?= $stats['progression'] ?>%</strong>
                            <span>progression</span>
                        </div>
                    </div>
                    <div class="progress-details">
                        <h3>Niveau global de progression</h3>
                        <p>Moyenne de progression des parcours suivis par vos collaborateurs.</p>
                        <div class="progress-line">
                            <span style="width:<?= $stats['progression'] ?>%;"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quick-panel">
                <div class="panel-top">
                    <div>
                        <span class="section-kicker">ACCÈS RAPIDE</span>
                        <h2>Actions principales</h2>
                    </div>
                </div>
                <div class="quick-list">
                    <a href="collaborateurs.php" class="quick-item">
                        <span class="quick-icon blue"><i class="fa-solid fa-user-plus"></i></span>
                        <span class="quick-text">
                            <strong>Ajouter un collaborateur</strong>
                            <small>Créer ou rattacher un compte</small>
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <a href="affectations.php" class="quick-item">
                        <span class="quick-icon turquoise"><i class="fa-solid fa-graduation-cap"></i></span>
                        <span class="quick-text">
                            <strong>Affecter une formation</strong>
                            <small>Choisir les collaborateurs concernés</small>
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <a href="progression.php" class="quick-item">
                        <span class="quick-icon navy"><i class="fa-solid fa-chart-line"></i></span>
                        <span class="quick-text">
                            <strong>Consulter la progression</strong>
                            <small>Suivre les parcours en cours</small>
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>

        </section>

        <!-- COLLABORATEURS -->
        <section class="panel table-panel reveal">
            <div class="panel-header">
                <div>
                    <span class="section-kicker">ÉQUIPE</span>
                    <h2>Progression des collaborateurs</h2>
                </div>
                <a href="progression.php">
                    Voir tout
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($collaborateurs)): ?>

                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                    <strong>Aucun collaborateur rattaché</strong>
                    <span>Ajoutez votre premier collaborateur pour commencer le suivi.</span>
                    <a href="collaborateurs.php">Ajouter un collaborateur</a>
                </div>

            <?php else: ?>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Collaborateur</th>
                                <th>Formations</th>
                                <th>Progression</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($collaborateurs as $collaborateur): ?>
                            <?php
                            $nomComplet = trim(($collaborateur['prenom_user'] ?? '') . ' ' . ($collaborateur['nom_user'] ?? ''));
                            $initiales = strtoupper(substr($collaborateur['prenom_user'] ?? '', 0, 1) . substr($collaborateur['nom_user'] ?? '', 0, 1));
                            $progressionCollaborateur = max(0, min(100, (int)round((float)$collaborateur['progression'])));
                            $termine = $progressionCollaborateur >= 100;
                            ?>
                            <tr>
                                <td>
                                    <div class="employee">
                                        <div class="employee-avatar"><?= e($initiales ?: 'CO') ?></div>
                                        <div>
                                            <strong><?= e($nomComplet ?: 'Collaborateur') ?></strong>
                                            <span><?= e($collaborateur['email_user'] ?? '') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= (int)$collaborateur['formations'] ?></td>
                                <td>
                                    <div class="table-progress">
                                        <div class="progress-bar">
                                            <span style="width:<?= $progressionCollaborateur ?>%;"></span>
                                        </div>
                                        <strong><?= $progressionCollaborateur ?>%</strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="status <?= $termine ? 'finished' : 'active' ?>">
                                        <?= $termine ? 'Terminé' : 'En cours' ?>
                                    </span>
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

<!-- TOUN AI -->
<button type="button" class="toun-ai-trigger" id="tounAiTrigger" aria-label="Ouvrir TOUN AI">
    <i class="fa-solid fa-robot"></i>
</button>

<div class="toun-ai-panel" id="tounAiPanel">
    <div class="toun-ai-header">
        <div class="toun-ai-brand">
            <div class="toun-ai-icon"><i class="fa-solid fa-robot"></i></div>
            <div>
                <strong>TOUN AI</strong>
                <span>Assistant entreprise</span>
            </div>
        </div>
        <button type="button" id="tounAiClose" class="toun-ai-close" aria-label="Fermer TOUN AI">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="toun-ai-messages" id="tounAiMessages">
        <div class="toun-ai-message assistant">
            Bonjour 👋<br>
            Je suis TOUN AI. Comment puis-je vous aider ?
        </div>
    </div>

    <div class="toun-ai-suggestions">
        <button type="button" data-question="Comment ajouter un collaborateur ?">Collaborateurs</button>
        <button type="button" data-question="Comment affecter une formation ?">Formations</button>
        <button type="button" data-question="Comment voir la progression ?">Progression</button>
    </div>

    <form class="toun-ai-form" id="tounAiForm">
        <input type="text" id="tounAiInput" placeholder="Écrivez votre question..." autocomplete="off">
        <button type="submit" aria-label="Envoyer"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
</div>

<script src="entreprise.js"></script>
</body>
</html>

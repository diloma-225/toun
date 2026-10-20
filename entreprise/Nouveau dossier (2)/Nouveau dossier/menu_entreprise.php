<?php
/**
 * Menu latéral + barre supérieure de l'espace entreprise.
 * Inclus par chaque page ; $active_page (défini par la page appelante
 * avant l'include) détermine le lien actif.
 */

$active_page = $active_page ?? '';

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/* Recherche du nom / logo de l'entreprise */
$nomEntrepriseMenu = 'Mon entreprise';
$logoEntrepriseMenu = '';
$logoEntrepriseUrl = '';

if (isset($con) && isset($_SESSION['id_user'])) {
    try {
        $stmtMenuEntreprise = $con->prepare("SELECT nom_entreprise, logo_entreprise FROM t_entreprises WHERE id_user = ? LIMIT 1");
        $stmtMenuEntreprise->execute([(int)$_SESSION['id_user']]);
        $entrepriseMenu = $stmtMenuEntreprise->fetch(PDO::FETCH_ASSOC);

        if ($entrepriseMenu) {
            $nomEntrepriseMenu = trim((string)($entrepriseMenu['nom_entreprise'] ?? '')) ?: $nomEntrepriseMenu;
            $logoEntrepriseMenu = trim((string)($entrepriseMenu['logo_entreprise'] ?? ''));

            if ($logoEntrepriseMenu !== '') {
                $logoNormalise = str_replace('\\', '/', $logoEntrepriseMenu);
                $logoNom = basename($logoNormalise);

                $candidatsLogo = [
                    [__DIR__ . '/../' . ltrim($logoNormalise, '/'), '../' . ltrim($logoNormalise, '/')],
                    [__DIR__ . '/../uploads/entreprises/' . $logoNom, '../uploads/entreprises/' . rawurlencode($logoNom)],
                    [__DIR__ . '/../uploads/' . $logoNom, '../uploads/' . rawurlencode($logoNom)],
                    [__DIR__ . '/../images/entreprises/' . $logoNom, '../images/entreprises/' . rawurlencode($logoNom)],
                    [__DIR__ . '/../images/' . $logoNom, '../images/' . rawurlencode($logoNom)],
                ];

                foreach ($candidatsLogo as $candidat) {
                    if (is_file($candidat[0])) {
                        $logoEntrepriseUrl = $candidat[1];
                        break;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // Silencieux : le menu reste utilisable même si l'entreprise n'a pas encore de fiche.
    }
}

/* Initiales / photo du responsable connecté */
$prenomMenu = trim((string)($_SESSION['prenom_user'] ?? ''));
$nomMenu = trim((string)($_SESSION['nom_user'] ?? ''));

$initialesMenu = strtoupper(mb_substr($prenomMenu, 0, 1, 'UTF-8') . mb_substr($nomMenu, 0, 1, 'UTF-8'));
$initialesMenu = $initialesMenu !== '' ? $initialesMenu : 'EN';

$photoMenu = trim((string)($_SESSION['photo_user'] ?? ''));
$photoMenuUrl = '';

if ($photoMenu !== '') {
    $photoNormalisee = str_replace('\\', '/', $photoMenu);
    $photoNom = basename($photoNormalisee);

    $candidatsPhoto = [
        [__DIR__ . '/../' . ltrim($photoNormalisee, '/'), '../' . ltrim($photoNormalisee, '/')],
        [__DIR__ . '/../uploads/profils/' . $photoNom, '../uploads/profils/' . rawurlencode($photoNom)],
        [__DIR__ . '/../uploads/' . $photoNom, '../uploads/' . rawurlencode($photoNom)],
        [__DIR__ . '/../images/profils/' . $photoNom, '../images/profils/' . rawurlencode($photoNom)],
        [__DIR__ . '/../images/' . $photoNom, '../images/' . rawurlencode($photoNom)],
    ];

    foreach ($candidatsPhoto as $candidat) {
        if (is_file($candidat[0])) {
            $photoMenuUrl = $candidat[1];
            break;
        }
    }
}

/* Aide d'affichage pour le lien actif */
function menuActifEntreprise(string $page, string $pageActuelle): string
{
    return $page === $pageActuelle ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">

    <div class="company-mini">
        <div class="company-mini-logo">
            <?php if ($logoEntrepriseUrl !== ''): ?>
                <img src="<?= e($logoEntrepriseUrl) ?>" alt="Logo <?= e($nomEntrepriseMenu) ?>">
            <?php else: ?>
                <span><?= e(mb_strtoupper(mb_substr($nomEntrepriseMenu, 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
            <?php endif; ?>
        </div>
        <div class="company-mini-text">
            <strong><?= e($nomEntrepriseMenu) ?></strong>
            <span>Espace entreprise</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <span class="menu-title">Principal</span>
        <a href="dashboard_entreprise.php" class="menu-link <?= menuActifEntreprise('dashboard_entreprise.php', $active_page) ?>">
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Tableau de bord</span>
        </a>
        <a href="collaborateurs.php" class="menu-link <?= menuActifEntreprise('collaborateurs.php', $active_page) ?>">
            <i class="fa-solid fa-users"></i>
            <span>Collaborateurs</span>
        </a>
        <a href="formations.php" class="menu-link <?= menuActifEntreprise('formations.php', $active_page) ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Formations</span>
        </a>
        <a href="affectations.php" class="menu-link <?= menuActifEntreprise('affectations.php', $active_page) ?>">
            <i class="fa-solid fa-user-check"></i>
            <span>Affectations</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Suivi</span>
        <a href="progression.php" class="menu-link <?= menuActifEntreprise('progression.php', $active_page) ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Progression</span>
        </a>
        <a href="evaluations.php" class="menu-link <?= menuActifEntreprise('evaluations.php', $active_page) ?>">
            <i class="fa-solid fa-square-check"></i>
            <span>Évaluations</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Gestion</span>
        <a href="paiements.php" class="menu-link <?= menuActifEntreprise('paiements.php', $active_page) ?>">
            <i class="fa-solid fa-credit-card"></i>
            <span>Paiements</span>
        </a>
        <a href="notifications.php" class="menu-link <?= menuActifEntreprise('notifications.php', $active_page) ?>">
            <i class="fa-solid fa-bell"></i>
            <span>Notifications</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Compte</span>
        <a href="profil.php" class="menu-link <?= menuActifEntreprise('profil.php', $active_page) ?>">
            <i class="fa-solid fa-user"></i>
            <span>Mon profil</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="deconnexion.php" class="menu-link logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Déconnexion</span>
        </a>
    </div>

</aside>

<header class="topbar">

    <button type="button" class="mobile-menu" id="menuToggle" aria-label="Ouvrir le menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="topbar-title">
        <span>ESPACE ENTREPRISE</span>
    </div>

    <div class="topbar-right">
        <a href="notifications.php" class="notification-btn">
            <i class="fa-regular fa-bell"></i>
            <span></span>
        </a>
        <a href="profil.php" class="topbar-profile">
            <div class="profile-avatar">
                <?php if ($photoMenuUrl !== ''): ?>
                    <img src="<?= e($photoMenuUrl) ?>" alt="Photo de <?= e(trim($prenomMenu . ' ' . $nomMenu)) ?>">
                <?php else: ?>
                    <?= e($initialesMenu) ?>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <strong><?= e(trim($prenomMenu . ' ' . $nomMenu) ?: 'Responsable') ?></strong>
                <small>Responsable entreprise</small>
            </div>
        </a>
    </div>

</header>

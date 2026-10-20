<?php
$active_page = $active_page ?? '';
$prenom = trim((string)($_SESSION['prenom_user'] ?? ''));
$nom = trim((string)($_SESSION['nom_user'] ?? ''));
$initiales = strtoupper(
    mb_substr($prenom, 0, 1, 'UTF-8') .
    mb_substr($nom, 0, 1, 'UTF-8')
) ?: 'EN';

$logo = trim((string)($entreprise['logo_entreprise'] ?? ''));
$logoUrl = '';
if ($logo !== '') {
    $logo = str_replace('\\', '/', $logo);
    $name = basename($logo);
    $paths = [
        [__DIR__ . '/../' . ltrim($logo, '/'), '../' . ltrim($logo, '/')],
        [__DIR__ . '/../uploads/entreprises/' . $name, '../uploads/entreprises/' . rawurlencode($name)],
        [__DIR__ . '/../images/entreprises/' . $name, '../images/entreprises/' . rawurlencode($name)]
    ];
    foreach ($paths as $p) {
        if (is_file($p[0])) {
            $logoUrl = $p[1];
            break;
        }
    }
}
?>
<aside class="sidebar" id="sidebar">
    <div class="company-mini">
        <a href="dashboard_entreprise.php" class="company-logo">
            <?php if ($logoUrl): ?>
                <img src="<?= e($logoUrl) ?>" alt="<?= e($nomEntreprise) ?>">
            <?php else: ?>
                <span><?= e(mb_strtoupper(mb_substr($nomEntreprise, 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
            <?php endif; ?>
        </a>
        <div>
            <strong><?= e($nomEntreprise) ?></strong>
            <span>Espace entreprise</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <span class="menu-title">Principal</span>
        <a href="dashboard_entreprise.php" class="menu-link <?= $active_page === 'dashboard_entreprise.php' ? 'active' : '' ?>"><i class="fa-solid fa-table-cells-large"></i><span>Tableau de bord</span></a>
        <a href="collaborateurs.php" class="menu-link <?= $active_page === 'collaborateurs.php' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i><span>Collaborateurs</span></a>
        <a href="formations.php" class="menu-link <?= $active_page === 'formations.php' ? 'active' : '' ?>"><i class="fa-solid fa-book-open"></i><span>Formations</span></a>
        <a href="affectations.php" class="menu-link <?= $active_page === 'affectations.php' ? 'active' : '' ?>"><i class="fa-solid fa-user-check"></i><span>Affectations</span></a>

        <div class="menu-separator"></div>
        <span class="menu-title">Suivi</span>
        <a href="progression.php" class="menu-link <?= $active_page === 'progression.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i><span>Progression</span></a>
        <a href="evaluations.php" class="menu-link <?= $active_page === 'evaluations.php' ? 'active' : '' ?>"><i class="fa-solid fa-square-check"></i><span>Évaluations</span></a>

        <div class="menu-separator"></div>
        <span class="menu-title">Gestion</span>
        <a href="paiements.php" class="menu-link <?= $active_page === 'paiements.php' ? 'active' : '' ?>"><i class="fa-solid fa-credit-card"></i><span>Paiements</span></a>
        <a href="notifications.php" class="menu-link <?= $active_page === 'notifications.php' ? 'active' : '' ?>"><i class="fa-solid fa-bell"></i><span>Notifications</span></a>

        <div class="menu-separator"></div>
        <span class="menu-title">Compte</span>
        <a href="profil.php" class="menu-link <?= $active_page === 'profil.php' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i><span>Mon profil</span></a>
        <a href="parametres.php" class="menu-link <?= $active_page === 'parametres.php' ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i><span>Paramètres</span></a>
    </nav>

    <div class="sidebar-bottom">
        <a href="deconnexion.php" class="menu-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Déconnexion</span></a>
    </div>
</aside>

<header class="topbar">
    <div class="topbar-title">ESPACE ENTREPRISE</div>
    <div class="topbar-right">
        <a href="notifications.php" class="topbar-icon"><i class="fa-regular fa-bell"></i></a>
        <a href="profil.php" class="topbar-profile">
            <div class="profile-avatar"><?= e($initiales) ?></div>
            <div class="profile-info">
                <strong><?= e(trim($prenom . ' ' . $nom) ?: 'Responsable') ?></strong>
                <small>Responsable entreprise</small>
            </div>
        </a>
    </div>
</header>
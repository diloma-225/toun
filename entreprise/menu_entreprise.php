<?php
$active_page = $active_page ?? '';

$nomEntrepriseMenu = trim((string) ($entreprise['nom_entreprise'] ?? 'Mon entreprise'));
$logoEntreprise = trim((string) ($entreprise['logo_entreprise'] ?? ''));

$prenomMenu = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nomMenu = trim((string) ($_SESSION['nom_user'] ?? ''));
$photoMenu = trim((string) ($_SESSION['photo_user'] ?? ''));
$initialesMenu = strtoupper(mb_substr($prenomMenu, 0, 1, 'UTF-8') . mb_substr($nomMenu, 0, 1, 'UTF-8'));
if ($initialesMenu === '') {
    $initialesMenu = 'EN';
}

function resolveMenuImage(string $stored): string
{
    if ($stored === '') {
        return '';
    }
    $stored = str_replace('\\', '/', $stored);
    $name = basename($stored);
    $candidates = [
        [__DIR__ . '/../' . ltrim($stored, '/'), '../' . ltrim($stored, '/')],
        [__DIR__ . '/../uploads/entreprises/' . $name, '../uploads/entreprises/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/profils/' . $name, '../uploads/profils/' . rawurlencode($name)],
        [__DIR__ . '/../uploads/' . $name, '../uploads/' . rawurlencode($name)],
        [__DIR__ . '/../images/entreprises/' . $name, '../images/entreprises/' . rawurlencode($name)],
        [__DIR__ . '/../images/profils/' . $name, '../images/profils/' . rawurlencode($name)]
    ];
    foreach ($candidates as $c) {
        if (is_file($c[0])) {
            return $c[1];
        }
    }
    return '';
}

$logoEntrepriseUrl = resolveMenuImage($logoEntreprise);
$photoMenuUrl = resolveMenuImage($photoMenu);
?>
<style>
    * {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0;
        min-height: 100%;
        font-family: Poppins, Arial, sans-serif;
        background: #f4f8fa;
        color: #294965;
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    button,
    input,
    select,
    textarea {
        font-family: inherit;
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 250px;
        padding: 9px 10px 8px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 1000;
        background: linear-gradient(160deg, #102d52, #154a79 50%, #087f96);
        box-shadow: 4px 0 18px rgba(11, 47, 76, 0.1);
    }

    .company-mini {
        min-height: 64px;
        padding: 2px 4px 8px;
        display: flex;
        align-items: center;
        gap: 9px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .company-logo {
        width: 50px;
        height: 50px;
        min-width: 50px;
        border-radius: 10px;
        background: #fff;
        display: grid;
        place-items: center;
        overflow: hidden;
    }

    .company-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 3px;
    }

    .company-logo span {
        color: #16808b;
        font-weight: 800;
        font-size: 14px;
    }

    .company-mini strong {
        display: block;
        color: #fff;
        font-size: 11px;
        line-height: 1.2;
    }

    .company-mini span {
        display: block;
        color: rgba(255, 255, 255, 0.6);
        font-size: 10px;
        margin-top: 2px;
    }

    .sidebar-menu {
        flex: 1;
        overflow: hidden;
        padding-top: 3px;
    }

    .menu-title {
        display: block;
        padding: 8px 9px 4px;
        color: #67d7d8;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.3px;
        text-transform: uppercase;
    }

    .menu-link {
        height: 34px;
        margin: 1px 0;
        padding: 0 9px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 8px;
        color: rgba(255, 255, 255, 0.78);
        font-size: 11.5px;
        transition: 0.2s;
    }

    .menu-link i {
        width: 17px;
        text-align: center;
        font-size: 12px;
    }

    .menu-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.09);
        transform: translateX(2px);
    }

    .menu-link.active {
        color: #fff;
        background: linear-gradient(100deg, #248fc0, #3975b8);
        box-shadow: 0 5px 13px rgba(8, 91, 139, 0.2);
    }

    .menu-separator {
        height: 1px;
        margin: 5px 7px;
        background: rgba(255, 255, 255, 0.08);
    }

    .sidebar-bottom {
        padding-top: 5px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-bottom .menu-link {
        background: rgba(255, 255, 255, 0.06);
    }

    .topbar {
        position: fixed;
        top: 0;
        left: 250px;
        right: 0;
        height: 64px;
        padding: 0 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(255, 255, 255, 0.98);
        border-bottom: 1px solid #e7eef2;
        z-index: 900;
    }

    .topbar-title {
        color: #148594;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.4px;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .notification-btn {
        width: 34px;
        height: 34px;
        border: 1px solid #e2ebef;
        border-radius: 9px;
        display: grid;
        place-items: center;
        color: #567187;
        font-size: 12px;
        transition: 0.2s;
    }

    .notification-btn:hover {
        color: #148b9a;
        background: #eff9fa;
        transform: translateY(-1px);
    }

    .topbar-profile-wrap {
        position: relative;
    }

    .topbar-profile {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 2px 7px 2px 2px;
        border-radius: 9px;
        border: 0;
        background: transparent;
        cursor: pointer;
    }

    .topbar-profile:hover {
        background: #f3f8fa;
    }

    .profile-avatar {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
        display: grid;
        place-items: center;
        overflow: hidden;
        font-size: 10.5px;
        font-weight: 800;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-info strong {
        display: block;
        color: #294965;
        font-size: 10.5px;
    }

    .profile-info small {
        display: block;
        color: #92a2ae;
        font-size: 10px;
        margin-top: 1px;
    }

    .profile-menu {
        position: absolute;
        right: 0;
        top: 43px;
        width: 160px;
        padding: 5px;
        border: 1px solid #dfe9ee;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(20, 56, 84, 0.12);
        display: none;
    }

    .profile-menu.open {
        display: block;
    }

    .profile-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border-radius: 7px;
        color: #526f84;
        font-size: 10px;
    }

    .profile-menu a:hover {
        background: #eef8fa;
        color: #168b9a;
    }

    .mobile-menu {
        display: none;
    }

    .main-content {
        position: relative;
        width: calc(100% - 250px);
        margin-left: 250px;
        padding: 84px 24px 28px;
        min-height: 100vh;
        overflow-x: hidden;
    }

    .page {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
    }

    .page-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 14px;
    }

    .eyebrow {
        display: block;
        color: #1393a2;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.3px;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    h1 {
        margin: 0;
        color: #173d62;
        font-size: 19px;
        line-height: 1.2;
    }

    h2 {
        margin: 0;
        color: #173d62;
        font-size: 13px;
    }

    .page-head p {
        margin: 4px 0 0;
        color: #8a9ca9;
        font-size: 10.5px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 0;
        border-radius: 8px;
        padding: 8px 11px;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
        font: 700 10.5px Poppins;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 7px 14px rgba(32, 161, 184, 0.18);
    }

    .btn-light {
        background: #edf7f8;
        color: #16808b;
        box-shadow: none;
    }

    .panel,
    .card {
        border: 1px solid #e1eaee;
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 3px 10px rgba(23, 59, 88, 0.06);
        overflow: hidden;
    }

    .alert {
        margin-bottom: 10px;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 10px;
    }

    .alert.ok {
        background: #e9f8f5;
        color: #237c6a;
    }

    .alert.err {
        background: #fff0f0;
        color: #a94d4d;
    }

    .toun-ai-trigger {
        position: fixed;
        right: 20px;
        bottom: 20px;
        width: 48px;
        height: 48px;
        border: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, #173d62, #20a1b8);
        color: #fff;
        display: grid;
        place-items: center;
        box-shadow: 0 12px 28px rgba(23, 61, 98, 0.22);
        z-index: 1100;
        cursor: pointer;
        transition: 0.2s;
    }

    .toun-ai-trigger:hover {
        transform: translateY(-3px) rotate(-2deg);
    }

    .toun-ai-panel {
        position: fixed;
        right: 20px;
        bottom: 78px;
        width: 310px;
        height: 390px;
        background: #fff;
        border: 1px solid #dce9ef;
        border-radius: 17px;
        box-shadow: 0 18px 40px rgba(20, 56, 84, 0.18);
        z-index: 1099;
        display: none;
        overflow: hidden;
    }

    .toun-ai-panel.open {
        display: flex;
        flex-direction: column;
    }

    .toun-ai-head {
        padding: 12px 13px;
        background: linear-gradient(135deg, #173d62, #20a1b8);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .toun-ai-head strong {
        font-size: 11px;
    }

    .toun-ai-head span {
        display: block;
        font-size: 10px;
        color: rgba(255, 255, 255, 0.72);
        margin-top: 2px;
    }

    .toun-ai-close {
        border: 0;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        width: 25px;
        height: 25px;
        border-radius: 7px;
        cursor: pointer;
    }

    .toun-ai-messages {
        flex: 1;
        padding: 11px;
        overflow: auto;
        background: #f8fbfc;
    }

    .toun-ai-message {
        max-width: 88%;
        padding: 8px 9px;
        border-radius: 10px;
        margin-bottom: 7px;
        font-size: 10px;
        line-height: 1.55;
    }

    .toun-ai-message.bot {
        background: #fff;
        border: 1px solid #e2ebef;
        color: #526f84;
    }

    .toun-ai-message.user {
        margin-left: auto;
        background: #eaf8fa;
        color: #167e8a;
    }

    .toun-ai-form {
        display: flex;
        gap: 6px;
        padding: 9px;
        border-top: 1px solid #e8eef1;
    }

    .toun-ai-form input {
        flex: 1;
        height: 30px;
        border: 1px solid #dce7eb;
        border-radius: 8px;
        padding: 0 9px;
        outline: 0;
        font-size: 10px;
    }

    .toun-ai-form button {
        width: 32px;
        border: 0;
        border-radius: 8px;
        background: #20a1b8;
        color: #fff;
        cursor: pointer;
    }

    @media (max-width: 800px) {
        .sidebar {
            transform: translateX(-100%);
            transition: 0.25s;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .topbar {
            left: 0;
            padding: 0 14px;
        }

        .mobile-menu {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 8px;
            background: #eef8fa;
            color: #16808b;
            cursor: pointer;
        }

        .topbar-title {
            display: none;
        }

        .main-content {
            width: 100%;
            margin-left: 0;
            padding: 78px 14px 22px;
        }

        .page-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .profile-info {
            display: none;
        }

        .toun-ai-panel {
            right: 12px;
            bottom: 70px;
            width: calc(100vw - 24px);
        }
    }
</style>

<aside class="sidebar" id="sidebar">
    <div class="company-mini">
        <div class="company-logo">
            <?php if ($logoEntrepriseUrl !== ''): ?>
                <img src="<?= e($logoEntrepriseUrl) ?>" alt="Logo">
            <?php else: ?>
                <span><?= e(mb_strtoupper(mb_substr($nomEntrepriseMenu, 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <strong><?= e($nomEntrepriseMenu) ?></strong>
            <span>Espace entreprise</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <span class="menu-title">Principal</span>
        <a href="dashboard_entreprise.php" class="menu-link <?= $active_page === 'dashboard_entreprise.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Tableau de bord</span>
        </a>
        <a href="collaborateurs.php" class="menu-link <?= $active_page === 'collaborateurs.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <span>Collaborateurs</span>
        </a>
        <a href="formations.php" class="menu-link <?= $active_page === 'formations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Formations</span>
        </a>
        <a href="affectations.php" class="menu-link <?= $active_page === 'affectations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-check"></i>
            <span>Affectations</span>
        </a>

        <div class="menu-separator"></div>
        <span class="menu-title">Suivi</span>
        <a href="progression.php" class="menu-link <?= $active_page === 'progression.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Progression</span>
        </a>
        <a href="evaluations.php" class="menu-link <?= $active_page === 'evaluations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-square-check"></i>
            <span>Évaluations</span>
        </a>

        <div class="menu-separator"></div>
        <span class="menu-title">Gestion</span>
        <a href="paiements.php" class="menu-link <?= $active_page === 'paiements.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-credit-card"></i>
            <span>Paiements</span>
        </a>
        <a href="notifications.php" class="menu-link <?= $active_page === 'notifications.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i>
            <span>Notifications</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="deconnexion.php" class="menu-link"><i class="fa-solid fa-right-from-bracket"></i><span>Déconnexion</span></a>
    </div>
</aside>

<header class="topbar">
    <button class="mobile-menu" id="mobileMenu" type="button"><i class="fa-solid fa-bars"></i></button>
    <div class="topbar-title">ESPACE ENTREPRISE</div>
    <div class="topbar-right">
        <a href="notifications.php" class="notification-btn"><i class="fa-regular fa-bell"></i></a>
        <div class="topbar-profile-wrap">
            <button class="topbar-profile" id="profileToggle" type="button">
                <div class="profile-avatar">
                    <?php if ($photoMenuUrl !== ''): ?>
                        <img src="<?= e($photoMenuUrl) ?>" alt="Photo">
                    <?php else: ?>
                        <?= e($initialesMenu) ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <strong><?= e(trim($prenomMenu . ' ' . $nomMenu) ?: 'Responsable') ?></strong>
                    <small>Responsable entreprise</small>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size:10px;color:#91a1ae"></i>
            </button>
            <div class="profile-menu" id="profileMenu">
                <a href="profil.php"><i class="fa-solid fa-user"></i> Mon profil</a>
                <a href="parametres.php"><i class="fa-solid fa-gear"></i> Paramètres</a>
                <a href="deconnexion.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </div>
        </div>
    </div>
</header>

<button class="toun-ai-trigger" id="tounAiTrigger" type="button" aria-label="TOUN AI"><i class="fa-solid fa-message"></i></button>
<section class="toun-ai-panel" id="tounAiPanel">
    <div class="toun-ai-head">
        <div>
            <strong>TOUN AI</strong>
            <span>Assistant de votre espace entreprise</span>
        </div>
        <button class="toun-ai-close" id="tounAiClose" type="button"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="toun-ai-messages" id="tounAiMessages">
        <div class="toun-ai-message bot">Bonjour 👋 Je peux vous guider dans les collaborateurs, formations, affectations, progression, évaluations et paiements.</div>
    </div>
    <form class="toun-ai-form" id="tounAiForm">
        <input id="tounAiInput" autocomplete="off" placeholder="Posez votre question...">
        <button><i class="fa-solid fa-paper-plane"></i></button>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const side = document.getElementById('sidebar');
        const mob = document.getElementById('mobileMenu');
        if (mob && side) mob.onclick = () => side.classList.toggle('open');

        const pt = document.getElementById('profileToggle');
        const pm = document.getElementById('profileMenu');
        if (pt && pm) pt.onclick = () => pm.classList.toggle('open');
        document.addEventListener('click', e => {
            if (pm && !e.target.closest('.topbar-profile-wrap')) pm.classList.remove('open');
        });

        const trigger = document.getElementById('tounAiTrigger');
        const panel = document.getElementById('tounAiPanel');
        const close = document.getElementById('tounAiClose');
        const form = document.getElementById('tounAiForm');
        const input = document.getElementById('tounAiInput');
        const messages = document.getElementById('tounAiMessages');

        function add(t, c) {
            const d = document.createElement('div');
            d.className = 'toun-ai-message ' + c;
            d.innerHTML = t;
            messages.appendChild(d);
            messages.scrollTop = messages.scrollHeight;
        }

        function answer(q) {
            q = q.toLowerCase();
            if (q.includes('collaborateur') || q.includes('employé') || q.includes('ajout'))
                return 'Ouvrez <strong>Collaborateurs</strong> pour ajouter, modifier, voir, désactiver ou réactiver un collaborateur.';
            if (q.includes('formation') || q.includes('acheter') || q.includes('payer'))
                return 'Une formation doit d’abord être achetée par l’entreprise. Utilisez <strong>Formations</strong> puis le bouton <strong>Accéder au catalogue</strong>.';
            if (q.includes('affect'))
                return '<strong>Affectations</strong> permet d’attribuer uniquement les formations déjà achetées à vos collaborateurs.';
            if (q.includes('progress'))
                return '<strong>Progression</strong> présente l’avancement, les formations terminées et les parcours en cours.';
            if (q.includes('évaluation') || q.includes('evaluation') || q.includes('résultat') || q.includes('resultat'))
                return '<strong>Évaluations</strong> affiche les quiz, les notes obtenues et les statuts de réussite de vos collaborateurs.';
            if (q.includes('profil'))
                return 'Votre profil et vos paramètres sont accessibles depuis votre photo en haut à droite.';
            return 'Je peux vous aider avec les <strong>collaborateurs</strong>, les <strong>formations</strong>, les <strong>affectations</strong>, la <strong>progression</strong>, les <strong>évaluations</strong> et les <strong>paiements</strong>.';
        }

        function send() {
            const q = input.value.trim();
            if (!q) return;
            add(q, 'user');
            input.value = '';
            setTimeout(() => add(answer(q), 'bot'), 180);
        }

        if (trigger) trigger.onclick = () => panel.classList.toggle('open');
        if (close) close.onclick = () => panel.classList.remove('open');
        if (form)
            form.onsubmit = e => {
                e.preventDefault();
                send();
            };
    });
</script>

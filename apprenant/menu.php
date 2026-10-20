<?php
$prenomMenu = trim($_SESSION['prenom_user'] ?? '');
$nomMenu = trim($_SESSION['nom_user'] ?? '');
$photoMenu = trim((string) ($_SESSION['photo_user'] ?? ''));

$initialesMenu = '';

if ($prenomMenu !== '') {
    $initialesMenu .= strtoupper(substr($prenomMenu, 0, 1));
}

if ($nomMenu !== '') {
    $initialesMenu .= strtoupper(substr($nomMenu, 0, 1));
}

if ($initialesMenu === '') {
    $initialesMenu = 'TO';
}

$pageActuelle = basename($_SERVER['SCRIPT_NAME']);
$estCollaborateurMenu = false;

if (!isset($con)) {
    require_once __DIR__ . '/../config/connexion.php';
}

if (isset($con) && $con instanceof PDO && !empty($_SESSION['id_user'])) {
    try {
        $reqMenu = $con->prepare("
            SELECT 1
            FROM t_entreprise_collaborateurs
            WHERE id_user = ?
              AND LOWER(statut) = 'actif'
            LIMIT 1
        ");

        $reqMenu->execute([(int) $_SESSION['id_user']]);
        $estCollaborateurMenu = (bool) $reqMenu->fetchColumn();
    } catch (Throwable $e) {
        $estCollaborateurMenu = false;
    }
}

function menuActif(string $page, string $pageActuelle): string
{
    return $page === $pageActuelle ? 'active' : '';
}

function tounAiTableExiste(PDO $con, string $table): bool
{
    try {
        $req = $con->prepare('SHOW TABLES LIKE ?');
        $req->execute([$table]);

        return (bool) $req->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function tounAiCompter(PDO $con, string $sql, array $params = []): int
{
    try {
        $req = $con->prepare($sql);
        $req->execute($params);

        return (int) $req->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$tounAiFormations = 0;
$tounAiCertificats = 0;
$tounAiProgression = 0;
$tounAiFormationEnCours = '';
$tounAiPaiementStatut = '';

if (isset($con) && $con instanceof PDO && !empty($_SESSION['id_user'])) {
    $idUserIA = (int) $_SESSION['id_user'];

    if (tounAiTableExiste($con, 't_inscriptions') && tounAiTableExiste($con, 't_formations')) {
        $tounAiFormations = tounAiCompter(
            $con,
            'SELECT COUNT(DISTINCT id_form) FROM t_inscriptions WHERE id_user = ?',
            [$idUserIA]
        );

        try {
            $reqIA = $con->prepare("
                SELECT f.titre_form
                FROM t_inscriptions i
                INNER JOIN t_formations f
                    ON f.id_form = i.id_form
                WHERE i.id_user = ?
                ORDER BY i.id_form DESC
                LIMIT 1
            ");

            $reqIA->execute([$idUserIA]);

            $tounAiFormationEnCours = (string) ($reqIA->fetchColumn() ?: '');
        } catch (Throwable $e) {
            $tounAiFormationEnCours = '';
        }
    }

    if (tounAiTableExiste($con, 't_certificats')) {
        $tounAiCertificats = tounAiCompter($con, 'SELECT COUNT(*) FROM t_certificats WHERE id_user = ?', [$idUserIA]);
    }

    if (tounAiTableExiste($con, 't_progression')) {
        try {
            $reqCols = $con->query('SHOW COLUMNS FROM t_progression');

            $colsIA = $reqCols ? $reqCols->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        } catch (Throwable $e) {
            $colsIA = [];
        }

        foreach (['pourcentage_progression', 'progression', 'pourcentage', 'progression_user'] as $c) {
            if (in_array($c, $colsIA, true)) {
                $tounAiProgression = (float) tounAiCompter(
                    $con,
                    "SELECT COALESCE(AVG(`$c`), 0)
                     FROM t_progression
                     WHERE id_user = ?",
                    [$idUserIA]
                );

                break;
            }
        }
    }

    $tounAiProgression = (int) max(0, min(100, round($tounAiProgression)));

    if (tounAiTableExiste($con, 't_paiements')) {
        try {
            $reqIA = $con->prepare("
                SELECT statut_paiement
                FROM t_paiements
                WHERE id_user = ?
                ORDER BY id_paiement DESC
                LIMIT 1
            ");

            $reqIA->execute([$idUserIA]);

            $tounAiPaiementStatut = (string) ($reqIA->fetchColumn() ?: '');
        } catch (Throwable $e) {
            $tounAiPaiementStatut = '';
        }
    }
}

$tounAiContexte = [
    'prenom' => $prenomMenu !== '' ? $prenomMenu : '',
    'collaborateur' => $estCollaborateurMenu,
    'formations' => $tounAiFormations,
    'certificats' => $tounAiCertificats,
    'progression' => $tounAiProgression,
    'formationEnCours' => $tounAiFormationEnCours,
    'paiementStatut' => $tounAiPaiementStatut
];
?>

<style>
    .toun-ai-trigger {
        position: fixed;
        right: 26px;
        bottom: 26px;
        width: 54px;
        height: 54px;
        border: none;
        border-radius: 50%;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #ffffff;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 10px 26px rgba(24, 33, 63, 0.28);
        z-index: 900;
        transition: transform 0.15s ease;
    }

    .toun-ai-trigger:hover {
        transform: translateY(-2px);
    }

    .toun-ai-panel {
        position: fixed;
        right: 26px;
        bottom: 92px;
        width: 320px;
        max-width: calc(100vw - 40px);
        height: 420px;
        max-height: calc(100vh - 140px);
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 20px 48px rgba(24, 33, 63, 0.22);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        transform: translateY(14px) scale(0.98);
        pointer-events: none;
        transition:
            opacity 0.18s ease,
            transform 0.18s ease;
        z-index: 900;
    }

    .toun-ai-panel.open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .toun-ai-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 18px;
        background: linear-gradient(120deg, #18213f 0%, #24558b 55%, #20a1b8 100%);
        color: #ffffff;
    }

    .toun-ai-head strong {
        display: block;
        font-size: 13px;
    }

    .toun-ai-head span {
        display: block;
        margin-top: 2px;
        font-size: 11.5px;
        color: rgba(255, 255, 255, 0.75);
    }

    .toun-ai-close {
        border: none;
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .toun-ai-messages {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #f3f8fb;
    }

    .toun-ai-message {
        max-width: 84%;
        padding: 10px 13px;
        border-radius: 14px;
        font-size: 12px;
        line-height: 1.5;
    }

    .toun-ai-message.bot {
        align-self: flex-start;
        background: #ffffff;
        color: #17345b;
        border-bottom-left-radius: 4px;
        box-shadow: 0 4px 12px rgba(24, 33, 63, 0.06);
    }

    .toun-ai-message.user {
        align-self: flex-end;
        background: #20a1b8;
        color: #ffffff;
        border-bottom-right-radius: 4px;
    }

    .toun-ai-form {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        border-top: 1px solid #e1e9ef;
        background: #ffffff;
    }

    .toun-ai-form input {
        flex: 1;
        border: 1px solid #dde8ee;
        border-radius: 12px;
        padding: 9px 12px;
        font-size: 12px;
        font-family: inherit;
        outline: none;
    }

    .toun-ai-form input:focus {
        border-color: #20a1b8;
    }

    .toun-ai-form button {
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #20a1b8;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .toun-ai-form button:hover {
        background: #1c8ea3;
    }

    @media (max-width: 560px) {
        .toun-ai-panel {
            right: 16px;
            left: 16px;
            width: auto;
        }

        .toun-ai-trigger {
            right: 16px;
            bottom: 16px;
        }
    }
</style>

<aside class="sidebar" id="sidebar">

    <div class="logo">
        <a href="dashboard.php">
            <img src="../images/logo1.png" alt="TOUN">
        </a>
    </div>

    <nav class="sidebar-menu">

        <span class="menu-title">Principal</span>

        <a href="dashboard.php"
            class="menu-link <?= menuActif('dashboard.php', $pageActuelle) ?>">
            <i class="fa-solid fa-house"></i>
            <span>Tableau de bord</span>
        </a>

        <a href="mes-formations.php"
            class="menu-link <?= menuActif('mes-formations.php', $pageActuelle) ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Mes formations</span>
        </a>

        <a href="lives.php"
            class="menu-link <?= menuActif('lives.php', $pageActuelle) ?>">
            <i class="fa-solid fa-video"></i>
            <span>Mes Lives</span>
        </a>

        <a href="../formation.php"
            class="menu-link <?= menuActif('formation.php', $pageActuelle) ?>">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Catalogue</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Apprentissage</span>

        <a href="progression.php"
            class="menu-link <?= menuActif('progression.php', $pageActuelle) ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Ma progression</span>
        </a>

        <a href="evaluations.php"
            class="menu-link <?= menuActif('evaluations.php', $pageActuelle) ?>">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Mes évaluations</span>
        </a>

        <a href="certificats.php"
            class="menu-link <?= menuActif('certificats.php', $pageActuelle) ?>">
            <i class="fa-solid fa-award"></i>
            <span>Mes certificats</span>
        </a>

        <a href="rendez-vous.php"
            class="menu-link <?= menuActif('rendez-vous.php', $pageActuelle) ?>">
            <i class="fa-regular fa-calendar-check"></i>
            <span>Mes rendez-vous</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Services</span>

        <a href="paiements.php"
            class="menu-link <?= menuActif('paiements.php', $pageActuelle) ?>">
            <i class="fa-solid fa-wallet"></i>
            <span>Mes paiements</span>
        </a>

        <a href="ressources.php"
            class="menu-link <?= menuActif('ressources.php', $pageActuelle) ?>">
            <i class="fa-solid fa-folder-open"></i>
            <span>Ressources</span>
        </a>

        <a href="notifications.php"
            class="menu-link <?= menuActif('notifications.php', $pageActuelle) ?>">
            <i class="fa-regular fa-bell"></i>
            <span>Notifications</span>
        </a>

        <a href="messages.php"
            class="menu-link <?= menuActif('messages.php', $pageActuelle) ?>">
            <i class="fa-regular fa-message"></i>
            <span>Messages</span>
        </a>

        <div class="menu-separator"></div>

        <span class="menu-title">Mon compte</span>

        <a href="profil.php"
            class="menu-link <?= menuActif('profil.php', $pageActuelle) ?>">
            <i class="fa-regular fa-user"></i>
            <span>Mon profil</span>
        </a>

        <a href="parametres.php"
            class="menu-link <?= menuActif('parametres.php', $pageActuelle) ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Paramètres</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

        <a href="deconnexion.php" class="menu-link logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Déconnexion</span>
        </a>

    </div>

</aside>

<button
    class="toun-ai-trigger"
    id="tounAiTrigger"
    type="button"
    aria-label="TOUN AI"
>
    <i class="fa-solid fa-message"></i>
</button>

<section class="toun-ai-panel" id="tounAiPanel">

    <div class="toun-ai-head">

        <div>
            <strong>TOUN AI</strong>
            <span>Votre assistant d'apprentissage</span>
        </div>

        <button
            class="toun-ai-close"
            id="tounAiClose"
            type="button"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

    <div class="toun-ai-messages" id="tounAiMessages">

        <div class="toun-ai-message bot">
            Bonjour
            <?= htmlspecialchars($prenomMenu !== '' ? $prenomMenu : '', ENT_QUOTES, 'UTF-8') ?>
            👋 Je peux vous renseigner sur vos formations,
            votre progression, vos certificats
            <?= $estCollaborateurMenu ? '' : ' et vos paiements' ?>.
        </div>

    </div>

    <form class="toun-ai-form" id="tounAiForm">

        <input
            id="tounAiInput"
            autocomplete="off"
            placeholder="Posez votre question..."
        >

        <button type="submit">
            <i class="fa-solid fa-paper-plane"></i>
        </button>

    </form>

</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const trigger = document.getElementById('tounAiTrigger');
        const panel = document.getElementById('tounAiPanel');
        const close = document.getElementById('tounAiClose');
        const form = document.getElementById('tounAiForm');
        const input = document.getElementById('tounAiInput');
        const messages = document.getElementById('tounAiMessages');

        const TOUN_CTX = <?= json_encode($tounAiContexte, JSON_UNESCAPED_UNICODE) ?>;

        function addMessage(text, type) {
            const message = document.createElement('div');

            message.className = 'toun-ai-message ' + type;

            message.innerHTML = text;

            messages.appendChild(message);

            messages.scrollTop = messages.scrollHeight;
        }

        function stripAccents(text) {
            return text
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        }

        function answer(question) {
            const q = stripAccents(question);

            if (q.includes('bonjour') || q.includes('salut') || q.includes('bonsoir')) {
                return `
                Bonjour ${TOUN_CTX.prenom} !
                Je peux vous renseigner sur vos formations,
                votre progression et vos certificats
                ${TOUN_CTX.collaborateur ? '' : ' ou vos paiements'}.
            `;
            }

            if (q.includes('formation') || q.includes('cours') || q.includes('catalogue')) {
                if (TOUN_CTX.formations > 0) {
                    return `
                    Vous suivez actuellement
                    <strong>${TOUN_CTX.formations}</strong>
                    formation(s)
                    ${TOUN_CTX.formationEnCours ? `, dont « ${TOUN_CTX.formationEnCours} »` : ''}.
                    Retrouvez-les dans
                    <strong>Mes formations</strong>.
                `;
                }

                if (TOUN_CTX.collaborateur) {
                    return `
                    Aucune formation ne vous a encore été attribuée.
                    Votre entreprise s'en charge.
                `;
                }

                return `
                Vous n'avez pas encore de formation en cours.
                Découvrez le catalogue pour choisir
                celle qui correspond à vos objectifs.
            `;
            }

            if (q.includes('progress') || q.includes('avancement') || q.includes('niveau')) {
                return `
                Votre progression moyenne est de
                <strong>${TOUN_CTX.progression}%</strong>.
                Le détail est disponible dans
                <strong>Ma progression</strong>.
            `;
            }

            if (q.includes('certificat') || q.includes('diplome') || q.includes('attestation')) {
                if (TOUN_CTX.certificats > 0) {
                    return `
                    Vous avez déjà obtenu
                    <strong>${TOUN_CTX.certificats}</strong>
                    certificat(s).
                    Retrouvez-les dans
                    <strong>Mes certificats</strong>.
                `;
                }

                return `
                Un certificat est délivré lorsque
                vous terminez une formation et validez
                son évaluation.
            `;
            }

            if (q.includes('evaluation') || q.includes('quiz') || q.includes('examen')) {
                return `
                Vos évaluations sont disponibles
                dans <strong>Mes évaluations</strong>.
            `;
            }

            if (q.includes('paiement') || q.includes('payer') || q.includes('facture') || q.includes('tarif')) {
                if (TOUN_CTX.collaborateur) {
                    return `
                    Vos formations sont prises en charge
                    par votre entreprise.
                `;
                }

                return `
                L'historique de vos paiements est disponible
                dans <strong>Mes paiements</strong>.
            `;
            }

            if (q.includes('rendez') || q.includes('formateur')) {
                return `
                Pour échanger avec un formateur,
                prenez rendez-vous depuis
                <strong>Mes rendez-vous</strong>
                ou utilisez <strong>Messages</strong>.
            `;
            }

            if (q.includes('profil') || q.includes('mot de passe') || q.includes('photo') || q.includes('compte')) {
                return `
                Vos informations personnelles se gèrent
                depuis <strong>Mon profil</strong>.
            `;
            }

            if (q.includes('merci') || q.includes('super') || q.includes('parfait')) {
                return `
                Avec plaisir ${TOUN_CTX.prenom} !
                N'hésitez pas si vous avez d'autres questions.
            `;
            }

            return `
            Je peux vous aider avec vos
            <strong>formations</strong>,
            votre <strong>progression</strong>,
            vos <strong>certificats</strong>
            ${TOUN_CTX.collaborateur ? '' : ' et vos <strong>paiements</strong>'}.
            Pour une question plus précise,
            utilisez <strong>Messages</strong>.
        `;
        }

        function sendMessage() {
            const question = input.value.trim();

            if (!question) {
                return;
            }

            addMessage(question, 'user');

            input.value = '';

            setTimeout(function () {
                addMessage(answer(question), 'bot');
            }, 220);
        }

        if (trigger) {
            trigger.addEventListener('click', function () {
                panel.classList.toggle('open');
            });
        }

        if (close) {
            close.addEventListener('click', function () {
                panel.classList.remove('open');
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                sendMessage();
            });
        }
    });
</script>
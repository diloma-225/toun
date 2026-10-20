<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function tableExiste(PDO $con, string $table): bool
{
    try {
        $req = $con->prepare('SHOW TABLES LIKE ?');
        $req->execute([$table]);
        return (bool) $req->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function colonnesTable(PDO $con, string $table): array
{
    if (!tableExiste($con, $table)) {
        return [];
    }
    try {
        $req = $con->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
        return $req->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function compter(PDO $con, string $sql, array $params = []): int
{
    try {
        $req = $con->prepare($sql);
        $req->execute($params);
        return (int) $req->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$idUser = (int) $_SESSION['id_user'];
$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));
$nomAffiche = trim($prenom . ' ' . $nom) ?: 'Apprenant';
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'TO';
$photoUrl = '';
$photoBrute = trim((string) ($_SESSION['photo_user'] ?? ''));
if ($photoBrute !== '') {
    $nomPhoto = basename(str_replace('\\', '/', $photoBrute));
    foreach (
        [
            __DIR__ . '/../uploads/profils/' . $nomPhoto => '../uploads/profils/' . rawurlencode($nomPhoto),
            __DIR__ . '/../images/' . $nomPhoto => '../images/' . rawurlencode($nomPhoto)
        ]
        as $fichier => $url
    ) {
        if (is_file($fichier)) {
            $photoUrl = $url;
            break;
        }
    }
}

$totalFormations = 0;
$totalCertificats = 0;
$progressionGlobale = 0;
$formationEnCours = '';
$prochainPaiementStatut = '';

if (tableExiste($con, 't_inscriptions') && tableExiste($con, 't_formations')) {
    $totalFormations = compter($con, 'SELECT COUNT(DISTINCT id_form) FROM t_inscriptions WHERE id_user = ?', [$idUser]);
    try {
        $req = $con->prepare(
            'SELECT f.titre_form FROM t_inscriptions i
             INNER JOIN t_formations f ON f.id_form = i.id_form
             WHERE i.id_user = ? ORDER BY i.id_form DESC LIMIT 1'
        );
        $req->execute([$idUser]);
        $formationEnCours = (string) ($req->fetchColumn() ?: '');
    } catch (Throwable $e) {
    }
}

if (tableExiste($con, 't_certificats')) {
    $totalCertificats = compter($con, 'SELECT COUNT(*) FROM t_certificats WHERE id_user = ?', [$idUser]);
}

if (tableExiste($con, 't_progression')) {
    $cols = colonnesTable($con, 't_progression');
    foreach (['pourcentage_progression', 'progression', 'pourcentage', 'progression_user'] as $c) {
        if (in_array($c, $cols, true)) {
            $progressionGlobale = (float) compter(
                $con,
                "SELECT COALESCE(AVG(`$c`), 0) FROM t_progression WHERE id_user = ?",
                [$idUser]
            );
            break;
        }
    }
}
$progressionGlobale = max(0, min(100, round($progressionGlobale)));

if (tableExiste($con, 't_paiements')) {
    try {
        $req = $con->prepare(
            'SELECT statut_paiement FROM t_paiements WHERE id_user = ? ORDER BY id_paiement DESC LIMIT 1'
        );
        $req->execute([$idUser]);
        $prochainPaiementStatut = (string) ($req->fetchColumn() ?: '');
    } catch (Throwable $e) {
    }
}

$contexteIA = [
    'prenom' => $prenom !== '' ? $prenom : 'là',
    'totalFormations' => $totalFormations,
    'totalCertificats' => $totalCertificats,
    'progression' => $progressionGlobale,
    'formationEnCours' => $formationEnCours,
    'paiementStatut' => $prochainPaiementStatut
];
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TOUN AI | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css">
        <style>
            .ia-page {
                padding: 24px 30px 30px;
                max-width: 1500px;
            }

            .ia-wrap {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 280px;
                gap: 18px;
                height: calc(100vh - 72px - 48px);
            }

            .ia-chat-card {
                display: flex;
                flex-direction: column;
                background: #fff;
                border: 1px solid #e1e9ef;
                border-radius: 18px;
                box-shadow: 0 9px 28px rgba(24, 33, 63, 0.045);
                overflow: hidden;
            }

            .ia-chat-head {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
                background: linear-gradient(120deg, #18213f 0%, #24558b 55%, #20a1b8 100%);
                color: #fff;
            }

            .ia-chat-avatar {
                width: 42px;
                height: 42px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.16);
                border: 1px solid rgba(255, 255, 255, 0.3);
                display: grid;
                place-items: center;
                font-size: 17px;
                flex-shrink: 0;
            }

            .ia-chat-head h1 {
                margin: 0;
                font-size: 13px;
            }

            .ia-chat-head span {
                display: flex;
                align-items: center;
                gap: 5px;
                margin-top: 2px;
                color: rgba(255, 255, 255, 0.75);
                font-size: 11px;
            }

            .ia-chat-head span::before {
                content: "";
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #4ade80;
            }

            .ia-chat-body {
                flex: 1;
                overflow-y: auto;
                padding: 22px 22px 10px;
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .ia-msg {
                display: flex;
                gap: 10px;
                max-width: 82%;
            }

            .ia-msg.user {
                align-self: flex-end;
                flex-direction: row-reverse;
            }

            .ia-msg-avatar {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                flex-shrink: 0;
                display: grid;
                place-items: center;
                font-size: 12px;
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
            }

            .ia-msg.user .ia-msg-avatar {
                background: linear-gradient(135deg, #18213f, #3f68ae);
            }

            .ia-bubble {
                padding: 11px 14px;
                border-radius: 14px;
                font-size: 12px;
                line-height: 1.65;
                background: #f2f6fa;
                color: #18213f;
                border: 1px solid #e7edf3;
            }

            .ia-msg.user .ia-bubble {
                background: linear-gradient(135deg, #18213f, #2a5490);
                color: #fff;
                border: 0;
            }

            .ia-bubble ul {
                margin: 6px 0 0;
                padding-left: 16px;
            }

            .ia-typing {
                display: flex;
                gap: 4px;
                padding: 13px 14px;
            }

            .ia-typing span {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #9fb0c4;
                animation: iaBlink 1.2s infinite ease-in-out;
            }

            .ia-typing span:nth-child(2) {
                animation-delay: 0.15s;
            }

            .ia-typing span:nth-child(3) {
                animation-delay: 0.3s;
            }

            @keyframes iaBlink {
                0%,
                80%,
                100% {
                    opacity: 0.25;
                    transform: translateY(0);
                }

                40% {
                    opacity: 1;
                    transform: translateY(-3px);
                }
            }

            .ia-suggestions {
                display: flex;
                flex-wrap: wrap;
                gap: 7px;
                padding: 0 22px 14px;
            }

            .ia-chip {
                padding: 7px 12px;
                border-radius: 20px;
                background: #eef6f9;
                border: 1px solid #dcecf1;
                color: #18729c;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .ia-chip:hover {
                background: #e2f2f6;
                transform: translateY(-1px);
            }

            .ia-input-row {
                display: flex;
                gap: 10px;
                padding: 14px 18px;
                border-top: 1px solid #e9eef3;
            }

            .ia-input-row input {
                flex: 1;
                height: 42px;
                border: 1px solid #dfe7ee;
                border-radius: 12px;
                padding: 0 15px;
                font: inherit;
                font-size: 12px;
                outline: 0;
                background: #f8fafc;
            }

            .ia-input-row input:focus {
                border-color: #8fc7d4;
            }

            .ia-send {
                width: 42px;
                height: 42px;
                border: 0;
                border-radius: 12px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                display: grid;
                place-items: center;
                cursor: pointer;
                flex-shrink: 0;
            }

            .ia-side {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .ia-side .card {
                padding: 17px;
            }

            .ia-side h2 {
                margin: 0 0 10px;
                font-size: 12px;
                color: #18213f;
            }

            .ia-fact {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 9px;
                border-radius: 10px;
                background: #f7fafc;
                margin-bottom: 6px;
            }

            .ia-fact i {
                width: 26px;
                height: 26px;
                border-radius: 8px;
                background: #edf8fa;
                color: #20a1b8;
                display: grid;
                place-items: center;
                font-size: 11.5px;
                flex-shrink: 0;
            }

            .ia-fact span {
                font-size: 11px;
                color: #7b899b;
            }

            .ia-fact strong {
                display: block;
                font-size: 11px;
                color: #18213f;
            }

            .ia-note {
                font-size: 11px;
                color: #93a0b2;
                line-height: 1.6;
            }

            @media (max-width: 1100px) {
                .ia-wrap {
                    grid-template-columns: 1fr;
                    height: auto;
                }

                .ia-side {
                    flex-direction: row;
                    flex-wrap: wrap;
                }

                .ia-side .card {
                    flex: 1;
                    min-width: 220px;
                }
            }

            @media (max-width: 800px) {
                .ia-page {
                    padding: 18px;
                }

                .ia-msg {
                    max-width: 92%;
                }
            }
        </style>
    </head>
    <body>
        <div class="layout">
            <?php include __DIR__ . '/menu.php'; ?>

            <main class="main">
                <header class="topbar">
                    <button class="mobile-menu" type="button" id="mobileMenu" aria-label="Ouvrir le menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Rechercher une formation...">
                    </div>
                    <div class="top-right">
                        <a href="notifications.php" class="notification" aria-label="Notifications">
                            <i class="fa-regular fa-bell"></i>
                            <span></span>
                        </a>
                        <a href="profil.php" class="profile">
                            <div class="profile-avatar">
                                <?php if ($photoUrl !== ''): ?>
                                    <img src="<?= e($photoUrl) ?>" alt="Photo de profil">
                                <?php else: ?>
                                    <?= e($initiales) ?>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <strong><?= e($nomAffiche) ?></strong>
                                <small>Apprenant</small>
                            </div>
                        </a>
                    </div>
                </header>

                <section class="ia-page">
                    <div class="ia-wrap">
                        <div class="ia-chat-card">
                            <div class="ia-chat-head">
                                <div class="ia-chat-avatar"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                                <div>
                                    <h1>TOUN AI</h1>
                                    <span>Assistant TOUN — en ligne</span>
                                </div>
                            </div>

                            <div class="ia-chat-body" id="iaBody"></div>

                            <div class="ia-suggestions" id="iaSuggestions">
                                <div class="ia-chip" data-q="Où en sont mes formations ?">Mes formations</div>
                                <div class="ia-chip" data-q="Quelle est ma progression ?">Ma progression</div>
                                <div class="ia-chip" data-q="Comment obtenir un certificat ?">Certificats</div>
                                <div class="ia-chip" data-q="Comment contacter un formateur ?">Contacter un formateur</div>
                                <div class="ia-chip" data-q="Comment fonctionnent les paiements ?">Paiements</div>
                            </div>

                            <form class="ia-input-row" id="iaForm">
                                <input type="text" id="iaInput" placeholder="Écrivez votre question à TOUN AI..." autocomplete="off">
                                <button type="submit" class="ia-send" aria-label="Envoyer"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>

                        <aside class="ia-side">
                            <div class="card">
                                <h2>Votre contexte</h2>
                                <div class="ia-fact">
                                    <i class="fa-solid fa-book-open"></i>
                                    <div><strong><?= (int) $totalFormations ?> formation(s)</strong><span>En cours ou terminées</span></div>
                                </div>
                                <div class="ia-fact">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <div><strong><?= (int) $progressionGlobale ?>% de progression</strong><span>Moyenne sur vos formations</span></div>
                                </div>
                                <div class="ia-fact">
                                    <i class="fa-solid fa-award"></i>
                                    <div><strong><?= (int) $totalCertificats ?> certificat(s)</strong><span>Obtenus sur TOUN</span></div>
                                </div>
                            </div>
                            <div class="card">
                                <h2>À savoir</h2>
                                <p class="ia-note">TOUN AI répond à partir des informations disponibles sur votre compte et sur la plateforme. Pour toute question nécessitant une intervention humaine, utilisez <a href="messages.php" style="color:#20a1b8;font-weight:600">Messages</a> pour écrire directement à l'équipe TOUN.</p>
                            </div>
                        </aside>
                    </div>
                </section>
            </main>
        </div>

        <script>
            const mobileMenu = document.getElementById('mobileMenu');
            const sidebar = document.getElementById('sidebar');
            if (mobileMenu && sidebar) {
                mobileMenu.addEventListener('click', () => sidebar.classList.toggle('open'));
            }

            const TOUN_CTX = <?= json_encode($contexteIA, JSON_UNESCAPED_UNICODE) ?>;

            const iaBody = document.getElementById('iaBody');
            const iaForm = document.getElementById('iaForm');
            const iaInput = document.getElementById('iaInput');

            function iaScroll() {
                iaBody.scrollTop = iaBody.scrollHeight;
            }

            function iaAddMessage(text, who) {
                const row = document.createElement('div');
                row.className = 'ia-msg ' + (who === 'user' ? 'user' : 'bot');
                const avatar = document.createElement('div');
                avatar.className = 'ia-msg-avatar';
                avatar.innerHTML =
                    who === 'user' ? '<i class="fa-solid fa-user"></i>' : '<i class="fa-solid fa-wand-magic-sparkles"></i>';
                const bubble = document.createElement('div');
                bubble.className = 'ia-bubble';
                bubble.innerHTML = text;
                row.appendChild(avatar);
                row.appendChild(bubble);
                iaBody.appendChild(row);
                iaScroll();
            }

            function iaTyping(show) {
                let el = document.getElementById('iaTypingRow');
                if (show) {
                    if (el) return;
                    el = document.createElement('div');
                    el.className = 'ia-msg bot';
                    el.id = 'iaTypingRow';
                    el.innerHTML =
                        '<div class="ia-msg-avatar"><i class="fa-solid fa-wand-magic-sparkles"></i></div><div class="ia-bubble ia-typing"><span></span><span></span><span></span></div>';
                    iaBody.appendChild(el);
                    iaScroll();
                } else if (el) {
                    el.remove();
                }
            }

            function stripAccents(s) {
                return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
            }

            const INTENTS = [
                {
                    keys: ['bonjour', 'salut', 'bonsoir', 'hello', 'coucou'],
                    reply: () =>
                        `Bonjour ${TOUN_CTX.prenom} ! Je suis TOUN AI, votre assistant sur la plateforme. Je peux vous renseigner sur vos formations, votre progression, vos certificats, vos paiements ou la navigation sur TOUN. Que puis-je faire pour vous ?`
                },
                {
                    keys: ['formation', 'cours', 'catalogue', 'apprend'],
                    reply: () =>
                        TOUN_CTX.totalFormations > 0
                            ? `Vous suivez actuellement <strong>${TOUN_CTX.totalFormations}</strong> formation(s)${TOUN_CTX.formationEnCours ? `, dont « ${TOUN_CTX.formationEnCours} »` : ''}. Retrouvez-les dans <a href="mes-formations.php">Mes formations</a>, ou explorez le <a href="../formation.php">catalogue complet</a> pour en démarrer une nouvelle.`
                            : `Vous n'avez pas encore de formation en cours. Découvrez le <a href="../formation.php">catalogue TOUN</a> pour choisir celle qui correspond à vos objectifs.`
                },
                {
                    keys: ['progression', 'avancement', 'ou j en suis', 'niveau'],
                    reply: () =>
                        `Votre progression moyenne est de <strong>${TOUN_CTX.progression}%</strong>. Vous pouvez suivre le détail module par module dans <a href="progression.php">Ma progression</a>.`
                },
                {
                    keys: ['certificat', 'diplome', 'attestation'],
                    reply: () =>
                        TOUN_CTX.totalCertificats > 0
                            ? `Vous avez déjà obtenu <strong>${TOUN_CTX.totalCertificats}</strong> certificat(s) ! Retrouvez-les et téléchargez-les dans <a href="certificats.php">Mes certificats</a>.`
                            : `Un certificat est délivré automatiquement lorsque vous terminez une formation et validez son évaluation. Suivez votre progression dans <a href="progression.php">Ma progression</a> — vos certificats apparaîtront ensuite dans <a href="certificats.php">Mes certificats</a>.`
                },
                {
                    keys: ['evaluation', 'quiz', 'examen', 'test'],
                    reply: () =>
                        `Les évaluations de vos formations sont disponibles dans <a href="evaluations.php">Mes évaluations</a>. Elles valident vos acquis avant l'obtention du certificat.`
                },
                {
                    keys: ['paiement', 'payer', 'facture', 'prix', 'tarif', 'fcfa', 'wave', 'orange money'],
                    reply: () =>
                        `TOUN accepte les paiements mobiles (Wave, Orange Money) et par carte. L'historique complet de vos transactions et leur statut est disponible dans <a href="paiements.php">Mes paiements</a>.`
                },
                {
                    keys: [
                        'formateur',
                        'professeur',
                        'enseignant',
                        'contacter quelqu un',
                        'parler a un humain',
                        'aide humaine',
                        'rendez-vous',
                        'rendez vous'
                    ],
                    reply: () =>
                        `Pour échanger avec un formateur ou l'équipe pédagogique, prenez rendez-vous depuis <a href="rendez-vous.php">Mes rendez-vous</a>, ou écrivez directement dans <a href="messages.php">Messages</a> — l'équipe TOUN vous répondra depuis là.`
                },
                {
                    keys: ['message', 'contact', 'support', 'ecrire', 'assistance', 'probleme', 'bug'],
                    reply: () =>
                        `Vous pouvez écrire directement à l'équipe TOUN depuis <a href="messages.php">Messages</a> — vos échanges y restent visibles et vous recevrez une réponse.`
                },
                {
                    keys: ['profil', 'compte', 'mot de passe', 'photo', 'email', 'coordonnees'],
                    reply: () =>
                        `Vos informations personnelles, votre sécurité et vos préférences de notifications se gèrent depuis <a href="profil.php">Mon profil</a> (onglet Paramètres).`
                },
                {
                    keys: ['ressource', 'document', 'support de cours', 'pdf', 'telecharger'],
                    reply: () =>
                        `Les documents et supports liés à vos formations sont regroupés dans <a href="ressources.php">Ressources</a>.`
                },
                {
                    keys: ['notification', 'nouveaute', 'actualite'],
                    reply: () =>
                        `Toutes vos notifications (certificats, paiements, messages, nouvelles formations) sont centralisées dans <a href="notifications.php">Notifications</a>.`
                },
                {
                    keys: ['merci', 'super', 'parfait', 'top', 'cool'],
                    reply: () => `Avec plaisir ${TOUN_CTX.prenom} ! N'hésitez pas si vous avez d'autres questions.`
                },
                {
                    keys: ['au revoir', 'bye', 'a bientot', 'ciao'],
                    reply: () => `À bientôt sur TOUN, bonne continuation dans votre apprentissage !`
                }
            ];

            function iaAnswer(message) {
                const norm = stripAccents(message);
                let best = null;
                let bestScore = 0;
                INTENTS.forEach(intent => {
                    let score = 0;
                    intent.keys.forEach(k => {
                        if (norm.includes(stripAccents(k))) score++;
                    });
                    if (score > bestScore) {
                        bestScore = score;
                        best = intent;
                    }
                });
                if (best) return best.reply();
                return `Je n'ai pas toute l'information pour répondre précisément à cela, ${TOUN_CTX.prenom}. Vous pouvez me parler de vos <strong>formations</strong>, votre <strong>progression</strong>, vos <strong>certificats</strong>, vos <strong>paiements</strong>, votre <strong>profil</strong> ou vos <strong>rendez-vous</strong>. Pour une question plus précise, écrivez à l'équipe via <a href="messages.php">Messages</a>.`;
            }

            function iaSend(text) {
                text = text.trim();
                if (text === '') return;
                iaAddMessage(text.replace(/</g, '&lt;'), 'user');
                iaInput.value = '';
                iaTyping(true);
                const delay = 450 + Math.random() * 500;
                setTimeout(() => {
                    iaTyping(false);
                    iaAddMessage(iaAnswer(text), 'bot');
                }, delay);
            }

            iaForm.addEventListener('submit', e => {
                e.preventDefault();
                iaSend(iaInput.value);
            });

            document.querySelectorAll('.ia-chip').forEach(chip => {
                chip.addEventListener('click', () => iaSend(chip.dataset.q));
            });

            iaAddMessage(
                `Bonjour ${TOUN_CTX.prenom} ! Je suis <strong>TOUN AI</strong>, votre assistant sur la plateforme. Posez-moi une question sur vos formations, votre progression, vos certificats ou vos paiements — ou choisissez une suggestion ci-dessous.`,
                'bot'
            );
        </script>
    </body>
</html>

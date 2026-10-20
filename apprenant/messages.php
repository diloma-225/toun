<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

try {
    $con->exec(
        "CREATE TABLE IF NOT EXISTS t_messages (
            id_message INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_user INT UNSIGNED NOT NULL,
            expediteur VARCHAR(20) NOT NULL DEFAULT 'apprenant',
            contenu TEXT NOT NULL,
            date_message DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            lu_apprenant TINYINT(1) NOT NULL DEFAULT 0,
            lu_admin TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id_message),
            INDEX idx_msg_user (id_user),
            INDEX idx_msg_date (date_message)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
}

$idUser = (int) $_SESSION['id_user'];

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $con->prepare("UPDATE t_messages SET lu_apprenant = 1 WHERE id_user = ? AND expediteur = 'admin'")->execute([
            $idUser
        ]);
        $req = $con->prepare(
            'SELECT id_message, expediteur, contenu, date_message FROM t_messages WHERE id_user = ? ORDER BY id_message ASC'
        );
        $req->execute([$idUser]);
        echo json_encode(['ok' => true, 'messages' => $req->fetchAll(PDO::FETCH_ASSOC) ?: []], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'messages' => []]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu'])) {
    $contenu = trim((string) $_POST['contenu']);
    if ($contenu !== '') {
        try {
            $req = $con->prepare("INSERT INTO t_messages (id_user, expediteur, contenu) VALUES (?, 'apprenant', ?)");
            $req->execute([$idUser, mb_substr($contenu, 0, 2000)]);
        } catch (Throwable $e) {
        }
    }
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit();
    }
    header('Location: messages.php');
    exit();
}

try {
    $con->prepare("UPDATE t_messages SET lu_apprenant = 1 WHERE id_user = ? AND expediteur = 'admin'")->execute([
        $idUser
    ]);
} catch (Throwable $e) {
}

$fil = [];
try {
    $req = $con->prepare(
        'SELECT id_message, expediteur, contenu, date_message FROM t_messages WHERE id_user = ? ORDER BY id_message ASC'
    );
    $req->execute([$idUser]);
    $fil = $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $fil = [];
}

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
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Messages | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <style>
            .msg-page {
                padding: 24px 30px 30px;
                max-width: 1100px;
            }

            .msg-card {
                display: flex;
                flex-direction: column;
                height: calc(100vh - 72px - 48px);
                min-height: 480px;
                background: #fff;
                border: 1px solid #e1e9ef;
                border-radius: 18px;
                box-shadow: 0 9px 28px rgba(24, 33, 63, 0.045);
                overflow: hidden;
            }

            .msg-head {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
                background: linear-gradient(120deg, #18213f 0%, #24558b 55%, #20a1b8 100%);
                color: #fff;
            }

            .msg-avatar {
                width: 42px;
                height: 42px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.16);
                border: 1px solid rgba(255, 255, 255, 0.3);
                display: grid;
                place-items: center;
                font-size: 16px;
                flex-shrink: 0;
            }

            .msg-head h1 {
                margin: 0;
                font-size: 13px;
            }

            .msg-head span {
                display: block;
                margin-top: 2px;
                color: rgba(255, 255, 255, 0.75);
                font-size: 11px;
            }

            .msg-body {
                flex: 1;
                overflow-y: auto;
                padding: 22px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .msg-row {
                display: flex;
                gap: 10px;
                max-width: 78%;
            }

            .msg-row.mine {
                align-self: flex-end;
                flex-direction: row-reverse;
            }

            .msg-row-avatar {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                flex-shrink: 0;
                display: grid;
                place-items: center;
                font-size: 11.5px;
                color: #fff;
                background: linear-gradient(135deg, #18213f, #3f68ae);
            }

            .msg-row.mine .msg-row-avatar {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
            }

            .msg-bubble {
                padding: 10px 13px;
                border-radius: 13px;
                background: #f2f6fa;
                color: #18213f;
                border: 1px solid #e7edf3;
                font-size: 12px;
                line-height: 1.6;
            }

            .msg-row.mine .msg-bubble {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                border: 0;
            }

            .msg-time {
                display: block;
                margin-top: 4px;
                font-size: 10px;
                color: #9aa7b6;
            }

            .msg-row.mine .msg-time {
                color: rgba(255, 255, 255, 0.7);
            }

            .msg-empty {
                margin: auto;
                text-align: center;
                color: #8b98a8;
            }

            .msg-empty i {
                font-size: 26px;
                color: #20a1b8;
                margin-bottom: 8px;
                display: block;
            }

            .msg-empty p {
                font-size: 11px;
                max-width: 280px;
            }

            .msg-input-row {
                display: flex;
                gap: 10px;
                padding: 14px 18px;
                border-top: 1px solid #e9eef3;
            }

            .msg-input-row textarea {
                flex: 1;
                resize: none;
                height: 42px;
                max-height: 110px;
                border: 1px solid #dfe7ee;
                border-radius: 12px;
                padding: 10px 15px;
                font: inherit;
                font-size: 12px;
                outline: 0;
                background: #f8fafc;
            }

            .msg-input-row textarea:focus {
                border-color: #8fc7d4;
            }

            .msg-send {
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

            .msg-note {
                padding: 9px 20px;
                background: #f7fafc;
                border-top: 1px solid #eef2f6;
                color: #8a94a5;
                font-size: 10.5px;
                text-align: center;
            }

            @media (max-width: 700px) {
                .msg-page {
                    padding: 18px;
                }

                .msg-row {
                    max-width: 90%;
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

                <section class="msg-page">
                    <div class="msg-card">
                        <div class="msg-head">
                            <div class="msg-avatar"><i class="fa-solid fa-headset"></i></div>
                            <div>
                                <h1>Messages</h1>
                                <span>Écrivez à l'équipe TOUN, elle vous répond ici</span>
                            </div>
                        </div>

                        <div class="msg-body" id="msgBody">
                            <?php if (!$fil): ?>
                                <div class="msg-empty">
                                    <i class="fa-regular fa-comments"></i>
                                    <p>Aucun message pour le moment. Écrivez à l'équipe TOUN ci-dessous : elle vous répondra directement dans cette conversation.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($fil as $m): ?>
                                    <?php $mine = $m['expediteur'] === 'apprenant'; ?>
                                    <div class="msg-row <?= $mine ? 'mine' : '' ?>">
                                        <div class="msg-row-avatar">
                                            <?php if ($mine): ?>
                                                <?= e($initiales) ?>
                                            <?php else: ?>
                                                <i class="fa-solid fa-headset"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="msg-bubble">
                                            <?= nl2br(e($m['contenu'])) ?>
                                            <span class="msg-time"><?= !empty($m['date_message']) ? date('d/m/Y H:i', strtotime($m['date_message'])) : '' ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form class="msg-input-row" id="msgForm">
                            <textarea id="msgInput" placeholder="Écrivez votre message..." required></textarea>
                            <button type="submit" class="msg-send" aria-label="Envoyer"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                        <div class="msg-note">L'équipe TOUN répond généralement sous 24h ouvrées.</div>
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

            const msgBody = document.getElementById('msgBody');
            const msgForm = document.getElementById('msgForm');
            const msgInput = document.getElementById('msgInput');
            const INITIALES = <?= json_encode($initiales) ?>;
            let lastCount = <?= count($fil) ?>;

            function scrollBottom() {
                msgBody.scrollTop = msgBody.scrollHeight;
            }
            scrollBottom();

            function renderMessages(list) {
                if (!list.length) {
                    msgBody.innerHTML =
                        '<div class="msg-empty"><i class="fa-regular fa-comments"></i><p>Aucun message pour le moment. Écrivez à l\'équipe TOUN ci-dessous : elle vous répondra directement dans cette conversation.</p></div>';
                    return;
                }
                msgBody.innerHTML = '';
                list.forEach(m => {
                    const mine = m.expediteur === 'apprenant';
                    const row = document.createElement('div');
                    row.className = 'msg-row' + (mine ? ' mine' : '');
                    const d = m.date_message ? new Date(m.date_message.replace(' ', 'T')) : null;
                    const timeStr = d
                        ? d.toLocaleDateString('fr-FR') +
                          ' ' +
                          d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
                        : '';
                    row.innerHTML = `<div class="msg-row-avatar">${mine ? INITIALES : '<i class="fa-solid fa-headset"></i>'}</div>
            <div class="msg-bubble">${(m.contenu || '').replace(/</g, '&lt;').replace(/\n/g, '<br>')}<span class="msg-time">${timeStr}</span></div>`;
                    msgBody.appendChild(row);
                });
                scrollBottom();
            }

            async function refreshThread(force) {
                try {
                    const res = await fetch('messages.php?ajax=1', { cache: 'no-store' });
                    const data = await res.json();
                    if (data.ok && (force || data.messages.length !== lastCount)) {
                        lastCount = data.messages.length;
                        renderMessages(data.messages);
                    }
                } catch (e) {}
            }

            msgForm.addEventListener('submit', async e => {
                e.preventDefault();
                const text = msgInput.value.trim();
                if (text === '') return;
                msgInput.value = '';
                const body = new URLSearchParams();
                body.set('contenu', text);
                body.set('ajax', '1');
                try {
                    await fetch('messages.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body
                    });
                } catch (e) {}
                refreshThread(true);
            });

            msgInput.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    msgForm.requestSubmit();
                }
            });

            setInterval(() => refreshThread(false), 8000);
        </script>
    </body>
</html>

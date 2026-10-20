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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUserPost = (int) ($_POST['id_user'] ?? 0);
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    if ($idUserPost > 0 && $contenu !== '') {
        try {
            $req = $con->prepare("INSERT INTO t_messages (id_user, expediteur, contenu) VALUES (?, 'admin', ?)");
            $req->execute([$idUserPost, mb_substr($contenu, 0, 2000)]);
            $_SESSION['flash_success'] = 'Message envoyé.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Le message n'a pas pu être envoyé.";
        }
    }
    header('Location: messagerie.php?id_user=' . $idUserPost);
    exit();
}

$conversations = [];
try {
    $conversations =
        $con
            ->query(
                "SELECT m.id_user,
                u.prenom_user, u.nom_user, u.email_user,
                MAX(m.date_message) AS last_date,
                SUM(CASE WHEN m.expediteur = 'apprenant' AND m.lu_admin = 0 THEN 1 ELSE 0 END) AS unread
         FROM t_messages m
         LEFT JOIN t_utilisateurs u ON u.id_user = m.id_user
         GROUP BY m.id_user
         ORDER BY last_date DESC
         LIMIT 200"
            )
            ->fetchAll(PDO::FETCH_ASSOC) ?:
        [];
} catch (Throwable $e) {
}

$idUserActif = isset($_GET['id_user']) ? (int) $_GET['id_user'] : (int) ($conversations[0]['id_user'] ?? 0);

$fil = [];
$contactActif = null;
if ($idUserActif > 0) {
    foreach ($conversations as $c) {
        if ((int) $c['id_user'] === $idUserActif) {
            $contactActif = $c;
            break;
        }
    }
    try {
        $con->prepare("UPDATE t_messages SET lu_admin = 1 WHERE id_user = ? AND expediteur = 'apprenant'")->execute([
            $idUserActif
        ]);
        $req = $con->prepare(
            'SELECT id_message, expediteur, contenu, date_message FROM t_messages WHERE id_user = ? ORDER BY id_message ASC'
        );
        $req->execute([$idUserActif]);
        $fil = $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
    }
}

$totalNonLus = 0;
foreach ($conversations as $c) {
    $totalNonLus += (int) $c['unread'];
}
?>

<style>
    .msgr-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .msgr-hero {
        min-height: 94px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 17px 21px;
        margin-bottom: 13px;
        border-radius: 18px;
        background: linear-gradient(110deg, #18213f, #3f68ae 60%, #20a1b8);
        color: #fff;
        box-shadow: 0 9px 22px rgba(24, 33, 63, 0.1);
    }

    .msgr-hero h1 {
        margin: 0;
        font-size: 21px;
    }

    .msgr-hero p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .msgr-count {
        min-width: 31px;
        height: 31px;
        display: grid;
        place-items: center;
        padding: 0 9px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.12);
        font-size: 11px;
    }

    .msgr-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
        height: calc(100vh - 68px - 150px);
        min-height: 460px;
    }

    .msgr-select {
        height: 34px;
        padding: 0 10px;
        border: 1px solid #dce7ed;
        border-radius: 9px;
        background: #fff;
        color: #18213f;
        font:
            11px "Poppins",
            sans-serif;
        outline: 0;
    }

    .msgr-thread {
        display: flex;
        flex-direction: column;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
        overflow: hidden;
    }

    .msgr-thread-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid #eef3f6;
        background: #f8fbfc;
    }

    .msgr-thread-head strong {
        display: block;
        font-size: 12px;
        color: #18213f;
    }

    .msgr-thread-head span {
        display: block;
        margin-top: 2px;
        font-size: 11px;
        color: #8996a5;
    }

    .msgr-thread-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 11px;
    }

    .msgr-row {
        display: flex;
        gap: 9px;
        max-width: 74%;
    }

    .msgr-row.mine {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .msgr-row-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        flex-shrink: 0;
        display: grid;
        place-items: center;
        font-size: 11px;
        color: #fff;
        background: linear-gradient(135deg, #18213f, #20a1b8);
    }

    .msgr-row.mine .msgr-row-avatar {
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
    }

    .msgr-bubble {
        padding: 9px 12px;
        border-radius: 12px;
        background: #f2f6fa;
        color: #18213f;
        border: 1px solid #e7edf3;
        font-size: 11px;
        line-height: 1.6;
    }

    .msgr-row.mine .msgr-bubble {
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
        border: 0;
    }

    .msgr-time {
        display: block;
        margin-top: 3px;
        font-size: 10px;
        color: #9aa7b6;
    }

    .msgr-row.mine .msgr-time {
        color: rgba(255, 255, 255, 0.7);
    }

    .msgr-form {
        display: flex;
        gap: 9px;
        padding: 12px 16px;
        border-top: 1px solid #eef3f6;
    }

    .msgr-form textarea {
        flex: 1;
        resize: none;
        height: 40px;
        border: 1px solid #dfe7ee;
        border-radius: 10px;
        padding: 9px 12px;
        font: inherit;
        font-size: 11px;
        outline: 0;
    }

    .msgr-form button {
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
        display: grid;
        place-items: center;
        cursor: pointer;
        flex-shrink: 0;
    }

    .msgr-thread-empty {
        margin: auto;
        text-align: center;
        color: #8996a5;
        padding: 40px;
    }

    .msgr-thread-empty i {
        display: block;
        font-size: 26px;
        color: #20a1b8;
        margin-bottom: 8px;
    }

    @media (max-width: 900px) {
        .msgr-layout {
            height: auto;
        }

        .msgr-thread {
            height: 480px;
        }
    }
</style>

<div class="main">
    <section class="msgr-page">
        <header class="msgr-hero">
            <div>
                <h1>Messagerie apprenants</h1>
                <p>Répondez directement aux apprenants qui vous écrivent depuis leur tableau de bord.</p>
            </div>
            <span class="msgr-count"><?= $totalNonLus ?> non lu(s)</span>
        </header>

        <div class="msgr-layout">
            <div class="msgr-thread">
                <?php if (!$conversations): ?>
                    <div class="msgr-thread-empty">
                        <i class="fa-regular fa-comments"></i>
                        Aucune conversation pour le moment.
                    </div>
                <?php elseif (!$contactActif): ?>
                    <div class="msgr-thread-empty">
                        <i class="fa-regular fa-comments"></i>
                        Sélectionnez une conversation pour répondre à un apprenant.
                    </div>
                <?php else: ?>
                    <?php
$nomContact = trim(($contactActif['prenom_user'] ?? '') . ' ' . ($contactActif['nom_user'] ?? ''));
if ($nomContact === '') {
    $nomContact = 'Apprenant #' . $idUserActif;
}
?>
                    <div class="msgr-thread-head">
                        <div>
                            <strong><?= e($nomContact) ?></strong>
                            <span><?= e($contactActif['email_user'] ?? '') ?></span>
                        </div>
                        <select class="msgr-select" onchange="location.href='messagerie.php?id_user=' + this.value">
                            <?php foreach ($conversations as $c): ?>
                                <?php
$nomOption = trim(($c['prenom_user'] ?? '') . ' ' . ($c['nom_user'] ?? ''));
if ($nomOption === '') {
    $nomOption = 'Apprenant #' . (int) $c['id_user'];
}
if ((int) $c['unread'] > 0) {
    $nomOption .= ' (' . (int) $c['unread'] . ' non lu)';
}
?>
                                <option value="<?= (int) $c['id_user'] ?>" <?= (int) $c['id_user'] === $idUserActif ? 'selected' : '' ?>>
                                    <?= e($nomOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="msgr-thread-body" id="msgrBody">
                        <?php foreach ($fil as $m): ?>
                            <?php $mine = $m['expediteur'] === 'admin'; ?>
                            <div class="msgr-row <?= $mine ? 'mine' : '' ?>">
                                <div class="msgr-row-avatar"><i class="fa-solid <?= $mine ? 'fa-headset' : 'fa-user' ?>"></i></div>
                                <div class="msgr-bubble">
                                    <?= nl2br(e($m['contenu'])) ?>
                                    <span class="msgr-time"><?= !empty($m['date_message']) ? date('d/m/Y H:i', strtotime($m['date_message'])) : '' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form class="msgr-form" method="post" action="messagerie.php">
                        <input type="hidden" name="id_user" value="<?= $idUserActif ?>">
                        <textarea name="contenu" placeholder="Répondre..." required></textarea>
                        <button type="submit" aria-label="Envoyer"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script>
    const msgrBody = document.getElementById('msgrBody');
    if (msgrBody) {
        msgrBody.scrollTop = msgrBody.scrollHeight;
    }
</script>

<?php
$active_page = 'messagerie.php';
include 'menu.php';
?>

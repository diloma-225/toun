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
        "CREATE TABLE IF NOT EXISTS t_messages_contact (
            id_message INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nom_message VARCHAR(100) NOT NULL,
            email_message VARCHAR(150) NOT NULL,
            sujet_message VARCHAR(180) NOT NULL,
            message_message TEXT NOT NULL,
            date_message DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            statut_message VARCHAR(20) NOT NULL DEFAULT 'nouveau',
            PRIMARY KEY (id_message),
            INDEX idx_message_statut (statut_message),
            INDEX idx_message_date (date_message)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
}

$rows = [];
try {
    $rows = $con
        ->query('SELECT * FROM t_messages_contact ORDER BY id_message DESC LIMIT 100')
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$newCount = count(array_filter($rows, fn($row) => strtolower((string) ($row['statut_message'] ?? '')) === 'nouveau'));
?>

<style>
    .message-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .message-hero {
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

    .message-hero h1 {
        margin: 0;
        font-size: 21px;
    }

    .message-hero p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .message-count {
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

    .message-panel {
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .message-toolbar {
        min-height: 58px;
        display: flex;
        align-items: center;
        padding: 10px 14px;
        background: #f1f7f9;
        border-bottom: 1px solid #dce7ed;
    }

    .message-search {
        width: 320px;
        height: 36px;
        padding: 0 10px;
        border: 1px solid #d2e0e7;
        border-radius: 9px;
        outline: 0;
        font:
            11px "Poppins",
            sans-serif;
    }

    .message-table-wrap {
        overflow-x: auto;
    }

    .message-table {
        width: 100%;
        border-collapse: collapse;
    }

    .message-table th {
        padding: 10px 13px;
        background: #e5eff3;
        color: #536a7f;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .message-table td {
        padding: 11px 13px;
        border-top: 1px solid #ecf1f4;
        color: #45566a;
        font-size: 11px;
    }

    .message-table td strong {
        color: #18213f;
    }

    .message-new {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        background: #e5f6f7;
        color: #158090;
        font-size: 10px;
        font-weight: 700;
    }

    .message-read {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        background: #edf1f6;
        color: #708096;
        font-size: 10px;
    }

    .message-repondu {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        background: #e7f5ec;
        color: #23915d;
        font-size: 10px;
        font-weight: 700;
    }

    .message-action {
        width: 28px;
        height: 28px;
        display: inline-grid;
        place-items: center;
        border-radius: 7px;
        background: #eaf0fa;
        color: #3f68ae;
        text-decoration: none;
    }

    .message-action.delete {
        background: #fdebec;
        color: #c34a4a;
    }

    .message-empty {
        padding: 45px 20px;
        text-align: center;
        color: #8996a5;
        font-size: 11px;
        background: #f7fafb;
    }

    .message-empty i {
        display: block;
        margin-bottom: 8px;
        color: #20a1b8;
        font-size: 27px;
    }

    @media (max-width: 600px) {
        .message-page {
            padding: 13px 10px 20px;
        }

        .message-search {
            width: 100%;
        }
    }
</style>

<div class="main">
    <section class="message-page">
        <header class="message-hero">
            <div>
                <h1>Messages</h1>
                <p>Retrouvez les demandes envoyées depuis le formulaire Contact.</p>
            </div>
            <span class="message-count"><?= $newCount ?> nouveau(x)</span>
        </header>

        <section class="message-panel">
            <div class="message-toolbar">
                <input id="messageSearch" class="message-search" type="search" placeholder="Rechercher un nom, un e-mail ou un sujet...">
            </div>

            <div class="message-table-wrap">
                <table class="message-table" id="messageTable">
                    <thead>
                        <tr>
                            <th>Expéditeur</th>
                            <th>E-mail</th>
                            <th>Sujet</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="message-empty">
                                        <i class="fa-regular fa-envelope"></i>
                                        Aucun message reçu pour le moment.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
$statut = strtolower((string) ($row['statut_message'] ?? ''));
$classeStatut = 'message-read';
if ($statut === 'nouveau') {
    $classeStatut = 'message-new';
} elseif ($statut === 'repondu') {
    $classeStatut = 'message-repondu';
}
$libelleStatut = $statut === 'repondu' ? 'répondu' : $row['statut_message'] ?? 'lu';
?>
                                <tr>
                                    <td><strong><?= e($row['nom_message'] ?? '—') ?></strong></td>
                                    <td><?= e($row['email_message'] ?? '—') ?></td>
                                    <td><?= e($row['sujet_message'] ?? '—') ?></td>
                                    <td><?= e($row['date_message'] ?? '—') ?></td>
                                    <td>
                                        <span class="<?= $classeStatut ?>">
                                            <?= e($libelleStatut) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="message-action" href="voir_message.php?id=<?= (int) $row['id_message'] ?>" title="Voir">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a class="message-action delete" href="supprimer_message.php?id=<?= (int) $row['id_message'] ?>" title="Supprimer" onclick="return confirm('Supprimer ce message ?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>

<script>
    document.getElementById('messageSearch')?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();

        document.querySelectorAll('#messageTable tbody tr').forEach(function (row) {
            row.style.display = !query || row.innerText.toLowerCase().includes(query) ? '' : 'none';
        });
    });
</script>

<?php
$active_page = 'messages.php';
include 'menu.php';
?>

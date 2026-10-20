<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'notifications.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Notifications — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .notification-list {
                padding: 12px;
            }

            .notification {
                display: flex;
                gap: 10px;
                padding: 11px;
                border: 1px solid #e5edf0;
                border-radius: 9px;
                background: #fbfdfe;
                margin-bottom: 7px;
            }

            .notif-icon {
                width: 30px;
                height: 30px;
                min-width: 30px;
                border-radius: 8px;
                background: #eef8fa;
                color: #168b9a;
                display: grid;
                place-items: center;
            }

            .notification strong {
                font-size: 10.5px;
                color: #294965;
            }

            .notification p {
                margin: 3px 0 0;
                color: #8a9ca9;
                font-size: 10px;
                line-height: 1.5;
            }

            .empty {
                text-align: center;
                padding: 45px;
                color: #8fa0ac;
                font-size: 10.5px;
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">

                <?php
$rows = [];
$available = tableExists($con, 't_notifications');
if ($available) {
    $cols = $con
        ->query(
            "SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='t_notifications'"
        )
        ->fetchAll(PDO::FETCH_COLUMN);
    $userCol = in_array('id_user', $cols, true) ? 'id_user' : null;
    if ($userCol) {
        $st = $con->prepare(
            'SELECT * FROM t_notifications WHERE id_user=? ORDER BY ' .
                (in_array('date_notification', $cols, true) ? 'date_notification' : 'id_notification') .
                ' DESC'
        );
        $st->execute([$userId]);
        $rows = $st->fetchAll();
    }
}
?>
                <div class="page-head">
                    <div>
                        <span class="eyebrow">GESTION</span>
                        <h1>Notifications</h1>
                        <p>Retrouvez les informations importantes liées à votre espace entreprise.</p>
                    </div>
                </div>
                <section class="panel notification-list">
                    <?php if (!$available): ?>
                        <div class="empty">La table des notifications n’est pas encore configurée.</div>
                    <?php elseif (!$rows): ?>
                        <div class="empty">Aucune notification.</div>
                    <?php else:foreach($rows as $r): ?>
                        <article class="notification">
                            <div class="notif-icon"><i class="fa-solid fa-bell"></i></div>
                            <div>
                                <strong><?= e($r['titre_notification'] ?? ($r['titre'] ?? 'Notification')) ?></strong>
                                <p><?= e($r['message_notification'] ?? ($r['message'] ?? '')) ?></p>
                            </div>
                        </article>
                    <?php endforeach;endif; ?>
                </section>

            </div>
        </main>
    </body>
</html>

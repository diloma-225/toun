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

$items = [];

try {
    $rows = $con
        ->query(
            "SELECT prenom_user, nom_user, date_inscrip_user
         FROM t_utilisateurs
         ORDER BY id_user DESC LIMIT 5"
        )
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $items[] = [
            'icon' => 'fa-user-plus',
            'title' => 'Nouvel utilisateur',
            'text' => trim(($row['prenom_user'] ?? '') . ' ' . ($row['nom_user'] ?? '')),
            'date' => $row['date_inscrip_user'] ?? '',
            'link' => 'utilisateur.php'
        ];
    }
} catch (Throwable $e) {
}

try {
    $rows = $con
        ->query(
            "SELECT c.date_obtention, f.titre_form
         FROM t_certificats c
         LEFT JOIN t_inscriptions i ON i.id_inscrip = c.id_inscrip
         LEFT JOIN t_formations f ON f.id_form = i.id_form
         ORDER BY c.id_certificat DESC LIMIT 5"
        )
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $items[] = [
            'icon' => 'fa-award',
            'title' => 'Certificat délivré',
            'text' => $row['titre_form'] ?? 'Certification',
            'date' => $row['date_obtention'] ?? '',
            'link' => 'certificats.php'
        ];
    }
} catch (Throwable $e) {
}

try {
    $rows = $con
        ->query(
            "SELECT montant_total, date_paiement
         FROM t_paiements
         WHERE statut_paiement = 'paye'
         ORDER BY id_paiement DESC LIMIT 5"
        )
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $items[] = [
            'icon' => 'fa-credit-card',
            'title' => 'Nouveau paiement',
            'text' => number_format((float) ($row['montant_total'] ?? 0), 0, ',', ' ') . ' FCFA',
            'date' => $row['date_paiement'] ?? '',
            'link' => 'paiements.php'
        ];
    }
} catch (Throwable $e) {
}

try {
    $rows = $con
        ->query(
            "SELECT nom_message, sujet_message, date_message
         FROM t_messages_contact
         WHERE LOWER(statut_message) = 'nouveau'
         ORDER BY id_message DESC LIMIT 5"
        )
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $items[] = [
            'icon' => 'fa-envelope',
            'title' => 'Nouveau message',
            'text' => trim(($row['nom_message'] ?? '') . ' — ' . ($row['sujet_message'] ?? 'Contact')),
            'date' => $row['date_message'] ?? '',
            'link' => 'messages.php'
        ];
    }
} catch (Throwable $e) {
}

usort($items, function ($a, $b) {
    return strtotime($b['date'] ?: '1970-01-01') <=> strtotime($a['date'] ?: '1970-01-01');
});
?>

<style>
    .notifications-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .notifications-card {
        max-width: 850px;
        margin: 0 auto;
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 17px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(24, 33, 63, 0.07);
    }

    .notifications-head {
        padding: 17px 20px;
        background: linear-gradient(110deg, #18213f, #3f68ae 60%, #20a1b8);
        color: #fff;
    }

    .notifications-head h1 {
        margin: 0;
        font-size: 20px;
    }

    .notifications-head p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .notification-page-item {
        display: flex;
        gap: 11px;
        padding: 13px 16px;
        border-top: 1px solid #ecf1f4;
        text-decoration: none;
    }

    .notification-page-item:hover {
        background: #f4f9fa;
    }

    .notification-page-icon {
        width: 37px;
        height: 37px;
        min-width: 37px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #e6f5f7;
        color: #20a1b8;
    }

    .notification-page-item:nth-child(3n) .notification-page-icon {
        background: #eaf0fa;
        color: #3f68ae;
    }

    .notification-page-item strong {
        display: block;
        color: #18213f;
        font-size: 11px;
    }

    .notification-page-item span {
        display: block;
        margin-top: 3px;
        color: #7b899b;
        font-size: 10.5px;
    }

    .notifications-empty {
        padding: 50px 20px;
        text-align: center;
        color: #8996a5;
        font-size: 11px;
    }

    @media (max-width: 600px) {
        .notifications-page {
            padding: 13px 10px 20px;
        }
    }
</style>

<div class="main">
    <section class="notifications-page">
        <article class="notifications-card">
            <header class="notifications-head">
                <h1>Notifications</h1>
                <p>Les dernières activités utiles à votre administration TOUN.</p>
            </header>

            <?php if (!$items): ?>
                <div class="notifications-empty">
                    Aucune notification pour le moment.
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <a class="notification-page-item" href="<?= e($item['link']) ?>">
                        <div class="notification-page-icon">
                            <i class="fa-solid <?= e($item['icon']) ?>"></i>
                        </div>
                        <div>
                            <strong><?= e($item['title']) ?></strong>
                            <span><?= e($item['text']) ?> · <?= e($item['date']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </article>
    </section>
</div>

<?php
$_SESSION['notifications_seen_at'] = date('Y-m-d H:i:s');
$active_page = 'notifications.php';
include 'menu.php';
?>

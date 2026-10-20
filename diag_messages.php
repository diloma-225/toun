<?php

session_start();
require_once __DIR__ . '/config/connexion.php';

if (($_GET['key'] ?? '') !== 'toun-diag-2026') {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

echo "-- t_messages (20 dernieres lignes) --\n";

$req = $con->query(
    "SELECT m.id_message, m.id_user, u.role_user, m.expediteur,
            LEFT(m.contenu, 25) AS apercu, m.date_message,
            m.lu_apprenant, m.lu_admin
     FROM t_messages m
     LEFT JOIN t_utilisateurs u ON u.id_user = m.id_user
     ORDER BY m.id_message DESC
     LIMIT 20"
);

foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo implode(' | ', [
        'id=' . $row['id_message'],
        'id_user=' . $row['id_user'],
        'role=' . ($row['role_user'] ?? '?'),
        'de=' . $row['expediteur'],
        'apercu=' . $row['apercu'],
        'date=' . $row['date_message'],
        'lu_apprenant=' . $row['lu_apprenant'],
        'lu_admin=' . $row['lu_admin'],
    ]) . "\n";
}

echo "\n-- Session actuelle --\n";
echo 'id_user=' . ($_SESSION['id_user'] ?? 'aucune') . ' role_user=' . ($_SESSION['role_user'] ?? 'aucun') . "\n";

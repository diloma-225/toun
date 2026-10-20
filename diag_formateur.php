<?php
if (($_GET['key'] ?? '') !== 'toun-diag-2026') {
    http_response_code(403);
    exit('Accès refusé.');
}

require __DIR__ . '/config/connexion.php';

header('Content-Type: text/plain; charset=utf-8');

$s = $con->query("
    SELECT f.id_form, f.titre_form, f.id_formateur, fo.id_formateur AS fo_id, fo.id_user,
           u.nom_user, u.prenom_user, u.photo_user
    FROM t_formations f
    LEFT JOIN t_formateurs fo ON fo.id_formateur = f.id_formateur
    LEFT JOIN t_utilisateurs u ON u.id_user = fo.id_user
");

foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo implode(' | ', array_map(fn($v) => $v ?? 'NULL', $r)) . "\n";
}

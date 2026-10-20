<?php
require __DIR__ . '/_bootstrap.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

function liveDureeMinutes(?string $duree): int
{
    $duree = strtolower(trim((string) $duree));

    if ($duree === '') {
        return 60;
    }

    if (preg_match('/(\d+)\s*h\s*(\d+)?/', $duree, $m)) {
        return (int) $m[1] * 60 + (int) ($m[2] ?? 0);
    }

    if (preg_match('/(\d+)/', $duree, $m)) {
        return max(1, (int) $m[1]);
    }

    return 60;
}

$colonnes = apiColonnesTable($con, 't_lives');

if (!in_array('titre', $colonnes, true) || !in_array('date_live', $colonnes, true)) {
    apiReponse(['en_cours' => [], 'a_venir' => [], 'termines' => []]);
}

try {
    $condition = in_array('id_user', $colonnes, true) ? 'WHERE id_user = ? OR id_user IS NULL' : '';
    $s = $con->prepare("SELECT * FROM t_lives $condition ORDER BY date_live ASC");
    $s->execute($condition === '' ? [] : [$idUser]);
    $lignes = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $lignes = [];
}

$maintenant = time();
$enCours = [];
$aVenir = [];
$termines = [];

foreach ($lignes as $ligne) {
    $heure = substr((string) ($ligne['heure_live'] ?? '00:00'), 0, 5);
    $debut = strtotime($ligne['date_live'] . ' ' . $heure);

    if (!$debut) {
        continue;
    }

    $fin = $debut + liveDureeMinutes($ligne['duree'] ?? null) * 60;

    $live = [
        'id_live' => (int) ($ligne['id_live'] ?? 0),
        'titre' => (string) $ligne['titre'],
        'formation' => (string) ($ligne['formation'] ?? ''),
        'formateur' => (string) ($ligne['formateur'] ?? ''),
        'date' => date('Y-m-d', $debut),
        'heure' => $heure,
        'duree' => (string) ($ligne['duree'] ?? ''),
        'lien' => (string) ($ligne['lien_live'] ?? '')
    ];

    if ($maintenant >= $debut && $maintenant <= $fin) {
        $live['statut'] = 'en_cours';
        $enCours[] = $live;
    } elseif ($maintenant < $debut) {
        $live['statut'] = 'a_venir';
        $aVenir[] = $live;
    } else {
        $live['statut'] = 'termine';
        $termines[] = $live;
    }
}

apiReponse([
    'en_cours' => $enCours,
    'a_venir' => $aVenir,
    'termines' => array_reverse($termines)
]);

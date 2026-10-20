<?php
require __DIR__ . '/_bootstrap.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$paiements = [];
$totalDepense = 0;
$nombrePayes = 0;
$nombreEnAttente = 0;

if (apiTableExiste($con, 't_paiements')) {
    $s = $con->prepare("
        SELECT p.ref_paiement, p.numero_recu, p.montant_total, p.mode_paiement,
               p.statut_paiement, p.date_paiement, p.id_form, f.titre_form
        FROM t_paiements p
        LEFT JOIN t_formations f ON f.id_form = p.id_form
        WHERE p.id_user = ?
        ORDER BY p.date_paiement DESC, p.id_paiement DESC
    ");
    $s->execute([$idUser]);

    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $statut = strtolower((string) $ligne['statut_paiement']);
        $montant = (float) $ligne['montant_total'];

        if ($statut === 'paye') {
            $totalDepense += $montant;
            $nombrePayes++;
        } elseif ($statut === 'en_attente') {
            $nombreEnAttente++;
        }

        $paiements[] = [
            'reference' => (string) $ligne['ref_paiement'],
            'numero_recu' => (string) ($ligne['numero_recu'] ?? ''),
            'formation' => (string) ($ligne['titre_form'] ?? 'Formation TOUN'),
            'id_form' => (int) $ligne['id_form'],
            'montant' => $montant,
            'mode' => (string) ($ligne['mode_paiement'] ?? ''),
            'statut' => $statut,
            'date' => $ligne['date_paiement']
        ];
    }
}

apiReponse([
    'paiements' => $paiements,
    'resume' => [
        'total' => count($paiements),
        'total_depense' => $totalDepense,
        'payes' => $nombrePayes,
        'en_attente' => $nombreEnAttente
    ],
    'collaborateur' => apiEstCollaborateur($con, $idUser)
]);

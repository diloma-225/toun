<?php
require __DIR__ . '/_bootstrap.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];

$elements = [];

try {
    if (apiTableExiste($con, 't_inscriptions') && apiTableExiste($con, 't_formations')) {
        $s = $con->prepare("
            SELECT f.titre_form
            FROM t_inscriptions i
            INNER JOIN t_formations f ON f.id_form = i.id_form
            WHERE i.id_user = ?
            ORDER BY i.id_inscrip DESC
            LIMIT 8
        ");
        $s->execute([$idUser]);

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $elements[] = [
                'type' => 'formation',
                'titre' => 'Formation rejointe',
                'texte' => (string) ($ligne['titre_form'] ?? 'Formation TOUN'),
                'date' => null
            ];
        }
    }
} catch (Throwable $e) {
}

try {
    if (apiTableExiste($con, 't_certificats')) {
        $s = $con->prepare("
            SELECT c.date_obtention, f.titre_form
            FROM t_certificats c
            LEFT JOIN t_inscriptions i ON i.id_inscrip = c.id_inscrip
            LEFT JOIN t_formations f ON f.id_form = i.id_form
            WHERE c.id_user = ?
            ORDER BY c.id_certificat DESC
            LIMIT 8
        ");
        $s->execute([$idUser]);

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $elements[] = [
                'type' => 'certificat',
                'titre' => 'Certificat obtenu',
                'texte' => $ligne['titre_form'] ?? 'Certification TOUN',
                'date' => $ligne['date_obtention'] ?? null
            ];
        }
    }
} catch (Throwable $e) {
}

try {
    if (apiTableExiste($con, 't_paiements')) {
        $s = $con->prepare("
            SELECT montant_total, date_paiement
            FROM t_paiements
            WHERE id_user = ? AND LOWER(statut_paiement) = 'paye'
            ORDER BY id_paiement DESC
            LIMIT 8
        ");
        $s->execute([$idUser]);

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $elements[] = [
                'type' => 'paiement',
                'titre' => 'Paiement confirmé',
                'texte' => number_format((float) ($ligne['montant_total'] ?? 0), 0, ',', ' ') . ' FCFA',
                'date' => $ligne['date_paiement'] ?? null
            ];
        }
    }
} catch (Throwable $e) {
}

try {
    if (apiTableExiste($con, 't_messages')) {
        $s = $con->prepare("
            SELECT contenu, date_message
            FROM t_messages
            WHERE id_user = ? AND expediteur = 'admin'
            ORDER BY id_message DESC
            LIMIT 8
        ");
        $s->execute([$idUser]);

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $elements[] = [
                'type' => 'message',
                'titre' => "Message de l'équipe TOUN",
                'texte' => (function_exists('mb_strimwidth') ? mb_strimwidth((string) ($ligne['contenu'] ?? ''), 0, 90, '...') : substr((string) ($ligne['contenu'] ?? ''), 0, 90)),
                'date' => $ligne['date_message'] ?? null
            ];
        }
    }
} catch (Throwable $e) {
}

try {
    if (apiTableExiste($con, 't_rendezvous')) {
        $s = $con->prepare("
            SELECT motif, date_rdv, heure_rdv, statut_rdv, date_creation
            FROM t_rendezvous
            WHERE id_user = ?
            ORDER BY id_rdv DESC
            LIMIT 8
        ");
        $s->execute([$idUser]);
        $titres = [
            'confirme' => 'Rendez-vous confirmé',
            'annule' => 'Rendez-vous annulé',
            'en_attente' => 'Demande de rendez-vous envoyée'
        ];

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $statut = strtolower((string) $ligne['statut_rdv']);
            $elements[] = [
                'type' => 'rendezvous',
                'titre' => $titres[$statut] ?? 'Rendez-vous',
                'texte' => $ligne['motif'] . ' · ' . date('d/m/Y', strtotime($ligne['date_rdv'])) . ' à ' . substr((string) $ligne['heure_rdv'], 0, 5),
                'date' => $ligne['date_creation'] ?? null
            ];
        }
    }
} catch (Throwable $e) {
}

try {
    if (apiTableExiste($con, 't_lives')) {
        $s = $con->prepare("
            SELECT titre, date_live, heure_live, date_creation
            FROM t_lives
            WHERE (id_user = ? OR id_user IS NULL) AND date_live >= CURDATE()
            ORDER BY date_live ASC
            LIMIT 5
        ");
        $s->execute([$idUser]);

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $elements[] = [
                'type' => 'live',
                'titre' => 'Live programmé',
                'texte' => $ligne['titre'] . ' · ' . date('d/m/Y', strtotime($ligne['date_live'])) . ' à ' . substr((string) $ligne['heure_live'], 0, 5),
                'date' => $ligne['date_creation'] ?? null
            ];
        }
    }
} catch (Throwable $e) {
}

usort($elements, function (array $a, array $b): int {
    return strtotime($b['date'] ?? '1970-01-01') <=> strtotime($a['date'] ?? '1970-01-01');
});

apiReponse([
    'notifications' => array_slice($elements, 0, 25)
]);
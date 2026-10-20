<?php
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/ressources.php';

$utilisateur = apiUtilisateurCourant($con);
$idUser = (int) $utilisateur['id_user'];
$ressources = [];

tounPreparerRessources($con);

if (apiTableExiste($con, 't_ressources')) {
    $s = $con->prepare("
        SELECT r.titre_ressource, r.type_ressource, r.fichier_ressource, r.lien_ressource, f.titre_form, m.titre_module, m.ordre_module
        FROM t_ressources r
        INNER JOIN t_formations f ON f.id_form = r.id_form
        INNER JOIN t_inscriptions i ON i.id_form = r.id_form AND i.id_user = ?
        LEFT JOIN t_modules m ON m.id_module = r.id_module
        WHERE i.statut_inscrip IN ('active', 'terminee')
        ORDER BY f.titre_form ASC, m.ordre_module ASC, r.date_ajout DESC
    ");
    $s->execute([$idUser]);

    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $lien = $ligne['lien_ressource'] !== ''
            ? $ligne['lien_ressource']
            : apiUrlMedia($ligne['fichier_ressource'], ['uploads/ressources']);

        if ($lien !== '') {
            $ressources[] = [
                'titre' => $ligne['titre_ressource'],
                'type' => $ligne['type_ressource'],
                'formation' => $ligne['titre_form'],
                'module' => $ligne['titre_module'] ? 'Module ' . (int) $ligne['ordre_module'] . ' : ' . $ligne['titre_module'] : '',
                'lien' => $lien
            ];
        }
    }
}

if (apiTableExiste($con, 't_lecons')) {
    $s = $con->prepare("
        SELECT l.titre_lecon, l.contenu_lecon, f.titre_form, m.titre_module, m.ordre_module
        FROM t_lecons l
        INNER JOIN t_modules m ON m.id_module = l.id_module
        INNER JOIN t_formations f ON f.id_form = m.id_form
        INNER JOIN t_inscriptions i ON i.id_form = f.id_form AND i.id_user = ?
        WHERE l.type_contenu = 'pdf' AND l.contenu_lecon <> '' AND i.statut_inscrip IN ('active', 'terminee')
        ORDER BY f.titre_form ASC, m.ordre_module ASC, l.ordre_lecon ASC
    ");
    $s->execute([$idUser]);

    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $lien = apiUrlMedia($ligne['contenu_lecon'], ['uploads/lecons']);

        if ($lien !== '') {
            $ressources[] = [
                'titre' => $ligne['titre_lecon'],
                'type' => 'pdf',
                'formation' => $ligne['titre_form'],
                'module' => 'Module ' . (int) $ligne['ordre_module'] . ' : ' . $ligne['titre_module'],
                'lien' => $lien
            ];
        }
    }
}

apiReponse(['ressources' => $ressources]);

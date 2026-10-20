<?php

function tounPreparerRessources(PDO $con): void
{
    try {
        $con->exec(
            "CREATE TABLE IF NOT EXISTS t_ressources (
                id_ressource INT UNSIGNED NOT NULL AUTO_INCREMENT,
                id_form INT UNSIGNED NOT NULL,
                id_module INT UNSIGNED NULL,
                titre_ressource VARCHAR(180) NOT NULL,
                type_ressource VARCHAR(20) NOT NULL DEFAULT 'pdf',
                fichier_ressource VARCHAR(255) NOT NULL DEFAULT '',
                lien_ressource VARCHAR(255) NOT NULL DEFAULT '',
                date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id_ressource),
                INDEX idx_ressource_form (id_form)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $colonnes = $con->query('SHOW COLUMNS FROM t_ressources')->fetchAll(PDO::FETCH_COLUMN, 0);

        if (!in_array('id_module', $colonnes, true)) {
            $con->exec('ALTER TABLE t_ressources ADD id_module INT UNSIGNED NULL AFTER id_form');
        }
    } catch (Throwable $e) {
    }
}

function tounRessourcesFormation(PDO $con, int $idForm): array
{
    tounPreparerRessources($con);

    $stmt = $con->prepare('SELECT * FROM t_ressources WHERE id_form = ? ORDER BY date_ajout ASC, id_ressource ASC');
    $stmt->execute([$idForm]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tounLienRessource(array $ressource, string $racine = '../'): string
{
    if ((string) $ressource['lien_ressource'] !== '') {
        return (string) $ressource['lien_ressource'];
    }

    return $racine . 'uploads/ressources/' . rawurlencode((string) $ressource['fichier_ressource']);
}

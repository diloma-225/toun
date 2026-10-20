<?php
return [
    'bd_hote' => getenv('DB_HOST') ?: 'db',
    'bd_nom' => getenv('DB_NAME') ?: 'bd_toun',
    'bd_utilisateur' => getenv('DB_USER') ?: 'toun',
    'bd_mot_de_passe' => getenv('DB_PASSWORD') ?: '',
];

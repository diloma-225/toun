<?php
$serveur = is_file(__DIR__ . '/serveur.php') ? require __DIR__ . '/serveur.php' : [];
$serveur = is_array($serveur) ? $serveur : [];

$hote = (string) ($serveur['bd_hote'] ?? '127.0.0.1');
$base = (string) ($serveur['bd_nom'] ?? 'bd_toun');
$user = (string) ($serveur['bd_utilisateur'] ?? 'root');
$pass = (string) ($serveur['bd_mot_de_passe'] ?? '');

try {
    $con = new PDO('mysql:host=' . $hote . ';dbname=' . $base . ';charset=utf8', $user, $pass);

    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Échec de connexion à la base de données.');
}
?>

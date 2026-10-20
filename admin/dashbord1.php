<?php
session_start();

require_once "../config/connexion.php";

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header("Location: ../connexion.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

$stmt = $pdo->prepare("
    SELECT id_user, nom_user, prenom_user, email_user, photo_user
    FROM t_utilisateurs
    WHERE id_user = ?
    LIMIT 1
");
$stmt->execute([$id_user]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_destroy();
    header("Location: ../connexion.php");
    exit;
}

$nom = $admin['nom_user'] ?? '';
$prenom = $admin['prenom_user'] ?? '';
$photo = $admin['photo_user'] ?? '';
$photo_url = '';

if (!empty($photo)) {
    $photo = basename($photo);

    $possible = [
        __DIR__ . '/uploads/profils/' . $photo,
        __DIR__ . '/uploads/' . $photo,
        __DIR__ . '/images/profils/' . $photo,
        __DIR__ . '/images/' . $photo
    ];

    $browser_paths = [
        'uploads/profils/' . $photo,
        'uploads/' . $photo,
        'images/profils/' . $photo,
        'images/' . $photo
    ];

    foreach ($possible as $i => $file) {
        if (is_file($file)) {
            $photo_url = $browser_paths[$i];
            break;
        }
    }
}

$active_page = 'dashbord.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard — TOUN Admin</title>

    <link rel="stylesheet" href="includes/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="layout">

    <?php include "includes/sidebar.php"; ?>

    <main class="main">

        <?php include "includes/topbar.php"; ?>

        <section class="content">

            <h1>Dashboard</h1>
            <p>Bienvenue dans votre espace d'administration TOUN.</p>

        </section>

    </main>

</div>

</body>
</html>

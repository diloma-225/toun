<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/connexion.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit();
}

if (!isset($con) || !($con instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Connexion à la base indisponible.']);
    exit();
}

try {
    $id = (int) $_SESSION['id_user'];
    $nom = trim((string) ($_POST['nom_user'] ?? ''));
    $prenom = trim((string) ($_POST['prenom_user'] ?? ''));
    $email = trim((string) ($_POST['email_user'] ?? ''));
    $tel = trim((string) ($_POST['tel_user'] ?? ''));
    $password = (string) ($_POST['motdepasse_user'] ?? '');

    if ($nom === '' || $prenom === '' || $email === '') {
        throw new RuntimeException('Le nom, le prénom et l’e-mail sont obligatoires.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('L’adresse e-mail est invalide.');
    }

    $check = $con->prepare(
        'SELECT id_user FROM t_utilisateurs
         WHERE email_user = ? AND id_user <> ?
         LIMIT 1'
    );
    $check->execute([$email, $id]);

    if ($check->fetch()) {
        throw new RuntimeException('Cette adresse e-mail est déjà utilisée.');
    }

    $sets = ['nom_user = ?', 'prenom_user = ?', 'email_user = ?', 'tel_user = ?'];
    $params = [$nom, $prenom, $email, $tel];
    $photoUrl = null;

    if (
        !empty($_FILES['photo_user']['name']) &&
        ($_FILES['photo_user']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
    ) {
        if (($_FILES['photo_user']['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('La photo dépasse 5 Mo.');
        }

        $extension = strtolower(pathinfo($_FILES['photo_user']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException('Format de photo non autorisé.');
        }

        $directory = __DIR__ . '/../uploads/profils';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = 'profil_' . $id . '_' . time() . '.' . $extension;
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($_FILES['photo_user']['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d’enregistrer la photo.');
        }

        $sets[] = 'photo_user = ?';
        $params[] = $filename;
        $photoUrl = '../uploads/profils/' . rawurlencode($filename);
        $_SESSION['photo_user'] = $filename;
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            throw new RuntimeException('Le nouveau mot de passe doit contenir au moins 6 caractères.');
        }

        $sets[] = 'motdepasse_user = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $params[] = $id;
    $update = $con->prepare('UPDATE t_utilisateurs SET ' . implode(', ', $sets) . ' WHERE id_user = ? LIMIT 1');
    $update->execute($params);

    $_SESSION['nom_user'] = $nom;
    $_SESSION['prenom_user'] = $prenom;
    $_SESSION['email_user'] = $email;
    $_SESSION['tel_user'] = $tel;

    echo json_encode([
        'success' => true,
        'message' => 'Vos informations ont bien été enregistrées.',
        'nom_complet' => $prenom . ' ' . $nom,
        'email' => $email,
        'photo_url' => $photoUrl
    ]);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
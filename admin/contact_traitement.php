<?php
require_once __DIR__ . '/../config/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contact.php');
    exit();
}

try {
    $con->exec(
        "CREATE TABLE IF NOT EXISTS t_messages_contact (
            id_message INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nom_message VARCHAR(100) NOT NULL,
            email_message VARCHAR(150) NOT NULL,
            sujet_message VARCHAR(180) NOT NULL,
            message_message TEXT NOT NULL,
            date_message DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            statut_message VARCHAR(20) NOT NULL DEFAULT 'nouveau',
            PRIMARY KEY (id_message),
            INDEX idx_message_statut (statut_message),
            INDEX idx_message_date (date_message)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $nom = trim((string) ($_POST['nom'] ?? ($_POST['nom_complet'] ?? '')));
    $email = trim((string) ($_POST['email'] ?? ''));
    $sujet = trim((string) ($_POST['sujet'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ($_POST['votre_message'] ?? '')));

    if ($nom === '' || $email === '' || $sujet === '' || $message === '') {
        throw new RuntimeException('Veuillez remplir tous les champs du formulaire.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Adresse e-mail invalide.');
    }

    $stmt = $con->prepare(
        'INSERT INTO t_messages_contact
         (nom_message, email_message, sujet_message, message_message)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$nom, $email, $sujet, $message]);

    header('Location: ../contact.php?message=success');
    exit();
} catch (Throwable $e) {
    header('Location: ../contact.php?message=error');
    exit();
}
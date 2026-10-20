<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function messageContactColonneExiste(PDO $con, string $colonne): bool
{
    try {
        $stmt = $con->prepare('SHOW COLUMNS FROM t_messages_contact LIKE ?');
        $stmt->execute([$colonne]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

try {
    if (!messageContactColonneExiste($con, 'reponse_message')) {
        $con->exec('ALTER TABLE t_messages_contact ADD COLUMN reponse_message TEXT NULL AFTER message_message');
    }
    if (!messageContactColonneExiste($con, 'date_reponse_message')) {
        $con->exec(
            'ALTER TABLE t_messages_contact ADD COLUMN date_reponse_message DATETIME NULL AFTER reponse_message'
        );
    }
} catch (Throwable $e) {
}

$id = (int) ($_GET['id'] ?? 0);
$row = null;
$envoiStatut = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reponse'])) {
    $reponse = trim((string) $_POST['reponse']);
    if ($id > 0 && $reponse !== '') {
        try {
            $stmt = $con->prepare('SELECT * FROM t_messages_contact WHERE id_message = ? LIMIT 1');
            $stmt->execute([$id]);
            $destinataire = $stmt->fetch(PDO::FETCH_ASSOC);

            $update = $con->prepare(
                "UPDATE t_messages_contact
                 SET reponse_message = ?, date_reponse_message = NOW(), statut_message = 'repondu'
                 WHERE id_message = ?
                 LIMIT 1"
            );
            $update->execute([$reponse, $id]);

            $envoiStatut = 'enregistre';

            if ($destinataire) {
                $sujetMail = 'Re : ' . ($destinataire['sujet_message'] ?? 'Votre message');
                $enTetes = "From: TOUN <no-reply@toun.local>\r\nContent-Type: text/plain; charset=UTF-8";
                if (@mail($destinataire['email_message'], $sujetMail, $reponse, $enTetes)) {
                    $envoiStatut = 'enregistre_envoye';
                }
            }
        } catch (Throwable $e) {
            $envoiStatut = 'erreur';
        }
    }
}

try {
    $stmt = $con->prepare('SELECT * FROM t_messages_contact WHERE id_message = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && strtolower((string) $row['statut_message']) === 'nouveau') {
        $update = $con->prepare("UPDATE t_messages_contact SET statut_message = 'lu' WHERE id_message = ? LIMIT 1");
        $update->execute([$id]);
        $row['statut_message'] = 'lu';
    }
} catch (Throwable $e) {
}

if (!$row) {
    header('Location: messages.php');
    exit();
}
?>

<style>
    .message-view-page {
        min-height: calc(100vh - 68px);
        padding: 18px 24px 28px;
        background: #eef5f8;
        font-family: "Poppins", Arial, sans-serif;
    }

    .message-view-card {
        max-width: 820px;
        margin: 0 auto;
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 17px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(24, 33, 63, 0.07);
    }

    .message-view-head {
        padding: 18px 20px;
        background: linear-gradient(110deg, #18213f, #3f68ae 60%, #20a1b8);
        color: #fff;
    }

    .message-view-head h1 {
        margin: 0;
        font-size: 19px;
    }

    .message-view-head p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.68);
        font-size: 11px;
    }

    .message-view-body {
        padding: 18px 20px;
    }

    .message-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
    }

    .message-meta div {
        padding: 10px;
        border-radius: 10px;
        background: #f0f7f9;
        border: 1px solid #dde9ee;
    }

    .message-meta small {
        display: block;
        color: #8390a0;
        font-size: 10px;
        text-transform: uppercase;
    }

    .message-meta strong {
        display: block;
        margin-top: 3px;
        color: #18213f;
        font-size: 11px;
    }

    .message-content {
        padding: 13px;
        border: 1px solid #dce7ed;
        border-radius: 11px;
        background: #fbfdfe;
        color: #45566a;
        font-size: 11px;
        line-height: 1.7;
    }

    .message-view-actions {
        display: flex;
        justify-content: flex-end;
        gap: 7px;
        margin-top: 14px;
    }

    .message-view-actions a {
        height: 35px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 12px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
    }

    .message-back {
        border: 1px solid #d3e0e6;
        background: #fff;
        color: #56667a;
    }

    .message-delete {
        background: #fdebec;
        color: #b53f3f;
    }

    .message-reponse-bloc {
        margin-top: 16px;
    }

    .message-reponse-titre {
        display: block;
        margin-bottom: 7px;
        color: #536a7f;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .message-reponse-contenu {
        padding: 13px;
        border: 1px solid #dcefe9;
        border-radius: 11px;
        background: #f3fbf8;
        color: #1f6a4f;
        font-size: 11px;
        line-height: 1.7;
    }

    .message-reponse-date {
        display: block;
        margin-top: 8px;
        color: #7c8c9e;
        font-size: 10.5px;
    }

    .message-alerte {
        padding: 10px 13px;
        margin-bottom: 14px;
        border-radius: 10px;
        font-size: 11px;
    }

    .message-alerte.succes {
        background: #e5f6f7;
        color: #158090;
    }

    .message-alerte.avertissement {
        background: #eaf3f9;
        color: #0b5a91;
    }

    .message-reponse-form {
        margin-top: 16px;
    }

    .message-reponse-form textarea {
        width: 100%;
        height: 100px;
        resize: vertical;
        border: 1px solid #dfe7ee;
        border-radius: 11px;
        padding: 11px 13px;
        font:
            11px "Poppins",
            sans-serif;
        outline: 0;
    }

    .message-reponse-form button {
        height: 37px;
        margin-top: 9px;
        padding: 0 16px;
        border: 0;
        border-radius: 9px;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }

    @media (max-width: 600px) {
        .message-view-page {
            padding: 13px 10px 20px;
        }

        .message-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main">
    <section class="message-view-page">
        <article class="message-view-card">
            <header class="message-view-head">
                <h1><?= e($row['sujet_message'] ?? 'Message') ?></h1>
                <p>Message reçu depuis le formulaire de contact TOUN.</p>
            </header>

            <div class="message-view-body">
                <div class="message-meta">
                    <div>
                        <small>Expéditeur</small>
                        <strong><?= e($row['nom_message'] ?? '—') ?></strong>
                    </div>
                    <div>
                        <small>E-mail</small>
                        <strong><?= e($row['email_message'] ?? '—') ?></strong>
                    </div>
                    <div>
                        <small>Date</small>
                        <strong><?= e($row['date_message'] ?? '—') ?></strong>
                    </div>
                    <div>
                        <small>Statut</small>
                        <strong><?= e($row['statut_message'] ?? 'lu') ?></strong>
                    </div>
                </div>

                <div class="message-content">
                    <?= nl2br(e($row['message_message'] ?? '')) ?>
                </div>

                <?php if ($envoiStatut === 'enregistre_envoye'): ?>
                    <div class="message-alerte succes">
                        <i class="fa-solid fa-circle-check"></i>
                        Réponse enregistrée et e-mail envoyé à <?= e($row['email_message'] ?? '') ?>.
                    </div>
                <?php elseif ($envoiStatut === 'enregistre'): ?>
                    <div class="message-alerte avertissement">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Réponse enregistrée, mais l'e-mail n'a pas pu être envoyé (vérifiez la configuration mail du serveur).
                    </div>
                <?php elseif ($envoiStatut === 'erreur'): ?>
                    <div class="message-alerte avertissement">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Une erreur est survenue, la réponse n'a pas pu être enregistrée.
                    </div>
                <?php endif; ?>

                <?php if (!empty($row['reponse_message'])): ?>
                    <div class="message-reponse-bloc">
                        <span class="message-reponse-titre">Votre réponse</span>
                        <div class="message-reponse-contenu">
                            <?= nl2br(e($row['reponse_message'])) ?>
                            <?php if (!empty($row['date_reponse_message'])): ?>
                                <span class="message-reponse-date">Envoyée le <?= e(date('d/m/Y H:i', strtotime($row['date_reponse_message']))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="message-reponse-form" method="post" action="voir_message.php?id=<?= (int) $row['id_message'] ?>">
                    <span class="message-reponse-titre"><?= !empty($row['reponse_message']) ? 'Modifier votre réponse' : 'Répondre à ce message' ?></span>
                    <textarea name="reponse" placeholder="Écrivez votre réponse..." required><?= e($row['reponse_message'] ?? '') ?></textarea>
                    <button type="submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        Envoyer la réponse
                    </button>
                </form>

                <div class="message-view-actions">
                    <a class="message-back" href="messages.php">
                        <i class="fa-solid fa-arrow-left"></i>
                        Retour
                    </a>
                    <a class="message-delete" href="supprimer_message.php?id=<?= (int) $row['id_message'] ?>" onclick="return confirm('Supprimer ce message ?');">
                        <i class="fa-solid fa-trash"></i>
                        Supprimer
                    </a>
                </div>
            </div>
        </article>
    </section>
</div>

<?php
$active_page = 'messages.php';
include 'menu.php';
?>

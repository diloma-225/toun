<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'parametres.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Paramètres — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .form-panel {
                max-width: 820px;
                margin: 0 auto;
                padding: 18px;
                border-color: #b9dfe5;
            }

            .section-title {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px;
                margin-bottom: 14px;
                border-radius: 9px;
                background: #eef9fa;
                color: #168b9a;
            }

            .section-title i {
                font-size: 12.5px;
            }

            .section-title strong {
                display: block;
                font-size: 10.5px;
                color: #294965;
            }

            .section-title span {
                display: block;
                font-size: 10px;
                color: #8a9ca9;
                margin-top: 2px;
            }

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 11px;
            }

            .field {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .field.full {
                grid-column: 1/-1;
            }

            .field label {
                font-size: 10px;
                font-weight: 700;
                color: #536d83;
            }

            .field input {
                height: 34px;
                border: 1px solid #cfe2e8;
                border-radius: 8px;
                background: #fbfdfe;
                padding: 0 10px;
                outline: 0;
                font: 10.5px Poppins;
                color: #294965;
            }

            .form-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 15px;
            }

            @media (max-width: 650px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }

                .field.full {
                    grid-column: auto;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">

                <?php
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $st = $con->prepare('SELECT motpass_user FROM t_utilisateurs WHERE id_user=?');
    $st->execute([$userId]);
    $hash = $st->fetchColumn();
    if (!$current || !password_verify($current, $hash)) {
        $errors[] = 'Mot de passe actuel incorrect.';
    } elseif (strlen($new) < 6) {
        $errors[] = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
    } elseif ($new !== $confirm) {
        $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
    } else {
        $st = $con->prepare('UPDATE t_utilisateurs SET motpass_user=? WHERE id_user=?');
        $st->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
        flash('ok', 'Mot de passe modifié.');
        header('Location: parametres.php');
        exit();
    }
}
?>
                <div class="page-head">
                    <div>
                        <span class="eyebrow">COMPTE</span>
                        <h1>Paramètres</h1>
                        <p>Gérez la sécurité de votre compte entreprise.</p>
                    </div>
                </div>
                <?php foreach ($errors as $error): ?>
                    <div class="alert err"><?= e($error) ?></div>
                <?php endforeach; ?>
                <?php if ($flash): ?>
                    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>
                <section class="panel form-panel">
                    <form method="post">
                        <div class="section-title">
                            <i class="fa-solid fa-lock"></i>
                            <div><strong>Sécurité</strong><span>Modifier votre mot de passe</span></div>
                        </div>
                        <div class="form-grid">
                            <div class="field full"><label>Mot de passe actuel</label><input type="password" name="current_password" required></div>
                            <div class="field"><label>Nouveau mot de passe</label><input type="password" name="new_password" minlength="6" required></div>
                            <div class="field"><label>Confirmation</label><input type="password" name="confirm_password" minlength="6" required></div>
                        </div>
                        <div class="form-actions"><button class="btn">Modifier le mot de passe</button></div>
                    </form>
                </section>

            </div>
        </main>
    </body>
</html>

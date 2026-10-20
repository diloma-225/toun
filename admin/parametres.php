<?php
session_start();
require_once '../config/connexion.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
function q(PDO $con, $sql, $params = [])
{
    try {
        $s = $con->prepare($sql);
        $s->execute($params);
        return $s;
    } catch (Throwable $ex) {
        return false;
    }
}
function countRows(PDO $con, $sql, $params = [])
{
    $s = q($con, $sql, $params);
    return $s ? (int) $s->fetchColumn() : 0;
}

$id = (int) $_SESSION['id_user'];
$message = '';
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_user'] ?? '');
    $prenom = trim($_POST['prenom_user'] ?? '');
    $email = trim($_POST['email_user'] ?? '');
    $tel = trim($_POST['tel_user'] ?? '');
    if ($nom === '' || $prenom === '' || $email === '') {
        $erreur = 'Veuillez renseigner les champs obligatoires.';
    } else {
        $s = q($con, 'UPDATE t_utilisateurs SET nom_user=?,prenom_user=?,email_user=?,tel_user=? WHERE id_user=?', [
            $nom,
            $prenom,
            $email,
            $tel,
            $id
        ]);
        if ($s) {
            $_SESSION['nom_user'] = $nom;
            $_SESSION['prenom_user'] = $prenom;
            $message = 'Paramètres enregistrés avec succès.';
        } else {
            $erreur = 'Impossible d’enregistrer les modifications.';
        }
    }
}
$s = q(
    $con,
    'SELECT nom_user,prenom_user,email_user,tel_user,role_user,statut_user FROM t_utilisateurs WHERE id_user=? LIMIT 1',
    [$id]
);
$admin = $s ? $s->fetch(PDO::FETCH_ASSOC) : [];
?>

<style>
    .admin-page {
        padding: 22px 26px 30px;
        background: #e8f1f6;
        min-height: calc(100vh - 68px);
        font-family: Poppins, Arial, sans-serif;
    }

    .admin-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .admin-head h1 {
        margin: 0;
        color: #18213f;
        font-size: 21px;
    }

    .admin-head p {
        margin: 5px 0 0;
        color: #7b899b;
        font-size: 12px;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 15px;
    }

    .stat-card,
    .panel {
        background: #fff;
        border: 1px solid #dfe8ef;
        border-radius: 13px;
        box-shadow: 0 5px 16px rgba(24, 33, 63, 0.045);
    }

    .stat-card {
        padding: 14px;
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #e7f5f7;
        color: #20a1b8;
        display: grid;
        place-items: center;
    }

    .stat-card small {
        display: block;
        color: #7c8999;
        font-size: 11px;
    }

    .stat-card strong {
        display: block;
        color: #18213f;
        font-size: 18px;
        margin-top: 2px;
    }

    .panel {
        overflow: hidden;
    }

    .panel-head {
        padding: 13px 15px;
        border-bottom: 1px solid #eaf0f4;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .panel-head strong {
        font-size: 12.5px;
        color: #18213f;
    }

    .panel-head span {
        font-size: 11px;
        color: #8491a2;
    }

    .filters {
        padding: 11px 13px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .filters input,
    .filters select {
        height: 36px;
        border: 1px solid #dce6ed;
        border-radius: 8px;
        padding: 0 10px;
        outline: 0;
        font-size: 11.5px;
        color: #18213f;
        background: #fff;
    }

    .filters input {
        min-width: 230px;
    }

    .table-wrap {
        overflow: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        padding: 11px 12px;
        background: #f4f8fa;
        color: #69788a;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.7px;
    }

    td {
        padding: 11px 12px;
        border-top: 1px solid #edf2f5;
        color: #344257;
        font-size: 12px;
    }

    td strong {
        color: #18213f;
    }

    .badge {
        display: inline-flex;
        padding: 5px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }

    .success {
        background: #e7f7f1;
        color: #15805f;
    }

    .warning {
        background: #eaf3f9;
        color: #0b5a91;
    }

    .danger {
        background: #fdeaea;
        color: #b53f3f;
    }

    .info {
        background: #e7f5f7;
        color: #147e91;
    }

    .empty {
        padding: 40px 20px;
        text-align: center;
        color: #8793a3;
        font-size: 11.5px;
    }

    .empty i {
        display: block;
        font-size: 28px;
        color: #20a1b8;
        margin-bottom: 9px;
    }

    .btn {
        height: 36px;
        padding: 0 13px;
        border-radius: 8px;
        border: 0;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-primary {
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        color: #fff;
    }

    .btn-light {
        background: #fff;
        border: 1px solid #dce6ed;
        color: #3f68ae;
    }

    .actions {
        display: flex;
        gap: 5px;
    }

    .action {
        width: 29px;
        height: 29px;
        border-radius: 7px;
        display: grid;
        place-items: center;
        text-decoration: none;
    }

    .action.view {
        background: #e7f7f8;
        color: #20a1b8;
    }

    .action.edit {
        background: #eaf0fb;
        color: #3f68ae;
    }

    .action.delete {
        background: #fdeaea;
        color: #c74747;
    }

    .progress {
        width: 150px;
        height: 7px;
        background: #e8eef3;
        border-radius: 20px;
        overflow: hidden;
    }

    .progress i {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #20a1b8, #3f68ae);
        border-radius: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        padding: 16px;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .field.full {
        grid-column: 1/-1;
    }

    .field label {
        font-size: 11px;
        font-weight: 700;
        color: #59677a;
    }

    .field input,
    .field select,
    .field textarea {
        border: 1px solid #dce6ed;
        border-radius: 8px;
        height: 37px;
        padding: 0 10px;
        outline: 0;
        font-size: 11.5px;
    }

    .field textarea {
        height: 90px;
        padding-top: 9px;
        resize: vertical;
    }

    .form-actions {
        padding: 0 16px 16px;
        display: flex;
        justify-content: flex-end;
        gap: 7px;
    }

    .alert {
        padding: 10px 12px;
        border-radius: 8px;
        background: #e7f5f7;
        color: #147e91;
        font-size: 11.5px;
        margin-bottom: 12px;
    }

    @media (max-width: 900px) {
        .stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 650px) {
        .admin-page {
            padding: 15px 10px;
        }

        .stats {
            grid-template-columns: 1fr;
        }

        .admin-head {
            align-items: flex-start;
            gap: 10px;
            flex-direction: column;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .field.full {
            grid-column: auto;
        }

        .filters input {
            min-width: 100%;
        }
    }
</style>

<div class="main">
    <section class="admin-page">

        <div class="admin-head">
            <div>
                <h1>Paramètres</h1>
                <p>Gérez les informations de votre compte administrateur.</p>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="alert"><i class="fa-solid fa-circle-check"></i> <?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alert" style="background:#FDEAEA;color:#B53F3F"><i class="fa-solid fa-circle-exclamation"></i> <?= e($erreur) ?></div>
        <?php endif; ?>
        <div class="panel">
            <div class="panel-head"><strong>Informations du compte</strong><span><?= e($admin['role_user'] ?? 'Administrateur') ?></span></div>
            <form method="post">
                <div class="form-grid">
                    <div class="field"><label>Nom *</label><input name="nom_user" required value="<?= e($admin['nom_user'] ?? '') ?>"></div>
                    <div class="field"><label>Prénom *</label><input name="prenom_user" required value="<?= e($admin['prenom_user'] ?? '') ?>"></div>
                    <div class="field"><label>Email *</label><input type="email" name="email_user" required value="<?= e($admin['email_user'] ?? '') ?>"></div>
                    <div class="field"><label>Téléphone</label><input name="tel_user" value="<?= e($admin['tel_user'] ?? '') ?>"></div>
                    <div class="field"><label>Rôle</label><input readonly value="<?= e($admin['role_user'] ?? 'Administrateur') ?>"></div>
                    <div class="field"><label>Statut</label><input readonly value="<?= e($admin['statut_user'] ?? '') ?>"></div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> Enregistrer</button></div>
            </form>
        </div>

    </section>
</div>
<?php
$active_page = 'parametres.php';
include 'menu.php';
?>

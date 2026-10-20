<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: entreprises.php');
    exit();
}

$stmt = $con->prepare("SELECT e.*, u.nom_user, u.prenom_user, u.email_user
                       FROM t_entreprises e
                       LEFT JOIN t_utilisateurs u ON e.id_user = u.id_user
                       WHERE e.id_entreprise = ? LIMIT 1");
$stmt->execute([$id]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    header('Location: entreprises.php');
    exit();
}

$logo_url = '';
if (!empty($entreprise['logo_entreprise'])) {
    $logo = basename(str_replace('\\', '/', $entreprise['logo_entreprise']));
    $candidates = [
        [__DIR__ . '/../uploads/entreprises/' . $logo, '../uploads/entreprises/' . rawurlencode($logo)],
        [__DIR__ . '/../uploads/' . $logo, '../uploads/' . rawurlencode($logo)],
        [__DIR__ . '/../images/entreprises/' . $logo, '../images/entreprises/' . rawurlencode($logo)]
    ];
    foreach ($candidates as $c) {
        if (is_file($c[0])) {
            $logo_url = $c[1];
            break;
        }
    }
}
$responsable = trim(($entreprise['prenom_user'] ?? '') . ' ' . ($entreprise['nom_user'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Voir l'entreprise - TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            .page {
                margin-left: 260px;
                padding: 92px 25px 30px;
                background: #f7f9fc;
                min-height: 100vh;
                font-family: Poppins, Arial, sans-serif;
            }

            .box {
                max-width: 1050px;
                margin: auto;
                background: #fff;
                border: 1px solid #edf0f5;
                border-radius: 18px;
                padding: 24px;
                box-shadow: 0 8px 25px rgba(31, 56, 88, 0.05);
            }

            .head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 22px;
            }

            .head h1 {
                font-size: 21px;
                color: #17243b;
                margin: 0;
                font-weight: 700;
            }

            .head p {
                margin: 5px 0 0;
                color: #8d97a8;
                font-size: 12px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 0;
                border-radius: 9px;
                padding: 10px 15px;
                font: 600 12px Poppins;
                cursor: pointer;
                text-decoration: none;
            }

            .btn-back {
                background: #eef3f8;
                color: #3f68ae;
            }

            .btn-save {
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
            }

            .grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
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
                font-size: 11.5px;
                font-weight: 600;
                color: #4e596d;
            }

            .field input,
            .field select,
            .field textarea {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #e3e8ef;
                border-radius: 9px;
                padding: 11px 12px;
                font: 500 12px Poppins;
                color: #263247;
                outline: none;
                background: #fff;
            }

            .field textarea {
                min-height: 110px;
                resize: vertical;
            }

            .field input:focus,
            .field select:focus,
            .field textarea:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .alert {
                padding: 11px 13px;
                border-radius: 9px;
                background: #fff1f1;
                color: #c33b3b;
                font-size: 12px;
                margin-bottom: 16px;
            }

            .info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }

            .info {
                background: #f8fafc;
                border: 1px solid #edf0f5;
                border-radius: 12px;
                padding: 14px;
            }

            .info span {
                display: block;
                color: #8e98a9;
                font-size: 11px;
                margin-bottom: 4px;
            }

            .info strong {
                display: block;
                color: #27344a;
                font-size: 12px;
            }

            .logo {
                width: 80px;
                height: 80px;
                border-radius: 14px;
                object-fit: cover;
                border: 1px solid #e8edf3;
                background: #f4f7fa;
            }

            .footer-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 22px;
            }

            @media (max-width: 900px) {
                .page {
                    margin-left: 0;
                }

                .grid,
                .info-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

    </head>
    <body>
        <?php
$active_page = 'entreprises.php';
include 'menu.php';
?>
        <main class="page">
            <div class="box">
                <div class="head">
                    <div>
                        <h1><?= e($entreprise['nom_entreprise']) ?></h1>
                        <p>Informations détaillées de l'entreprise.</p>
                    </div>
                    <a href="entreprises.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Retour</a>
                </div>
                <div style="display:flex;gap:18px;align-items:center;margin-bottom:22px">
                    <?php if ($logo_url): ?>
                        <img class="logo" src="<?= e($logo_url) ?>" alt="Logo">
                    <?php else: ?>
                        <div class="logo" style="display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#3f68ae"><?= e(mb_strtoupper(mb_substr($entreprise['nom_entreprise'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                    <?php endif; ?>
                    <div>
                        <strong style="font-size:15px;color:#263247"><?= e($entreprise['nom_entreprise']) ?></strong>
                        <div style="font-size:11.5px;color:#8e98a9;margin-top:4px"><?= e($entreprise['sect_activite'] ?: 'Secteur non renseigné') ?></div>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info"><span>Secteur d'activité</span><strong><?= e($entreprise['sect_activite'] ?: '—') ?></strong></div>
                    <div class="info"><span>Statut</span><strong><?= e($entreprise['statut_entreprise'] ?: '—') ?></strong></div>
                    <div class="info"><span>Email</span><strong><?= e($entreprise['email_entreprise'] ?: '—') ?></strong></div>
                    <div class="info"><span>Téléphone</span><strong><?= e($entreprise['tel_entreprise'] ?: '—') ?></strong></div>
                    <div class="info"><span>Responsable</span><strong><?= e($responsable ?: '—') ?></strong></div>
                    <div class="info"><span>Date de création</span><strong><?= !empty($entreprise['date_creation_entreprise']) ? e(date('d/m/Y', strtotime($entreprise['date_creation_entreprise']))) : '—' ?></strong></div>
                    <div class="info" style="grid-column:1/-1"><span>Adresse</span><strong><?= e($entreprise['ad_entreprise'] ?: '—') ?></strong></div>
                </div>
                <div class="footer-actions"><a href="modifier_entreprise.php?id=<?= $id ?>" class="btn btn-save"><i class="fa-solid fa-pen"></i> Modifier</a></div>
            </div>
        </main>
    </body>
</html>

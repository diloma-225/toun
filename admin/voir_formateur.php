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

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: formateurs.php');
    exit();
}

$stmt = $con->prepare("
    SELECT
        f.*,
        u.nom_user,
        u.prenom_user,
        u.email_user,
        u.tel_user,
        u.photo_user
    FROM t_formateurs f
    INNER JOIN t_utilisateurs u ON u.id_user = f.id_user
    WHERE f.id_formateur = ?
");

$stmt->execute([$id]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$f) {
    header('Location: formateurs.php');
    exit();
}

$photoPath = '';

if (!empty($f['photo_user'])) {
    $candidates = [
        '../uploads/photos/' . $f['photo_user'],
        '../uploads/' . $f['photo_user'],
        '../assets/uploads/' . $f['photo_user'],
        '../assets/images/' . $f['photo_user']
    ];

    foreach ($candidates as $path) {
        if (is_file(__DIR__ . '/' . $path)) {
            $photoPath = $path;
            break;
        }
    }
}

$initiales = strtoupper(substr($f['prenom_user'] ?? '', 0, 1)) . strtoupper(substr($f['nom_user'] ?? '', 0, 1));

$stmt = $con->prepare("
    SELECT
        id_form,
        titre_form,
        niveau_form,
        prix_form,
        statut_form
    FROM t_formations
    WHERE id_formateur = ?
    ORDER BY date_creation_form DESC
");

$stmt->execute([$id]);
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Profil formateur - TOUN</title>

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        >

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: #eef4f8;
                font-family: "Poppins", sans-serif;
                color: #26384c;
            }

            .main {
                margin-left: 260px;
                padding: 88px 25px 30px;
                min-height: 100vh;
            }

            .back {
                width: 680px;
                max-width: 100%;
                margin: 0 auto 13px;
                display: flex;
                align-items: center;
                gap: 7px;
                color: #3f68ae;
                text-decoration: none;
                font-size: 11.5px;
                font-weight: 600;
            }

            .back:hover {
                color: #20a1b8;
            }

            .profile-card {
                width: 680px;
                max-width: 100%;
                margin: 0 auto;
                background: #fff;
                border: 1px solid #d7e6ed;
                border-radius: 14px;
                box-shadow: 0 5px 20px rgba(63, 104, 174, 0.07);
                overflow: hidden;
            }

            .profile-header {
                padding: 20px 22px;
                background: linear-gradient(135deg, #eaf8f9, #edf2fa);
                border-bottom: 1px solid #dce9ee;
            }

            .profile-header-inner {
                display: flex;
                align-items: center;
                gap: 17px;
            }

            .photo {
                width: 86px;
                height: 86px;
                min-width: 86px;
                border-radius: 50%;
                object-fit: cover;
                border: 4px solid #fff;
                box-shadow: 0 4px 13px rgba(30, 70, 100, 0.17);
                background: #e7f7f8;
            }

            .photo-placeholder {
                width: 86px;
                height: 86px;
                min-width: 86px;
                border-radius: 50%;
                border: 4px solid #fff;
                background: linear-gradient(135deg, #dff5f7, #e7edf9);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #3f68ae;
                font-size: 25px;
                font-weight: 600;
                box-shadow: 0 4px 13px rgba(30, 70, 100, 0.13);
            }

            .profile-name {
                min-width: 0;
            }

            .profile-name h1 {
                margin: 0;
                color: #183b66;
                font-size: 20px;
                font-weight: 700;
                line-height: 1.25;
            }

            .profile-name p {
                margin: 5px 0 9px;
                color: #61768a;
                font-size: 11.5px;
            }

            .specialite {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 9px;
                border-radius: 6px;
                background: #dff5f7;
                color: #168397;
                font-size: 11px;
                font-weight: 600;
            }

            .specialite i {
                font-size: 10.5px;
            }

            .status {
                margin-left: 7px;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 5px 8px;
                border-radius: 20px;
                font-size: 10.5px;
                font-weight: 600;
            }

            .status::before {
                content: "";
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
            }

            .status.actif {
                color: #16805b;
                background: #e4f7ef;
            }

            .status.inactif {
                color: #7b8794;
                background: #edf0f3;
            }

            .profile-content {
                padding: 18px 22px 21px;
            }

            .block-title {
                display: flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 9px;
                color: #183b66;
                font-size: 12px;
                font-weight: 700;
            }

            .block-title i {
                color: #20a1b8;
                font-size: 11.5px;
            }

            .contact-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 9px;
                margin-bottom: 17px;
            }

            .contact-item {
                min-height: 52px;
                padding: 9px 11px;
                border-radius: 8px;
                background: #f7fafc;
                border: 1px solid #e1ebf0;
            }

            .contact-item span {
                display: block;
                margin-bottom: 3px;
                color: #8a98a6;
                font-size: 10.5px;
                text-transform: uppercase;
                font-weight: 600;
            }

            .contact-item strong {
                color: #365773;
                font-size: 11px;
                font-weight: 600;
                word-break: break-word;
            }

            .contact-item i {
                margin-right: 4px;
                color: #20a1b8;
            }

            .details-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 9px;
                margin-bottom: 17px;
            }

            .detail-item {
                padding: 10px 11px;
                border-radius: 8px;
                background: linear-gradient(135deg, #f7fbfc, #f7f9fd);
                border: 1px solid #e0e9ef;
            }

            .detail-item span {
                display: block;
                margin-bottom: 4px;
                color: #8a98a6;
                font-size: 10.5px;
                text-transform: uppercase;
                font-weight: 600;
            }

            .detail-item strong {
                color: #315575;
                font-size: 11.5px;
                font-weight: 600;
            }

            .linkedin {
                color: #3f68ae;
                text-decoration: none;
            }

            .linkedin:hover {
                color: #20a1b8;
            }

            .bio-container {
                margin-top: 2px;
                padding: 12px 13px;
                border-radius: 9px;
                background: #f5fafb;
                border-left: 3px solid #20a1b8;
            }

            .bio-container p {
                margin: 0;
                color: #627286;
                font-size: 11px;
                line-height: 1.7;
            }

            .formations-card {
                width: 680px;
                max-width: 100%;
                margin: 16px auto 0;
                background: #fff;
                border: 1px solid #d7e6ed;
                border-radius: 14px;
                box-shadow: 0 5px 20px rgba(63, 104, 174, 0.06);
                padding: 17px 20px;
            }

            .formations-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 9px;
            }

            .formations-title {
                display: flex;
                align-items: center;
                gap: 7px;
                color: #183b66;
                font-size: 12px;
                font-weight: 700;
            }

            .formations-title i {
                color: #20a1b8;
            }

            .count {
                min-width: 24px;
                height: 22px;
                padding: 0 7px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #e5f6f8;
                color: #168397;
                font-size: 11px;
                font-weight: 700;
            }

            .formation {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
                padding: 10px 3px;
                border-top: 1px solid #edf2f5;
            }

            .formation-name {
                min-width: 0;
            }

            .formation-name strong {
                display: block;
                color: #315575;
                font-size: 11px;
                font-weight: 600;
            }

            .formation-name small {
                display: block;
                margin-top: 3px;
                color: #8795a3;
                font-size: 10.5px;
            }

            .price {
                white-space: nowrap;
                color: #3f68ae;
                font-size: 11px;
                font-weight: 700;
            }

            .empty {
                padding: 13px 3px 3px;
                border-top: 1px solid #edf2f5;
                color: #8a97a5;
                font-size: 11px;
            }

            @media (max-width: 850px) {
                .main {
                    margin-left: 0;
                    padding: 85px 15px 25px;
                }

                .profile-card,
                .formations-card,
                .back {
                    width: 100%;
                }
            }

            @media (max-width: 600px) {
                .profile-header-inner {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .contact-grid,
                .details-grid {
                    grid-template-columns: 1fr;
                }

                .profile-content {
                    padding: 16px;
                }

                .profile-header {
                    padding: 18px 16px;
                }

                .formations-card {
                    padding: 15px;
                }
            }
        </style>

    </head>

    <body>

        <?php include 'menu.php'; ?>

        <main class="main">

            <a href="formateurs.php" class="back">
                <i class="fa-solid fa-arrow-left"></i>
                Retour aux formateurs
            </a>

            <div class="profile-card">

                <div class="profile-header">

                    <div class="profile-header-inner">

                        <?php if ($photoPath): ?>

                            <img
                                src="<?= e($photoPath) ?>"
                                alt="Photo du formateur"
                                class="photo"
                            >

                        <?php else: ?>

                            <div class="photo-placeholder">
                                <?= e($initiales ?: 'F') ?>
                            </div>

                        <?php endif; ?>

                        <div class="profile-name">

                            <h1>
                                <?= e($f['prenom_user'] . ' ' . $f['nom_user']) ?>
                            </h1>

                            <p>
                                Formateur professionnel
                            </p>

                            <span class="specialite">
                                <i class="fa-solid fa-chalkboard-user"></i>
                                <?= e($f['specialite']) ?>
                            </span>

                            <span class="status <?= $f['statut_formateur'] === 'actif' ? 'actif' : 'inactif' ?>">
                                <?= e(ucfirst($f['statut_formateur'])) ?>
                            </span>

                        </div>

                    </div>

                </div>

                <div class="profile-content">

                    <div class="block-title">
                        <i class="fa-solid fa-address-card"></i>
                        Coordonnées
                    </div>

                    <div class="contact-grid">

                        <div class="contact-item">

                            <span>Email</span>

                            <strong>
                                <i class="fa-solid fa-envelope"></i>
                                <?= e($f['email_user']) ?>
                            </strong>

                        </div>

                        <div class="contact-item">

                            <span>Téléphone</span>

                            <strong>
                                <i class="fa-solid fa-phone"></i>
                                <?= e($f['tel_user'] ?: 'Non renseigné') ?>
                            </strong>

                        </div>

                    </div>

                    <div class="block-title">
                        <i class="fa-solid fa-briefcase"></i>
                        Parcours professionnel
                    </div>

                    <div class="details-grid">

                        <div class="detail-item">

                            <span>Expérience</span>

                            <strong>
                                <?= e($f['experience'] ?: 'Non renseignée') ?>
                            </strong>

                        </div>

                        <div class="detail-item">

                            <span>Diplôme / qualification</span>

                            <strong>
                                <?= e($f['diplome'] ?: 'Non renseigné') ?>
                            </strong>

                        </div>

                        <div class="detail-item">

                            <span>LinkedIn</span>

                            <strong>

                                <?php if (!empty($f['linkedin'])): ?>

                                    <a
                                        href="<?= e($f['linkedin']) ?>"
                                        target="_blank"
                                        rel="noopener"
                                        class="linkedin"
                                    >
                                        <i class="fa-brands fa-linkedin"></i>
                                        Voir le profil
                                    </a>

                                <?php else: ?>

                                    Non renseigné

                                <?php endif; ?>

                            </strong>

                        </div>

                    </div>

                    <div class="block-title">
                        <i class="fa-solid fa-user"></i>
                        À propos du formateur
                    </div>

                    <div class="bio-container">

                        <p>
                            <?= nl2br(e($f['biographie'] ?: 'Aucune biographie renseignée.')) ?>
                        </p>

                    </div>

                </div>

            </div>

            <div class="formations-card">

                <div class="formations-header">

                    <div class="formations-title">
                        <i class="fa-solid fa-book-open"></i>
                        Formations associées
                    </div>

                    <div class="count">
                        <?= count($formations) ?>
                    </div>

                </div>

                <?php if (!$formations): ?>

                    <div class="empty">
                        Aucune formation n'est encore associée à ce formateur.
                    </div>

                <?php else: ?>

                    <?php foreach ($formations as $fo): ?>

                        <div class="formation">

                            <div class="formation-name">

                                <strong>
                                    <?= e($fo['titre_form']) ?>
                                </strong>

                                <small>
                                    <?= e($fo['niveau_form'] ?: 'Niveau non renseigné') ?>
                                    ·
                                    <?= e($fo['statut_form']) ?>
                                </small>

                            </div>

                            <div class="price">
                                <?= number_format((float) $fo['prix_form'], 0, ',', ' ') ?>
                                FCFA
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </main>

    </body>

</html>
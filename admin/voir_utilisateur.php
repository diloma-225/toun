<?php
session_start();

require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function roleLabel($role)
{
    $labels = [
        'admin' => 'Administrateur',
        'formateur' => 'Formateur',
        'gest_entreprise' => 'Entreprise',
        'apprenant' => 'Professionnel'
    ];
    return $labels[$role] ?? ucfirst(str_replace('_', ' ', (string) $role));
}

function initiales($prenom, $nom)
{
    $a = mb_substr(trim((string) $prenom), 0, 1, 'UTF-8');
    $b = mb_substr(trim((string) $nom), 0, 1, 'UTF-8');
    return mb_strtoupper($a . $b, 'UTF-8');
}

function photoUtilisateur($photo)
{
    if (empty($photo)) {
        return '';
    }

    $photo = ltrim(str_replace('\\', '/', trim($photo)), '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $nom_photo = basename($photo);

    $candidats = [
        [__DIR__ . '/../uploads/profils/' . $nom_photo, '../uploads/profils/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../uploads/photos/' . $nom_photo, '../uploads/photos/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../uploads/photos/' . $nom_photo, '../uploads/photos/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../uploads/' . $nom_photo, '../uploads/' . rawurlencode($nom_photo)],
        [__DIR__ . '/../images/' . $nom_photo, '../images/' . rawurlencode($nom_photo)]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat[0])) {
            return $candidat[1];
        }
    }

    return '';
}

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));

$stmt = $con->prepare("
    SELECT id_user, nom_user, prenom_user, email_user, tel_user,
           photo_user, role_user, statut_user, date_inscrip_user
    FROM t_utilisateurs
    WHERE id_user = ?
    LIMIT 1
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['user_error'] = 'Utilisateur introuvable.';
    header('Location: utilisateur.php');
    exit();
}

$photo_user = photoUtilisateur($user['photo_user'] ?? '');
$actif = strtolower((string) ($user['statut_user'] ?? '')) === 'actif';
$role = $user['role_user'] ?? '';

$stats = [];
$liste = [];
$liste_titre = '';
$details = [];

try {
    if ($role === 'apprenant') {
        $q = $con->prepare('SELECT COUNT(*) FROM t_inscriptions WHERE id_user = ?');
        $q->execute([$id]);
        $stats[] = [(int) $q->fetchColumn(), 'Formations suivies'];

        $q = $con->prepare(
            "SELECT COALESCE(SUM(montant_total), 0) FROM t_paiements WHERE id_user = ? AND statut_paiement = 'paye'"
        );
        $q->execute([$id]);
        $stats[] = [number_format((float) $q->fetchColumn(), 0, ',', ' ') . ' FCFA', 'Total payé'];

        $q = $con->prepare('SELECT COUNT(*) FROM t_certificats WHERE id_user = ?');
        $q->execute([$id]);
        $stats[] = [(int) $q->fetchColumn(), 'Certificats obtenus'];

        $q = $con->prepare("
            SELECT f.titre_form, i.date_inscrip_form, i.statut_inscrip
            FROM t_inscriptions i
            INNER JOIN t_formations f ON f.id_form = i.id_form
            WHERE i.id_user = ?
            ORDER BY i.date_inscrip_form DESC
        ");
        $q->execute([$id]);
        $liste_titre = 'Formations suivies';
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $liste[] = [
                $row['titre_form'],
                !empty($row['date_inscrip_form'])
                    ? 'Inscrit le ' . date('d/m/Y', strtotime($row['date_inscrip_form']))
                    : '',
                ucfirst((string) ($row['statut_inscrip'] ?: 'active'))
            ];
        }
    }

    if ($role === 'formateur') {
        $q = $con->prepare(
            'SELECT id_formateur, specialite, statut_formateur FROM t_formateurs WHERE id_user = ? LIMIT 1'
        );
        $q->execute([$id]);
        $formateur = $q->fetch(PDO::FETCH_ASSOC);

        if ($formateur) {
            $details[] = ['fa-solid fa-briefcase', 'Spécialité', $formateur['specialite'] ?: '—'];

            $q = $con->prepare(
                'SELECT titre_form, statut_form FROM t_formations WHERE id_formateur = ? ORDER BY titre_form'
            );
            $q->execute([(int) $formateur['id_formateur']]);
            $formations = $q->fetchAll(PDO::FETCH_ASSOC);

            $stats[] = [count($formations), 'Formations rattachées'];
            $liste_titre = 'Formations du formateur';
            foreach ($formations as $row) {
                $liste[] = [$row['titre_form'], '', ucfirst((string) $row['statut_form'])];
            }
        }
    }

    if ($role === 'gest_entreprise') {
        $q = $con->prepare(
            'SELECT id_entreprise, nom_entreprise, sect_activite FROM t_entreprises WHERE id_user = ? LIMIT 1'
        );
        $q->execute([$id]);
        $entreprise = $q->fetch(PDO::FETCH_ASSOC);

        if ($entreprise) {
            $details[] = ['fa-solid fa-building', 'Entreprise', $entreprise['nom_entreprise']];
            $details[] = ['fa-solid fa-industry', "Secteur d'activité", $entreprise['sect_activite'] ?: '—'];

            $q = $con->prepare('SELECT COUNT(*) FROM t_entreprise_collaborateurs WHERE id_entreprise = ?');
            $q->execute([(int) $entreprise['id_entreprise']]);
            $stats[] = [(int) $q->fetchColumn(), 'Collaborateurs'];
        }
    }
} catch (PDOException $e) {
    $erreur = "Certaines informations n'ont pas pu être chargées.";
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Détail utilisateur - TOUN</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html,
            body {
                min-height: 100%;
            }

            body {
                font-family: "Poppins", sans-serif;
                background: #f5f8fc;
                color: #18213f;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            button,
            input,
            select {
                font: inherit;
            }

            .layout {
                min-height: 100vh;
            }

            .main {
                width: calc(100% - 260px);
                min-height: 100vh;
                margin-left: 260px;
                padding-top: 64px;
            }

            .content {
                padding: 16px 20px 22px;
            }

            .page-head {
                min-height: 42px;
                margin-bottom: 11px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
            }

            .page-title h1 {
                margin-bottom: 2px;
                font-size: 21px;
                line-height: 1.2;
            }

            .page-title p {
                color: #8995a8;
                font-size: 11px;
            }

            .btn-primary {
                height: 36px;
                padding: 0 14px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 0;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
                box-shadow: 0 6px 15px rgba(63, 104, 174, 0.16);
                font-size: 11px;
                font-weight: 600;
                transition:
                    transform 0.2s ease,
                    box-shadow 0.2s ease;
            }

            .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 9px 18px rgba(63, 104, 174, 0.22);
            }

            .role,
            .status {
                display: inline-flex;
                align-items: center;
                border-radius: 6px;
                font-size: 10px;
                font-weight: 600;
            }

            .role {
                padding: 4px 7px;
                color: #3f68ae;
                background: #edf3fb;
            }

            .status {
                gap: 4px;
                padding: 4px 7px;
            }

            .status::before {
                content: "";
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
            }

            .status.active {
                color: #168b61;
                background: #eaf8f2;
            }

            .status.inactive {
                color: #d05763;
                background: #fff0f2;
            }

            .success-box {
                margin-bottom: 10px;
                padding: 9px 12px;
                border: 1px solid #c9ecd9;
                border-radius: 8px;
                color: #168b61;
                background: #eaf8f2;
                font-size: 11px;
            }

            .error-box {
                margin-bottom: 10px;
                padding: 9px 12px;
                border: 1px solid #f3d5d9;
                border-radius: 8px;
                color: #c84e5a;
                background: #fff1f3;
                font-size: 11px;
            }

            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 4px;
                color: #8995a8;
                font-size: 11px;
            }

            .back-link:hover {
                color: #3f68ae;
            }

            .head-actions {
                display: flex;
                gap: 8px;
            }

            .btn-danger {
                height: 36px;
                padding: 0 14px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border-radius: 9px;
                color: #d05763;
                background: #fff0f2;
                font-size: 11px;
                font-weight: 600;
                transition: background 0.2s ease;
            }

            .btn-danger:hover {
                background: #ffe2e6;
            }

            .detail-grid {
                display: grid;
                grid-template-columns: 280px minmax(0, 1fr);
                gap: 14px;
                align-items: start;
            }

            .card {
                padding: 18px;
                border: 1px solid #e7edf4;
                border-radius: 14px;
                background: #fff;
                box-shadow: 0 6px 18px rgba(24, 33, 63, 0.04);
            }

            .user-card {
                text-align: center;
            }

            .big-avatar {
                width: 86px;
                height: 86px;
                margin: 0 auto 12px;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 3px solid #e2f3f5;
                border-radius: 50%;
                color: #fff;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                font-size: 24px;
                font-weight: 700;
            }

            .big-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .user-card h2 {
                margin-bottom: 8px;
                font-size: 15px;
            }

            .badges {
                display: flex;
                justify-content: center;
                gap: 6px;
                margin-bottom: 14px;
            }

            .info-list {
                display: grid;
                gap: 9px;
                padding-top: 14px;
                border-top: 1px solid #eef2f7;
                text-align: left;
            }

            .info-item {
                display: flex;
                align-items: center;
                gap: 9px;
                font-size: 11px;
            }

            .info-item i {
                width: 26px;
                height: 26px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                color: #3f68ae;
                background: #edf3fb;
                font-size: 11.5px;
            }

            .info-item span {
                display: block;
                color: #8995a8;
                font-size: 10.5px;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 14px;
            }

            .stat {
                padding: 14px;
                border: 1px solid #e7edf4;
                border-radius: 12px;
                background: #fff;
            }

            .stat strong {
                display: block;
                color: #3f68ae;
                font-size: 18px;
            }

            .stat span {
                color: #8995a8;
                font-size: 11px;
            }

            .card h3 {
                margin-bottom: 12px;
                font-size: 12.5px;
            }

            .list-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 9px 0;
                border-bottom: 1px solid #f0f3f8;
                font-size: 11px;
            }

            .list-row:last-child {
                border-bottom: 0;
            }

            .list-row small {
                color: #8995a8;
                font-size: 10.5px;
            }

            .muted {
                color: #8995a8;
                font-size: 11px;
            }

            @media (max-width: 900px) {
                .detail-grid {
                    grid-template-columns: 1fr;
                }

                .stats {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>

    <body>
        <div class="layout">

            <?php
$active_page = 'utilisateur.php';
include 'menu.php';
?>

            <main class="main">
                <section class="content">

                    <div class="page-head">
                        <div class="page-title">
                            <a href="utilisateur.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Retour aux utilisateurs</a>
                            <h1>Détail de l'utilisateur</h1>
                            <p>Informations du compte et activité sur la plateforme.</p>
                        </div>

                        <div class="head-actions">
                            <a href="modifier_utilisateur.php?id=<?= (int) $user['id_user'] ?>" class="btn-primary">
                                <i class="fa-solid fa-pen"></i>
                                Modifier
                            </a>
                            <a href="supprimer_utilisateur.php?id=<?= (int) $user['id_user'] ?>" class="btn-danger"
                                onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');">
                                <i class="fa-regular fa-trash-can"></i>
                                Supprimer
                            </a>
                        </div>
                    </div>

                    <?php if (isset($erreur)): ?>
                        <div class="error-box">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?= e($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-grid">
                        <aside class="card user-card">
                            <div class="big-avatar">
                                <?php if ($photo_user): ?>
                                    <img src="<?= e($photo_user) ?>" alt="<?= e($user['prenom_user'] . ' ' . $user['nom_user']) ?>">
                                <?php else: ?>
                                    <?= e(initiales($user['prenom_user'], $user['nom_user'])) ?>
                                <?php endif; ?>
                            </div>

                            <h2><?= e(trim($user['prenom_user'] . ' ' . $user['nom_user'])) ?></h2>

                            <div class="badges">
                                <span class="role"><?= e(roleLabel($role)) ?></span>
                                <span class="status <?= $actif ? 'active' : 'inactive' ?>"><?= $actif ? 'Actif' : 'Inactif' ?></span>
                            </div>

                            <div class="info-list">
                                <div class="info-item">
                                    <i class="fa-regular fa-envelope"></i>
                                    <div><span>E-mail</span><?= e($user['email_user']) ?></div>
                                </div>
                                <div class="info-item">
                                    <i class="fa-solid fa-phone"></i>
                                    <div><span>Téléphone</span><?= e($user['tel_user'] ?: '—') ?></div>
                                </div>
                                <div class="info-item">
                                    <i class="fa-regular fa-calendar"></i>
                                    <div><span>Inscription</span><?= !empty($user['date_inscrip_user']) ? date('d/m/Y', strtotime($user['date_inscrip_user'])) : '—' ?></div>
                                </div>
                                <?php foreach ($details as [$icone, $libelle, $valeur]): ?>
                                    <div class="info-item">
                                        <i class="<?= e($icone) ?>"></i>
                                        <div><span><?= e($libelle) ?></span><?= e($valeur) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </aside>

                        <div>
                            <?php if ($stats): ?>
                                <div class="stats">
                                    <?php foreach ($stats as [$valeur, $libelle]): ?>
                                        <div class="stat">
                                            <strong><?= e($valeur) ?></strong>
                                            <span><?= e($libelle) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="card">
                                <h3><?= e($liste_titre ?: 'Activité') ?></h3>

                                <?php if ($liste): ?>
                                    <?php foreach ($liste as [$titre, $sous_titre, $etat]): ?>
                                        <div class="list-row">
                                            <div>
                                                <?= e($titre) ?>
                                                <?php if ($sous_titre): ?>
                                                    <br><small><?= e($sous_titre) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="role"><?= e($etat) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="muted">Aucune activité à afficher pour ce compte.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </section>
            </main>
        </div>
    </body>
</html>

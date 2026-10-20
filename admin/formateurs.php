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

$sql = "SELECT f.id_formateur, f.specialite, f.experience, f.statut_formateur,
               u.id_user, u.nom_user, u.prenom_user, u.email_user,
               u.tel_user, u.photo_user
        FROM t_formateurs f
        INNER JOIN t_utilisateurs u ON u.id_user = f.id_user
        ORDER BY u.nom_user ASC, u.prenom_user ASC";

$stmt = $con->query($sql);
$formateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($formateurs);
$actifs = 0;
$inactifs = 0;

foreach ($formateurs as $formateur) {
    if (($formateur['statut_formateur'] ?? '') === 'actif') {
        $actifs++;
    } else {
        $inactifs++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formateurs - TOUN</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: "Poppins", sans-serif;
                background: #eef4f8;
                color: #26364a;
            }

            .main-content {
                margin-left: 260px;
                padding: 92px 25px 25px;
                min-height: 100vh;
            }

            .page-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 18px;
            }

            .page-title h1 {
                margin: 0;
                font-size: 23px;
                font-weight: 700;
                color: #183b66;
            }

            .page-title p {
                margin: 4px 0 0;
                font-size: 12px;
                color: #718096;
            }

            .btn-add {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 40px;
                padding: 0 16px;
                border-radius: 9px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                color: #fff;
                text-decoration: none;
                font-size: 12px;
                font-weight: 600;
                box-shadow: 0 5px 14px rgba(32, 161, 184, 0.2);
                transition: 0.2s ease;
            }

            .btn-add:hover {
                transform: translateY(-1px);
                box-shadow: 0 7px 17px rgba(63, 104, 174, 0.24);
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                margin-bottom: 20px;
            }

            .stat-card {
                min-height: 92px;
                padding: 16px 18px;
                background: #fff;
                border-radius: 12px;
                border: 1px solid #dceaf0;
                display: flex;
                align-items: center;
                gap: 14px;
                box-shadow: 0 4px 12px rgba(63, 104, 174, 0.06);
            }

            .stat-card.total {
                border-left: 4px solid #3f68ae;
            }

            .stat-card.active {
                border-left: 4px solid #20a1b8;
            }

            .stat-card.inactive {
                border-left: 4px solid #7285b5;
            }

            .stat-icon {
                width: 46px;
                height: 46px;
                min-width: 46px;
                border-radius: 11px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
            }

            .total .stat-icon {
                background: #e9eef9;
                color: #3f68ae;
            }

            .active .stat-icon {
                background: #e7f7f8;
                color: #20a1b8;
            }

            .inactive .stat-icon {
                background: #eef0f7;
                color: #7285b5;
            }

            .stat-info {
                min-width: 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 3px;
            }

            .stat-number {
                font-size: 22px;
                font-weight: 700;
                line-height: 1.1;
                color: #183b66;
            }

            .stat-label {
                font-size: 12px;
                font-weight: 500;
                line-height: 1.35;
                color: #718096;
                white-space: normal;
            }

            .alert {
                padding: 11px 14px;
                border-radius: 9px;
                margin-bottom: 16px;
                font-size: 12px;
                font-weight: 500;
            }

            .alert-success {
                background: #e9f8f1;
                color: #18794e;
                border: 1px solid #bdebd4;
            }

            .alert-error {
                background: #fff0f0;
                color: #b42318;
                border: 1px solid #f4c7c7;
            }

            .content-card {
                background: #f7fafc;
                border: 1px solid #cfe1ea;
                border-left: 4px solid #20a1b8;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 5px 16px rgba(63, 104, 174, 0.06);
            }

            .content-header {
                min-height: 66px;
                padding: 12px 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
                background: linear-gradient(135deg, #eaf7f9, #edf2fa);
                border-bottom: 1px solid #d5e6ed;
            }

            .content-title {
                display: flex;
                align-items: center;
                gap: 9px;
            }

            .content-title i {
                color: #20a1b8;
                font-size: 15px;
            }

            .content-title h2 {
                margin: 0;
                color: #183b66;
                font-size: 13px;
                font-weight: 700;
            }

            .search-box {
                width: 285px;
                height: 38px;
                position: relative;
            }

            .search-box i {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #20a1b8;
                font-size: 12.5px;
            }

            .search-box input {
                width: 100%;
                height: 100%;
                padding: 0 12px 0 34px;
                border: 1px solid #b9dce4;
                border-radius: 8px;
                outline: none;
                background: #fff;
                color: #26364a;
                font-family: "Poppins", sans-serif;
                font-size: 12px;
            }

            .search-box input:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .table-wrapper {
                width: 100%;
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                min-width: 850px;
            }

            thead th {
                padding: 12px 13px;
                text-align: left;
                background: #e7f1f6;
                color: #49647f;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.2px;
                border-bottom: 1px solid #d1e1e8;
            }

            tbody tr {
                background: #fbfdfe;
                border-bottom: 1px solid #e4edf1;
                transition: 0.15s ease;
            }

            tbody tr:nth-child(even) {
                background: #f5fafc;
            }

            tbody tr:hover {
                background: #eaf7f9;
            }

            tbody td {
                padding: 10px 13px;
                color: #40556c;
                font-size: 12px;
                vertical-align: middle;
            }

            .user-cell {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .avatar {
                width: 38px;
                height: 38px;
                min-width: 38px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid #20a1b8;
                background: #e7f6f8;
            }

            .avatar-placeholder {
                width: 38px;
                height: 38px;
                min-width: 38px;
                border-radius: 50%;
                border: 2px solid #20a1b8;
                background: #e7f6f8;
                color: #20a1b8;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 13px;
            }

            .user-name {
                color: #183b66;
                font-size: 12px;
                font-weight: 600;
            }

            .user-email {
                margin-top: 2px;
                color: #8a98a8;
                font-size: 11px;
            }

            .speciality {
                display: inline-flex;
                align-items: center;
                padding: 5px 9px;
                border-radius: 7px;
                background: #e7f6f8;
                color: #187f93;
                font-size: 11px;
                font-weight: 600;
            }

            .status {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 9px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 600;
            }

            .status::before {
                content: "";
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
            }

            .status.active {
                color: #16805b;
                background: #e7f7f0;
            }

            .status.inactive {
                color: #7b8794;
                background: #eef1f4;
            }

            .actions {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .action-btn {
                width: 29px;
                height: 29px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 7px;
                text-decoration: none;
                font-size: 11.5px;
                transition: 0.15s ease;
            }

            .action-view {
                color: #3f68ae;
                background: #e9eef9;
            }

            .action-edit {
                color: #20a1b8;
                background: #e7f7f8;
            }

            .action-delete {
                color: #c94b4b;
                background: #fff0f0;
            }

            .action-btn:hover {
                transform: translateY(-1px);
            }

            .empty {
                text-align: center;
                padding: 45px 20px;
                color: #8a98a8;
                font-size: 12px;
            }

            .empty i {
                display: block;
                margin-bottom: 10px;
                font-size: 27px;
                color: #9bb9c8;
            }

            @media (max-width: 1000px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }

                .page-header {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .search-box {
                    width: 240px;
                }
            }

            @media (max-width: 700px) {
                .main-content {
                    margin-left: 0;
                    padding: 82px 15px 20px;
                }

                .content-header {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .search-box {
                    width: 100%;
                }
            }
        </style>
    </head>

    <body>

        <?php include 'menu.php'; ?>

        <main class="main-content">

            <div class="page-header">
                <div class="page-title">
                    <h1>Gestion des formateurs</h1>
                    <p>Gérez les formateurs et leurs profils professionnels.</p>
                </div>

                <a href="formateur_ajout.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter un formateur
                </a>
            </div>

            <section class="stats-grid">

                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $total ?></div>
                        <div class="stat-label">Total des formateurs</div>
                    </div>
                </div>

                <div class="stat-card active">
                    <div class="stat-icon">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $actifs ?></div>
                        <div class="stat-label">Formateurs actifs</div>
                    </div>
                </div>

                <div class="stat-card inactive">
                    <div class="stat-icon">
                        <i class="fa-solid fa-user-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $inactifs ?></div>
                        <div class="stat-label">Formateurs inactifs</div>
                    </div>
                </div>

            </section>

            <section class="content-card">

                <div class="content-header">
                    <div class="content-title">
                        <i class="fa-solid fa-users"></i>
                        <h2>Liste des formateurs</h2>
                    </div>

                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="text"
                            id="searchFormateur"
                            placeholder="Rechercher un formateur..."
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div class="table-wrapper">

                    <?php if (empty($formateurs)): ?>

                        <div class="empty">
                            <i class="fa-solid fa-user-tie"></i>
                            Aucun formateur enregistré pour le moment.
                        </div>

                    <?php else: ?>

                        <table id="formateursTable">
                            <thead>
                                <tr>
                                    <th>Formateur</th>
                                    <th>Téléphone</th>
                                    <th>Spécialité</th>
                                    <th>Expérience</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($formateurs as $f): ?>

                                    <?php
$photo = trim((string) ($f['photo_user'] ?? ''));
$photoUrl = '';

if ($photo !== '') {
    if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://') || str_starts_with($photo, '../')) {
        $photoUrl = $photo;
    } else {
        $photoCandidates = [
            '../uploads/' . $photo,
            '../uploads/photos/' . $photo,
            '../assets/uploads/' . $photo,
            '../assets/images/' . $photo
        ];

        foreach ($photoCandidates as $candidate) {
            if (file_exists(__DIR__ . '/' . $candidate)) {
                $photoUrl = $candidate;
                break;
            }
        }

        if ($photoUrl === '') {
            $photoUrl = '../uploads/photos/' . $photo;
        }
    }
}

$initiales = strtoupper(substr($f['prenom_user'] ?? '', 0, 1)) . strtoupper(substr($f['nom_user'] ?? '', 0, 1));
?>

                                    <tr>
                                        <td>
                                            <div class="user-cell">

                                                <?php if ($photoUrl !== ''): ?>
                                                    <img
                                                        src="<?= e($photoUrl) ?>"
                                                        alt="Photo"
                                                        class="avatar"
                                                    >
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <?= e($initiales) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div>
                                                    <div class="user-name">
                                                        <?= e(($f['prenom_user'] ?? '') . ' ' . ($f['nom_user'] ?? '')) ?>
                                                    </div>
                                                    <div class="user-email">
                                                        <?= e($f['email_user']) ?>
                                                    </div>
                                                </div>

                                            </div>
                                        </td>

                                        <td>
                                            <?= e($f['tel_user'] ?: '—') ?>
                                        </td>

                                        <td>
                                            <span class="speciality">
                                                <?= e($f['specialite']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= e($f['experience'] ?: '—') ?>
                                        </td>

                                        <td>
                                            <?php if (($f['statut_formateur'] ?? '') === 'actif'): ?>
                                                <span class="status active">Actif</span>
                                            <?php else: ?>
                                                <span class="status inactive">Inactif</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="actions">
                                                <a
                                                    href="voir_formateur.php?id=<?= (int) $f['id_formateur'] ?>"
                                                    class="action-btn action-view"
                                                    title="Voir"
                                                >
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>

                                                <a
                                                    href="modifier_formateur.php?id=<?= (int) $f['id_formateur'] ?>"
                                                    class="action-btn action-edit"
                                                    title="Modifier"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>

                                                <a
                                                    href="supprimer_formateur.php?id=<?= (int) $f['id_formateur'] ?>"
                                                    class="action-btn action-delete"
                                                    title="Supprimer"
                                                    onclick="return confirm('Voulez-vous vraiment supprimer ce formateur ?');"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>

                    <?php endif; ?>

                </div>
            </section>

        </main>

        <script>
            const searchInput = document.getElementById('searchFormateur');
            const rows = document.querySelectorAll('#formateursTable tbody tr');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const search = this.value.toLowerCase().trim();

                    rows.forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
                    });
                });
            }
        </script>

    </body>
</html>

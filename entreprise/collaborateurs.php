<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'collaborateurs.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Collaborateurs — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .collab-stats {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-bottom: 12px;
            }

            .collab-stats > div {
                padding: 10px 12px;
                border-left: 4px solid transparent;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.72);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                box-shadow: 0 6px 16px rgba(25, 64, 91, 0.06);
                display: flex;
                align-items: center;
                gap: 9px;
                transition: 0.2s;
            }

            .collab-stats > div:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(25, 64, 91, 0.1);
            }

            .collab-stats .blue {
                border-left-color: #3f68ae;
            }

            .collab-stats .green {
                border-left-color: #1c9a82;
            }

            .collab-stats .gray {
                border-left-color: #77899a;
            }

            .collab-stats i {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: grid;
                place-items: center;
                font-size: 11.5px;
                flex: 0 0 auto;
            }

            .collab-stats .blue i {
                background: #eaf1ff;
                color: #3f68ae;
            }

            .collab-stats .green i {
                background: #e9f8f5;
                color: #1c9a82;
            }

            .collab-stats .gray i {
                background: #f0f3f5;
                color: #77899a;
            }

            .collab-stats span {
                color: #8b9da9;
                font-size: 10px;
            }

            .collab-stats strong {
                display: block;
                color: #173d62;
                font-size: 15px;
                margin-top: 1px;
            }

            .toolbar {
                padding: 10px 13px;
                border-bottom: 1px solid #edf2f4;
                display: flex;
                justify-content: flex-end;
            }

            .search {
                width: 300px;
                height: 32px;
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 0 10px;
                border: 1px solid #dce8ed;
                border-radius: 9px;
                background: #fafcfd;
            }

            .search i {
                font-size: 11px;
                color: #8ca4b2;
            }

            .search input {
                width: 100%;
                border: 0;
                outline: 0;
                background: transparent;
                font: 10.5px Poppins;
                color: #405d76;
            }

            .table-wrap {
                overflow: auto;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
            }

            .table th {
                padding: 9px 12px;
                background: linear-gradient(135deg, #eaf1ff, #eef8fa);
                color: #2c5680;
                font-size: 10px;
                font-weight: 800;
                text-align: left;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                border-bottom: 2px solid #cfe3ea;
            }

            .table td {
                padding: 9px 12px;
                border-top: 1px solid #edf2f4;
                font-size: 10.5px;
            }

            .table tbody tr {
                border-left: 3px solid transparent;
                transition: 0.15s;
            }

            .table tbody tr:hover {
                background: #f8fcfd;
                border-left-color: #20a1b8;
            }

            .user {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .avatar {
                width: 32px;
                height: 32px;
                border-radius: 9px;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
                color: #fff;
                display: grid;
                place-items: center;
                overflow: hidden;
                font-size: 10px;
                font-weight: 800;
            }

            .avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .user strong {
                display: block;
                color: #294965;
                font-size: 10.5px;
            }

            .user span {
                display: block;
                color: #9aaab5;
                font-size: 10px;
                margin-top: 1px;
            }

            .count-pill {
                display: grid;
                place-items: center;
                width: 27px;
                height: 23px;
                border-radius: 7px;
                background: #eaf1ff;
                color: #3f68ae;
                font-size: 10px;
                font-weight: 800;
            }

            .status {
                display: inline-flex;
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: 800;
            }

            .status.ok {
                background: #e9f8f5;
                color: #23866e;
            }

            .status.off {
                background: #f0f3f5;
                color: #82939f;
            }

            .actions {
                display: flex;
                gap: 4px;
            }

            .action {
                width: 27px;
                height: 27px;
                border: 1px solid #e2ebef;
                border-radius: 8px;
                background: #fff;
                color: #708696;
                display: grid;
                place-items: center;
                font-size: 10.5px;
                transition: 0.2s;
            }

            .action:hover {
                transform: translateY(-1px);
                background: #eef8fa;
                color: #168b9a;
                border-color: #b8dfe5;
            }

            .action.danger:hover {
                color: #c35b66;
                background: #fff1f2;
            }

            .action.success:hover {
                color: #21866f;
                background: #eaf8f5;
            }

            .empty {
                text-align: center;
                padding: 40px;
                color: #8fa0ac;
                font-size: 10.5px;
            }

            @media (max-width: 650px) {
                .collab-stats {
                    grid-template-columns: 1fr;
                }

                .toolbar {
                    justify-content: stretch;
                }

                .search {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">
                <?php
$q = trim($_GET['q'] ?? '');
$collaborateurs = entrepriseCollaborators($con, $idEntreprise);
if ($q !== '') {
    $collaborateurs = array_values(
        array_filter(
            $collaborateurs,
            fn($r) => stripos($r['nom_user'] . ' ' . $r['prenom_user'] . ' ' . $r['email_user'], $q) !== false
        )
    );
}
$total = count($collaborateurs);
$actifs = count(array_filter($collaborateurs, fn($r) => $r['statut'] === 'actif'));
$inactifs = $total - $actifs;
?>

                <div class="page-head">
                    <div>
                        <span class="eyebrow">ÉQUIPE</span>
                        <h1>Collaborateurs</h1>
                        <p>Ajoutez, modifiez et contrôlez les accès de votre équipe.</p>
                    </div>
                    <a class="btn" href="collaborateur-ajouter.php"><i class="fa-solid fa-user-plus"></i> Ajouter</a>
                </div>

                <?php if ($flash): ?>
                    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>

                <section class="collab-stats">
                    <div class="blue"><i class="fa-solid fa-users"></i><span>Total<strong><?= $total ?></strong></span></div>
                    <div class="green"><i class="fa-solid fa-user-check"></i><span>Actifs<strong><?= $actifs ?></strong></span></div>
                    <div class="gray"><i class="fa-solid fa-user-slash"></i><span>Inactifs<strong><?= $inactifs ?></strong></span></div>
                </section>

                <section class="panel">
                    <div class="toolbar">
                        <form class="search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input name="q" value="<?= e($q) ?>" placeholder="Rechercher un collaborateur...">
                        </form>
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th>Téléphone</th>
                                    <th>Formations</th>
                                    <th>Ajouté</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$collaborateurs): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty">Aucun collaborateur trouvé.</div>
                                        </td>
                                    </tr>
                                <?php else: foreach ($collaborateurs as $r):
                    $initiales = strtoupper(mb_substr($r['prenom_user'], 0, 1, 'UTF-8') . mb_substr($r['nom_user'], 0, 1, 'UTF-8'));
                    $photo = !empty($r['photo_user']) ? '../'.ltrim(str_replace('\\', '/', $r['photo_user']), '/') : '';
                ?>
                                    <tr>
                                        <td>
                                            <div class="user">
                                                <div class="avatar">
                                                    <?php if ($photo): ?>
                                                        <img src="<?= e($photo) ?>" alt="">
                                                    <?php else: ?>
                                                        <?= e($initiales) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <strong><?= e($r['prenom_user'] . ' ' . $r['nom_user']) ?></strong>
                                                    <span><?= e($r['email_user']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e($r['tel_user'] ?: '—') ?></td>
                                        <td><span class="count-pill"><?= e($r['formations']) ?></span></td>
                                        <td><?= e(date('d/m/Y', strtotime($r['date_ajout']))) ?></td>
                                        <td><span class="status <?= $r['statut'] === 'actif' ? 'ok' : 'off' ?>"><?= $r['statut'] === 'actif' ? 'Actif' : 'Inactif' ?></span></td>
                                        <td>
                                            <div class="actions">
                                                <a class="action" href="collaborateur-details.php?id=<?= $r['id_collaborateur'] ?>"><i class="fa-regular fa-eye"></i></a>
                                                <a class="action" href="collaborateur-modifier.php?id=<?= $r['id_collaborateur'] ?>"><i class="fa-solid fa-pen"></i></a>
                                                <?php if ($r['statut'] === 'actif'): ?>
                                                    <a class="action danger" href="collaborateur-desactiver.php?id=<?= $r['id_collaborateur'] ?>" onclick="return confirm('Désactiver ce collaborateur ?')"><i class="fa-solid fa-user-slash"></i></a>
                                                <?php else: ?>
                                                    <a class="action success" href="collaborateur-reactiver.php?id=<?= $r['id_collaborateur'] ?>"><i class="fa-solid fa-user-check"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </main>
    </body>
</html>

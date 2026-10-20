<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'progression.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Progression — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .progress-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin-bottom: 12px;
            }

            .progress-stats > div {
                height: 72px;
                padding: 10px 12px;
                border: 1px solid #dce9ef;
                border-right: 3px solid transparent;
                border-radius: 12px;
                background: #fff;
                display: flex;
                align-items: center;
                gap: 9px;
                transition: 0.2s;
            }

            .progress-stats > div:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px rgba(25, 64, 91, 0.08);
            }

            .progress-stats i {
                width: 31px;
                height: 31px;
                border-radius: 9px;
                background: #eef8fa;
                color: #168b9a;
                display: grid;
                place-items: center;
                font-size: 11.5px;
            }

            .progress-stats .turquoise {
                border-right-color: #20a1b8;
            }

            .progress-stats .turquoise i {
                background: #eef8fa;
                color: #168b9a;
            }

            .progress-stats .blue {
                border-right-color: #0b5a91;
            }

            .progress-stats .blue i {
                background: #eaf3f9;
                color: #0b5a91;
            }

            .progress-stats .green {
                border-right-color: #1c9a82;
            }

            .progress-stats .green i {
                background: #e9f8f5;
                color: #1c9a82;
            }

            .progress-stats .gray {
                border-right-color: #77899a;
            }

            .progress-stats .gray i {
                background: #f0f3f5;
                color: #77899a;
            }

            .progress-stats span {
                color: #8c9da9;
                font-size: 10px;
            }

            .progress-stats strong {
                display: block;
                color: #173d62;
                font-size: 16px;
                margin-top: 2px;
            }

            .section-head {
                padding: 12px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #edf2f4;
            }

            .count-pill {
                width: 27px;
                height: 23px;
                display: grid;
                place-items: center;
                border-radius: 7px;
                background: #eef8fa;
                color: #168b9a;
                font-size: 10px;
                font-weight: 800;
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
                background: linear-gradient(135deg, #eaf3f9, #eef8fa);
                color: #2c5680;
                font-size: 10px;
                font-weight: 800;
                text-align: left;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                border-bottom: 2px solid #cfe3ea;
            }

            .table tbody tr {
                border-left: 3px solid transparent;
                transition: 0.15s;
            }

            .table tbody tr:hover {
                background: #f8fcfd;
                border-left-color: #20a1b8;
            }

            .table td {
                padding: 11px 12px;
                border-top: 1px solid #edf2f4;
                font-size: 10.5px;
                color: #526f84;
            }

            .table td strong {
                color: #294965;
            }

            .progress-cell {
                display: flex;
                align-items: center;
                gap: 7px;
                min-width: 170px;
            }

            .progress-line {
                width: 130px;
                height: 6px;
                border-radius: 20px;
                background: #e8eff2;
                overflow: hidden;
            }

            .progress-line span {
                display: block;
                height: 100%;
                border-radius: 20px;
                background: linear-gradient(90deg, #20a1b8, #0b5a91);
            }

            .progress-cell b {
                font-size: 10px;
                color: #168b9a;
            }

            .state {
                display: inline-flex;
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: 800;
            }

            .state.done {
                background: #e9f8f5;
                color: #23866e;
            }

            .state.doing {
                background: #eef8fa;
                color: #168b9a;
            }

            .state.waiting {
                background: #f1f4f6;
                color: #7f909d;
            }

            .empty {
                text-align: center;
                padding: 40px;
                color: #8fa0ac;
                font-size: 10.5px;
            }

            @media (max-width: 850px) {
                .progress-stats {
                    grid-template-columns: 1fr 1fr;
                }
            }

            @media (max-width: 500px) {
                .progress-stats {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">
                <?php
$rows = [];
$progressCol = getProgressColumn($con);
$collabs = entrepriseCollaborators($con, $idEntreprise);
$started = 0;
$finished = 0;
$notStarted = 0;
$avg = 0;

if ($collabs) {
    $ids = array_column($collabs, 'id_user');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $sql = 'SELECT i.id_inscrip,i.id_user,i.id_form,u.nom_user,u.prenom_user,f.titre_form,i.statut_inscrip';
    $sql .= $progressCol ? ",COALESCE(AVG(p.$progressCol),0) progression" : ',0 progression';
    $sql .=
        ' FROM t_inscriptions i INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user=i.id_user AND ec.id_entreprise=? INNER JOIN t_utilisateurs u ON u.id_user=i.id_user INNER JOIN t_formations f ON f.id_form=i.id_form';
    if ($progressCol) {
        $sql .= ' LEFT JOIN t_progression p ON p.id_user=i.id_user AND p.id_form=i.id_form';
    }
    $sql .=
        ' GROUP BY i.id_inscrip,i.id_user,i.id_form,u.nom_user,u.prenom_user,f.titre_form,i.statut_inscrip ORDER BY u.nom_user,f.titre_form';
    $params = array_merge([$idEntreprise]);
    $s = $con->prepare($sql);
    $s->execute($params);
    $rows = $s->fetchAll();
}

foreach ($rows as &$r) {
    $r['progression'] = max(0, min(100, (float) $r['progression']));
    if ($r['progression'] >= 100) {
        $finished++;
    } elseif ($r['progression'] > 0) {
        $started++;
    } else {
        $notStarted++;
    }
}
unset($r);
$avg = $rows ? array_sum(array_column($rows, 'progression')) / count($rows) : 0;
?>

                <div class="page-head">
                    <div>
                        <span class="eyebrow">SUIVI</span>
                        <h1>Progression</h1>
                        <p>Une vue claire de l’avancement de chaque collaborateur.</p>
                    </div>
                </div>

                <section class="progress-stats">
                    <div class="turquoise"><i class="fa-solid fa-chart-line"></i><span>Progression moyenne<strong><?= number_format($avg, 0) ?>%</strong></span></div>
                    <div class="blue"><i class="fa-solid fa-spinner"></i><span>En cours<strong><?= $started ?></strong></span></div>
                    <div class="green"><i class="fa-solid fa-circle-check"></i><span>Terminées<strong><?= $finished ?></strong></span></div>
                    <div class="gray"><i class="fa-solid fa-circle-pause"></i><span>Non commencées<strong><?= $notStarted ?></strong></span></div>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <div>
                            <span class="eyebrow">PARCOURS</span>
                            <h2>Suivi des collaborateurs</h2>
                        </div>
                        <span class="count-pill"><?= count($rows) ?></span>
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th>Formation</th>
                                    <th>Avancement</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="4">
                                            <div class="empty">Aucune formation n’est encore affectée.</div>
                                        </td>
                                    </tr>
                                <?php else: foreach ($rows as $r): $p = (float)$r['progression']; $state = $p >= 100 ? 'Terminée' : ($p > 0 ? 'En cours' : 'Non commencée'); ?>
                                    <tr>
                                        <td><strong><?= e($r['prenom_user'] . ' ' . $r['nom_user']) ?></strong></td>
                                        <td><?= e($r['titre_form']) ?></td>
                                        <td>
                                            <div class="progress-cell">
                                                <div class="progress-line"><span style="width:<?= $p ?>%"></span></div>
                                                <b><?= number_format($p, 0) ?>%</b>
                                            </div>
                                        </td>
                                        <td><span class="state <?= $p >= 100 ? 'done' : ($p > 0 ? 'doing' : 'waiting') ?>"><?= $state ?></span></td>
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

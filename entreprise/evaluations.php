<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'evaluations.php';
$flash = getFlash();
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Évaluations — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .eval-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin-bottom: 12px;
            }

            .eval-stats > div {
                height: 72px;
                padding: 10px 12px;
                border: 1px solid #dce9ef;
                border-bottom: 3px solid transparent;
                border-radius: 12px;
                background: #fff;
                display: flex;
                align-items: center;
                gap: 9px;
                transition: 0.2s;
            }

            .eval-stats > div:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px rgba(25, 64, 91, 0.08);
            }

            .eval-stats i {
                width: 31px;
                height: 31px;
                border-radius: 9px;
                background: #eef8fa;
                color: #168b9a;
                display: grid;
                place-items: center;
                font-size: 11.5px;
            }

            .eval-stats .blue {
                border-bottom-color: #0b5a91;
            }

            .eval-stats .blue i {
                background: #eaf3f9;
                color: #0b5a91;
            }

            .eval-stats .green {
                border-bottom-color: #1c9a82;
            }

            .eval-stats .green i {
                background: #e9f8f5;
                color: #1c9a82;
            }

            .eval-stats .turquoise {
                border-bottom-color: #20a1b8;
            }

            .eval-stats .turquoise i {
                background: #eef8fa;
                color: #168b9a;
            }

            .eval-stats .red {
                border-bottom-color: #c55362;
            }

            .eval-stats .red i {
                background: #fff0f2;
                color: #c55362;
            }

            .eval-stats span {
                color: #8c9da9;
                font-size: 10px;
            }

            .eval-stats strong {
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

            .rate {
                padding: 5px 8px;
                border-radius: 20px;
                background: #e9f8f5;
                color: #23866e;
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
                padding: 10px 12px;
                border-top: 1px solid #edf2f4;
                font-size: 10px;
                color: #526f84;
            }

            .table td strong {
                color: #294965;
            }

            .note {
                color: #168b9a;
                font-size: 10.5px;
                font-weight: 800;
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

            .state.retry {
                background: #fff0f2;
                color: #c55362;
            }

            .state.wait {
                background: #eef8fa;
                color: #168b9a;
            }

            .empty {
                text-align: center;
                padding: 40px;
                color: #8fa0ac;
                font-size: 10.5px;
            }

            .empty i {
                display: block;
                font-size: 20px;
                color: #20a1b8;
                margin-bottom: 7px;
            }

            .empty strong {
                display: block;
                color: #5f788c;
                font-size: 11px;
            }

            .empty span {
                display: block;
                font-size: 10px;
                margin-top: 3px;
            }

            @media (max-width: 850px) {
                .eval-stats {
                    grid-template-columns: 1fr 1fr;
                }
            }

            @media (max-width: 500px) {
                .eval-stats {
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
$total = 0;
$success = 0;
$toPass = 0;
$retry = 0;

if (tableExists($con, 't_quiz') && tableExists($con, 't_resultats_quiz')) {
    $sql = "SELECT q.id_quiz,q.titre_quiz,q.description_quiz,q.note_minimale,m.titre_module,f.titre_form,u.id_user,u.nom_user,u.prenom_user,r.id_resultat,r.note_obtenue,r.statut_resultat,r.date_passage
            FROM t_inscriptions i
            INNER JOIN t_entreprise_collaborateurs ec ON ec.id_user=i.id_user AND ec.id_entreprise=?
            INNER JOIN t_utilisateurs u ON u.id_user=i.id_user
            INNER JOIN t_formations f ON f.id_form=i.id_form
            INNER JOIN t_modules m ON m.id_form=f.id_form
            INNER JOIN t_quiz q ON q.id_module=m.id_module
            LEFT JOIN t_resultats_quiz r ON r.id_resultat=(SELECT MAX(r2.id_resultat) FROM t_resultats_quiz r2 WHERE r2.id_user=i.id_user AND r2.id_quiz=q.id_quiz)
            WHERE i.statut_inscrip IN ('active','terminee','terminée')
            ORDER BY u.nom_user,f.titre_form,q.titre_quiz";
    $s = $con->prepare($sql);
    $s->execute([$idEntreprise]);
    $rows = $s->fetchAll();
    $total = count($rows);
    foreach ($rows as $r) {
        $st = mb_strtolower(trim((string) ($r['statut_resultat'] ?? '')), 'UTF-8');
        if (empty($r['id_resultat'])) {
            $toPass++;
        } elseif (in_array($st, ['réussi', 'reussi', 'success', 'valide', 'validé'], true)) {
            $success++;
        } else {
            $retry++;
        }
    }
}
?>

                <div class="page-head">
                    <div>
                        <span class="eyebrow">SUIVI</span>
                        <h1>Évaluations</h1>
                        <p>Consultez les résultats obtenus par vos collaborateurs.</p>
                    </div>
                </div>

                <section class="eval-stats">
                    <div class="blue"><i class="fa-solid fa-clipboard-list"></i><span>Total<strong><?= $total ?></strong></span></div>
                    <div class="green"><i class="fa-solid fa-circle-check"></i><span>Réussies<strong><?= $success ?></strong></span></div>
                    <div class="turquoise"><i class="fa-solid fa-clock"></i><span>À passer<strong><?= $toPass ?></strong></span></div>
                    <div class="red"><i class="fa-solid fa-rotate-right"></i><span>À reprendre<strong><?= $retry ?></strong></span></div>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <div>
                            <span class="eyebrow">RÉSULTATS</span>
                            <h2>Résultats des collaborateurs</h2>
                        </div>
                        <span class="rate"><?= $total ? number_format(($success / $total) * 100, 0) : 0 ?>% réussite</span>
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th>Formation</th>
                                    <th>Évaluation</th>
                                    <th>Note</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty"><i class="fa-solid fa-clipboard-check"></i><strong>Aucun résultat d’évaluation.</strong><span>Les résultats apparaîtront lorsque vos collaborateurs auront des évaluations liées à leurs formations.</span></div>
                                        </td>
                                    </tr>
                                <?php else: foreach ($rows as $r):
                    $st = mb_strtolower(trim((string)($r['statut_resultat'] ?? '')), 'UTF-8');
                    $has = !empty($r['id_resultat']);
                    $ok = $has && in_array($st, ['réussi', 'reussi', 'success', 'valide', 'validé'], true);
                    $label = $has ? ($ok ? 'Réussi' : 'À reprendre') : 'À passer';
                ?>
                                    <tr>
                                        <td><strong><?= e($r['prenom_user'] . ' ' . $r['nom_user']) ?></strong></td>
                                        <td><?= e($r['titre_form']) ?></td>
                                        <td><?= e($r['titre_quiz']) ?></td>
                                        <td><b class="note"><?= $has ? e($r['note_obtenue']) . '/20' : '—' ?></b></td>
                                        <td><span class="state <?= $ok ? 'done' : ($has ? 'retry' : 'wait') ?>"><?= $label ?></span></td>
                                        <td><?= !empty($r['date_passage']) ? e(date('d/m/Y', strtotime($r['date_passage']))) : '—' ?></td>
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

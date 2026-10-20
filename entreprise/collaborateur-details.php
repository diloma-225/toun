<?php
require __DIR__ . '/entreprise_init.php';
$active_page = 'collaborateur-details.php';
$flash = getFlash();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$s = $con->prepare("
    SELECT ec.*, u.id_user, u.nom_user, u.prenom_user, u.email_user, u.tel_user, u.photo_user, u.date_inscrip_user
    FROM t_entreprise_collaborateurs ec
    INNER JOIN t_utilisateurs u ON u.id_user = ec.id_user
    WHERE ec.id_collaborateur = ? AND ec.id_entreprise = ?
    LIMIT 1
");
$s->execute([$id, $idEntreprise]);
$r = $s->fetch();

if (!$r) {
    exit('Collaborateur introuvable.');
}

$s = $con->prepare("
    SELECT f.id_form, f.titre_form, i.date_inscrip_form, i.statut_inscrip
    FROM t_inscriptions i
    INNER JOIN t_formations f ON f.id_form = i.id_form
    WHERE i.id_user = ?
    ORDER BY i.date_inscrip_form DESC
");
$s->execute([$r['id_user']]);
$formations = $s->fetchAll();

$nom = trim($r['prenom_user'] . ' ' . $r['nom_user']);
$initiales = strtoupper(mb_substr($r['prenom_user'], 0, 1, 'UTF-8') . mb_substr($r['nom_user'], 0, 1, 'UTF-8'));
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Collaborateur — TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            .detail-grid {
                display: grid;
                grid-template-columns: 260px 1fr;
                gap: 12px;
            }

            .card {
                padding: 14px;
            }

            .profile-card {
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 230px;
            }

            .detail-photo {
                width: 90px;
                height: 90px;
                border-radius: 20px;
                background: linear-gradient(135deg, #20a1b8, #3f68ae);
                display: grid;
                place-items: center;
                color: #fff;
                font-size: 24px;
                font-weight: 800;
                overflow: hidden;
                margin-bottom: 10px;
            }

            .detail-photo img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .profile-card h2 {
                font-size: 13px;
            }

            .role {
                color: #92a2ae;
                font-size: 10px;
                margin: 3px 0 9px;
            }

            .badge {
                display: inline-flex;
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: 800;
            }

            .badge.ok {
                background: #e9f8f5;
                color: #23866e;
            }

            .badge.off {
                background: #f0f3f5;
                color: #82939f;
            }

            .card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 10px;
            }

            .info-list {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .info-list > div {
                padding: 11px;
                border-top: 1px solid #edf2f4;
            }

            .info-list span {
                display: block;
                color: #91a0ab;
                font-size: 10px;
                margin-bottom: 3px;
            }

            .info-list strong {
                font-size: 10.5px;
                color: #294965;
            }

            .formations-card {
                margin-top: 12px;
            }

            .count {
                min-width: 24px;
                height: 22px;
                display: grid;
                place-items: center;
                border-radius: 7px;
                background: #eef8fa;
                color: #168b9a;
                font-size: 10px;
                font-weight: 800;
            }

            .formation-list {
                display: grid;
                gap: 6px;
            }

            .formation-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 9px;
                border: 1px solid #e8eef1;
                border-radius: 9px;
            }

            .formation-row strong {
                display: block;
                font-size: 10.5px;
                color: #294965;
            }

            .formation-row span:not(.badge) {
                display: block;
                color: #98a8b3;
                font-size: 10px;
                margin-top: 2px;
            }

            .empty {
                text-align: center;
                padding: 28px;
                color: #8fa0ac;
                font-size: 10.5px;
            }

            @media (max-width: 700px) {
                .detail-grid {
                    grid-template-columns: 1fr;
                }

                .info-list {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/menu_entreprise.php'; ?>
        <main class="main-content">
            <div class="page">

                <div class="page-head">
                    <div>
                        <span class="eyebrow">COLLABORATEUR</span>
                        <h1><?= e($nom) ?></h1>
                        <p><?= e($r['email_user']) ?></p>
                    </div>
                    <a class="btn btn-light" href="collaborateurs.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
                </div>

                <section class="detail-grid">
                    <div class="card profile-card">
                        <div class="detail-photo">
                            <?php if (!empty($r['photo_user'])): ?>
                                <img src="<?= e('../' . ltrim(str_replace('\\', '/', $r['photo_user']), '/')) ?>" alt="">
                            <?php else: ?>
                                <span><?= e($initiales) ?></span>
                            <?php endif; ?>
                        </div>
                        <h2><?= e($nom) ?></h2>
                        <span class="role">Collaborateur</span>
                        <span class="badge <?= $r['statut'] === 'actif' ? 'ok' : 'off' ?>"><?= e(ucfirst($r['statut'])) ?></span>
                    </div>

                    <div class="card">
                        <div class="card-head">
                            <h2>Informations</h2>
                            <a class="btn btn-light" href="collaborateur-modifier.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Modifier</a>
                        </div>
                        <div class="info-list">
                            <div>
                                <span>E-mail</span>
                                <strong><?= e($r['email_user']) ?></strong>
                            </div>
                            <div>
                                <span>Téléphone</span>
                                <strong><?= e($r['tel_user'] ?: '—') ?></strong>
                            </div>
                            <div>
                                <span>Ajouté le</span>
                                <strong><?= e(date('d/m/Y', strtotime($r['date_ajout']))) ?></strong>
                            </div>
                            <div>
                                <span>Compte créé le</span>
                                <strong><?= !empty($r['date_inscrip_user']) ? e(date('d/m/Y', strtotime($r['date_inscrip_user']))) : '—' ?></strong>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card formations-card">
                    <div class="card-head">
                        <h2>Formations suivies</h2>
                        <span class="count"><?= $n = count($formations) ? $n : 0 ?></span>
                    </div>

                    <?php if (!$formations): ?>
                        <div class="empty">Aucune formation attribuée ou achetée.</div>
                    <?php else: ?>
                        <div class="formation-list">
                            <?php foreach ($formations as $f): ?>
                                <div class="formation-row">
                                    <div>
                                        <strong><?= e($f['titre_form']) ?></strong>
                                        <span><?= !empty($f['date_inscrip_form']) ? e(date('d/m/Y', strtotime($f['date_inscrip_form']))) : '—' ?></span>
                                    </div>
                                    <span class="badge <?= $f['statut_inscrip'] === 'active' ? 'ok' : 'off' ?>"><?= e($f['statut_inscrip']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

            </div>
        </main>
    </body>
</html>

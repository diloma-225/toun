<?php
session_start();
require_once "../config/connexion.php";

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'gest_entreprise') {
    header("Location: ../connexion.php");
    exit;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$userId = (int)$_SESSION['id_user'];
$stmtEntreprise = $con->prepare("SELECT id_entreprise, nom_entreprise FROM t_entreprises WHERE id_user = ? LIMIT 1");
$stmtEntreprise->execute([$userId]);
$entreprise = $stmtEntreprise->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    die("Entreprise introuvable.");
}

$idEntreprise = (int)$entreprise['id_entreprise'];

$recherche = trim($_GET['q'] ?? '');
$sql = "
SELECT ec.id_collaborateur, ec.id_user, ec.date_ajout, ec.statut,
u.nom_user, u.prenom_user, u.email_user, u.tel_user, u.photo_user, u.statut_user,
COUNT(DISTINCT i.id_form) AS formations
FROM t_entreprise_collaborateurs ec
INNER JOIN t_utilisateurs u ON u.id_user = ec.id_user
LEFT JOIN t_inscriptions i ON i.id_user = u.id_user
WHERE ec.id_entreprise = ?
";
$params = [$idEntreprise];
if ($recherche !== '') {
    $sql .= " AND (u.nom_user LIKE ? OR u.prenom_user LIKE ? OR u.email_user LIKE ?) ";
    $mot = "%".$recherche."%";
    array_push($params, $mot, $mot, $mot);
}
$sql .= " GROUP BY ec.id_collaborateur, ec.id_user, ec.date_ajout, ec.statut,
u.nom_user, u.prenom_user, u.email_user, u.tel_user, u.photo_user, u.statut_user
ORDER BY u.nom_user ASC, u.prenom_user ASC";
$stmt = $con->prepare($sql);
$stmt->execute($params);
$collaborateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCollaborateurs = count($collaborateurs);
$actifs = count(array_filter($collaborateurs, fn($c) => ($c['statut'] ?? '') === 'actif'));
$inactifs = $totalCollaborateurs - $actifs;
$messages = [
'ajout'=>'Le collaborateur a été ajouté à votre équipe.',
'modifie'=>'Les informations du collaborateur ont été mises à jour.',
'desactive'=>'Le collaborateur a été désactivé.',
'reactive'=>'Le collaborateur a été réactivé.'
];
$messageConfirmation = null;
foreach ($messages as $key=>$value) {
    if (($_GET[$key] ?? '') === 'ok') { $messageConfirmation = $value; break; }
}
$active_page = 'collaborateurs.php';
?>

<section class="ci-page">
<?php if ($messageConfirmation): ?>
<div class="ci-alert"><i class="fa-solid fa-circle-check"></i><?= e($messageConfirmation) ?></div>
<?php endif; ?>

<div class="ci-head">
<div>
<span class="ci-eyebrow">GESTION DE L'ÉQUIPE</span>
<h1>Collaborateurs</h1>
<p>Gérez les membres de votre entreprise et leurs accès aux formations.</p>
</div>
<a href="collaborateur-ajouter.php" class="ci-primary"><i class="fa-solid fa-user-plus"></i>Ajouter un collaborateur</a>
</div>

<div class="ci-stats">
<div><span class="ci-stat-icon"><i class="fa-solid fa-users"></i></span><section><small>Total</small><strong><?= $totalCollaborateurs ?></strong></section></div>
<div><span class="ci-stat-icon green"><i class="fa-solid fa-user-check"></i></span><section><small>Actifs</small><strong><?= $actifs ?></strong></section></div>
<div><span class="ci-stat-icon gray"><i class="fa-solid fa-user-slash"></i></span><section><small>Inactifs</small><strong><?= $inactifs ?></strong></section></div>
</div>

<div class="ci-panel">
<div class="ci-toolbar">
<h2>Membres de votre équipe</h2>
<form method="get" class="ci-search"><i class="fa-solid fa-magnifying-glass"></i><input name="q" value="<?= e($recherche) ?>" placeholder="Rechercher un collaborateur..."></form>
</div>
<?php if (!$collaborateurs): ?>
<div class="ci-empty"><i class="fa-solid fa-users"></i><strong><?= $recherche ? 'Aucun résultat trouvé' : 'Aucun collaborateur' ?></strong><p><?= $recherche ? 'Aucun collaborateur ne correspond à votre recherche.' : 'Ajoutez votre premier collaborateur.' ?></p><?php if (!$recherche): ?><a href="collaborateur-ajouter.php" class="ci-primary">Ajouter</a><?php endif; ?></div>
<?php else: ?>
<div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Collaborateur</th><th>Téléphone</th><th>Formations</th><th>Ajouté le</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($collaborateurs as $c):
$nom = trim(($c['prenom_user'] ?? '').' '.($c['nom_user'] ?? ''));
$initiales = strtoupper(substr($c['prenom_user'] ?? '',0,1).substr($c['nom_user'] ?? '',0,1));
$actif = ($c['statut'] ?? '') === 'actif';
$photo = trim((string)($c['photo_user'] ?? ''));
$photoUrl = '';
if ($photo !== '') {
    $p = str_replace('\\','/',$photo);
    $photoUrl = '../'.ltrim($p,'/');
}
?>
<tr>
<td><div class="ci-user"><div class="ci-avatar"><?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt=""><?php else: ?><?= e($initiales ?: 'CO') ?><?php endif; ?></div><div><strong><?= e($nom ?: 'Collaborateur') ?></strong><span><?= e($c['email_user'] ?? '') ?></span></div></div></td>
<td><?= e($c['tel_user'] ?: '—') ?></td><td><b class="ci-count"><?= (int)$c['formations'] ?></b></td>
<td><?= !empty($c['date_ajout']) ? date('d/m/Y', strtotime($c['date_ajout'])) : '—' ?></td>
<td><span class="ci-status <?= $actif ? 'active':'inactive' ?>"><?= $actif?'Actif':'Inactif' ?></span></td>
<td><div class="ci-actions">
<a href="collaborateur-details.php?id=<?= (int)$c['id_collaborateur'] ?>" title="Voir"><i class="fa-regular fa-eye"></i></a>
<a href="collaborateur-modifier.php?id=<?= (int)$c['id_collaborateur'] ?>" title="Modifier"><i class="fa-solid fa-pen"></i></a>
<?php if ($actif): ?><a href="collaborateur-desactiver.php?id=<?= (int)$c['id_collaborateur'] ?>" title="Désactiver"><i class="fa-solid fa-user-slash"></i></a>
<?php else: ?><a href="collaborateur-reactiver.php?id=<?= (int)$c['id_collaborateur'] ?>" title="Réactiver"><i class="fa-solid fa-user-check"></i></a><?php endif; ?>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>
</section>

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collaborateurs — TOUN</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet" href="entreprise.css">
<style>

.ci-page{max-width:1180px;margin:0 auto;padding:8px 0 40px;font-family:Poppins,sans-serif;color:#102a43}
.ci-alert{background:#eafaf8;border:1px solid #82d8cf;color:#087f76;border-radius:12px;padding:12px 15px;margin-bottom:16px;display:flex;gap:9px;align-items:center;font-size:13px}
.ci-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:20px}
.ci-eyebrow{font-size:10px;font-weight:800;letter-spacing:1.5px;color:#09a9b7}
.ci-head h1{font-size:27px;margin:3px 0 3px;color:#092b55}
.ci-head p{margin:0;color:#70839a;font-size:12px}
.ci-primary{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#063b78,#09aeb5);color:#fff;text-decoration:none;border:0;border-radius:10px;padding:10px 15px;font:600 12px Poppins;box-shadow:0 7px 18px rgba(7,77,120,.15);transition:.25s}
.ci-primary:hover{transform:translateY(-2px);box-shadow:0 11px 22px rgba(7,77,120,.22)}
.ci-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
.ci-stats>div{background:#fff;border:1px solid #d5e7ee;border-radius:14px;padding:14px;display:flex;align-items:center;gap:12px;transition:.25s}
.ci-stats>div:hover{transform:translateY(-3px);box-shadow:0 10px 22px rgba(16,66,95,.1)}
.ci-stat-icon{width:38px;height:38px;border-radius:11px;background:#e8f3ff;color:#0874c9;display:grid;place-items:center}.ci-stat-icon.green{background:#e8faf6;color:#079b87}.ci-stat-icon.gray{background:#f0f3f6;color:#718096}
.ci-stats small{display:block;color:#7b8da0;font-size:10px}.ci-stats strong{font-size:21px;color:#092b55}
.ci-panel{background:#fff;border:1px solid #d5e7ee;border-radius:15px;overflow:hidden;box-shadow:0 7px 20px rgba(17,57,80,.05)}
.ci-toolbar{padding:15px 17px;border-bottom:1px solid #e7eef2;display:flex;justify-content:space-between;align-items:center;gap:15px}.ci-toolbar h2{font-size:14px;margin:0;color:#12395e}
.ci-search{width:280px;height:37px;border:1px solid #c8dce5;border-radius:9px;display:flex;align-items:center;gap:8px;padding:0 11px}.ci-search i{color:#8ba0b0;font-size:12px}.ci-search input{border:0;outline:0;width:100%;font:12px Poppins;color:#234}
.ci-table-wrap{overflow-x:auto}.ci-table{width:100%;border-collapse:collapse;font-size:11px}.ci-table th{text-align:left;padding:11px 15px;background:#f5f9fb;color:#71869a;font-size:10px;font-weight:700}.ci-table td{padding:12px 15px;border-top:1px solid #edf2f5;color:#50677b}.ci-user{display:flex;align-items:center;gap:10px}.ci-user strong{display:block;color:#12395e;font-size:11px}.ci-user span{display:block;color:#8a9aaa;font-size:9px;margin-top:2px}.ci-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#07509b,#10b9bd);color:#fff;display:grid;place-items:center;font-size:10px;font-weight:700;overflow:hidden}.ci-avatar img{width:100%;height:100%;object-fit:cover}.ci-count{display:inline-grid;place-items:center;min-width:25px;height:24px;border-radius:7px;background:#edf7fb;color:#087c9a}.ci-status{display:inline-block;padding:5px 8px;border-radius:20px;font-size:9px;font-weight:700}.ci-status.active{background:#e7faf3;color:#07836e}.ci-status.inactive{background:#f2f4f6;color:#788899}.ci-actions{display:flex;gap:6px}.ci-actions a{width:29px;height:29px;border:1px solid #d7e5eb;border-radius:8px;color:#477086;display:grid;place-items:center;text-decoration:none;transition:.2s}.ci-actions a:hover{color:#fff;background:#087ca5;border-color:#087ca5;transform:translateY(-2px)}.ci-empty{text-align:center;padding:55px 20px}.ci-empty>i{font-size:30px;color:#8bcbd0}.ci-empty strong{display:block;margin-top:12px;color:#12395e;font-size:14px}.ci-empty p{color:#8193a2;font-size:11px;margin:6px 0 15px}
@media(max-width:800px){.ci-head{align-items:flex-start;flex-direction:column}.ci-stats{grid-template-columns:1fr}.ci-toolbar{align-items:flex-start;flex-direction:column}.ci-search{width:100%}}

</style>
</head>
<body>
<?php include __DIR__ . '/menu_entreprise.php'; ?>
<main class="main-content">
<div class="dashboard">

</div>
</main>

<script src="entreprise.js"></script>
</body>
</html>

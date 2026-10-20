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

$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id){header("Location: collaborateurs.php");exit;}
$stmt=$con->prepare("SELECT ec.*,u.id_user,u.nom_user,u.prenom_user,u.email_user,u.tel_user,u.photo_user,u.statut_user FROM t_entreprise_collaborateurs ec INNER JOIN t_utilisateurs u ON u.id_user=ec.id_user WHERE ec.id_collaborateur=? AND ec.id_entreprise=? LIMIT 1");
$stmt->execute([$id,$idEntreprise]);$c=$stmt->fetch(PDO::FETCH_ASSOC);if(!$c){header("Location: collaborateurs.php");exit;}
$stmt=$con->prepare("SELECT COUNT(DISTINCT id_form) FROM t_inscriptions WHERE id_user=?");$stmt->execute([(int)$c['id_user']]);$nbForm=(int)$stmt->fetchColumn();
$nom=trim(($c['prenom_user']??'').' '.($c['nom_user']??''));$initiales=strtoupper(substr($c['prenom_user']??'',0,1).substr($c['nom_user']??'',0,1));
$photo=!empty($c['photo_user'])?'../'.ltrim(str_replace('\\','/',$c['photo_user']),'/'):'';
$active_page='collaborateurs.php';
?>
<section class="cd-page">
<a class="cd-back" href="collaborateurs.php"><i class="fa-solid fa-arrow-left"></i> Retour aux collaborateurs</a>
<div class="cd-hero"><div class="cd-avatar"><?php if($photo):?><img src="<?=e($photo)?>" alt=""><?php else:?><?=e($initiales?:'CO')?><?php endif;?></div><div class="cd-name"><span>COLLABORATEUR</span><h1><?=e($nom?:'Collaborateur')?></h1><p><?=e($c['email_user']??'')?></p></div><span class="cd-status <?=($c['statut']??'')==='actif'?'active':'inactive'?>"><?=($c['statut']??'')==='actif'?'Actif':'Inactif'?></span></div>
<div class="cd-grid">
<div class="cd-card"><h2><i class="fa-solid fa-user"></i> Informations personnelles</h2><div class="cd-list"><div><small>Nom</small><strong><?=e($c['nom_user']??'—')?></strong></div><div><small>Prénom</small><strong><?=e($c['prenom_user']??'—')?></strong></div><div><small>Email</small><strong><?=e($c['email_user']??'—')?></strong></div><div><small>Téléphone</small><strong><?=e($c['tel_user']?:'Non renseigné')?></strong></div></div></div>
<div class="cd-card"><h2><i class="fa-solid fa-chart-line"></i> Activité</h2><div class="cd-metrics"><div><strong><?= $nbForm ?></strong><span>Formation<?= $nbForm>1?'s':'' ?></span></div><div><strong><?=!empty($c['date_ajout'])?date('d/m/Y',strtotime($c['date_ajout'])):'—'?></strong><span>Date d'ajout</span></div></div></div>
</div>
<div class="cd-actions"><a href="collaborateur-modifier.php?id=<?=$id?>" class="cd-edit"><i class="fa-solid fa-pen"></i> Modifier</a><?php if(($c['statut']??'')==='actif'):?><a href="collaborateur-desactiver.php?id=<?=$id?>" class="cd-disable"><i class="fa-solid fa-user-slash"></i> Désactiver</a><?php else:?><a href="collaborateur-reactiver.php?id=<?=$id?>" class="cd-edit"><i class="fa-solid fa-user-check"></i> Réactiver</a><?php endif;?></div>
</section>

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voir le collaborateur — TOUN</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet" href="entreprise.css">
<style>

.cd-page{max-width:980px;margin:0 auto;padding:8px 0 45px;font-family:Poppins,sans-serif;color:#102f4d}.cd-back{display:inline-flex;gap:7px;color:#587589;text-decoration:none;font-size:11px;margin-bottom:15px}.cd-back:hover{color:#078da2}.cd-hero{background:linear-gradient(120deg,#f8fcfd,#eaf8fa);border:1px solid #b9dfe5;border-radius:17px;padding:22px;display:flex;align-items:center;gap:15px;box-shadow:0 8px 24px rgba(8,60,91,.06)}.cd-avatar{width:82px;height:82px;border-radius:50%;background:linear-gradient(135deg,#063b78,#0bb6b9);color:#fff;display:grid;place-items:center;font-weight:800;font-size:22px;overflow:hidden;flex:none}.cd-avatar img{width:100%;height:100%;object-fit:cover}.cd-name{flex:1}.cd-name span{font-size:9px;letter-spacing:1.5px;color:#08aeb6;font-weight:800}.cd-name h1{font-size:24px;margin:3px 0;color:#092b55}.cd-name p{margin:0;font-size:11px;color:#75899a}.cd-status{font-size:10px;font-weight:700;padding:6px 10px;border-radius:20px}.cd-status.active{background:#e7faf3;color:#07836e}.cd-status.inactive{background:#f0f3f5;color:#788899}.cd-grid{display:grid;grid-template-columns:1.25fr .75fr;gap:14px;margin-top:14px}.cd-card{background:#fff;border:1px solid #c6e0e6;border-radius:15px;padding:17px;box-shadow:0 6px 18px rgba(16,66,95,.05);transition:.25s}.cd-card:hover{transform:translateY(-2px);box-shadow:0 11px 23px rgba(16,66,95,.09)}.cd-card h2{font-size:13px;color:#12395e;margin:0 0 15px;padding-bottom:10px;border-bottom:1px solid #e7f0f3}.cd-card h2 i{color:#08aeb6;margin-right:7px}.cd-list{display:grid;grid-template-columns:1fr 1fr;gap:14px}.cd-list small{display:block;color:#8b9aa8;font-size:9px;margin-bottom:3px}.cd-list strong{font-size:11px;color:#34546c;word-break:break-word}.cd-metrics{display:grid;gap:12px}.cd-metrics div{background:#f5fafb;border:1px solid #dcebef;border-radius:10px;padding:12px}.cd-metrics strong{display:block;color:#087c9a;font-size:19px}.cd-metrics span{font-size:9px;color:#7b8d9c}.cd-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:15px}.cd-actions a{padding:10px 14px;border-radius:9px;text-decoration:none;font:600 11px Poppins;transition:.2s}.cd-edit{background:linear-gradient(135deg,#063b78,#09aeb5);color:#fff}.cd-disable{background:#fff1f1;color:#a64242;border:1px solid #efc4c4}.cd-actions a:hover{transform:translateY(-2px)}@media(max-width:700px){.cd-hero{flex-wrap:wrap}.cd-status{margin-left:97px}.cd-grid{grid-template-columns:1fr}.cd-list{grid-template-columns:1fr}}

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

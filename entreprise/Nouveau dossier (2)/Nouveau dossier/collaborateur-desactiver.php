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

$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){header("Location: collaborateurs.php");exit;}
$stmt=$con->prepare("SELECT ec.id_collaborateur,ec.id_user,ec.statut,u.nom_user,u.prenom_user FROM t_entreprise_collaborateurs ec INNER JOIN t_utilisateurs u ON u.id_user=ec.id_user WHERE ec.id_collaborateur=? AND ec.id_entreprise=? LIMIT 1");
$stmt->execute([$id,$idEntreprise]);$c=$stmt->fetch(PDO::FETCH_ASSOC);if(!$c){header("Location: collaborateurs.php");exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nouveau = 'inactif';
    try{
        $con->beginTransaction();
        $st=$con->prepare("UPDATE t_entreprise_collaborateurs SET statut=? WHERE id_collaborateur=? AND id_entreprise=?");
        $st->execute([$nouveau,$id,$idEntreprise]);
        $st=$con->prepare("UPDATE t_utilisateurs SET statut_user=? WHERE id_user=?");
        $st->execute([$nouveau==='actif'?'actif':'inactif',(int)$c['id_user']]);
        $con->commit();
        header("Location: collaborateurs.php?desactive=ok");exit;
    }catch(Throwable $e){if($con->inTransaction())$con->rollBack();$error="L'opération n'a pas pu être effectuée.";}
}
$active_page='collaborateurs.php';
?>
<section class="ca-page">
<div class="ca-card">
<div class="ca-icon warn"><i class="fa-solid fa-user-slash"></i></div>
<h1>Désactiver le collaborateur</h1>
<p>Voulez-vous vraiment désactiver <strong><?=e(trim(($c['prenom_user']??'').' '.($c['nom_user']??'')))?></strong> ?</p>
<?php if(!empty($error)): ?><p style="color:#b44444"><?=e($error)?></p><?php endif;?>
<form method="post" class="ca-actions"><a class="ca-cancel" href="collaborateurs.php">Annuler</a><button class="ca-confirm" type="submit">Désactiver</button></form>
</div>
</section>

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Désactiver le collaborateur — TOUN</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet" href="entreprise.css">
<style>

.ca-page{max-width:620px;margin:35px auto;font-family:Poppins,sans-serif}.ca-card{background:#fff;border:1px solid #c5e0e6;border-radius:17px;padding:30px;text-align:center;box-shadow:0 10px 28px rgba(9,58,86,.08)}.ca-icon{width:58px;height:58px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;font-size:22px}.ca-icon.warn{background:#fff3f0;color:#d66a49}.ca-icon.ok{background:#e9faf6;color:#078b78}.ca-card h1{font-size:20px;color:#092b55;margin:0 0 7px}.ca-card p{font-size:11px;color:#75899a;line-height:1.7;margin:0 auto 20px;max-width:450px}.ca-actions{display:flex;justify-content:center;gap:9px}.ca-actions a,.ca-actions button{border:0;border-radius:9px;padding:10px 14px;text-decoration:none;font:600 11px Poppins;cursor:pointer}.ca-cancel{background:#eef3f5;color:#557082}.ca-confirm{background:linear-gradient(135deg,#063b78,#09aeb5);color:#fff}

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

<?php
require __DIR__.'/entreprise_init.php';
$flash=getFlash();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $old=$_POST['ancien']??'';$new=$_POST['nouveau']??'';$confirm=$_POST['confirmation']??'';
    $s=$con->prepare("SELECT motpass_user FROM t_utilisateurs WHERE id_user=?");$s->execute([$userId]);$hash=$s->fetchColumn();
    if(!password_verify($old,$hash))flash('err','Ancien mot de passe incorrect.');
    elseif(strlen($new)<6||$new!==$confirm)flash('err','Le nouveau mot de passe est invalide.');
    else{$s=$con->prepare("UPDATE t_utilisateurs SET motpass_user=? WHERE id_user=?");$s->execute([password_hash($new,PASSWORD_DEFAULT),$userId]);flash('ok','Mot de passe modifié.');}
    header('Location: parametres.php');exit;
}
$active_page='parametres.php';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Paramètres — TOUN</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="entreprise-menu.css"><link rel="stylesheet" href="entreprise-ui.css"></head><body><?php include __DIR__.'/menu_entreprise.php';?><main class="main-content"><div class="page"><div class="page-head"><div><span class="eyebrow">COMPTE</span><h1>Paramètres</h1><p>Sécurisez votre compte.</p></div></div><?php if($flash):?><div class="alert <?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endif;?><section class="panel form"><form method="post"><div class="form-grid"><div class="field full"><label>Ancien mot de passe</label><input type="password" name="ancien" required></div><div class="field"><label>Nouveau mot de passe</label><input type="password" name="nouveau" minlength="6" required></div><div class="field"><label>Confirmation</label><input type="password" name="confirmation" minlength="6" required></div></div><div class="form-actions"><button class="btn">Modifier le mot de passe</button></div></form></section></div></main></body></html>
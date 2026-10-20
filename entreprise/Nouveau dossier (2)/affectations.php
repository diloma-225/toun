<?php
require __DIR__.'/entreprise_init.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=(int)($_POST['id_user']??0);$fid=(int)($_POST['id_form']??0);
    $s=$con->prepare("SELECT id_collaborateur FROM t_entreprise_collaborateurs WHERE id_entreprise=? AND id_user=? AND statut='actif'");$s->execute([$idEntreprise,$uid]);
    if($s->fetch()){
        $s=$con->prepare("SELECT id_inscrip FROM t_inscriptions WHERE id_user=? AND id_form=? LIMIT 1");$s->execute([$uid,$fid]);
        if(!$s->fetch()){
            $s=$con->prepare("INSERT INTO t_inscriptions(id_user,id_form,date_inscrip_form,statut_inscrip) VALUES(?,?,NOW(),'active')");$s->execute([$uid,$fid]);flash('ok','Formation affectée au collaborateur.');
        }else flash('err','Cette formation est déjà affectée.');
    }
    header('Location: affectations.php');exit;
}
$users=$con->prepare("SELECT u.id_user,u.nom_user,u.prenom_user FROM t_entreprise_collaborateurs ec INNER JOIN t_utilisateurs u ON u.id_user=ec.id_user WHERE ec.id_entreprise=? AND ec.statut='actif' ORDER BY u.nom_user");$users->execute([$idEntreprise]);$users=$users->fetchAll();
$forms=$con->query("SELECT id_form,titre_form FROM t_formations WHERE statut_form='publie' ORDER BY titre_form")->fetchAll();
$flash=getFlash();$active_page='affectations.php';$selected=(int)($_GET['formation']??0);
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Affectations — TOUN</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="entreprise-menu.css"><link rel="stylesheet" href="entreprise-ui.css"></head><body><?php include __DIR__.'/menu_entreprise.php';?><main class="main-content"><div class="page"><div class="page-head"><div><span class="eyebrow">ÉQUIPE</span><h1>Affectations</h1><p>Affectez une formation à un collaborateur.</p></div></div><?php if($flash):?><div class="alert <?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endif;?><section class="panel form"><form method="post"><div class="form-grid"><div class="field"><label>Collaborateur</label><select name="id_user" required><option value="">Choisir</option><?php foreach($users as $u):?><option value="<?=$u['id_user']?>"><?=e($u['prenom_user'].' '.$u['nom_user'])?></option><?php endforeach;?></select></div><div class="field"><label>Formation</label><select name="id_form" required><option value="">Choisir</option><?php foreach($forms as $f):?><option value="<?=$f['id_form']?>" <?=$selected===$f['id_form']?'selected':''?>><?=e($f['titre_form'])?></option><?php endforeach;?></select></div></div><div class="form-actions"><button class="btn"><i class="fa-solid fa-user-check"></i>Affecter</button></div></form></section></div></main></body></html>
<?php
require __DIR__.'/entreprise_init.php';
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nom=trim($_POST['nom']??'');$prenom=trim($_POST['prenom']??'');$email=trim($_POST['email']??'');$tel=trim($_POST['tel']??'');$pass=$_POST['motpass']??'';$confirm=$_POST['confirmation']??'';
    if(!$nom||!$prenom||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<6||$pass!==$confirm)$errors[]='Vérifiez les informations et le mot de passe.';
    if(!$errors){
        try{
            $con->beginTransaction();
            $s=$con->prepare("SELECT id_user FROM t_utilisateurs WHERE email_user=? LIMIT 1");$s->execute([$email]);
            if($s->fetch())throw new Exception('Cette adresse e-mail est déjà utilisée.');
            $s=$con->prepare("INSERT INTO t_utilisateurs(nom_user,prenom_user,email_user,tel_user,motpass_user,role_user,statut_user,date_inscrip_user) VALUES(?,?,?,?,?,'apprenant','actif',NOW())");
            $s->execute([$nom,$prenom,$email,$tel,password_hash($pass,PASSWORD_DEFAULT)]);
            $uid=(int)$con->lastInsertId();
            $s=$con->prepare("INSERT INTO t_entreprise_collaborateurs(id_entreprise,id_user,statut) VALUES(?,?,'actif')");
            $s->execute([$idEntreprise,$uid]);$con->commit();flash('ok','Collaborateur ajouté avec succès.');header('Location: collaborateurs.php');exit;
        }catch(Throwable $e){if($con->inTransaction())$con->rollBack();$errors[]=$e->getMessage();}
    }
}
$active_page='collaborateurs.php';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ajouter — TOUN</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="entreprise-menu.css"><link rel="stylesheet" href="entreprise-ui.css"></head><body><?php include __DIR__.'/menu_entreprise.php';?><main class="main-content"><div class="page"><div class="page-head"><div><span class="eyebrow">ÉQUIPE</span><h1>Ajouter un collaborateur</h1><p>Créez son compte et rattachez-le à votre entreprise.</p></div></div><?php foreach($errors as $er):?><div class="alert err"><?=e($er)?></div><?php endforeach;?><section class="panel form"><form method="post"><div class="form-grid"><div class="field"><label>Nom</label><input name="nom" required></div><div class="field"><label>Prénom</label><input name="prenom" required></div><div class="field"><label>E-mail</label><input type="email" name="email" required></div><div class="field"><label>Téléphone</label><input name="tel"></div><div class="field"><label>Mot de passe</label><input type="password" name="motpass" minlength="6" required></div><div class="field"><label>Confirmation</label><input type="password" name="confirmation" minlength="6" required></div></div><div class="form-actions"><a class="btn btn-light" href="collaborateurs.php">Annuler</a><button class="btn" type="submit">Créer le collaborateur</button></div></form></section></div></main><script src="entreprise.js"></script></body></html>
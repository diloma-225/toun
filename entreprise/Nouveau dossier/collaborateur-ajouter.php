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

$message = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_user'] ?? '');
    $prenom = trim($_POST['prenom_user'] ?? '');
    $email = trim($_POST['email_user'] ?? '');
    $tel = trim($_POST['tel_user'] ?? '');
    $password = $_POST['motpass_user'] ?? '';
    $confirm = $_POST['confirmation_user'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '' || $password === '') $errors[] = "Veuillez remplir tous les champs obligatoires.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email est invalide.";
    if ($password !== $confirm) $errors[] = "Les mots de passe ne correspondent pas.";
    if (strlen($password) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";

    $photoPath = '';
    if (!empty($_FILES['photo_user']['name'])) {
        if ($_FILES['photo_user']['error'] !== UPLOAD_ERR_OK) $errors[] = "Impossible de charger la photo.";
        elseif ($_FILES['photo_user']['size'] > 2*1024*1024) $errors[] = "La photo ne doit pas dépasser 2 Mo.";
        else {
            $ext = strtolower(pathinfo($_FILES['photo_user']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) $errors[] = "Format photo accepté : JPG, PNG ou WEBP.";
            else {
                $dir = __DIR__ . '/../uploads/profils/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $file = 'collab_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($_FILES['photo_user']['tmp_name'], $dir.$file)) $photoPath = 'uploads/profils/'.$file;
                else $errors[] = "La photo n'a pas pu être enregistrée.";
            }
        }
    }

    if (!$errors) {
        try {
            $check = $con->prepare("SELECT id_user FROM t_utilisateurs WHERE email_user = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) throw new RuntimeException("Cette adresse email est déjà utilisée.");

            $con->beginTransaction();
            $stmt = $con->prepare("INSERT INTO t_utilisateurs (nom_user, prenom_user, email_user, tel_user, photo_user, motpass_user, role_user, statut_user, date_inscrip_user) VALUES (?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$nom,$prenom,$email,$tel,$photoPath,password_hash($password,PASSWORD_DEFAULT),'apprenant','actif']);
            $newUser = (int)$con->lastInsertId();

            $stmt = $con->prepare("INSERT INTO t_entreprise_collaborateurs (id_entreprise,id_user,statut) VALUES (?,?, 'actif')");
            $stmt->execute([$idEntreprise,$newUser]);
            $con->commit();
            header("Location: collaborateurs.php?ajout=ok");
            exit;
        } catch (Throwable $e) {
            if ($con->inTransaction()) $con->rollBack();
            if ($photoPath && is_file(__DIR__.'/../'.$photoPath)) @unlink(__DIR__.'/../'.$photoPath);
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : "Impossible d'ajouter le collaborateur.";
        }
    }
}
$active_page='collaborateurs.php';
?>
<section class="cf-page">
<a class="cf-back" href="collaborateurs.php"><i class="fa-solid fa-arrow-left"></i> Retour aux collaborateurs</a>
<div class="cf-head"><div><span>ÉQUIPE</span><h1>Ajouter un collaborateur</h1><p>Créez le compte et ajoutez-le à votre entreprise.</p></div></div>
<?php if ($errors): ?><div class="cf-error"><?php foreach($errors as $er): ?><div><i class="fa-solid fa-circle-exclamation"></i><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="cf-panel">
<div class="cf-photo"><div class="cf-preview" id="photoPreview"><i class="fa-solid fa-user"></i></div><div><label class="cf-upload" for="photo_user"><i class="fa-solid fa-camera"></i> Choisir une photo</label><input id="photo_user" type="file" name="photo_user" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WEBP · 2 Mo maximum</small></div></div>
<div class="cf-grid">
<label>Nom *<input name="nom_user" value="<?= e($_POST['nom_user']??'') ?>" required></label>
<label>Prénom *<input name="prenom_user" value="<?= e($_POST['prenom_user']??'') ?>" required></label>
<label>Email *<input type="email" name="email_user" value="<?= e($_POST['email_user']??'') ?>" required></label>
<label>Téléphone<input name="tel_user" value="<?= e($_POST['tel_user']??'') ?>"></label>
<label>Mot de passe *<input type="password" name="motpass_user" required></label>
<label>Confirmation *<input type="password" name="confirmation_user" required></label>
</div>
<div class="cf-actions"><a href="collaborateurs.php">Annuler</a><button type="submit"><i class="fa-solid fa-user-plus"></i> Ajouter le collaborateur</button></div>
</form></section>

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter un collaborateur — TOUN</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet" href="entreprise.css">
<style>

.cf-page{max-width:850px;margin:0 auto;padding:8px 0 45px;font-family:Poppins,sans-serif}.cf-back{display:inline-flex;gap:7px;color:#50728a;text-decoration:none;font-size:11px;margin-bottom:15px}.cf-back:hover{color:#078da2}.cf-head{margin-bottom:16px}.cf-head span{font-size:10px;font-weight:800;letter-spacing:1.5px;color:#08aeb6}.cf-head h1{font-size:25px;color:#092b55;margin:3px 0}.cf-head p{font-size:11px;color:#7b8da0;margin:0}.cf-panel{background:#fff;border:1px solid #b9dfe5;border-radius:15px;padding:20px;box-shadow:0 8px 24px rgba(8,60,91,.07)}.cf-photo{display:flex;align-items:center;gap:14px;padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid #e3eef1}.cf-preview{width:78px;height:78px;border-radius:50%;background:linear-gradient(135deg,#07509b,#10b9bd);display:grid;place-items:center;color:#fff;font-size:23px;overflow:hidden}.cf-preview img{width:100%;height:100%;object-fit:cover}.cf-upload{display:inline-flex;gap:8px;align-items:center;background:#edf9fa;color:#087f8c;border:1px solid #a8dfe2;padding:9px 12px;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer}.cf-photo input[type=file]{display:none}.cf-photo small{display:block;color:#8a9aa8;font-size:9px;margin-top:7px}.cf-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:13px}.cf-grid label{font-size:10px;font-weight:600;color:#456177}.cf-grid input{display:block;width:100%;box-sizing:border-box;margin-top:6px;border:1px solid #c7dce4;border-radius:8px;height:39px;padding:0 11px;outline:none;font:11px Poppins;color:#173b58}.cf-grid input:focus{border-color:#12aeb5;box-shadow:0 0 0 3px rgba(18,174,181,.08)}.cf-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:20px}.cf-actions a,.cf-actions button{border:0;border-radius:9px;padding:10px 14px;font:600 11px Poppins;text-decoration:none;cursor:pointer}.cf-actions a{background:#f0f4f6;color:#587083}.cf-actions button{background:linear-gradient(135deg,#063b78,#09aeb5);color:#fff}.cf-error{background:#fff1f1;border:1px solid #f0b8b8;color:#a33a3a;border-radius:10px;padding:10px 13px;margin-bottom:13px;font-size:10px}.cf-error div+div{margin-top:5px}@media(max-width:650px){.cf-grid{grid-template-columns:1fr}.cf-panel{padding:15px}}

</style>
</head>
<body>
<?php include __DIR__ . '/menu_entreprise.php'; ?>
<main class="main-content">
<div class="dashboard">

</div>
</main>
<script>
const input=document.getElementById('photo_user'),preview=document.getElementById('photoPreview');
if(input) input.addEventListener('change',function(){const f=this.files[0];if(!f)return;if(f.size>2097152){alert('La photo ne doit pas dépasser 2 Mo.');this.value='';return}const r=new FileReader();r.onload=e=>preview.innerHTML='<img src="'+e.target.result+'" alt="">';r.readAsDataURL(f);});
</script>
<script src="entreprise.js"></script>
</body>
</html>

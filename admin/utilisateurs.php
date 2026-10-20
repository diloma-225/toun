<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_role(['admin'], 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/functions.php';

$pdo = toun_db();
$moi = current_user();

if (empty($_SESSION['csrf_admin_user'])) {
    $_SESSION['csrf_admin_user'] = bin2hex(random_bytes(32));
}

$erreurs = [];
$valeurs = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'tel' => '',
    'role' => 'apprenant',
    'statut' => 'actif',
    'id_entreprise' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_admin_user'], $_POST['csrf'] ?? '')) {
        $erreurs[] = 'Session expirée, merci de réessayer.';
    } else {
        $valeurs['nom'] = trim((string) ($_POST['nom'] ?? ''));
        $valeurs['prenom'] = trim((string) ($_POST['prenom'] ?? ''));
        $valeurs['email'] = trim((string) ($_POST['email'] ?? ''));
        $valeurs['tel'] = trim((string) ($_POST['tel'] ?? ''));
        $valeurs['role'] = (string) ($_POST['role'] ?? '');
        $valeurs['statut'] = (string) ($_POST['statut'] ?? 'actif');
        $valeurs['id_entreprise'] = (string) ($_POST['id_entreprise'] ?? '');
        $motpasse = (string) ($_POST['motpasse'] ?? '');
        $motpasseConfirm = (string) ($_POST['motpasse_confirm'] ?? '');

        if ($valeurs['nom'] === '' || $valeurs['prenom'] === '') {
            $erreurs[] = 'Le nom et le prénom sont obligatoires.';
        }
        if (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'adresse email n'est pas valide.";
        }
        if (!array_key_exists($valeurs['role'], ROLES_LABELS)) {
            $erreurs[] = 'Rôle invalide.';
        }
        if (!array_key_exists($valeurs['statut'], STATUTS_LABELS)) {
            $erreurs[] = 'Statut invalide.';
        }
        if (strlen($motpasse) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($motpasse !== $motpasseConfirm) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }

        if (!$erreurs) {
            try {
                create_user($pdo, [
                    'nom' => $valeurs['nom'],
                    'prenom' => $valeurs['prenom'],
                    'email' => $valeurs['email'],
                    'tel' => $valeurs['tel'],
                    'motpasse' => $motpasse,
                    'role' => $valeurs['role'],
                    'statut' => $valeurs['statut'],
                    'id_entreprise' => $valeurs['id_entreprise']
                ]);

                unset($_SESSION['csrf_admin_user']);
                header('Location: utilisateurs.php?cree=1');
                exit();
            } catch (RuntimeException $e) {
                $erreurs[] = $e->getMessage();
            }
        }
    }
}

$entreprises = get_entreprises_list($pdo);
$utilisateurs = get_all_users($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Gestion des utilisateurs — Admin TOUN</title>
        <link rel="stylesheet" href="../assets/css/formations.css">
        <link rel="stylesheet" href="../assets/css/admin.css">
    </head>
    <body class="page-admin">

        <header class="admin-topbar">
            <div class="admin-topbar-inner">
                <img src="../assets/img/logo-toun.png" alt="TOUN" class="admin-logo">
                <nav class="admin-nav">
                    <a href="utilisateurs.php" class="active">Utilisateurs</a>
                </nav>
                <div class="admin-user">
                    <span><?= h($moi['prenom_user']) ?> · <?= h(ROLES_LABELS[$moi['role_user']]) ?></span>
                    <a href="deconnexion.php" class="btn-ghost">Déconnexion</a>
                </div>
            </div>
        </header>

        <main class="admin-main">

            <div class="admin-header-row">
                <div>
                    <h1>Gestion des utilisateurs</h1>
                    <p class="admin-sub">Crée les comptes admin, formateur, gestionnaire d'entreprise et apprenant.</p>
                </div>
            </div>

            <?php if (isset($_GET['cree'])): ?>
                <div class="alerte-succes">Utilisateur créé avec succès.</div>
            <?php endif; ?>

            <?php if ($erreurs): ?>
                <div class="alerte-erreur">
                    <ul>
                        <?php foreach ($erreurs as $e): ?>
                            <li><?= h($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="admin-card">
                <h2>Ajouter un utilisateur</h2>
                <form method="post" action="utilisateurs.php" class="form-grid" novalidate>
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_admin_user']) ?>">

                    <div class="champ">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" value="<?= h($valeurs['nom']) ?>" required>
                    </div>

                    <div class="champ">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" value="<?= h($valeurs['prenom']) ?>" required>
                    </div>

                    <div class="champ">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= h($valeurs['email']) ?>" required>
                    </div>

                    <div class="champ">
                        <label for="tel">Téléphone</label>
                        <input type="text" id="tel" name="tel" value="<?= h($valeurs['tel']) ?>" maxlength="10">
                    </div>

                    <div class="champ">
                        <label for="role">Rôle</label>
                        <select id="role" name="role" required>
                            <?php foreach (ROLES_LABELS as $val => $label): ?>
                                <option value="<?= h($val) ?>" <?= $valeurs['role'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="champ">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut" required>
                            <?php foreach (STATUTS_LABELS as $val => $label): ?>
                                <option value="<?= h($val) ?>" <?= $valeurs['statut'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="champ">
                        <label for="id_entreprise">Entreprise (optionnel)</label>
                        <select id="id_entreprise" name="id_entreprise">
                            <option value="">— Aucune (apprenant grand public) —</option>
                            <?php foreach ($entreprises as $ent): ?>
                                <option value="<?= (int) $ent['id_entreprise'] ?>" <?= $valeurs['id_entreprise'] === (string) $ent['id_entreprise'] ? 'selected' : '' ?>>
                                    <?= h($ent['nom_entreprise']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="champ">
                        <label for="motpasse">Mot de passe</label>
                        <input type="password" id="motpasse" name="motpasse" required minlength="8">
                    </div>

                    <div class="champ">
                        <label for="motpasse_confirm">Confirmer le mot de passe</label>
                        <input type="password" id="motpasse_confirm" name="motpasse_confirm" required minlength="8">
                    </div>

                    <div class="champ champ-full">
                        <button type="submit" class="btn-primary">Créer l'utilisateur</button>
                    </div>
                </form>
            </section>

            <section class="admin-card">
                <h2>Utilisateurs existants (<?= count($utilisateurs) ?>)</h2>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Entreprise</th>
                                <th>Statut</th>
                                <th>Inscrit le</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$utilisateurs): ?>
                                <tr>
                                    <td colspan="7" class="table-vide">Aucun utilisateur pour l'instant.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($utilisateurs as $u): ?>
                                <tr>
                                    <td><?= h($u['prenom_user'] . ' ' . $u['nom_user']) ?></td>
                                    <td><?= h($u['email_user']) ?></td>
                                    <td><?= h($u['tel_user'] ?? '—') ?></td>
                                    <td><span class="badge badge-role-<?= h($u['role_user']) ?>"><?= h(ROLES_LABELS[$u['role_user']] ?? $u['role_user']) ?></span></td>
                                    <td><?= h($u['nom_entreprise'] ?? '—') ?></td>
                                    <td><span class="badge badge-statut-<?= h($u['statut_user']) ?>"><?= h(STATUTS_LABELS[$u['statut_user']] ?? $u['statut_user']) ?></span></td>
                                    <td><?= h((new DateTime($u['date_inscrip_user']))->format('d/m/Y')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>

    </body>
</html>

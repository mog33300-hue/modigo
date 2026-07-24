<?php
require 'auth.php';
require 'config.php';

$societe_id = intval($_SESSION['societe_id'] ?? 0);
$role_session = $_SESSION['role'] ?? '';

if ($societe_id <= 0) {
    die("Société invalide");
}

if (!in_array($role_session, ['admin','superadmin'])) {
    die("Accès refusé");
}

/* PLAN SOCIETE */
$stmt = $pdo->prepare("SELECT plan FROM societes WHERE id=? LIMIT 1");
$stmt->execute([$societe_id]);
$societe = $stmt->fetch(PDO::FETCH_ASSOC);

$plan = strtolower($societe['plan'] ?? 'basic');

$limite_users = 3;
if ($plan === 'pro') $limite_users = 10;
if ($plan === 'premium') $limite_users = 999999;

$message = '';
$erreur = '';

/* SUPPRESSION */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    if ($id > 0 && $id != intval($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id=?
            AND societe_id=?
            AND role IN ('admin','gerant','controleur')
        ");
        $stmt->execute([$id, $societe_id]);

        header("Location: utilisateurs.php");
        exit;
    }
}

/* AJOUT UTILISATEUR */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $roles_autorises = ['admin','gerant','controleur'];

    if (empty($prenom) || empty($email) || empty($role) || empty($password)) {
        $erreur = "Veuillez remplir les champs obligatoires.";
    } elseif (!in_array($role, $roles_autorises)) {
        $erreur = "Rôle invalide.";
    } else {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE societe_id=?
            AND role IN ('admin','gerant','controleur')
        ");
        $stmt->execute([$societe_id]);
        $nb_users = intval($stmt->fetchColumn());

        if ($nb_users >= $limite_users) {
            $erreur = "⚠ Limite d'utilisateurs atteinte pour votre abonnement. Passez à l'offre supérieure.";
        } else {

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erreur = "Cet email existe déjà.";
            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users
                    (
                        email,
                        password,
                        role,
                        prenom,
                        telephone,
                        token,
                        vehicule,
                        societe_id,
                        created_at
                    )
                    VALUES
                    (
                        ?,?,?,?,?,NULL,NULL,?,NOW()
                    )
                ");

                $stmt->execute([
                    $email,
                    $hash,
                    $role,
                    $prenom,
                    $telephone,
                    $societe_id
                ]);

                $message = "Utilisateur créé avec succès.";
            }
        }
    }
}

/* LISTE UTILISATEURS HORS CHAUFFEURS */
$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE societe_id=?
    AND role IN ('admin','gerant','controleur')
    ORDER BY role ASC, prenom ASC
");
$stmt->execute([$societe_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($users);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Utilisateurs - Medigo</title>

<link rel="stylesheet" href="style.css">

<style>
.role-badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:bold;
}

.role-admin{background:#dbeafe;color:#1d4ed8;}
.role-gerant{background:#dcfce7;color:#166534;}
.role-controleur{background:#fef3c7;color:#92400e;}

.alert-success{
    background:#dcfce7;
    color:#166534;
    padding:14px;
    border-radius:12px;
    margin-bottom:20px;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    padding:14px;
    border-radius:12px;
    margin-bottom:20px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:15px;
}

.btn-delete{
    background:#fee2e2;
    color:#dc2626;
    padding:9px 13px;
    border-radius:10px;
    font-size:13px;
    text-decoration:none;
    display:inline-block;
    margin-left:6px;
}

.btn-edit{
    background:#dbeafe;
    color:#1d4ed8;
    padding:9px 13px;
    border-radius:10px;
    font-size:13px;
    text-decoration:none;
    display:inline-block;
}

.info-abonnement{
    background:white;
    border-radius:14px;
    padding:18px;
    margin-bottom:20px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}
</style>
</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<h1>👥 Utilisateurs</h1>

<<div style="
display:flex;
justify-content:flex-end;
margin-bottom:20px;
">

<a href="dashboard.php"
style="
background:#111827;
color:white;
padding:12px 18px;
border-radius:10px;
text-decoration:none;
font-weight:600;
display:inline-block;
">
🏠 Retour accueil
</a>

</div>

<div class="info-abonnement">

<strong>Plan actuel :</strong>
<?= htmlspecialchars(strtoupper($plan)) ?>

<br>

<strong>Utilisateurs administratifs :</strong>
<?= intval($total_users) ?>
/
<?= $limite_users >= 999999 ? 'Illimité' : intval($limite_users) ?>

<br>

<small>
Les chauffeurs se gèrent uniquement dans le menu 🚘 Chauffeurs.
</small>

<br>

<?php if($limite_users < 999999 && $total_users >= $limite_users): ?>
<span style="color:#dc2626;font-weight:bold;">
⚠ Limite atteinte. Passez à l'offre supérieure pour ajouter plus d'utilisateurs.
</span>
<?php endif; ?>

</div>

<div class="card">

<h2>➕ Ajouter un utilisateur</h2>

<?php if($message): ?>
<div class="alert-success">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if($erreur): ?>
<div class="alert-error">
<?= htmlspecialchars($erreur) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-grid">

<div>
<label>Prénom *</label>
<input type="text" name="prenom" required>
</div>

<div>
<label>Email *</label>
<input type="email" name="email" required>
</div>

<div>
<label>Téléphone</label>
<input type="text" name="telephone">
</div>

<div>
<label>Rôle *</label>
<select name="role" required>
<option value="">Choisir un rôle</option>
<option value="admin">👑 Admin</option>
<option value="gerant">📋 Gérant</option>
<option value="controleur">🔍 Contrôleur</option>
</select>
</div>

<div>
<label>Mot de passe *</label>
<input type="password" name="password" required>
</div>

</div>

<br>

<button type="submit" class="btn btn-add">
💾 Créer utilisateur
</button>

</form>

</div>

<div class="card">

<h2>📋 Liste des utilisateurs</h2>

<div class="table-scroll">

<table class="table-pro">

<tr>
<th>ID</th>
<th>Prénom</th>
<th>Email</th>
<th>Téléphone</th>
<th>Rôle</th>
<th>Créé le</th>
<th>Actions</th>
</tr>

<?php if(empty($users)): ?>

<tr>
<td colspan="7">Aucun utilisateur administratif</td>
</tr>

<?php endif; ?>

<?php foreach($users as $u): ?>

<?php
$role = $u['role'] ?? '';
$role_label = ucfirst($role);

if ($role === 'admin') $role_label = '👑 Admin';
if ($role === 'gerant') $role_label = '📋 Gérant';
if ($role === 'controleur') $role_label = '🔍 Contrôleur';
?>

<tr>

<td><?= intval($u['id']) ?></td>

<td><?= htmlspecialchars($u['prenom'] ?? '') ?></td>

<td><?= htmlspecialchars($u['email'] ?? '') ?></td>

<td><?= htmlspecialchars($u['telephone'] ?? '') ?></td>

<td>
<span class="role-badge role-<?= htmlspecialchars($role) ?>">
<?= htmlspecialchars($role_label) ?>
</span>
</td>

<td>
<?= !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '-' ?>
</td>

<td>

<a
href="edit_utilisateur.php?id=<?= intval($u['id']) ?>"
class="btn-edit"
>
✏ Modifier
</a>

<?php if(intval($u['id']) !== intval($_SESSION['user_id'])): ?>

<a
href="utilisateurs.php?delete=<?= intval($u['id']) ?>"
class="btn-delete"
onclick="return confirm('Supprimer cet utilisateur ?');"
>
🗑 Supprimer
</a>

<?php else: ?>

<br>
<span style="color:#6b7280;font-size:13px;">
Compte actuel
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</div>

</body>
</html>
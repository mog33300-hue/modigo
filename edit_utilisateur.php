<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);
$role_session = $_SESSION['role'] ?? '';

if ($societe_id <= 0) {
    die("Société invalide");
}

if (!in_array($role_session, ['admin','superadmin'])) {
    die("Accès refusé");
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Utilisateur invalide");
}

/* UTILISATEUR */
$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE id=?
AND societe_id=?
LIMIT 1
");
$stmt->execute([$id, $societe_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur introuvable");
}

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $vehicule = trim($_POST['vehicule'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $roles_autorises = [
        'admin',
        'gerant',
        'controleur',
        'chauffeur'
    ];

    if (
        empty($prenom) ||
        empty($email) ||
        empty($role)
    ) {
        $erreur = "Veuillez remplir les champs obligatoires.";

    } elseif (!in_array($role, $roles_autorises)) {

        $erreur = "Rôle invalide.";

    } else {

        /* EMAIL EXISTANT */
        $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email=?
        AND id<>?
        LIMIT 1
        ");
        $stmt->execute([$email, $id]);

        if ($stmt->fetch()) {

            $erreur = "Cet email est déjà utilisé.";

        } else {

            $token = $user['token'] ?? null;

            if ($role === 'chauffeur' && empty($token)) {
                $token = bin2hex(random_bytes(16));
            }

            if ($role !== 'chauffeur') {
                $token = null;
                $vehicule = '';
            }

            if (!empty($password)) {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                UPDATE users
                SET
                    prenom=?,
                    email=?,
                    telephone=?,
                    role=?,
                    vehicule=?,
                    token=?,
                    password=?
                WHERE id=?
                AND societe_id=?
                ");

                $stmt->execute([
                    $prenom,
                    $email,
                    $telephone,
                    $role,
                    $vehicule,
                    $token,
                    $hash,
                    $id,
                    $societe_id
                ]);

            } else {

                $stmt = $pdo->prepare("
                UPDATE users
                SET
                    prenom=?,
                    email=?,
                    telephone=?,
                    role=?,
                    vehicule=?,
                    token=?
                WHERE id=?
                AND societe_id=?
                ");

                $stmt->execute([
                    $prenom,
                    $email,
                    $telephone,
                    $role,
                    $vehicule,
                    $token,
                    $id,
                    $societe_id
                ]);
            }

            header("Location: utilisateurs.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Modifier utilisateur - Medigo</title>

<link rel="stylesheet" href="style.css">

<style>
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

.info-box{
    background:#f9fafb;
    border:1px solid #e5e7eb;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
}
</style>
</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<a href="utilisateurs.php" class="btn btn-back">
⬅ Retour utilisateurs
</a>

<br><br>

<h1>✏ Modifier utilisateur</h1>

<div class="card">

<div class="info-box">
<strong>ID :</strong> <?= intval($user['id']) ?><br>
<strong>Créé le :</strong>
<?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' ?>
</div>

<?php if($erreur): ?>
<div class="alert-error">
<?= htmlspecialchars($erreur) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-grid">

<div>
<label>Prénom *</label>
<input
type="text"
name="prenom"
value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
required
>
</div>

<div>
<label>Email *</label>
<input
type="email"
name="email"
value="<?= htmlspecialchars($user['email'] ?? '') ?>"
required
>
</div>

<div>
<label>Téléphone</label>
<input
type="text"
name="telephone"
value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
>
</div>

<div>
<label>Rôle *</label>
<select name="role" required>
<option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>👑 Admin</option>
<option value="gerant" <?= ($user['role'] ?? '') === 'gerant' ? 'selected' : '' ?>>📋 Gérant</option>
<option value="controleur" <?= ($user['role'] ?? '') === 'controleur' ? 'selected' : '' ?>>🔍 Contrôleur</option>
<option value="chauffeur" <?= ($user['role'] ?? '') === 'chauffeur' ? 'selected' : '' ?>>🚘 Chauffeur</option>
</select>
</div>

<div>
<label>Véhicule</label>
<input
type="text"
name="vehicule"
value="<?= htmlspecialchars($user['vehicule'] ?? '') ?>"
>
</div>

<div>
<label>Nouveau mot de passe</label>
<input
type="password"
name="password"
placeholder="Laisser vide pour ne pas changer"
>
</div>

</div>

<br>

<button type="submit" class="btn btn-add">
💾 Enregistrer les modifications
</button>

</form>

</div>

</div>

</body>
</html>
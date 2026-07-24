<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 1);

/* PLAN SOCIETE */
$stmt = $pdo->prepare("
SELECT plan
FROM societes
WHERE id=?
LIMIT 1
");
$stmt->execute([$societe_id]);
$societe = $stmt->fetch(PDO::FETCH_ASSOC);

$plan = strtolower($societe['plan'] ?? 'basic');

$limite_chauffeurs = 5;

if ($plan === 'pro') {
    $limite_chauffeurs = 15;
}

if ($plan === 'premium') {
    $limite_chauffeurs = 999999;
}

$success = "";
$error = "";

/* AJOUT CHAUFFEUR */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $vehicule = trim($_POST['vehicule'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($prenom) || empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {

        $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE role='chauffeur'
        AND societe_id=?
        ");
        $stmt->execute([$societe_id]);
        $nb_chauffeurs = intval($stmt->fetchColumn());

        if ($nb_chauffeurs >= $limite_chauffeurs) {

            $error = "⚠ Limite de chauffeurs atteinte pour votre abonnement. Passez à l'offre supérieure.";

        } else {

            $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email=?
            LIMIT 1
            ");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {

                $error = "Cet email existe déjà.";

            } else {

                $token = bin2hex(random_bytes(16));
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                INSERT INTO users (
                    prenom,
                    email,
                    telephone,
                    vehicule,
                    password,
                    token,
                    role,
                    societe_id
                ) VALUES (
                    ?,?,?,?,?,?,?,?
                )
                ");

                $stmt->execute([
                    $prenom,
                    $email,
                    $telephone,
                    $vehicule,
                    $hash,
                    $token,
                    'chauffeur',
                    $societe_id
                ]);

                $success = "Chauffeur ajouté avec succès.";
            }
        }
    }
}

/* SUPPRESSION */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $pdo->prepare("
    DELETE FROM users
    WHERE id=?
    AND role='chauffeur'
    AND societe_id=?
    ");
    $stmt->execute([$id, $societe_id]);

    header("Location: chauffeurs.php");
    exit;
}

/* CHAUFFEURS */
$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE role='chauffeur'
AND societe_id=?
ORDER BY prenom ASC
");
$stmt->execute([$societe_id]);
$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($chauffeurs);
?>

<?php
$page_title = 'MODIGO - Chauffeurs';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';
?>

<div class="topbar">
    <div>
        <h1>Chauffeurs</h1>
        <p>Gestion des comptes chauffeurs, accès mobile et affectations MODIGO</p>
    </div>

    <div class="topbar-actions">
        <a href="dashboard_modigo_final.php" class="btn btn-glass">🏠 Dashboard</a>
        <a href="chauffeur_courses.php" class="btn btn-white">📱 Espace chauffeur</a>
    </div>
</div>

<section class="hero">
    <div class="badge-title">Module chauffeurs</div>
    <h2>Gestion des chauffeurs</h2>
    <p>
        Créez les chauffeurs, associez un compte de connexion et préparez l'utilisation mobile :
        appels, missions, GPS, incidents et historique.
    </p>
</section>

<?php if(isset($error) && !empty($error)): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if(isset($success) && !empty($success)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <small>🚘 Chauffeurs</small>
        <h2><?= isset($chauffeurs) && is_array($chauffeurs) ? count($chauffeurs) : 0 ?></h2>
    </div>

    <div class="stat-card">
        <small>📱 Accès mobile</small>
        <h2>ON</h2>
    </div>

    <div class="stat-card">
        <small>🚑 Plan</small>
        <h2><?= htmlspecialchars(strtoupper($plan ?? '')) ?></h2>
    </div>
</div>

<div class="panel">
<h2 style="margin-bottom:18px;">➕ Ajouter un chauffeur</h2>

<form method="POST">

<div class="form-grid">

<div>
<label>Prénom</label>
<input type="text" name="prenom" placeholder="Prénom" required>
</div>

<div>
<label>Nom</label>
<input type="text" name="nom" placeholder="Nom" required>
</div>

<div>
<label>Téléphone</label>
<input type="text" name="telephone" placeholder="06..." required>
</div>

<div>
<label>Email de connexion</label>
<input type="email" name="email" placeholder="chauffeur@societe.fr" required>
</div>

<div>
<label>Mot de passe</label>
<input type="password" name="password" placeholder="Mot de passe" required>
</div>

<div style="display:flex;align-items:end;">
<button type="submit" class="btn btn-white" style="width:100%;">
💾 Créer chauffeur
</button>
</div>

</div>

</form>
</div>

<?php if(!isset($chauffeurs) || empty($chauffeurs)): ?>

<div class="empty">
Aucun chauffeur enregistré
</div>

<?php else: ?>

<div class="driver-grid">

<?php foreach($chauffeurs as $chauffeur): ?>

<?php
$nom_complet = trim(($chauffeur['prenom'] ?? '') . ' ' . ($chauffeur['nom'] ?? ''));
$telephone = trim($chauffeur['telephone'] ?? '');
$email = trim($chauffeur['email'] ?? '');
?>

<div class="driver-card">

<div class="driver-head">

<div class="driver-name">
<div class="avatar">🚘</div>
<div>
<strong><?= htmlspecialchars($nom_complet ?: 'Chauffeur') ?></strong>
<small>ID chauffeur #<?= intval($chauffeur['id'] ?? 0) ?></small>
</div>
</div>

<span class="status">Disponible</span>

</div>

<div class="info">

<div>
<span>📞</span>
<div><?= !empty($telephone) ? htmlspecialchars($telephone) : 'Téléphone non renseigné' ?></div>
</div>

<div>
<span>✉️</span>
<div><?= !empty($email) ? htmlspecialchars($email) : 'Email non renseigné' ?></div>
</div>

<div>
<span>📱</span>
<div>Accès chauffeur mobile disponible</div>
</div>

</div>

<div class="card-actions">

<?php if(!empty($telephone)): ?>
<a href="tel:<?= htmlspecialchars($telephone) ?>" class="btn btn-call">
📞 Appeler
</a>
<?php endif; ?>

<a href="chauffeur_courses.php" class="btn btn-warning">
📱 Espace
</a>

<?php if(isset($chauffeur['id'])): ?>
<a href="edit_chauffeur.php?id=<?= intval($chauffeur['id']) ?>" class="btn btn-edit">
✏ Modifier
</a>
<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

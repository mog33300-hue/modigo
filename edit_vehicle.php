<?php
require 'auth.php';
require 'config.php';

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Véhicule invalide");
}

$message = '';
$error = '';

/* VEHICULE */
$stmt = $pdo->prepare("
    SELECT *
    FROM vehicles
    WHERE id=?
    AND company_id=?
    LIMIT 1
");
$stmt->execute([$id, $societe_id]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
    die("Véhicule introuvable");
}

/* MODIFICATION */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $plate = trim($_POST['plate'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? 'standard');

    if (empty($plate)) {
        $error = "L'immatriculation est obligatoire.";
    } else {

        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET plate=?,
                name=?,
                type=?
            WHERE id=?
            AND company_id=?
        ");

        $stmt->execute([
            $plate,
            $name,
            $type,
            $id,
            $societe_id
        ]);

        header("Location: vehicles.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier véhicule - Medigo</title>
<link rel="stylesheet" href="style.css">

<style>
.alert-error{
    background:#fee2e2;
    color:#991b1b;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}
</style>
</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
<a href="vehicles.php" style="background:#111827;color:white;padding:12px 18px;border-radius:10px;text-decoration:none;font-weight:600;">
⬅ Retour véhicules
</a>
</div>

<h1>✏ Modifier véhicule</h1>

<div class="card">

<?php if($error): ?>
<div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">

<div class="form-grid">

<div>
<label>Immatriculation *</label>
<input type="text" name="plate" value="<?= htmlspecialchars($vehicle['plate'] ?? '') ?>" required>
</div>

<div>
<label>Nom du véhicule</label>
<input type="text" name="name" value="<?= htmlspecialchars($vehicle['name'] ?? '') ?>">
</div>

<div>
<label>Type</label>
<select name="type">

<?php
$types = ['standard', 'VSL', 'Taxi', 'Ambulance', 'TPMR'];
$current_type = $vehicle['type'] ?? 'standard';
?>

<?php foreach($types as $t): ?>
<option value="<?= htmlspecialchars($t) ?>" <?= $current_type === $t ? 'selected' : '' ?>>
<?= htmlspecialchars($t) ?>
</option>
<?php endforeach; ?>

</select>
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
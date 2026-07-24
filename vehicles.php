<?php
require 'auth.php';
require 'config.php';

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

$message = '';
$error = '';

/* AJOUT VEHICULE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $plate = trim($_POST['plate'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? 'standard');

    if (empty($plate)) {
        $error = "L'immatriculation est obligatoire.";
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (company_id, name, plate, type)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $societe_id,
            $name,
            $plate,
            $type
        ]);

        $message = "Véhicule ajouté avec succès.";
    }
}

/* SUPPRESSION */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $pdo->prepare("
        DELETE FROM vehicles
        WHERE id=?
        AND company_id=?
    ");

    $stmt->execute([$id, $societe_id]);

    header("Location: vehicles.php");
    exit;
}

/* LISTE */
$stmt = $pdo->prepare("
    SELECT *
    FROM vehicles
    WHERE company_id=?
    ORDER BY plate ASC
");
$stmt->execute([$societe_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($vehicles);
?>

<?php
$page_title = 'MODIGO - Véhicules';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';
?>

<div class="topbar">
    <div>
        <h1>Véhicules</h1>
        <p>Gestion du parc véhicules, ambulances, VSL et véhicules de transport</p>
    </div>

    <div class="topbar-actions">
        <a href="dashboard_modigo_final.php" class="btn btn-glass">🏠 Dashboard</a>
        <a href="gps_admin.php" class="btn btn-white">📍 Régulation GPS</a>
    </div>
</div>

<section class="hero">
    <div class="badge-title">Module véhicules</div>
    <h2>Parc véhicules MODIGO</h2>
    <p>
        Gérez vos véhicules, plaques, noms et types. Cette base servira ensuite
        au suivi GPS, aux affectations chauffeurs et à la maintenance.
    </p>
</section>

<?php if(isset($error) && !empty($error)): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if(isset($success) && !empty($success)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php
$totalVehicles = isset($vehicles) && is_array($vehicles) ? count($vehicles) : 0;
$ambulances = 0;
$vsl = 0;

if(isset($vehicles) && is_array($vehicles)){
    foreach($vehicles as $v){
        $type = strtolower($v['type'] ?? '');
        if(str_contains($type, 'ambulance')) $ambulances++;
        if(str_contains($type, 'vsl')) $vsl++;
    }
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <small>🚐 Véhicules</small>
        <h2><?= intval($totalVehicles) ?></h2>
    </div>

    <div class="stat-card">
        <small>🚑 Ambulances</small>
        <h2><?= intval($ambulances) ?></h2>
    </div>

    <div class="stat-card">
        <small>🚘 VSL</small>
        <h2><?= intval($vsl) ?></h2>
    </div>
</div>

<div class="panel">
<h2 style="margin-bottom:18px;">➕ Ajouter un véhicule</h2>

<form method="POST">

<div class="form-grid">

<div>
<label>Immatriculation</label>
<input type="text" name="plate" placeholder="AA-123-AA" required>
</div>

<div>
<label>Nom du véhicule</label>
<input type="text" name="name" placeholder="Ambulance 1 / VSL 2" required>
</div>

<div>
<label>Type</label>
<select name="type" required>
<option value="">Choisir un type</option>
<option value="Ambulance">Ambulance</option>
<option value="VSL">VSL</option>
<option value="Taxi">Taxi conventionné</option>
<option value="TPMR">TPMR</option>
<option value="Autre">Autre</option>
</select>
</div>

<div style="display:flex;align-items:end;">
<button type="submit" class="btn btn-white" style="width:100%;">
💾 Ajouter véhicule
</button>
</div>

</div>

</form>
</div>

<?php if(!isset($vehicles) || empty($vehicles)): ?>

<div class="empty">
Aucun véhicule enregistré
</div>

<?php else: ?>

<div class="vehicle-grid">

<?php foreach($vehicles as $vehicle): ?>

<?php
$plate = trim($vehicle['plate'] ?? '');
$name = trim($vehicle['name'] ?? '');
$type = trim($vehicle['type'] ?? '');
?>

<div class="vehicle-card">

<div class="vehicle-head">

<div class="vehicle-name">
<div class="avatar">🚐</div>
<div>
<strong><?= htmlspecialchars($name ?: 'Véhicule') ?></strong>
<small><?= htmlspecialchars($plate ?: 'Immatriculation non renseignée') ?></small>
</div>
</div>

<span class="status">Disponible</span>

</div>

<div class="info">

<div>
<span>🔢</span>
<div><?= !empty($plate) ? htmlspecialchars($plate) : 'Plaque non renseignée' ?></div>
</div>

<div>
<span>🚑</span>
<div><?= !empty($type) ? htmlspecialchars($type) : 'Type non renseigné' ?></div>
</div>

<div>
<span>📍</span>
<div>Prêt pour le suivi GPS MODIGO</div>
</div>

</div>

<div class="card-actions">

<?php if(isset($vehicle['id'])): ?>
<a href="edit_vehicle.php?id=<?= intval($vehicle['id']) ?>" class="btn btn-edit">
✏ Modifier
</a>

<a
href="vehicles_modigo.php?delete=<?= intval($vehicle['id']) ?>"
class="btn btn-delete"
onclick="return confirm('Supprimer ce véhicule ?')"
>
🗑 Supprimer
</a>
<?php endif; ?>

<a href="gps_admin.php" class="btn btn-success">
📍 GPS
</a>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("<h2>Société invalide</h2>");
}

$date = trim($_GET['date'] ?? '');
$chauffeur = trim($_GET['chauffeur'] ?? '');
$statut = trim($_GET['statut'] ?? '');

$stmt = $pdo->prepare("
SELECT id, prenom
FROM users
WHERE role='chauffeur'
AND societe_id=?
ORDER BY prenom ASC
");
$stmt->execute([$societe_id]);
$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
SELECT
    c.*,
    u.prenom AS chauffeur_nom,
    v.plate AS vehicule
FROM courses c
LEFT JOIN users u ON u.id = c.chauffeur_id
LEFT JOIN vehicles v ON v.id = c.vehicle_id
WHERE c.societe_id=?
AND c.statut NOT IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')
";

$params = [$societe_id];

if (!empty($date)) {
    $sql .= " AND c.date_course=? ";
    $params[] = $date;
}

if (!empty($chauffeur)) {
    $sql .= " AND c.chauffeur_id=? ";
    $params[] = $chauffeur;
}

if (!empty($statut)) {
    $sql .= " AND c.statut=? ";
    $params[] = $statut;
}

$sql .= " ORDER BY c.date_course ASC, c.heure_pickup ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($courses);
$encours = 0;

foreach($courses as $c){
    if (strtolower(trim($c['statut'] ?? '')) === 'en cours') {
        $encours++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="30">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Courses - Medigo</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f3f4f6;color:#111827}

.topbar{
background:white;
padding:20px 30px;
display:flex;
justify-content:space-between;
align-items:center;
box-shadow:0 2px 10px rgba(0,0,0,.06);
}

.topbar-left h1{font-size:30px;color:#2563eb}
.topbar-left p{margin-top:5px;color:#6b7280}
.topbar-actions{display:flex;gap:15px;flex-wrap:wrap}

.btn{
padding:14px 20px;
border-radius:14px;
text-decoration:none;
font-weight:600;
border:none;
cursor:pointer;
font-size:14px;
display:inline-block;
}

.btn-dark{background:#111827;color:white}
.btn-primary{background:#2563eb;color:white}
.btn-edit{background:#dbeafe;color:#1d4ed8;padding:10px 14px;border-radius:10px;font-size:13px}

.btn-delete{
background:#fee2e2;
color:#dc2626;
padding:10px 14px;
border-radius:10px;
font-size:13px;
text-decoration:none;
margin-left:6px;
display:inline-block;
}

.container{max-width:1600px;margin:auto;padding:30px}

.card{
background:white;
border-radius:22px;
padding:25px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
margin-bottom:30px;
}

.stats-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
gap:20px;
margin-bottom:30px;
}

.stat-card{
background:white;
border-radius:22px;
padding:25px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.stat-card h2{font-size:42px;color:#2563eb}
.stat-card p{margin-top:8px;color:#6b7280}

.filter-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:20px;
}

label{display:block;margin-bottom:8px;font-weight:600;color:#374151}

input,select{
width:100%;
padding:14px;
border-radius:14px;
border:1px solid #d1d5db;
background:#f9fafb;
font-size:14px;
}

.table-scroll{overflow-x:auto}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#f9fafb;
text-align:left;
padding:16px;
color:#374151;
font-size:14px;
}

td{
padding:16px;
border-top:1px solid #e5e7eb;
font-size:14px;
vertical-align:middle;
}

tr:hover{background:#f9fafb}

.badge{
padding:7px 12px;
border-radius:999px;
font-size:12px;
font-weight:600;
}

.badge-prevu{background:#dbeafe;color:#1d4ed8}
.badge-cours{background:#fef3c7;color:#92400e}

.empty{
padding:40px;
text-align:center;
color:#6b7280;
}

@media(max-width:768px){
.topbar{flex-direction:column;align-items:flex-start;gap:15px}
.container{padding:20px}
}
</style>
</head>

<body>

<div class="topbar">

<div class="topbar-left">
<h1>🚗 Courses</h1>
<p>Gestion des courses Medigo</p>
</div>

<div class="topbar-actions">
<a href="dashboard.php" class="btn btn-dark">🏠 Retour accueil</a>
<a href="create_course.php" class="btn btn-primary">➕ Créer une course</a>
<a href="courses.php" class="btn btn-primary">🔄 Actualiser</a>
<a href="export_courses_csv.php" class="btn btn-primary">📄 Export CSV / Excel</a>
</div>

</div>

<div class="container">

<div class="stats-grid">

<div class="stat-card">
<h2><?= intval($total) ?></h2>
<p>Courses actives</p>
</div>

<div class="stat-card">
<h2><?= intval($encours) ?></h2>
<p>Courses en cours</p>
</div>

</div>

<div class="card">

<form method="GET">

<div class="filter-grid">

<div>
<label>Date</label>
<input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
</div>

<div>
<label>Chauffeur</label>
<select name="chauffeur">
<option value="">Tous</option>
<?php foreach($chauffeurs as $c): ?>
<option value="<?= intval($c['id']) ?>" <?= $chauffeur == $c['id'] ? 'selected' : '' ?>>
<?= htmlspecialchars($c['prenom']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div>
<label>Statut</label>
<select name="statut">
<option value="">Tous</option>
<option value="prévue" <?= $statut === 'prévue' ? 'selected' : '' ?>>Prévue</option>
<option value="prévu" <?= $statut === 'prévu' ? 'selected' : '' ?>>Prévu</option>
<option value="en cours" <?= $statut === 'en cours' ? 'selected' : '' ?>>En cours</option>
</select>
</div>

<div style="display:flex;align-items:end;">
<button type="submit" class="btn btn-primary" style="width:100%;">
🔍 Filtrer
</button>
</div>

</div>

</form>

</div>

<div class="card">

<div class="table-scroll">

<table>

<tr>
<th>Date</th>
<th>Heure</th>
<th>Patient</th>
<th>Chauffeur</th>
<th>🚑 Véhicule</th>
<th>📍 GPS</th>
<th>Départ</th>
<th>Arrivée</th>
<th>Statut</th>
<th>Actions</th>
</tr>

<?php if(empty($courses)): ?>

<tr>
<td colspan="10">
<div class="empty">Aucune course active</div>
</td>
</tr>

<?php endif; ?>

<?php foreach($courses as $course): ?>

<?php
$statut_course = strtolower(trim($course['statut'] ?? ''));

$statut_badge = '<span class="badge badge-prevu">Prévue</span>';

if ($statut_course === 'en cours') {
    $statut_badge = '<span class="badge badge-cours">En cours</span>';
}

$retard = false;

if (
    !empty($course['date_course']) &&
    !empty($course['heure_pickup']) &&
    empty($course['depart_reel'])
) {
    $heure_prevue = strtotime($course['date_course'] . ' ' . $course['heure_pickup']);

    if (time() > $heure_prevue) {
        $retard = true;
    }
}
?>

<tr>

<td>
<?= !empty($course['date_course']) ? date('d/m/Y', strtotime($course['date_course'])) : '-' ?>
</td>

<td>
<?= htmlspecialchars(substr($course['heure_pickup'] ?? '', 0, 5)) ?>
</td>

<td>
<strong><?= htmlspecialchars($course['client_nom'] ?? '') ?></strong>
</td>

<td>
<?= !empty($course['chauffeur_nom']) ? htmlspecialchars($course['chauffeur_nom']) : '<span style="color:#dc2626;font-weight:600;">Non assigné</span>' ?>
</td>

<td>
<?= !empty($course['vehicule']) ? htmlspecialchars($course['vehicule']) : '-' ?>
</td>
<td>

<?php if(!empty($course['latitude']) && !empty($course['longitude'])): ?>

<a
href="https://www.google.com/maps?q=<?= $course['latitude'] ?>,<?= $course['longitude'] ?>"
target="_blank"
style="
background:#dbeafe;
color:#1d4ed8;
padding:8px 12px;
border-radius:10px;
text-decoration:none;
font-weight:600;
"
>
📍 Voir
</a>

<?php else: ?>

-

<?php endif; ?>

</td>
<td>
<?= htmlspecialchars($course['adresse_depart'] ?? '') ?>
</td>

<td>
<?= htmlspecialchars($course['adresse_arrivee'] ?? '') ?>
</td>

<td>
<?php if($retard): ?>
<span style="
background:#fee2e2;
color:#991b1b;
padding:7px 12px;
border-radius:999px;
font-size:12px;
font-weight:700;
">
⚠ En retard
</span>
<?php else: ?>
<?= $statut_badge ?>
<?php endif; ?>
</td>

<td>

<a
href="edit_course.php?id=<?= intval($course['id']) ?>"
class="btn btn-edit"
>
✏ Modifier
</a>

<a
href="delete_course.php?id=<?= intval($course['id']) ?>"
class="btn-delete"
onclick="return confirm('Voulez-vous vraiment supprimer cette course ?');"
>
🗑 Supprimer
</a>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</div>

</body>
</html>
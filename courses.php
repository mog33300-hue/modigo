<?php
require 'auth.php';
require 'config.php';

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

foreach ($courses as $c) {
    if (strtolower(trim($c['statut'] ?? '')) === 'en cours') {
        $encours++;
    }
}
?>

<?php
$page_title = 'MODIGO - Courses';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';
?>

<div class="topbar">
    <div>
        <h1>Courses</h1>
        <p>Gestion des missions actives MODIGO · Actualisation automatique toutes les 30 secondes</p>
    </div>

    <div class="topbar-actions">
        <a href="dashboard.php" class="btn btn-glass">🏠 Dashboard</a>
        <a href="create_course.php" class="btn btn-white">➕ Créer une course</a>
        <a href="courses.php" class="btn btn-glass">🔄 Actualiser</a>
        <a href="export_courses_csv.php" class="btn btn-glass">📄 Export CSV</a>
    </div>
</div>

<section class="hero">
    <div>
        <div class="badge-title">Module régulation</div>
        <h2>Suivi des courses</h2>
        <p>
            Consultez les courses actives, filtrez par chauffeur, statut ou date,
            et gardez une vision claire des retards, véhicules et positions GPS.
        </p>
    </div>
</section>

<div class="stats-grid">

<div class="stat-card">
<small>🚗 Courses actives</small>
<h2><?= intval($total) ?></h2>
</div>

<div class="stat-card">
<small>🟠 Courses en cours</small>
<h2><?= intval($encours) ?></h2>
</div>

<div class="stat-card">
<small>👥 Chauffeurs</small>
<h2><?= intval(count($chauffeurs)) ?></h2>
</div>

</div>

<div class="panel">

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
<button type="submit" class="btn btn-white" style="width:100%;">
🔍 Filtrer
</button>
</div>

</div>

</form>

</div>

<div class="panel">

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

if ($statut_course === 'en cours' || $statut_course === 'en_cours') {
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
<?= !empty($course['heure_pickup']) ? htmlspecialchars(substr($course['heure_pickup'], 0, 5)) : '-' ?>
</td>

<td>
<strong><?= htmlspecialchars($course['client_nom'] ?? '') ?></strong>
</td>

<td>
<?= !empty($course['chauffeur_nom']) ? htmlspecialchars($course['chauffeur_nom']) : '<span style="color:#fecaca;font-weight:900;">Non assigné</span>' ?>
</td>

<td>
<?= !empty($course['vehicule']) ? htmlspecialchars($course['vehicule']) : '-' ?>
</td>

<td>
<?php if(!empty($course['latitude']) && !empty($course['longitude'])): ?>
<a
href="https://www.google.com/maps?q=<?= htmlspecialchars($course['latitude']) ?>,<?= htmlspecialchars($course['longitude']) ?>"
target="_blank"
class="btn-map"
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
<span class="badge badge-retard">⚠ En retard</span>
<?php else: ?>
<?= $statut_badge ?>
<?php endif; ?>
</td>

<td>

<div class="actions">
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
</div>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

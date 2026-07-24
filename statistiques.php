<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['societe_id'])) {
    die("Session société introuvable");
}

$societe_id = intval($_SESSION['societe_id']);

/* COURSES AUJOURD'HUI */
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE date_course = CURDATE()
AND societe_id=?
");
$stmt->execute([$societe_id]);
$courses_today = intval($stmt->fetchColumn());

/* COURSES SEMAINE */
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE YEARWEEK(date_course,1)=YEARWEEK(CURDATE(),1)
AND societe_id=?
");
$stmt->execute([$societe_id]);
$courses_week = intval($stmt->fetchColumn());

/* COURSES MOIS */
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE MONTH(date_course)=MONTH(CURDATE())
AND YEAR(date_course)=YEAR(CURDATE())
AND societe_id=?
");
$stmt->execute([$societe_id]);
$courses_month = intval($stmt->fetchColumn());

/* PATIENTS */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE societe_id=?");
$stmt->execute([$societe_id]);
$patients = intval($stmt->fetchColumn());

/* CHAUFFEURS */
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM users
WHERE role='chauffeur'
AND societe_id=?
");
$stmt->execute([$societe_id]);
$chauffeurs = intval($stmt->fetchColumn());

/* CHAUFFEURS ACTIFS SEMAINE */
$stmt = $pdo->prepare("
SELECT COUNT(DISTINCT chauffeur_id)
FROM courses
WHERE YEARWEEK(date_course,1)=YEARWEEK(CURDATE(),1)
AND chauffeur_id > 0
AND societe_id=?
");
$stmt->execute([$societe_id]);
$chauffeurs_actifs = intval($stmt->fetchColumn());
<div class="stat-card">
<div>
<h2><?= intval($utilisateurs) ?></h2>
<p>Utilisateurs</p>
</div>
<div class="stat-icon">👤</div>
</div>
/* COURSES TERMINEES */
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE statut IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')
AND societe_id=?
");
$stmt->execute([$societe_id]);
$courses_finished = intval($stmt->fetchColumn());

/* COURSES EN COURS */
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE statut IN ('en cours','en_cours')
AND societe_id=?
");
$stmt->execute([$societe_id]);
$courses_running = intval($stmt->fetchColumn());

/* DUREE MOYENNE */
$stmt = $pdo->prepare("
SELECT AVG(TIME_TO_SEC(TIMEDIFF(arrivee_reelle, depart_reel)))
FROM courses
WHERE depart_reel IS NOT NULL
AND arrivee_reelle IS NOT NULL
AND arrivee_reelle >= depart_reel
AND societe_id=?
");
$stmt->execute([$societe_id]);
$avg_seconds = intval($stmt->fetchColumn());

$avg_duration = "-";
if ($avg_seconds > 0) {
    $h = floor($avg_seconds / 3600);
    $m = floor(($avg_seconds % 3600) / 60);
    $avg_duration = $h > 0 ? $h . "h " . $m . "min" : $m . "min";
}

/* COURSES PAR CHAUFFEUR */
$stmt = $pdo->prepare("
SELECT
    u.prenom AS chauffeur,
    COUNT(c.id) AS total_courses,
    SUM(CASE WHEN c.statut IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE') THEN 1 ELSE 0 END) AS terminees
FROM users u
LEFT JOIN courses c
ON c.chauffeur_id = u.id
AND c.societe_id = ?
WHERE u.role='chauffeur'
AND u.societe_id=?
GROUP BY u.id, u.prenom
ORDER BY total_courses DESC
");
$stmt->execute([$societe_id, $societe_id]);
$stats_chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* DERNIERES COURSES */
$stmt = $pdo->prepare("
SELECT
    c.*,
    u.prenom AS chauffeur
FROM courses c
LEFT JOIN users u
ON u.id = c.chauffeur_id
WHERE c.societe_id=?
ORDER BY c.id DESC
LIMIT 10
");
$stmt->execute([$societe_id]);
$last_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Medigo - Statistiques</title>

<link rel="stylesheet" href="style.css">

<style>
.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:15px;
    margin-bottom:20px;
}

.stat-card{
    background:white;
    border-radius:14px;
    padding:20px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);
}

.stat-card h2{
    margin:0;
    font-size:34px;
    color:#111827;
}

.stat-card p{
    margin-top:10px;
    color:#6b7280;
    font-size:14px;
}

.badge{
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:bold;
}

.badge-prevu{background:#dbeafe;color:#1d4ed8;}
.badge-cours{background:#fef3c7;color:#92400e;}
.badge-termine{background:#dcfce7;color:#166534;}
</style>
</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<a href="index.php" class="btn btn-back">⬅ Retour</a>
<a href="export_courses_csv.php" class="btn btn-add">
📄 Export CSV / Excel
</a>

<h1>📊 Statistiques</h1>

<div class="stats-grid">

<div class="stat-card">
<h2><?= $courses_today ?></h2>
<p>📅 Courses aujourd’hui</p>
</div>

<div class="stat-card">
<h2><?= $courses_week ?></h2>
<p>🗓 Courses semaine</p>
</div>

<div class="stat-card">
<h2><?= $courses_month ?></h2>
<p>📆 Courses mois</p>
</div>

<div class="stat-card">
<h2><?= $patients ?></h2>
<p>👥 Patients</p>
</div>

<div class="stat-card">
<h2><?= $chauffeurs ?></h2>
<p>🚘 Chauffeurs</p>
</div>

<div class="stat-card">
<h2><?= $chauffeurs_actifs ?></h2>
<p>✅ Chauffeurs actifs semaine</p>
</div>

<div class="stat-card">
<h2><?= $courses_running ?></h2>
<p>🚗 Courses en cours</p>
</div>

<div class="stat-card">
<h2><?= $courses_finished ?></h2>
<p>✔ Courses terminées</p>
</div>

<div class="stat-card">
<h2><?= htmlspecialchars($avg_duration) ?></h2>
<p>⏱ Durée moyenne course</p>
</div>

</div>

<div class="card">

<h2>🚘 Statistiques par chauffeur</h2>

<div class="table-scroll">

<table class="table-pro">

<tr>
<th>Chauffeur</th>
<th>Total courses</th>
<th>Terminées</th>
</tr>

<?php if(empty($stats_chauffeurs)): ?>

<tr>
<td colspan="3">Aucun chauffeur</td>
</tr>

<?php endif; ?>

<?php foreach($stats_chauffeurs as $s): ?>

<tr>
<td><?= htmlspecialchars($s['chauffeur'] ?? '') ?></td>
<td><?= intval($s['total_courses'] ?? 0) ?></td>
<td><?= intval($s['terminees'] ?? 0) ?></td>
</tr>

<?php endforeach; ?>

</table>

</div>

</div>

<div class="card">

<h2>🚗 Dernières courses</h2>

<div class="table-scroll">

<table class="table-pro">

<tr>
<th>Date</th>
<th>Heure</th>
<th>Patient</th>
<th>Chauffeur</th>
<th>Départ réel</th>
<th>Arrivée réelle</th>
<th>Statut</th>
</tr>

<?php if(empty($last_courses)): ?>

<tr>
<td colspan="7">Aucune course</td>
</tr>

<?php endif; ?>

<?php foreach($last_courses as $c): ?>

<?php
$statut = strtolower(trim($c['statut'] ?? ''));

$badge = '<span class="badge badge-prevu">Prévue</span>';

if($statut === 'en cours' || $statut === 'en_cours'){
    $badge = '<span class="badge badge-cours">En cours</span>';
}

if(in_array($statut, ['terminée','terminee','terminé','termine'])){
    $badge = '<span class="badge badge-termine">Terminée</span>';
}
?>

<tr>

<td>
<?= !empty($c['date_course']) ? date('d/m/Y', strtotime($c['date_course'])) : '-' ?>
</td>

<td>
<?= htmlspecialchars(substr($c['heure_pickup'] ?? '', 0, 5)) ?>
</td>

<td>
<?= htmlspecialchars($c['client_nom'] ?? '') ?>
</td>

<td>
<?= htmlspecialchars($c['chauffeur'] ?? '') ?>
</td>

<td>
<?= !empty($c['depart_reel']) ? htmlspecialchars(substr($c['depart_reel'], 0, 5)) : '-' ?>
</td>

<td>
<?= !empty($c['arrivee_reelle']) ? htmlspecialchars(substr($c['arrivee_reelle'], 0, 5)) : '-' ?>
</td>

<td>
<?= $badge ?>
</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

<div class="footer">
Medigo V1.0
<br>
Gestion intelligente du transport médical
<br><br>
<a href="rgpd.php">🔒 Politique RGPD</a>
</div>

</div>

</body>
</html>
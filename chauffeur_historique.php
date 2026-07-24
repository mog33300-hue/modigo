<?php
require 'auth.php';
require 'config.php';

$chauffeur_id = intval($_SESSION['user_id']);
$societe_id = intval($_SESSION['societe_id'] ?? 0);
$prenom = $_SESSION['prenom'] ?? 'Chauffeur';

$stmt = $pdo->prepare("
SELECT c.*, v.plate AS vehicule, v.name AS vehicule_nom
FROM courses c
LEFT JOIN vehicles v ON v.id = c.vehicle_id
WHERE c.societe_id=?
AND c.chauffeur_id=?
AND c.statut IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')
ORDER BY c.date_course DESC, c.heure_pickup DESC
");

$stmt->execute([$societe_id, $chauffeur_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Historique chauffeur - Medigo</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f3f4f6;color:#111827}
.topbar{background:white;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:center;gap:15px}
.topbar h1{color:#2563eb;font-size:28px}
.topbar p{color:#6b7280;margin-top:5px}
.btn-logout{background:#111827;color:white;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:600}
.container{max-width:1100px;margin:auto;padding:25px}
.course-card{background:white;border-radius:22px;padding:25px;margin-bottom:20px;box-shadow:0 5px 18px rgba(0,0,0,.05)}
.course-title{font-size:22px;font-weight:700;margin-bottom:10px}
.info{margin-top:10px;color:#374151;line-height:1.7}
.empty{background:white;border-radius:22px;padding:40px;text-align:center;color:#6b7280;box-shadow:0 5px 18px rgba(0,0,0,.05)}
.badge{display:inline-block;padding:7px 12px;border-radius:999px;font-size:13px;font-weight:700;background:#dcfce7;color:#166534}
@media(max-width:768px){.topbar{flex-direction:column;align-items:flex-start}.btn-logout{width:100%;text-align:center}}
</style>
</head>

<body>

<div class="topbar">
<div>
<h1>📜 Historique chauffeur</h1>
<p>Bonjour <?= htmlspecialchars($prenom) ?> — Vos courses terminées</p>
</div>

<div style="display:flex;gap:10px;flex-wrap:wrap;">
<a href="chauffeur_courses.php" class="btn-logout">📋 Mes courses</a>
<a href="logout.php" class="btn-logout">🚪 Déconnexion</a>
</div>
</div>

<div class="container">

<?php if(empty($courses)): ?>
<div class="empty">Aucune course terminée.</div>
<?php endif; ?>

<?php foreach($courses as $c): ?>

<?php
$depart = !empty($c['depart_reel']) ? substr($c['depart_reel'],0,5) : '-';
$arrivee = !empty($c['arrivee_reelle']) ? substr($c['arrivee_reelle'],0,5) : '-';
$duree = '-';

if (!empty($c['depart_reel']) && !empty($c['arrivee_reelle'])) {
    $d1 = strtotime($c['depart_reel']);
    $d2 = strtotime($c['arrivee_reelle']);
    if ($d2 >= $d1) {
        $diff = $d2 - $d1;
        $h = floor($diff / 3600);
        $m = floor(($diff % 3600) / 60);
        $duree = $h > 0 ? $h.'h '.$m.'min' : $m.'min';
    }
}
?>

<div class="course-card">

<div class="course-title">
<?= htmlspecialchars($c['client_nom'] ?? 'Patient') ?>
</div>

<span class="badge">Terminée</span>

<div class="info">
<strong>Date :</strong>
<?= !empty($c['date_course']) ? date('d/m/Y', strtotime($c['date_course'])) : '-' ?>
<br>

<strong>Heure prévue :</strong>
<?= !empty($c['heure_pickup']) ? substr($c['heure_pickup'],0,5) : '-' ?>
<br>

<strong>Départ :</strong>
<?= htmlspecialchars($c['adresse_depart'] ?? '-') ?>
<?php if(!empty($c['ville_depart'])): ?>
, <?= htmlspecialchars($c['ville_depart']) ?>
<?php endif; ?>
<br>

<strong>Arrivée :</strong>
<?= htmlspecialchars($c['adresse_arrivee'] ?? '-') ?>
<?php if(!empty($c['ville_arrivee'])): ?>
, <?= htmlspecialchars($c['ville_arrivee']) ?>
<?php endif; ?>
<br>

<strong>Véhicule :</strong>
<?= htmlspecialchars($c['vehicule'] ?? '-') ?>

<?php if(!empty($c['vehicule_nom'])): ?>
- <?= htmlspecialchars($c['vehicule_nom']) ?>
<?php endif; ?>

<br>

<strong>Départ réel :</strong> <?= htmlspecialchars($depart) ?><br>
<strong>Arrivée réelle :</strong> <?= htmlspecialchars($arrivee) ?><br>
<strong>Durée :</strong> <?= htmlspecialchars($duree) ?>
</div>

</div>

<?php endforeach; ?>

</div>

</body>
</html>
<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$chauffeur_id = intval($_SESSION['user_id']);
$societe_id = intval($_SESSION['societe_id'] ?? 0);
$role = $_SESSION['role'] ?? '';
$prenom = $_SESSION['prenom'] ?? 'Chauffeur';

if ($societe_id <= 0) {
    die("Société invalide");
}

if ($role !== 'chauffeur' && $role !== 'admin' && $role !== 'superadmin') {
    die("Accès refusé");
}

/* ACTIONS CHAUFFEUR */
if (isset($_GET['action'], $_GET['id'])) {

    $action = trim($_GET['action']);
    $course_id = intval($_GET['id']);

    if ($course_id > 0) {

        if ($action === 'depart') {

            $stmt = $pdo->prepare("
                UPDATE courses
                SET depart_reel = CURTIME(),
                    statut = 'en cours'
                WHERE id = ?
                AND chauffeur_id = ?
                AND societe_id = ?
            ");

            $stmt->execute([
                $course_id,
                $chauffeur_id,
                $societe_id
            ]);
        }

        if ($action === 'terminer') {

            $stmt = $pdo->prepare("
                UPDATE courses
                SET arrivee_reelle = CURTIME(),
                    statut = 'terminée'
                WHERE id = ?
                AND chauffeur_id = ?
                AND societe_id = ?
            ");

            $stmt->execute([
                $course_id,
                $chauffeur_id,
                $societe_id
            ]);
        }
    }

    header("Location: chauffeur_courses.php");
    exit;
}

/* COURSES DU CHAUFFEUR */
$stmt = $pdo->prepare("
SELECT
    c.*,
    v.plate AS vehicule,
    v.name AS vehicule_nom
FROM courses c
LEFT JOIN vehicles v
ON v.id = c.vehicle_id
WHERE c.societe_id = ?
AND c.chauffeur_id = ?
AND c.statut NOT IN (
    'terminée',
    'terminee',
    'terminé',
    'termine',
    'TERMINEE',
    'TERMINE'
)
ORDER BY
c.date_course ASC,
c.heure_pickup ASC
");

$stmt->execute([
    $societe_id,
    $chauffeur_id
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($courses);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Espace chauffeur - Medigo</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:#f3f4f6;
    color:#111827;
}

.topbar{
    background:white;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
}

.topbar h1{
    color:#2563eb;
    font-size:28px;
}

.topbar p{
    color:#6b7280;
    margin-top:5px;
}

.btn-logout{
    background:#111827;
    color:white;
    padding:12px 18px;
    border-radius:12px;
    text-decoration:none;
    font-weight:600;
}

.container{
    max-width:1100px;
    margin:auto;
    padding:25px;
}

.stat-card{
    background:white;
    border-radius:22px;
    padding:25px;
    margin-bottom:25px;
    box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.stat-card h2{
    font-size:42px;
    color:#2563eb;
}

.course-card{
    background:white;
    border-radius:22px;
    padding:25px;
    margin-bottom:20px;
    box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.course-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:15px;
    margin-bottom:15px;
}

.course-title{
    font-size:22px;
    font-weight:700;
}

.course-time{
    background:#dbeafe;
    color:#1d4ed8;
    padding:8px 12px;
    border-radius:999px;
    font-weight:700;
}

.info{
    margin-top:10px;
    color:#374151;
    line-height:1.7;
}

.actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:22px;
}

.btn{
    display:inline-block;
    padding:14px 18px;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
}

.btn-start{
    background:#fef3c7;
    color:#92400e;
}

.btn-finish{
    background:#dcfce7;
    color:#166534;
}

.badge{
    display:inline-block;
    padding:7px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
}

.badge-prevue{
    background:#dbeafe;
    color:#1d4ed8;
}

.badge-cours{
    background:#fef3c7;
    color:#92400e;
}

.empty{
    background:white;
    border-radius:22px;
    padding:40px;
    text-align:center;
    color:#6b7280;
    box-shadow:0 5px 18px rgba(0,0,0,.05);
}

@media(max-width:768px){
    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }

    .course-header{
        flex-direction:column;
    }

    .btn{
        width:100%;
        text-align:center;
    }
}
</style>
</head>

<body>

<div class="topbar">

<div>
<h1>🚑 Espace chauffeur</h1>
<p>Bonjour <?= htmlspecialchars($prenom) ?> — Vos courses du jour et à venir</p>
</div>

<<div style="display:flex;gap:10px;flex-wrap:wrap;">

<a href="chauffeur_historique.php" class="btn-logout">
📜 Mon historique
</a>
<div style="display:flex;gap:10px;flex-wrap:wrap;">

<a href="chauffeur_historique.php" class="btn-logout">
📜 Mon historique
</a>
<a href="logout.php" class="btn-logout">
🚪 Déconnexion
</a>

</div>

</div>

<div class="container">

<div class="stat-card">
<h2><?= intval($total) ?></h2>
<p>Course(s) active(s)</p>
</div>

<?php if(empty($courses)): ?>

<div class="empty">
Aucune course active pour le moment.
</div>

<?php endif; ?>

<?php foreach($courses as $c): ?>

<?php
$statut = strtolower(trim($c['statut'] ?? 'prévue'));

$badge = '<span class="badge badge-prevue">Prévue</span>';

if ($statut === 'en cours') {
    $badge = '<span class="badge badge-cours">En cours</span>';
}

$heure = !empty($c['heure_pickup'])
    ? substr($c['heure_pickup'], 0, 5)
    : '--:--';
?>

<div class="course-card">

<div class="course-header">

<div>
<div class="course-title">
<?= htmlspecialchars($c['client_nom'] ?? 'Patient') ?>
</div>

<div class="info">
<?= $badge ?>
</div>
</div>

<div class="course-time">
<?= htmlspecialchars($heure) ?>
</div>

</div>

<div class="info">

<strong>Date :</strong>
<?= !empty($c['date_course']) ? date('d/m/Y', strtotime($c['date_course'])) : '-' ?>
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

<strong>Départ réel :</strong>
<?= !empty($c['depart_reel']) ? substr($c['depart_reel'],0,5) : '-' ?>
<br>

<strong>Arrivée réelle :</strong>
<?= !empty($c['arrivee_reelle']) ? substr($c['arrivee_reelle'],0,5) : '-' ?>

<?php if(!empty($c['observations'])): ?>
<br>
<strong>Observations :</strong>
<?= nl2br(htmlspecialchars($c['observations'])) ?>
<?php endif; ?>

</div>

<div class="actions">

<a
href="#"
onclick="envoyerPosition(<?= intval($c['id']) ?>);return false;"
class="btn btn-start"
>
📍 GPS
</a>

<?php if(empty($c['depart_reel'])): ?>

<a
href="chauffeur_courses.php?action=depart&id=<?= intval($c['id']) ?>"
class="btn btn-start"
>
🚗 Démarrer
</a>

<?php endif; ?>

<?php if(!empty($c['depart_reel'])): ?>

<a
href="chauffeur_courses.php?action=terminer&id=<?= intval($c['id']) ?>"
class="btn btn-finish"
>
✅ Terminer
</a>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>
<script>

function envoyerPosition(courseId){

    if(!navigator.geolocation){

        alert("GPS non disponible");

        return;
    }

    navigator.geolocation.getCurrentPosition(

        function(position){

            fetch(
                'save_position.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':'application/x-www-form-urlencoded'
                    },

                    body:
                        'course_id=' + courseId +
                        '&lat=' + position.coords.latitude +
                        '&lng=' + position.coords.longitude
                }
            )

            .then(r=>r.text())

            .then(data=>{

                alert('📍 Position enregistrée');

            });

        }

    );

}

</script>
</body>
</html>
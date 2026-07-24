<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   SECURITE
===================================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {

    die("<h2>Société invalide</h2>");
}

/* =====================================================
   SEMAINE
===================================================== */

$week = $_GET['week'] ?? date('Y-\WW');

$year = intval(substr($week, 0, 4));

$weekNumber = intval(substr($week, 6, 2));

$monday = new DateTime();

$monday->setISODate($year, $weekNumber);

$days = [];

for ($i = 0; $i < 7; $i++) {

    $day = clone $monday;

    $day->modify("+$i day");

    $days[] = [

        'date' => $day->format('Y-m-d'),

        'label' => [

            'Sunday' => 'Dimanche',
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi'

        ][$day->format('l')] . ' ' . $day->format('d/m')

    ];
}

$prevWeek = clone $monday;

$prevWeek->modify('-1 week');

$nextWeek = clone $monday;

$nextWeek->modify('+1 week');

/* =====================================================
   CHAUFFEURS
===================================================== */

$stmt = $pdo->prepare("
SELECT id, prenom
FROM users
WHERE role='chauffeur'
AND societe_id=?
ORDER BY prenom ASC
");

$stmt->execute([$societe_id]);

$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   COURSES DE LA SEMAINE
===================================================== */

$start = $days[0]['date'];

$end = $days[6]['date'];

$stmt = $pdo->prepare("
SELECT
    c.*,
    u.prenom AS chauffeur_nom
FROM courses c
LEFT JOIN users u
ON u.id = c.chauffeur_id
WHERE c.societe_id=?
AND c.date_course BETWEEN ? AND ?
ORDER BY c.heure_pickup ASC
");

$stmt->execute([
    $societe_id,
    $start,
    $end
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   ORGANISATION
===================================================== */

$planning = [];

foreach ($chauffeurs as $chauffeur) {

    foreach ($days as $day) {

        $planning[$chauffeur['id']][$day['date']] = [];
    }
}

foreach ($courses as $course) {

    $chauffeur_id =
    intval($course['chauffeur_id'] ?? 0);

    $date =
    $course['date_course'] ?? '';

    if (
        isset($planning[$chauffeur_id]) &&
        isset($planning[$chauffeur_id][$date])
    ) {

        $planning[$chauffeur_id][$date][] =
        $course;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1"
>

<title>
Planning semaine - Medigo
</title>

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
padding:20px 30px;
display:flex;
justify-content:space-between;
align-items:center;
box-shadow:0 2px 10px rgba(0,0,0,.06);
}

.topbar-left h1{
font-size:30px;
color:#2563eb;
}

.topbar-left p{
margin-top:5px;
color:#6b7280;
}

.topbar-actions{
display:flex;
gap:15px;
flex-wrap:wrap;
}

.btn{
padding:14px 20px;
border-radius:14px;
text-decoration:none;
font-weight:600;
border:none;
cursor:pointer;
font-size:14px;
}

.btn-dark{
background:#111827;
color:white;
}

.btn-primary{
background:#2563eb;
color:white;
}

.container{
max-width:1800px;
margin:auto;
padding:30px;
}

.week-card{
background:white;
border-radius:22px;
padding:25px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
margin-bottom:30px;
display:flex;
justify-content:space-between;
align-items:center;
gap:15px;
flex-wrap:wrap;
}

.week-card h2{
color:#2563eb;
}

.table-card{
background:white;
border-radius:22px;
overflow:hidden;
box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.table-scroll{
overflow-x:auto;
}

table{
width:100%;
border-collapse:collapse;
min-width:1200px;
}

th{
background:#f9fafb;
padding:16px;
text-align:left;
color:#374151;
font-size:14px;
border-bottom:1px solid #e5e7eb;
}

td{
padding:12px;
border-top:1px solid #e5e7eb;
vertical-align:top;
min-height:120px;
}

.chauffeur-cell{
font-weight:700;
color:#111827;
background:#f9fafb;
min-width:160px;
}

.course{
border-radius:14px;
padding:12px;
margin-bottom:10px;
font-size:13px;
line-height:1.4;
}

.course-prevue{
background:#dbeafe;
color:#1e40af;
}

.course-cours{
background:#fef3c7;
color:#92400e;
}

.course-terminee{
background:#dcfce7;
color:#166534;
}

.course-time{
font-weight:700;
margin-bottom:5px;
}

.course-patient{
font-weight:600;
}

.course-address{
margin-top:5px;
font-size:12px;
}

.empty{
color:#9ca3af;
font-size:13px;
padding:10px;
}

@media(max-width:768px){

.topbar{
flex-direction:column;
align-items:flex-start;
gap:15px;
}

.container{
padding:20px;
}

}

</style>

</head>

<body>

<div class="topbar">

<div class="topbar-left">

<h1>
📅 Planning semaine
</h1>

<p>
Vue chauffeur du lundi au dimanche
</p>

</div>

<div class="topbar-actions">

<a href="dashboard.php" class="btn btn-dark">
🏠 Retour accueil
</a>

</div>

</div>

<div class="container">

<div class="week-card">

<a
class="btn btn-dark"
href="planning_semaine.php?week=<?= $prevWeek->format('Y-\WW') ?>"
>
⬅ Semaine précédente
</a>

<h2>
Semaine <?= htmlspecialchars($weekNumber) ?>
</h2>

<a
class="btn btn-dark"
href="planning_semaine.php?week=<?= $nextWeek->format('Y-\WW') ?>"
>
Semaine suivante ➡
</a>

</div>

<div class="table-card">

<div class="table-scroll">

<table>

<tr>

<th>
Chauffeur
</th>

<?php foreach($days as $day): ?>

<th>
<?= htmlspecialchars($day['label']) ?>
</th>

<?php endforeach; ?>

</tr>

<?php if(empty($chauffeurs)): ?>

<tr>

<td colspan="8">

<div style="
padding:40px;
text-align:center;
color:#6b7280;
">

Aucun chauffeur enregistré

</div>

</td>

</tr>

<?php endif; ?>

<?php foreach($chauffeurs as $chauffeur): ?>

<tr>

<td class="chauffeur-cell">
🚘 <?= htmlspecialchars($chauffeur['prenom']) ?>
</td>

<?php foreach($days as $day): ?>

<td>

<?php

$dayCourses =
$planning[$chauffeur['id']][$day['date']] ?? [];

?>

<?php if(empty($dayCourses)): ?>

<div class="empty">
Aucune course
</div>

<?php endif; ?>

<?php foreach($dayCourses as $course): ?>

<?php

$statut =
strtolower(trim($course['statut'] ?? ''));

$class =
'course-prevue';

if (
    $statut === 'en cours' ||
    $statut === 'en_cours'
) {
    $class =
    'course-cours';
}

if (
    in_array(
        $statut,
        [
            'terminée',
            'terminee',
            'terminé',
            'termine'
        ]
    )
) {
    $class =
    'course-terminee';
}

?>

<div class="course <?= $class ?>">

<div class="course-time">
<?= htmlspecialchars(substr($course['heure_pickup'] ?? '', 0, 5)) ?>
</div>

<div class="course-patient">
<?= htmlspecialchars($course['client_nom'] ?? '') ?>
</div>

<div class="course-address">
📍 <?= htmlspecialchars($course['adresse_depart'] ?? '') ?>
</div>

<div class="course-address">
🏁 <?= htmlspecialchars($course['adresse_arrivee'] ?? '') ?>
</div>

</div>

<?php endforeach; ?>

</td>

<?php endforeach; ?>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</div>

</body>

</html>
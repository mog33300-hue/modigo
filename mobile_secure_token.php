<?php
require 'config.php';

/* =====================================================
   TOKEN
===================================================== */

$token = $_GET['token'] ?? '';

/* =====================================================
   CHAUFFEUR
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE token=?
");

$stmt->execute([$token]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {

    die("Accès refusé");
}

/* =====================================================
   SEMAINE
===================================================== */

$today = date('Y-m-d');

$endWeek = date(
    'Y-m-d',
    strtotime('+6 days')
);

/* =====================================================
   COURSES
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM courses
WHERE chauffeur_id=?
AND date_course BETWEEN ? AND ?
ORDER BY date_course, heure_pickup
");

$stmt->execute([
    $user['id'],
    $today,
    $endWeek
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   ORGANISATION
===================================================== */

$planning = [];

foreach($courses as $c){

    $planning[$c['date_course']][] = $c;
}

/* =====================================================
   JOURS FR
===================================================== */

$jours = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];
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
Medigo Mobile
</title>

<link rel="stylesheet" href="style.css">

<style>

/* =====================================================
   MOBILE BODY
===================================================== */

body{

    background:#f3f4f6;

    margin:0;

    font-family:Arial,sans-serif;
}

/* =====================================================
   MAIN
===================================================== */

.main{

    padding:15px;
}

/* =====================================================
   HEADER
===================================================== */

.mobile-header{

    background:#111827;

    color:white;

    padding:22px;

    border-radius:14px;

    margin-bottom:20px;

    text-align:center;
}

.mobile-header h1{

    margin:0;

    color:white;

    font-size:26px;
}

.mobile-header p{

    margin-top:10px;

    color:#d1d5db;

    line-height:1.6;
}

/* =====================================================
   DAY CARD
===================================================== */

.day-card{

    background:white;

    border-radius:14px;

    padding:18px;

    margin-bottom:20px;

    box-shadow:0 2px 8px rgba(0,0,0,0.06);
}

/* =====================================================
   DAY TITLE
===================================================== */

.day-title{

    font-size:20px;

    font-weight:bold;

    margin-bottom:15px;

    border-bottom:1px solid #e5e7eb;

    padding-bottom:10px;

    color:#111827;
}

/* =====================================================
   COURSE
===================================================== */

.course{

    background:#f9fafb;

    border-left:5px solid #2563eb;

    border-radius:10px;

    padding:15px;

    margin-bottom:15px;
}

.course.en_cours{
    border-left-color:#f59e0b;
}

.course.termine{
    border-left-color:#16a34a;
}

/* =====================================================
   TIME
===================================================== */

.course-time{

    font-size:22px;

    font-weight:bold;

    margin-bottom:10px;

    color:#111827;
}

/* =====================================================
   BADGES
===================================================== */

.badge{

    display:inline-block;

    padding:5px 10px;

    border-radius:6px;

    color:white;

    font-size:11px;

    font-weight:bold;

    margin-bottom:12px;
}

.badge-prevu{
    background:#2563eb;
}

.badge-cours{
    background:#f59e0b;
}

.badge-termine{
    background:#16a34a;
}

/* =====================================================
   INFOS
===================================================== */

.course-info{

    margin-bottom:10px;

    line-height:1.6;

    color:#374151;

    font-size:15px;
}

/* =====================================================
   ACTIONS
===================================================== */

.actions{

    display:flex;

    flex-direction:column;

    gap:10px;

    margin-top:15px;
}

/* =====================================================
   BUTTONS
===================================================== */

.btn-mobile{

    display:block;

    width:100%;

    padding:14px;

    border-radius:10px;

    text-decoration:none;

    text-align:center;

    color:white;

    font-size:15px;

    font-weight:bold;

    box-sizing:border-box;
}

.call{
    background:#16a34a;
}

.gps{
    background:#2563eb;
}

.start{
    background:#f59e0b;
}

.finish{
    background:#111827;
}

/* =====================================================
   EMPTY
===================================================== */

.empty{

    color:#6b7280;

    font-style:italic;
}

/* =====================================================
   FOOTER
===================================================== */

.footer{

    text-align:center;

    margin-top:30px;

    font-size:11px;

    color:#9ca3af;

    line-height:1.8;
}

</style>

</head>

<body>

<div class="main">

<!-- RETOUR -->

<a
href="chauffeurs.php"
class="btn btn-back"
style="margin-bottom:15px;display:inline-block;"
>
⬅ Retour chauffeurs
</a>

<!-- HEADER -->

<div class="mobile-header">

<h1>
🚑 Medigo
</h1>

<p>

Gestion intelligente du transport médical

<br><br>

👤 <?= htmlspecialchars($user['prenom'] ?? '') ?>

</p>

</div>

<!-- JOURS -->

<?php for($i = 0; $i < 7; $i++): ?>

<?php

$currentDate = date(
    'Y-m-d',
    strtotime("+$i days")
);

$dayName = date(
    'l',
    strtotime($currentDate)
);

$jourFR = $jours[$dayName];

?>

<div class="day-card">

<div class="day-title">

<?= $jourFR ?>

<?= date(
'd/m/Y',
strtotime($currentDate)
) ?>

</div>

<?php if(!empty($planning[$currentDate])): ?>

<?php foreach($planning[$currentDate] as $c): ?>

<?php

$class = "";
$badge = "badge-prevu";
$label = "Prévue";

if(
    ($c['statut'] ?? '') === 'en cours' ||
    ($c['statut'] ?? '') === 'en_cours'
){

    $class = "en_cours";
    $badge = "badge-cours";
    $label = "En cours";
}

if(
    ($c['statut'] ?? '') === 'terminé' ||
    ($c['statut'] ?? '') === 'termine'
){

    $class = "termine";
    $badge = "badge-termine";
    $label = "Terminée";
}

?>

<div class="course <?= $class ?>">

<div class="course-time">

🕒 <?= htmlspecialchars($c['heure_pickup'] ?? '') ?>

</div>

<div class="badge <?= $badge ?>">

<?= $label ?>

</div>

<div class="course-info">

<strong>
👤 Patient :
</strong>

<?= htmlspecialchars($c['client_nom'] ?? '') ?>

</div>

<?php if(!empty($c['telephone'])): ?>

<div class="course-info">

<strong>
📞 Téléphone :
</strong>

<?= htmlspecialchars($c['telephone'] ?? '') ?>

</div>

<?php endif; ?>

<div class="course-info">

<strong>
📍 Aller :
</strong>

<?= htmlspecialchars($c['adresse_depart'] ?? '') ?>

</div>

<div class="course-info">

<strong>
🏁 Retour :
</strong>

<?= htmlspecialchars($c['adresse_arrivee'] ?? '') ?>

<?= htmlspecialchars($c['ville_arrivee'] ?? '') ?>

</div>

<?php if(!empty($c['notes'])): ?>

<div class="course-info">

<strong>
📝 Notes :
</strong>

<?= nl2br(htmlspecialchars($c['notes'] ?? '')) ?>

</div>

<?php endif; ?>

<!-- ACTIONS -->

<div class="actions">

<?php if(!empty($c['telephone'])): ?>

<a
href="tel:<?= htmlspecialchars($c['telephone'] ?? '') ?>"
class="btn-mobile call"
>
📞 Appeler
</a>

<?php endif; ?>

<a
href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($c['adresse_depart'] ?? '') ?>"
target="_blank"
class="btn-mobile gps"
>
🧭 GPS
</a>

<a
href="start_course.php?id=<?= urlencode($c['id'] ?? '') ?>"
class="btn-mobile start"
>
🚗 Départ
</a>

<a
href="finish_course.php?id=<?= urlencode($c['id'] ?? '') ?>"
class="btn-mobile finish"
>
✔ Fin course
</a>

</div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="empty">

Aucune course

</div>

<?php endif; ?>

</div>

<?php endfor; ?>

<!-- FOOTER -->

<div class="footer">

Medigo Mobile

<br>

Gestion intelligente du transport médical

</div>

</div>

</body>

</html>
<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   DATE
===================================================== */

$date = $_GET['date'] ?? date('Y-m-d');

/* =====================================================
   COURSES
===================================================== */

$stmt = $pdo->prepare("
SELECT
    c.*,
    u.prenom AS chauffeur
FROM courses c
LEFT JOIN users u ON u.id = c.chauffeur_id
WHERE c.date_course = ?
ORDER BY c.heure_pickup ASC
");

$stmt->execute([$date]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
Agenda
</title>

<link rel="stylesheet" href="style.css">

<style>

/* =====================================================
   FILTER CARD
===================================================== */

.filter-card{

    background:white;

    border-radius:10px;

    padding:20px;

    margin-bottom:20px;

    box-shadow:0 2px 6px rgba(0,0,0,0.06);
}

/* =====================================================
   COURSE CARD
===================================================== */

.course-card{

    background:white;

    border-radius:10px;

    padding:20px;

    margin-bottom:20px;

    box-shadow:0 2px 6px rgba(0,0,0,0.06);

    border-left:5px solid #2563eb;
}

/* =====================================================
   HEADER
===================================================== */

.course-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:15px;

    flex-wrap:wrap;

    gap:10px;
}

/* =====================================================
   TIME
===================================================== */

.course-time{

    font-size:22px;

    font-weight:bold;

    color:#111827;
}

/* =====================================================
   INFOS
===================================================== */

.course-info{

    margin-bottom:10px;

    color:#374151;

    line-height:1.5;
}

</style>

</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<a href="index.php" class="btn btn-back">
⬅ Retour
</a>

<h1>
📅 Agenda
</h1>

<!-- FILTRE -->

<div class="filter-card">

<form method="GET" action="agenda.php">

<label>
Date
</label>

<input
type="date"
name="date"
value="<?= htmlspecialchars($date) ?>"
>

<div class="actions">

<button
type="submit"
class="btn btn-add"
>
📅 Afficher
</button>

</div>

</form>

</div>

<!-- INFOS JOUR -->

<div class="card">

<h2>

Agenda du

<?= date(
'd/m/Y',
strtotime($date)
) ?>

</h2>

<p>

<?= count($courses) ?>

course(s)

</p>

</div>

<!-- COURSES -->

<?php if(empty($courses)): ?>

<div class="card">

Aucune course prévue

</div>

<?php endif; ?>

<?php foreach($courses as $c): ?>

<?php

$badge =
'<span class="badge badge-prevu">
Prévue
</span>';

if(
    $c['statut'] === 'en cours' ||
    $c['statut'] === 'en_cours'
){

    $badge =
    '<span class="badge badge-cours">
    En cours
    </span>';
}

if(
    $c['statut'] === 'terminé' ||
    $c['statut'] === 'termine'
){

    $badge =
    '<span class="badge badge-termine">
    Terminée
    </span>';
}

?>

<div class="course-card">

<div class="course-header">

<div class="course-time">

🕒 <?= htmlspecialchars($c['heure_pickup']) ?>

</div>

<div>

<?= $badge ?>

</div>

</div>

<div class="course-info">

<strong>
👤 Patient :
</strong>

<?= htmlspecialchars($c['client_nom']) ?>

</div>

<?php if(!empty($c['telephone'])): ?>

<div class="course-info">

<strong>
📞 Téléphone :
</strong>

<?= htmlspecialchars($c['telephone']) ?>

</div>

<?php endif; ?>

<div class="course-info">

<strong>
🚘 Chauffeur :
</strong>

<?= htmlspecialchars($c['chauffeur'] ?? '') ?>

</div>

<div class="course-info">

<strong>
📍 Aller :
</strong>

<?= htmlspecialchars($c['adresse_depart']) ?>

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

<?= nl2br(htmlspecialchars($c['notes'])) ?>

</div>

<?php endif; ?>

<!-- ACTIONS -->

<div class="actions">

<a
href="edit_course.php?id=<?= $c['id'] ?>"
class="btn btn-edit"
>
✏ Modifier
</a>

<a
href="delete_course.php?id=<?= $c['id'] ?>"
class="btn btn-delete"
onclick="return confirm('Supprimer cette course ?')"
>
🗑 Supprimer
</a>

</div>

</div>

<?php endforeach; ?>

<!-- FOOTER -->

<div class="footer">

Medigo V1.0

<br>

Gestion intelligente du transport médical

<br><br>

<a href="rgpd.php">
🔒 Politique RGPD
</a>

</div>
</div>

</body>

</html>
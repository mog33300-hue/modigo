<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   SECURITE SOCIETE
===================================================== */

if (!isset($_SESSION['societe_id'])) {

    die("Session société introuvable");

}

$societe_id = intval($_SESSION['societe_id']);

/* =====================================================
   COURSES AUJOURD'HUI
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE date_course = CURDATE()
AND societe_id=?
");

$stmt->execute([
    $societe_id
]);

$courses_today = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   COURSES SEMAINE
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE YEARWEEK(date_course,1)=YEARWEEK(CURDATE(),1)
AND societe_id=?
");

$stmt->execute([
    $societe_id
]);

$courses_week = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   COURSES MOIS
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE MONTH(date_course)=MONTH(CURDATE())
AND YEAR(date_course)=YEAR(CURDATE())
AND societe_id=?
");

$stmt->execute([
    $societe_id
]);

$courses_month = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   PATIENTS
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM patients
WHERE societe_id=?
");

$stmt->execute([
    $societe_id
]);

$patients = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   CHAUFFEURS
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM users
WHERE role='chauffeur'
AND societe_id=?
");

$stmt->execute([
    $societe_id
]);

$chauffeurs = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   CHAUFFEURS ACTIFS
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(DISTINCT chauffeur_id)
FROM courses
WHERE YEARWEEK(date_course,1)=YEARWEEK(CURDATE(),1)
AND societe_id=?
");

$stmt->execute([
    $societe_id
]);

$chauffeurs_actifs = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   COURSES TERMINEES
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE (
    statut='terminé'
    OR statut='termine'
)
AND societe_id=?
");

$stmt->execute([
    $societe_id
]);

$courses_finished = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   COURSES EN COURS
===================================================== */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM courses
WHERE (
    statut='en cours'
    OR statut='en_cours'
)
AND societe_id=?
");

$stmt->execute([
    $societe_id
]);

$courses_running = intval(
    $stmt->fetchColumn()
);

/* =====================================================
   DERNIERES COURSES
===================================================== */

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

$stmt->execute([
    $societe_id
]);

$last_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
Medigo - Statistiques
</title>

<link rel="stylesheet" href="style.css">

<style>

.stats-grid{

    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(220px,1fr));

    gap:15px;

    margin-bottom:20px;
}

.stat-card{

    background:white;

    border-radius:14px;

    padding:20px;

    box-shadow:0 2px 8px rgba(0,0,0,0.06);

    transition:0.2s;
}

.stat-card:hover{

    transform:translateY(-2px);
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

.badge-prevu{

    background:#dbeafe;

    color:#1d4ed8;
}

.badge-cours{

    background:#fef3c7;

    color:#92400e;
}

.badge-termine{

    background:#dcfce7;

    color:#166534;
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
📊 Statistiques
</h1>

<!-- STATS -->

<div class="stats-grid">

<div class="stat-card">

<h2>
<?= $courses_today ?>
</h2>

<p>
📅 Courses aujourd’hui
</p>

</div>

<div class="stat-card">

<h2>
<?= $courses_week ?>
</h2>

<p>
🗓 Courses semaine
</p>

</div>

<div class="stat-card">

<h2>
<?= $courses_month ?>
</h2>

<p>
📆 Courses mois
</p>

</div>

<div class="stat-card">

<h2>
<?= $patients ?>
</h2>

<p>
👥 Patients
</p>

</div>

<div class="stat-card">

<h2>
<?= $chauffeurs ?>
</h2>

<p>
🚘 Chauffeurs
</p>

</div>

<div class="stat-card">

<h2>
<?= $chauffeurs_actifs ?>
</h2>

<p>
✅ Chauffeurs actifs
</p>

</div>

<div class="stat-card">

<h2>
<?= $courses_running ?>
</h2>

<p>
🚗 Courses en cours
</p>

</div>

<div class="stat-card">

<h2>
<?= $courses_finished ?>
</h2>

<p>
✔ Courses terminées
</p>

</div>

</div>

<!-- DERNIERES COURSES -->

<div class="card">

<h2>
🚗 Dernières courses
</h2>

<div class="table-scroll">

<table class="table-pro">

<tr>

<th>Date</th>

<th>Heure</th>

<th>Patient</th>

<th>Chauffeur</th>

<th>Statut</th>

</tr>

<?php if(empty($last_courses)): ?>

<tr>

<td colspan="5">

Aucune course

</td>

</tr>

<?php endif; ?>

<?php foreach($last_courses as $c): ?>

<?php

$badge =
'<span class="badge badge-prevu">
Prévue
</span>';

if(
    ($c['statut'] ?? '') === 'en cours' ||
    ($c['statut'] ?? '') === 'en_cours'
){

    $badge =
    '<span class="badge badge-cours">
    En cours
    </span>';
}

if(
    ($c['statut'] ?? '') === 'terminé' ||
    ($c['statut'] ?? '') === 'termine'
){

    $badge =
    '<span class="badge badge-termine">
    Terminée
    </span>';
}

?>

<tr>

<td>

<?php

if(!empty($c['date_course'])){

    echo date(
        'd/m/Y',
        strtotime($c['date_course'])
    );

}else{

    echo '-';
}

?>

</td>

<td>

<?= htmlspecialchars($c['heure_pickup'] ?? '') ?>

</td>

<td>

<?= htmlspecialchars($c['client_nom'] ?? '') ?>

</td>

<td>

<?= htmlspecialchars($c['chauffeur'] ?? '') ?>

</td>

<td>

<?= $badge ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

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
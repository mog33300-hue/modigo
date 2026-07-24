<?php
require 'config.php';

/* =====================================================
   TOKEN
===================================================== */

$token = trim($_GET['token'] ?? '');

if (empty($token)) {

    die("
    <h2>
    Token invalide
    </h2>
    ");
}

/* =====================================================
   CHAUFFEUR
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE token=?
AND role='chauffeur'
LIMIT 1
");

$stmt->execute([
    $token
]);

$chauffeur =
$stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur) {

    die("
    <h2>
    Chauffeur introuvable
    </h2>
    ");
}

/* =====================================================
   UPDATE STATUT
===================================================== */

if (
    isset($_GET['course']) &&
    isset($_GET['action'])
) {

    $course_id =
    intval($_GET['course']);

    $action =
    trim($_GET['action']);

    if ($action === 'depart') {

        $stmt = $pdo->prepare("
        UPDATE courses
        SET statut='en cours'
        WHERE id=?
        ");

        $stmt->execute([
            $course_id
        ]);
    }

    if ($action === 'termine') {

        $stmt = $pdo->prepare("
        UPDATE courses
        SET statut='terminée'
        WHERE id=?
        ");

        $stmt->execute([
            $course_id
        ]);
    }

    header("
    Location:
    chauffeur_mobile.php?token=
    " . urlencode($token));

    exit;
}

/* =====================================================
   COURSES
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM courses
WHERE chauffeur_id=?
AND statut NOT IN (
    'terminée',
    'terminee',
    'TERMINEE'
)
ORDER BY date_course ASC,
heure_pickup ASC
");

$stmt->execute([
    $chauffeur['id']
]);

$courses =
$stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta http-equiv="refresh" content="30">
<meta
name="viewport"
content="width=device-width, initial-scale=1"
>

<title>
Espace Chauffeur
</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

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
padding:20px;
color:#111827;
}

/* =====================================================
TOP
===================================================== */

.top{
margin-bottom:25px;
}

.top h1{
font-size:30px;
color:#2563eb;
}

.top p{
margin-top:8px;
color:#6b7280;
}

/* =====================================================
CARD
===================================================== */

.card{
background:white;
border-radius:22px;
padding:22px;
margin-bottom:20px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.card h2{
font-size:20px;
margin-bottom:15px;
}

/* =====================================================
INFOS
===================================================== */

.info{
margin-bottom:10px;
font-size:15px;
}

.label{
font-weight:600;
color:#374151;
}

/* =====================================================
BADGES
===================================================== */

.badge{
display:inline-block;
padding:8px 14px;
border-radius:999px;
font-size:12px;
font-weight:600;
margin-top:10px;
}

.badge-prevu{
background:#dbeafe;
color:#1d4ed8;
}

.badge-cours{
background:#fef3c7;
color:#92400e;
}

/* =====================================================
BUTTONS
===================================================== */

.actions{
display:flex;
gap:12px;
margin-top:20px;
flex-wrap:wrap;
}

.btn{
flex:1;
padding:15px;
border-radius:14px;
text-decoration:none;
text-align:center;
font-weight:600;
font-size:15px;
}

.btn-start{
background:#2563eb;
color:white;
}

.btn-end{
background:#16a34a;
color:white;
}

.btn-gps{
background:#f59e0b;
color:white;
}

/* =====================================================
EMPTY
===================================================== */

.empty{
background:white;
padding:40px;
border-radius:22px;
text-align:center;
color:#6b7280;
box-shadow:0 5px 18px rgba(0,0,0,.05);
}

</style>

</head>

<body>

<div class="top">

<h1>
🚘 Bonjour
<?= htmlspecialchars($chauffeur['prenom'] ?? '') ?>
</h1>

<p>
Espace chauffeur Medigo
</p>

</div>

<?php if(empty($courses)): ?>

<div class="empty">

Aucune course active

</div>

<?php endif; ?>

<?php foreach($courses as $course): ?>

<?php

$statut =
$course['statut'] ?? 'prévue';

$badge =
'<span class="badge badge-prevu">
Prévue
</span>';

if($statut === 'en cours'){

    $badge =
    '<span class="badge badge-cours">
    En cours
    </span>';
}

?>

<div class="card">

<h2>

📅
<?= !empty($course['date_course'])
? date(
'd/m/Y',
strtotime($course['date_course'])
)
: '-' ?>

-
<?= htmlspecialchars($course['heure_pickup'] ?? '') ?>

</h2>

<div class="info">

<span class="label">
Patient :
</span>

<?= htmlspecialchars($course['client_nom'] ?? '') ?>

</div>

<div class="info">

<span class="label">
Départ :
</span>

<?= htmlspecialchars($course['adresse_depart'] ?? '') ?>

</div>

<div class="info">

<span class="label">
Arrivée :
</span>

<?= htmlspecialchars($course['adresse_arrivee'] ?? '') ?>

</div>

<?= $badge ?>

<div class="actions">

<a
class="btn btn-gps"
target="_blank"
href="
https://www.google.com/maps/search/?api=1&query=<?= urlencode($course['adresse_depart'] ?? '') ?>
"
>

🧭 GPS

</a>

<?php if($statut !== 'en cours'): ?>

<a
class="btn btn-start"
href="
chauffeur_mobile.php
?token=<?= urlencode($token) ?>
&course=<?= intval($course['id']) ?>
&action=depart
"
>

🚗 Départ

</a>

<?php endif; ?>

<a
class="btn btn-end"
href="
chauffeur_mobile.php
?token=<?= urlencode($token) ?>
&course=<?= intval($course['id']) ?>
&action=termine
"
>

✅ Terminée

</a>

</div>

</div>

<?php endforeach; ?>

</body>

</html>
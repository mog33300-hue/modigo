<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   SECURITE SESSION
===================================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* =====================================================
   SOCIETE
===================================================== */

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("<h2>Société invalide</h2>");
}

/* =====================================================
   FILTRES
===================================================== */

$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

/* =====================================================
   REQUETE
===================================================== */

$sql = "
SELECT
    c.*,
    u.prenom AS chauffeur
FROM courses c
LEFT JOIN users u
ON u.id = c.chauffeur_id
WHERE c.societe_id=?
AND c.statut IN (
    'terminée',
    'terminee',
    'terminé',
    'termine',
    'TERMINEE',
    'TERMINE'
)
";

$params = [$societe_id];

if (!empty($date_debut)) {
    $sql .= " AND c.date_course >= ? ";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND c.date_course <= ? ";
    $params[] = $date_fin;
}

$sql .= "
ORDER BY
c.date_course DESC,
c.heure_pickup DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   STATS
===================================================== */

$total = count($courses);

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
Historique - Medigo
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
}

.btn{
padding:14px 20px;
border-radius:14px;
text-decoration:none;
font-weight:600;
transition:.2s;
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
max-width:1700px;
margin:auto;
padding:30px;
}

.card{
background:white;
border-radius:22px;
padding:25px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
margin-bottom:30px;
}

.stats-number{
font-size:42px;
color:#2563eb;
font-weight:700;
}

.stats-text{
margin-top:8px;
color:#6b7280;
}

.filter-grid{
display:grid;
grid-template-columns:
repeat(auto-fit,minmax(220px,1fr));
gap:20px;
}

label{
display:block;
margin-bottom:8px;
font-weight:600;
color:#374151;
}

input{
width:100%;
padding:14px;
border-radius:14px;
border:1px solid #d1d5db;
background:#f9fafb;
font-size:14px;
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
text-align:left;
padding:16px;
color:#374151;
font-size:14px;
}

td{
padding:16px;
border-top:1px solid #e5e7eb;
font-size:14px;
vertical-align:top;
}

tr:hover{
background:#f9fafb;
}

.badge{
padding:7px 12px;
border-radius:999px;
font-size:12px;
font-weight:600;
background:#dcfce7;
color:#166534;
}

.empty{
padding:40px;
text-align:center;
color:#6b7280;
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
📊 Historique
</h1>

<p>
Archive des courses terminées avec horaires réels
</p>

</div>

<div class="topbar-actions">

<a href="dashboard.php" class="btn btn-dark">
🏠 Retour accueil
</a>

</div>

</div>

<div class="container">

<div class="card">

<div class="stats-number">
<?= $total ?>
</div>

<div class="stats-text">
Courses terminées
</div>

</div>

<div class="card">

<form method="GET">

<div class="filter-grid">

<div>
<label>Date début</label>
<input
type="date"
name="date_debut"
value="<?= htmlspecialchars($date_debut) ?>"
>
</div>

<div>
<label>Date fin</label>
<input
type="date"
name="date_fin"
value="<?= htmlspecialchars($date_fin) ?>"
>
</div>

<div style="display:flex;align-items:end;">

<button
type="submit"
class="btn btn-primary"
style="width:100%;"
>
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
<th>Heure prévue</th>
<th>Patient</th>
<th>Chauffeur</th>
<th>Départ réel</th>
<th>Arrivée réelle</th>
<th>Durée</th>
<th>Adresse départ</th>
<th>Adresse arrivée</th>
<th>Statut</th>
</tr>

<?php if(empty($courses)): ?>

<tr>
<td colspan="10">
<div class="empty">
Aucune course terminée
</div>
</td>
</tr>

<?php endif; ?>

<?php foreach($courses as $course): ?>

<?php
$depart_reel =
!empty($course['depart_reel'])
? substr($course['depart_reel'], 0, 5)
: '-';

$arrivee_reelle =
!empty($course['arrivee_reelle'])
? substr($course['arrivee_reelle'], 0, 5)
: '-';

$duree = '-';

if (
    !empty($course['depart_reel']) &&
    !empty($course['arrivee_reelle'])
) {
    $depart_time = strtotime($course['depart_reel']);
    $arrivee_time = strtotime($course['arrivee_reelle']);

    if ($arrivee_time >= $depart_time) {
        $diff = $arrivee_time - $depart_time;
        $heures = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);

        if ($heures > 0) {
            $duree = $heures . 'h ' . $minutes . 'min';
        } else {
            $duree = $minutes . 'min';
        }
    }
}
?>

<tr>

<td>
<?= !empty($course['date_course'])
? date('d/m/Y', strtotime($course['date_course']))
: '-' ?>
</td>

<td>
<?= htmlspecialchars(substr($course['heure_pickup'] ?? '', 0, 5)) ?>
</td>

<td>
<strong>
<?= htmlspecialchars($course['client_nom'] ?? '') ?>
</strong>
</td>

<td>
<?= htmlspecialchars($course['chauffeur'] ?? '') ?>
</td>

<td>
<?= htmlspecialchars($depart_reel) ?>
</td>

<td>
<?= htmlspecialchars($arrivee_reelle) ?>
</td>

<td>
<?= htmlspecialchars($duree) ?>
</td>

<td>
<?= htmlspecialchars($course['adresse_depart'] ?? '') ?>
</td>

<td>
<?= htmlspecialchars($course['adresse_arrivee'] ?? '') ?>
</td>

<td>
<span class="badge">
Terminée
</span>
</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</div>

</body>

</html>
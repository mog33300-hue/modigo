<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("<h2>Société invalide</h2>");
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("<h2>Course invalide</h2>");
}

/* COURSE */
$stmt = $pdo->prepare("
SELECT *
FROM courses
WHERE id=?
AND societe_id=?
LIMIT 1
");
$stmt->execute([$id, $societe_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("<h2>Course introuvable</h2>");
}

/* CHAUFFEURS */
$stmt = $pdo->prepare("
SELECT id, prenom
FROM users
WHERE role='chauffeur'
AND societe_id=?
ORDER BY prenom ASC
");
$stmt->execute([$societe_id]);
$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = "";

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date_course = trim($_POST['date_course'] ?? '');
    $heure_pickup = trim($_POST['heure_pickup'] ?? '');
    $chauffeur_id = intval($_POST['chauffeur_id'] ?? 0);
    $adresse_depart = trim($_POST['adresse_depart'] ?? '');
    $adresse_arrivee = trim($_POST['adresse_arrivee'] ?? '');
    $statut = trim($_POST['statut'] ?? 'prévu');
    $observations = trim($_POST['observations'] ?? '');

    if (empty($date_course) || empty($chauffeur_id)) {

        $error = "Veuillez choisir une date et un chauffeur.";

    } else {

        $stmt = $pdo->prepare("
        UPDATE courses
        SET
            date_course=?,
            heure_pickup=?,
            chauffeur_id=?,
            adresse_depart=?,
            adresse_arrivee=?,
            statut=?,
            observations=?
        WHERE id=?
        AND societe_id=?
        ");

        $stmt->execute([
            $date_course,
            $heure_pickup,
            $chauffeur_id,
            $adresse_depart,
            $adresse_arrivee,
            $statut,
            $observations,
            $id,
            $societe_id
        ]);

        header("Location: courses.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Modifier course - Medigo</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f3f4f6;color:#111827}

.topbar{
background:white;
padding:20px 30px;
display:flex;
justify-content:space-between;
align-items:center;
box-shadow:0 2px 10px rgba(0,0,0,.06);
}

.topbar h1{font-size:30px;color:#2563eb}

.btn{
padding:14px 20px;
border-radius:14px;
text-decoration:none;
font-weight:600;
border:none;
cursor:pointer;
font-size:14px;
display:inline-block;
}

.btn-dark{background:#111827;color:white}
.btn-primary{background:#2563eb;color:white}

.container{max-width:1000px;margin:40px auto;padding:20px}

.card{
background:white;
border-radius:24px;
padding:35px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.alert{
background:#fee2e2;
color:#991b1b;
padding:16px;
border-radius:14px;
margin-bottom:25px;
}

.form-grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
}

.form-group{display:flex;flex-direction:column}
.form-group.full{grid-column:1 / -1}

label{
margin-bottom:8px;
font-size:14px;
font-weight:600;
color:#374151;
}

input,select,textarea{
padding:16px;
border-radius:14px;
border:1px solid #d1d5db;
font-size:15px;
background:#f9fafb;
}

textarea{
min-height:120px;
resize:vertical;
}

.btn-submit{
width:100%;
margin-top:30px;
border:none;
padding:18px;
border-radius:16px;
background:linear-gradient(135deg,#2563eb,#1d4ed8);
color:white;
font-size:16px;
font-weight:600;
cursor:pointer;
}

.info-box{
background:#f9fafb;
border:1px solid #e5e7eb;
padding:18px;
border-radius:16px;
margin-bottom:25px;
}

@media(max-width:768px){
.form-grid{grid-template-columns:1fr}
.topbar{flex-direction:column;gap:15px;align-items:flex-start}
}
</style>
</head>

<body>

<div class="topbar">
<h1>✏ Modifier course</h1>
<a href="courses.php" class="btn btn-dark">⬅ Retour courses</a>
</div>

<div class="container">

<div class="card">

<div class="info-box">
<strong>Patient :</strong>
<?= htmlspecialchars($course['client_nom'] ?? '') ?>
<br>
<strong>Course ID :</strong>
<?= intval($course['id']) ?>
</div>

<?php if($error): ?>
<div class="alert">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-grid">

<div class="form-group">
<label>Date course *</label>
<input
type="date"
name="date_course"
value="<?= htmlspecialchars($course['date_course'] ?? '') ?>"
required
>
</div>

<div class="form-group">
<label>Heure pickup</label>
<input
type="time"
name="heure_pickup"
value="<?= htmlspecialchars(substr($course['heure_pickup'] ?? '', 0, 5)) ?>"
>
</div>

<div class="form-group full">
<label>Chauffeur *</label>
<select name="chauffeur_id" required>
<option value="">Choisir un chauffeur</option>

<?php foreach($chauffeurs as $chauffeur): ?>
<option
value="<?= intval($chauffeur['id']) ?>"
<?= intval($course['chauffeur_id'] ?? 0) === intval($chauffeur['id']) ? 'selected' : '' ?>
>
<?= htmlspecialchars($chauffeur['prenom']) ?>
</option>
<?php endforeach; ?>

</select>
</div>

<div class="form-group full">
<label>Adresse départ</label>
<input
type="text"
name="adresse_depart"
value="<?= htmlspecialchars($course['adresse_depart'] ?? '') ?>"
>
</div>

<div class="form-group full">
<label>Adresse arrivée</label>
<input
type="text"
name="adresse_arrivee"
value="<?= htmlspecialchars($course['adresse_arrivee'] ?? '') ?>"
>
</div>

<div class="form-group">
<label>Statut</label>
<select name="statut">
<option value="prévu" <?= ($course['statut'] ?? '') === 'prévu' ? 'selected' : '' ?>>Prévu</option>
<option value="prévue" <?= ($course['statut'] ?? '') === 'prévue' ? 'selected' : '' ?>>Prévue</option>
<option value="en cours" <?= ($course['statut'] ?? '') === 'en cours' ? 'selected' : '' ?>>En cours</option>
<option value="terminé" <?= ($course['statut'] ?? '') === 'terminé' ? 'selected' : '' ?>>Terminé</option>
<option value="terminée" <?= ($course['statut'] ?? '') === 'terminée' ? 'selected' : '' ?>>Terminée</option>
</select>
</div>

<div class="form-group full">
<label>Observations</label>
<textarea name="observations"><?= htmlspecialchars($course['observations'] ?? '') ?></textarea>
</div>

</div>

<button type="submit" class="btn-submit">
💾 Enregistrer les modifications
</button>

</form>

</div>

</div>

</body>
</html>
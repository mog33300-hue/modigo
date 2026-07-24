<?php
require 'auth.php';
require 'config.php';

$societe_id = intval($_SESSION['societe_id'] ?? 1);

/* PATIENTS */
$stmt = $pdo->prepare("
SELECT *
FROM patients
WHERE societe_id=?
ORDER BY nom ASC
");
$stmt->execute([$societe_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* CHAUFFEURS */
$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE role='chauffeur'
AND societe_id=?
ORDER BY prenom ASC
");
$stmt->execute([$societe_id]);
$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* VEHICULES */
$stmt = $pdo->prepare("
SELECT *
FROM vehicles
WHERE company_id=?
ORDER BY plate ASC
");
$stmt->execute([$societe_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = "";

/* CREATION COURSE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_id = intval($_POST['patient_id'] ?? 0);
    $chauffeur_id = intval($_POST['chauffeur_id'] ?? 0);
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);

    $date_course = trim($_POST['date_course'] ?? '');
    $heure_pickup = trim($_POST['heure_pickup'] ?? '');

    $adresse_depart = trim($_POST['adresse_depart'] ?? '');
    $ville_depart = trim($_POST['ville_depart'] ?? '');

    $adresse_arrivee = trim($_POST['adresse_arrivee'] ?? '');
    $ville_arrivee = trim($_POST['ville_arrivee'] ?? '');

    $statut = trim($_POST['statut'] ?? 'prévue');
    $observations = trim($_POST['observations'] ?? '');

    if (
        empty($patient_id) ||
        empty($chauffeur_id) ||
        empty($date_course)
    ) {
        $error = "Veuillez sélectionner un patient, un chauffeur et une date.";
    } else {

        try {

            $stmt = $pdo->prepare("
            SELECT
                prenom,
                nom,
                telephone
            FROM patients
            WHERE id=?
            AND societe_id=?
            LIMIT 1
            ");
            $stmt->execute([$patient_id, $societe_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$patient) {
                throw new Exception("Patient introuvable.");
            }

            $client_nom = trim(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? ''));
            $telephone = trim($patient['telephone'] ?? '');

            $stmt = $pdo->prepare("
            INSERT INTO courses
            (
                societe_id,
                patient_id,
                client_nom,
                telephone,
                chauffeur_id,
                vehicle_id,
                date_course,
                heure_pickup,
                adresse_depart,
                ville_depart,
                adresse_arrivee,
                ville_arrivee,
                statut,
                observations,
                created_at
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
            ");

            $stmt->execute([
                $societe_id,
                $patient_id,
                $client_nom,
                $telephone,
                $chauffeur_id,
                $vehicle_id,
                $date_course,
                $heure_pickup,
                $adresse_depart,
                $ville_depart,
                $adresse_arrivee,
                $ville_arrivee,
                $statut,
                $observations
            ]);

            header("Location: courses.php");
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>MODIGO - Nouvelle course</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}

:root{
    --glass:rgba(255,255,255,.12);
    --glass2:rgba(255,255,255,.16);
    --border:rgba(255,255,255,.18);
    --muted:#cbd5e1;
    --blue:#2563eb;
    --green:#22c55e;
    --orange:#f59e0b;
    --red:#ef4444;
    --shadow:0 30px 80px rgba(0,0,0,.35);
}

body{
    font-family:'Inter',sans-serif;
    min-height:100vh;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.25), transparent 34%),
        radial-gradient(circle at bottom right, rgba(14,165,233,.22), transparent 28%),
        linear-gradient(135deg,#0f172a,#1e3a8a);
    color:white;
}

.app{display:grid;grid-template-columns:280px 1fr;min-height:100vh}

.sidebar{
    padding:28px 18px;
    background:rgba(15,23,42,.45);
    border-right:1px solid var(--border);
    backdrop-filter:blur(18px);
}

.brand{display:flex;align-items:center;gap:14px;padding:12px 12px 26px;border-bottom:1px solid var(--border);margin-bottom:24px}
.brand-icon{width:58px;height:58px;border-radius:20px;display:flex;align-items:center;justify-content:center;background:var(--glass2);font-size:31px}
.brand h1{font-size:28px;letter-spacing:.5px}
.brand p{color:var(--muted);font-size:12px;font-weight:700;margin-top:5px}

.nav{display:grid;gap:8px}
.nav a{display:flex;align-items:center;gap:12px;color:#dbeafe;text-decoration:none;padding:14px 16px;border-radius:16px;font-weight:800;transition:.2s}
.nav a:hover,.nav a.active{background:var(--glass2);color:white;transform:translateX(4px)}
.logout{margin-top:25px;background:rgba(239,68,68,.18)!important}

.main{padding:34px}

.topbar{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:28px}
.topbar h1{font-size:38px;margin-bottom:7px}
.topbar p{color:var(--muted);font-weight:600}
.topbar-actions{display:flex;gap:12px;flex-wrap:wrap}

.btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:13px 17px;
    border-radius:15px;
    text-decoration:none;
    font-weight:900;
    border:1px solid transparent;
    cursor:pointer;
    transition:.2s;
    font-size:13px;
}
.btn:hover{transform:translateY(-2px)}
.btn-white{background:white;color:#1e3a8a}
.btn-glass{background:rgba(255,255,255,.12);color:white;border-color:var(--border)}
.btn-green{background:#dcfce7;color:#166534}

.hero{
    background:var(--glass);
    border:1px solid var(--border);
    border-radius:34px;
    padding:30px;
    box-shadow:var(--shadow);
    backdrop-filter:blur(20px);
    margin-bottom:25px;
}

.badge-title{
    display:inline-block;
    background:rgba(255,255,255,.14);
    border:1px solid var(--border);
    padding:9px 13px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
    margin-bottom:15px;
}

.hero h2{font-size:34px;line-height:1.1;margin-bottom:10px}
.hero p{color:#dbeafe;font-size:16px;line-height:1.6}

.alert{
    border-radius:20px;
    padding:16px 18px;
    margin-bottom:18px;
    font-weight:800;
    background:#fee2e2;
    color:#991b1b;
}

.form-layout{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
}

.section{
    background:var(--glass);
    border:1px solid var(--border);
    border-radius:30px;
    padding:26px;
    backdrop-filter:blur(18px);
    box-shadow:0 20px 50px rgba(0,0,0,.22);
}

.section.full{
    grid-column:1 / -1;
}

.section h2{
    font-size:24px;
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:10px;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.form-group.full{
    grid-column:1 / -1;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:900;
    color:#dbeafe;
    font-size:13px;
}

input,select,textarea{
    width:100%;
    padding:14px;
    border-radius:16px;
    border:1px solid var(--border);
    background:rgba(255,255,255,.13);
    color:white;
    font-size:14px;
    outline:none;
    transition:.2s;
}

input::placeholder,textarea::placeholder{
    color:#cbd5e1;
}

input:focus,select:focus,textarea:focus{
    border-color:white;
    box-shadow:0 0 0 4px rgba(255,255,255,.10);
}

select option{
    color:#0f172a;
}

textarea{
    min-height:130px;
    resize:vertical;
}

.actions-bottom{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-top:24px;
    flex-wrap:wrap;
}

.submit{
    min-width:240px;
    padding:17px 24px;
    font-size:15px;
}

.help{
    color:#cbd5e1;
    font-weight:700;
    font-size:13px;
}

.footer{text-align:center;color:#cbd5e1;padding:25px;font-size:13px}

@media(max-width:1150px){
    .app{grid-template-columns:1fr}
    .sidebar{position:relative}
    .form-layout{grid-template-columns:1fr}
}

@media(max-width:760px){
    .main{padding:18px}
    .topbar{flex-direction:column;align-items:flex-start}
    .topbar-actions{width:100%;display:grid;grid-template-columns:1fr}
    .btn{width:100%}
    .hero{padding:22px;border-radius:26px}
    .hero h2{font-size:28px}
    .section{padding:20px;border-radius:24px}
    .form-grid{grid-template-columns:1fr}
    .form-group.full{grid-column:auto}
    .actions-bottom{display:grid;grid-template-columns:1fr;width:100%}
    .submit{min-width:0;width:100%}
    .nav{grid-template-columns:1fr 1fr;display:grid}
    .nav a{font-size:13px;padding:12px}
}
</style>
</head>

<body>

<div class="app">

<aside class="sidebar">
<div class="brand">
    <div class="brand-icon">🚑</div>
    <div>
        <h1>MODIGO</h1>
        <p>Transport sanitaire intelligent</p>
    </div>
</div>

<nav class="nav">
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="courses.php">🚗 Courses</a>
    <a href="create_course.php" class="active">➕ Nouvelle course</a>
    <a href="patients.php">👥 Patients</a>
    <a href="chauffeurs.php">🚘 Chauffeurs</a>
    <a href="vehicles.php">🚐 Véhicules</a>
    <a href="planning_global.php">📅 Planning</a>
    <a href="gps_admin.php">📍 GPS</a>
    <a href="logout.php" class="logout">🚪 Déconnexion</a>
</nav>
</aside>

<main class="main">

<div class="topbar">
    <div>
        <h1>Nouvelle course</h1>
        <p>Création d'une mission de transport sanitaire MODIGO</p>
    </div>

    <div class="topbar-actions">
        <a href="dashboard.php" class="btn btn-glass">🏠 Dashboard</a>
        <a href="courses.php" class="btn btn-white">⬅ Retour courses</a>
    </div>
</div>

<section class="hero">
    <div class="badge-title">Assistant de création</div>
    <h2>Créer une course</h2>
    <p>
        Sélectionnez le patient, renseignez le trajet, affectez un chauffeur
        et un véhicule, puis enregistrez la mission.
    </p>
</section>

<?php if($error): ?>
<div class="alert">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-layout">

<section class="section">
<h2>👤 Patient</h2>

<div class="form-grid">

<div class="form-group full">
<label>Patient *</label>
<select name="patient_id" required>
<option value="">Sélectionner un patient</option>

<?php foreach($patients as $patient): ?>
<option value="<?= intval($patient['id']) ?>">
<?= htmlspecialchars(trim(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? ''))) ?>
<?php if(!empty($patient['telephone'])): ?>
 — <?= htmlspecialchars($patient['telephone']) ?>
<?php endif; ?>
</option>
<?php endforeach; ?>

</select>
</div>

</div>
</section>

<section class="section">
<h2>📅 Date et heure</h2>

<div class="form-grid">

<div class="form-group">
<label>Date course *</label>
<input type="date" name="date_course" required>
</div>

<div class="form-group">
<label>Heure pickup</label>
<input type="time" name="heure_pickup">
</div>

<div class="form-group full">
<label>Statut</label>
<select name="statut">
<option value="prévue">Prévue</option>
<option value="en cours">En cours</option>
<option value="terminée">Terminée</option>
</select>
</div>

</div>
</section>

<section class="section">
<h2>📍 Départ</h2>

<div class="form-grid">

<div class="form-group full">
<label>Adresse départ</label>
<input type="text" name="adresse_depart" placeholder="Adresse de départ">
</div>

<div class="form-group full">
<label>Ville départ</label>
<input type="text" name="ville_depart" placeholder="Ville de départ">
</div>

</div>
</section>

<section class="section">
<h2>🎯 Arrivée</h2>

<div class="form-grid">

<div class="form-group full">
<label>Adresse arrivée</label>
<input type="text" name="adresse_arrivee" placeholder="Adresse d'arrivée">
</div>

<div class="form-group full">
<label>Ville arrivée</label>
<input type="text" name="ville_arrivee" placeholder="Ville d'arrivée">
</div>

</div>
</section>

<section class="section">
<h2>🚘 Affectation</h2>

<div class="form-grid">

<div class="form-group">
<label>Chauffeur *</label>
<select name="chauffeur_id" required>
<option value="">Choisir un chauffeur</option>

<?php foreach($chauffeurs as $chauffeur): ?>
<option value="<?= intval($chauffeur['id']) ?>">
<?= htmlspecialchars(trim(($chauffeur['prenom'] ?? '') . ' ' . ($chauffeur['nom'] ?? ''))) ?>
</option>
<?php endforeach; ?>

</select>
</div>

<div class="form-group">
<label>Véhicule</label>
<select name="vehicle_id">
<option value="">Choisir un véhicule</option>

<?php foreach($vehicles as $vehicle): ?>
<option value="<?= intval($vehicle['id']) ?>">
<?= htmlspecialchars($vehicle['plate'] ?? '') ?>
<?php if(!empty($vehicle['name'])): ?>
 - <?= htmlspecialchars($vehicle['name']) ?>
<?php endif; ?>
</option>
<?php endforeach; ?>

</select>
</div>

</div>
</section>

<section class="section">
<h2>📝 Observations</h2>

<div class="form-grid">

<div class="form-group full">
<label>Observations</label>
<textarea name="observations" placeholder="Informations utiles pour le chauffeur, PMR, étage, accompagnant, consignes..."></textarea>
</div>

</div>
</section>

</div>

<div class="actions-bottom">

<div class="help">
Les champs Patient, Chauffeur et Date sont obligatoires.
</div>

<button type="submit" class="btn btn-white submit">
💾 Créer la course
</button>

</div>

</form>

<div class="footer">
MODIGO — Création de course
</div>

</main>
</div>

</body>
</html>

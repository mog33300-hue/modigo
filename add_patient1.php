<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   SOCIETE
===================================================== */

$societe_id = $_SESSION['societe_id'] ?? 1;

/* =====================================================
   MESSAGE
===================================================== */

$message = "";
$error = "";

/* =====================================================
   AJOUT PATIENT
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $securite_sociale = trim($_POST['securite_sociale'] ?? '');
    $observations = trim($_POST['observations'] ?? '');

    if (
        empty($prenom) ||
        empty($nom)
    ) {

        $error = "Veuillez remplir les champs obligatoires.";

    } else {

        try {

            $stmt = $pdo->prepare("
            INSERT INTO patients
            (
                prenom,
                nom,
                telephone,
                adresse,
                ville,
                securite_sociale,
                observations,
                societe_id,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
            ");

            $stmt->execute([
                $prenom,
                $nom,
                $telephone,
                $adresse,
                $ville,
                $securite_sociale,
                $observations,
                $societe_id
            ]);

            header("Location: patients.php");
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

<meta
name="viewport"
content="width=device-width, initial-scale=1"
>

<title>
Nouveau patient - Medigo
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

/* =====================================================
   TOPBAR
===================================================== */

.topbar{

    background:white;

    padding:20px 30px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    box-shadow:0 2px 10px rgba(0,0,0,0.06);
}

.topbar h1{

    font-size:30px;

    color:#2563eb;
}

.btn-return{

    background:#111827;

    color:white;

    padding:14px 20px;

    border-radius:14px;

    text-decoration:none;

    font-weight:600;
}

/* =====================================================
   CONTAINER
===================================================== */

.container{

    max-width:900px;

    margin:40px auto;

    padding:20px;
}

/* =====================================================
   CARD
===================================================== */

.card{

    background:white;

    border-radius:24px;

    padding:35px;

    box-shadow:0 5px 18px rgba(0,0,0,0.05);
}

/* =====================================================
   ALERTS
===================================================== */

.alert{

    padding:16px;

    border-radius:14px;

    margin-bottom:25px;

    font-size:14px;
}

.alert-error{

    background:#fee2e2;

    color:#991b1b;
}

.alert-success{

    background:#dcfce7;

    color:#166534;
}

/* =====================================================
   FORM
===================================================== */

.form-grid{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:20px;
}

.form-group{

    display:flex;

    flex-direction:column;
}

.form-group.full{

    grid-column:1 / -1;
}

label{

    margin-bottom:8px;

    font-size:14px;

    font-weight:600;

    color:#374151;
}

input,
textarea{

    padding:16px;

    border-radius:14px;

    border:1px solid #d1d5db;

    font-size:15px;

    transition:0.2s;

    background:#f9fafb;
}

input:focus,
textarea:focus{

    outline:none;

    border-color:#2563eb;

    background:white;

    box-shadow:0 0 0 4px rgba(37,99,235,0.12);
}

textarea{

    min-height:120px;

    resize:vertical;
}

/* =====================================================
   BUTTON
===================================================== */

.btn-submit{

    margin-top:30px;

    width:100%;

    border:none;

    padding:18px;

    border-radius:16px;

    background:linear-gradient(135deg,#2563eb,#1d4ed8);

    color:white;

    font-size:16px;

    font-weight:600;

    cursor:pointer;

    transition:0.2s;
}

.btn-submit:hover{

    transform:translateY(-2px);

    box-shadow:0 10px 20px rgba(37,99,235,0.25);
}

/* =====================================================
   MOBILE
===================================================== */

@media(max-width:768px){

    .form-grid{

        grid-template-columns:1fr;
    }

    .topbar{

        flex-direction:column;

        gap:15px;

        align-items:flex-start;
    }
}

</style>

</head>

<body>

<!-- TOPBAR -->

<div class="topbar">

<h1>
➕ Nouveau patient
</h1>

<a href="patients.php" class="btn-return">

⬅ Retour patients

</a>

</div>

<!-- CONTENT -->

<div class="container">

<div class="card">

<?php if($error): ?>

<div class="alert alert-error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<?php if($message): ?>

<div class="alert alert-success">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<form method="POST">

<div class="form-grid">

<div class="form-group">

<label>Prénom *</label>

<input
type="text"
name="prenom"
required
>

</div>

<div class="form-group">

<label>Nom *</label>

<input
type="text"
name="nom"
required
>

</div>

<div class="form-group">

<label>Téléphone</label>

<input
type="text"
name="telephone"
>

</div>

<div class="form-group">

<label>Ville</label>

<input
type="text"
name="ville"
>

</div>

<div class="form-group full">

<label>Adresse</label>

<input
type="text"
name="adresse"
>

</div>

<div class="form-group">

<label>Sécurité sociale</label>

<input
type="text"
name="securite_sociale"
>

</div>

<div class="form-group full">

<label>Observations</label>

<textarea
name="observations"
></textarea>

</div>

</div>

<button type="submit" class="btn-submit">

💾 Enregistrer le patient

</button>

</form>

</div>

</div>

</body>

</html>
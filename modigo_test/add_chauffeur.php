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

$success = "";
$error = "";

/* =====================================================
   AJOUT CHAUFFEUR
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom = trim($_POST['prenom'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $telephone = trim($_POST['telephone'] ?? '');

    $password = trim($_POST['password'] ?? '');

    $statut = trim($_POST['statut'] ?? 'actif');

    if (
        empty($prenom) ||
        empty($email) ||
        empty($password)
    ) {

        $error =
        "Veuillez remplir tous les champs obligatoires.";

    } else {

        /* EMAIL EXISTE */

        $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email=?
        LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);

        if ($stmt->fetch()) {

            $error =
            "Cet email existe déjà.";

        } else {

            /* INSERT */

            $hash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
            INSERT INTO users (
                prenom,
                email,
                telephone,
                password,
                role,
                statut,
                societe_id
            ) VALUES (
                ?,?,?,?,?,?,?
            )
            ");

            $stmt->execute([
                $prenom,
                $email,
                $telephone,
                $hash,
                'chauffeur',
                $statut,
                $societe_id
            ]);

            $success =
            "Chauffeur ajouté avec succès.";
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
Ajouter Chauffeur - Medigo
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

/* =====================================================
   BUTTONS
===================================================== */

.btn{
    padding:14px 20px;
    border-radius:14px;
    text-decoration:none;
    font-weight:600;
    transition:0.2s;
    border:none;
    cursor:pointer;
    font-size:14px;
}

.btn-dark{
    background:#111827;
    color:white;
}

.btn-dark:hover{
    background:#1f2937;
}

.btn-primary{
    background:#2563eb;
    color:white;
}

.btn-primary:hover{
    background:#1d4ed8;
}

/* =====================================================
   CONTAINER
===================================================== */

.container{
    max-width:800px;
    margin:auto;
    padding:30px;
}

/* =====================================================
   CARD
===================================================== */

.card{
    background:white;
    border-radius:22px;
    padding:30px;
    box-shadow:0 5px 18px rgba(0,0,0,0.05);
}

/* =====================================================
   FORM
===================================================== */

.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:#374151;
}

input,
select{
    width:100%;
    padding:15px;
    border-radius:14px;
    border:1px solid #d1d5db;
    background:#f9fafb;
    font-size:15px;
}

input:focus,
select:focus{
    outline:none;
    border-color:#2563eb;
    background:white;
}

/* =====================================================
   ALERTS
===================================================== */

.alert-success{
    background:#dcfce7;
    color:#166534;
    padding:15px;
    border-radius:14px;
    margin-bottom:20px;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    padding:15px;
    border-radius:14px;
    margin-bottom:20px;
}

/* =====================================================
   MOBILE
===================================================== */

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

<!-- TOPBAR -->

<div class="topbar">

<div class="topbar-left">

<h1>
🚘 Nouveau Chauffeur
</h1>

<p>
Ajouter un chauffeur Medigo
</p>

</div>

<div class="topbar-actions">

<a href="chauffeurs.php" class="btn btn-dark">
🏠 Retour chauffeurs
</a>

</div>

</div>

<!-- CONTENT -->

<div class="container">

<div class="card">

<?php if($success): ?>

<div class="alert-success">

<?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>

<?php if($error): ?>

<div class="alert-error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<form method="POST">

<div class="form-group">

<label>
Prénom
</label>

<input
type="text"
name="prenom"
required
>

</div>

<div class="form-group">

<label>
Email
</label>

<input
type="email"
name="email"
required
>

</div>

<div class="form-group">

<label>
Téléphone
</label>

<input
type="text"
name="telephone"
>

</div>

<div class="form-group">

<label>
Mot de passe
</label>

<input
type="password"
name="password"
required
>

</div>

<div class="form-group">

<label>
Statut
</label>

<select name="statut">

<option value="actif">
Actif
</option>

<option value="inactif">
Inactif
</option>

</select>

</div>

<button
type="submit"
class="btn btn-primary"
style="width:100%;"
>

💾 Ajouter le chauffeur

</button>

</form>

</div>

</div>

</body>

</html>
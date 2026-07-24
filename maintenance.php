<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $confirmation = trim($_POST['confirmation'] ?? '');

    if ($confirmation !== 'SUPPRIMER') {

        $erreur = "Vous devez taper exactement SUPPRIMER";

    } else {

        try {

            $pdo->beginTransaction();

            /* COURSES */

            $stmt = $pdo->prepare("
            DELETE FROM courses
            WHERE societe_id=?
            ");

            $stmt->execute([$societe_id]);

            /* PATIENTS */

            $stmt = $pdo->prepare("
            DELETE FROM patients
            WHERE societe_id=?
            ");

            $stmt->execute([$societe_id]);

            $pdo->commit();

            $message =
            "Toutes les données ont été supprimées.";

        } catch(Exception $e){

            $pdo->rollBack();

            $erreur =
            "Erreur : ".$e->getMessage();
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
Maintenance
</title>

<link rel="stylesheet" href="style.css">

<style>

.warning{

background:#fee2e2;
border:2px solid #dc2626;
color:#991b1b;
padding:20px;
border-radius:14px;
margin-bottom:20px;

}

.success{

background:#dcfce7;
border:2px solid #16a34a;
color:#166534;
padding:20px;
border-radius:14px;
margin-bottom:20px;

}

.form-box{

background:white;
padding:25px;
border-radius:14px;
max-width:700px;

}

.btn-danger{

background:#dc2626;
color:white;
padding:14px 20px;
border:none;
border-radius:10px;
font-weight:bold;
cursor:pointer;

}

</style>

</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<h1>
🧹 Maintenance
</h1>

<?php if($message): ?>

<div class="success">
<?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>

<?php if($erreur): ?>

<div class="warning">
<?= htmlspecialchars($erreur) ?>
</div>

<?php endif; ?>

<div class="form-box">

<div class="warning">

⚠️ ATTENTION

<br><br>

Cette opération supprimera :

<ul>
<li>Toutes les courses</li>
<li>Tous les patients</li>
<li>Tout l'historique</li>
</ul>

<br>

Les utilisateurs seront conservés.

</div>

<form method="post">

<label>

Tapez :

<b>SUPPRIMER</b>

pour confirmer

</label>

<input
type="text"
name="confirmation"
required
>

<br><br>

<button
type="submit"
class="btn-danger"
onclick="return confirm('Confirmer la suppression définitive ?');"
>

🗑 Vider toutes les données

</button>

</form>

</div>

</div>

</body>
</html>
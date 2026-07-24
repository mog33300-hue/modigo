<?php
require 'auth.php';
require 'config.php';

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $observations = trim($_POST['observations'] ?? '');

    if (empty($prenom) || empty($nom)) {

        $error = "Le prénom et le nom sont obligatoires.";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO patients
            (
                prenom,
                nom,
                telephone,
                adresse,
                ville,
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
                NOW()
            )
        ");

        $stmt->execute([
            $prenom,
            $nom,
            $telephone,
            $adresse,
            $ville,
            $observations,
            $societe_id
        ]);

        header("Location: patients.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Nouveau patient - Medigo</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<h1>➕ Nouveau patient</h1>

<?php if($error): ?>
<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:10px;margin-bottom:20px;">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="form-card">

<form method="POST">

<label>Prénom *</label>
<input type="text" name="prenom" required>

<label>Nom *</label>
<input type="text" name="nom" required>

<label>Téléphone</label>
<input type="text" name="telephone">

<label>Adresse</label>
<input type="text" name="adresse">

<label>Ville</label>
<input type="text" name="ville">

<label>Observations</label>
<textarea name="observations"></textarea>

<button type="submit" class="btn btn-add">
💾 Enregistrer
</button>

<a href="patients.php" class="btn btn-back">
⬅ Retour
</a>

</form>

</div>

</div>

</body>
</html>
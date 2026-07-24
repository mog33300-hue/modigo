<?php
require 'auth.php';
require 'config.php';

$message = "";
$error = "";

/* =====================================================
   SECURITE SOCIETE
===================================================== */

if (!isset($_SESSION['societe_id'])) {

    die("Session société introuvable");

}

$societe_id = intval($_SESSION['societe_id']);

/* =====================================================
   ID PATIENT
===================================================== */

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {

    header("Location: patients.php");
    exit;
}

/* =====================================================
   RECUP PATIENT
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM patients
WHERE id=?
AND societe_id=?
LIMIT 1
");

$stmt->execute([
    $id,
    $societe_id
]);

$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {

    die("Patient introuvable ou accès refusé");
}

/* =====================================================
   UPDATE
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom          = trim($_POST['nom'] ?? '');
    $telephone    = trim($_POST['telephone'] ?? '');
    $telephone2   = trim($_POST['telephone2'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $adresse      = trim($_POST['adresse'] ?? '');
    $code_postal  = trim($_POST['code_postal'] ?? '');
    $ville        = trim($_POST['ville'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    if (empty($nom)) {

        $error = "Le nom du patient est obligatoire.";

    } else {

        $stmt = $pdo->prepare("
        UPDATE patients
        SET
            nom=?,
            telephone=?,
            telephone2=?,
            email=?,
            adresse=?,
            code_postal=?,
            ville=?,
            notes=?
        WHERE id=?
        AND societe_id=?
        ");

        $stmt->execute([

            $nom,
            $telephone,
            $telephone2,
            $email,
            $adresse,
            $code_postal,
            $ville,
            $notes,

            $id,
            $societe_id
        ]);

        $message = "Patient modifié avec succès";

        /* refresh */

        $stmt = $pdo->prepare("
        SELECT *
        FROM patients
        WHERE id=?
        AND societe_id=?
        LIMIT 1
        ");

        $stmt->execute([
            $id,
            $societe_id
        ]);

        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
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
Medigo - Modifier patient
</title>

<link rel="stylesheet" href="style.css">

<style>

.form-card{

    background:white;

    border-radius:14px;

    padding:20px;

    box-shadow:0 2px 8px rgba(0,0,0,0.06);
}

.alert{

    background:#dcfce7;

    color:#166534;

    padding:12px;

    border-radius:8px;

    margin-bottom:20px;
}

.alert-error{

    background:#fee2e2;

    color:#991b1b;

    padding:12px;

    border-radius:8px;

    margin-bottom:20px;
}

</style>

</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<a href="patients.php" class="btn btn-back">
⬅ Retour
</a>

<h1>
✏ Modifier patient
</h1>

<?php if($message): ?>

<div class="alert">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<?php if($error): ?>

<div class="alert-error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<div class="form-card">

<form method="POST">

<label>
Nom du patient *
</label>

<input
type="text"
name="nom"
required
value="<?= htmlspecialchars($patient['nom'] ?? '') ?>"
>

<label>
Téléphone
</label>

<input
type="text"
name="telephone"
value="<?= htmlspecialchars($patient['telephone'] ?? '') ?>"
>

<label>
Téléphone 2
</label>

<input
type="text"
name="telephone2"
value="<?= htmlspecialchars($patient['telephone2'] ?? '') ?>"
>

<label>
Email
</label>

<input
type="email"
name="email"
value="<?= htmlspecialchars($patient['email'] ?? '') ?>"
>

<label>
Adresse
</label>

<input
type="text"
name="adresse"
value="<?= htmlspecialchars($patient['adresse'] ?? '') ?>"
>

<label>
Code postal
</label>

<input
type="text"
name="code_postal"
id="code_postal"
value="<?= htmlspecialchars($patient['code_postal'] ?? '') ?>"
>

<label>
Ville
</label>

<input
type="text"
name="ville"
id="ville"
value="<?= htmlspecialchars($patient['ville'] ?? '') ?>"
>

<label>
Notes
</label>

<textarea
name="notes"
rows="4"
><?= htmlspecialchars($patient['notes'] ?? '') ?></textarea>

<div class="actions">

<button
type="submit"
class="btn btn-edit"
>
💾 Enregistrer
</button>

</div>

</form>

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

<!-- AUTO VILLE -->

<script>

document
.getElementById('code_postal')
.addEventListener('blur', function(){

    let cp = this.value;

    if(cp.length >= 4){

        fetch(
        'https://geo.api.gouv.fr/communes?codePostal='
        + cp +
        '&fields=nom'
        )

        .then(response => response.json())

        .then(data => {

            if(data.length > 0){

                document
                .getElementById('ville')
                .value = data[0].nom;
            }

        });

    }

});

</script>

</body>

</html>
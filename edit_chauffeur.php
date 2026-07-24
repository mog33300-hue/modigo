# edit_chauffeur.php

```php
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

$societe_id =
intval($_SESSION['societe_id'] ?? 1);

/* =====================================================
   ID
===================================================== */

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {

    die("Chauffeur invalide");
}

/* =====================================================
   CHAUFFEUR
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE id=?
AND role='chauffeur'
AND societe_id=?
LIMIT 1
");

$stmt->execute([
    $id,
    $societe_id
]);

$chauffeur =
$stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur) {

    die("Chauffeur introuvable");
}

/* =====================================================
   MESSAGE
===================================================== */

$success = "";
$error = "";

/* =====================================================
   UPDATE
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom =
    trim($_POST['prenom'] ?? '');

    $email =
    trim($_POST['email'] ?? '');

    $telephone =
    trim($_POST['telephone'] ?? '');

    $vehicule =
    trim($_POST['vehicule'] ?? '');

    $password =
    trim($_POST['password'] ?? '');

    if (
        empty($prenom) ||
        empty($email)
    ) {

        $error =
        "Veuillez remplir les champs obligatoires.";

    } else {

        /* EMAIL EXISTE */

        $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email=?
        AND id!=?
        LIMIT 1
        ");

        $stmt->execute([
            $email,
            $id
        ]);

        if ($stmt->fetch()) {

            $error =
            "Cet email existe déjà.";

        } else {

            /* PASSWORD */

            if (!empty($password)) {

                $hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                UPDATE users SET
                    prenom=?,
                    email=?,
                    telephone=?,
                    vehicule=?,
                    password=?
                WHERE id=?
                AND societe_id=?
                ");

                $stmt->execute([
                    $prenom,
                    $email,
                    $telephone,
                    $vehicule,
                    $hash,
                    $id,
                    $societe_id
                ]);

            } else {

                $stmt = $pdo->prepare("
                UPDATE users SET
                    prenom=?,
                    email=?,
                    telephone=?,
                    vehicule=?
                WHERE id=?
                AND societe_id=?
                ");

                $stmt->execute([
                    $prenom,
                    $email,
                    $telephone,
                    $vehicule,
                    $id,
                    $societe_id
                ]);
            }

            header("Location: chauffeurs.php");
            exit;
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
Modifier chauffeur - Medigo
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

.btn{
padding:14px 20px;
border-radius:14px;
text-decoration:none;
font-weight:600;
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
max-width:900px;
margin:auto;
padding:30px;
}

.card{
background:white;
border-radius:22px;
padding:30px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.form-grid{
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

.alert-error{
background:#fee2e2;
color:#991b1b;
padding:15px;
border-radius:14px;
margin-bottom:20px;
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
✏ Modifier chauffeur
</h1>

<p>
Modification des informations chauffeur
</p>

</div>

<a href="chauffeurs.php" class="btn btn-dark">
🏠 Retour chauffeurs
</a>

</div>

<div class="container">

<div class="card">

<?php if($error): ?>

<div class="alert-error">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>

<form method="POST">

<div class="form-grid">

<div>
<label>
Prénom
</label>
<input
type="text"
name="prenom"
value="<?= htmlspecialchars($chauffeur['prenom'] ?? '') ?>"
required
>
</div>

<div>
<label>
Email
</label>
<input
type="email"
name="email"
value="<?= htmlspecialchars($chauffeur['email'] ?? '') ?>"
required
>
</div>

<div>
<label>
Téléphone
</label>
<input
type="text"
name="telephone"
value="<?= htmlspecialchars($chauffeur['telephone'] ?? '') ?>"
>
</div>

<div>
<label>
Véhicule
</label>
<input
type="text"
name="vehicule"
value="<?= htmlspecialchars($chauffeur['vehicule'] ?? '') ?>"
>
</div>

<div>
<label>
Nouveau mot de passe
</label>
<input
type="password"
name="password"
placeholder="Laisser vide pour conserver"
>
</div>

</div>

<button
type="submit"
class="btn btn-primary"
style="margin-top:25px;"
>
💾 Enregistrer modifications
</button>

</form>

</div>

</div>

</body>
</html>
```

# Modification à faire dans chauffeurs.php

Cherche :

```php
<th>Actions</th>
```

Puis ajoute ce bouton AVANT supprimer :

```php
<a
href="edit_chauffeur.php?id=<?= intval($chauffeur['id']) ?>"
class="btn-pdf"
style="background:#dbeafe;color:#1d4ed8;display:inline-block;margin-bottom:8px;"
>
✏ Modifier
</a>
```

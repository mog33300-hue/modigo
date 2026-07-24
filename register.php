<?php
session_start();
require 'config.php';

$error = "";
$success = "";

/* =====================================================
   SI CONNECTE
===================================================== */

if (isset($_SESSION['user_id'])) {

    header("Location: index.php");
    exit;
}

/* =====================================================
   REGISTER
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $societe      = trim($_POST['societe'] ?? '');
    $prenom       = trim($_POST['prenom'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $telephone    = trim($_POST['telephone'] ?? '');

    /* ================================================
       VALIDATION
    ================================================ */

    if (
        empty($societe) ||
        empty($prenom) ||
        empty($email) ||
        empty($password)
    ) {

        $error = "Veuillez remplir tous les champs obligatoires.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Adresse email invalide.";

    } elseif (strlen($password) < 4) {

        $error = "Mot de passe trop court.";

    } else {

        /* ============================================
           EMAIL EXISTANT
        ============================================ */

        $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email=?
        LIMIT 1
        ");

        $check->execute([
            $email
        ]);

        if ($check->fetch()) {

            $error = "Cet email existe déjà.";

        } else {

            try {

                $pdo->beginTransaction();

                /* ====================================
                   CREATION SOCIETE
                ==================================== */

                $stmt = $pdo->prepare("
                INSERT INTO societes (
                    nom,
                    email,
                    telephone,
                    plan,
                    statut,
                    date_creation
                )
                VALUES (?, ?, ?, 'basic', 'active', NOW())
                ");

                $stmt->execute([

                    $societe,
                    $email,
                    $telephone
                ]);

                $societe_id = $pdo->lastInsertId();

                /* ====================================
                   PASSWORD
                ==================================== */

                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                /* ====================================
                   TOKEN
                ==================================== */

                $token = bin2hex(
                    random_bytes(32)
                );

                /* ====================================
                   ADMIN
                ==================================== */

                $stmt = $pdo->prepare("
                INSERT INTO users (
                    prenom,
                    email,
                    password,
                    telephone,
                    role,
                    token,
                    societe_id,
                    created_at
                )
                VALUES (
                    ?, ?, ?, ?, 'admin', ?, ?, NOW()
                )
                ");

                $stmt->execute([

                    $prenom,
                    $email,
                    $hash,
                    $telephone,
                    $token,
                    $societe_id
                ]);

                $user_id = $pdo->lastInsertId();

                $pdo->commit();

                /* ====================================
                   SESSION AUTO
                ==================================== */

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user_id;

                $_SESSION['prenom'] = $prenom;

                $_SESSION['email'] = $email;

                $_SESSION['role'] = 'admin';

                $_SESSION['societe_id'] = $societe_id;

                $_SESSION['societe_nom'] = $societe;

                $_SESSION['plan'] = 'basic';

                header("Location: index.php");
                exit;

            } catch (Exception $e) {

                $pdo->rollBack();

                $error =
                "Erreur création compte : " .
                $e->getMessage();
            }
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
Inscription - Medigo SaaS
</title>

<link rel="stylesheet" href="style.css">

<style>

body{

    background:#f3f4f6;

    display:flex;

    align-items:center;

    justify-content:center;

    min-height:100vh;
}

.register-box{

    width:100%;

    max-width:500px;

    background:white;

    border-radius:18px;

    padding:35px;

    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.logo{

    text-align:center;

    font-size:42px;

    margin-bottom:10px;
}

.title{

    text-align:center;

    font-size:28px;

    font-weight:bold;

    color:#111827;

    margin-bottom:5px;
}

.subtitle{

    text-align:center;

    color:#6b7280;

    margin-bottom:30px;
}

.alert-error{

    background:#fee2e2;

    color:#991b1b;

    padding:12px;

    border-radius:10px;

    margin-bottom:20px;
}

.footer{

    text-align:center;

    margin-top:25px;

    color:#6b7280;

    font-size:13px;
}

.login-link{

    text-align:center;

    margin-top:20px;
}

</style>

</head>

<body>

<div class="register-box">

<div class="logo">
🚑
</div>

<div class="title">
Medigo SaaS
</div>

<div class="subtitle">
Créer votre société de transport médical
</div>

<?php if($error): ?>

<div class="alert-error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<form method="POST">

<label>
Nom de la société *
</label>

<input
type="text"
name="societe"
required
>

<label>
Nom / prénom admin *
</label>

<input
type="text"
name="prenom"
required
>

<label>
Téléphone
</label>

<input
type="text"
name="telephone"
>

<label>
Adresse email *
</label>

<input
type="email"
name="email"
required
autocomplete="email"
>

<label>
Mot de passe *
</label>

<input
type="password"
name="password"
required
autocomplete="new-password"
>

<div class="actions">

<button
type="submit"
class="btn btn-add"
style="width:100%;"
>
🚀 Créer mon compte
</button>

</div>

</form>

<div class="login-link">

<a href="login.php">
🔐 Déjà un compte ? Connexion
</a>

</div>

<div class="footer">

Medigo SaaS V1.0

<br><br>

Plateforme sécurisée multi-sociétés

</div>

</div>

</body>

</html>
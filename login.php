<?php

session_start();

require 'config.php';

/* =====================================================
   DEJA CONNECTE
===================================================== */

if (isset($_SESSION['user_id'])) {

    header("Location: dashboard.php");
    exit;
}

/* =====================================================
   MESSAGE
===================================================== */

$error = "";

/* =====================================================
   LOGIN
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    $password = trim($_POST['password'] ?? '');

    if (
        empty($email) ||
        empty($password)
    ) {

        $error = "Veuillez remplir tous les champs.";

    } else {

        $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE email=?
        LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $user &&
            password_verify(
                $password,
                $user['password']
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] =
            $user['id'];

            $_SESSION['societe_id'] =
            $user['societe_id'];

            $_SESSION['role'] =
            $user['role'];

            $_SESSION['prenom'] =
            $user['prenom'];

            $_SESSION['email'] =
            $user['email'];

            $_SESSION['last_activity'] =
            time();

            if ($user['role'] === 'chauffeur') {
    header("Location: chauffeur_courses.php");
    exit;
}

header("Location: dashboard.php");
exit;

            exit;

        } else {

            $error =
            "Email ou mot de passe incorrect.";
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
Connexion Medigo
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

    background:
    linear-gradient(
        135deg,
        #2563eb,
        #1e3a8a
    );

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;
}

.login-card{

    width:100%;

    max-width:450px;

    background:white;

    border-radius:28px;

    padding:40px;

    box-shadow:
    0 15px 35px rgba(0,0,0,0.15);
}

.logo{

    text-align:center;

    margin-bottom:30px;
}

.logo h1{

    font-size:42px;

    color:#2563eb;
}

.logo p{

    margin-top:10px;

    color:#6b7280;
}

.alert{

    background:#fee2e2;

    color:#991b1b;

    padding:15px;

    border-radius:14px;

    margin-bottom:20px;

    font-size:14px;
}

.form-group{

    margin-bottom:20px;
}

label{

    display:block;

    margin-bottom:8px;

    font-weight:600;

    color:#374151;
}

input{

    width:100%;

    padding:16px;

    border-radius:14px;

    border:1px solid #d1d5db;

    font-size:15px;

    background:#f9fafb;

    transition:0.2s;
}

input:focus{

    outline:none;

    border-color:#2563eb;

    background:white;

    box-shadow:
    0 0 0 4px rgba(37,99,235,0.12);
}

.btn-login{

    width:100%;

    border:none;

    padding:18px;

    border-radius:16px;

    background:
    linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );

    color:white;

    font-size:16px;

    font-weight:600;

    cursor:pointer;

    transition:0.2s;
}

.btn-login:hover{

    transform:translateY(-2px);

    box-shadow:
    0 10px 20px rgba(37,99,235,0.25);
}

.footer{

    margin-top:25px;

    text-align:center;

    color:#6b7280;

    font-size:14px;
}

</style>

</head>

<body>

<div class="login-card">

<div class="logo">

<h1>
🚑 Medigo
</h1>

<p>
Transport médical intelligent
</p>

</div>

<?php if($error): ?>

<div class="alert">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<form method="POST">

<div class="form-group">

<label>
Adresse email
</label>

<input
type="email"
name="email"
required
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

<button
type="submit"
class="btn-login"
>

🔐 Connexion

</button>

</form>

<div class="footer">

Medigo SaaS sécurisé

</div>

</div>

</body>

</html>
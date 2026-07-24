<?php
session_start();
require_once 'config.php';

/*
|--------------------------------------------------------------------------
| VERIFICATION INSTALLATION
|--------------------------------------------------------------------------
*/

if (file_exists("install.lock")) {
    die("Medigo est déjà installé.");
}

/*
|--------------------------------------------------------------------------
| CREATION DOSSIERS SOCIETE
|--------------------------------------------------------------------------
*/

function createSocieteFolders($societe_id)
{
    $base = __DIR__ . "/uploads/societes/" . intval($societe_id);

    $folders = [
        $base,
        "$base/logos",
        "$base/kbis",
        "$base/assurances",
        "$base/backups",
        "$base/logs"
    ];

    foreach ($folders as $folder) {

        if (!is_dir($folder)) {

            mkdir($folder, 0775, true);
        }
    }

    return "uploads/societes/" . intval($societe_id);
}

/*
|--------------------------------------------------------------------------
| TRAITEMENT FORMULAIRE
|--------------------------------------------------------------------------
*/

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $societe = trim($_POST['societe']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);

    $admin_nom = trim($_POST['admin_nom']);
    $admin_email = trim($_POST['admin_email']);

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {

        /*
        |--------------------------------------------------------------------------
        | VERIFICATION EMAIL EXISTANT
        |--------------------------------------------------------------------------
        */

        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
        ");

        $check->execute([$admin_email]);

        if ($check->fetch()) {

            throw new Exception("Cet email existe déjà.");
        }

        /*
        |--------------------------------------------------------------------------
        | CREATION SOCIETE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO societes
            (
                nom,
                email,
                telephone,
                plan,
                statut,
                date_expiration,
                date_creation
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'premium',
                'active',
                DATE_ADD(NOW(), INTERVAL 1 MONTH),
                NOW()
            )
        ");

        $stmt->execute([
            $societe,
            $email,
            $telephone
        ]);

        $societe_id = $pdo->lastInsertId();

        /*
        |--------------------------------------------------------------------------
        | CREATION DOSSIERS NAS
        |--------------------------------------------------------------------------
        */

        $folder_path = createSocieteFolders($societe_id);

        /*
        |--------------------------------------------------------------------------
        | ENREGISTREMENT DOSSIER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE societes
            SET dossier = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $folder_path,
            $societe_id
        ]);

        /*
        |--------------------------------------------------------------------------
        | CREATION SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO users
            (
                email,
                password,
                role,
                prenom,
                telephone,
                societe_id,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                'superadmin',
                ?,
                ?,
                ?,
                NOW()
            )
        ");

        $stmt->execute([
            $admin_email,
            $password,
            $admin_nom,
            $telephone,
            $societe_id
        ]);

        /*
        |--------------------------------------------------------------------------
        | DOSSIERS SYSTEME
        |--------------------------------------------------------------------------
        */

        if (!is_dir("uploads")) {
            mkdir("uploads", 0775, true);
        }

        if (!is_dir("backups")) {
            mkdir("backups", 0775, true);
        }

        if (!is_dir("logs")) {
            mkdir("logs", 0775, true);
        }

        /*
        |--------------------------------------------------------------------------
        | INSTALL LOCK
        |--------------------------------------------------------------------------
        */

        file_put_contents("install.lock", "INSTALLED");

        $message = "Installation Medigo terminée avec succès.";

    } catch (Exception $e) {

        $message = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Installation Medigo Pack Pro</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#f3f4f6;
}

.container{
    max-width:700px;
    margin:50px auto;
    background:white;
    padding:40px;
    border-radius:20px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
}

h1{
    text-align:center;
    color:#2563eb;
}

h2{
    color:#1f2937;
}

input{
    width:100%;
    padding:15px;
    margin-bottom:20px;
    border:1px solid #d1d5db;
    border-radius:10px;
    font-size:16px;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:15px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:10px;
    font-size:18px;
    cursor:pointer;
}

button:hover{
    background:#1d4ed8;
}

.message{
    padding:15px;
    margin-bottom:20px;
    border-radius:10px;
    background:#dcfce7;
    color:#166534;
}

.error{
    background:#fee2e2;
    color:#991b1b;
}

.section{
    margin-top:30px;
}

.logo{
    text-align:center;
    font-size:50px;
}

</style>

</head>

<body>

<div class="container">

<div class="logo">
🚑
</div>

<h1>Installation Medigo Pack Pro</h1>

<?php if($message): ?>

<div class="message <?= strpos($message, 'Erreur') !== false ? 'error' : '' ?>">

    <?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<form method="POST">

<div class="section">

<h2>🏢 Société</h2>

<input
    type="text"
    name="societe"
    placeholder="Nom de la société"
    required
>

<input
    type="email"
    name="email"
    placeholder="Email société"
    required
>

<input
    type="text"
    name="telephone"
    placeholder="Téléphone société"
>

</div>

<div class="section">

<h2>👤 Administrateur</h2>

<input
    type="text"
    name="admin_nom"
    placeholder="Nom administrateur"
    required
>

<input
    type="email"
    name="admin_email"
    placeholder="Email administrateur"
    required
>

<input
    type="password"
    name="password"
    placeholder="Mot de passe"
    required
>

</div>

<button type="submit">

    Installer Medigo

</button>

</form>

</div>

</body>
</html>
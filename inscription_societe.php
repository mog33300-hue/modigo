<?php
require 'config.php';

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim($_POST['nom'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $siret = trim($_POST['siret'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if (empty($nom) || empty($responsable) || empty($email) || empty($siret) || empty($password)) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } elseif ($password !== $password_confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT id
                FROM societes
                WHERE email = ?
                OR siret = ?
                LIMIT 1
            ");
            $stmt->execute([$email, $siret]);

            if ($stmt->fetch()) {
                $erreur = "Une société existe déjà avec cet email ou ce SIRET.";
            } else {

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE email = ?
                    LIMIT 1
                ");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $erreur = "Cette adresse email est déjà utilisée par un utilisateur.";
                } else {

                    $pdo->beginTransaction();

                    $date_expiration = date('Y-m-d', strtotime('+30 days'));

                    $dossier = strtolower(
                        preg_replace('/[^a-zA-Z0-9]+/', '-', $nom)
                    );
                    $dossier = trim($dossier, '-');

                    if ($dossier === '') {
                        $dossier = 'societe-' . time();
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO societes
                        (
                            nom,
                            email,
                            telephone,
                            adresse,
                            plan,
                            statut,
                            date_expiration,
                            siret,
                            responsable,
                            dossier,
                            created_at,
                            date_creation
                        )
                        VALUES
                        (
                            ?, ?, ?, ?, 'basic', 'active', ?, ?, ?, ?, NOW(), NOW()
                        )
                    ");

                    $stmt->execute([
                        $nom,
                        $email,
                        $telephone,
                        $adresse,
                        $date_expiration,
                        $siret,
                        $responsable,
                        $dossier
                    ]);

                    $societe_id = intval($pdo->lastInsertId());

                    $base_dir = __DIR__ . '/uploads/societes/' . $societe_id;

                    if (!is_dir($base_dir)) {
                        @mkdir($base_dir, 0777, true);
                    }

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        INSERT INTO users
                        (
                            email,
                            password,
                            role,
                            prenom,
                            telephone,
                            token,
                            vehicule,
                            societe_id,
                            created_at
                        )
                        VALUES
                        (
                            ?, ?, 'admin', ?, ?, NULL, NULL, ?, NOW()
                        )
                    ");

                    $stmt->execute([
                        $email,
                        $hash,
                        $responsable,
                        $telephone,
                        $societe_id
                    ]);

                    $pdo->commit();

                    $login_url = "https://minicartransgps.synology.me/minicartrans/login.php";

                    $sujet = "Bienvenue sur Medigo";

                    $message_mail =
                        "Bonjour " . $responsable . ",\n\n" .
                        "Votre société a été créée avec succès.\n\n" .
                        "Société : " . $nom . "\n" .
                        "Email : " . $email . "\n" .
                        "Plan : BASIC\n" .
                        "Essai gratuit : 30 jours\n" .
                        "Expiration : " . $date_expiration . "\n\n" .
                        "Connexion :\n" .
                        $login_url . "\n\n" .
                        "Merci d'utiliser Medigo.\n";

                    $headers =
                        "From: Medigo <tecth33300@gmail.com>\r\n" .
                        "Reply-To: tecth33300@gmail.com\r\n" .
                        "Content-Type: text/plain; charset=UTF-8\r\n";

                    @mail($email, $sujet, $message_mail, $headers);

                    $message = "Votre société a été créée avec succès. Vous pouvez maintenant vous connecter.";
                }
            }

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Inscription société - Medigo</title>

<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f3f4f6;color:#111827}
.container{max-width:900px;margin:40px auto;padding:20px}
.card{background:white;padding:30px;border-radius:18px;box-shadow:0 4px 14px rgba(0,0,0,.08)}
h1{margin-top:0;color:#2563eb}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:15px}
label{display:block;font-weight:bold;margin-bottom:6px}
input,textarea{width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;margin-bottom:15px}
textarea{min-height:90px}
.btn{background:#2563eb;color:white;border:0;padding:14px 20px;border-radius:10px;font-weight:bold;cursor:pointer}
.alert-success{background:#dcfce7;color:#166534;padding:14px;border-radius:10px;margin-bottom:20px}
.alert-error{background:#fee2e2;color:#991b1b;padding:14px;border-radius:10px;margin-bottom:20px}
.small{color:#6b7280;font-size:13px}
</style>
</head>

<body>

<div class="container">
<div class="card">

<h1>🚑 Créer votre société Medigo</h1>

<p class="small">
Essai gratuit 30 jours — compte administrateur créé automatiquement.
</p>

<?php if($message): ?>
<div class="alert-success">
<?= htmlspecialchars($message) ?>
<br><br>
<a href="login.php">Se connecter</a>
</div>
<?php endif; ?>

<?php if($erreur): ?>
<div class="alert-error">
<?= htmlspecialchars($erreur) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="grid">

<div>
<label>Nom de la société *</label>
<input type="text" name="nom" required>
</div>

<div>
<label>Responsable *</label>
<input type="text" name="responsable" required>
</div>

<div>
<label>Email de connexion *</label>
<input type="email" name="email" required>
</div>

<div>
<label>Téléphone</label>
<input type="text" name="telephone">
</div>

<div>
<label>SIRET *</label>
<input type="text" name="siret" required>
</div>

<div>
<label>Mot de passe *</label>
<input type="password" name="password" required>
</div>

<div>
<label>Confirmer mot de passe *</label>
<input type="password" name="password_confirm" required>
</div>

</div>

<label>Adresse</label>
<textarea name="adresse"></textarea>

<button type="submit" class="btn">
✅ Créer mon compte société
</button>

</form>

</div>
</div>

</body>
</html>
<?php
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $contenu = trim($_POST['message'] ?? '');
    $captcha = trim($_POST['captcha'] ?? '');
    $rgpd = isset($_POST['rgpd']);

    if (
        empty($nom) ||
        empty($email) ||
        empty($sujet) ||
        empty($contenu)
    ) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } elseif ($captcha !== '7') {
        $erreur = "Réponse anti-spam incorrecte.";
    } elseif (!$rgpd) {
        $erreur = "Vous devez accepter l'utilisation de vos données pour traiter votre demande.";
    } else {

        $to = "tecth33300@gmail.com";
        $subject = "Contact Medigo Synology - " . $sujet;

        $body =
"Nom : ".$nom."\n".
"Email : ".$email."\n".
"Téléphone : ".$telephone."\n\n".
"Message :\n".$contenu."\n";

        $headers =
"From: Medigo Synology <tecth33300@gmail.com>\r\n".
"Reply-To: ".$email."\r\n".
"Content-Type: text/plain; charset=UTF-8\r\n";

        @mail($to, $subject, $body, $headers);

        $message = "Votre message a bien été envoyé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contact - Medigo Synology</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main" style="max-width:800px;margin:auto;padding:30px;">

<h1>📧 Contact</h1>

<p>
Medigo Synology — Solution SaaS MinicarTrans.
</p>

<?php if($message): ?>
<div style="background:#dcfce7;color:#166534;padding:15px;border-radius:10px;margin:20px 0;">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if($erreur): ?>
<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:10px;margin:20px 0;">
<?= htmlspecialchars($erreur) ?>
</div>
<?php endif; ?>

<form method="post">

<label>Nom *</label>
<input type="text" name="nom" required>

<label>Email *</label>
<input type="email" name="email" required>

<label>Téléphone</label>
<input type="text" name="telephone">

<label>Sujet *</label>
<input type="text" name="sujet" required>

<label>Message *</label>
<textarea name="message" required></textarea>

<label>Anti-spam : combien font 3 + 4 ? *</label>
<input type="text" name="captcha" required>

<label>
<input type="checkbox" name="rgpd" required>
J'accepte que mes données soient utilisées uniquement pour traiter ma demande.
</label>

<br><br>

<button class="btn btn-add" type="submit">
📨 Envoyer
</button>

</form>

<br>
<a href="landing.php">⬅ Retour accueil</a>

</div>

</body>
</html>
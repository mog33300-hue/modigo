<?php
require 'auth.php';
require 'config.php';
require 'stripe_config.php';

require_once __DIR__ . '/stripe-php/init.php';
require_once __DIR__ . '/generer_facture.php';

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

$session_id = $_GET['session_id'] ?? '';

if (empty($session_id)) {
    die("Session Stripe manquante");
}

$plans = [
    'basic' => 99.00,
    'pro' => 149.00,
    'premium' => 199.00
];

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {

    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status !== 'paid') {
        die("Paiement non validé");
    }

    $stripe_societe_id = intval($session->metadata->societe_id ?? 0);
    $plan = $session->metadata->plan ?? '';

    if ($stripe_societe_id !== $societe_id) {
        die("Paiement non autorisé");
    }

    if (!isset($plans[$plan])) {
        die("Plan invalide");
    }

    $stmt = $pdo->prepare("
        SELECT id, facture_pdf
        FROM paiements
        WHERE stripe_session_id = ?
        LIMIT 1
    ");
    $stmt->execute([$session_id]);
    $paiement_existant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paiement_existant) {

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE societes
            SET plan = ?,
                statut = 'active',
                date_expiration = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            WHERE id = ?
        ");
        $stmt->execute([$plan, $societe_id]);

        $stmt = $pdo->prepare("
            INSERT INTO paiements
            (
                societe_id,
                plan,
                montant,
                stripe_session_id,
                statut,
                facture_pdf,
                created_at
            )
            VALUES
            (
                ?, ?, ?, ?, 'paye', NULL, NOW()
            )
        ");
        $stmt->execute([
            $societe_id,
            $plan,
            $plans[$plan],
            $session_id
        ]);

        $paiement_id = intval($pdo->lastInsertId());

        $stmt = $pdo->prepare("
            SELECT nom, responsable, adresse, siret, email
            FROM societes
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$societe_id]);
        $societe = $stmt->fetch(PDO::FETCH_ASSOC);

        $facture_pdf = genererFacture(
            $paiement_id,
            $societe['nom'] ?? '',
            $societe['responsable'] ?? '',
            $societe['adresse'] ?? '',
            $societe['siret'] ?? '',
            $plan,
            $plans[$plan]
        );

        $stmt = $pdo->prepare("
            UPDATE paiements
            SET facture_pdf = ?
            WHERE id = ?
        ");
        $stmt->execute([$facture_pdf, $paiement_id]);

        $pdo->commit();

        /* ENVOI EMAIL FACTURE */
        try {

            if (!empty($societe['email'])) {

                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'tecth33300@gmail.com';

                // Mot de passe d'application Gmail, sans espaces
                $mail->Password = 'bokk clhs mafm krii';

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('tecth33300@gmail.com', 'Medigo Synology');
                $mail->addAddress($societe['email'], $societe['nom'] ?? '');

                $mail->Subject = 'Votre facture Medigo Synology';

                $mail->Body =
                    "Bonjour,\n\n" .
                    "Votre paiement Medigo Synology a bien été validé.\n\n" .
                    "Plan : " . strtoupper($plan) . "\n" .
                    "Montant : " . number_format($plans[$plan], 2, ',', ' ') . " €\n\n" .
                    "Votre facture est jointe à cet email.\n\n" .
                    "Merci pour votre confiance.\n\n" .
                    "Medigo Synology\n" .
                    "Solution MinicarTrans";

                $chemin_facture = __DIR__ . '/' . $facture_pdf;

                if (file_exists($chemin_facture)) {
                    $mail->addAttachment($chemin_facture);
                }

                $mail->send();
            }

        } catch (Exception $e) {

            file_put_contents(
                __DIR__ . '/mail_error.log',
                date('Y-m-d H:i:s') .
                ' - ' .
                $mail->ErrorInfo .
                PHP_EOL,
                FILE_APPEND
            );
        }

    } else {
        $paiement_id = intval($paiement_existant['id']);
    }

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Erreur paiement : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Paiement validé - Medigo Synology</title>

<style>
body{
    font-family:Arial,sans-serif;
    background:#f3f4f6;
    padding:40px;
}

.card{
    background:white;
    max-width:650px;
    margin:auto;
    padding:35px;
    border-radius:18px;
    text-align:center;
    box-shadow:0 4px 14px rgba(0,0,0,.08);
}

h1{
    color:#16a34a;
}

.btn{
    display:inline-block;
    margin-top:20px;
    background:#2563eb;
    color:white;
    padding:14px 20px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
}
</style>
</head>

<body>

<div class="card">

<h1>✅ Paiement validé</h1>

<p>
Votre abonnement Medigo Synology a été activé avec succès.
</p>

<p>
Plan :
<strong><?= htmlspecialchars(strtoupper($plan)) ?></strong>
</p>

<p>
Montant :
<strong><?= number_format($plans[$plan], 2, ',', ' ') ?> €</strong>
</p>

<a href="subscription.php" class="btn">
Retour à mon abonnement
</a>

</div>

</body>
</html>
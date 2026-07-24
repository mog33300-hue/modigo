<?php
require 'config.php';

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$jours_relance = [7, 3, 1];

foreach ($jours_relance as $jours) {

    $date_cible = date('Y-m-d', strtotime("+$jours days"));

    $stmt = $pdo->prepare("
    SELECT
        MIN(id) AS id,
        MAX(nom) AS nom,
        email,
        MAX(plan) AS plan,
        date_expiration
    FROM societes
    WHERE statut='active'
    AND date_expiration = ?
    AND email IS NOT NULL
    AND email <> ''
    GROUP BY email, date_expiration
");

    $stmt->execute([$date_cible]);

    $societes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($societes as $societe) {

        try {

            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'tecth33300@gmail.com';
            $mail->Password = 'bokk clhs mafm krii';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('tecth33300@gmail.com', 'Medigo Synology');
            $mail->addAddress($societe['email'], $societe['nom']);

            $mail->Subject = "Votre abonnement Medigo Synology expire dans $jours jour(s)";

            $mail->Body =
                "Bonjour,\n\n" .
                "Votre abonnement Medigo Synology arrive bientôt à expiration.\n\n" .
                "Société : " . $societe['nom'] . "\n" .
                "Plan actuel : " . strtoupper($societe['plan']) . "\n" .
                "Date d'expiration : " . $societe['date_expiration'] . "\n\n" .
                "Il vous reste : $jours jour(s).\n\n" .
                "Pour renouveler votre abonnement, connectez-vous ici :\n" .
                "https://minicartransgps.synology.me/minicartrans/subscription.php\n\n" .
                "Merci pour votre confiance.\n\n" .
                "Medigo Synology\n" .
                "Solution MinicarTrans";

            $mail->send();

            echo "Relance envoyée à " . htmlspecialchars($societe['email']) . " pour " . intval($jours) . " jour(s)<br>";

        } catch (Exception $e) {

            echo "Erreur email pour " . htmlspecialchars($societe['email']) . " : " . htmlspecialchars($mail->ErrorInfo) . "<br>";
        }
    }
}

echo "<br>Traitement terminé.";
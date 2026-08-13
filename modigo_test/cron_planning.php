<?php

/* =====================================================
   MEDIGO DISPATCH PRO
===================================================== */

require 'config.php';

/* =====================================================
   PHPMAILER
===================================================== */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

/* =====================================================
   SEMAINE
===================================================== */

$week =
date('Y-\WW');

$weekNumber =
date('W');

/* =====================================================
   DOSSIER TEMP
===================================================== */

$temp_dir =
__DIR__.'/temp';

if(!is_dir($temp_dir)){

    die("Dossier temp introuvable");
}

/* =====================================================
   CHAUFFEURS
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE role='chauffeur'
AND email IS NOT NULL
AND email != ''
ORDER BY prenom ASC
");

$stmt->execute();

$chauffeurs =
$stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($chauffeurs)){

    die("Aucun chauffeur trouvé");
}

/* =====================================================
   BOUCLE
===================================================== */

foreach($chauffeurs as $chauffeur){

    $chauffeur_id =
    intval($chauffeur['id']);

    $prenom =
    $chauffeur['prenom'] ?? '';

    $email =
    $chauffeur['email'] ?? '';

    echo "<hr>";
    echo "Traitement : ".$prenom."<br>";

    /* =====================================================
       GENERATION PDF
    ===================================================== */

    $_GET['chauffeur'] =
    $chauffeur_id;

    $_GET['week'] =
    $week;

    $_GET['cron'] =
    1;

    include 'planning_pdf.php';

    $pdf_file =
    $temp_dir.
    '/planning_'.
    $chauffeur_id.
    '_semaine_'.
    $weekNumber.
    '.pdf';

    if(!file_exists($pdf_file)){

        echo "Erreur génération PDF<br>";

        continue;
    }

    echo "PDF généré<br>";

    /* =====================================================
       MAIL
    ===================================================== */

    $mail = new PHPMailer(true);

    try{

        /* =====================================================
           DEBUG SMTP
        ===================================================== */

        $mail->SMTPDebug = 0;

        /* =====================================================
           SMTP GMAIL
        ===================================================== */

        $mail->isSMTP();

        $mail->Host =
        'smtp.gmail.com';

        $mail->SMTPAuth =
        true;

        $mail->Username =
        'tecth33300@gmail.com';

        $mail->Password =
        'qvbqdrbnjwscijya';

        $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
        587;

        /* =====================================================
           UTF8
        ===================================================== */

        $mail->CharSet =
        'UTF-8';

        /* =====================================================
           SSL OPTIONS
        ===================================================== */

        $mail->SMTPOptions = [

            'ssl' => [

                'verify_peer' => false,

                'verify_peer_name' => false,

                'allow_self_signed' => true

            ]
        ];

        /* =====================================================
           EXPEDITEUR
        ===================================================== */

        $mail->setFrom(
            'tecth33300@gmail.com',
            'Medigo Dispatch Pro'
        );

        /* =====================================================
           DESTINATAIRE
        ===================================================== */

        $mail->addAddress(
            $email,
            $prenom
        );

        /* =====================================================
           PIECE JOINTE
        ===================================================== */

        $mail->addAttachment(
            $pdf_file
        );

        /* =====================================================
           SUJET
        ===================================================== */

        $mail->Subject =
        'Planning semaine Medigo';

        /* =====================================================
           MESSAGE
        ===================================================== */

        $mail->Body =
"Bonjour ".$prenom.",

Voici votre planning chauffeur de la semaine.

Le planning PDF est en pièce jointe.

Medigo Dispatch Pro";

        /* =====================================================
           ENVOI
        ===================================================== */

        $mail->send();

        echo "Mail envoyé avec succès<br>";

    }catch(Exception $e){

        echo "Erreur Mail : ".
        $mail->ErrorInfo.
        "<br>";
    }
}

echo "<hr>";
echo "Terminé";
?>
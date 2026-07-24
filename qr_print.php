<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   ID CHAUFFEUR
===================================================== */

$id = intval($_GET['id'] ?? 0);

/* =====================================================
   CHAUFFEUR
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE id=?
AND role='chauffeur'
");

$stmt->execute([$id]);

$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {

    die("Chauffeur introuvable");
}

/* =====================================================
   LIEN MOBILE
===================================================== */

$link =
"https://minicartransgps.synology.me/minicartrans/mobile_secure_token.php?token=" .
urlencode($c['token'] ?? '');

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
QR Chauffeur
</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>

/* =====================================================
   RESET DARK MODE
===================================================== */

html,
body{

    background:#f3f4f6 !important;

    color:#111827 !important;

    margin:0;

    padding:0;

    font-family:Arial,sans-serif;

    min-height:100vh;
}

/* =====================================================
   MAIN
===================================================== */

.main{

    padding:30px;

    text-align:center;
}

/* =====================================================
   ACTIONS
===================================================== */

.top-actions{

    margin-bottom:20px;
}

.top-actions a{

    display:inline-block;

    padding:10px 16px;

    border-radius:8px;

    text-decoration:none;

    color:white !important;

    font-weight:bold;

    margin:5px;

    font-size:14px;
}

/* =====================================================
   BUTTONS
===================================================== */

.btn-back{

    background:#2563eb !important;
}

.btn-print{

    background:#16a34a !important;
}

/* =====================================================
   QR BOX
===================================================== */

.box{

    background:white !important;

    color:#111827 !important;

    border-radius:12px;

    padding:25px;

    display:inline-block;

    box-shadow:0 2px 8px rgba(0,0,0,0.10);

    max-width:320px;
}

/* =====================================================
   TITLES
===================================================== */

.box h2{

    margin-top:0;

    color:#111827 !important;
}

/* =====================================================
   QR
===================================================== */

#qrcode{

    margin:20px auto;

    display:flex;

    justify-content:center;

    background:white !important;

    padding:10px;

    border-radius:10px;
}

/* =====================================================
   TEXT
===================================================== */

.box p{

    color:#111827 !important;
}

/* =====================================================
   PRINT
===================================================== */

@media print{

    .top-actions{

        display:none;
    }

    html,
    body{

        background:white !important;
    }

    .box{

        box-shadow:none;

        border:2px solid #111827;
    }
}

</style>

</head>

<body>

<div class="main">

<!-- ACTIONS -->

<div class="top-actions">

<a
href="chauffeurs.php"
class="btn-back"
>

⬅ Retour

</a>

<a
href="javascript:window.print();"
class="btn-print"
>

🖨 Imprimer

</a>

</div>

<!-- QR -->

<div class="box">

<h2>

<?= htmlspecialchars($c['prenom'] ?? '') ?>

</h2>

<p>

🚗 <?= htmlspecialchars($c['vehicule'] ?? 'Non défini') ?>

</p>

<div id="qrcode"></div>

<p>

Scanner pour accéder au planning chauffeur

</p>

</div>

</div>

<!-- QR GENERATION -->

<script>

new QRCode(
    document.getElementById("qrcode"),
    {
        text: "<?= htmlspecialchars($link, ENT_QUOTES) ?>",
        width: 220,
        height: 220,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    }
);

</script>

</body>

</html>
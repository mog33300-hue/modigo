<?php
require 'auth.php';
require 'config.php';

$message = "";
$error = "";

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {

    die("Société introuvable");
}

/* =====================================================
   DOSSIERS UPLOAD
===================================================== */

$logoDir = __DIR__ . '/uploads/logos';

$kbisDir = __DIR__ . '/uploads/kbis';

$assuranceDir = __DIR__ . '/uploads/assurances';

/* =====================================================
   RECUP SOCIETE
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM societes
WHERE id=?
LIMIT 1
");

$stmt->execute([
    $societe_id
]);

$societe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$societe) {

    die("Société introuvable");
}

/* =====================================================
   UPLOAD FUNCTION
===================================================== */

function uploadFile(
    $file,
    $dir,
    $prefix,
    $allowedExt
){

    if (
        !isset($file) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {

        return null;
    }

    $ext = strtolower(
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array($ext, $allowedExt)) {

        return false;
    }

    $filename =
    $prefix .
    '_' .
    time() .
    '_' .
    bin2hex(random_bytes(4)) .
    '.' .
    $ext;

    $target =
    $dir .
    '/' .
    $filename;

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $target
        )
    ) {

        return false;
    }

    return $filename;
}

/* =====================================================
   UPDATE
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim($_POST['nom'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $telephone = trim($_POST['telephone'] ?? '');

    $siret = trim($_POST['siret'] ?? '');

    $adresse = trim($_POST['adresse'] ?? '');

    $code_postal = trim($_POST['code_postal'] ?? '');

    $ville = trim($_POST['ville'] ?? '');

    $responsable = trim($_POST['responsable'] ?? '');

    if (empty($nom)) {

        $error =
        "Le nom de la société est obligatoire.";

    } else {

        $logo =
        $societe['logo'] ?? null;

        $kbis =
        $societe['kbis'] ?? null;

        $assurance =
        $societe['assurance'] ?? null;

        /* ============================================
           LOGO
        ============================================ */

        $newLogo = uploadFile(

            $_FILES['logo'] ?? null,

            $logoDir,

            'logo_' . $societe_id,

            ['jpg','jpeg','png','webp']

        );

        if ($newLogo === false) {

            $error =
            "Logo invalide.";

        } elseif ($newLogo) {

            $logo =
            'uploads/logos/' .
            $newLogo;
        }

        /* ============================================
           KBIS
        ============================================ */

        $newKbis = uploadFile(

            $_FILES['kbis'] ?? null,

            $kbisDir,

            'kbis_' . $societe_id,

            ['pdf']

        );

        if ($newKbis === false) {

            $error =
            "KBIS invalide.";

        } elseif ($newKbis) {

            $kbis =
            'uploads/kbis/' .
            $newKbis;
        }

        /* ============================================
           ASSURANCE
        ============================================ */

        $newAssurance = uploadFile(

            $_FILES['assurance'] ?? null,

            $assuranceDir,

            'assurance_' . $societe_id,

            ['pdf']

        );

        if ($newAssurance === false) {

            $error =
            "Assurance invalide.";

        } elseif ($newAssurance) {

            $assurance =
            'uploads/assurances/' .
            $newAssurance;
        }

        /* ============================================
           UPDATE SQL
        ============================================ */

        if (!$error) {

            $stmt = $pdo->prepare("
            UPDATE societes
            SET
                nom=?,
                email=?,
                telephone=?,
                siret=?,
                adresse=?,
                code_postal=?,
                ville=?,
                responsable=?,
                logo=?,
                kbis=?,
                assurance=?
            WHERE id=?
            ");

            $stmt->execute([

                $nom,
                $email,
                $telephone,
                $siret,
                $adresse,
                $code_postal,
                $ville,
                $responsable,
                $logo,
                $kbis,
                $assurance,
                $societe_id
            ]);

            $_SESSION['societe_nom'] = $nom;

            $message =
            "Informations société mises à jour.";

            /* refresh */

            $stmt = $pdo->prepare("
            SELECT *
            FROM societes
            WHERE id=?
            LIMIT 1
            ");

            $stmt->execute([
                $societe_id
            ]);

            $societe =
            $stmt->fetch(PDO::FETCH_ASSOC);
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
Profil société - Medigo SaaS
</title>

<link rel="stylesheet" href="style.css">

<style>

.profile-card{

    background:white;

    border-radius:16px;

    padding:25px;

    box-shadow:0 2px 8px rgba(0,0,0,0.06);

    max-width:900px;
}

.alert{

    background:#dcfce7;

    color:#166534;

    padding:12px;

    border-radius:8px;

    margin-bottom:20px;
}

.alert-error{

    background:#fee2e2;

    color:#991b1b;

    padding:12px;

    border-radius:8px;

    margin-bottom:20px;
}

.logo-preview{

    max-width:180px;

    max-height:100px;

    margin-bottom:15px;

    background:#f3f4f6;

    padding:10px;

    border-radius:10px;
}

.doc-links{

    margin-top:25px;

    background:#f9fafb;

    padding:15px;

    border-radius:12px;
}

</style>

</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<a
href="index.php"
class="btn btn-back"
>
⬅ Retour
</a>

<h1>
🏢 Profil société
</h1>

<?php if($message): ?>

<div class="alert">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<?php if($error): ?>

<div class="alert-error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<div class="profile-card">

<form
method="POST"
enctype="multipart/form-data"
>

<label>
Nom société *
</label>

<input
type="text"
name="nom"
required
value="<?= htmlspecialchars($societe['nom'] ?? '') ?>"
>

<label>
Email société
</label>

<input
type="email"
name="email"
value="<?= htmlspecialchars($societe['email'] ?? '') ?>"
>

<label>
Téléphone
</label>

<input
type="text"
name="telephone"
value="<?= htmlspecialchars($societe['telephone'] ?? '') ?>"
>

<label>
SIRET
</label>

<input
type="text"
name="siret"
value="<?= htmlspecialchars($societe['siret'] ?? '') ?>"
>

<label>
Responsable
</label>

<input
type="text"
name="responsable"
value="<?= htmlspecialchars($societe['responsable'] ?? '') ?>"
>

<label>
Adresse
</label>

<input
type="text"
name="adresse"
value="<?= htmlspecialchars($societe['adresse'] ?? '') ?>"
>

<label>
Code postal
</label>

<input
type="text"
name="code_postal"
value="<?= htmlspecialchars($societe['code_postal'] ?? '') ?>"
>

<label>
Ville
</label>

<input
type="text"
name="ville"
value="<?= htmlspecialchars($societe['ville'] ?? '') ?>"
>

<hr>

<!-- LOGO -->

<label>
Logo société
</label>

<?php if(!empty($societe['logo'])): ?>

<img
src="<?= htmlspecialchars($societe['logo']) ?>"
class="logo-preview"
>

<?php endif; ?>

<input
type="file"
name="logo"
accept=".jpg,.jpeg,.png,.webp"
>

<!-- KBIS -->

<label>
KBIS PDF
</label>

<input
type="file"
name="kbis"
accept=".pdf"
>

<!-- ASSURANCE -->

<label>
Assurance PDF
</label>

<input
type="file"
name="assurance"
accept=".pdf"
>

<div class="actions">

<button
type="submit"
class="btn btn-add"
>
💾 Enregistrer
</button>

</div>

</form>

<!-- DOCS -->

<div class="doc-links">

<h3>
📄 Documents enregistrés
</h3>

<p>

KBIS :

<?php if(!empty($societe['kbis'])): ?>

<a
href="<?= htmlspecialchars($societe['kbis']) ?>"
target="_blank"
>

Voir KBIS

</a>

<?php else: ?>

Non ajouté

<?php endif; ?>

</p>

<p>

Assurance :

<?php if(!empty($societe['assurance'])): ?>

<a
href="<?= htmlspecialchars($societe['assurance']) ?>"
target="_blank"
>

Voir assurance

</a>

<?php else: ?>

Non ajoutée

<?php endif; ?>

</p>

</div>

</div>

</div>

</body>

</html>
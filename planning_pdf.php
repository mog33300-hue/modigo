<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   TCPDF
===================================================== */

require_once(__DIR__ . '/tcpdf/tcpdf.php');

/* =====================================================
   SECURITE
===================================================== */

if (!isset($_SESSION['user_id']) && !isset($_GET['cron'])) {

    die('Accès refusé');
}

$societe_id =
intval($_SESSION['societe_id'] ?? 1);

/* =====================================================
   CHAUFFEUR
===================================================== */

$chauffeur_id =
intval($_GET['chauffeur'] ?? 0);

if ($chauffeur_id <= 0) {

    die('Chauffeur invalide');
}

/* =====================================================
   SEMAINE
===================================================== */

$week =
$_GET['week'] ?? date('Y-\WW');

$year =
intval(substr($week, 0, 4));

$weekNumber =
intval(substr($week, 6, 2));

$monday = new DateTime();

$monday->setISODate(
    $year,
    $weekNumber
);

$sunday = clone $monday;

$sunday->modify('+6 day');

$start =
$monday->format('Y-m-d');

$end =
$sunday->format('Y-m-d');

/* =====================================================
   CHAUFFEUR
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE id=?
AND role='chauffeur'
LIMIT 1
");

$stmt->execute([
    $chauffeur_id
]);

$chauffeur =
$stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur) {

    die('Chauffeur introuvable');
}

/* =====================================================
   COURSES
===================================================== */

$stmt = $pdo->prepare("
SELECT *
FROM courses
WHERE chauffeur_id=?
AND date_course BETWEEN ? AND ?
ORDER BY
date_course ASC,
heure_pickup ASC
");

$stmt->execute([
    $chauffeur_id,
    $start,
    $end
]);

$courses =
$stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   TEMP DOSSIER
===================================================== */

$temp_dir =
__DIR__.'/temp';

if(!is_dir($temp_dir)){

    die('Dossier temp introuvable');
}

/* =====================================================
   PDF
===================================================== */

$pdf = new TCPDF(
    'L',
    'mm',
    'A4',
    true,
    'UTF-8',
    false
);

$pdf->SetCreator('Medigo');

$pdf->SetAuthor('Medigo');

$pdf->SetTitle('Planning Chauffeur');

$pdf->SetMargins(10,10,10);

$pdf->SetAutoPageBreak(TRUE, 10);

$pdf->AddPage();

/* =====================================================
   HTML
===================================================== */

$html = '

<h1 style="
color:#2563eb;
font-size:26px;
">
Medigo Dispatch Pro
</h1>

<h2>
Planning Chauffeur
</h2>

<table cellpadding="6">

<tr>
<td width="150">
<b>Chauffeur :</b>
</td>

<td>
'.htmlspecialchars($chauffeur['prenom']).'
</td>
</tr>

<tr>
<td>
<b>Semaine :</b>
</td>

<td>
'.$weekNumber.'
</td>
</tr>

<tr>
<td>
<b>Période :</b>
</td>

<td>
Du '.date('d/m/Y',strtotime($start)).'
au '.date('d/m/Y',strtotime($end)).'
</td>
</tr>

</table>

<br><br>

<table
border="1"
cellpadding="6"
style="font-size:11px;"
>

<tr style="
background-color:#2563eb;
color:white;
font-weight:bold;
">

<th width="70">
Date
</th>

<th width="60">
Heure
</th>

<th width="120">
Patient
</th>

<th width="180">
Départ
</th>

<th width="180">
Arrivée
</th>

<th width="90">
Statut
</th>

</tr>
';

/* =====================================================
   AUCUNE COURSE
===================================================== */

if (empty($courses)) {

    $html .= '

    <tr>

    <td colspan="6">

    Aucune course cette semaine

    </td>

    </tr>
    ';
}

/* =====================================================
   COURSES
===================================================== */

foreach($courses as $course){

    $html .= '

    <tr>

    <td>
    '.date(
        'd/m/Y',
        strtotime($course['date_course'])
    ).'
    </td>

    <td>
    '.substr(
        htmlspecialchars(
            $course['heure_pickup'] ?? ''
        ),
        0,
        5
    ).'
    </td>

    <td>
    '.htmlspecialchars(
        $course['client_nom'] ?? ''
    ).'
    </td>

    <td>
    '.htmlspecialchars(
        $course['adresse_depart'] ?? ''
    ).'
    </td>

    <td>
    '.htmlspecialchars(
        $course['adresse_arrivee'] ?? ''
    ).'
    </td>

    <td>
    '.htmlspecialchars(
        $course['statut'] ?? ''
    ).'
    </td>

    </tr>
    ';
}

$html .= '

</table>

<br><br>

<p style="
font-size:10px;
color:#6b7280;
">

Document généré automatiquement par
Medigo Dispatch Pro

</p>
';

/* =====================================================
   WRITE
===================================================== */

$pdf->writeHTML(
    $html,
    true,
    false,
    true,
    false,
    ''
);

/* =====================================================
   FILENAME
===================================================== */

$filename =
$temp_dir.
'/planning_'.
$chauffeur_id.
'_semaine_'.
$weekNumber.
'.pdf';

/* =====================================================
   OUTPUT
===================================================== */

if(isset($_GET['download'])){

    $pdf->Output(
        basename($filename),
        'I'
    );

}else{

    $pdf->Output(
        $filename,
        'F'
    );
}

?>
<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   SECURITE SOCIETE
===================================================== */

if (!isset($_SESSION['societe_id'])) {

    die("Session société introuvable");

}

$societe_id = intval($_SESSION['societe_id']);

/* =====================================================
   NOM FICHIER
===================================================== */

$filename =
"export_courses_" .
date('Y-m-d_H-i-s') .
".csv";

/* =====================================================
   HEADERS CSV
===================================================== */

header('Content-Type: text/csv; charset=utf-8');

header(
    'Content-Disposition: attachment; filename=' .
    $filename
);

/* =====================================================
   OUTPUT
===================================================== */

$output = fopen('php://output', 'w');

/* =====================================================
   UTF8 BOM
===================================================== */

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

/* =====================================================
   ENTETES CSV
===================================================== */

fputcsv($output, [

    'ID',
    'Date',
    'Heure',
    'Patient',
    'Téléphone',
    'Chauffeur',
    'Adresse départ',
    'Adresse arrivée',
    'Ville arrivée',
    'Statut',
    'Notes'

], ';');

/* =====================================================
   SQL
===================================================== */

$stmt = $pdo->prepare("
SELECT
    c.*,
    u.prenom AS chauffeur
FROM courses c
LEFT JOIN users u
ON u.id = c.chauffeur_id
WHERE c.societe_id=?
ORDER BY c.date_course DESC,
c.heure_pickup DESC
");

$stmt->execute([
    $societe_id
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   EXPORT
===================================================== */

foreach($courses as $c){

    fputcsv($output, [

        $c['id'] ?? '',

        $c['date_course'] ?? '',

        $c['heure_pickup'] ?? '',

        $c['client_nom'] ?? '',

        $c['telephone'] ?? '',

        $c['chauffeur'] ?? '',

        $c['adresse_depart'] ?? '',

        $c['adresse_arrivee'] ?? '',

        $c['ville_arrivee'] ?? '',

        $c['statut'] ?? '',

        $c['notes'] ?? ''

    ], ';');
}

/* =====================================================
   FERMETURE
===================================================== */

fclose($output);

exit;
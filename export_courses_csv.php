<?php
require 'auth.php';
require 'config.php';

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export_courses.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID',
    'Date',
    'Heure prévue',
    'Patient',
    'Chauffeur',
    'Adresse départ',
    'Adresse arrivée',
    'Statut',
    'Départ réel',
    'Arrivée réelle'
], ';');

$stmt = $pdo->prepare("
SELECT c.*, u.prenom AS chauffeur
FROM courses c
LEFT JOIN users u ON u.id = c.chauffeur_id
WHERE c.societe_id=?
ORDER BY c.date_course DESC, c.heure_pickup DESC
");

$stmt->execute([$societe_id]);

while ($course = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $course['id'] ?? '',
        $course['date_course'] ?? '',
        $course['heure_pickup'] ?? '',
        $course['client_nom'] ?? '',
        $course['chauffeur'] ?? '',
        $course['adresse_depart'] ?? '',
        $course['adresse_arrivee'] ?? '',
        $course['statut'] ?? '',
        $course['depart_reel'] ?? '',
        $course['arrivee_reelle'] ?? ''
    ], ';');
}

fclose($output);
exit;
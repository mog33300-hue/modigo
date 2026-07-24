<?php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($pdo)) {
    require 'config.php';
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);
$role = $_SESSION['role'] ?? '';

if ($role === 'superadmin') {
    return;
}

if ($societe_id <= 0) {
    die("Société invalide");
}

$stmt = $pdo->prepare("
SELECT statut, date_expiration
FROM societes
WHERE id=?
LIMIT 1
");
$stmt->execute([$societe_id]);
$societe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$societe) {
    die("Société introuvable");
}

$statut = strtolower($societe['statut'] ?? '');
$date_expiration = $societe['date_expiration'] ?? null;

if ($statut !== 'active') {
    die("<h2>Compte désactivé</h2><p>Veuillez contacter l’administrateur.</p>");
}

if (!empty($date_expiration) && strtotime($date_expiration) < strtotime(date('Y-m-d'))) {
    die("
    <h2>Abonnement expiré</h2>
    <p>Votre période d'essai ou abonnement est terminé.</p>
    <p>Veuillez contacter Medigo pour réactiver votre accès.</p>
    ");
}
?>
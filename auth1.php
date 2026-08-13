<?php
session_start();

require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* TIMEOUT SESSION */
$timeout = 3600;

if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $timeout
) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$_SESSION['last_activity'] = time();

/* SESSION */
$user_id = intval($_SESSION['user_id']);
$societe_id = intval($_SESSION['societe_id'] ?? 0);

/* USER + SOCIETE */
$stmt = $pdo->prepare("
SELECT
    u.*,
    s.nom AS societe_nom,
    s.plan,
    s.statut,
    s.date_expiration
FROM users u
LEFT JOIN societes s ON s.id = u.societe_id
WHERE u.id=?
LIMIT 1
");

$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* REFRESH SESSION */
$_SESSION['role'] = $user['role'] ?? '';
$_SESSION['prenom'] = $user['prenom'] ?? '';
$_SESSION['email'] = $user['email'] ?? '';
$_SESSION['societe_id'] = $user['societe_id'] ?? $societe_id;
$_SESSION['societe_nom'] = $user['societe_nom'] ?? 'Medigo Synology';
$_SESSION['plan'] = $user['plan'] ?? 'basic';

/* SOCIETE DESACTIVEE */
if (
    isset($user['statut']) &&
    $user['statut'] !== 'active'
) {
    session_destroy();

    die("
    <h2>Société désactivée</h2>
    Contactez l'administrateur Medigo Synology.
    ");
}

/* ABONNEMENT EXPIRE */
$page_actuelle = basename($_SERVER['PHP_SELF']);

if (
    !in_array($_SESSION['role'] ?? '', ['admin','superadmin','chauffeur'], true) &&
    $page_actuelle !== 'subscription.php' &&
    $page_actuelle !== 'payment.php' &&
    $page_actuelle !== 'payment_success.php' &&
    $page_actuelle !== 'payment_cancel.php' &&
    !empty($user['date_expiration']) &&
    strtotime($user['date_expiration']) < strtotime(date('Y-m-d'))
) {
    header("Location: subscription.php");
    exit;
}

/* SECURITE CHAUFFEUR */
$role = $_SESSION['role'] ?? '';

$pages_autorisees_chauffeur = [
    'chauffeur_courses.php',
    'chauffeur_historique.php',
    'save_position.php',
    'logout.php'
];

if (
    $role === 'chauffeur' &&
    !in_array($page_actuelle, $pages_autorisees_chauffeur, true)
) {
    header("Location: chauffeur_courses.php");
    exit;
}
?>
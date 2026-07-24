<?php

require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);
$id = intval($_GET['id'] ?? 0);

if ($societe_id <= 0 || $id <= 0) {
    die("Course invalide");
}

$stmt = $pdo->prepare("
DELETE FROM courses
WHERE id=?
AND societe_id=?
LIMIT 1
");

$stmt->execute([
    $id,
    $societe_id
]);

header("Location: courses.php");
exit;
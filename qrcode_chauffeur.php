<?php
require 'auth.php';
require 'config.php';

$id = intval($_GET['id'] ?? 0);
$societe_id = intval($_SESSION['societe_id'] ?? 1);

if ($id <= 0) {
    die("Chauffeur invalide");
}

$stmt = $pdo->prepare("
SELECT token
FROM users
WHERE id=?
AND societe_id=?
AND role='chauffeur'
LIMIT 1
");

$stmt->execute([
    $id,
    $societe_id
]);

$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur || empty($chauffeur['token'])) {
    die("QR Code indisponible");
}

$url =
"https://minicartransgps.synology.me/minicartrans/chauffeur_mobile.php?token=" .
urlencode($chauffeur['token']);

header(
    "Location: https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=20&data=" .
    urlencode($url)
);

exit;
?>
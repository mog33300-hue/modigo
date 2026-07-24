<?php
require 'auth.php';
require 'config.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
    http_response_code(403);
    die('Accès refusé');
}

$message = '';
$erreur = '';

try {
    $col = $pdo->query("SHOW COLUMNS FROM courses LIKE 'gps_updated_at'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN gps_updated_at DATETIME NULL AFTER longitude");
        $message = 'Mise à jour installée : le suivi GPS temps réel est prêt.';
    } else {
        $message = 'La mise à jour GPS était déjà installée.';
    }
} catch (Throwable $e) {
    $erreur = $e->getMessage();
}
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation Sprint 4.1</title>
<style>body{font-family:Arial,sans-serif;background:#f3f4f6;padding:30px;color:#111827}.box{max-width:720px;margin:auto;background:#fff;padding:30px;border-radius:18px;box-shadow:0 8px 25px #0001}.ok{background:#dcfce7;color:#166534;padding:18px;border-radius:12px}.err{background:#fee2e2;color:#991b1b;padding:18px;border-radius:12px}a{display:inline-block;margin-top:20px;padding:12px 18px;background:#2563eb;color:#fff;text-decoration:none;border-radius:10px}</style></head>
<body><div class="box"><h1>Minicartrans — Sprint 4.1</h1>
<?php if ($erreur): ?><div class="err"><strong>Erreur :</strong> <?= htmlspecialchars($erreur) ?></div><?php else: ?><div class="ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<a href="gps_admin.php">Ouvrir le centre GPS</a></div></body></html>

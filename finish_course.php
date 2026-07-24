<?php
require 'config.php';

$id = $_GET['id'] ?? 0;

$pdo->prepare("
UPDATE courses 
SET arrivee_reelle = NOW(), statut='terminé'
WHERE id=?
")->execute([$id]);

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
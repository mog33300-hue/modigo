<?php
require 'config.php';

$id = $_GET['id'] ?? 0;

$pdo->prepare("
UPDATE courses 
SET depart_reel = NOW() 
WHERE id=?
")->execute([$id]);

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
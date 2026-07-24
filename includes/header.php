<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = $page_title ?? 'MODIGO';
$modigo_page_class = $modigo_page_class ?? '';
$modigo_extra_head = $modigo_extra_head ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/modigo.css?v=1.0.6">
<?= $modigo_extra_head ?>

<script src="assets/js/modigo.js?v=1.0.6" defer></script>
<link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>

<body class="<?= htmlspecialchars($modigo_page_class, ENT_QUOTES, 'UTF-8') ?>">
<div class="app">

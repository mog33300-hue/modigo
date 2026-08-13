<?php
require 'config.php';

$id = $_GET['id'];

$pdo->prepare("DELETE FROM patients WHERE id=?")->execute([$id]);

header("Location: patients.php");
exit;
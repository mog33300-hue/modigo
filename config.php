<?php

/* =====================================================
   LOGS ERREURS PHP
===================================================== */

ini_set(
    'log_errors',
    1
);

ini_set(
    'error_log',
    '/volume1/logs_minicartrans/php_errors.log'
);

error_reporting(E_ALL);

/* =====================================================
   CONFIGURATION MYSQL
===================================================== */

$host = 'localhost';

$dbname = 'minicartrans';

$user = 'root';

$pass = 'MiniCar2025!';

/* =====================================================
   CONNEXION PDO
===================================================== */

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch(PDOException $e){

    die(
        "Erreur connexion SQL : " .
        $e->getMessage()
    );
}

?>
<?php

require 'auth.php';
require 'config.php';

/* =====================================================
   SECURITE ADMIN
===================================================== */

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] != 'admin'
) {

    die('Accès refusé');
}

/* =====================================================
   CONFIG MYSQL
===================================================== */

$db_host = 'localhost';

$db_name = 'minicartrans';

/*
   ⚠ IMPORTANT
   Remplace par tes vrais identifiants MariaDB
*/

$db_user = 'root';

$db_pass = '';

/* =====================================================
   NOM FICHIER
===================================================== */

$backup_file =
'backup_minicartrans_' .
date('Ymd_His') .
'.sql';

/* =====================================================
   HEADER DOWNLOAD
===================================================== */

header('Content-Type: application/sql');

header(
    'Content-Disposition: attachment; filename="' .
    $backup_file .
    '"'
);

/* =====================================================
   MYSQLDUMP
===================================================== */

$command =
"mysqldump " .
"--host={$db_host} " .
"--user={$db_user} " .
"--password={$db_pass} " .
"{$db_name}";

/* =====================================================
   EXECUTION
===================================================== */

passthru($command);

exit;

?>
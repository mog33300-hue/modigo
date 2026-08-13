<?php

require 'config.php';

/* =====================================================
   DOSSIER BACKUP DSM
===================================================== */

$backup_dir =
'/volume1/backup_minicartrans/';

/* =====================================================
   VERIFICATION DOSSIER
===================================================== */

if (!is_dir($backup_dir)) {

    die('Dossier backup introuvable.');
}

/* =====================================================
   NOM FICHIER
===================================================== */

$backup_file =
$backup_dir .
'backup_' .
date('Ymd_His') .
'.sql';

/* =====================================================
   CREATION FICHIER
===================================================== */

$file = fopen($backup_file, 'w');

if (!$file) {

    die('Impossible de créer le fichier backup.');
}

/* =====================================================
   HEADER SQL
===================================================== */

fwrite($file, "-- =====================================\n");
fwrite($file, "-- MiniCarTrans SQL Backup\n");
fwrite($file, "-- Date : " . date('Y-m-d H:i:s') . "\n");
fwrite($file, "-- =====================================\n\n");

/* =====================================================
   TABLES
===================================================== */

$tables = $pdo
->query("SHOW TABLES")
->fetchAll(PDO::FETCH_COLUMN);

/* =====================================================
   EXPORT TABLES
===================================================== */

foreach ($tables as $table) {

    /* =====================================================
       STRUCTURE
    ===================================================== */

    $create = $pdo
    ->query("SHOW CREATE TABLE `$table`")
    ->fetch(PDO::FETCH_ASSOC);

    fwrite($file, "\n\n");
    fwrite($file, "-- Structure table : $table\n\n");

    fwrite(
        $file,
        "DROP TABLE IF EXISTS `$table`;\n"
    );

    fwrite(
        $file,
        $create['Create Table'] . ";\n\n"
    );

    /* =====================================================
       DONNEES
    ===================================================== */

    $rows = $pdo->query("SELECT * FROM `$table`");

    foreach ($rows as $row) {

        $values = [];

        foreach (array_values($row) as $value) {

            if ($value === null) {

                $values[] = "NULL";

            } else {

                $values[] = $pdo->quote($value);
            }
        }

        $sql =
        "INSERT INTO `$table` VALUES (" .
        implode(',', $values) .
        ");\n";

        fwrite($file, $sql);
    }
}

/* =====================================================
   FERMETURE
===================================================== */

fclose($file);

/* =====================================================
   RESULTAT
===================================================== */

echo "✅ Sauvegarde SQL créée avec succès<br><br>";

echo $backup_file;

?>
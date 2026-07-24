<?php

require __DIR__ . '/config.php';

/* =====================================================
   DOSSIER SAUVEGARDE
===================================================== */

$backup_dir = __DIR__ . '/sauvegardes/';

/* =====================================================
   CREATION DOSSIER
===================================================== */

if (!is_dir($backup_dir)) {

    mkdir($backup_dir, 0777, true);
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

    die('Impossible de créer le fichier backup');
}

/* =====================================================
   HEADER SQL
===================================================== */

fwrite($file, "-- MiniCarTrans Backup\n");
fwrite($file, "-- Date : " . date('Y-m-d H:i:s') . "\n\n");

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

    /* =========================
       CREATE TABLE
    ========================= */

    $create = $pdo
        ->query("SHOW CREATE TABLE `$table`")
        ->fetch(PDO::FETCH_ASSOC);

    fwrite($file, "\n\n");
    fwrite($file, "DROP TABLE IF EXISTS `$table`;\n");
    fwrite($file, $create['Create Table'] . ";\n\n");

    /* =========================
       DONNEES
    ========================= */

    $rows = $pdo->query("SELECT * FROM `$table`");

    while ($row = $rows->fetch(PDO::FETCH_NUM)) {

        $values = [];

        foreach ($row as $value) {

            if ($value === null) {

                $values[] = "NULL";

            } else {

                $values[] = $pdo->quote($value);
            }
        }

        fwrite(
            $file,
            "INSERT INTO `$table` VALUES (" .
            implode(',', $values) .
            ");\n"
        );
    }
}

/* =====================================================
   FERMETURE
===================================================== */

fclose($file);

/* =====================================================
   MESSAGE
===================================================== */

echo "
<h2 style='color:green;font-family:Arial;'>

✅ SAUVEGARDE OK

</h2>

<p>

Fichier :

<br><br>

<b>$backup_file</b>

</p>
";
?>
<?php
require 'auth.php';
require 'config.php';
$role=$_SESSION['role']??'';
if(!in_array($role,['admin','superadmin'],true)){die('Accès refusé');}
$messages=[];
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS chauffeur_positions (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 societe_id INT NOT NULL,
 chauffeur_id INT NOT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy DECIMAL(10,2) NULL,
 speed DECIMAL(10,2) NULL,
 heading DECIMAL(10,2) NULL,
 battery_level TINYINT UNSIGNED NULL,
 network_type VARCHAR(30) NULL,
 service_status ENUM('hors_service','en_service','pause') NOT NULL DEFAULT 'hors_service',
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 UNIQUE KEY uq_chauffeur_societe(societe_id,chauffeur_id),
 KEY idx_updated_at(updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$messages[]='Table chauffeur_positions prête.';
}catch(Throwable $e){$messages[]='Erreur : '.$e->getMessage();}
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation Sprint 4.1.1</title><style>body{font-family:Arial;background:#f3f4f6;padding:30px}.box{max-width:760px;margin:auto;background:#fff;padding:28px;border-radius:18px;box-shadow:0 5px 20px #0001}h1{color:#2563eb}.ok{background:#dcfce7;color:#166534;padding:14px;border-radius:12px;margin:10px 0}.warn{background:#fef3c7;color:#92400e;padding:14px;border-radius:12px}</style></head><body><div class="box"><h1>MODIGO — Sprint 4.1.1</h1><?php foreach($messages as $m): ?><div class="ok"><?=htmlspecialchars($m)?></div><?php endforeach; ?><div class="warn"><strong>Important :</strong> supprimez ce fichier après l'installation. Le GPS mobile nécessite une adresse HTTPS avec certificat valide.</div><p><a href="chauffeur_courses.php">Tester l'espace chauffeur</a> · <a href="gps_admin.php">Ouvrir le centre GPS</a></p></div></body></html>
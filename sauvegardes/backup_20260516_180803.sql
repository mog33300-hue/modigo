-- MiniCarTrans Backup
-- Date : 2026-05-16 18:08:03



DROP TABLE IF EXISTS `chauffeurs`;
CREATE TABLE `chauffeurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'disponible',
  `company_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `chauffeurs` VALUES ('1','Dominique','0672438682','1','occupé','1');
INSERT INTO `chauffeurs` VALUES ('2','Lopez Patrick','0633371326','2','disponible','1');
INSERT INTO `chauffeurs` VALUES ('3','Trottier Patrick','0641163078','3','disponible','1');
INSERT INTO `chauffeurs` VALUES ('4','Vay Thierry','0662939630','4','disponible','1');
INSERT INTO `chauffeurs` VALUES ('5','Henrique','0648148542','5','disponible','1');
INSERT INTO `chauffeurs` VALUES ('6','Didier','0611524449','6','disponible','1');
INSERT INTO `chauffeurs` VALUES ('7','Juliette ','0698374517','7','disponible','1');
INSERT INTO `chauffeurs` VALUES ('8','Romain','0677022168','8','disponible','1');
INSERT INTO `chauffeurs` VALUES ('9','Rodriguer Patrick','0662911010','9','disponible','1');


DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;



DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `companies` VALUES ('1','Admin','2026-03-31 10:16:30','1');
INSERT INTO `companies` VALUES ('2','Admin','2026-03-31 10:23:31','1');


DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_nom` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'en cours',
  `chauffeur_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `heure_pickup` time DEFAULT NULL,
  `heure_depot` time DEFAULT NULL,
  `type_course` varchar(50) DEFAULT NULL,
  `type_pmr` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_course` date DEFAULT NULL,
  `adresse_depart` varchar(255) DEFAULT NULL,
  `adresse_arrivee` varchar(255) DEFAULT NULL,
  `ordre` int(11) DEFAULT 0,
  `depart_reel` time DEFAULT NULL,
  `arrivee_reelle` time DEFAULT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `ville_arrivee` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `courses` VALUES ('3','dominique','0672438682',NULL,NULL,'terminé','2','1','2026-04-28 20:03:56','14:00:00','14:30:00','aller_retour','','',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL);
INSERT INTO `courses` VALUES ('4','garrido sylvia',NULL,NULL,NULL,'terminé','9','1','2026-05-02 13:13:02','14:13:00',NULL,NULL,NULL,NULL,'2026-05-02','50 ave marcel dassault 33300 bordeaux','tour de gasy','1',NULL,NULL,NULL,NULL);
INSERT INTO `courses` VALUES ('5','fouleymata dominique',NULL,NULL,NULL,'en cours','12','1','2026-05-05 19:34:49','20:35:00',NULL,NULL,NULL,NULL,'2026-05-05','50 ave marcel dassault 33300 bordeaux','tour de gasy','1','23:01:27',NULL,NULL,NULL);
INSERT INTO `courses` VALUES ('6','Da Costa Da Costa','07889943',NULL,NULL,'prévu','12','1','2026-05-06 07:19:33','07:19:00',NULL,NULL,NULL,NULL,'2026-05-06','2 impasses des embruns sainte helene',NULL,'0',NULL,NULL,NULL,NULL);
INSERT INTO `courses` VALUES ('7','? Coustaud','0611393397',NULL,NULL,'prévu','12','1','2026-05-06 08:58:30','11:31:00',NULL,NULL,NULL,NULL,'2026-05-06','50 ave marcel dassault 33300 bordeaux','tour de gasy','0',NULL,NULL,NULL,'blanquefort');
INSERT INTO `courses` VALUES ('8','Coustaud','0611393397',NULL,NULL,'prévu','12','1','2026-05-09 23:11:50','23:10:00',NULL,NULL,NULL,'Ok','2026-05-10','77 rue des palus','La tour','0',NULL,NULL,NULL,'Blanquefort');
INSERT INTO `courses` VALUES ('9','Irribarria','0609137109',NULL,NULL,'prévu','12','1','2026-05-11 10:45:28','12:47:00',NULL,NULL,NULL,'','2026-05-13','41E rue des sablons','tours','0',NULL,NULL,NULL,'blanquefort');
INSERT INTO `courses` VALUES ('10','Coustaud','0611393397',NULL,NULL,'prévu','12','1','2026-05-15 15:28:02','11:00:00',NULL,NULL,NULL,'','2026-05-16','77 rue des palus','la tours','0',NULL,NULL,NULL,'Bordeaux');


DROP TABLE IF EXISTS `gps_positions`;
CREATE TABLE `gps_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24193 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `gps_positions` VALUES ('24183','1','44.88','-0.57','2026-04-21 14:24:51');
INSERT INTO `gps_positions` VALUES ('24184','1','44.88','-0.57','2026-04-21 18:35:56');
INSERT INTO `gps_positions` VALUES ('24185','1','44.88','-0.57','2026-04-21 19:50:48');
INSERT INTO `gps_positions` VALUES ('24186','1','44.88','-0.57','2026-04-22 06:28:35');
INSERT INTO `gps_positions` VALUES ('24187','1','44.88','-0.57','2026-04-22 09:41:08');
INSERT INTO `gps_positions` VALUES ('24188','1','44.88','-0.57','2026-04-22 10:17:05');
INSERT INTO `gps_positions` VALUES ('24189','1','44.88','-0.57','2026-04-23 10:36:35');
INSERT INTO `gps_positions` VALUES ('24190','1','44.88','-0.57','2026-04-23 14:18:57');
INSERT INTO `gps_positions` VALUES ('24191','1','44.88','-0.57','2026-04-23 19:05:01');
INSERT INTO `gps_positions` VALUES ('24192','1','44.88','-0.57','2026-04-23 19:57:59');


DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `besoin_transport` varchar(100) DEFAULT NULL,
  `fauteuil` varchar(20) DEFAULT 'non',
  `accompagnant` varchar(20) DEFAULT 'non',
  `notes_transport` text DEFAULT NULL,
  `company_id` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `pmr` tinyint(1) DEFAULT 0,
  `telephone2` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `patients` VALUES ('4','fouleymata','dominique','0672438682','50 ave marcel dassault home by ginko log 1303',NULL,'bordeaux',NULL,'non','non',NULL,'1','2026-05-03 22:55:24','0',NULL,NULL,NULL);
INSERT INTO `patients` VALUES ('5','fouleymata','dominique','0672438682','50 ave marcel dassault home by ginko log 1303',NULL,'bordeaux',NULL,'non','non',NULL,'1','2026-05-05 19:34:00','1',NULL,NULL,NULL);
INSERT INTO `patients` VALUES ('6','Coustaud','?','0611393397','77 rue des palus',NULL,'Parempuyre',NULL,'non','non',NULL,'1','2026-05-06 06:43:46','0',NULL,NULL,NULL);
INSERT INTO `patients` VALUES ('7','Irribarria','Irribarria','0609137109','41E rue des sablons',NULL,'saint medard en jalles',NULL,'non','non',NULL,'1','2026-05-06 06:46:42','1',NULL,NULL,NULL);
INSERT INTO `patients` VALUES ('8','Da Costa','Da Costa','07889943','2 impasses des embruns',NULL,'sainte helene',NULL,'non','non',NULL,'1','2026-05-06 06:48:31','0',NULL,NULL,NULL);


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `vehicule` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_company_id` (`company_id`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `users` VALUES ('3','1','patient@test.com','1234','patient','2026-04-28 15:24:46','','','246dc6b3a35f80840c969989db7bb256',NULL);
INSERT INTO `users` VALUES ('8','1','admin@test.com','$2y$10$kzn0yufCZMupHqiCD0lXcO/0mVS5yxFBYPpzUK2H5eQq7.53m5hE.','admin','2026-05-01 14:17:13','Admin','0600000000','08e37834a93aa58b6169581aafcf31fc',NULL);
INSERT INTO `users` VALUES ('12','1','Dominique@minicartrans.fr','$2y$10$lsM1L0E2S.cgRYQN3fspfuCD/P9dM653xbtsq3x9QztDJ26sBz5I6','chauffeur','2026-05-05 19:30:19','dominique','0672438682','85723e7d7094d5235bb170b2e4909dd4','Voiture1');
INSERT INTO `users` VALUES ('13','1','gestion@test.fr','$2y$10$/vGko1YO4RycGAiLEyQfr.HOmfI6uqo/nH8Lu24dXwDGEHH9LdyJ.','gestion','2026-05-11 20:46:58','gestion',NULL,NULL,NULL);


DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `plate` varchar(50) DEFAULT NULL,
  `type` varchar(20) DEFAULT 'standard',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `vehicles` VALUES ('2','1','Fouleymata','GR806CM','standard');

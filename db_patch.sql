CREATE TABLE IF NOT EXISTS `wo_safety_checks` (
  `safety_id` int(11) NOT NULL AUTO_INCREMENT,
  `check_text` varchar(255) NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`safety_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `wo_safety_completions` (
  `completion_id` int(11) NOT NULL AUTO_INCREMENT,
  `wo_id` int(11) NOT NULL,
  `safety_id` int(11) NOT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`completion_id`),
  UNIQUE KEY `idx_wo_safety` (`wo_id`,`safety_id`),
  KEY `safety_id` (`safety_id`),
  KEY `completed_by` (`completed_by`),
  CONSTRAINT `fk_safety_comp_wo` FOREIGN KEY (`wo_id`) REFERENCES `work_orders` (`wo_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_safety_comp_check` FOREIGN KEY (`safety_id`) REFERENCES `wo_safety_checks` (`safety_id`),
  CONSTRAINT `fk_safety_comp_user` FOREIGN KEY (`completed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `wo_safety_checks` (`safety_id`, `check_text`, `is_mandatory`, `sort_order`) VALUES
(1, 'Device Unplugged & Discharged', 1, 10),
(2, 'Anti-Static Wristband Applied', 1, 20),
(3, 'Data Backed Up', 0, 30),
(4, 'Work Area Clear', 1, 40);

-- 0_ksf_em_routes
-- Table definition + pre-seed data for ksf_em_routes.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

CREATE TABLE IF NOT EXISTS `0_ksf_em_routes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `to_address` VARCHAR(255) NOT NULL,
  `action` VARCHAR(20) NOT NULL,
  `keywords` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `priority` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_to_address` (`to_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 0_ksf_em_mailing_lists
-- Table definition + pre-seed data for ksf_em_mailing_lists.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

CREATE TABLE IF NOT EXISTS `0_ksf_em_mailing_lists` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `list_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `from_address` VARCHAR(255) DEFAULT NULL,
  `from_name` VARCHAR(100) DEFAULT NULL,
  `reply_to` VARCHAR(255) DEFAULT NULL,
  `subscription_type` VARCHAR(20) DEFAULT 'double_optin',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_list_name` (`list_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


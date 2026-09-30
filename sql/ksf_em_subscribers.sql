-- 0_ksf_em_subscribers
-- Table definition + pre-seed data for ksf_em_subscribers.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

CREATE TABLE IF NOT EXISTS `0_ksf_em_subscribers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `list_id` INT(11) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `debtor_no` INT(11) DEFAULT NULL,
  `contact_id` INT(11) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'pending',
  `unsubscribe_token` VARCHAR(50) DEFAULT NULL,
  `subscribe_token` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_list_id` (`list_id`),
  KEY `idx_email` (`email`),
  KEY `idx_debtor` (`debtor_no`),
  UNIQUE KEY `idx_list_email` (`list_id`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 0_ksf_em_inbound_emails
-- Table definition + pre-seed data for ksf_em_inbound_emails.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

CREATE TABLE IF NOT EXISTS `0_ksf_em_inbound_emails` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `message_id` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `from_address` VARCHAR(255) DEFAULT NULL,
  `from_name` VARCHAR(255) DEFAULT NULL,
  `to_address` VARCHAR(255) DEFAULT NULL,
  `cc_addresses` TEXT,
  `bcc_addresses` TEXT,
  `body_text` TEXT,
  `body_html` TEXT,
  `attachments` TEXT,
  `received_date` DATETIME DEFAULT NULL,
  `raw_headers` TEXT,
  `routing_action` VARCHAR(20) DEFAULT NULL,
  `linked_entity_id` INT(11) DEFAULT NULL,
  `linked_entity_type` VARCHAR(20) DEFAULT NULL,
  `debtor_no` INT(11) DEFAULT NULL,
  `contact_id` INT(11) DEFAULT NULL,
  `account_id` INT(11) DEFAULT NULL,
  `is_processed` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_routing` (`routing_action`, `linked_entity_id`),
  KEY `idx_debtor` (`debtor_no`),
  KEY `idx_is_processed` (`is_processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


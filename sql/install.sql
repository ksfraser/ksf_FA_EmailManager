-- ksf_FA_EmailManager module schema.
-- FA install engine replaces the literal 0_ prefix with TB_PREF; do not use
-- {TB_PREF} or @TB_PREF@ here (see AGENTS.md "FA extension install &
-- activation mechanics").
--
-- fa_em_accounts is the mailbox-of-record for BR-CRM-02 (ksf_FA_CRM). The
-- auto_import/import_frequency/last_import columns migrated in from the
-- retired 0_fa_crm_email_accounts so the periodic import worker has a home.

CREATE TABLE IF NOT EXISTS `0_fa_em_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_name` VARCHAR(100) NOT NULL,
  `email_address` VARCHAR(255) NOT NULL,
  `account_type` VARCHAR(20) DEFAULT 'imap',
  `server_host` VARCHAR(100) DEFAULT NULL,
  `server_port` INT(11) DEFAULT 993,
  `encryption` VARCHAR(10) DEFAULT 'ssl',
  `username` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `sync_folder` VARCHAR(50) DEFAULT 'INBOX',
  `is_active` TINYINT(1) DEFAULT 1,
  `debtor_no` INT(11) DEFAULT NULL,
  `contact_id` INT(11) DEFAULT NULL,
  `auto_import` TINYINT(1) DEFAULT 0,
  `import_frequency` INT(11) DEFAULT 60,
  `last_import` DATETIME DEFAULT NULL,
  `last_sync` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email_address` (`email_address`),
  KEY `idx_debtor_no` (`debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `0_fa_em_inbound_emails` (
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

CREATE TABLE IF NOT EXISTS `0_fa_em_mailing_lists` (
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

CREATE TABLE IF NOT EXISTS `0_fa_em_subscribers` (
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

CREATE TABLE IF NOT EXISTS `0_fa_em_routes` (
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

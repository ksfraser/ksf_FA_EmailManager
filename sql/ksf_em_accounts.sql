-- 0_ksf_em_accounts
-- Table definition + pre-seed data for ksf_em_accounts.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- ksf_FA_EmailManager module schema.
-- FA install engine replaces the literal 0_ prefix with TB_PREF; do not use
-- {TB_PREF} or @TB_PREF@ here (see AGENTS.md "FA extension install &
-- activation mechanics").
--
-- fa_em_accounts is the mailbox-of-record for BR-CRM-02 (ksf_FA_CRM). The
-- auto_import/import_frequency/last_import columns migrated in from the
-- retired 0_fa_crm_email_accounts so the periodic import worker has a home.

CREATE TABLE IF NOT EXISTS `0_ksf_em_accounts` (
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


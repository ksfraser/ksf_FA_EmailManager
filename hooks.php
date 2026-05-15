<?php
/**
 * FA_EmailManager Module Hooks for FrontAccounting
 */

define('SS_EMAIL', 126 << 8);

class hooks_fa_emailmanager extends hooks {
    var $module_name = 'fa_emailmanager';
    var $version = '1.0.0';

    function install_options($app) {
        global $path_to_root;

        switch($app->id) {
            case 'CRM':
                $app->add_lapp_function(0, _("Email Accounts"),
                    $path_to_root."/modules/".$this->module_name."/accounts.php", 'SA_EMAILMANAGE', MENU_MAINTENANCE);
                $app->add_lapp_function(1, _("Inbound Emails"),
                    $path_to_root."/modules/".$this->module_name."/inbound.php", 'SA_EMAILVIEW', MENU_INQUIRY);
                $app->add_lapp_function(2, _("Mailing Lists"),
                    $path_to_root."/modules/".$this->module_name."/lists.php", 'SA_EMAILMANAGE', MENU_ENTRY);
                $app->add_rapp_function(3, _("Email Routes"),
                    $path_to_root."/modules/".$this->module_name."/routes.php", 'SA_EMAILMANAGE', MENU_MAINTENANCE);
                break;
        }
    }

    function install_access() {
        $security_sections[SS_EMAIL] = _("Email Management");
        $security_areas['SA_EMAILVIEW'] = array(SS_EMAIL | 1, _("View Emails"));
        $security_areas['SA_EMAILMANAGE'] = array(SS_EMAIL | 2, _("Manage Emails"));
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only=true) {
        $updates = array('sql/update.sql' => array($this->module_name));
        $ok = $this->update_databases($company, $updates, $check_only);
        if ($check_only || !$ok) {
            return $ok;
        }
        $this->ensure_email_schema();
        return $ok;
    }

    private function table_exists($table) {
        $sql = "SHOW TABLES LIKE " . db_escape($table);
        $res = db_query($sql, 'Failed checking table existence');
        return db_num_rows($res) > 0;
    }

    private function ensure_email_schema() {
        $tables = array(
            TB_PREF . "fa_em_accounts" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_accounts` (
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
                    `last_sync` DATETIME DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_email_address` (`email_address`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            TB_PREF . "fa_em_inbound_emails" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_inbound_emails` (
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
                    `attachments` JSON,
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            TB_PREF . "fa_em_mailing_lists" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_mailing_lists` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            TB_PREF . "fa_em_subscribers" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_subscribers` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            TB_PREF . "fa_em_routes" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_routes` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `to_address` VARCHAR(255) NOT NULL,
                    `action` VARCHAR(20) NOT NULL,
                    `keywords` TEXT,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `priority` INT(11) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_to_address` (`to_address`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        foreach ($tables as $table_name => $sql) {
            db_query($sql, "Could not create Email Manager table: $table_name");
        }
    }

    function db_prevoid($trans_type, $trans_no) {
        // Handle voiding if needed
    }
}
?>

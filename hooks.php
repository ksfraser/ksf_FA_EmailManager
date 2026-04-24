<?php
/**
 * FA_EmailManager Module Hooks for FrontAccounting
 */

$module_name = 'FA_EmailManager';
$module_version = '1.0.0';
$module_description = 'Email Management - IMAP import, routing, mailing lists';
$module_author = 'KSFII Development Team';
$module_category = 'CRM';

function fa_em_install(): bool
{
    global $db;

    @include_once __DIR__ . '/vendor-src/Ksfraser/Common/ComposerDependencyManager.php';
    if (class_exists('Ksfraser\Common\ComposerDependencyManager')) {
        $composerMgr = new \Ksfraser\Common\ComposerDependencyManager(__DIR__);
        $composerMgr->ensureDependencies();
        @include_once $composerMgr->getAutoloadPath();
    }

    if (!fa_em_create_tables()) return false;
    if (!fa_em_insert_initial_data()) return false;
    return true;
}

function fa_em_activate(): bool
{
    @include_once __DIR__ . '/vendor-src/Ksfraser/Common/ComposerDependencyManager.php';
    if (class_exists('Ksfraser\Common\ComposerDependencyManager')) {
        $composerMgr = new \Ksfraser\Common\ComposerDependencyManager(__DIR__);
        $composerMgr->ensureDependencies();
        @include_once $composerMgr->getAutoloadPath();
    }

    add_hook('email_received', 'fa_em_on_email_received');
    add_hook('ticket_created', 'fa_em_on_ticket_created');
    add_hook('opportunity_created', 'fa_em_on_opportunity_created');
    return true;
}

function fa_em_deactivate(): bool { return true; }
function fa_em_uninstall(): bool { return true; }

function fa_em_create_tables(): bool
{
    global $db;

    $tables = [
        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_accounts` (
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

        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_inbound_emails` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `message_id` VARCHAR(255) DEFAULT NULL,
            `subject` VARCHAR(255) DEFAULT NULL,
            `from_address` VARCHAR(255) DEFAULT NULL,
            `from_name` VARCHAR(255) DEFAULT NULL,
            `to_address` VARCHAR(255)_DEFAULT NULL,
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

        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_mailing_lists` (
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

        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_subscribers` (
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

        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_em_routes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `to_address` VARCHAR(255) NOT NULL,
            `action` VARCHAR(20) NOT NULL,
            `keywords` TEXT,
            `is_active` TINYINT(1) DEFAULT 1,
            `priority` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_to_address` (`to_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        if (!db_query($sql, "Could not create email table")) return false;
    }
    return true;
}

function fa_em_insert_initial_data(): bool
{
    $routes = [
        ['support@', 'ticket', 'help,issue,problem,refund'],
        ['sales@', 'opportunity', 'interested,quote,price'],
        ['info@', 'lead', ''],
    ];

    foreach ($routes as $route) {
        db_query("INSERT IGNORE INTO " . TB_PREF . "fa_em_routes 
            (to_address, action, keywords, is_active) 
            VALUES ('" . db_escape($route[0]) . "', '" . db_escape($route[1]) . "', '" . db_escape($route[2]) . "', 1)");
    }

    db_query("INSERT IGNORE INTO " . TB_PREF . "fa_em_mailing_lists 
        (list_name, description, from_address, subscription_type) 
        VALUES ('default', 'Default Mailing List', 'noreply@" . get_company_pref('coy_name') . "', 'double_optin')");

    return true;
}

function fa_em_on_email_received($emailId) { error_log("Email received: $emailId"); }
function fa_em_on_ticket_created($ticketId) { error_log("Ticket created from email: $ticketId"); }
function fa_em_on_opportunity_created($opportunityId) { error_log("Opportunity created from email: $opportunityId"); }
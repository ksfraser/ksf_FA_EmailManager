<?php
/**
 * KSF FrontAccounting Module Hooks
 *
 * STANDARD PATTERNS:
 *
 * 1. ADDING MODULE TABS
 *    Define a class extending 'application' in hooks.php.
 *    Return new instance from install_tabs().
 *    Include add_extensions() to load other modules' install_options.
 *
 * 2. ADDING MENU ITEMS TO EXISTING APPS
 *    Use install_options() with switch($app->id).
 *    Use add_module() + add_lapp_function() for new menu section.
 *
 * 3. DATABASE SCHEMA
 *    DO NOT create tables in PHP code.
 *    Use sql/install.sql with literal 0_ table prefixes.
 *    Call $this->update_databases() in activate_extension().
 *
 * 4. SECURITY
 *    Define SS_<MODULE> constant (section << 8).
 *    Define SA_<MODULE>VIEW and SA_<MODULE>MANAGE in install_access().
 *
 * @package KsfFA_ksf_FA_EmailManager
 * @version 2.4.3-0
 */

define('SS_ksf_FA_EmailManager', 122 << 8);

$moduleAutoload = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($moduleAutoload)) {
    require_once $moduleAutoload;
}

class hooks_ksf_FA_EmailManager extends hooks {
    use \Ksfraser\Traits\HookQueryProviderTrait;

    var $module_name = 'ksf_FA_EmailManager';
    var $version = '2.4.3-0';

    /**
     * Add module tab
     *
     * @param application|null $app Ignored
     * @return application|null New tab application instance or nothing
     */
    function install_tabs($app) {
    }

    /**
     * Add menu items to the CRM app. EmailManager owns the mailbox account
     * editor; the CRM must not carry a second copy of it (see #25).
     *
     * Only pages that exist are linked; missing pages are not stubbed.
     */
    function install_options($app) {
        global $path_to_root;

        switch($app->id) {
            case 'CRM':
                $page = $path_to_root . "/modules/" . $this->module_name . "/";
                $app->add_lapp_function(0, _("Email Accounts"),
                    $page."accounts.php", 'SA_ksf_FA_EmailManagerMANAGE', MENU_MAINTENANCE);
                $app->add_lapp_function(1, _("Inbound Emails"),
                    $page."inbox.php", 'SA_ksf_FA_EmailManagerVIEW', MENU_INQUIRY);
                $app->add_lapp_function(2, _("Mailing Lists"),
                    $page."mailing_lists.php", 'SA_ksf_FA_EmailManagerMANAGE', MENU_ENTRY);
                $app->add_lapp_function(3, _("Email Campaigns"),
                    $page."campaigns.php", 'SA_ksf_FA_EmailManagerVIEW', MENU_ENTRY);
                break;
        }
    }

    /**
     * Define security areas
     *
     * @return array [0] => $security_areas, [1] => $security_sections
     */
    function install_access() {
        $security_sections[SS_ksf_FA_EmailManager] = _("Email Management");
        $security_areas['SA_ksf_FA_EmailManagerVIEW'] = array(
            SS_ksf_FA_EmailManager | 1,
            _("View ")
        );
        $security_areas['SA_ksf_FA_EmailManagerMANAGE'] = array(
            SS_ksf_FA_EmailManager | 2,
            _("Manage ")
        );
        return array($security_areas, $security_sections);
    }

    /**
     * Activate extension
     *
     * One sql/<tablename>.sql per table, each holding that table's definition
     * plus any pre-seed data. update_databases() gates each file on its own
     * table, so a partially-installed database gets the missing tables
     * individually.
     *
     * @param int $company Company number
     * @param bool $check_only Only check if activation possible
     * @return bool Success
     */
    function activate_extension($company, $check_only=true) {
        $this->ensure_composer_dependencies();

        $updates = array(
            'ksf_em_accounts.sql'        => array('ksf_em_accounts'),
            'ksf_em_inbound_emails.sql'  => array('ksf_em_inbound_emails'),
            'ksf_em_mailing_lists.sql'   => array('ksf_em_mailing_lists'),
            'ksf_em_subscribers.sql'     => array('ksf_em_subscribers'),
            'ksf_em_routes.sql'          => array('ksf_em_routes'),
        );
        $ok = $this->update_databases($company, $updates, $check_only);

        if (!$check_only && $ok) {
            $this->retire_misnamed_tables($company);
        }

        return $ok;
    }

    /**
     * Run sql/upgrade_2.4.3-1.sql, which drops the misnamed 0_fa_em_* tables.
     *
     * Deliberately NOT part of the update_databases() map: that gates on
     * "table missing => run this file", which is the inverse of what a cleanup
     * script needs. Driven explicitly, and only when there is something to do.
     *
     * @param int $company Company number
     * @return bool
     */
    private function retire_misnamed_tables($company) {
        global $db_connections;

        $legacy = array('fa_em_accounts', 'fa_em_inbound_emails',
            'fa_em_mailing_lists', 'fa_em_subscribers', 'fa_em_routes');

        $present = false;
        foreach ($legacy as $table) {
            $res = db_query("SHOW TABLES LIKE " . db_escape(TB_PREF . $table), 'Cannot check table');
            if (db_num_rows($res) > 0) {
                $present = true;
                break;
            }
        }
        if (!$present) {
            return true; // already cut over
        }

        $file = dirname(__FILE__) . '/sql/upgrade_2.4.3-1.sql';
        if (!file_exists($file)) {
            return true;
        }

        $conn = ($company == -1) ? $db_connections
            : array($company => $db_connections[$company]);
        foreach ($conn as $comp => $con) {
            set_global_connection($comp);
            if (!db_import($file, $con)) {
                db_close();
                return false;
            }
            db_close();
        }
        return true;
    }

    /**
     * Return all values this module advertises via the query hook system.
     *
     * @return array<string, mixed>
     */
    protected function _getAdvertisedValues(): array
    {
        return array(
            'emailmanager.version'       => $this->version,
            'emailmanager.module_name'   => $this->module_name,
            'emailmanager.hooks_version' => '2.0',
        );
    }

    /**
     * Install composer dependencies if needed
     */
    private function ensure_composer_dependencies() {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';

        if (file_exists($autoload_path)) {
            return;
        }

        $composer_path = $module_dir . '/composer.json';
        if (!file_exists($composer_path)) {
            return;
        }

        chdir($module_dir);
        $output = array();
        $return_code = 0;
        exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            error_log('KSF Module: composer install failed: ' . implode("\n", $output));
        }
    }
}

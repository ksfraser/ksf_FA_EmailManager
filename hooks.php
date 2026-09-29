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
     * @param int $company Company number
     * @param bool $check_only Only check if activation possible
     * @return bool Success
     */
    function activate_extension($company, $check_only=true) {
        $this->ensure_composer_dependencies();

        if (file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            $updates = array('install.sql' => array($this->module_name));
            return $this->update_databases($company, $updates, $check_only);
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

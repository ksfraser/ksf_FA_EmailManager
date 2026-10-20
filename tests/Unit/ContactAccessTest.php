<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FA\EmailManager;

use PHPUnit\Framework\TestCase;

/**
 * Two table names in this module addressed tables that exist nowhere:
 *
 *  - 0_crm_opportunities - the CRM module's table is 0_ksf_crm_opportunities
 *  - 0_crm_campaigns    - no schema in the estate creates a campaigns table;
 *                          the CRM module owns the catalogue
 *                          (0_ksf_crm_campaigns)
 *
 * Both statements therefore failed with MySQL error 1146.
 */
class ContactAccessTest extends TestCase
{
    private string $moduleDir;

    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }

    public function testRoutingDoesNotAddressTheNonExistentCrmOpportunitiesTable(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/includes/em_routing.inc');

        $this->assertStringNotContainsString(
            'TB_PREF . "crm_opportunities',
            $contents,
            'the CRM opportunities table is 0_ksf_crm_opportunities, not 0_crm_opportunities'
        );
        $this->assertStringContainsString(
            'TB_PREF . "ksf_crm_opportunities',
            $contents,
            'an inbound email must be filed against the CRM module\'s own opportunities table'
        );
    }

    public function testOpportunityInsertUsesTheCrmTablesColumnNames(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/includes/em_routing.inc');

        foreach (['opportunity_name', 'notes', 'created_at'] as $column) {
            $this->assertStringContainsString($column, $contents);
        }
        $this->assertStringNotContainsString('created_date', $contents);
    }

    public function testCampaignPickerDoesNotAddressTheNonExistentCrmCampaignsTable(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/pages/campaigns.php');

        $this->assertStringNotContainsString(
            'TB_PREF . "crm_campaigns',
            $contents,
            'the campaign catalogue is 0_ksf_crm_campaigns, not 0_crm_campaigns'
        );
        $this->assertStringContainsString(
            'TB_PREF . "ksf_crm_campaigns',
            $contents,
            'the campaign picker must read the CRM module\'s campaign catalogue'
        );
    }

    public function testCampaignLinksAreReadFromTheTableTheyAreWrittenTo(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/pages/campaigns.php');

        $this->assertStringNotContainsString(
            'ksf_em_campaign_links',
            $contents,
            'the link table is 0_ksf_em_campaign_lists; a second name for the same rows is a bug'
        );
    }

    public function testSchemaCreatesTheCampaignLinksTable(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/sql/ksf_em_campaign_lists.sql');

        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `0_ksf_em_campaign_lists`',
            $contents
        );
        $this->assertStringContainsString('`campaign_id`', $contents);
        $this->assertStringContainsString('`list_id`', $contents);
    }

    public function testActivationInstallsTheCampaignLinksTable(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/hooks.php');

        $this->assertStringContainsString(
            "'ksf_em_campaign_lists.sql'  => array('ksf_em_campaign_lists')",
            $contents,
            'activate_extension() must install the campaign-links table'
        );
    }
}

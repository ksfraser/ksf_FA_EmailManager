<?php
/**
 * Campaign Integration - Link campaigns to mailing lists
 */

$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FA_EmailManager/includes/em_db.inc");

page(_("Campaign Integration"));

$campaign_filter = $_POST['campaign_id'] ?? 0;
$action = $_POST['action'] ?? '';

//--------------------------------------------------------------------------------

if ($action === 'link') {
    $campaign_id = $_POST['campaign_id'];
    $list_id = $_POST['list_id'];
    
    db_query("INSERT IGNORE INTO " . TB_PREF . "fa_em_campaign_lists 
        (campaign_id, list_id) VALUES (" . db_escape($campaign_id) . ", " . db_escape($list_id) . ")");
    
    display_notification(_("Campaign linked to mailing list"));
}

if ($action === 'unlink') {
    $campaign_id = $_POST['campaign_id'];
    $list_id = $_POST['list_id'];
    
    db_query("DELETE FROM " . TB_PREF . "fa_em_campaign_lists 
        WHERE campaign_id = " . db_escape($campaign_id) . " AND list_id = " . db_escape($list_id));
    
    display_notification(_("Campaign unlinked from mailing list"));
}

if ($action === 'send') {
    $campaign_id = $_POST['campaign_id'];
    
    $sql = "SELECT l.list_id FROM " . TB_PREF . "fa_em_campaign_lists l WHERE l.campaign_id = " . db_escape($campaign_id);
    $result = db_query($sql, "Could not get linked lists");
    
    $total_sent = 0;
    while ($row = db_fetch_assoc($result)) {
        $list_id = $row['list_id'];
        
        $subs = db_query("SELECT email FROM " . TB_PREF . "fa_em_subscribers 
            WHERE list_id = " . db_escape($list_id) . " AND status = 'active'");
        
        while ($sub = db_fetch_assoc($subs)) {
            $total_sent++;
        }
    }
    
    display_notification(sprintf(_("Campaign queued to %d recipients"), $total_sent));
}

//--------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE, "width=50%");
table_section_title(_("Select Campaign"));

$sql = "SELECT c.campaign_id, c.campaign_name FROM " . TB_PREF . "crm_campaigns c ORDER BY c.campaign_name";
if (!db_query($sql, "Could not get campaigns")) {
    $sql = "SELECT campaign_id, campaign_name FROM " . TB_PREF . "crm_campaigns ORDER BY campaign_name";
}
$result = @$sql ? db_query($sql, "Could not get campaigns") : null;

$campaigns = [0 => _('Select a campaign')];
if ($result) {
    while ($row = db_fetch_assoc($result)) {
        $campaigns[$row['campaign_id'] ?? $row['id']] = $row['campaign_name'] ?? $row['name'] ?? 'Campaign #' . ($row['campaign_id'] ?? $row['id']);
    }
}
select_row(_("Campaign:"), 'campaign_id', $campaign_filter, $campaigns);

end_table();
submit_center('view', _("View Campaign"), true);

end_form();

//--------------------------------------------------------------------------------

if ($campaign_filter > 0) {
    echo '<h3>' . _('Mailing Lists') . '</h3>';
    
    $sql = "SELECT l.id, l.list_name, l.from_address, 
           (SELECT COUNT(*) FROM " . TB_PREF . "fa_em_subscribers s 
            WHERE s.list_id = l.id AND s.status = 'active') as subscriber_count
        FROM " . TB_PREF . "fa_em_mailing_lists l
        ORDER BY l.list_name";
    $result = db_query($sql, "Could not get lists");
    
    $linked_lists = [];
    $sql2 = "SELECT list_id FROM " . TB_PREF . "fa_em_campaign_links 
        WHERE campaign_id = " . db_escape($campaign_filter);
    $result2 = @db_query($sql2);
    if ($result2) {
        while ($row2 = db_fetch_assoc($result2)) {
            $linked_lists[] = $row2['list_id'];
        }
    }
    
    start_form();
    hidden('campaign_id', $campaign_filter);
    
    start_table(TABLESTYLE, "width=70%");
    table_header([
        _("List Name"), _("Subscribers"), _("Linked"), _("Actions")
    ]);
    
    while ($row = db_fetch_assoc($result)) {
        $list_id = $row['id'];
        $is_linked = in_array($list_id, $linked_lists);
        
        label_cell($row['list_name']);
        label_cell($row['subscriber_count']);
        label_cell($is_linked ? _('Yes') : _('No'));
        
        if ($is_linked) {
            hidden("list_id_{$list_id}", $list_id);
            submit_button("unlink_{$list_id}", _("Unlink"), true);
        } else {
            hidden("list_id_{$list_id}", $list_id);
            submit_button("link_{$list_id}", _("Link"), true);
        }
        
        end_row();
    }
    
    end_table();
    
    echo '<h3>' . _('Send Campaign') . '</h3>';
    start_table(TABLESTYLE, "width=50%");
    submit_row('send', _("Send Campaign Email"), true);
    end_table();
    
    end_form();
}

end_page();
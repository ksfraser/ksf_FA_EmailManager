<?php
/**
 * Email Inbox - View and Process Incoming Emails
 */

$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FA_EmailManager/includes/em_db.inc");
include_once($path_to_root . "/modules/FA_EmailManager/includes/em_routing.inc");

page(_($help_context = "Email Inbox"));

$filter = $_POST['filter'] ?? 'unprocessed';

//-----------------------------------------------------------------------------------

if (isset($_POST['sync_account'])) {
    include_once($path_to_root . "/modules/FA_EmailManager/includes/em_routing.inc");
    
    $account_id = $_POST['account_id'];
    $result = sync_email_account($account_id);
    
    if (isset($result['error'])) {
        display_error($result['error']);
    } else {
        display_notification(sprintf(_("Synced %d emails"), $result['count']));
    }
}

if (isset($_POST['process_email'])) {
    $email_id = $_POST['email_id'];
    $result = process_inbound_email($email_id);
    
    if ($result) {
        display_notification(sprintf(_("Email routed to %s (ID: %d)"), $result['action'], $result['entity_id']));
    }
}

if (isset($_POST['process_all'])) {
    $emails = get_unprocessed_emails(50);
    $processed = 0;
    
    foreach ($emails as $email) {
        process_inbound_email($email['id']);
        $processed++;
    }
    
    display_notification(sprintf(_("Processed %d emails"), $processed));
}

//-----------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE, "width=60%");
table_section_title(_("Sync Email Account"));

$accounts = get_active_email_accounts();
$account_options = [];
foreach ($accounts as $acc) {
    $account_options[$acc['id']] = $acc['account_name'] . ' (' . $acc['email_address'] . ')';
}
select_row(_("Account:"), 'account_id', '', $account_options);
submit_row('sync_account', _("Sync Now"), true);

end_table();

end_form();

//-----------------------------------------------------------------------------------

start_form();
submit_row('process_all', _("Process All Pending"), true);
end_form();

//--------------------------------------------------------------------------------

$where = "1=1";
if ($filter === 'unprocessed') {
    $where = "is_processed = 0";
} elseif ($filter === 'processed') {
    $where = "is_processed = 1";
} elseif ($filter === 'tickets') {
    $where = "routing_action = 'ticket'";
} elseif ($filter === 'opportunities') {
    $where = "routing_action IN ('opportunity', 'lead')";
}

$sql = "SELECT * FROM " . TB_PREF . "fa_em_inbound_emails 
    WHERE {$where} ORDER BY received_date DESC LIMIT 100";
$result = db_query($sql, "Could not get emails");

echo '<form method="post">';
echo '<select name="filter" onchange="this.form.submit()">';
echo '<option value="all"' . ($filter === 'all' ? ' selected' : '') . '>All</option>';
echo '<option value="unprocessed"' . ($filter === 'unprocessed' ? ' selected' : '') . '>Unprocessed</option>';
echo '<option value="processed"' . ($filter === 'processed' ? ' selected' : '') . '>Processed</option>';
echo '<option value="tickets"' . ($filter === 'tickets' ? ' selected' : '') . '>Tickets</option>';
echo '<option value="opportunities"' . ($filter === 'opportunities' ? ' selected' : '') . '>Opportunities</option>';
echo '</select>';
echo '</form>';

start_table(TABLESTYLE, "width=90%");

table_header([
    _("Date"), _("From"), _("To"), _("Subject"), _("Routing"), _("Status"), _("Actions")
]);

while ($row = db_fetch_assoc($result)) {
    $subject = $row['subject'] ?: '(No subject)';
    $from = $row['from_address'];
    $to = $row['to_address'];
    
    label_cell(sql2date($row['received_date']));
    label_cell($from);
    label_cell($to);
    label_cell($subject);
    
    if ($row['routing_action']) {
        label_cell($row['routing_action'] . ' #' . $row['linked_entity_id']);
    } else {
        label_cell('-');
    }
    
    if ($row['is_processed']) {
        label_cell('<span class="processed">' . _('Processed') . '</span>');
    } else {
        label_cell('<span class="unprocessed">' . _('Pending') . '</span>');
        
        echo '<td>';
        echo '<button type="submit" name="process_email" value="' . $row['id'] . '">' . _('Process') . '</button>';
        echo '<input type="hidden" name="email_id" value="' . $row['id'] . '">';
        echo '</td>';
    }
    
    end_row();
}

end_table();

end_page();
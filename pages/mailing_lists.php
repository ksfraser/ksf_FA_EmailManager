<?php
/**
 * Mailing List Management
 */

$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FA_EmailManager/includes/em_db.inc");

page(_($help_context = "Mailing Lists"));

simple_page_mode(true);

//-----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')
{
    $input_error = 0;
    if (strlen($_POST['list_name']) == 0)
    {
        $input_error = 1;
        display_error(_("List name cannot be empty."));
    }
    if ($input_error != 1)
    {
        $list_data = [
            'list_name' => $_POST['list_name'],
            'description' => $_POST['description'],
            'from_address' => $_POST['from_address'],
            'from_name' => $_POST['from_name'],
            'reply_to' => $_POST['reply_to'],
            'subscription_type' => $_POST['subscription_type'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($selected_id != -1) {
            update_mailing_list($selected_id, $list_data);
            display_notification(_('List updated'));
        } else {
            add_mailing_list($list_data);
            display_notification(_('List added'));
        }
        $Mode = 'RESET';
    }
}

if ($Mode == 'Delete') {
    $sql = "DELETE FROM " . TB_PREF . "fa_em_mailing_lists WHERE id = " . db_escape($selected_id);
    db_query($sql, "Could not delete list");
    display_notification(_('List deleted'));
    $Mode = 'RESET';
}

if ($Mode == 'EDIT_ITEM') {
    $myrow = get_mailing_list($selected_id);
    if ($myrow) $_POST = $myrow;
}

if ($Mode == 'RESET') {
    $_POST['list_name'] = '';
    $_POST['description'] = '';
    $_POST['from_address'] = '';
    $_POST['from_name'] = '';
    $_POST['subscription_type'] = 'double_optin';
    $_POST['is_active'] = 1;
}

//-----------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE, "width=60%");
table_section_title($Mode == 'EDIT_ITEM' ? _("Edit Mailing List") : _("New Mailing List"));

text_row_ex(_("List Name:"), 'list_name', 30);
text_row_ex(_("Description:"), 'description', 60);
text_row_ex(_("From Address:"), 'from_address', 50);
text_row_ex(_("From Name:"), 'from_name', 50);
text_row_ex(_("Reply To:"), 'reply_to', 50);

$types = ['single' => 'Single Opt-in', 'double_optin' => 'Double Opt-in', 'Confirmed' => 'Confirmed Opt-in'];
select_row(_("Subscription Type:"), 'subscription_type', $_POST['subscription_type'] ?? 'double_optin', $types);

check_row(_("Active:"), 'is_active', $_POST['is_active'] ?? 1);

end_table();

submit_center($Mode == 'EDIT_ITEM' ? _("Update") : _("Add List"), true, '', true);

end_form();

//--------------------------------------------------------------------------------

echo '<h3>' . _('Mailing Lists') . '</h3>';

$sql = "SELECT * FROM " . TB_PREF . "fa_em_mailing_lists ORDER BY list_name";
$result = db_query($sql, "Could not get lists");

start_table(TABLESTYLE, "width=70%");
table_header([
    _("Name"), _("Subscribers"), _("From"), _("Status"), _("Actions")
]);

while ($row = db_fetch_assoc($result)) {
    $sub_count = db_num_rows(db_query("SELECT id FROM " . TB_PREF . "fa_em_subscribers 
        WHERE list_id = " . db_escape($row['id']) . " AND status != 'unsubscribed'"));
    
    href_js_edit_link("?selected_id=" . $row['id'] . "&Mode=EDIT_ITEM", 'edit', $row['list_name']);
    label_cell($sub_count);
    label_cell($row['from_address']);
    label_cell($row['is_active'] ? _('Active') : _('Inactive'));
    delete_button_center("?selected_id=" . $row['id'] . "&Mode=Delete");
    end_row();
}

end_table();

end_page();
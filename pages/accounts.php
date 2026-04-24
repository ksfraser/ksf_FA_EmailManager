<?php
/**
 * Email Account Management Page
 */

$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FA_EmailManager/includes/em_db.inc");

page(_($help_context = "Email Account Management"));

simple_page_mode(true);

//-----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')
{
    $input_error = 0;
    if (strlen($_POST['account_name']) == 0)
    {
        $input_error = 1;
        display_error(_("Account name cannot be empty."));
    }
    if (strlen($_POST['email_address']) == 0)
    {
        $input_error = 1;
        display_error(_("Email address cannot be empty."));
    }
    if ($input_error != 1)
    {
        $account_data = [
            'account_name' => $_POST['account_name'],
            'email_address' => $_POST['email_address'],
            'account_type' => $_POST['account_type'],
            'server_host' => $_POST['server_host'],
            'server_port' => $_POST['server_port'],
            'encryption' => $_POST['encryption'],
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'sync_folder' => $_POST['sync_folder'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'debtor_no' => $_POST['debtor_no'],
            'contact_id' => $_POST['contact_id'],
        ];
        if ($selected_id != -1) {
            update_email_account($selected_id, $account_data);
            display_notification(_('Account updated'));
        } else {
            add_email_account($account_data);
            display_notification(_('Account added'));
        }
        $Mode = 'RESET';
    }
}

if ($Mode == 'Delete') {
    delete_email_account($selected_id);
    display_notification(_('Account deleted'));
    $Mode = 'RESET';
}

if ($Mode == 'EDIT_ITEM') {
    $myrow = get_email_account($selected_id);
    if ($myrow) {
        $_POST = $myrow;
    }
}

if ($Mode == 'RESET') {
    $_POST['account_name'] = '';
    $_POST['email_address'] = '';
    $_POST['account_type'] = 'imap';
    $_POST['server_host'] = '';
    $_POST['server_port'] = 993;
    $_POST['encryption'] = 'ssl';
    $_POST['sync_folder'] = 'INBOX';
    $_POST['is_active'] = 1;
}

//-----------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE, "width=60%");

if ($Mode == 'EDIT_ITEM')
    table_section_title(_("Edit Email Account"));
else
    table_section_title(_("New Email Account"));

text_row_ex(_("Account Name:"), 'account_name', 30, '', '', '', '');
text_row_ex(_("Email Address:"), 'email_address', 50, '', '', '', '');

$types = ['imap' => 'IMAP', 'pop3' => 'POP3'];
select_row(_("Account Type:"), 'account_type', $_POST['account_type'] ?? 'imap', $types);

text_row_ex(_("Server Host:"), 'server_host', 50, '', '', '', '');
text_row_ex(_("Server Port:"), 'server_port', 10, '', '', '', '993');

$encryptions = ['ssl' => 'SSL/TLS', 'tls' => 'TLS', 'notls' => 'None'];
select_row(_("Encryption:"), 'encryption', $_POST['encryption'] ?? 'ssl', $encryptions);

text_row_ex(_("Username:"), 'username', 30, '', '', '', '');
text_row_ex(_("Password:"), 'password', 30, '', '', '', '');
text_row_ex(_("Sync Folder:"), 'sync_folder', 20, '', '', '', 'INBOX');
check_row(_("Active:"), 'is_active', $_POST['is_active'] ?? 1);

debtor_row(_("Customer:"), 'debtor_no', $_POST['debtor_no'], true);
smallint_row(_("Contact:"), 'contact_id', $_POST['contact_id']);

end_table();

submit_center($Mode == 'EDIT_ITEM' ? _("Update") : _("Add Account"), true, '', true);

//--------------------------------------------------------------------------------

$sql = "SELECT * FROM " . TB_PREF . "fa_em_accounts ORDER BY account_name";
$result = db_query($sql, "Could not get accounts");

start_table(TABLESTYLE, "width=80%");

table_header([
    _("Name"), _("Email"), _("Type"), _("Server"), _("Status"), _("Actions")
]);

while ($row = db_fetch_assoc($result)) {
    $edit = $row['account_name'];
    href_js_edit_link("?selected_id=" . $row['id'] . "&Mode=EDIT_ITEM", 'edit', $edit);
    
    label_cell($row['email_address']);
    label_cell($row['account_type']);
    label_cell($row['server_host'] . ':' . $row['server_port']);
    label_cell($row['is_active'] ? _('Active') : _('Inactive'));
    
    delete_button_center("?selected_id=" . $row['id'] . "&Mode=Delete", _("Delete"));
    end_row();
}

end_table();

end_form();

end_page();
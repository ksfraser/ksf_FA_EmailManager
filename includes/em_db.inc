<?php
/**
 * Email Manager Database Functions
 */

// Try to load event manager
@include_once __DIR__ . '/../../vendor/ksfraser/event/src/Ksfraser/Event/EventManager.php';
$use_events = class_exists('Ksfraser\Event\EventManager');

function em_dispatch_event(string $eventName, $eventData = null): void
{
    if ($use_events && class_exists('Ksfraser\Event\EventManager')) {
        try {
            \Ksfraser\Event\EventManager::dispatchEvent($eventName, $eventData);
        } catch (Exception $e) {
            error_log('EM Event Error: ' . $e->getMessage());
        }
    }
    if (function_exists('add_hook')) {
        add_hook($eventName, function($data) use ($eventData) { return $eventData; });
    }
}

function add_email_account($account_data)
{
    $sql = "INSERT INTO " . TB_PREF . "fa_em_accounts
        (account_name, email_address, account_type, server_host, server_port, encryption,
         username, password, sync_folder, is_active, debtor_no, contact_id)
    VALUES (" . db_escape($account_data['account_name']) . ",
            " . db_escape($account_data['email_address']) . ",
            " . db_escape($account_data['account_type']) . ",
            " . db_escape($account_data['server_host']) . ",
            " . db_escape($account_data['server_port']) . ",
            " . db_escape($account_data['encryption']) . ",
            " . db_escape($account_data['username']) . ",
            " . db_escape($account_data['password']) . ",
            " . db_escape($account_data['sync_folder']) . ",
            " . db_escape($account_data['is_active']) . ",
            " . db_escape($account_data['debtor_no']) . ",
            " . db_escape($account_data['contact_id']) . ")";

    db_query($sql, "Could not add email account");
    return db_insert_id();
}

function get_email_account($account_id)
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_em_accounts WHERE id = " . db_escape($account_id);
    $result = db_query($sql, "Could not get email account");
    return db_fetch_assoc($result);
}

function get_active_email_accounts()
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_em_accounts WHERE is_active = 1";
    $result = db_query($sql, "Could not get email accounts");
    $accounts = [];
    while ($row = db_fetch_assoc($result)) {
        $accounts[] = $row;
    }
    return $accounts;
}

function update_email_account($account_id, $account_data)
{
    $sql = "UPDATE " . TB_PREF . "fa_em_accounts SET
        account_name = " . db_escape($account_data['account_name']) . ",
        email_address = " . db_escape($account_data['email_address']) . ",
        server_host = " . db_escape($account_data['server_host']) . ",
        server_port = " . db_escape($account_data['server_port']) . ",
        encryption = " . db_escape($account_data['encryption']) . ",
        username = " . db_escape($account_data['username']) . ",
        password = " . db_escape($account_data['password']) . ",
        sync_folder = " . db_escape($account_data['sync_folder']) . ",
        is_active = " . db_escape($account_data['is_active']) . ",
        debtor_no = " . db_escape($account_data['debtor_no']) . ",
        contact_id = " . db_escape($account_data['contact_id']) . "
    WHERE id = " . db_escape($account_id);

    db_query($sql, "Could not update email account");
}

function save_inbound_email($email_data)
{
    $sql = "INSERT INTO " . TB_PREF . "fa_em_inbound_emails
        (message_id, subject, from_address, from_name, to_address, cc_addresses, bcc_addresses,
         body_text, body_html, attachments, received_date, raw_headers, routing_action,
         linked_entity_id, linked_entity_type, debtor_no, contact_id, account_id)
    VALUES (" . db_escape($email_data['message_id']) . ",
            " . db_escape($email_data['subject']) . ",
            " . db_escape($email_data['from_address']) . ",
            " . db_escape($email_data['from_name']) . ",
            " . db_escape($email_data['to_address']) . ",
            " . db_escape($email_data['cc_addresses']) . ",
            " . db_escape($email_data['bcc_addresses']) . ",
            " . db_escape($email_data['body_text']) . ",
            " . db_escape($email_data['body_html']) . ",
            " . db_escape($email_data['attachments']) . ",
            " . db_escape($email_data['received_date']) . ",
            " . db_escape($email_data['raw_headers']) . ",
            " . db_escape($email_data['routing_action']) . ",
            " . db_escape($email_data['linked_entity_id']) . ",
            " . db_escape($email_data['linked_entity_type']) . ",
            " . db_escape($email_data['debtor_no']) . ",
            " . db_escape($email_data['contact_id']) . ",
            " . db_escape($email_data['account_id']) . ")";

    db_query($sql, "Could not save inbound email");
    $email_id = db_insert_id();
    
    em_dispatch_event('email_received', ['email_id' => $email_id, 'data' => $email_data]);
    
    return $email_id;
}

function get_inbound_email($email_id)
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_em_inbound_emails WHERE id = " . db_escape($email_id);
    $result = db_query($sql, "Could not get inbound email");
    return db_fetch_assoc($result);
}

function get_unprocessed_emails($limit = 50)
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_em_inbound_emails 
        WHERE is_processed = 0 
        ORDER BY received_date ASC 
        LIMIT " . db_escape($limit);
    $result = db_query($sql, "Could not get unprocessed emails");
    $emails = [];
    while ($row = db_fetch_assoc($result)) {
        $emails[] = $row;
    }
    return $emails;
}

function mark_email_processed($email_id)
{
    $sql = "UPDATE " . TB_PREF . "fa_em_inbound_emails SET is_processed = 1 WHERE id = " . db_escape($email_id);
    db_query($sql, "Could not mark email processed");
}

function add_mailing_list($list_data)
{
    $sql = "INSERT INTO " . TB_PREF . "fa_em_mailing_lists
        (list_name, description, from_address, from_name, reply_to, subscription_type, is_active)
    VALUES (" . db_escape($list_data['list_name']) . ",
            " . db_escape($list_data['description']) . ",
            " . db_escape($list_data['from_address']) . ",
            " . db_escape($list_data['from_name']) . ",
            " . db_escape($list_data['reply_to']) . ",
            " . db_escape($list_data['subscription_type']) . ",
            " . db_escape($list_data['is_active']) . ")";

    db_query($sql, "Could not add mailing list");
    return db_insert_id();
}

function get_mailing_list($list_id)
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_em_mailing_lists WHERE id = " . db_escape($list_id);
    $result = db_query($sql, "Could not get mailing list");
    return db_fetch_assoc($result);
}

function add_subscriber($subscriber_data)
{
    $subscribe_token = bin2hex(random_bytes(16));
    $unsubscribe_token = bin2hex(random_bytes(16));
    
    $sql = "INSERT INTO " . TB_PREF . "fa_em_subscribers
        (list_id, email, name, debtor_no, contact_id, status, subscribe_token, unsubscribe_token)
    VALUES (" . db_escape($subscriber_data['list_id']) . ",
            " . db_escape($subscriber_data['email']) . ",
            " . db_escape($subscriber_data['name']) . ",
            " . db_escape($subscriber_data['debtor_no']) . ",
            " . db_escape($subscriber_data['contact_id']) . ",
            " . db_escape($subscriber_data['status'] ?? 'pending') . ",
            " . db_escape($subscribe_token) . ",
            " . db_escape($unsubscribe_token) . ")";

    db_query($sql, "Could not add subscriber");
    $subscriber_id = db_insert_id();
    
    em_dispatch_event('mailinglist.subscribed', [
        'subscriber_id' => $subscriber_id,
        'list_id' => $subscriber_data['list_id'],
        'email' => $subscriber_data['email'],
    ]);
    
    return $subscriber_id;
}

function get_subscribers($list_id)
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_em_subscribers WHERE list_id = " . db_escape($list_id);
    $result = db_query($sql, "Could not get subscribers");
    $subscribers = [];
    while ($row = db_fetch_assoc($result)) {
        $subscribers[] = $row;
    }
    return $subscribers;
}

function confirm_subscriber($subscribe_token)
{
    $sql = "UPDATE " . TB_PREF . "fa_em_subscribers SET status = 'active' 
        WHERE subscribe_token = " . db_escape($subscribe_token);
    db_query($sql, "Could not confirm subscriber");
}

function unsubscribe_by_token($unsubscribe_token)
{
    $sql = "UPDATE " . TB_PREF . "fa_em_subscribers SET status = 'unsubscribed' 
        WHERE unsubscribe_token = " . db_escape($unsubscribe_token);
    db_query($sql, "Could not unsubscribe");
}

function find_debtor_by_email($email_address)
{
    $sql = "SELECT debtor_no FROM " . TB_PREF . "debtors_master 
        WHERE email = " . db_escape($email_address) . " LIMIT 1";
    $result = db_query($sql, "Could not find debtor");
    $row = db_fetch_assoc($result);
    return $row ? $row['debtor_no'] : null;
}

function find_contact_by_email($email_address)
{
    $sql = "SELECT contact_id FROM " . TB_PREF . "crm_contacts 
        WHERE email = " . db_escape($email_address) . " LIMIT 1";
    $result = db_query($sql, "Could not find contact");
    $row = db_fetch_assoc($result);
    return $row ? $row['contact_id'] : null;
}

function em_find_customer_match($email_address): array
{
    $debtor_no = find_debtor_by_email($email_address);
    $contact_id = find_contact_by_email($email_address);
    
    return [
        'debtor_no' => $debtor_no,
        'contact_id' => $contact_id,
    ];
}

function route_email($email_id)
{
    $email = get_inbound_email($email_id);
    if (!$email) return null;
    
    $to_address = strtolower($email['to_address']);
    $subject = strtolower($email['subject']);
    $body = strtolower($email['body_text'] ?? '');
    
    $sql = "SELECT * FROM " . TB_PREF . "fa_em_routes 
        WHERE is_active = 1 ORDER BY priority DESC";
    $result = db_query($sql, "Could not get routes");
    
    while ($route = db_fetch_assoc($result)) {
        $route_to = strtolower($route['to_address']);
        
        if (strpos($to_address, $route_to) !== false) {
            $keywords = array_filter(array_map('trim', explode(',', $route['keywords'])));
            
            if (empty($keywords)) {
                return [
                    'action' => $route['action'],
                    'reason' => 'TO match',
                ];
            }
            
            foreach ($keywords as $keyword) {
                if (strpos($subject, $keyword) !== false || strpos($body, $keyword) !== false) {
                    return [
                        'action' => $route['action'],
                        'reason' => "Keyword: $keyword",
                    ];
                }
            }
        }
    }
    
    return ['action' => 'ticket', 'reason' => 'default'];
}
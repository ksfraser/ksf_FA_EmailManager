<?php
/**
 * Email Routing and Processing
 */

function process_inbound_email($email_id)
{
    $email = get_inbound_email($email_id);
    if (!$email) return null;
    
    $to_address = strtolower($email['to_address']);
    $from_address = $email['from_address'];
    
    $customer_match = em_find_customer_match($from_address);
    $debtor_no = $customer_match['debtor_no'];
    $contact_id = $customer_match['contact_id'];
    
    if ($debtor_no) {
        $sql = "UPDATE " . TB_PREF . "fa_em_inbound_emails SET debtor_no = " . db_escape($debtor_no) . " WHERE id = " . db_escape($email_id);
        db_query($sql, "Could not update debtor");
    }
    if ($contact_id) {
        $sql = "UPDATE " . TB_PREF . "fa_em_inbound_emails SET contact_id = " . db_escape($contact_id) . " WHERE id = " . db_escape($email_id);
        db_query($sql, "Could not update contact");
    }
    
    $route = route_email($email_id);
    $action = $route['action'];
    $reason = $route['reason'];
    
    $sql = "UPDATE " . TB_PREF . "fa_em_inbound_emails SET 
        routing_action = " . db_escape($action) . "
        WHERE id = " . db_escape($email_id);
    db_query($sql, "Could not update routing");
    
    $entity_id = 0;
    
    if ($action === 'ticket') {
        $entity_id = em_create_ticket_from_email($email_id, $debtor_no, $contact_id);
        em_dispatch_event('ticket_created', ['ticket_id' => $entity_id, 'email_id' => $email_id]);
    } elseif ($action === 'opportunity' || $action === 'lead') {
        $entity_id = em_create_opportunity_from_email($email_id, $debtor_no, $contact_id);
        em_dispatch_event('opportunity_created', ['opportunity_id' => $entity_id, 'email_id' => $email_id]);
    }
    
    if ($entity_id > 0) {
        $sql = "UPDATE " . TB_PREF . "fa_em_inbound_emails SET 
            linked_entity_id = " . db_escape($entity_id) . ",
            linked_entity_type = " . db_escape($action) . "
            WHERE id = " . db_escape($email_id);
        db_query($sql, "Could not link entity");
    }
    
    mark_email_processed($email_id);
    
    return [
        'email_id' => $email_id,
        'action' => $action,
        'entity_id' => $entity_id,
        'debtor_no' => $debtor_no,
    ];
}

function em_create_ticket_from_email($email_id, $debtor_no = 0, $contact_id = 0): int
{
    $email = get_inbound_email($email_id);
    if (!$email) return 0;
    
    $ticket_number = 'TKT-' . date('Ymd') . '-' . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO " . TB_PREF . "fa_st_tickets
        (ticket_number, subject, description, type, priority, status, debtor_no, contact_id, created_by)
    VALUES (" . db_escape($ticket_number) . ",
            " . db_escape($email['subject']) . ",
            " . db_escape($email['body_text']) . ",
            'Email',
            'Medium',
            'New',
            " . db_escape($debtor_no) . ",
            " . db_escape($contact_id) . ",
            'Email System')";

    db_query($sql, "Could not create ticket");
    return db_insert_id();
}

function em_create_opportunity_from_email($email_id, $debtor_no = 0, $contact_id = 0): int
{
    $email = get_inbound_email($email_id);
    if (!$email) return 0;
    
    $sql = "INSERT INTO " . TB_PREF . "crm_opportunities
        (name, description, debtor_no, contact_id, source, status, probability, created_date)
    VALUES (" . db_escape($email['subject']) . ",
            " . db_escape($email['body_text']) . ",
            " . db_escape($debtor_no) . ",
            " . db_escape($contact_id) . ",
            'Email',
            'New',
            10,
            CURDATE())";

    @db_query($sql, "Could not create opportunity");
    return @db_insert_id() ?: 0;
}

function sync_email_account($account_id)
{
    $account = get_email_account($account_id);
    if (!$account) return ['error' => 'Account not found'];
    
    if (!function_exists('imap_open')) {
        return ['error' => 'IMAP extension not available'];
    }
    
    $host = $account['server_host'];
    $port = $account['server_port'];
    $encryption = $account['encryption'];
    
    $mailbox = "{" . $host . ":" . $port . "/imap/" . $encryption . "}";
    $username = $account['username'];
    $password = $account['password'];
    
    $imap = @imap_open($mailbox, $username, $password);
    
    if (!$imap) {
        return ['error' => imap_errors() ?: 'Connection failed'];
    }
    
    $headers = imap_headers($imap);
    
    if (!$headers) {
        imap_close($imap);
        return ['count' => 0];
    }
    
    $synced = 0;
    
    foreach ($headers as $msgno => $header_info) {
        $header = imap_headerinfo($imap, $msgno + 1);
        
        $existing = db_num_rows(db_query("SELECT id FROM " . TB_PREF . "fa_em_inbound_emails 
            WHERE message_id = " . db_escape($header->message_id)));
        
        if ($existing > 0) continue;
        
        $structure = imap_fetchstructure($imap, $msgno + 1);
        
        $body_text = '';
        $body_html = '';
        
        if (isset($structure->parts)) {
            foreach ($structure->parts as $part) {
                if ($part->type === 'text') {
                    if ($part->subtype === 'HTML') {
                        $body_html = imap_fetchbody($imap, $msgno + 1, $part->number);
                    } else {
                        $body_text = imap_fetchbody($imap, $msgno + 1, $part->number);
                    }
                }
            }
        } else {
            $body_text = imap_fetchbody($imap, $msgno + 1, '1');
        }
        
        $from_address = ($header->from[0]->mailbox ?? '') . '@' . ($header->from[0]->host ?? '');
        
        $email_data = [
            'message_id' => $header->message_id ?? '',
            'subject' => $header->subject ?? '',
            'from_address' => $from_address,
            'from_name' => $header->from[0]->personal ?? '',
            'to_address' => ($header->to[0]->mailbox ?? '') . '@' . ($header->to[0]->host ?? ''),
            'body_text' => $body_text,
            'body_html' => $body_html,
            'received_date' => date('Y-m-d H:i:s', strtotime($header->date)),
            'account_id' => $account_id,
        ];
        
        save_inbound_email($email_data);
        $synced++;
    }
    
    $sql = "UPDATE " . TB_PREF . "fa_em_accounts SET last_sync = NOW() WHERE id = " . db_escape($account_id);
    db_query($sql, "Could not update last sync");
    
    imap_close($imap);
    
    return ['count' => $synced];
}
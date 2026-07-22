# Functional Requirements - ksf_FA_EmailManager

## Document Information
- **Module**: ksf_FA_EmailManager
- **Version**: 2.0.0
- **Date**: 2026-07-22
- **Status**: Active

---

## Overview

Email campaign management for FrontAccounting - templates, automation, and tracking with CRM integration. This module handles outbound email delivery with optional GPG signing and encryption.

---

## FR-001 Email Templates
**Satisfies**: BR-001

- FR-001.1 The system shall provide email template management.
- FR-001.2 Templates shall support variables/placeholders.
- FR-001.3 Templates shall support HTML and plain text formats.

---

## FR-002 Email Campaigns
**Satisfies**: BR-002

- FR-002.1 The system shall support email campaign creation.
- FR-002.2 Campaigns shall link to contacts and opportunities.
- FR-002.3 Campaigns shall track send status and delivery.

---

## FR-003 Email Logging
**Satisfies**: BR-003

- FR-003.1 The system shall log all sent emails.
- FR-003.2 Logs shall include: recipient, subject, status, timestamp.
- FR-003.3 Logs shall link to contacts and campaigns.

---

## FR-004 Email Attachments
**Satisfies**: BR-004

- FR-004.1 The system shall support file attachments.
- FR-004.2 Attachments shall be stored in FA's attachment system.

---

## FR-005 GPG Signing Integration
**Satisfies**: BR-005

- FR-005.1 The system shall sign all outgoing emails when a GPG key exists for the recipient.
- FR-005.2 The system shall call `hook_invoke_all('gpg_sign', $data)` before sending.
- FR-005.3 The system shall sign email attachments.
- FR-005.4 The system shall log signing operations.

### Implementation TODO
```php
// TODO: Add GPG signing to email send pipeline
// In email sending function, before final send:
$data = [
    'contact_type' => $contactType,
    'contact_id' => $contactId,
    'email' => $toEmail,
    'file_path' => $attachmentPath,
];
hook_invoke_all('gpg_sign', $data);
```

---

## FR-006 GPG Encryption Integration
**Satisfies**: BR-006

- FR-006.1 The system shall encrypt emails when the encrypt flag is set.
- FR-006.2 The system shall call `hook_invoke_all('gpg_encrypt', $data)` before sending.
- FR-006.3 The system shall encrypt email attachments.
- FR-006.4 The system shall support password-based encryption for contacts without keys.

### Implementation TODO
```php
// TODO: Add GPG encryption to email send pipeline
// In email sending function, before final send:
if ($encryptFlag) {
    $data = [
        'email' => $toEmail,
        'file_path' => $attachmentPath,
        'password' => null, // Use GPG key if available, otherwise password
    ];
    hook_invoke_all('gpg_encrypt', $data);
}
```

---

## FR-007 GPG Email Before Send Hook
**Satisfies**: BR-007

- FR-007.1 The system shall call `hook_invoke_all('gpg_email_before_send', $data)` before sending.
- FR-007.2 The data array shall include: `to`, `subject`, `body`, `attachments`, `encrypt` flag.
- FR-007.3 The system shall use returned file paths from GPG hooks.

### Implementation TODO
```php
// TODO: Add GPG pre-send hook
$data = [
    'to' => $toEmail,
    'subject' => $subject,
    'body' => $body,
    'attachments' => $attachments,
    'encrypt' => $encryptFlag,
];
hook_invoke_all('gpg_email_before_send', $data);
// Update attachments array with GPG-signed/encrypted paths
$attachments = $data['attachments'];
```

---

## FR-008 Inter-Module Communication
**Satisfies**: BR-008

- FR-008.1 The module shall implement `getModuleCapabilities()` for email sending.
- FR-008.2 The module shall respond to `hasCapability('email')` calls.

### Implementation TODO
```php
// TODO: Add GPG capability check in getModuleCapabilities()
public function getModuleCapabilities(&$data, $opts = null) {
    $capabilities = [
        'email' => [
            'description' => 'Send emails with GPG signing/encryption',
            'methods' => ['sendEmail', 'sendWithAttachment'],
        ],
    ];
    $data['capabilities'] = $capabilities;
    return $capabilities;
}
```

---

## FR-009 Security Areas

- FR-009.1 `SA_EMAILMANAGER_VIEW`: View email templates and logs
- FR-009.2 `SA_EMAILMANAGER_SEND`: Send emails
- FR-009.3 `SA_EMAILMANAGER_SETUP`: Configure email settings

---

## FR-010 Dependencies

- FR-010.1 `ksf_FA_CRM` for contact lookup
- FR-010.2 `ksf_FA_GPG` for signing/encryption (optional)
- FR-010.3 FrontAccounting 2.4.19
- FR-010.4 PHP 7.3

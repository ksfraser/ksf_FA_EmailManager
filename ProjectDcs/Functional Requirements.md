# Functional Requirements - ksf_FA_EmailManager

## Document Information
- **Module**: ksf_FA_EmailManager
- **Version**: 2.0.0
- **Date**: 2026-07-22
- **Status**: Active

---

## Overview

Email campaign management for FrontAccounting - templates, automation, and tracking with CRM integration. This module handles outbound email delivery with GPG signing. **EmailManager does NOT encrypt** - calling modules (CRM, HRM, Suppliers) are responsible for encrypting attachments before calling EmailManager.

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

- FR-005.1 The system shall sign all outgoing emails when a GPG key exists for the sender.
- FR-005.2 The system shall call `hook_invoke_all('gpg_sign', $data)` before sending.
- FR-005.3 The system shall sign email attachments (including encrypted attachments from calling modules).
- FR-005.4 The system shall log signing operations.

### Implementation TODO
```php
// TODO: Add GPG signing to email send pipeline
// In email sending function, before final send:
$data = [
    'sender_email' => $senderEmail,  // Sign with sender's key
    'file_path' => $attachmentPath,   // May already be encrypted by calling module
];
hook_invoke_all('gpg_sign', $data);
```

---

## FR-006 Encryption by Calling Modules (NOT EmailManager)
**Satisfies**: BR-006

- FR-006.1 EmailManager shall NOT encrypt emails or attachments.
- FR-006.2 Calling modules (CRM, HRM, Suppliers, Calendar) are responsible for encryption.
- FR-006.3 Calling modules shall encrypt files BEFORE calling EmailManager.
- FR-006.4 EmailManager shall sign encrypted attachments like any other attachment.

### Architecture Note
```
Calendar flow: generate .ics → EmailManager signs → sends (NO encryption, .ics must be readable)
CRM flow: encrypt file → save encrypted version → EmailManager signs + sends
HRM flow: encrypt file → save encrypted version → EmailManager signs + sends
```

---

## FR-007 GPG Email Before Send Hook
**Satisfies**: BR-007

- FR-007.1 The system shall call `hook_invoke_all('gpg_email_before_send', $data)` before sending.
- FR-007.2 The data array shall include: `to`, `subject`, `body`, `attachments` (may be pre-encrypted by caller).
- FR-007.3 The system shall use returned file paths from GPG signing hooks.

### Implementation TODO
```php
// TODO: Add GPG pre-send hook
$data = [
    'to' => $toEmail,
    'subject' => $subject,
    'body' => $body,
    'attachments' => $attachments,  // May contain encrypted files from CRM/HRM
];
hook_invoke_all('gpg_email_before_send', $data);
// Update attachments array with GPG-signed paths
$attachments = $data['attachments'];
```

---

## FR-008 Inter-Module Communication
**Satisfies**: BR-008

- FR-008.1 The module shall implement `getModuleCapabilities()` for email sending.
- FR-008.2 The module shall respond to `hasCapability('email')` calls.
- FR-008.3 EmailManager capability is "email" and "email_with_signing" (no encryption capability).

### Implementation TODO
```php
// TODO: Add email capability check in getModuleCapabilities()
public function getModuleCapabilities(&$data, $opts = null) {
    $capabilities = [
        'email' => [
            'description' => 'Send emails',
            'methods' => ['sendEmail', 'sendWithAttachment'],
        ],
        'email_with_signing' => [
            'description' => 'Send emails with GPG signing',
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
- FR-010.2 `ksf_FA_GPG` for signing only (optional)
- FR-010.3 FrontAccounting 2.4.19
- FR-010.4 PHP 7.3

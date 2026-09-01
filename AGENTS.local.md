<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

```markdown
# ksf_FA_EmailManager
## Architecture Overview
**FA Module** for Email Campaign Management - templates, automation, and tracking with CRM integration.
## Repository Structure
```
ksf_FA_EmailManager/
├── sql/
│   ├── fa_email_templates.sql
│   ├── fa_email_campaigns.sql
│   ├── fa_email_logs.sql
│   └── fa_email_attachments.sql
├── includes/
│   ├── templates_db.inc
│   ├── campaigns_db.inc
│   ├── logs_db.inc
│   └── attachments_db.inc
├── pages/
├── hooks.php
├── composer.json
└── ProjectDocs/
```
## Dependencies
- **ksf_FA_EmailManager_Core** (business logic)
- **ksf_FA_CRM** (link emails to contacts)
- **FrontAccounting 2.4+**
```

# AGENTS.md - ksf_FA_EmailManager#

## Architecture Overview#

**FA Module** for Email Campaign Management - templates, automation, and tracking with CRM integration.

### Core Principles#
- **SOLID**, **DRY**, **TDD**, **DI**, **SRP**#

## Repository Structure#

```
ksf_FA_EmailManager/
├── sql/#
│   ├── fa_email_templates.sql#
│   ├── fa_email_campaigns.sql#
│   ├── fa_email_logs.sql#
│   └── fa_email_attachments.sql#
├── includes/#
│   ├── templates_db.inc#
│   ├── campaigns_db.inc#
│   ├── logs_db.inc#
│   └── attachments_db.inc#
├── pages/#
├── hooks.php#
├── composer.json#
└── ProjectDocs/#
```

## Dependencies#

- **ksf_FA_EmailManager_Core** (business logic)#
- **ksf_FA_CRM** (link emails to contacts)#
- **FrontAccounting 2.4+**#

## Development Workflow

All development is done in the **devel tree** (`~/Documents/ksf_FA_EmailManager`). Do **not** edit files in the UAT bind point directly.

### Workflow Steps
1. **Develop** in this repo (feature branches preferred)
2. **Test**: run repo-appropriate tests
3. **Lint**: `php -l` on modified PHP files (no syntax errors)
4. **Commit** and **Push** branch to GitHub
5. **Merge** to `master` when ready
6. **Push** `master` to GitHub
7. **Deploy** to UAT by pulling in the Infrastructure bind point:

   ```
   cd ~/ksf_Infrastructure/fa_modules/ksf_FA_EmailManager
   git stash -u
   git pull origin master
   git stash pop
   ```

### UAT Bind Point
| Path | Purpose |
|------|---------|
| `~/Documents/ksf_FA_EmailManager` | Devel tree — all development, testing, commits |
| `~/ksf_Infrastructure/fa_modules/ksf_FA_EmailManager` | UAT bind point — deployment target, integration testing (if mirrored) |


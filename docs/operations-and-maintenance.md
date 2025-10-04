# Operations and Maintenance Runbook

## 1. Environment Profiles
| Environment | Purpose | Configuration Notes |
| --- | --- | --- |
| **Local Development** | Developer workstations running XAMPP or Docker. | Configure database credentials directly in `app/config.php`; enable verbose error reporting (`display_errors = On`). |
| **Staging** | Pre-production validation environment. | Mirror production schema with anonymised data; enable debug logging, disable outbound SMS/email in `OutboundMessageService`. |
| **Production** | Live system for branch staff and students. | HTTPS enforced, `display_errors = Off`, cron jobs enabled, backups scheduled, restricted file permissions. |

## 2. Infrastructure Checklist
- Apache 2.4 with PHP 8.1+ and PDO MySQL extension.
- MySQL/MariaDB 10+ with nightly backups (`mysqldump`).
- Writable directories: `uploads/` and `logs/` (recommend `750` permissions with web server ownership).
- Cron scheduler capable of invoking PHP CLI commands.
- Optional monitoring agent (Netdata, New Relic, etc.).

## 3. Deployment Procedure
1. **Pre-deployment**
   - Merge changes into release branch.
   - Run DAO regression scripts (`php tests/*_test.php`).
   - Backup database and uploads directory.
2. **Deploy**
   - Upload code via rsync/SFTP to server (excluding `uploads/`, `logs/`).
   - Run `composer dump-autoload` if class map changed.
   - Apply SQL migrations or re-import updated `sql/database.sql` delta.
   - Clear opcode cache (if using `opcache_reset()`).
3. **Post-deployment validation**
   - Login as admin, instructor, and student to confirm dashboards.
   - Create test schedule and verify reminder queue entry.
  - Generate invoice and ensure payment workflow operates.
   - Review error logs for anomalies.

## 4. Cron Jobs
| Schedule | Command | Purpose |
| --- | --- | --- |
| Every 15 minutes | `php -r "require 'app/bootstrap.php'; (new App\\Services\\ReminderService())->processDueReminders();"` | Dispatch due reminders and convert to notifications/outbound messages. |
| Nightly 02:00 | `php -r "file_exists('logs/application.log') ? rename('logs/application.log', 'logs/application-' . date('Ymd-His') . '.log') : null;"` | Simple log rotation (replace with logrotate in production). |
| Nightly 02:15 | `mysqldump origin_driving_school > /backups/origin_$(date +%F).sql` | Database backup. |
| Weekly Sunday 03:00 | `mysql -u <user> -p<pass> -e "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 MONTH);" origin_driving_school` | Clean notifications older than 12 months. |

> Capture stdout/stderr to `/var/log/cron.log` or dedicated files. The notification purge command assumes an optional helper; alternatively run manual SQL (`DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 MONTH);`).

## 5. Monitoring & Alerting
- **Application Logs:** Review `logs/application.log` daily; configure log rotation to keep 30 days.
- **Audit Trail:** Periodically export `audit_trail` for compliance review.
- **Server Metrics:** Monitor CPU, memory, disk, and Apache worker counts.
- **Database Metrics:** Track slow queries, connection counts, and storage growth.
- **Cron Health:** Cron scripts should log start/end status; configure email alerts on failure.

## 6. Backup & Restore
### 6.1 Backup Strategy
- Nightly full database dump stored off-server.
- Hourly incremental filesystem snapshots for `uploads/`.
- Weekly full archive of codebase to versioned storage (e.g., S3 bucket).

### 6.2 Restore Procedure
1. Provision clean server with Apache + PHP + MySQL.
2. Restore latest database dump (`mysql -u user -p origin_driving_school < backup.sql`).
3. Restore `uploads/` from snapshot.
4. Deploy matching code commit from version control.
5. Update `app/config.php` credentials if changed.
6. Run smoke tests (login, schedule view, invoice list).

## 7. Incident Response
| Severity | Definition | Response Steps |
| --- | --- | --- |
| **Sev 1** | System outage or data loss. | Engage on-call engineer, switch to maintenance page, restore from latest backup, communicate ETA to stakeholders. |
| **Sev 2** | Major functionality degraded (scheduling, invoicing). | Assess logs, rollback recent deployment if necessary, apply hotfix. |
| **Sev 3** | Minor bug or cosmetic issue. | Log ticket, prioritise in next sprint, no immediate user comms required. |

Escalation path: Support Desk → Operations Manager → Technical Lead → Vendor (if third-party integration failure).

## 8. Maintenance Tasks
| Frequency | Task |
| --- | --- |
| Daily | Review application and server logs; ensure cron jobs succeeded. |
| Weekly | Rotate logs, verify backups, run `ANALYZE TABLE` on high-churn tables (`schedules`, `reminders`). |
| Monthly | User access review, instructor accreditation expiry audit, database index health check. |
| Quarterly | Restore-from-backup drill, security patch review, PHP/MySQL minor version updates on staging. |
| Annually | Review compliance policies, update disaster recovery plan, test failover procedure. |

## 9. Configuration Management
- Central configuration in `app/config.php` covering database credentials, base URL, email/SMS placeholders.
- For multi-environment support, wrap config values with environment variable checks (`getenv`) before falling back to defaults.
- Store sensitive values (DB passwords, API keys) outside version control (e.g., environment variables or protected `.ini` files).

## 10. Documentation & Knowledge Transfer
- Maintain change logs for deployments (date, commit hash, summary, tester).
- Update README and `docs/` folder when introducing new modules or altering workflows.
- Record custom scripts and cron additions in this runbook.
- Provide onboarding guide for new staff including environment setup, access request process, and escalation procedures.

## 11. Appendix
- **Support Contacts:**
  - Operations Manager: operations@origin.com
  - Technical Lead: techlead@origin.com
  - Database Administrator: dba@origin.com
- **Useful Commands:**
  - Clear cache: `php -r "opcache_reset();"`
  - Database connectivity test: `php -r "require 'app/bootstrap.php'; var_dump(App\Core\Database::connection()->query('SELECT 1')->fetch());"`
  - Run targeted DAO test: `php tests/invoice_test.php`

Refer back to [`architecture.md`](architecture.md) for component-level insights and [`diagrams.md`](diagrams.md) for visual references when diagnosing complex issues.

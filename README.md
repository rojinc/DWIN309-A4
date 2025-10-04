# Origin Driving School Management System

A full-featured management platform for Origin Driving School covering student enrolments, scheduling, invoicing, communications, fleet oversight, and compliance tracking. The application is implemented in modern PHP without a framework and targets XAMPP-style deployments.

## Table of Contents
1. [Solution Overview](#solution-overview)
2. [Core Capabilities](#core-capabilities)
3. [Technology Stack](#technology-stack)
4. [Architecture Summary](#architecture-summary)
5. [Data Architecture Summary](#data-architecture-summary)
6. [Directory Structure](#directory-structure)
7. [Setup & Installation](#setup--installation)
8. [Operational Usage](#operational-usage)
9. [Testing Strategy](#testing-strategy)
10. [Security Hardening](#security-hardening)
11. [Deployment & DevOps Notes](#deployment--devops-notes)
12. [Maintenance Playbook](#maintenance-playbook)
13. [Documentation Index](#documentation-index)

## Solution Overview
Origin Driving School requires an internal system that unifies administrative tasks across multiple branches. The platform centralises:

- Learner lifecycle management from enquiry to completion
- Instructor scheduling, qualifications, and availability
- Fleet assignments and vehicle service cadence
- Financial workflows (enrolments, invoices, payments, reminders)
- Communications (broadcasts, targeted notifications, reminders)
- Compliance artefacts (documents, audit trails, feedback notes)

The application is designed to operate on modest infrastructure while providing robust security (role-based access control, CSRF protection, sanitisation) and operational observability (audit trail, structured logging).

## Core Capabilities
The feature catalogue is aligned to departmental workflows. Each bullet summarises what the module delivers **and** the headline tasks it supports day to day.

- **Authentication & RBAC:** Secure login/logout, password hashing, CSRF protection, per-role dashboards (admin, staff, instructor, student), idle session expiry, password reset hooks.
- **Branch Administration:** Create and maintain branches, assign staff/instructors, manage branch contact directory, view utilisation KPIs per branch.
- **Student Management:** Full CRM capabilities (create/update profiles, search, filter by branch/course, upload documents, capture notes, view communications history, enrolment status tracking).
- **Instructor Management:** Maintain accreditation/licensing, working-with-children checks, availability notes, and load indicators. Surface assigned students, upcoming lessons, and satisfaction scores.
- **Course Catalogue:** Manage courses, pricing, required lesson counts, prerequisites, recommended instructors, and publish/retire lifecycle.
- **Enrolment Requests & Enrolments:** Accept self-service submissions, triage queue, approve/decline with notes, convert to structured enrolments in one click, trigger welcome notifications and initial invoices.
- **Scheduling:** Create, reschedule, and cancel lessons/exams with conflict detection across instructors/vehicles/branches; capture lesson outcomes; auto-queue reminders; surface weekly calendars and printable itineraries.
- **Fleet & Vehicle Maintenance:** Track vehicles, registration, transmission, servicing history, odometer, branch allocation, and downtime windows. Link to schedules for utilisation and conflict warnings.
- **Invoicing & Payments:** Auto-generate invoices per enrolment, manage line items, apply discounts, log payments, produce statements, export PDF-friendly layouts, monitor overdue balances, trigger reminder workflows.
- **Notifications & Reminders:** In-app notifications, queued reminders (email/SMS/in-app), opt-in/out tracking, acknowledgement audit, cron-driven processing with delivery logs.
- **Communications:** Broadcast announcements, segment audiences (branch/course/role), maintain delivery history, support attachments, and record follow-up tasks.
- **Reporting:** KPI dashboards, instructor performance heatmaps, student progress drill-downs, revenue snapshots, export to CSV/Excel-ready tables.
- **Document Repository:** Secure storage for licences, identification, assessments, medical clearance; enforce MIME/size policies; maintain audit trail of downloads and deletions.
- **Audit Trail:** Immutable log covering sensitive state changes for compliance and troubleshooting with filters by module, user, entity, and time range.

### Feature Breakdown by Persona
| Persona | Daily Goals | Key Features & Functions |
| --- | --- | --- |
| **Administrator** | Maintain organisational settings, compliance, and KPIs. | Branch management, course catalogue, staff provisioning, KPI dashboards, audit trail review, policy/document distribution, global notifications. |
| **Operations Staff** | Onboard students, coordinate schedules, manage payments. | Enrolment request pipeline, student CRM, scheduling calendar with conflict detection, invoice creation, payment recording, document uploads, outbound communications, reminder queue supervision. |
| **Instructor** | Deliver lessons efficiently and keep availability up to date. | Personal dashboard, availability submission, assigned schedule view (daily/weekly), lesson outcome capture, student progress notes, acknowledgement of reminders/notifications. |
| **Student/Learner** | Stay informed about lessons, payments, and compliance requirements. | Enrolment request form, lesson timetable, invoice & balance view, secure document upload, notification centre for reminders and announcements. |
| **Finance Officer** | Reconcile revenue and chase overdue balances. | Invoice ageing reports, payment ledger, exportable statements, reminder escalation management, audit trail of financial adjustments. |

### Service & Automation Highlights
- **Reminder engine:** `ReminderService::queueScheduleReminder` / `::queueInvoiceReminder` schedule automated nudges; `::processDueReminders` runs via cron to deliver notifications and record outcomes.
- **Audit hooks:** Critical controllers call `AuditService::record` with metadata snapshots to support compliance investigations.
- **Outbound messaging:** `OutboundMessageService` normalises payloads for future SMTP/SMS integrations while providing structured logging today.
- **Validation toolkit:** Shared helpers enforce complex validation rules (e.g., overlapping schedule detection, licence expiry checks, invoice balancing) ensuring consistent behaviour across modules.

## Technology Stack
The solution is intentionally lightweight while maintaining a clean separation of concerns:

| Layer | Technology | Notes |
| --- | --- | --- |
| Runtime | PHP 8.x | Framework-free, leverages native OOP and SPL |
| Web server | Apache (via XAMPP) | Document root serves `index.php` front controller |
| Database | MySQL / MariaDB 10.x | PDO driver, strict modes, UTF8MB4 |
| Front-end | HTML5, vanilla JavaScript, CSS3 | Progressive enhancement, no third-party dependencies |
| Package / Build | Composer (autoload only) | PSR-4 autoloading configured in `app/bootstrap.php` |
| Testing | Custom CLI harness | DAO tests in `/tests` executed via `php` CLI |
| Logging | File-based (`logs/`) | Structured log lines with timestamps and log levels |
| Email/SMS integration | Service stubs | Ready for integration with SMTP / SMS gateways |

## Architecture Summary
The application follows a lightweight MVC pattern combined with service/DAO layers:

- **Front Controller & Router:** `index.php` interprets `?page` and `action` parameters, instantiates controllers, and dispatches to `{Action}` methods.
- **Controllers:** Orchestrate request handling, input validation, business logic coordination, and view rendering. Controllers are grouped by bounded contexts (students, schedules, invoices, etc.).
- **Models / Data Access Objects:** Encapsulate SQL queries, returning typed arrays. All database interaction flows through PDO prepared statements with transactional support.
- **Services:** Shared business utilities (authentication, reminders, notifications, audit logging, outbound messaging) decouple cross-cutting concerns.
- **Views:** PHP templates organised by module, using partials for layout, with CSRF tokens and escaped output to avoid XSS.
- **Helpers:** Validation, sanitisation, and CSRF token generation centralised for reuse.

Further architectural deep dives, request lifecycles, component interactions, and deployment topology are captured in [`docs/architecture.md`](docs/architecture.md).

## Data Architecture Summary
- Normalised relational schema with InnoDB engine and referential integrity enforcement.
- Core entities include `users`, `students`, `instructors`, `courses`, `enrollments`, `schedules`, `invoices`, `payments`, `vehicles`, `reminders`, `notifications`, and `audit_trail`.
- Crow's foot ER diagram, entity catalogue, indexing strategies, and data retention rules are documented in [`docs/database-design.md`](docs/database-design.md).
- Seed data is provided in [`sql/database.sql`](sql/database.sql) for rapid onboarding and testing.

## Directory Structure
A curated overview of the repository structure:

```
./index.php                Entry point & router
./app/
  bootstrap.php            Autoloader, session bootstrap, helpers registration
  config.php               Environment configuration, constants, PDO connection factory
  Core/                    Base controller, model, and routing primitives
  Controllers/             Request handlers per module
  Models/                  Data access objects (PDO based)
  Services/                Cross-cutting services (Auth, Audit, Notifications, etc.)
  Helpers/                 Validation, sanitisation, CSRF helpers
  Views/                   PHP templates grouped by feature + shared partials
./assets/                  CSS & JavaScript assets
./docs/                    Comprehensive documentation set (architecture, diagrams, operations)
./sql/                     Database schema & seed data
./tests/                   CLI regression tests per DAO / workflow
./uploads/                 User-uploaded documents (protected via access checks)
./logs/                    Application logs (rotatable)
```

## Setup & Installation
1. **Prerequisites**
   - PHP 8.1+ with PDO MySQL extension
   - Apache/Nginx configured to direct traffic to `index.php`
   - MySQL/MariaDB 10+
   - Composer (optional, for autoload dump)

2. **Clone & Configure**
   ```bash
   git clone <repository>
   cd DWIN309-A4
   ```
   Update database credentials, application name, SMTP placeholders, and environment flags in `app/config.php`.

3. **Install Dependencies**
   - No third-party PHP packages are required. Composer may be used to regenerate autoloaders:
     ```bash
     composer dump-autoload
     ```

4. **Database Provisioning**
   - Create database `origin_driving_school`.
   - Import `sql/database.sql` using phpMyAdmin or MySQL CLI:
     ```bash
     mysql -u <user> -p origin_driving_school < sql/database.sql
     ```

5. **File Permissions**
   - Ensure `uploads/` and `logs/` are writable by the web server user.

6. **Launch**
   - Point browser to the configured host (default: `http://localhost/DWIN309-A4/index.php`).

7. **Seed Accounts (from `sql/database.sql`)**
   - Admin: `admin@origin.com` / `password123`
   - Staff: `operations@origin.com` / `password123`
   - Instructor: `amelia.ward@origin.com` / `password123`
   - Student: `jack.mason@student.com` / `password123`

## Operational Usage
- **Authentication:** Access `/index.php?page=auth&action=login`. Sessions leverage PHP's native session handler with HTTP-only cookies.
- **Navigation:** Menu adapts to role; modules require appropriate permissions enforced via `Controller::requireRole()`.
- **Data Entry:** All forms are protected with CSRF tokens and server-side validation. Flash messaging communicates success/failure.
- **Documents:** Uploads stored in `uploads/documents` with sanitized filenames and MIME type enforcement.
- **Logging:** Business events recorded in `audit_trail`; technical issues logged under `logs/application.log`.

## Testing Strategy
- **DAO Regression Tests:** Each DAO has a CLI script in `tests/` (e.g., `tests/student_test.php`, `tests/invoice_test.php`). Execute after loading seed data.
- **Workflow Scenarios:** Composite scripts (e.g., `tests/schedule_test.php`) simulate end-to-end processes such as booking lessons and processing payments.
- **Manual QA:** Browser-based verification for UI flows, emphasising RBAC boundaries, scheduling conflicts, and invoice calculations.
- **Data Validation:** SQL constraints and indexes protect referential integrity; DAO tests include edge cases for invalid inputs.

## Security Hardening
- Bcrypt password hashing and strict password policies enforced at creation time.
- CSRF token verification on all mutating forms; tokens stored per-intent to prevent cross-module reuse.
- Input sanitisation via centralized helper; output escaping across views to mitigate XSS.
- Role-based access control with fine-grained checks before controller actions.
- Prepared statements and parameterized queries to eliminate SQL injection risk.
- Audit trail records user, action, entity, payload snapshot, and timestamp for sensitive operations.
- Optional reCAPTCHA/SMS verification hooks can be wired through `OutboundMessageService`.

## Deployment & DevOps Notes
- Designed for shared-hosting or XAMPP stack; no background workers required.
- Cron jobs recommended for reminder dispatch and log rotation (see [`docs/operations-and-maintenance.md`](docs/operations-and-maintenance.md)).
- Environment configuration handled via `app/config.php`; for multi-environment deployments, wrap in environment variable checks.
- Backup strategy should include nightly database dumps and file sync for `uploads/` and `logs/`.

## Maintenance Playbook
- **Log Rotation:** Rotate `logs/application.log` weekly or when exceeding 10 MB.
- **Database Maintenance:** Run `ANALYZE TABLE` monthly, monitor index usage, and clean stale reminders/notifications.
- **Security Reviews:** Quarterly review of user accounts, password policies, and dependency versions.
- **Upgrade Path:** PHP and MySQL upgrades should be validated via tests and staging environment before production roll-out.
- **Disaster Recovery:** Documented recovery steps in [`docs/operations-and-maintenance.md`](docs/operations-and-maintenance.md) outline RTO/RPO objectives and restoration procedures.

## Documentation Index
- [`docs/system-overview.md`](docs/system-overview.md): Business context, personas, user journeys, and feature deep dives.
- [`docs/architecture.md`](docs/architecture.md): Layered architecture, component relationships, request lifecycles, deployment topology.
- [`docs/database-design.md`](docs/database-design.md): Entity catalogue, crow's foot ER diagram, indexing strategy, retention policies.
- [`docs/diagrams.md`](docs/diagrams.md): Use case, class, sequence, activity, deployment, and data flow diagrams.
- [`docs/operations-and-maintenance.md`](docs/operations-and-maintenance.md): Runbooks for environments, logging, backups, monitoring, and incident response.
- [`docs/testers.md`](docs/testers.md): Instructions for executing DAO verification scripts.

This README is intentionally exhaustive; consult the individual documents above for specialised topics and diagrams.

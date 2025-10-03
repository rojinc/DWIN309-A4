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
The feature catalogue is aligned to departmental workflows:

- **Authentication & RBAC:** Secure login/logout, password hashing, CSRF protection, per-role dashboards (admin, staff, instructor, student).
- **Branch Administration:** Create and maintain branches, assign staff/instructors, view rosters.
- **Student Management:** CRUD, search, upload documents, capture notes, progress summaries, and attach enrolments.
- **Instructor Management:** Maintain accreditation details, track availability, view feedback summaries, manage assigned lessons.
- **Course Catalogue:** Manage courses, pricing, required lessons, and instructor allocations.
- **Enrolment Requests & Enrolments:** Accept self-service submissions, approve/decline requests, convert to structured enrolments.
- **Scheduling:** Create, reschedule, and cancel lessons/exams with conflict detection across instructors and vehicles; integrate reminders.
- **Fleet & Vehicle Maintenance:** Track vehicles, service logs, availability, and allocate to schedules.
- **Invoicing & Payments:** Auto-generate invoices, manage invoice items, apply payments, print/export friendly views, manage overdue reminders.
- **Notifications & Reminders:** In-app notifications, queued reminders (email/SMS/in-app) with acknowledgement tracking.
- **Communications:** Broadcast messages with audience filters and delivery history.
- **Reporting:** KPI dashboards, instructor performance, financial snapshots, export to CSV.
- **Document Repository:** Secure storage for licences, assessments, and attachments under role-based access.
- **Audit Trail:** Immutable log covering sensitive state changes for compliance and troubleshooting.

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

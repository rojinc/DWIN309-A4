# Origin Driving School Management System Architecture

## Application Stack
- **Language:** PHP 8 (no frameworks)
- **Database:** MySQL / MariaDB (accessed via PDO)
- **Front-end:** HTML5, vanilla JavaScript, single global CSS file (`assets/css/style.css`)
- **Pattern:** Lightweight MVC with Router delegating to Controllers and strongly-typed Data Access Objects (DAOs)
- **Security:** Password hashing (bcrypt), prepared statements, server-side validation, CSRF tokens, granular role-based access control.

## Directory Layout
```
./index.php                Front controller & router
./app/
  bootstrap.php            Autoloader & session bootstrap
  config.php               Environment configuration + DB credentials
  Core/                    Base MVC components
  Controllers/             Feature controllers (Dashboard, Students, etc.)
  Models/                  DAO classes encapsulating queries
  Services/                Helper services (Auth, Mailer stubs, Reporting)
  Helpers/                 Validation, Sanitization, CSRF utilities
  Views/                   PHP templates organised per module + shared partials
assets/
  css/style.css            Global stylesheet
  js/app.js                Front-end helpers (calendar, form UX)
docs/
  architecture.md          This document
  database-design.md       Normalised schema & ER notes
  testers.md               How to use DAO CLI testers
sql/
  database.sql             Schema + seed data for phpMyAdmin import
tests/
  *_test.php               Standalone CLI scripts exercising each DAO
uploads/
  documents/               Stored attachments (protected via access checks)
```

## Core Modules
1. **Authentication & RBAC** – login/logout, session management, per-role dashboards (admin, staff, instructor, student).
2. **Dashboard** – KPIs (active students, upcoming classes, overdue invoices, fleet availability), line chart (JS) and alerts.
3. **Students** – CRUD, search, document uploads, progress tracking, enrolments, lesson history, invoice links.
4. **Instructors** – profiles, qualifications, availability management, feedback summaries, schedule overview.
5. **Staff & Branches** – manage staff accounts, assign to branches, branch contact & roster vision.
6. **Courses** – catalogue with pricing, required lessons, assigned instructors, status toggling.
7. **Scheduling** – lesson/exam booking, conflict detection, drag-friendly calendar, reminder triggers, fleet assignment.
8. **Invoices & Payments** – auto-create on enrolment, edit line items, apply payments, generate printable PDF-ready layout.
9. **Reminders & Notifications** – queue reminders (email/SMS) and present in-app notifications with acknowledgement tracking.
10. **Communications** – send broadcast or targeted messages, retain delivery history.
11. **Fleet Management** – vehicle registry, service tracking, lesson allocation.
12. **Reporting & Analytics** – student progress, instructor KPIs, financial snapshots exportable as CSV.

## Database Overview
All tables use InnoDB, UTF8MB4, proper indexing, created via `sql/database.sql`.

- `users` – core profile & credentials (role, branch, status).
- `students`, `instructors`, `staff_profiles` – role-specific extensions.
- `branches`, `courses`, `course_instructor`, `enrollments` – academic structure.
- `vehicles`, `vehicle_service_logs` – fleet.
- `schedules` – lessons/exams; references enrollments, instructors, vehicles.
- `schedule_attendance` – actual attendance logs and feedback.
- `invoices`, `invoice_items`, `payments` – billing.
- `reminders`, `notifications` – messaging pipeline.
- `communications`, `communication_recipients` – mass comms history.
- `documents`, `notes` – attachments & qualitative tracking.
- `audit_trail` – action logging for compliance.

Refer to `docs/database-design.md` for column-level specification and relationships.

## Routing Strategy
`index.php` inspects `?page=` and optional `action`. Router maps to controller methods (`{Page}Controller::{action}Action`). Default route `dashboard/index` with authentication guard. Public routes: `auth/login`, `auth/logout`, `auth/register` (admin restricted).

## Security Highlights
- Password hashing via `password_hash` and `password_verify`.
- CSRF tokens stored in session and embedded in all POST forms.
- Input validation service returning sanitized payloads & errors.
- Upload sanitisation (whitelist file types, size limits, unique filenames).
- Strict access checks before data mutations, per-role permission matrix.
- Output escaped via `htmlspecialchars` to prevent XSS.

## Testing Approach
- Each DAO has dedicated CLI tester in `/tests` using sample dataset.
- Business workflows validated via scenario scripts (e.g., `tests/schedule_workflow_test.php`).
- JavaScript utilities covered with basic browser console instructions.

## Performance Considerations
- Lazy loading lists with pagination.
- Indexed queries and prepared statements only.
- Caching of configuration metadata (courses, branches) in session for quick reuse.

## Deployment Notes
- Designed for XAMPP on Windows: drop into `htdocs`, import `sql/database.sql`, adjust credentials in `app/config.php` if necessary.
- Document root exposes `index.php`; all assets referenced relatively (`./assets/...`).
- Writable directories (`uploads/documents`, `logs`) must have appropriate permissions.
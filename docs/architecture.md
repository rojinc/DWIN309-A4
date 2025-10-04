# Origin Driving School Management System Architecture

## 1. Architectural Goals
- **Unify operations** across students, instructors, fleet, and finance.
- **Optimise for shared hosting** (XAMPP) by avoiding heavy dependencies while maintaining clean separation of concerns.
- **Support observability and compliance** with audit trails and structured logging.
- **Remain extensible** for integrations (payments, SMS, BI) without refactoring core modules.

## 2. Application Stack
| Layer | Technology | Rationale |
| --- | --- | --- |
| Language | PHP 8.x | Modern OOP support, ubiquitous hosting availability |
| Web Server | Apache 2.4 (mod_php) | Default in XAMPP; simple rewrite routing |
| Database | MySQL / MariaDB 10.x | ACID transactions, referential integrity, phpMyAdmin friendly |
| Front-end | HTML5, vanilla JavaScript, CSS3 | Progressive enhancement, minimal dependencies |
| Sessions | PHP native session handler | Sufficient for single-node deployment |
| Auth | Bcrypt via `password_hash` | Secure password storage |
| Logging | File-based writer under `logs/` | Lightweight yet auditable |
| Background Jobs | Cron-triggered PHP scripts | Drives reminder dispatch, log rotation, archival |

## 3. Runtime Context
```
+------------+       HTTPS        +-----------------------+       PDO        +-------------------+
|  Browser   | <----------------> | Apache + PHP 8 (MVC)  | <-------------> | MySQL / MariaDB    |
| (User/UI)  |                    | index.php Front Ctrl. |                 | origin_driving...  |
+------------+                    +-----------------------+                 +-------------------+
        ^                                 |   ^
        | Static Assets (CSS/JS)          |   | Log Files / Uploads (writable dirs)
        +---------------------------------+   +-----------------------> `logs/`, `uploads/`
```

## 4. Layered Architecture
```
Presentation Layer (Views)
    ↑ render()
Controller Layer (App\Controllers\*)
    ↑ orchestrate
Service Layer (App\Services\*)
    ↑ collaborate
Data Access Layer (App\Models\*) ↔ Database (MySQL)
    ↑ utilities
Infrastructure Layer (App\Core, Helpers, bootstrap)
```
- **Controllers** enforce RBAC, orchestrate validation, manage transactions, and prepare data for views.
- **Services** encapsulate cross-cutting logic (authentication, reminders, notifications, audit logging, outbound messaging).
- **Models/DAOs** wrap SQL access with prepared statements, encapsulating entity-specific queries.
- **Views** are PHP templates, largely logic-free, relying on sanitized data passed in by controllers.
- **Helpers & Core** provide reusable utilities (CSRF, validation, routing, configuration, database connections).

## 5. Module Catalogue
| Module | Key Controllers | Primary Models | Supporting Services | Highlights |
| --- | --- | --- | --- | --- |
| Authentication | `AuthController` | `UserModel` (via `AuthService`) | `AuthService` | Login, logout, session management, CSRF-protected forms. |
| Dashboard | `DashboardController` | `ReportModel`, `ScheduleModel`, `InvoiceModel`, `NotificationModel`, `InstructorModel`, `StudentModel`, `EnrollmentRequestModel` | `ReminderService` | KPI cards, charts, role-specific insights, due reminder processing. |
| Students | `StudentsController` | `StudentModel`, `UserModel`, `EnrollmentModel`, `CourseModel`, `InvoiceModel`, `DocumentModel`, `ScheduleModel`, `InstructorModel`, `NoteModel`, `BranchModel` | `AuditService`, `NotificationService`, `ReminderService`, `OutboundMessageService` | Student CRM, enrolment workflows, document handling, messaging. |
| Instructors | `InstructorsController` | `InstructorModel`, `UserModel`, `BranchModel`, `ScheduleModel` | `AuditService` | Instructor onboarding, profile management, schedule overview. |
| Staff & Branches | `StaffController`, `BranchesController` | `StaffModel`, `BranchModel`, `UserModel` | `AuditService` | Staff provisioning, branch contact management, RBAC alignment. |
| Courses | `CoursesController` | `CourseModel`, `InstructorModel` | `AuditService` | Course catalogue CRUD, instructor allocations, pricing. |
| Enrolment Requests | `EnrollmentRequestsController` | `EnrollmentRequestModel`, `StudentModel`, `CourseModel`, `InstructorModel` | `NotificationService`, `OutboundMessageService`, `AuditService` | Intake pipeline, approvals, conversion to enrolments. |
| Scheduling | `SchedulesController`, `ApischedulesController` | `ScheduleModel`, `EnrollmentModel`, `InstructorModel`, `VehicleModel`, `BranchModel`, `StudentModel`, `CourseModel` | `ReminderService`, `AuditService` | Calendar, conflict detection, lesson booking, reminder queueing. |
| Fleet | `FleetController` | `VehicleModel`, `ScheduleModel` | `AuditService`, `ReminderService` (for maintenance reminders) | Vehicle registry, maintenance tracking, utilisation reports. |
| Finance | `InvoicesController`, `PaymentsController`, `StudentinvoicesController` | `InvoiceModel`, `PaymentModel`, `EnrollmentModel`, `StudentModel` | `AuditService`, `NotificationService`, `OutboundMessageService` | Invoice lifecycle, payment capture, overdue alerts, printable invoices. |
| Communications | `CommunicationsController`, `NotificationsController`, `RemindersController` | `CommunicationModel`, `NotificationModel`, `ReminderModel`, `UserModel` | `OutboundMessageService`, `NotificationService`, `ReminderService`, `AuditService` | Broadcast messaging, in-app notifications, reminder queue administration. |
| Documents | `DocumentsController` | `DocumentModel`, `StudentModel`, `InstructorModel`, `ScheduleModel` | `AuditService` | Secure download streaming, permission checks, audit logging. |
| Reporting | `ReportsController` | `ReportModel`, `StudentModel`, `InstructorModel`, `ScheduleModel`, `InvoiceModel` | `AuditService` (for export logging) | Analytical dashboards, CSV exports, retention metrics. |

## 6. Request Lifecycle
1. **Bootstrap** (`app/bootstrap.php`) loads configuration, registers autoloader, starts session, and initialises the global `AuthService`.
2. **Routing** occurs in `index.php`, mapping `?page` to controller class (`{Page}Controller`) and `action` to `{action}Action` method.
3. **Authentication Guard** ensures session validity; unauthenticated users redirected to `auth/login`.
4. **Authorisation** via `Controller::requireRole()` to enforce RBAC before executing controller logic.
5. **Input Normalisation** uses helper functions (`post()`, `get()`) plus `Validation::make()` to sanitise and validate payloads.
6. **Business Logic** orchestrated by controllers, often leveraging services and wrapping multi-DAO mutations in PDO transactions.
7. **Persistence** executed through models with prepared statements and explicit transactions when necessary.
8. **View Rendering** delegates to PHP templates with escaped variables, flash messaging, and CSRF tokens embedded.
9. **Response** delivered as HTML or JSON (for AJAX endpoints in scheduling module).

## 7. Cross-Cutting Concerns
- **Authentication:** `AuthService` manages login, logout, password verification, and session hydration.
- **Audit Logging:** `AuditService` records immutable events to `audit_trail` for compliance.
- **Notifications:** `NotificationService` persists in-app alerts; `ReminderService` schedules future reminders and processes due jobs.
- **Outbound Messaging:** `OutboundMessageService` encapsulates future integration with SMTP/SMS while currently logging payloads.
- **Validation & CSRF:** `Validation` helper ensures server-side rules; `Csrf` helper generates per-intent tokens stored in session.
- **Error Handling:** Controllers catch domain exceptions, roll back transactions, and flash contextual error messages.

## 8. Deployment Topology
```
+----------------------------+
| Single Web Server (LAMP)   |
| - Apache + PHP 8           |
| - Source code (git clone)  |
| - Writable: logs/, uploads/|
+--------------+-------------+
               |
               | PDO over TCP/IP
               v
+----------------------------+
| Managed MySQL/MariaDB      |
| - origin_driving_school DB |
| - Scheduled backups        |
+----------------------------+
```
Future scaling options include hosting MySQL separately, offloading static assets to CDN, and introducing a worker queue for reminders.

## 9. Front-end Architecture
- **CSS:** Single stylesheet `assets/css/style.css` segmented into layout, components, utilities, and responsive tweaks.
- **JavaScript:** `assets/js/app.js` handles UI enhancements (calendar rendering, modal toggles, AJAX helpers).
- **Templates:** Views under `app/Views/` use shared partials (navigation, footer, flash) and maintain minimal inline logic.
- **Accessibility:** Semantic HTML5 elements, labelled controls, keyboard navigation, and focus management.

## 10. Security Architecture
- **Password Security:** Bcrypt hashing with per-user salts; password reset tokens stored securely.
- **Session Protection:** HTTP-only cookies, session regeneration upon login.
- **RBAC Enforcement:** Fine-grained checks preceding each mutating action; students limited to personal data.
- **Input Safeguards:** Validation, sanitisation, `htmlspecialchars` in views, file upload whitelists, and size limits.
- **Transport Security:** Recommend TLS termination at Apache; enforce `https` links in production configuration.
- **Audit Trail:** Records user id, action, entity type/id, payload snapshot (JSON), timestamp; no delete/update operations on audit rows.

## 11. Performance & Scalability
- Pagination for heavy lists (students, invoices, schedules).
- Indexed columns for frequent filters (`schedules.instructor_id`, `invoices.status`, `reminders.status`, etc.).
- Caching of static metadata (branches, courses) in session to reduce repeated queries.
- Potential enhancements: HTTP caching headers, query result caching, asynchronous reminder processor.

## 12. Extensibility Strategy
- **Service Abstraction:** Services expose narrow interfaces; replacing email/SMS implementation is isolated to service class.
- **Modular Controllers:** Each bounded context has dedicated controller + view folder, easing feature additions.
- **Config-Driven:** `app/config.php` centralises environment toggles, integration credentials, feature flags.
- **Testing Hooks:** CLI scripts in `/tests` cover DAOs; easily migrate to PHPUnit by wrapping them into suites.

## 13. Deployment Pipeline
1. Develop locally; run CLI DAO tests (`php tests/*_test.php`).
2. Optional CI executes syntax checks (`php -l`), static analysis, and DAO scripts.
3. Deploy via rsync/SFTP to Apache host; ensure `logs/` and `uploads/` permissions.
4. Run SQL migrations or import updates via `sql/` scripts.
5. Perform smoke test: login as each role, verify dashboard, schedule creation, invoice issuance.

## 14. Observability & Recovery
- **Logging:** PHP error log + application log; configure log rotation (weekly or >10MB).
- **Monitoring:** MySQL slow query log, cron success logs, uptime checks (UptimeRobot/Netdata).
- **Backups:** Nightly `mysqldump`; incremental file sync for `uploads/`. Documented recovery steps in [`operations-and-maintenance.md`](operations-and-maintenance.md).
- **Incident Response:** Severity matrix, escalation contacts, rollback instructions detailed in operations runbook.

## 15. Reference Documents
- [System Overview](system-overview.md) — personas, business context, feature walkthroughs.
- [Database Design](database-design.md) — entity catalogue, crow's foot ERD, indexing, retention.
- [Diagrams](diagrams.md) — comprehensive UML/use-case representations.
- [Operations & Maintenance](operations-and-maintenance.md) — runbooks for deployment, monitoring, backups.

# System Overview

## 1. Introduction
Origin Driving School operates multiple branches across Victoria and required a unified back-office platform to manage students, instructors, fleet, scheduling, and finances. This document provides a holistic overview of the business context, user personas, workflows, and guiding principles for the Origin Driving School Management System.

## 2. Business Objectives
1. **Increase operational visibility** across branches with centralised dashboards and reporting.
2. **Streamline learner lifecycle** from enquiry through enrolment, scheduling, lesson delivery, and graduation.
3. **Improve compliance** by enforcing audit trails, document retention, and instructor accreditation tracking.
4. **Accelerate cash flow** via automated invoicing, payment tracking, and reminder workflows.
5. **Enhance communication** with targeted notifications, reminders, and broadcast messaging.

## 3. Stakeholders & Personas
| Persona | Responsibilities | Primary Modules |
| --- | --- | --- |
| **Administrator** | Configure branches, manage staff accounts, oversee finances, ensure compliance. | Dashboard, Staff, Branches, Courses, Finance, Reports |
| **Operations Staff** | Manage enrolments, schedules, invoices, payments, communications, documents. | Students, Scheduling, Invoices, Payments, Communications, Documents |
| **Instructor** | Maintain availability, deliver lessons, provide feedback, view assigned students/schedules. | Dashboard (instructor view), Scheduling, Students (restricted), Notifications |
| **Student/Learner** | Review schedules, invoices, receive notifications, upload documents, submit enrolment requests. | Dashboard (student view), Student Portal (subset of Students module), Invoices, Documents |
| **Finance Officer** *(subset of staff)* | Reconcile payments, generate financial reports, chase arrears. | Invoices, Payments, Reports |

## 4. High-Level Features
Each capability below references the primary personas that benefit from it and the critical functions delivered.

- **Multi-branch management** *(Administrator, Operations)* — Maintain branch directory, assign staff/instructors, compare KPIs, and surface branch-specific announcements.
- **Role-based dashboards** *(All personas)* — Provide persona-specific metrics (e.g., instructor utilisation, student pipeline status, overdue invoices, upcoming lessons) and actionable shortcuts.
- **Student CRM** *(Operations, Instructor)* — Capture contact details, licences, guardians/emergency contacts, enrolments, documents, communication history, notes, and progress summaries with timeline view.
- **Course catalogue** *(Administrator, Operations)* — Manage offerings, pricing tiers, lesson counts, prerequisites, course content outlines, and default instructor pools.
- **Enrolment request pipeline** *(Operations)* — Accept learner requests, prioritise queue, capture intake metadata, approve/decline with reasoning, trigger onboarding tasks and welcome messages.
- **Scheduling engine** *(Operations, Instructor)* — Visual calendars, availability planning, conflict detection across instructors/vehicles/branches, lesson outcomes recording, attendance tracking, automatic reminder creation.
- **Fleet tracking** *(Operations)* — Register vehicles, assign to branches, log servicing, flag compliance expiries, track utilisation, and mark downtime windows.
- **Financial suite** *(Administrator, Finance)* — Generate invoices with line items, manage discounts, process payments, compute outstanding balances, print/export statements, send overdue reminders.
- **Notifications and communications** *(Operations, Instructor, Student)* — In-app notifications, segmented broadcasts, reminder digest, read receipts, and acknowledgement audit.
- **Comprehensive audit trail** *(Administrator, Compliance)* — Immutable log of sensitive CRUD actions, filterable by user/entity/module for investigations.

### Module Feature Matrix
| Module | Core Functions | Supporting Automations |
| --- | --- | --- |
| **Authentication** | Login/logout, password reset initiation, session management, role gating. | CSRF token issuance, session regeneration, brute-force lockout counters. |
| **Dashboard** | Persona dashboards, KPI widgets, action shortcuts, reminder summary. | Background reminder processing, cached statistics refresh, notification fetching. |
| **Students** | CRUD, enrolment linking, document management, note taking, communication history, balance view. | Audit logging on critical updates, reminder queueing for missing documents, notification dispatch for onboarding milestones. |
| **Instructors** | Profile maintenance, availability submission, assigned schedule view, performance insights. | Conflict detection when updating availability, auto-reminders for expiring certifications. |
| **Courses & Enrolments** | Course catalogue, enrolment request approval, enrolment lifecycle (active/completed/cancelled). | Conversion wizard that creates student, enrolment, invoice, and welcome notification atomically. |
| **Scheduling** | Calendar views, lesson CRUD, outcome recording, printable itineraries, instructor/student calendars. | Reminder creation for lessons, automatic conflict checks (instructor, student, vehicle, branch). |
| **Fleet** | Vehicle registry, servicing log, allocation, maintenance alerting. | Reminder creation for service due dates, utilisation reports. |
| **Finance** | Invoice issuance, payment capture, credit notes, balance adjustments, statement exports. | Reminder engine for overdue invoices, audit logging for financial amendments. |
| **Communications** | Broadcast campaigns, targeted notifications, message templates, delivery history. | Outbound message logging, segmentation caching, opt-out respect. |
| **Reporting** | KPI dashboards, CSV exports, compliance snapshots. | Nightly roll-up jobs (optional) populating summary tables, caching heavy aggregates. |

### Automation & Integration Overview
- **ReminderService** polls `reminders` table for lessons/payments due, generates in-app notifications, and logs outbound payloads for SMS/email providers.
- **NotificationService** ensures each persona has a central inbox with read/unread tracking, linking back to originating entity.
- **AuditService** captures before/after snapshots with user context, enabling compliance review and rollback investigations.
- **OutboundMessageService** abstracts communication channels so future integrations (SMTP, SMS gateway) can be swapped without controller changes.
- **Validation helpers** guard against scheduling conflicts, duplicate enrolments, licence expiry oversights, and incomplete payment allocations.

## 5. User Journeys
### 5.1 Student Onboarding
1. Student submits enrolment request via staff or self-service form (capturing course preference, schedule, notes).
2. Operations staff review request, optionally assign instructor, approve/decline with notes.
3. Approval triggers creation of `user`, `student`, `enrollment`, and initial `invoice` records within a transaction.
4. Scheduler creates initial lessons, assigns instructor/vehicle, and reminder jobs scheduled automatically.
5. Student receives notification and can view upcoming lessons, invoices, and uploaded documents.

**Key automations:** Duplicate detection on email/licence, automatic audit log of approval decision, welcome notification queued via `NotificationService`, invoice reminder scheduled based on due date.

### 5.2 Lesson Delivery Cycle
1. Instructor views weekly calendar (filtered by instructor ID) showing upcoming schedules.
2. If instructor needs leave, they create an unavailability block to prevent bookings in that window.
3. Lesson occurs; instructor or staff marks status `completed`, optionally adds notes, updates student rating.
4. Reminder service marks reminders as sent; notifications log completion for student.
5. Audit trail captures schedule creation, updates, and completion events.

**Key automations:** Conflict detection triggers revalidation on update; completion triggers reminder cancellation and optional feedback request notification to student.

### 5.3 Financial Management
1. New enrolment auto-generates invoice with line items (course package).
2. Finance officer records partial or full payments; system recalculates balance and updates status (`sent`, `partial`, `paid`, `overdue`).
3. Reminder service monitors `reminders` table to send upcoming due notices via email/SMS/in-app notifications.
4. Reports module aggregates revenue, outstanding balances, and instructor earnings for dashboards.

**Key automations:** Overdue invoices escalate to operations dashboard, audit log records all balance adjustments, optional CSV export logs stored for compliance.

## 6. Functional Requirements
- **Authentication & RBAC:** Only authenticated users may access modules; actions constrained by role matrix.
- **Data Validation:** All forms require server-side validation (e.g., unique email, valid dates, required fields).
- **Scheduling Conflicts:** Prevent overlapping bookings for same instructor or vehicle; respect instructor unavailability windows.
- **Reminder Processing:** Cron-triggered `ReminderService::processDueReminders()` marks reminders as sent and spawns notifications/outbound messages.
- **Document Management:** Support PDF/image uploads with size/type checks; maintain metadata for retrieval.
- **Audit Logging:** Record actor, action, entity, and metadata for sensitive operations (create/update/delete for core entities).

## 7. Non-Functional Requirements
| Category | Requirement |
| --- | --- |
| Performance | Support simultaneous branch staff usage (~30 concurrent users) with <1s average response for CRUD operations. |
| Availability | Operate during business hours with nightly maintenance window; rely on single-node deployment with backups. |
| Security | Enforce HTTPS in production, bcrypt password hashing, RBAC, CSRF protection, sanitised outputs. |
| Maintainability | Code organised into controllers/models/services with PSR-4 autoloading. Extensive documentation and CLI tests. |
| Scalability | Database and app server can be scaled vertically; architecture ready for horizontal scaling with session store adjustments. |
| Compliance | Retain records per state regulatory requirements (5+ years for student records). |

## 8. Assumptions & Constraints
- Deployment on XAMPP or similar LAMP stack with PHP 8.1+ and MySQL/MariaDB 10+.
- Background jobs handled via cron; no dedicated worker queue assumed.
- Email/SMS integrations not yet connected; OutboundMessageService logs payloads for manual dispatch.
- Students access limited, curated subset of modules; majority of operations performed by staff.
- Multi-language/localisation not implemented; English (Australia) is default locale.

## 9. Risk Register
| Risk | Impact | Mitigation |
| --- | --- | --- |
| Single web server failure | High | Daily backups, documented recovery, optional standby environment. |
| Reminder cron misconfiguration | Medium | Monitoring + log review in `logs/`, manual catch-up script. |
| Data entry errors | Medium | Server-side validation, audit trail for reversal, notes capturing rationale. |
| Instructor overbooking | Medium | Conflict detection via database queries and `instructor_unavailability` enforcement. |
| Document storage growth | Medium | Periodic archival strategy, file size limits, cloud storage integration roadmap. |

## 10. Related Documents
- [`architecture.md`](architecture.md) — Technical architecture and component breakdown.
- [`database-design.md`](database-design.md) — Detailed schema definitions and ER diagrams.
- [`diagrams.md`](diagrams.md) — UML, sequence, activity, and deployment diagrams.
- [`operations-and-maintenance.md`](operations-and-maintenance.md) — Runbooks for day-to-day operations and incident response.

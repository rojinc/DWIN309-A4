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
- **Multi-branch management** with branch-level KPIs and contact directories.
- **Role-based dashboards** showing relevant metrics (e.g., instructor utilisation, student progress, overdue invoices).
- **Student CRM** capturing contact details, licences, notes, enrolments, documents, communication history.
- **Course catalogue** with lesson counts, pricing, instructor allocations.
- **Enrolment request pipeline** enabling prospective learners to request courses and staff to approve/decline.
- **Scheduling engine** with conflict detection across instructors and vehicles, reminder automation, completion tracking.
- **Fleet tracking** for vehicles, service dates, availability.
- **Financial suite** generating invoices, logging payments, calculating balances, and sending payment reminders.
- **Notifications and communications** for targeted messaging, broadcast announcements, and reminder delivery.
- **Comprehensive audit trail** capturing sensitive actions for compliance review.

## 5. User Journeys
### 5.1 Student Onboarding
1. Student submits enrolment request via staff or self-service form (capturing course preference, schedule, notes).
2. Operations staff review request, optionally assign instructor, approve/decline with notes.
3. Approval triggers creation of `user`, `student`, `enrollment`, and initial `invoice` records within a transaction.
4. Scheduler creates initial lessons, assigns instructor/vehicle, and reminder jobs scheduled automatically.
5. Student receives notification and can view upcoming lessons, invoices, and uploaded documents.

### 5.2 Lesson Delivery Cycle
1. Instructor views weekly calendar (filtered by instructor ID) showing upcoming schedules.
2. If instructor needs leave, they create an unavailability block to prevent bookings in that window.
3. Lesson occurs; instructor or staff marks status `completed`, optionally adds notes, updates student rating.
4. Reminder service marks reminders as sent; notifications log completion for student.
5. Audit trail captures schedule creation, updates, and completion events.

### 5.3 Financial Management
1. New enrolment auto-generates invoice with line items (course package).
2. Finance officer records partial or full payments; system recalculates balance and updates status (`sent`, `partial`, `paid`, `overdue`).
3. Reminder service monitors `reminders` table to send upcoming due notices via email/SMS/in-app notifications.
4. Reports module aggregates revenue, outstanding balances, and instructor earnings for dashboards.

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

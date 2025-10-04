# Diagram Catalogue

This document consolidates the primary diagrams describing the Origin Driving School Management System. All diagrams are rendered using [Mermaid](https://mermaid.js.org/) syntax for maintainability.

## 1. Use Case Diagram
```mermaid
usecaseDiagram
  actor Admin as "Administrator"
  actor Staff as "Operations Staff"
  actor Instructor
  actor Student

  Admin -- (Manage Branches)
  Admin -- (Configure Courses)
  Admin -- (View Organisation KPIs)
  Admin -- (Manage Staff Accounts)
  Admin -- (Audit Sensitive Actions)

  Staff -- (Onboard Student)
  Staff -- (Approve Enrolment Request)
  Staff -- (Schedule Lesson)
  Staff -- (Issue Invoice)
  Staff -- (Record Payment)
  Staff -- (Send Broadcast Communication)
  Staff -- (Upload Document)

  Instructor -- (View Assigned Schedule)
  Instructor -- (Submit Availability)
  Instructor -- (Record Lesson Outcome)
  Instructor -- (View Assigned Students)

  Student -- (Submit Enrolment Request)
  Student -- (View Lesson Schedule)
  Student -- (Review Invoice & Balance)
  Student -- (Upload Identification Document)
  Student -- (Receive Notifications)
```

## 2. Logical Component Diagram
```mermaid
flowchart LR
  subgraph Presentation
    V[Views (PHP Templates)]
    JS[assets/js/app.js]
    CSS[assets/css/style.css]
  end

  subgraph Controllers
    AuthC[AuthController]
    StudentsC[StudentsController]
    SchedulesC[SchedulesController]
    InvoicesC[InvoicesController]
    DashboardC[DashboardController]
  end

  subgraph Services
    AuthS[AuthService]
    ReminderS[ReminderService]
    NotificationS[NotificationService]
    OutboundS[OutboundMessageService]
    AuditS[AuditService]
  end

  subgraph Models
    UserM[UserModel]
    StudentM[StudentModel]
    EnrollmentM[EnrollmentModel]
    ScheduleM[ScheduleModel]
    InvoiceM[InvoiceModel]
    ReminderM[ReminderModel]
    NotificationM[NotificationModel]
    CommunicationM[CommunicationModel]
  end

  DB[(MySQL Database)]
  Files[(Uploads / Logs)]

  V --> AuthC
  V --> StudentsC
  V --> SchedulesC
  V --> InvoicesC
  V --> DashboardC
  JS --> SchedulesC
  CSS --> V
  AuthC --> AuthS
  StudentsC --> ReminderS
  StudentsC --> NotificationS
  StudentsC --> AuditS
  SchedulesC --> ReminderS
  SchedulesC --> AuditS
  InvoicesC --> NotificationS
  InvoicesC --> OutboundS
  InvoicesC --> AuditS
  DashboardC --> ReminderS
  AuthS --> UserM
  ReminderS --> ReminderM
  ReminderS --> NotificationS
  ReminderS --> OutboundS
  NotificationS --> NotificationM
  OutboundS --> Files
  AuditS --> AuditTrail[(audit_trail table)]
  StudentsC --> StudentM
  StudentsC --> EnrollmentM
  StudentsC --> ScheduleM
  StudentsC --> InvoiceM
  SchedulesC --> ScheduleM
  SchedulesC --> EnrollmentM
  InvoicesC --> InvoiceM
  Models --> DB

```

## 3. UML Class Diagram (Simplified)
```mermaid
classDiagram
  class Controller {
    <<abstract>>
    - AuthService $auth
    + __construct()
    + requireAuth()
    + requireRole(roles)
    + render(view, data)
    + redirect(url)
  }

  class StudentsController {
    - StudentModel $students
    - EnrollmentModel $enrollments
    - InvoiceModel $invoices
    - DocumentModel $documents
    - NotificationService $notifications
    - ReminderService $reminders
    - AuditService $audit
    + indexAction()
    + createAction()
    + storeAction()
    + showAction(id)
    + updateAction(id)
    + destroyAction(id)
  }

  class ScheduleModel {
    + all()
    + upcoming(limit, instructorId, studentId)
    + create(data)
    + update(id, data)
    + forInstructor(id)
    + forStudent(id)
    + detectConflicts(data)
  }

  class ReminderService {
    + queueScheduleReminder(scheduleId)
    + queueInvoiceReminder(invoiceId)
    + processDueReminders()
  }

  class NotificationService {
    + create(userId, title, message, level)
    + forUser(userId)
    + markRead(notificationId)
  }

  class AuditService {
    + record(action, entityType, entityId, details)
  }

  Controller <|-- StudentsController
  StudentsController --> StudentModel
  StudentsController --> EnrollmentModel
  StudentsController --> InvoiceModel
  StudentsController --> DocumentModel
  StudentsController --> NotificationService
  StudentsController --> ReminderService
  StudentsController --> AuditService
  ReminderService --> ReminderModel
  ReminderService --> NotificationService
  ReminderService --> OutboundMessageService
  ScheduleModel --> Database
```

## 4. Sequence Diagram — Schedule Creation
```mermaid
sequenceDiagram
  participant Staff
  participant StudentsController as StudentsController
  participant ScheduleModel as ScheduleModel
  participant ReminderService as ReminderService
  participant NotificationService as NotificationService
  participant OutboundMessageService as OutboundService
  participant AuditService as AuditService

  Staff->>StudentsController: POST schedule form
  StudentsController->>ScheduleModel: detectConflicts(payload)
  ScheduleModel-->>StudentsController: conflict result
  alt no conflicts
    StudentsController->>ScheduleModel: create(payload)
    ScheduleModel-->>StudentsController: scheduleId
    StudentsController->>ReminderService: queueScheduleReminder(scheduleId)
    ReminderService->>NotificationService: create(studentId, "Lesson booked", ...)
    ReminderService->>OutboundService: dispatch SMS/Email payload
    StudentsController->>AuditService: record("schedule_created", scheduleId)
    StudentsController-->>Staff: success response
  else conflict found
    StudentsController-->>Staff: error message (conflict details)
  end
```

## 5. Activity Diagram — Invoice Settlement
```mermaid
flowchart TD
  A[Invoice Created] --> B{Payment Received?}
  B -- No --> C[Reminder Scheduled]
  C --> D[Reminder Sent]
  D --> B
  B -- Yes --> E[Record Payment]
  E --> F[Update Invoice Status]
  F --> G{Balance Remaining?}
  G -- Yes --> H[Status = Partial]
  H --> C
  G -- No --> I[Status = Paid]
  I --> J[Notify Student]
```

## 6. Deployment Diagram
```mermaid
graph TD
  subgraph Client Environment
    Browser[Web Browser]
  end

  subgraph DMZ
    Apache[Apache 2.4 + PHP 8]
    direction TB
    Apache -->|Serves| Assets[Static Assets]
  end

  subgraph Internal Network
    MySQL[(MySQL/MariaDB)]
    Cron[Cron Scheduler]
    Storage[(File Storage: uploads/, logs/)]
  end

  Browser -->|HTTPS| Apache
  Apache -->|PDO| MySQL
  Apache --> Storage
  Cron --> Apache
  Cron --> ReminderService
```

## 7. Data Flow Diagram (Context Level)
```mermaid
flowchart LR
  Student[(Student)] -->|Enrolment Requests, Documents| App[Origin Management System]
  Instructor[(Instructor)] -->|Availability, Lesson Outcomes| App
  Staff[(Operations Staff)] -->|Student Data, Scheduling, Finance| App
  App -->|Reminders / Notifications| Student
  App -->|Schedules & Tasks| Instructor
  App -->|Reports, Audit Logs| Administrator[(Administrator)]
  App -->|Invoices, Payments| Finance[(Finance Officer)]
  App -->|SQL Queries| DB[(MySQL Database)]
  App -->|Uploads, Logs| Storage[(File System)]
```

These diagrams should be reviewed alongside the textual descriptions in [`architecture.md`](architecture.md) and [`system-overview.md`](system-overview.md) for full context.

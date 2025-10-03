# Database Design

## Overview
The Origin Driving School Management System uses a normalised MySQL schema with InnoDB storage, UTF8MB4 encoding, and referential integrity enforced through foreign keys. Primary keys are unsigned integers with auto-increment, and transactional consistency is maintained via PDO with prepared statements.

## Entity Catalogue

### users
| Column | Type | Notes |
| --- | --- | --- |
| id | INT UNSIGNED PK | Auto increment |
| role | ENUM('admin','staff','instructor','student') | Authorisation role |
| first_name | VARCHAR(80) | |
| last_name | VARCHAR(80) | |
| email | VARCHAR(160) UNIQUE | Login credential |
| phone | VARCHAR(32) | |
| password_hash | VARCHAR(255) | Bcrypt hash |
| status | ENUM('active','archived') DEFAULT 'active' | Soft delete |
| branch_id | INT UNSIGNED NULL FK branches(id) | Home branch |
| created_at | DATETIME | |
| updated_at | DATETIME | |

### branches
Stores contact details for each driving school location.

### students
Extends `users` for learner-specific data including licence, emergency contact, address, and progress summary. References `users(id)` and `branches(id)`.

### instructors
Extends `users` for instructor accreditation, experience, and availability. References `users(id)` and `branches(id)`.

### staff_profiles
Extends `users` for non-instructor employees (operations, finance, etc.). References `users(id)` and `branches(id)`.

### courses
Defines training programmes with pricing and lesson count. Junction table `course_instructor(course_id, instructor_id)` manages teaching allocations.

### enrollments
Links students to courses. Tracks start date, status (active, in_progress, completed, cancelled), progress percentage, and notes. References `students(id)` and `courses(id)`.

### vehicles
Fleet registry with transmission type, plate number, VIN, branch, availability status, service history dates, and notes.

### schedules
Lesson/exam bookings referencing `enrollments`, `instructors`, `vehicles`, and `branches`. Enforces conflict detection (instructor/vehicle/time). Tracks start/end times, event type, status, lesson topic, notes, and reminder flag.

### invoices & invoice_items
Financial documents generated per enrolment. Header totals (subtotal, GST, total) with line items for each charge. `invoice_items` holds description, quantity, unit price, and row total.

### payments
Receipts recorded against invoices including amount, date, method, reference, notes, and user who recorded the payment.

### reminders
Stores automated reminder jobs (invoice, schedule). Columns: related_type, related_id, recipient_user_id, channel, reminder_type, message, send_on, status.

### notifications
In-app alerts for individual users. Contains title, message, level (info|success|warning|danger), is_read flag, timestamps.

### communications & communication_recipients
Broadcast or targeted messages captured for audit. Header row includes sender, audience scope, channel, subject, message. Recipients table normalises user targets.

### documents
Uploaded files metadata (user_id, file_name, file_path, mime_type, file_size, category, notes).

### notes
Qualitative notes per entity (student, instructor, staff) linked to author user and timestamped.

### audit_trail
Immutable log of key actions (user_id, action, entity_type, entity_id, details, created_at).

## Referential Integrity Highlights
- `students.user_id`, `instructors.user_id`, `staff_profiles.user_id` all cascade on delete to preserve data integrity when a user is removed (soft delete controlled via status instead).
- `enrollments.student_id` references `students.id`; deleting a student cascades to enrolments, schedules, invoices, and reminders.
- `schedules.vehicle_id` is nullable to allow bookings without vehicle assignment.
- `payments.invoice_id` cascades, ensuring orphan payments cannot exist.

## Indexing Strategy
- Unique indexes on `users.email`, `vehicles.plate_number`, `invoices.invoice_number`.
- Secondary indexes on foreign key columns (e.g., `schedules` on `instructor_id`, `vehicle_id`, `scheduled_date`).
- Composite index on `reminders (status, send_on)` for quick due reminder retrieval.

## Sample Data
Seed data in `sql/database.sql` covers:
- Foundational branches (CBD, Bayside, Northern Suburbs).
- Admin, staff, instructor, and student accounts with hashed passwords.
- Courses (Learner Package, Overseas Licence Conversion, Test Day Intensive).
- Fleet vehicles with service schedules.
- Enrolments, schedules, invoices, payments, reminders, notifications for demonstration.

## Import Instructions
1. Start MySQL/MariaDB (XAMPP).  
2. Create database `origin_driving_school`.  
3. Use phpMyAdmin or `mysql` CLI to run `sql/database.sql`.  
4. Update `app/config.php` if your MySQL credentials differ from the defaults.

The schema is designed for transactional integrity, minimal duplication, and rapid querying by common administrative workflows.
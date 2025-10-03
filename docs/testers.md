# DAO Test Scripts

The `tests/` directory contains standalone PHP scripts that exercise the major data access objects with sample data loaded via [`sql/database.sql`](../sql/database.sql). Each script bootstraps the full application (`app/bootstrap.php`) to ensure parity with production configuration.

## Running Tests
1. Ensure MySQL/MariaDB is running and `sql/database.sql` has been imported.
2. Navigate to the project root (`/path/to/DWIN309-A4`).
3. Execute the desired script using the PHP CLI, for example:
   ```bash
   php tests/student_test.php
   ```
4. Review console output. Scripts echo structured status blocks and exit with non-zero status on failure.

> Tip: Run all tests sequentially with `for file in tests/*_test.php; do php "$file" || break; done`.

## Available Test Scripts
| Script | Targets | Coverage Highlights |
| --- | --- | --- |
| `branch_test.php` | `BranchModel` | CRUD operations, branch search, contact updates. |
| `communication_test.php` | `CommunicationModel` | Message history listing, recipient retrieval. |
| `course_test.php` | `CourseModel` | Course catalogue listing, instructor assignment retrieval. |
| `enrollment_request_test.php` | `EnrollmentRequestModel` | Intake lifecycle, instructor filtering, status transitions. |
| `instructor_test.php` | `InstructorModel`, `BranchModel` | Instructor listing, branch associations, profile lookups. |
| `invoice_test.php` | `InvoiceModel`, `PaymentModel` | Invoice retrieval, balance calculation, payment ledger, status updates. |
| `notification_test.php` | `NotificationModel` | Notification creation, read-state filtering. |
| `reminder_test.php` | `ReminderModel`, `ReminderService` | Due reminder retrieval, processing loop validation. |
| `report_test.php` | `ReportModel` | Dashboard aggregates (enrolments, revenue, instructor stats). |
| `schedule_test.php` | `ScheduleModel` | Conflict detection, instructor/student calendars, completion updates. |
| `student_test.php` | `StudentModel`, `UserModel`, `EnrollmentModel` | Student CRUD, search, enrolment linkage, progress summaries. |
| `vehicle_test.php` | `VehicleModel` | Fleet listing, branch filtering, status updates. |

## Troubleshooting
- If a test fails due to missing tables, confirm the schema import succeeded and matches the latest `sql/database.sql`.
- Ensure `app/config.php` contains valid database credentials for the CLI environment.
- Clear residual data between runs by re-importing the SQL script to reset to a known state.
- Review `logs/application.log` for additional context if tests trigger service-level logging.

For further architectural context consult [`architecture.md`](architecture.md) and [`database-design.md`](database-design.md).

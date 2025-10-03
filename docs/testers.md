# DAO Test Scripts

The `/tests` directory contains standalone PHP scripts that exercise the major data access objects with known-good sample data loaded via `sql/database.sql`.

## Running Tests
1. Ensure your web server/database are running and that `sql/database.sql` has been imported.  
2. From the repository root (`c:\xampp\htdocs`), run:  
   `php tests/student_test.php`  
3. Observe the console output for pass/fail results. Each script prints structured results and will exit with a non-zero code if an assertion fails.

## Available Tests
- `student_test.php` – validates CRUD operations, search, and progress stats for `StudentModel`.
- `instructor_test.php` – exercises instructor creation, lookup, and branch filtering.
- `schedule_test.php` – verifies conflict detection, calendar aggregation, and instructor/student queries.
- `invoice_test.php` – covers invoice creation with line items, status updates, and payment balance calculation.
- `reminder_test.php` – ensures reminder queue insertion and due processing logic.
- `report_test.php` – confirms aggregated dashboard metrics.

Each tester bootstraps the application environment (via `app/bootstrap.php`) and uses sample payloads representative of production workflows. Adjust database credentials in `app/config.php` before running if necessary.
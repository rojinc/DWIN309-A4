CREATE DATABASE IF NOT EXISTS origin_driving_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE origin_driving_school;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS audit_trail;
DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS communication_recipients;
DROP TABLE IF EXISTS communications;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS course_instructor;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS staff_profiles;
DROP TABLE IF EXISTS instructors;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS branches;

CREATE TABLE branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(80) NULL,
    state VARCHAR(50) NULL,
    postcode VARCHAR(12) NULL,
    phone VARCHAR(32) NULL,
    email VARCHAR(160) NULL,
    manager_name VARCHAR(120) NULL,
    opening_hours VARCHAR(160) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin','staff','instructor','student') NOT NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(32) NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    branch_id INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    license_number VARCHAR(45) NULL,
    license_status VARCHAR(20) DEFAULT 'Learner',
    license_expiry DATE NULL,
    emergency_contact_name VARCHAR(120) NULL,
    emergency_contact_phone VARCHAR(32) NULL,
    address_line VARCHAR(160) NULL,
    city VARCHAR(80) NULL,
    postcode VARCHAR(12) NULL,
    progress_summary VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_students_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE instructors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    certification_number VARCHAR(80) NULL,
    accreditation_expiry DATE NULL,
    experience_years INT UNSIGNED DEFAULT 0,
    availability_notes TEXT NULL,
    bio TEXT NULL,
    rating DECIMAL(3,2) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_instructors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_instructors_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    position_title VARCHAR(100) NULL,
    employment_type VARCHAR(40) NULL,
    start_date DATE NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_staff_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    lesson_count INT UNSIGNED NOT NULL,
    category VARCHAR(80) NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE course_instructor (
    course_id INT UNSIGNED NOT NULL,
    instructor_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (course_id, instructor_id),
    CONSTRAINT fk_ci_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_ci_instructor FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    type VARCHAR(80) NULL,
    transmission VARCHAR(20) NULL,
    plate_number VARCHAR(20) UNIQUE,
    vin VARCHAR(50) NULL,
    branch_id INT UNSIGNED NULL,
    status ENUM('available','in_service','maintenance') DEFAULT 'available',
    last_service_date DATE NULL,
    next_service_due DATE NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicles_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    status ENUM('active','in_progress','completed','cancelled') DEFAULT 'active',
    progress_percentage INT UNSIGNED DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_enroll_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    instructor_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NULL,
    event_type ENUM('lesson','exam','assessment') DEFAULT 'lesson',
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
    lesson_topic VARCHAR(160) NULL,
    notes TEXT NULL,
    reminder_sent TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_instructor FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    CONSTRAINT fk_schedule_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    INDEX idx_schedule_date (scheduled_date),
    INDEX idx_schedule_instructor (instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('sent','partial','paid','overdue') DEFAULT 'sent',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_item_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    method VARCHAR(40) NULL,
    reference VARCHAR(80) NULL,
    notes TEXT NULL,
    recorded_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reminders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    related_type ENUM('invoice','schedule') NOT NULL,
    related_id INT UNSIGNED NOT NULL,
    recipient_user_id INT UNSIGNED NOT NULL,
    channel ENUM('email','sms','in-app') DEFAULT 'sms',
    reminder_type VARCHAR(80) NOT NULL,
    message TEXT NOT NULL,
    send_on DATE NOT NULL,
    status ENUM('pending','sent','cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reminder_user FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reminder_status_send (status, send_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    level ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE communications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT UNSIGNED NULL,
    audience_scope VARCHAR(40) NOT NULL,
    channel ENUM('email','sms','in-app') NOT NULL,
    subject VARCHAR(160) NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comm_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE communication_recipients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    communication_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_cr_comm FOREIGN KEY (communication_id) REFERENCES communications(id) ON DELETE CASCADE,
    CONSTRAINT fk_cr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(160) NOT NULL,
    file_path VARCHAR(160) NOT NULL,
    mime_type VARCHAR(80) NULL,
    file_size INT UNSIGNED NULL,
    category VARCHAR(80) NULL,
    notes VARCHAR(160) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_document_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    related_type ENUM('student','instructor','staff') NOT NULL,
    related_id INT UNSIGNED NOT NULL,
    author_user_id INT UNSIGNED NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_note_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_trail (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id INT UNSIGNED NULL,
    details TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- Seed data
INSERT INTO branches (name, address, city, state, postcode, phone, email, manager_name, opening_hours) VALUES
('CBD Headquarters', '120 Collins St', 'Melbourne', 'VIC', '3000', '(03) 8000 9000', 'cbd@origin.com.au', 'Sophie Turner', 'Mon-Fri 8am-6pm'),
('Bayside Training Hub', '45 Beach Rd', 'St Kilda', 'VIC', '3182', '(03) 8111 9200', 'bayside@origin.com.au', 'Marcus Shaw', 'Mon-Sat 8am-7pm'),
('Northern Suburbs Centre', '28 Plenty Rd', 'Preston', 'VIC', '3072', '(03) 8222 9300', 'north@origin.com.au', 'Linh Nguyen', 'Mon-Fri 9am-5pm');

INSERT INTO users (role, first_name, last_name, email, phone, password_hash, status, branch_id) VALUES
('admin', 'Laura', 'Matthews', 'admin@origin.com', '(03) 8000 9000', '$2y$10$G6NCBslVaYRbfCoWPNWGz.f3HcXEKDjaWjGdPBU4rwr0c1TH1WW2i', 'active', 1),
('staff', 'Oliver', 'Grant', 'operations@origin.com', '(03) 8000 9001', '$2y$10$G6NCBslVaYRbfCoWPNWGz.f3HcXEKDjaWjGdPBU4rwr0c1TH1WW2i', 'active', 1),
('instructor', 'Amelia', 'Ward', 'amelia.ward@origin.com', '0400 111 222', '$2y$10$G6NCBslVaYRbfCoWPNWGz.f3HcXEKDjaWjGdPBU4rwr0c1TH1WW2i', 'active', 2),
('student', 'Jack', 'Mason', 'jack.mason@student.com', '0401 555 777', '$2y$10$G6NCBslVaYRbfCoWPNWGz.f3HcXEKDjaWjGdPBU4rwr0c1TH1WW2i', 'active', 2);

INSERT INTO staff_profiles (user_id, branch_id, position_title, employment_type, start_date, notes) VALUES
(2, 1, 'Operations Coordinator', 'Full-time', '2021-02-15', 'Oversees daily bookings and invoicing.');

INSERT INTO instructors (user_id, branch_id, certification_number, accreditation_expiry, experience_years, availability_notes, bio, rating) VALUES
(3, 2, 'CERT-IV-DRV-2020-045', '2025-11-30', 8, 'Available Tue-Sat, prefers mornings.', 'Former fleet instructor specialising in defensive driving.', 4.8);

INSERT INTO students (user_id, branch_id, license_number, license_status, license_expiry, emergency_contact_name, emergency_contact_phone, address_line, city, postcode, progress_summary) VALUES
(4, 2, 'L1234567', 'Learner', '2026-05-01', 'Sarah Mason', '0402 888 999', '12 Marine Parade', 'St Kilda', '3182', 'Enrolled and first lesson scheduled.');

INSERT INTO courses (title, description, price, lesson_count, category, status) VALUES
('Learner Essentials Package', 'Ten structured lessons covering VicRoads criteria.', 880.00, 10, 'Learner', 'active'),
('Overseas Licence Conversion', 'Tailored refresher for overseas licence holders.', 540.00, 6, 'Conversion', 'active'),
('Test Day Intensive', 'Pre-test simulation and vehicle hire on test day.', 320.00, 2, 'Assessment', 'active');

INSERT INTO course_instructor (course_id, instructor_id) VALUES
(1, 1),
(2, 1),
(3, 1);

INSERT INTO vehicles (name, type, transmission, plate_number, vin, branch_id, status, last_service_date, next_service_due, notes) VALUES
('Toyota Corolla Hybrid', 'Hatchback', 'Automatic', 'ORI-123', 'JTNK43BE503456789', 2, 'available', '2024-12-10', '2025-06-10', 'Hybrid model used for learner lessons.'),
('Mazda 3 Sport', 'Sedan', 'Manual', 'ORI-456', 'JM0BN423810123456', 1, 'maintenance', '2024-11-01', '2025-05-01', 'Manual gearbox training car.');

INSERT INTO enrollments (student_id, course_id, start_date, status, progress_percentage, notes) VALUES
(1, 1, '2025-03-01', 'active', 10, 'Completed orientation lesson.');

INSERT INTO invoices (enrollment_id, invoice_number, issue_date, due_date, subtotal, tax_amount, total, status, notes) VALUES
(1, 'INV-20250301-0001', '2025-03-01', '2025-03-08', 880.00, 88.00, 968.00, 'partial', 'Auto-generated upon enrolment.');

INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES
(1, 'Learner Essentials Package (10 lessons)', 1, 880.00, 880.00);

INSERT INTO payments (invoice_id, amount, payment_date, method, reference, notes, recorded_by) VALUES
(1, 400.00, '2025-03-02', 'Card', 'AUTH7832', 'Deposit received online.', 2);

INSERT INTO schedules (enrollment_id, instructor_id, vehicle_id, branch_id, event_type, scheduled_date, start_time, end_time, status, lesson_topic, notes, reminder_sent) VALUES
(1, 1, 1, 2, 'lesson', '2025-03-05', '09:00:00', '10:30:00', 'scheduled', 'Urban driving fundamentals', 'Focus on mirrors and observation.', 0),
(1, 1, 1, 2, 'lesson', '2025-03-12', '09:00:00', '10:30:00', 'scheduled', 'Parking techniques', 'Parallel parking practice.', 0);

INSERT INTO reminders (related_type, related_id, recipient_user_id, channel, reminder_type, message, send_on, status) VALUES
('invoice', 1, 4, 'email', 'Invoice Due', 'Your invoice INV-20250301-0001 is due on 08 Mar 2025.', '2025-03-06', 'pending'),
('schedule', 1, 4, 'sms', 'Upcoming Lesson', 'Reminder: lesson on 05 Mar 2025 at 09:00.', '2025-03-04', 'sent');

INSERT INTO notifications (user_id, title, message, level, is_read, created_at) VALUES
(4, 'Welcome to Origin Driving School', 'Your first lesson is booked for 05 Mar 2025.', 'info', 0, '2025-03-01 09:00:00'),
(3, 'New Lesson Assigned', 'Jack Mason has been assigned to you on 05 Mar 2025.', 'info', 1, '2025-03-01 09:05:00');

INSERT INTO communications (sender_user_id, audience_scope, channel, subject, message, created_at) VALUES
(2, 'selected', 'email', 'Autumn Coaching Clinics', 'Register for our advanced manoeuvres clinic this April.', '2025-02-20 10:00:00');

INSERT INTO communication_recipients (communication_id, user_id) VALUES
(1, 3),
(1, 4);

INSERT INTO documents (user_id, file_name, file_path, mime_type, file_size, category, notes) VALUES
(4, 'Learner_Permit.pdf', 'doc_learner_permit.pdf', 'application/pdf', 234567, 'Identification', 'Uploaded via admin');

INSERT INTO notes (related_type, related_id, author_user_id, content, created_at) VALUES
('student', 1, 2, 'Jack shows strong mirror skills; continue practising lane changes.', '2025-03-05 11:15:00');

INSERT INTO audit_trail (user_id, action, entity_type, entity_id, details, created_at) VALUES
(2, 'student_created', 'student', 1, 'Enrollment #1 and invoice INV-20250301-0001 generated.', '2025-03-01 08:30:00'),
(2, 'schedule_created', 'schedule', 1, 'Lesson scheduled for 05 Mar 2025.', '2025-03-01 08:45:00');
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**ACE Tuition Management System** — a plain PHP + MySQL web app for managing tuition center operations. No build tools, no Composer, no framework. Files are served directly by Apache (XAMPP/WAMP).

## Setup

1. Copy the project folder to `C:/xampp/htdocs/` (XAMPP) or `C:/wamp/www/` (WAMP).
2. Import `tuition_db.sql` into phpMyAdmin as database `tuitiondb`.
3. Start Apache and MySQL in XAMPP/WAMP.
4. Access via `http://localhost/tuitionsystem/`.

Database connection is hardcoded in each portal's `db.php`:
- Host: `localhost`, DB: `tuitiondb`, User: `root`, Password: `""`
- Uses MySQLi procedural style (`mysqli_connect`) — not PDO.

## Architecture

### Role-Based Portal Structure

The app is split into four self-contained portals, each in its own directory with its own `db.php`, `login.php`, and `logout.php`:

| Directory | Role | Login URL |
|-----------|------|-----------|
| `/admin/` | Admin | `/admin/login.php` |
| `/parent/` | Parent | `/parent/login.php` |
| `/student/` | Student (root `/`) | `/login.php` or `/tms/login.php` |
| `/teacher/` | Teacher | `/teacher/login.php` |

Each portal checks `$_SESSION['role']` and `$_SESSION['username']` at the top of every page and redirects to its own `login.php` if not authenticated.

### Key Database Tables

- `users` — all user accounts; `role` column distinguishes admin/teacher/parent
- `children` — student records linked to parents via `parent_child` join table
- `subjects` — subject listings with `fee`, `subject_code`, `time`
- `payments` — payment records with `receipt_number`, `parent_username`, `child_id`, `month`, `year`
- `payment_items` — line items per payment (subject, `base_fee`, `monthly_adjustment`, `final_fee`)
- `payment_history` — audit trail of payment status changes
- `student_subjects` — which subjects a student is enrolled in

### Page Pattern

Every page follows the same structure:
1. `session_start()` + authentication check at the top
2. `include 'db.php'` to get `$conn`
3. PHP business logic (queries, POST handling)
4. Inline HTML with embedded CSS (no separate stylesheet per page — styles are duplicated across pages)
5. Bootstrap 5 + Font Awesome loaded from CDN
6. JavaScript at the bottom (sidebar toggle, DataTables init, etc.)

### Notable Modules

- **Attendance** (`teacher/qr_attendance.php`, `teacher/generate_qr_ajax.php`): QR code-based attendance marking.
- **Payment** (`parent/payment.php`): Makes payments by selecting subjects; generates receipts with `PYMT-{date}-{id}` receipt numbers. Exports to CSV or PDF (TCPDF library at `tcpdf/tcpdf.php`). ToyyibPay integration is configured in `parent/config.php`.
- **Performance** (`teacher/performance.php`, `teacher/add_performance.php`): Teachers record exam results.
- **Booking** (`parent/book.php`, `student/book.php`): Enrolling students in subjects/courses.
- **Reports** (`admin/attendance_report.php`, `admin/payment_report.php`, `admin/export_attendance.php`, `admin/export_payment.php`): Admin exports.

## Known Issues / Quirks

- **Plaintext passwords**: `admin/login.php` compares passwords as plaintext strings — no hashing.
- **SQL injection risk**: `admin/login.php` uses `mysqli_real_escape_string` but builds the query with string interpolation. Other pages use prepared statements correctly.
- **Duplicated db.php**: Each portal has its own identical `db.php` connecting to the same database. The root-level `/db.php` and `/asset/db.php` also exist.
- **`parent/payment.php`** contains significant dead/experimental code — multiple conflicting query blocks and debug `echo`/`var_dump` calls that were never removed.
- CSS is duplicated inline in nearly every file rather than shared via a stylesheet.
- The `$activePage` variable used for sidebar active-link highlighting is often not defined on the page that includes the sidebar markup, causing PHP notices.

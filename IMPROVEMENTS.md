# ACE Tuition System — Improvement Checklist

A prioritised list of issues and enhancements identified across the codebase.
Issues are grouped by theme and ordered by severity within each group.

---

## Critical — Fix Before Any Production Use

### Unresolved Merge Conflicts
The following files contain raw Git conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`).
PHP will fail to parse them — behaviour is undefined.

| File | What conflicts |
|------|----------------|
| [admin/login.php](admin/login.php) | Plaintext vs hashed password comparison |
| [parent/login.php](parent/login.php) | Same as above |
| [student/login.php](student/login.php) | Same as above |
| [admin/insert.php](admin/insert.php) | Plaintext vs `password_hash()` on insert |
| [parent/process_student.php](parent/process_student.php) | Registration logic |
| [admin/process_password.php](admin/process_password.php) | Password update logic |

**Action:** Resolve every conflict, keeping the `password_hash` / `password_verify` variant throughout.

---

### Plaintext Password Storage
- [admin/login.php](admin/login.php): one conflict branch compares raw password strings with `password='$password'` in SQL.
- [admin/insert.php](admin/insert.php): one conflict branch inserts plaintext into `users`.

**Action:** Ensure every password write uses `password_hash($password, PASSWORD_DEFAULT)` and every login check uses `password_verify()`.

---

### SQL Injection
Direct string concatenation in SQL queries — these are exploitable.

| File | Location | Vulnerable variable |
|------|----------|---------------------|
| [admin/book.php](admin/book.php) | Line ~164 | `$username` from session |
| [admin/dashboard.php](admin/dashboard.php) | Line ~15 | `$tableName` used directly as table name |
| [parent/payment.php](parent/payment.php) | Lines ~273–279, ~326 | `$month`, `$year` concatenated into WHERE clause |
| [admin/bookcourse.php](admin/bookcourse.php) | Multiple | `mysqli_real_escape_string` used instead of prepared statements |

**Action:** Replace every string-interpolated query with a prepared statement (`$conn->prepare()` + `bind_param()`).

---

### Privilege Escalation — Missing Ownership Checks
- [parent/edit_child.php](parent/edit_child.php): fetches child by `$_GET['id']` with no check that the child belongs to the logged-in parent. Any parent can edit any child by changing the URL.
- [parent/payment.php](parent/payment.php) does verify ownership in some paths but inconsistently.

**Action:** After loading a child/payment record, always assert `child.parent_username = $_SESSION['username']`.

---

### Undefined Variable — Crashes at Runtime
| File | Undefined variable | Should be |
|------|-------------------|-----------|
| [admin/delete_user.php](admin/delete_user.php) | `$pdo` | `$conn` |
| [admin/changepassword.php](admin/changepassword.php) | `$parent` | `$user` (set on line ~36) |
| [admin/manageuser.php](admin/manageuser.php) | `$parent` | current row from the users loop |
| [parent/payment.php](parent/payment.php) | `$user` | result of user-lookup query |

---

### CSRF Protection Missing
No form in the entire application generates or validates a CSRF token.
Any logged-in user can be tricked into submitting forged requests.

**Action:** Generate a token on session start, embed it as a hidden field in every form, and validate it on every `$_POST` handler.

---

## High — Fix Within One Week

### XSS — Unescaped Output
Most pages use `htmlspecialchars()` correctly, but gaps exist:

- [parent/payment.php](parent/payment.php): `$user['name']` used in PDF generation without null-check or escaping.
- Any place where DB data is echoed without `htmlspecialchars()`.

**Action:** Audit every `echo` / `?>…<?php` block; wrap dynamic values in `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.

---

### Session Security
- No session regeneration after successful login (`session_regenerate_id(true)`).
- No session timeout — sessions live indefinitely.
- Cookie flags (HttpOnly, SameSite=Strict) are not explicitly set.

**Action:**
```php
// At login success:
session_regenerate_id(true);

// In each db.php or a shared config:
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
```
Add an idle-timeout check at the top of authenticated pages.

---

### Missing Input Validation
Forms that write to the database perform no server-side validation:

- [admin/dashboard.php](admin/dashboard.php): username, name, email, gender posted directly.
- [parent/edit_child.php](parent/edit_child.php): no email format check, no date-range check.
- [admin/edit_user.php](admin/edit_user.php): email field not validated.

**Action:** Validate and sanitise all `$_POST` values before using them. Reject or sanitise unexpected values.

---

### Wrong `bind_param` Type String
[parent/payment.php](parent/payment.php) line ~194 declares `"ssssddssss"` (10 params) but `$month` and `$year` are strings passed where `d` (double) is expected.
This silently inserts wrong data.

**Action:** Match every type character (`s`, `i`, `d`) to the actual PHP type of each bound variable.

---

### Double DB Include
[parent/payment.php](parent/payment.php) lines 14–15:
```php
require_once("db.php");
include("db.php");   // redundant
```
**Action:** Remove the duplicate `include`.

---

### Wrong Form Action
[student/login.php](student/login.php) (and reportedly [teacher/login.php](teacher/login.php)) submits to `process_student.php`.
**Action:** Verify each login form posts to its own portal's login processor.

---

### Overwritten Prepared Statement
[admin/manageuser.php](admin/manageuser.php):
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt = $conn->prepare($query);   // overwrites the line above immediately
```
**Action:** Remove the dead first prepare or merge the logic.

---

## Medium — Fix Within Two Weeks

### Code Duplication in payment.php
[parent/payment.php](parent/payment.php) is the largest single file (~1 000+ lines) and contains:
- Child-fetching logic duplicated at lines ~110–145 and ~431–474.
- Payment insertion logic duplicated at ~193–260 and ~477–561.
- Report-generation logic duplicated at ~264–421 and ~562–908.

**Action:** Extract each repeated block into a named function or a separate include file.

---

### Dead / Debug Code
- [parent/payment.php](parent/payment.php): multiple `echo`/`var_dump` debug calls never removed.
- Lines that are outside any conditional and never execute.

**Action:** Delete all debug output. Track remaining dead branches and remove or document them.

---

### Error Handling on Queries
- [admin/book.php](admin/book.php): calls `mysqli_num_rows($result)` without checking that `$result !== false`.
- [parent/bookcourse.php](parent/bookcourse.php): calls `$result->fetch_assoc()` without checking `$result`.
- [student/payment.php](student/payment.php): no error handling anywhere.

**Action:** After every `query()` / `prepare()` / `execute()`, check for failure and handle it gracefully (log server-side, show a friendly message to the user).

---

### Missing Rate Limiting on Login & Sensitive Endpoints
Brute-force attacks on all login pages are undetected.

**Action:** Track failed login attempts per IP / username (in a DB table or APCu), lock out after N failures, and add a delay or CAPTCHA.

---

### Receipt Number Collision Risk
[parent/payment.php](parent/payment.php):
```php
$receipt_number = 'PYMT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
```
`uniqid()` provides low entropy; concurrent inserts can collide.

**Action:** Use a DB auto-increment sequence or `random_bytes(4)` for the suffix, and add a UNIQUE constraint on `receipt_number`.

---

### Hardcoded Magic Values
- [student/payment.php](student/payment.php): `$fee = 45;` — fee should come from the subjects table.
- [parent/bookcourse.php](parent/bookcourse.php): subject schedules hardcoded in PHP instead of stored in the database.

**Action:** Move all configurable values into the database or a dedicated config file.

---

### Verbose Error Exposure
[parent/payment.php](parent/payment.php):
```php
echo "Payment failed: " . $e->getMessage();
```
Exposes internal DB errors to the user.

**Action:** Log errors server-side (`error_log()`), display a generic message to the user.

---

### N+1 Query Patterns
- [teacher/performance.php](teacher/performance.php): exam results fetched without aggregation — could be one JOIN query.
- [parent/bookcourse.php](parent/bookcourse.php): per-child age logic in PHP loop instead of a SQL expression.

**Action:** Audit all loops that run queries and replace with JOINs or subqueries.

---

## Low — Technical Debt

### Inconsistent Database API
Some files use procedural `mysqli_*` functions; most use OOP `$conn->prepare()`. Mixing styles makes the codebase harder to reason about.

**Action:** Standardise on the OOP MySQLi style throughout.

---

### Duplicated `db.php` Files
`/db.php`, `/admin/db.php`, `/parent/db.php`, `/teacher/db.php`, `/asset/db.php` are identical.

**Action:** Create one `/config/db.php` and `require_once` it with the correct relative path from each portal.

---

### Hardcoded Database Credentials
Every `db.php` contains:
```php
$host = "localhost"; $username = "root"; $password = "";
```

**Action:** Load credentials from environment variables or a `.env` file (excluded from Git via `.gitignore`).

---

### No Audit / Security Logging
There is no record of logins, logouts, payment submissions, user deletions, or admin operations.

**Action:** Add an `audit_log` table and write a row for every security-relevant event.

---

### Missing Database Indexes
Columns used heavily in WHERE/JOIN clauses but likely without indexes:

- `users(username)`
- `children(username)`, `children(parent_username)`
- `subjects(username)`, `subjects(subject_code)`
- `payments(parent_username)`, `payments(child_id)`, `payments(month, year)`
- `student_subjects(student_id)`, `student_subjects(subject_id)`

**Action:** Add indexes via `ALTER TABLE` or update `tuition_db.sql`.

---

### Inline CSS Duplication
Nearly every page contains its own copy of the same Bootstrap-override styles.

**Action:** Extract shared styles into `/assets/css/custom.css` and link it from every page.

---

### Undefined `$activePage` Variable
Pages that include the sidebar markup do not always define `$activePage`, producing PHP notices.

**Action:** Either initialise `$activePage = '';` at the top of every page or use `$activePage ?? ''` in the sidebar include.

---

## Summary Table

| Priority | Count | Theme |
|----------|-------|-------|
| Critical | 6 | Merge conflicts, plaintext passwords, SQL injection, privilege escalation, undefined vars, CSRF |
| High | 7 | XSS, session security, input validation, bind_param types, double include, wrong form action, overwritten stmt |
| Medium | 8 | Code duplication, dead code, error handling, rate limiting, receipt collision, magic values, verbose errors, N+1 queries |
| Low | 6 | API inconsistency, duplicate db.php, hardcoded credentials, no audit log, missing indexes, inline CSS |

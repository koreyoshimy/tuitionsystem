<?php
// attendance_report.php
require_once 'db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Initialize filter variables
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$student_id = $_GET['student_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$time_from = $_GET['time_from'] ?? '';
$time_to = $_GET['time_to'] ?? '';

// Build base query
$query = "SELECT a.*, 
                 s.name as student_name, 
                 s.admission_number,
                 sub.subject_name,
                 t.name as tutor_name,
                 c.class_name,
                 DATE_FORMAT(a.session_start, '%H:%i') as session_start_time,
                 DATE_FORMAT(a.session_end, '%H:%i') as session_end_time
          FROM attendance a
          JOIN students s ON a.student_id = s.id
          JOIN subjects sub ON a.subject_id = sub.id
          LEFT JOIN tutors t ON a.tutor_id = t.id
          LEFT JOIN classes c ON a.class_id = c.id
          WHERE a.attendance_date BETWEEN :date_from AND :date_to";

$params = [
    ':date_from' => $date_from,
    ':date_to' => $date_to
];

// Add student filter if selected
if (!empty($student_id)) {
    $query .= " AND a.student_id = :student_id";
    $params[':student_id'] = $student_id;
}

// Add subject filter if selected
if (!empty($subject_id)) {
    $query .= " AND a.subject_id = :subject_id";
    $params[':subject_id'] = $subject_id;
}

// Add time range filter if selected
if (!empty($time_from) && !empty($time_to)) {
    $query .= " AND TIME(a.session_start) >= :time_from AND TIME(a.session_end) <= :time_to";
    $params[':time_from'] = $time_from;
    $params[':time_to'] = $time_to;
}

$query .= " ORDER BY a.attendance_date DESC, a.session_start ASC";

// Prepare and execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all students for filter dropdown
$students = $pdo->query("SELECT id, name, admission_number FROM students WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects for filter dropdown
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            background-color: #f4f4f4;
        }

        .time-input { max-width: 120px; }
        .status-present { background-color: #d4edda; }
        .status-absent { background-color: #f8d7da; }
        .status-late { background-color: #fff3cd; }
         .toggle {
            cursor: pointer;
        }
        .sidebar {
    position: fixed;
    top: 60px;  /* leave space for navbar */
    height: calc(100% - 60px);  /* adjust height */
    z-index: 1;
    width: 250px;
    background-color: #2b2640;
    color: white;
    padding: 20px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    left: 0;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 22px;
    font-weight: 600;
}

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    margin: 15px 0;
}

.sidebar ul li a {
    text-decoration: none;
    color: white;
    font-size: 16px;
    display: block;
    padding: 12px 18px;
    border-radius: 6px;
    transition: background-color 0.3s, padding-left 0.3s;
}

.sidebar ul li a:hover,
.sidebar ul li a.active {
    background-color: #002244; /* Slightly lighter dark blue for hover effect */
    padding-left: 25px; /* Slightly increase padding for hover effect */
}

/* Expandable Submenu Styling */
.sidebar ul .submenu {
    display: none;
    padding-left: 20px;
}

.sidebar ul .submenu li {
    margin: 5px 0;
}

.sidebar ul .submenu li a {
    font-size: 14px;
}
/* Main Content Styling */
.main-content {
    flex: 1;
    margin-left: 250px; /* leave space for sidebar */
    padding: 80px 40px 40px 40px; /* top padding for navbar */
    background-color: #f0f4f8;
    min-height: 100vh;
    box-sizing: border-box;
}

/* Navbar Styling */
.navbar-custom {
    background-color: #2b2640;
    color: white;
    height: 60px;
    display: flex;
    align-items: center;
    padding: 0 20px;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%; /* full width */
    z-index: 999; /* navbar is on top */
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.navbar-custom h1 {
    font-size: 20px;
    margin: 0;
    color: white;
    font-weight: 600;
}
        .toggle {
            cursor: pointer;
        }
    </style>
    <!-- Font Awesome CSS (Latest version) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="navbar-custom">
    <h1>ACE Tuition Management System</h1>
</div>
    <div class="main-container">
        <h2 class="mb-4">Student Attendance Report</h2>
        <ul>
        <li class="toggle">
            <a href="javascript:void(0);"><i class="fa fa-user"></i>My Profile <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>"> <i class="fa fa-chart-line"></i>Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>"> <i class="fa fa-key"></i> Change Password</a></li>
            </ul></li>
            <li><a href="manageuser.php" class="<?php echo ($activePage === 'manageuser') ? 'active' : ''; ?>"><i class="fa fa-user-edit"></i>Manage User</a></li>
            <li><a href="manageschedule.php" class="<?php echo ($activePage === 'manageschedule') ? 'active' : ''; ?>"><i class="fa fa-calendar"></i>Manage Schedule</a></li>
            <li class="toggle">
            <a href="javascript:void(0);"><i class="fa fa-chart-bar"></i>Report</i></a>
            <ul class="submenu">
                <li><a href="payment_report.php" class="<?php echo ($activePage === 'payment_report') ? 'active' : ''; ?>"><i class="fa fa-credit-card"></i>Payment</a></li>
                <li><a href="attendance_report.php" class="<?php echo ($activePage === 'attendance_report') ? 'active' : ''; ?>"><i class="fa fa-calendar-check"></i> Attendance</a></li>
            </ul></li>     
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
    </ul>
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Filter Options</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="time_from" class="form-label">Time From</label>
                        <input type="time" class="form-control time-input" id="time_from" name="time_from" value="<?= htmlspecialchars($time_from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="time_to" class="form-label">Time To</label>
                        <input type="time" class="form-control time-input" id="time_to" name="time_to" value="<?= htmlspecialchars($time_to) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id">
                            <option value="">All Students</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>" <?= $student_id == $student['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['admission_number']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select class="form-select" id="subject_id" name="subject_id">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= $subject_id == $subject['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="attendance_report.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Report Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Sessions</h6>
                                <h3><?= count($attendance) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Present</h6>
                                <h3><?= count(array_filter($attendance, fn($a) => $a['status'] == 'present')) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Absent</h6>
                                <h3><?= count(array_filter($attendance, fn($a) => $a['status'] == 'absent')) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Late Arrivals</h6>
                                <h3><?= count(array_filter($attendance, fn($a) => $a['status'] == 'late')) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Records Table -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attendance Records</h5>
                    <a href="export_attendance.php?<?= http_build_query($_GET) ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-download"></i> Export to Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Subject</th>
                                <th>Tutor</th>
                                <th>Class</th>
                                <th>Session Time</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendance)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No attendance records found for the selected filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($attendance as $record): ?>
                                    <tr class="status-<?= $record['status'] ?>">
                                        <td><?= htmlspecialchars($record['attendance_date']) ?></td>
                                        <td><?= htmlspecialchars($record['student_name']) ?></td>
                                        <td><?= htmlspecialchars($record['admission_number']) ?></td>
                                        <td><?= htmlspecialchars($record['subject_name']) ?></td>
                                        <td><?= htmlspecialchars($record['tutor_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($record['class_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?= htmlspecialchars($record['session_start_time']) ?> - 
                                            <?= htmlspecialchars($record['session_end_time']) ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = [
                                                    'present' => 'success',
                                                    'absent' => 'danger',
                                                    'late' => 'warning'
                                                ][$record['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= ucfirst(htmlspecialchars($record['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($record['remarks'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr("#date_from", {
            dateFormat: "Y-m-d",
            defaultDate: "<?= $date_from ?>"
        });
        
        flatpickr("#date_to", {
            dateFormat: "Y-m-d",
            defaultDate: "<?= $date_to ?>"
        });
    // Add click event to toggle the submenu
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const submenu = this.querySelector('.submenu');
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
    </script>
</body>
</html>
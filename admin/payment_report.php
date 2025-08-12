<?php
// payment_report.php
require_once 'db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Initialize filter variables
$month = $_GET['month'] ?? date('Y-m');
$status = $_GET['status'] ?? 'all';
$student_id = $_GET['student_id'] ?? '';
$class_id = $_GET['class_id'] ?? '';

// Calculate date range for the selected month
$first_day = date('Y-m-01', strtotime($month));
$last_day = date('Y-m-t', strtotime($month));

// Build base query
$query = "SELECT p.*, 
                 s.name as student_name, 
                 s.admission_number,
                 c.class_name,
                 DATE_FORMAT(p.due_date, '%Y-%m-%d') as due_date_formatted,
                 DATE_FORMAT(p.payment_date, '%Y-%m-%d') as payment_date_formatted
          FROM payments p
          JOIN students s ON p.student_id = s.id
          LEFT JOIN classes c ON p.class_id = c.id
          WHERE p.due_date BETWEEN :first_day AND :last_day";

$params = [
    ':first_day' => $first_day,
    ':last_day' => $last_day
];

// Add status filter
if ($status != 'all') {
    if ($status == 'paid') {
        $query .= " AND p.payment_status = 'paid'";
    } elseif ($status == 'unpaid') {
        $query .= " AND (p.payment_status IS NULL OR p.payment_status = 'unpaid')";
    } elseif ($status == 'partial') {
        $query .= " AND p.payment_status = 'partial'";
    }
}

// Add student filter if selected
if (!empty($student_id)) {
    $query .= " AND p.student_id = :student_id";
    $params[':student_id'] = $student_id;
}

// Add class filter if selected
if (!empty($class_id)) {
    $query .= " AND p.class_id = :class_id";
    $params[':class_id'] = $class_id;
}

$query .= " ORDER BY p.due_date, s.name";

// Prepare and execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all students for filter dropdown
$students = $pdo->query("SELECT id, name, admission_number FROM students WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get all classes for filter dropdown
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$total_paid = 0;
$total_balance = 0;

foreach ($payments as $payment) {
    $total_amount += $payment['amount'];
    if ($payment['payment_status'] == 'paid') {
        $total_paid += $payment['amount_paid'] ?? $payment['amount'];
    } elseif ($payment['payment_status'] == 'partial') {
        $total_paid += $payment['amount_paid'] ?? 0;
    }
}
$total_balance = $total_amount - $total_paid;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .status-paid { background-color: #d4edda; }
        .status-unpaid { background-color: #f8d7da; }
        .status-partial { background-color: #fff3cd; }
        .table-responsive { overflow-x: auto; }
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
    <div class="container-fluid mt-4">
        <h2 class="mb-4">Payment Report</h2>
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
                        <label for="month" class="form-label">Month</label>
                        <input type="month" class="form-control" id="month" name="month" value="<?= htmlspecialchars($month) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Payment Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Payments</option>
                            <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid Only</option>
                            <option value="unpaid" <?= $status == 'unpaid' ? 'selected' : '' ?>>Unpaid Only</option>
                            <option value="partial" <?= $status == 'partial' ? 'selected' : '' ?>>Partial Payments</option>
                        </select>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class</label>
                        <select class="form-select" id="class_id" name="class_id">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="payment_report.php" class="btn btn-outline-secondary">Reset</a>
                        <a href="export_payments.php?<?= http_build_query($_GET) ?>" class="btn btn-success float-end">
                            <i class="bi bi-download"></i> Export to Excel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Payment Summary for <?= date('F Y', strtotime($month)) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Invoices</h6>
                                <h3><?= count($payments) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Amount</h6>
                                <h3><?= number_format($total_amount, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Paid</h6>
                                <h3><?= number_format($total_paid, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Balance</h6>
                                <h3 class="text-warning"><?= number_format($total_balance, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Collection Rate</h6>
                                <h3><?= count($payments) > 0 ? number_format(($total_paid/$total_amount)*100, 2).'%' : '0%' ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Records Table -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Invoice No.</th>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Class</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                                <th>Amount Paid</th>
                                <th>Balance</th>
                                <th>Payment Method</th>
                                <th>Receipt No.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="12" class="text-center">No payment records found for the selected filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <?php
                                        $balance = $payment['amount'] - ($payment['amount_paid'] ?? 0);
                                        $statusClass = [
                                            'paid' => 'paid',
                                            'partial' => 'partial',
                                            'unpaid' => 'unpaid'
                                        ][$payment['payment_status'] ?? 'unpaid'] ?? '';
                                    ?>
                                    <tr class="status-<?= $statusClass ?>">
                                        <td><?= htmlspecialchars($payment['invoice_number']) ?></td>
                                        <td><?= htmlspecialchars($payment['student_name']) ?></td>
                                        <td><?= htmlspecialchars($payment['admission_number']) ?></td>
                                        <td><?= htmlspecialchars($payment['class_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($payment['due_date_formatted']) ?></td>
                                        <td><?= number_format($payment['amount'], 2) ?></td>
                                        <td>
                                            <?php 
                                                $statusBadge = [
                                                    'paid' => 'success',
                                                    'partial' => 'warning',
                                                    'unpaid' => 'danger'
                                                ][$payment['payment_status'] ?? 'unpaid'] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusBadge ?>">
                                                <?= ucfirst($payment['payment_status'] ?? 'unpaid') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($payment['payment_date_formatted'] ?? '') ?></td>
                                        <td><?= isset($payment['amount_paid']) ? number_format($payment['amount_paid'], 2) : '0.00' ?></td>
                                        <td><?= number_format($balance, 2) ?></td>
                                        <td><?= htmlspecialchars($payment['payment_method'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($payment['receipt_number'] ?? '') ?></td>
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
</body>
</html>
<?php
session_start();
include("db.php");

// Check if teacher is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] == 1) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_'.$_GET['month'].'_'.$_GET['year'].'.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, ['Date', 'Class', 'Student', 'Subject', 'Status', 'Method', 'Recorded By']);
    
    // Data rows
    foreach ($attendance_data as $record) {
        fputcsv($output, [
            date('d M Y', strtotime($record['date'])),
            $record['class'],
            $record['student_name'],
            $record['subject'],
            ucfirst($record['status']),
            ucfirst($record['method']),
            $record['recorded_by']
        ]);
    }
    
    fclose($output);
    exit();
}
// Get current month and year
$current_month = date('m');
$current_year = date('Y');

// Get selected month/year from GET parameters
$selected_month = $_GET['month'] ?? $current_month;
$selected_year = $_GET['year'] ?? $current_year;

// Get teacher's classes
$teacher_classes = [];
$stmt = $conn->prepare("SELECT class FROM teacher_classes WHERE teacher_username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $teacher_classes[] = $row['class'];
}

// Get attendance data
$attendance_data = [];
if (!empty($teacher_classes)) {
    $placeholders = implode(',', array_fill(0, count($teacher_classes), '?'));
    $types = str_repeat('s', count($teacher_classes));
    
    $query = "SELECT 
                a.username, 
                a.stuName as student_name, 
                a.class, 
                a.subject, 
                a.date, 
                a.day, 
                a.status,
                a.method,
                a.recorded_by
              FROM attendance a
              WHERE a.class IN ($placeholders)
              AND MONTH(a.date) = ?
              AND YEAR(a.date) = ?
              AND a.recorded_by = ?
              ORDER BY a.date, a.class, a.stuName";
    
    $params = array_merge($teacher_classes, [$selected_month, $selected_year, $_SESSION['username']]);
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types.'iis', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_data = $result->fetch_all(MYSQLI_ASSOC);
}

// Get available years with attendance data
$years = [];
$stmt = $conn->prepare("SELECT DISTINCT YEAR(date) as year 
                       FROM attendance 
                       WHERE recorded_by = ?
                       ORDER BY year DESC");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $years[] = $row['year'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .report-header {
            background-color: #2b2640;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .attendance-table th {
            background-color: #2b2640;
            color: white;
        }
        .present {
            background-color: #d4edda;
        }
        .absent {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <!-- Include your sidebar/navigation here -->
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="report-header">
                <h2>Monthly Attendance Report</h2>
                <form method="get" class="row g-3 mt-2">
                    <div class="col-md-3">
                        <select name="month" class="form-select">
                            <?php foreach (range(1, 12) as $month): ?>
                                <option value="<?= $month ?>" <?= $month == $selected_month ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $month, 1)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="year" class="form-select">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                    <div class="col-md-2">
                        <a href="attendance_report.php?export=1&month=<?= $selected_month ?>&year=<?= $selected_year ?>" 
                           class="btn btn-success">Export to Excel</a>
                    </div>
                </form>
            </div>

            <?php if (!empty($attendance_data)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered attendance-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Class</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_data as $record): ?>
                                <tr class="<?= $record['status'] == 'present' ? 'present' : 'absent' ?>">
                                    <td><?= date('d M Y', strtotime($record['date'])) ?></td>
                                    <td><?= htmlspecialchars($record['class']) ?></td>
                                    <td><?= htmlspecialchars($record['student_name']) ?></td>
                                    <td><?= htmlspecialchars($record['subject']) ?></td>
                                    <td><?= ucfirst($record['status']) ?></td>
                                    <td><?= ucfirst($record['method']) ?></td>
                                    <td><?= htmlspecialchars($record['recorded_by']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Statistics -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Summary Statistics</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $total_records = count($attendance_data);
                        $present_count = count(array_filter($attendance_data, fn($r) => $r['status'] == 'present'));
                        $absent_count = $total_records - $present_count;
                        $attendance_rate = $total_records > 0 ? round(($present_count/$total_records)*100, 2) : 0;
                        ?>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card text-white bg-primary mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Records</h5>
                                        <p class="card-text display-4"><?= $total_records ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-success mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Present</h5>
                                        <p class="card-text display-4"><?= $present_count ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-danger mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Absent</h5>
                                        <p class="card-text display-4"><?= $absent_count ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Attendance Rate</h5>
                                        <p class="card-text display-4"><?= $attendance_rate ?>%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No attendance records found for the selected period.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
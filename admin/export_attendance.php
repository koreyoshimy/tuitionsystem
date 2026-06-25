<?php
// export_attendance.php
require_once 'db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Get filters from URL
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$student_id = $_GET['student_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$time_from = $_GET['time_from'] ?? '';
$time_to = $_GET['time_to'] ?? '';

// Build the same query as in the report page
$query = "SELECT a.attendance_date, 
                 s.name as student_name, 
                 s.admission_number,
                 sub.subject_name,
                 t.name as tutor_name,
                 c.class_name,
                 DATE_FORMAT(a.session_start, '%H:%i') as session_start_time,
                 DATE_FORMAT(a.session_end, '%H:%i') as session_end_time,
                 a.status,
                 a.remarks
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

if (!empty($student_id)) {
    $query .= " AND a.student_id = :student_id";
    $params[':student_id'] = $student_id;
}

if (!empty($subject_id)) {
    $query .= " AND a.subject_id = :subject_id";
    $params[':subject_id'] = $subject_id;
}

if (!empty($time_from) && !empty($time_to)) {
    $query .= " AND TIME(a.session_start) >= :time_from AND TIME(a.session_end) <= :time_to";
    $params[':time_from'] = $time_from;
    $params[':time_to'] = $time_to;
}

$query .= " ORDER BY a.attendance_date DESC, a.session_start ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_report_' . date('Ymd') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'Date', 
    'Student Name', 
    'Admission Number',
    'Subject',
    'Tutor',
    'Class',
    'Session Time',
    'Status',
    'Remarks'
]);

// Write data rows
foreach ($attendance as $record) {
    fputcsv($output, [
        $record['attendance_date'],
        $record['student_name'],
        $record['admission_number'],
        $record['subject_name'],
        $record['tutor_name'] ?? 'N/A',
        $record['class_name'] ?? 'N/A',
        $record['session_start_time'] . ' - ' . $record['session_end_time'],
        ucfirst($record['status']),
        $record['remarks'] ?? ''
    ]);
}

fclose($output);
exit;
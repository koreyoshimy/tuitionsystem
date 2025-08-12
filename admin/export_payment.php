<?php
// export_payments.php
require_once 'db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Get filters from URL
$month = $_GET['month'] ?? date('Y-m');
$status = $_GET['status'] ?? 'all';
$student_id = $_GET['student_id'] ?? '';
$class_id = $_GET['class_id'] ?? '';

// Calculate date range for the selected month
$first_day = date('Y-m-01', strtotime($month));
$last_day = date('Y-m-t', strtotime($month));

// Build the same query as in the report page
$query = "SELECT p.invoice_number,
                 s.name as student_name, 
                 s.admission_number,
                 c.class_name,
                 DATE_FORMAT(p.due_date, '%Y-%m-%d') as due_date,
                 p.amount,
                 p.payment_status,
                 DATE_FORMAT(p.payment_date, '%Y-%m-%d') as payment_date,
                 p.amount_paid,
                 (p.amount - IFNULL(p.amount_paid, 0)) as balance,
                 p.payment_method,
                 p.receipt_number
          FROM payments p
          JOIN students s ON p.student_id = s.id
          LEFT JOIN classes c ON p.class_id = c.id
          WHERE p.due_date BETWEEN :first_day AND :last_day";

$params = [
    ':first_day' => $first_day,
    ':last_day' => $last_day
];

if ($status != 'all') {
    if ($status == 'paid') {
        $query .= " AND p.payment_status = 'paid'";
    } elseif ($status == 'unpaid') {
        $query .= " AND (p.payment_status IS NULL OR p.payment_status = 'unpaid')";
    } elseif ($status == 'partial') {
        $query .= " AND p.payment_status = 'partial'";
    }
}

if (!empty($student_id)) {
    $query .= " AND p.student_id = :student_id";
    $params[':student_id'] = $student_id;
}

if (!empty($class_id)) {
    $query .= " AND p.class_id = :class_id";
    $params[':class_id'] = $class_id;
}

$query .= " ORDER BY p.due_date, s.name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$total_paid = 0;
foreach ($payments as $payment) {
    $total_amount += $payment['amount'];
    $total_paid += $payment['amount_paid'] ?? 0;
}
$total_balance = $total_amount - $total_paid;

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="payment_report_' . date('Ymd') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'Payment Report for ' . date('F Y', strtotime($month)),
    'Generated on ' . date('Y-m-d H:i:s')
]);
fputcsv($output, []); // Empty row
fputcsv($output, [
    'Total Invoices: ' . count($payments),
    'Total Amount: ' . number_format($total_amount, 2),
    'Total Paid: ' . number_format($total_paid, 2),
    'Total Balance: ' . number_format($total_balance, 2),
    'Collection Rate: ' . (count($payments) > 0 ? number_format(($total_paid/$total_amount)*100, 2).'%' : '0%'
]);
fputcsv($output, []); // Empty row

// Write column headers
fputcsv($output, [
    'Invoice No.', 
    'Student Name', 
    'Admission No.',
    'Class',
    'Due Date',
    'Amount',
    'Status',
    'Payment Date',
    'Amount Paid',
    'Balance',
    'Payment Method',
    'Receipt No.'
]);

// Write data rows
foreach ($payments as $payment) {
    fputcsv($output, [
        $payment['invoice_number'],
        $payment['student_name'],
        $payment['admission_number'],
        $payment['class_name'] ?? 'N/A',
        $payment['due_date'],
        number_format($payment['amount'], 2),
        ucfirst($payment['payment_status'] ?? 'unpaid'),
        $payment['payment_date'] ?? '',
        isset($payment['amount_paid']) ? number_format($payment['amount_paid'], 2) : '0.00',
        number_format($payment['balance'], 2),
        $payment['payment_method'] ?? '',
        $payment['receipt_number'] ?? ''
    ]);
}

fclose($output);
exit;
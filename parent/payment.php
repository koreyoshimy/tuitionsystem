<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// At the very top of the file
session_start();

// Check authentication more robustly
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

// Then include your db connection
require_once("db.php"); // Use require_once to ensure it's loaded
include("db.php");

// Check authentication
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Fetch parent details
// Fetch parent details properly
$username = $_SESSION['username'];
$query = "SELECT id, username, name, email, gender FROM users WHERE username = ? AND role = 'parent'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$parent = $result->fetch_assoc();

if (!$parent) {
    // If no parent record found, log out
    session_destroy();
    header("Location: login.php");
    exit();
}
// Handle print request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['print_report'])) {
    // Store data in session
    $_SESSION['print_data'] = [
        'title' => $report_title,
        'data' => $report_data,
        'params' => [
            'month' => $month,
            'year' => $year,
            'type' => $report_type
        ],
        'generated_on' => date('Y-m-d H:i:s'),
        'generated_by' => $_SESSION['username']
    ];
    
    // Clear output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Redirect to print page
    header("Location: print_paymentreport.php");
    exit();
}
// ==============================================
// PAYMENT PROCESSING SECTION
// ==============================================
if (isset($_POST['selected_subjects']) && is_array($_POST['selected_subjects'])) {
    $subjectIds = $_POST['selected_subjects'];
    $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));

    $types = str_repeat('i', count($subjectIds));
    $sql = "SELECT SUM(fee) AS total_fee FROM subjects WHERE id IN ($placeholders)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$subjectIds);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total_fee'] ?? 0;

    echo "Total amount to be paid: RM " . number_format($total, 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    // Validate inputs
    $required = ['child', 'month', 'payment_method', 'subjects'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['payment_error'] = "All fields are required";
            header("Location: payment.php");
            exit();
        }
    }

    // Get payment details
    $child_username = $_POST['child'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $payment_method = $_POST['payment_method'];
    $selectedSubjects = $_POST['subjects'];

    // Verify child belongs to parent
    $stmt = $conn->prepare("SELECT name FROM children WHERE username = ? AND parent_username = ?");
    $stmt->bind_param("ss", $child_username, $_SESSION['username']);
    $stmt->execute();
    $child = $stmt->get_result()->fetch_assoc();

    if (!$child) {
        $_SESSION['payment_error'] = "Invalid student selection";
        header("Location: payment.php");
        exit();
    }
// Get children for payment form
// Get children for the current parent
$children = [];
$query = "SELECT c.id, c.username, c.name 
          FROM children c
          JOIN parent_child pc ON c.id = pc.child_id
          WHERE pc.parent_username = ?";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $children = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($children)) {
        $_SESSION['payment_error'] = "No children registered under your account";
    }
} else {
    die("Database error: " . $conn->error);
}
// Get all available subjects
$subjects = [];
$subjectQuery = "SELECT subject_id, subject_name, fee FROM subjects";
if ($stmt = $conn->prepare($subjectQuery)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
} else {
    die("Error fetching subjects: " . $conn->error);
}
    // Calculate total amount
    $total = 0;
    $subjectDetails = [];
    
    foreach ($selectedSubjects as $subject_id) {
        $stmt = $conn->prepare("SELECT DISTINCT s.subject_name, 
                              COALESCE(mf.additional_fee, 0) as adjustment,
                              sf.base_fee,
                              (sf.base_fee + COALESCE(mf.additional_fee, 0)) as total_fee
                              FROM subjects s
                              JOIN subject_fees sf ON s.subject_id = sf.subject_id
                              LEFT JOIN monthly_fees mf ON s.subject_id = mf.subject_id
                                  AND mf.month = ? AND mf.year = ?
                              WHERE s.subject_id = ?");
        $stmt->bind_param("ssi", $month, $year, $subject_id);
        $stmt->execute();
        $subject = $stmt->get_result()->fetch_assoc();
        
        if (!$subject) {
            $_SESSION['payment_error'] = "Invalid subject selection";
            header("Location: payment.php");
            exit();
        }
        
        $total += $subject['total_fee'];
        $subjectDetails[] = [
            'id' => $subject_id,
            'name' => $subject['subject_name'],
            'base' => $subject['base_fee'],
            'adjustment' => $subject['adjustment'],
            'total' => $subject['total_fee']
        ];
    }
$paid_amount = $total_amount;
    // Generate receipt number
    $receipt_number = 'PYMT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // Start database transaction
    $conn->begin_transaction();


    
    try {
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (`receipt_number`, `parent_username`, `child_id`, `child_name`, `total_amount`, `paid_amount`, `month`, `year`, `recorded_by`, `notes`)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
   // Handle the case where paid_amount is 499 (null)
     $paid_amount = $total_amount;
        $stmt->bind_param("ssssddssss", 
            $receipt_number,
            $_SESSION['username'],
            $child_username,
            $child['name'],
            $total,
            $total,
            $payment_method,
            $month,
            $year,
            $_SESSION['username']
        );
        $stmt->execute();
        $payment_id = $conn->insert_id;
        
        // Insert payment items
        foreach ($subjectDetails as $subject) {
            $stmt = $conn->prepare("INSERT INTO payment_items (
                payment_id, subject_id, subject_name, 
                base_fee, monthly_adjustment, final_fee
            ) VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("iisddd", 
                $payment_id,
                $subject['id'],
                $subject['name'],
                $subject['base'],
                $subject['adjustment'],
                $subject['total']
            );
            $stmt->execute();
    }
        
        // Record in payment history
        $stmt = $conn->prepare("INSERT INTO payment_history (
            payment_id, status_changed_to, changed_by, change_note
        ) VALUES (?, 'completed', ?, 'Initial payment recorded')");
        $stmt->bind_param("is", $payment_id, $_SESSION['username']);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Store for receipt page
        $_SESSION['payment_report_data'] = [
            'receipt_number' => $receipt_number,
            'amount' => $total,
            'month' => $month,
            'year' => $year,
            'subjects' => array_column($subjectDetails, 'name'),
            'payment_date' => date('Y-m-d H:i:s'),
            'payment_method' => $payment_method,
            'child_name' => $child['name'],
            'is_preview' => false
        ];
        
        // Redirect to receipt page
        header("Location: payment_receipt.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['payment_error'] = "Payment failed: " . $e->getMessage();
        header("Location: payment.php");
        exit();
    }
}

// ==============================================
// REPORT GENERATION SECTION
// ==============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $format = $_POST['format'];
    
    // Generate the report based on parameters
    $where = "WHERE 1=1";
    
    if ($month != 'all') {
        $where .= " AND month = '$month'";
    }
    
    if ($year != 'all') {
        $where .= " AND year = $year";
    }
    
    switch ($report_type) {
        case 'summary':
            $query = "SELECT 
                      month, year,
                      COUNT(*) as payment_count,
                      SUM(total_amount) as total_received,
                      AVG(total_amount) as average_payment
                      FROM payments
                      $where
                      GROUP BY month, year
                      ORDER BY year, FIELD(month, 
                          'January','February','March','April','May','June',
                          'July','August','September','October','November','December')";
            $filename = "payment-summary-$year-$month";
            $report_title = "Payment Summary Report";
            break;
            
        case 'detailed':
            $query = "SELECT 
                      p.receipt_number, p.student_name, 
                      p.month, p.year, p.payment_date,
                      p.total_amount, p.payment_method, p.payment_status,
                      GROUP_CONCAT(pi.subject_name SEPARATOR ', ') as subjects
                      FROM payments p
                      LEFT JOIN payment_items pi ON p.payment_id = pi.payment_id
                      $where
                      GROUP BY p.payment_id
                      ORDER BY p.payment_date DESC";
            $filename = "payment-details-$year-$month";
            $report_title = "Detailed Payment Report";
            break;
            
        case 'unpaid':
            $query = "SELECT 
                      s.username, s.name, s.class,
                      GROUP_CONCAT(sub.subject_name SEPARATOR ', ') as unpaid_subjects
                      FROM children s
                      JOIN student_subjects ss ON s.username = ss.student_username
                      JOIN subjects sub ON ss.subject_id = sub.subject_id
                      LEFT JOIN (
                          SELECT student_username, subject_id 
                          FROM payment_items pi
                          JOIN payments p ON pi.payment_id = p.payment_id
                          WHERE p.payment_status = 'completed'
                          AND p.month = '$month' AND p.year = $year
                      ) paid ON s.username = paid.student_username AND ss.subject_id = paid.subject_id
                      WHERE paid.student_username IS NULL
                      GROUP BY s.username";
            $filename = "unpaid-students-$year-$month";
            $report_title = "Unpaid Students Report";
            break;
    }
    
    $result = $conn->query($query);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    
    $report_data = [];
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }
    
    // Handle print request
    if (isset($_POST['print_report'])) {
        $_SESSION['report_data'] = [
            'title' => $report_title,
            'data' => $report_data,
            'params' => [
                'month' => $month,
                'year' => $year,
                'type' => $report_type
            ]
        ];
        header("Location: print_paymentreport.php");
        exit();
    }
    
    // Export in requested format
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        if (!empty($report_data)) {
            fputcsv($output, array_keys($report_data[0]));
        }
        
        // Data rows
        foreach ($report_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    } 
    elseif ($format === 'pdf') {
        // Verify TCPDF is installed
        if (!file_exists('tcpdf/tcpdf.php')) {
            die("TCPDF library not found. Please install it first.");
        }
        
        require_once('tcpdf/tcpdf.php');
        
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
        $html = '<h1>'.$report_title.'</h1>';
        $html .= '<p><strong>Period:</strong> '.($month != 'all' ? $month.' ' : '').($year != 'all' ? $year : 'All Years').'</p>';
        $html .= '<table border="1" cellpadding="4">';
        
        // Header row
        if (!empty($report_data)) {
            $html .= '<tr>';
            foreach (array_keys($report_data[0]) as $header) {
                $html .= '<th><strong>'.htmlspecialchars($header).'</strong></th>';
            }
            $html .= '</tr>';
        }
        
        // Data rows
        foreach ($report_data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>'.htmlspecialchars($value).'</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '<p style="text-align:right;font-style:italic">Generated on '.date('Y-m-d H:i:s').'</p>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filename . '.pdf', 'D');
        exit();
    }
}

// Get available years and months for reports
$year = (new DateTime())->format('Y');
$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Get children for payment form
// Get children for the current parent
// Get children for the current parent (excluding admin users)
// Fetch children for this parent
// Fetch children for this parent
$children = [];
$childrenQuery = "SELECT c.id, c.username, c.name, c.gender, c.school, c.age 
                 FROM children c
                 JOIN parent_child pc ON c.id = pc.child_id
                 WHERE pc.parent_username = ?";

$stmt = $conn->prepare($childrenQuery);
if ($stmt) {
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $childrenResult = $stmt->get_result();
    $children = $childrenResult->fetch_all(MYSQLI_ASSOC);
    
    if (empty($children)) {
        $_SESSION['error'] = "No children registered under your account";
    }
    $stmt->close();
    if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
} else {
    die("Database error: " . $conn->error);
}
$children = [];
$childrenQuery = "
    SELECT c.id AS child_id, c.name AS child_name
    FROM children c
    JOIN parent_child pc ON c.username = pc.child_id
    WHERE pc.parent_username = ?
";
$stmt = $conn->prepare($childrenQuery);
if ($stmt) {
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $children = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Query failed: " . $conn->error);
}


// Now start a new statement for the payment insert
$recorded_by = $_SESSION['username'] ?? 'SYSTEM';
$notes = 'Payment completed via QR code';
$sql = "SELECT c.id AS child_id, c.name AS child_name";
// Loop and insert for each child
foreach ($children as $child) {
    echo "Child ID: " . $child['child_id'] . "<br>";
    echo "Child Name: " . $child['child_name'] . "<br>";
    echo "<pre>"; print_r($children); echo "</pre>";
var_dump($child);
    // If child_id or child_name is missing, skip
    if (empty($child['child_id']) || empty($child['child_name'])) {
        echo "Skipping child due to missing data.<br>";
        continue;
    }
}
$child_id = 2;
$child_name = 'John Doe';
$insertQuery = "INSERT INTO payments (receipt_number, parent_username, child_id, child_name, total_amount, paid_amount, month,recorded_by, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertQuery);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$total_amount = 45;
// Get the child username from the form
$child_username = $_POST['child'];

// 1. Check if the child exists in the database
$checkChild = $conn->prepare("SELECT username, name FROM children WHERE username = ?");
$checkChild->bind_param("s", $child_username);
$checkChild->execute();
$childResult = $checkChild->get_result();

if ($childResult->num_rows === 0) {
    $_SESSION['payment_error'] = "Error: The selected child does not exist!";
    header("Location: payment.php");
    exit();
}

// Get the child's details
$childData = $childResult->fetch_assoc();
$child_name = $childData['name']; // Now safely available
// s=string, d=decimal
$stmt->bind_param("ssssddsss",  // Changed to 9 parameters
    $receipt_number,
    $_SESSION['username'],
    $child_id,
    $child_name,
    $total_amount,
    $paid_amount,
    $month,
    $recorded_by,
    $notes
);
$paid_amount = $total_amount;
$month = date('F'); // e.g., "July"
$stmt->execute();
$stmt->close();
    
    if ($stmt->execute()) {
        $payment_id = $conn->insert_id;
        
        // Insert payment items (if you have this table)
        foreach ($selectedSubjects as $subject_id) {
            $item_stmt = $conn->prepare("INSERT INTO payment_items 
                (payment_id, subject_id, fee_amount) 
                VALUES (?, ?, ?)");
            $item_stmt->bind_param("iid", $payment_id, $subject_id, 45.00);
            $item_stmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'receipt_number' => $receipt_number,
            'amount' => $total_amount
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
    }
    exit();
    $report_data = [];
$report_title = "";
$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Get available years for reports
$years = $conn->query("SELECT DISTINCT year FROM payments ORDER BY year DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $format = $_POST['format'];
    
    // Generate the report based on parameters
    $where = "WHERE 1=1";
    
    if ($month != 'all') {
        $where .= " AND p.month = '$month'";
    }
    
    if ($year != 'all') {
        $where .= " AND p.year = $year";
    }
    
    switch ($report_type) {
        case 'summary':
            $query = "SELECT 
                      p.month, p.year,
                      COUNT(*) as payment_count,
                      SUM(p.total_amount) as total_received,
                      AVG(p.total_amount) as average_payment,
                      COUNT(DISTINCT p.child_id) as students_count
                      FROM payments p
                      $where
                      GROUP BY p.month, p.year
                      ORDER BY p.year, FIELD(p.month, 
                          'January','February','March','April','May','June',
                          'July','August','September','October','November','December')";
            $filename = "payment-summary-$year-$month";
            $report_title = "Payment Summary Report";
            break;
            
        case 'detailed':
            $query = "SELECT 
                      p.receipt_number, 
                      p.parent_username,
                      p.child_name as student_name, 
                      p.month, 
                      p.year, 
                      p.payment_date,
                      p.total_amount, 
                      p.payment_method, 
                      p.payment_status,
                      GROUP_CONCAT(CONCAT(pi.subject_name, ' (RM', pi.final_fee, ')') SEPARATOR ', ') as subjects,
                      p.recorded_by,
                      p.notes
                      FROM payments p
                      LEFT JOIN payment_items pi ON p.payment_id = pi.payment_id
                      $where
                      GROUP BY p.payment_id
                      ORDER BY p.payment_date DESC";
            $filename = "payment-details-$year-$month";
            $report_title = "Detailed Payment Report";
            break;
            
        case 'unpaid':
            $query = "SELECT 
                      c.username, c.name, c.class,
                      GROUP_CONCAT(s.subject_name SEPARATOR ', ') as unpaid_subjects,
                      SUM(s.fee) as total_unpaid
                      FROM children c
                      JOIN student_subjects ss ON c.username = ss.student_username
                      JOIN subjects s ON ss.subject_id = s.subject_id
                      LEFT JOIN (
                          SELECT child_id, subject_id 
                          FROM payment_items pi
                          JOIN payments p ON pi.payment_id = p.payment_id
                          WHERE p.payment_status = 'completed'
                          AND p.month = '$month' AND p.year = $year
                      ) paid ON c.username = paid.child_id AND ss.subject_id = paid.subject_id
                      WHERE paid.child_id IS NULL
                      GROUP BY c.username";
            $filename = "unpaid-students-$year-$month";
            $report_title = "Unpaid Students Report";
            break;
            
        case 'receipt':
            if (!isset($_POST['receipt_number'])) {
                die("Receipt number required");
            }
            $receipt_number = $_POST['receipt_number'];
            
            // Payment details
            $query = "SELECT * FROM payments WHERE receipt_number = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $receipt_number);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();
            
            if (!$payment) {
                die("Payment not found");
            }
            
            // Payment items
            $query = "SELECT * FROM payment_items WHERE payment_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $payment['payment_id']);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Payment history
            $query = "SELECT * FROM payment_history WHERE payment_id = ? ORDER BY change_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $payment['payment_id']);
            $stmt->execute();
            $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $report_data = [
                'payment' => $payment,
                'items' => $items,
                'history' => $history
            ];
            
            $filename = "receipt-{$payment['receipt_number']}";
            $report_title = "Payment Receipt: {$payment['receipt_number']}";
            break;
    }
    
    // Execute query for non-receipt reports
    if ($report_type !== 'receipt') {
        $result = $conn->query($query);
        if (!$result) {
            die("Query failed: " . $conn->error);
        }
        
        $report_data = [];
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    }
    
    // Handle print request
    if (isset($_POST['print_report'])) {
        $_SESSION['report_data'] = [
            'title' => $report_title,
            'data' => $report_data,
            'params' => [
                'month' => $month,
                'year' => $year,
                'type' => $report_type
            ],
            'generated_by' => $user['name'],
            'generated_on' => date('Y-m-d H:i:s')
        ];
        header("Location: print_paymentreport.php");
        exit();
    }
    
    // Export in requested format
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // For receipt report, flatten the structure
        if ($report_type === 'receipt') {
            $payment = $report_data['payment'];
            $items = $report_data['items'];
            
            // Write payment header
            fputcsv($output, ['Receipt Number', $payment['receipt_number']]);
            fputcsv($output, ['Date', $payment['payment_date']]);
            fputcsv($output, ['Parent', $payment['parent_username']]);
            fputcsv($output, ['Student', $payment['child_name']]);
            fputcsv($output, ['Total Amount', $payment['total_amount']]);
            fputcsv($output, ['Payment Method', $payment['payment_method']]);
            fputcsv($output, ['Status', $payment['payment_status']]);
            fputcsv($output, ['Notes', $payment['notes']]);
            fputcsv($output, []);
            
            // Write items header
            fputcsv($output, ['Subject', 'Base Fee', 'Adjustment', 'Final Fee']);
            
            // Write items
            foreach ($items as $item) {
                fputcsv($output, [
                    $item['subject_name'],
                    $item['base_fee'],
                    $item['monthly_adjustment'],
                    $item['final_fee']
                ]);
            }
            
            fputcsv($output, []);
            fputcsv($output, ['Total', '', '', $payment['total_amount']]);
        } 
        else {
            // Header row for other reports
            if (!empty($report_data)) {
                fputcsv($output, array_keys($report_data[0]));
            }
            
            // Data rows
            foreach ($report_data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit();
    } 
    elseif ($format === 'pdf') {
        require_once('tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('ACE Tuition System');
        $pdf->SetTitle($report_title);
        
        // Set default header data
        $pdf->SetHeaderData('', 0, $report_title, "Generated on: " . date('Y-m-d H:i:s') . "\nGenerated by: " . $user['name']);
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Add a page
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        
        // Report header
        $html = '<h2>'.$report_title.'</h2>';
        $html .= '<p><strong>Period:</strong> '.($month != 'all' ? $month.' ' : '').($year != 'all' ? $year : 'All Years').'</p>';
        
        // Generate content based on report type
        if ($report_type === 'receipt') {
            $payment = $report_data['payment'];
            $items = $report_data['items'];
            $history = $report_data['history'];
            
            // Payment details
            $html .= '<div style="margin-bottom:20px;">';
            $html .= '<h3>Payment Details</h3>';
            $html .= '<table border="0" cellpadding="4">';
            $html .= '<tr><td width="30%"><strong>Receipt Number:</strong></td><td>'.$payment['receipt_number'].'</td></tr>';
            $html .= '<tr><td><strong>Date:</strong></td><td>'.$payment['payment_date'].'</td></tr>';
            $html .= '<tr><td><strong>Parent:</strong></td><td>'.$payment['parent_username'].'</td></tr>';
            $html .= '<tr><td><strong>Student:</strong></td><td>'.$payment['child_name'].'</td></tr>';
            $html .= '<tr><td><strong>Total Amount:</strong></td><td>RM '.number_format($payment['total_amount'], 2).'</td></tr>';
            $html .= '<tr><td><strong>Payment Method:</strong></td><td>'.$payment['payment_method'].'</td></tr>';
            $html .= '<tr><td><strong>Status:</strong></td><td>'.$payment['payment_status'].'</td></tr>';
            $html .= '<tr><td><strong>Notes:</strong></td><td>'.$payment['notes'].'</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
            
            // Payment items
            $html .= '<div style="margin-bottom:20px;">';
            $html .= '<h3>Payment Items</h3>';
            $html .= '<table border="1" cellpadding="4">';
            $html .= '<tr style="background-color:#f2f2f2;">';
            $html .= '<th>Subject</th><th>Base Fee</th><th>Adjustment</th><th>Final Fee</th>';
            $html .= '</tr>';
            
            foreach ($items as $item) {
                $html .= '<tr>';
                $html .= '<td>'.$item['subject_name'].'</td>';
                $html .= '<td style="text-align:right">RM '.number_format($item['base_fee'], 2).'</td>';
                $html .= '<td style="text-align:right">RM '.number_format($item['monthly_adjustment'], 2).'</td>';
                $html .= '<td style="text-align:right">RM '.number_format($item['final_fee'], 2).'</td>';
                $html .= '</tr>';
            }
            
            $html .= '<tr style="font-weight:bold;">';
            $html .= '<td colspan="3" style="text-align:right">Total:</td>';
            $html .= '<td style="text-align:right">RM '.number_format($payment['total_amount'], 2).'</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';
            
            // Payment history
            if (!empty($history)) {
                $html .= '<div style="margin-bottom:20px;">';
                $html .= '<h3>Payment History</h3>';
                $html .= '<table border="1" cellpadding="4">';
                $html .= '<tr style="background-color:#f2f2f2;">';
                $html .= '<th>Date</th><th>Status</th><th>Changed By</th><th>Notes</th>';
                $html .= '</tr>';
                
                foreach ($history as $entry) {
                    $html .= '<tr>';
                    $html .= '<td>'.$entry['change_date'].'</td>';
                    $html .= '<td>'.$entry['status_changed_to'].'</td>';
                    $html .= '<td>'.$entry['changed_by'].'</td>';
                    $html .= '<td>'.$entry['change_note'].'</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</table>';
                $html .= '</div>';
            }
        } 
        else {
            // For other report types
            if (!empty($report_data)) {
                $html .= '<table border="1" cellpadding="4">';
                
                // Header row
                $html .= '<tr style="background-color:#f2f2f2;">';
                foreach (array_keys($report_data[0]) as $header) {
                    $html .= '<th><strong>'.htmlspecialchars($header).'</strong></th>';
                }
                $html .= '</tr>';
                
                // Data rows
                foreach ($report_data as $row) {
                    $html .= '<tr>';
                    foreach ($row as $value) {
                        $html .= '<td>'.htmlspecialchars($value).'</td>';
                    }
                    $html .= '</tr>';
                }
                
                $html .= '</table>';
            } else {
                $html .= '<p>No data found for the selected criteria.</p>';
            }
        }
        
        $html .= '<p style="text-align:right;font-style:italic">Generated on '.date('Y-m-d H:i:s').' by '.$user['name'].'</p>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filename . '.pdf', 'D');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Your existing CSS styles here */
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

/* Sidebar icons */
.sidebar ul li a i {
    font-size: 16px;
    width: 20px; /* fixed width for alignment */
    margin-right: 10px;
    text-align: center;
}

/* Submenu icons */
.sidebar ul .submenu li a i {
    font-size: 14px;
    width: 18px;
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
         @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        .tab-content {
            padding: 20px;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
     <div class="navbar-custom d-flex justify-content-between">
    <h1>ACE Tuition Management System</h1>
    <div class="d-flex align-items-center gap-3 text-white">
        <div class="text-end">
            <div><?php echo htmlspecialchars($name); ?></div> <!-- Use $username from session -->
            <div style="font-size: 12px;">Parent</div> <!-- Hardcode role since we know it's parent -->
        </div>
        <a href="logout.php" class="text-white" title="Logout">
            <i class="fas fa-sign-out-alt fa-lg"></i>
        </a>
    </div>
</div>
   <div class="sidebar">
        <h2>Welcome, <?php echo htmlspecialchars($parent['name']); ?>!</h2>
        <ul>
        <li class="toggle">
            <a href="javascript:void(0);"><i class="fa fa-user"></i>My Profile <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>"><i class="fa fa-chart-line"></i>Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>"><i class="fa fa-key"></i>Change Password</a></li>
            </ul>      
        <li><a href="book.php" class="<?php echo ($activePage === 'book') ? 'active' : ''; ?>"><i class="fas fa-book"></i> Subject</a></li>     
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Attendance</a></li>
        <li><a href="payment.php" class="<?php echo ($activePage === 'payment') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payment</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>

    </div>

    <div class="main-content">
        <ul class="nav nav-tabs" id="paymentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="make-payment-tab" data-bs-toggle="tab" data-bs-target="#make-payment" type="button" role="tab">Make Payment</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">Payment Reports</button>
            </li>
        </ul>

        <div class="tab-content" id="paymentTabsContent">
            <!-- Make Payment Tab -->
            <div class="tab-pane fade show active" id="make-payment" role="tabpanel">
                <h2>Make Payment</h2>
                <?php if (isset($_SESSION['payment_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['payment_error']; unset($_SESSION['payment_error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
            // Change <form method="post" action="" id="paymentForm">
// To this (empty action submits to same page)
    <!-- Student Selection -->
    <div class="mb-3">
        <label class="form-label">Select Student</label>
        <select name="child" class="form-select" required>
            <option value="">-- Select Child --</option>
            <?php foreach ($children as $child): ?>
                <option value="<?= htmlspecialchars($child['id']) ?>">
                    <?= htmlspecialchars($child['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Month Selection -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">Month</label>
            <select name="month" class="form-select" required>
                <option value="">Select Month</option>
                <?php foreach ($months as $m): ?>
                    <option value="<?= $m ?>"><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
 <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">Year</label>
            <select name="year" class="form-select" required>
                <option value="">Select Year</option>
                <?php foreach ($year as $m): ?>
                    <option value="<?= $m ?>"><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <!-- Payment Method -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
                <option value="">Select Method</option>
                <option value="bank_transfer">Duitnow QR</option>
            </select>
        </div>
    </div>

    <!-- Subjects Table -->
      <div class="row mb-3">
    <div class="col-12">
        <label class="form-label">Subjects</label>
        <div class="subject-list">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="50px">No</th>
                        <th>Child Name</th>
                        <th>Subject Name</th>
                        <th>Subject Code</th>
                        <th>Time</th>
                        <p><strong>Total Selected Subjects:</strong> <span id="selectedCount">0</span></p>
                        <th>Fee (RM)</th>
                    </tr>
                </thead>
                <tbody id="subjectList">
                    <!-- Subjects will be loaded via AJAX when student, month and year are selected -->
                    <?php
                    // Example PHP code to fetch subjects from database
                    // SQL query to fetch subjects data
                   $parent_username = $_SESSION['username'] ?? ''; // Adjust based on your session variable
                    
                    // Query to get bookable subjects for payment
                    $paymentQuery = "SELECT 
                    DISTINCT s.id as subject_id,
                    CASE 
                        WHEN LOWER(s.subject_name) = 'math' THEN 'Mathematics'
                        ELSE s.subject_name 
                    END as subject_name,
                    s.subject_code,
                    s.time,
                    s.fee,
                    c.name as child_name
                FROM subjects s
                JOIN children c ON s.username = c.id
                JOIN parent_child pc ON c.id = pc.child_id
                WHERE pc.parent_username = ?
                AND s.id NOT IN (
                    SELECT pi.subject_id 
                    FROM payment_items pi
                    JOIN payments p ON pi.payment_id = p.payment_id
                    WHERE p.parent_username = ? 
                    AND p.payment_status = 'completed'
                    AND p.month = ? 
                    AND p.year = ?
                )
                ORDER BY c.name, s.subject_name";
                    
                    $stmt = $conn->prepare($paymentQuery);
                    $stmt->bind_param("ss", $parent_username, $parent_username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $subjects = $result->fetch_all(MYSQLI_ASSOC);
                    
                    if (!empty($subjects)) {
                        foreach($subjects as $subject) {
                            echo '<tr>
                                <td><input type="checkbox" name="selected_subjects[]" value="'.$subject['subject_id'].'" class="subject-checkbox"></td>
                                <td>'.htmlspecialchars($subject['child_name'] ?? '').'</td>
                                <td>'.htmlspecialchars($subject['subject_name'] ?? '').'</td>
                                <td>'.htmlspecialchars($subject['subject_code'] ?? '').'</td>
                                <td>'.htmlspecialchars($subject['time'] ?? '').'</td>
                                <td>'.number_format($subject['fee'] ?? 0, 2).'</td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">No subjects available for payment</td></tr>';
                    }
                    
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>

    <!-- Additional Fields -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Additional Notes</label>
            <textarea name="payment_notes" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Payment Reference (Optional)</label>
            <input type="text" name="payment_reference" class="form-control">
        </div>
    </div>

    <!-- Payment Type -->
    <div class="payment-options mb-3">
        <label class="form-label">Payment Type</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_type" id="typeStandard" value="standard" checked>
            <label class="form-check-label" for="typeStandard">Standard Payment</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_type" id="typeInstallment" value="installment">
            <label class="form-check-label" for="typeInstallment">Installment Plan</label>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="payment-summary mb-4 p-3 bg-light rounded">
        <!-- Summary content -->
    </div>

    <!-- Submit Button -->
    <div class="d-grid gap-2">
        <button type="submit" name="make_payment" class="btn btn-primary btn-lg">
            <i class="fas fa-credit-card"></i> Confirm Payment
        </button>
    </div>
</form>

<div class="payment-summary mb-4 p-3 bg-light rounded">
                  <div class="tab-pane fade" id="reports" role="tabpanel">
                <h2>Payment Reports</h2>
                <!-- Report Form -->
                <div class="card report-card mb-4 no-print">
                    <div class="card-body">
                        <form method="post" id="reportForm">
                            <input type="hidden" name="generate_report" value="1">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Report Type</label>
                                    <select name="report_type" class="form-select" id="reportType" required>
                                        <option value="summary">Summary Report</option>
                                        <option value="detailed">Detailed Payments</option>
                                        <option value="unpaid">Unpaid Students</option>
                                        <option value="receipt">Payment Receipt</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3" id="monthField">
                                    <label class="form-label">Month</label>
                                    <select name="month" class="form-select">
                                        <option value="all">All Months</option>
                                        <?php foreach ($months as $m): ?>
                                            <option value="<?= $m ?>" <?= (isset($month) && $month == $m) ? 'selected' : '' ?>>
                                                <?= $m ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3" id="yearField">
                                    <label class="form-label">Year</label>
                                    <select name="year" class="form-select">
                                        <option value="all">All Years</option>
                                        <?php 
                                        $years->data_seek(0); // Reset pointer
                                        while ($y = $years->fetch_assoc()): ?>
                                            <option value="<?= $y['year'] ?>" <?= (isset($year) && $year == $y['year']) ? 'selected' : '' ?>>
                                                <?= $y['year'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Format</label>
                                    <select name="format" class="form-select">
                                        <option value="csv">CSV</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Receipt number field (hidden by default) -->
                            <div class="row mb-3" id="receiptField" style="display:none;">
                                <div class="col-md-6">
                                    <label class="form-label">Receipt Number</label>
                                    <input type="text" name="receipt_number" class="form-control" placeholder="Enter receipt number">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-export me-2"></i>Generate Report
                                </button>
                                <button type="submit" name="print_report" class="btn btn-secondary">
                                    <i class="fas fa-print me-2"></i>Print Preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Results -->
                <?php if (!empty($report_data)): ?>
                    <?php if (isset($_POST['report_type']) && $_POST['report_type'] === 'receipt'): ?>
                        <!-- Receipt View -->
                        <div class="card report-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4><?= $report_title ?></h4>
                                <div class="report-actions no-print">
                                    <button onclick="window.print()" class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <span class="text-muted">
                                        Generated on <?= date('Y-m-d H:i:s') ?> by <?= $user['name'] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="receipt-header">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h3>ACE Tuition Center</h3>
                                            <p>123 Education Street<br>Knowledge City, 50200</p>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <h3>Payment Receipt</h3>
                                            <p><strong>Receipt #:</strong> <?= $report_data['payment']['receipt_number'] ?><br>
                                            <strong>Date:</strong> <?= $report_data['payment']['payment_date'] ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5>Parent Information</h5>
                                        <p><strong>Name:</strong> <?= $report_data['payment']['parent_username'] ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Student Information</h5>
                                        <p><strong>Name:</strong> <?= $report_data['payment']['child_name'] ?></p>
                                    </div>
                                </div>
                                
                                <h5 class="mb-3">Payment Items</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered receipt-table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th class="text-end">Base Fee</th>
                                                <th class="text-end">Adjustment</th>
                                                <th class="text-end">Final Fee</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data['items'] as $item): ?>
                                            <tr>
                                                <td><?= $item['subject_name'] ?></td>
                                                <td class="text-end">RM <?= number_format($item['base_fee'], 2) ?></td>
                                                <td class="text-end">RM <?= number_format($item['monthly_adjustment'], 2) ?></td>
                                                <td class="text-end">RM <?= number_format($item['final_fee'], 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total Amount:</th>
                                                <th class="text-end">RM <?= number_format($report_data['payment']['total_amount'], 2) ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h5>Payment Details</h5>
                                        <p><strong>Payment Method:</strong> <?= $report_data['payment']['payment_method'] ?><br>
                                        <strong>Status:</strong> <?= $report_data['payment']['payment_status'] ?><br>
                                        <strong>Recorded By:</strong> <?= $report_data['payment']['recorded_by'] ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Notes</h5>
                                        <p><?= $report_data['payment']['notes'] ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($report_data['history'])): ?>
                                <div class="mt-4">
                                    <h5>Payment History</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Changed By</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($report_data['history'] as $history): ?>
                                                <tr>
                                                    <td><?= $history['change_date'] ?></td>
                                                    <td><?= $history['status_changed_to'] ?></td>
                                                    <td><?= $history['changed_by'] ?></td>
                                                    <td><?= $history['change_note'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-4 text-center no-print">
                                    <p>Thank you for your payment!</p>
                                    <p class="text-muted">This is a computer generated receipt and does not require a signature.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Standard Report View -->
                        <div class="card report-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4><?= $report_title ?></h4>
                                <div class="report-actions no-print">
                                    <button onclick="window.print()" class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <span class="text-muted">
                                        Generated on <?= date('Y-m-d H:i:s') ?> by <?= $user['name'] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <?php foreach (array_keys($report_data[0]) as $header): ?>
                                                <th><?= htmlspecialchars($header) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                <td><?= htmlspecialchars($value) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-muted no-print">
                                    Showing <?= count($report_data) ?> records | 
                                    Period: <?= (isset($month) && $month != 'all' ? $month.' ' : '').(isset($year) && $year != 'all' ? $year : 'All Years') ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php elseif (isset($_POST['generate_report'])): ?>
                    <div class="alert alert-info">
                        No data found for the selected report criteria.
                    </div>
                <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle submenu functionality
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const submenu = this.querySelector('.submenu');
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
<script>
        // Toggle receipt number field based on report type
        document.getElementById('reportType').addEventListener('change', function() {
            const receiptField = document.getElementById('receiptField');
            const monthField = document.getElementById('monthField');
            const yearField = document.getElementById('yearField');
            
            if (this.value === 'receipt') {
                receiptField.style.display = 'block';
                monthField.style.display = 'none';
                yearField.style.display = 'none';
            } else {
                receiptField.style.display = 'none';
                monthField.style.display = 'block';
                yearField.style.display = 'block';
            }
        });
        
        // Set form target for PDF generation
        document.querySelector('select[name="format"]').addEventListener('change', function() {
            document.getElementById('reportForm').target = this.value === 'pdf' ? '_blank' : '';
        });
    // Update form action based on format selection
    document.querySelectorAll('input[name="format"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('reportForm').target = this.value === 'pdf' ? '_blank' : '';
        });
    });

    // Payment calculation functionality
    function setupPaymentCalculation() {
        const checkboxes = document.querySelectorAll('.subject-checkbox');
        const summarySubjectCount = document.getElementById('summarySubjectCount');
        const summaryTotal = document.getElementById('summaryTotal');
        const monthSelect = document.querySelector('select[name="month"]');
        const summaryMonth = document.getElementById('summaryMonth');
        const feePerSubject = 45; // Fixed fee per subject (RM45)

        function updatePaymentSummary() {
            let selectedCount = 0;
            
            // Count selected checkboxes
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedCount++;
                }
            });

            // Calculate total amount (45 per subject)
            const totalAmount = feePerSubject * selectedCount;

            // Update display
            summarySubjectCount.textContent = selectedCount;
            summaryTotal.textContent = totalAmount.toFixed(2);

            // Update month display if month select exists
            if (monthSelect && summaryMonth) {
                const month = monthSelect.options[monthSelect.selectedIndex]?.text || '';
                summaryMonth.textContent = month || '-';
            }
        }

        // Attach event listeners to checkboxes
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updatePaymentSummary);
        });

        // Also update when month changes
        if (monthSelect) monthSelect.addEventListener('change', updatePaymentSummary);

        // Initial update
        updatePaymentSummary();
    }

    // Initial setup - no need for loadSubjects() anymore
    setupPaymentCalculation();
});
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    
    confirmBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Validate selections
        const selectedSubjects = Array.from(
            document.querySelectorAll('.subject-checkbox:checked')
        ).map(cb => cb.value);
        
        if (selectedSubjects.length === 0) {
            alert('Please select at least one subject');
            return;
        }
        
        // Get form data
        const formData = {
            child_id: document.querySelector('select[name="child"]').value,
            month: document.querySelector('select[name="month"]').value,
            selected_subjects: selectedSubjects,
            confirm_payment: true
        };
        
        // Show payment confirmation with QR code
        Swal.fire({
            title: 'Complete Payment',
            html: `
                <div class="text-center">
                    <p>Total Amount: RM ${(45 * selectedSubjects.length).toFixed(2)}</p>
                    <img src="qr_code.png" alt="Payment QR" class="img-fluid mb-3" style="max-width: 200px;">
                    <div class="form-check">
                        <input type="checkbox" id="paymentVerified" class="form-check-input">
                        <label for="paymentVerified" class="form-check-label">I've completed the payment</label>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Confirm Payment',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                if (!document.getElementById('paymentVerified').checked) {
                    Swal.showValidationMessage('Please verify payment completion');
                    return false;
                }
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit payment
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Processing...
                `;
                
                fetch('payment_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `payment_receipt.php?receipt=${data.receipt_number}`;
                    } else {
                        Swal.fire('Error', data.message, 'error');
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = 'Confirm Payment';
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Payment processing failed', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Confirm Payment';
                });
            }
        });
    });
});
// Add this to your confirmBtn event listener
const paymentNotes = document.querySelector('textarea[name="payment_notes"]').value;
const paymentReference = document.querySelector('input[name="payment_reference"]').value;
const paymentType = document.querySelector('input[name="payment_type"]:checked').value;

// Add to your formData object
formData.payment_notes = paymentNotes;
formData.payment_reference = paymentReference;
formData.payment_type = paymentType;
</script>
</body>
</html>
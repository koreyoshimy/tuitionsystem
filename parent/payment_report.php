<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("db.php");

// Check if admin is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Handle print request
    if (isset($_POST['print_report'])) {
        $_SESSION['report_data'] = [
            'title' => $report_title,
            'data' => $data,
            'params' => [
                'month' => $month,
                'year' => $year,
                'type' => $report_type
            ]
        ];
        header("Location: print_report.php");
        exit();
    }
    
    // Export in requested format
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Data rows
        foreach ($data as $row) {
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
        if (!empty($data)) {
            $html .= '<tr>';
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th><strong>'.htmlspecialchars($header).'</strong></th>';
            }
            $html .= '</tr>';
        }
        
        // Data rows
        foreach ($data as $row) {
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

// Get available years and months
$years = $conn->query("SELECT DISTINCT year FROM payments ORDER BY year DESC");
$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Payment Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
                font-size: 12px;
            }
            .table {
                width: 100% !important;
            }
        }
        .report-actions {
            margin-bottom: 20px;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h2 class="mb-4">Generate Payment Report</h2>
        
        <div class="card no-print">
            <div class="card-header">
                <h4>Report Parameters</h4>
            </div>
            <div class="card-body">
                <form method="post" id="reportForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Report Type</label>
                            <select name="report_type" class="form-select" required>
                                <option value="summary">Summary Report</option>
                                <option value="detailed">Detailed Payments</option>
                                <option value="unpaid">Unpaid Students</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select" required>
                                <option value="all">All Months</option>
                                <?php foreach ($months as $m): ?>
                                    <option value="<?= $m ?>"><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select" required>
                                <option value="all">All Years</option>
                                <?php while ($y = $years->fetch_assoc()): ?>
                                    <option value="<?= $y['year'] ?>"><?= $y['year'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Output Format</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="formatCsv" value="csv" checked>
                                <label class="form-check-label" for="formatCsv">CSV (Excel)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="formatPdf" value="pdf">
                                <label class="form-check-label" for="formatPdf">PDF</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="formatPrint" value="print">
                                <label class="form-check-label" for="formatPrint">Print Preview</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="generate_report" class="btn btn-primary btn-lg">
                            <i class="fas fa-file-export"></i> Generate Report
                        </button>
                        <?php if (isset($data) && !empty($data)): ?>
                        <button type="submit" name="print_report" class="btn btn-secondary btn-lg">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (isset($data) && !empty($data)): ?>
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Report Preview</h4>
                <div class="report-actions no-print">
                    <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <?php foreach (array_keys($data[0]) as $header): ?>
                                <th><?= htmlspecialchars($header) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                <td><?= htmlspecialchars($value) ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted no-print">Showing <?= count($data) ?> records</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update form action based on format selection
        document.querySelectorAll('input[name="format"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('reportForm').target = this.value === 'pdf' ? '_blank' : '';
            });
        });
        
        // Dynamic report type changes
        document.querySelector('select[name="report_type"]').addEventListener('change', function() {
            // Could add logic here to show/hide fields based on report type
        });
    });
    </script>
</body>
</html>
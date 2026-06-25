<?php
include 'db.php';
session_start();
if (!isset($_GET['stuID'])) {
    die("Invalid request.");
}
$activePage = basename($_SERVER['PHP_SELF'], ".php");
$username = $_GET['stuID']; 
$stuID = $_GET['stuID']; 
// Fetch student info
$stmt = $conn->prepare("SELECT name, age FROM children WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$studentResult = $stmt->get_result();

if ($studentResult->num_rows === 0) {
    die("Student not found.");
}
$student = $studentResult->fetch_assoc();
$age = $student['age'];  // Fetch age after fetching student data

// Calculate year from age
$year = $age - 6;
// Fetch student exam records
$stmt = $conn->prepare("SELECT exam, score,subject FROM exams WHERE stuID = ?");
$stmt->bind_param("s", $stuID);
$stmt->execute();
$examResult = $stmt->get_result();
 $counter=1;   

$firstSubject = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Report - <?php echo htmlspecialchars($student['name']); ?></title>
    <style>
/* General reset and base styling */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

h1 {
    font-size: 28px;
    font-weight: 600;
    text-align: center;
    margin: 20px 0;
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
    width: 100%;
    z-index: 999;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.navbar-custom h1 {
    font-size: 20px;
    margin: 0;
    color: white;
    font-weight: 600;
}

/* Sidebar Styling */
.sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    width: 250px;
    height: calc(100% - 60px);
    background-color: #2b2640;
    color: white;
    padding: 20px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar h2 {
    text-align: center;
    font-size: 22px;
    margin-bottom: 30px;
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
    background-color: #002244;
    padding-left: 25px;
}

/* Main Content Styling */
.main-content {
    margin-left: 250px;
    padding: 80px 40px 40px 40px;
    background-color: #f0f4f8;
    min-height: 100vh;
    box-sizing: border-box;
}

.student-info {
    background-color: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.student-info strong {
    font-size: 16px;
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 12px 20px;
    text-align: left;
    font-size: 14px;
    color: #333;
}

table th {
    background-color: #f2f2f2;
    font-weight: bold;
}

table tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}

table tbody tr:hover {
    background-color: #e6f7ff;
}
        .toggle {
            cursor: pointer;
        }
/* Media Queries */
/* Print-specific styles */
        @media print {
            /* Hide the sidebar and navbar during printing */
            .sidebar, .navbar-custom {
                display: none !important;
            }

            /* Ensure only the main content is visible */
            .main-content {
                display: block !important;
                color: black !important;
            }

            /* Make the table borders and text black */
            table, th, td {
                border: 1px solid black !important;
                color: black !important;
            }

            th {
                background-color: #f2f2f2 !important;
            }
    /* Prevent page breaks inside the main content */
    .main-content {
        page-break-before: avoid !important;
        page-break-after: avoid !important;
    }
       /* Optional: Prevent page break after each row */
    table tr {
        page-break-inside: avoid !important;
    }

    /* Adjust margins for print */
    body {
        margin: 0;
        padding: 0;
    }

            /* Hide elements that should not be printed (like buttons) */
            .no-print {
                display: none !important;
            }
               .main-content {
        max-height: 100vh; /* Limit the content height to 1 page */
        overflow: hidden; /* Prevent content from overflowing */
    }
        }    </style>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdn.datatables.net/2.2.1/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
 <div class="navbar-custom">
    <h1>ACE Tuition Management System</h1>
</div>
    <div class="sidebar">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <ul>
        <li class="toggle">
            <a href="javascript:void(0);"><i class="fa fa-user"></i>My Profile <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>"><i class="fa fa-chart-line"></i>Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>"><i class="fa fa-key"></i>Change Password</a></li>
            </ul>      
        <li><a href="book.php" class="<?php echo ($activePage === 'book') ? 'active' : ''; ?>"> <i class="fas fa-book"></i> Subject</a></li>     
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Attendance</a></li>
        <li><a href="payment.php" class="<?php echo ($activePage === 'payment') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payment</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
    </div>
<h1>Student Performance Report</h1>
<div class="main-content">
<div class="student-info">
    <strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?><br>
    <strong>Age:</strong> <?php echo htmlspecialchars($student['age']); ?><br>
    <strong>Year:</strong> <?php echo htmlspecialchars($year); ?><br>
<strong>Subjects:</strong> 
 <?php $firstSubject = null;

// Loop through the result once to get the first subject
while ($row = $examResult->fetch_assoc()) {
    if ($firstSubject === null && !empty($row['subject'])) {
        // Save the first subject and break the loop
        $firstSubject = htmlspecialchars($row['subject']);
    }
    // We can stop the loop after the first subject is found
    if ($firstSubject !== null) {
        break;
    }
}
 echo $firstSubject ?: "No subjects available."; ?><br>

</div>

<h2>Exam Performance</h2>
<table>
    <thead>
        <tr>
            <th>No.</th>
            <th>Exam Type</th>
            <th>Marks</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $counter = 1;
        // Go back to the start of the result set (since we've already looped through it for subjects)
        $examResult->data_seek(0); 
        while ($row = $examResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $counter++ . "</td>";
            echo "<td>" . htmlspecialchars($row['exam']) . "</td>";
            echo "<td>" . htmlspecialchars($row['score']) . "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<div class="print-btn">
      <a href="performance.php">
        <button class="btn btn-secondary">Cancel</button>
    </a>
      <button onclick="window.print()" class="btn btn-primary">Print / Save as PDF</button>
</div>
</div>
<script>
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const submenu = this.querySelector('.submenu');
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });

    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

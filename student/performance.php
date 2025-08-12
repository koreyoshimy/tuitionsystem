<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if session is not active
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
// Fetch teacher information
//$teacherID = $_SESSION['teacherID'];

// Handle form submission to save exam data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam = $_POST['exam'];
    $stuID = $_POST['stuID'];
    $score = $_POST['score'];

    // Insert the exam details into the database
    $stmt = $conn->prepare("INSERT INTO exams (exam, stuID, teacherID, score) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siii", $exam, $stuID, $teacherID, $score);
    
    if ($stmt->execute()) {
        $message = "Exam saved successfully!";
    } else {
        $message = "Failed to save the exam.";
    }
}
$activePage = basename($_SERVER['PHP_SELF'], ".php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance - Exams</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdn.datatables.net/2.2.1/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Styling similar to course.php with minor tweaks for the performance page */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            background-color: #f4f4f4;
        }

        .sidebar {
    width: 250px;
    background-color:  #2b2640; /* Dark blue background */
    color: white;
    padding: 20px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    height: 100%;
    position: fixed;
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


.main-content {
    flex: 1;
    margin-left: 280px; /* Ensure the main content is not hidden behind the sidebar */
    display: flex;
    flex-direction: column;
    
    justify-content: flex-start;
    min-height: 100vh;
    background-color: #f0f4f8;
    padding: 40px;
    box-sizing: border-box;
}

        .form-container {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        .form-container input,
        .form-container select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-container button {
            background-color: #4a9cf0;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 16px;
        }

        .form-container button:hover {
            background-color: #3a8ad0;
        }

        .exam-list {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .exam-list table {
            width: 80%;
            border-collapse: collapse;
        }

        .exam-list table, th, td {
            border: 1px solid #ddd;
        }

        .exam-list th, td {
            padding: 8px;
            text-align: left;
        }

        .exam-list th {
            background-color: #f2f2f2;
    font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
       
        <ul>
        <li class="toggle">
            <a href="javascript:void(0);">My Profile <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>">Change Password</a></li>
            </ul>      
        <li><a href="book.php" class="<?php echo ($activePage === 'book') ? 'active' : ''; ?>">Subject</a></li>
      
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>">Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>">Attendance</a></li>
        <li><a href="payment.php" class="<?php echo ($activePage === 'payment') ? 'active' : ''; ?>">Payment</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>">Log Out</a></li>
    </ul>
    </div>

    <div class="main-content">
        <h1>Performance - Exams</h1>
        
        <!-- Message for form submission -->
        <?php if (isset($message)) { ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php } ?>

        <!-- Exam Marks List -->
        <div class="exam-list">
            <h2>Exam Marks</h2>
            <table>
                <thead>
                    <tr>
                        <th>Num</th>
                        <th>Subject</th>
                        <th>Exam Type</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all exam records for the logged-in teacher
                    $stmt = $conn->prepare("SELECT testID, exam, score FROM exams ");
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $counter=1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['testID']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['exam']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['score']) . "</td>";
                        echo "</tr>";
                    }
                    
                    ?>
                      <!-- Exam Creation Form -->
      
                </tbody>
            </table>
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
</body>
</html>

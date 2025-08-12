<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = trim($_SESSION['username']);
$teacher_username = $username;
// Debug output at the start
//echo "<div style='background:#f0f0f0; padding:10px; margin-bottom:20px;'>";
//echo "Debug: Current teacher username is: '" . htmlspecialchars($username) . "'<br>";

// Get teacher's subject using prepared statement
$sql_subject = "SELECT id, subject_id, subject_name, time FROM subjects WHERE teacher_username = ?";
$stmt = $conn->prepare($sql_subject);
$stmt->bind_param("s", $username); // Binding the teacher_username correctly as a string
$stmt->execute();
$result_subject = $stmt->get_result();

// Fetch the subject details
$subject = $result_subject->fetch_assoc();

// Check if any subject is found
if (!$subject) {
    echo "No matching subjects found in database for this teacher.";
    die("No subject found for this teacher. Please contact administrator.");
}

$subject_id = $subject['subject_id'];
$subject_name = $subject['subject_name'];
$subject_time = $subject['time']; // Now you have the time correctly fetched


// Debug: Output the subject info
$subject_id = $subject['subject_id'];
$id = $subject['id'];

// Get students registered for this subject
$sql_students = "SELECT c.name, c.username, c.class  
                FROM children c
                JOIN subjects s ON c.id = s.username
                WHERE s.subject_id = ?";
$stmt = $conn->prepare($sql_students);
$stmt->bind_param("s", $subject_id);
$stmt->execute();
$result_students = $stmt->get_result();

if (!$result_students) {
    die("Error fetching students: " . $conn->error);
}
// New variable name: $queryTeacherClasses for the query to fetch the teacher's classes
$queryTeacherClasses = "
    SELECT c.class 
    FROM children c
    JOIN teacher_classes tc ON c.class = tc.class
    WHERE tc.teacher_username = ?
";
$teacherId = $_SESSION['username']; 
$stmt = $conn->prepare($queryTeacherClasses);
$stmt->bind_param("s", $teacherId);  // Bind the teacher's username to the query
$stmt->execute();

// New variable name: $classResults to store the fetched result
$class = $stmt->get_result();

// Check if any classes are found for the teacher
if ($class->num_rows > 0) {
    while ($row = $class->fetch_assoc()) {
        echo "Class: " . htmlspecialchars($row['class']) . "<br>";
    }
} else {
    echo "No classes found for this teacher.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Class List</title>
    <link rel="stylesheet" href="//cdn.datatables.net/2.2.1/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
        
        /* Sidebar Styling */
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

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 16px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #2b2640;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .student-count {
            background: #2b2640;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 20px;
        }

    </style>
</head>
<body>
    <div class="navbar-custom d-flex justify-content-between">
    <h1>ACE Tuition Management System</h1>
    <div class="d-flex align-items-center gap-3 text-white">
        <div class="text-end">
            <div><?php echo htmlspecialchars($name); ?></div> <!-- Use $username from session -->
            <div style="font-size: 12px;">Teacher</div> <!-- Hardcode role since we know it's parent -->
<div class="sidebar">
       <ul>
        <li class="toggle">
            <a href="javascript:void(0);"><i class="fa fa-user"></i>My Profile <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>"><i class="fa fa-chart-line"></i>Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>"><i class="fa fa-key"></i>Change Password</a></li>
            </ul>      
        <li><a href="classlist.php" class="<?php echo ($activePage === 'class') ? 'active' : ''; ?>"><i class="fas fa-book"></i> Class</a></li>     
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Attendance</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
    </div>

<div class="main-content">
    <h1>Class List for "<?php echo htmlspecialchars($subject['subject_name']); ?>"</h1>
    
    <div class="student-count">
        Total students: <?php echo $result_students->num_rows; ?>
    </div>

    <?php if ($result_students->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Username</th>
                <th>Time</th>
                <th>Class</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($student = $result_students->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><?php echo htmlspecialchars($student['username']); ?></td>
                
                    <?php
                    // Assuming the time format in the database is "2:00 p.m." or similar.
                    // Convert the time to a 24-hour format.
                    if (strpos($subject_time, 'p.m.') !== false || strpos($subject_time, 'a.m.') !== false) {
                        $time = date("H:i", strtotime($subject_time)); // Convert to 24-hour format (e.g., "14:00")
                    } else {
                        $time = $subject_time; // Use the time as-is if already in correct format
                    }

                    // If the day is not part of the `time` field, you may either hardcode it or get it from a different field
                    $day_of_week = "Wed"; // Example, adjust this according to your day data or store in the DB

                    // Display time as "Wed 14:00-16:00"
                    echo "<td>" . htmlspecialchars($day_of_week) . " " . htmlspecialchars($time) . "</td>";
                    ?>
                <td><?php echo htmlspecialchars($student['class']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No students registered for this subject yet.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
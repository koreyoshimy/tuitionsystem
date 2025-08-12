<?php
session_start();
include 'db.php';

// // Check if the user is logged in and is a student
// if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
//     // Redirect to login page if session is not active or user is not student
//     header("Location: login.php");
//     exit();
// }

// // Fetch student data from database
// $username = $_SESSION['username'];
// $sql = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
// $result = mysqli_query($conn, $sql);
// $student = mysqli_fetch_assoc($result);

// // Example: Fetch enrolled courses
// $sql_courses = "SELECT * FROM courses WHERE student_id = " . $student['id'];
// $courses = mysqli_query($conn, $sql_courses);

// ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        /* General Styling */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            background-color: #f4f4f4;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background-color: #4a9cf0; /* Sidebar background color */
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 20px;
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
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .sidebar ul li a.active {
            background-color: #3a8ad0; /* Active color */
            font-weight: bold;
        }

        .sidebar ul li a:hover {
            background-color: #3a8ad0;
        }

        /* Main Content Styling */
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: white;
            border-left: 1px solid #ddd;
        }

        .welcome {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .courses {
            margin-top: 30px;
        }

        .courses table {
            width: 100%;
            border-collapse: collapse;
        }

        .courses th, .courses td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .courses th {
            background-color: #4a9cf0;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Student Dashboard</h2>
        <ul>
            <li><a href="student.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="viewcourses.php" class="<?php echo ($activePage === 'viewcourses') ? 'active' : ''; ?>">My Courses</a></li>
            <li><a href="assignments.php" class="<?php echo ($activePage === 'assignments') ? 'active' : ''; ?>">Assignments</a></li>
            <li><a href="grades.php" class="<?php echo ($activePage === 'grades') ? 'active' : ''; ?>">Grades</a></li>
            <li><a href="profile.php" class="<?php echo ($activePage === 'profile') ? 'active' : ''; ?>">Edit Profile</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome">
            Welcome, <?php echo htmlspecialchars($student['name']); ?>!
        </div>
        <h1>Your Dashboard</h1>
        
        <!-- Display Enrolled Courses -->
        <div class="courses">
            <h2>Your Courses</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Instructor</th>
                        <th>Schedule</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Display each course the student is enrolled in
                    while ($course = mysqli_fetch_assoc($courses)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($course['course_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($course['instructor']) . "</td>";
                        echo "<td>" . htmlspecialchars($course['schedule']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Display Grades Section -->
        <div class="grades">
            <h2>Your Grades</h2>
            <p>View your completed assignments and grades here.</p>
        </div>
    </div>
</body>
</html>

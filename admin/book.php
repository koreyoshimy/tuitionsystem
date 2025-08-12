<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if session is not active
    header("Location: login.php");
    exit();
}
// Get the logged-in student's ID
$username = $_SESSION['username']; 
$activePage = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
            color: white; /* Link color */
            font-size: 16px;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #3a8ad0;
        }

        /* Expandable Submenu Styling */
        .sidebar ul .submenu {
            display: none; /* Initially hide submenus */
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
            padding: 20px;
            background-color: white;
            border-left: 1px solid #ddd;
        }

        .main-content h1 {
            color: #333;
        }
        .toggle {
            cursor: pointer;
        }
        .edit-btn {
        background-color: #4caf50; /* Green background */
        color: white; /* White text */
        border: 1px solid #4caf50;
    }

    .edit-btn:hover {
        background-color: #45a049; /* Darker green on hover */
        color: white;
    }

    /* Delete Button Styling */
    .delete-btn {
        background-color: #f44336; /* Red background */
        color: white; /* White text */
        border: 1px solid #f44336;
    }

    .delete-btn:hover {
        background-color: #d32f2f; /* Darker red on hover */
        color: white;
    }
    .action-btn {
        display: inline-block;
        padding: 8px 12px;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        text-align: center;
        border-radius: 5px;
        transition: background-color 0.3s, color 0.3s;
        margin-right: 5px;
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
                <li><a href="editprofile.php" class="<?php echo ($activePage === 'editprofile') ? 'active' : ''; ?>">Edit Profile</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>">Change Password</a></li>
            </ul>
        </li>
        <li class="toggle">
            <a href="javascript:void(0);">Subjects <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="course.php" class="<?php echo ($activePage === 'course') ? 'active' : ''; ?>">Add</a></li>
                <li><a href="book.php" class="<?php echo ($activePage === 'book') ? 'active' : ''; ?>">Manage</a></li>
            </ul>
        </li>
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>">Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>">Attendance</a></li>
        <li><a href="payment.php" class="<?php echo ($activePage === 'payment') ? 'active' : ''; ?>">Payment</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>">Log Out</a></li>
    </ul>
</div>

    <div class="main-content">
        <h1>Registered Subject</h1>
        <!-- Add dashboard content -->
        <?php

    // Fetch course details from the database
    $result = mysqli_query($conn, "SELECT * FROM subjects WHERE username = '$username'");

    if (mysqli_num_rows($result) > 0) {
        echo "<h3>All Booked Courses:</h3>";
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr>
                <th>Num</th>
                <th>Subject</th>
                <th>Time</th>
                <th>Section</th>
                <th>Age</th>
                <th>Actions</th>
              </tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['time']) . "</td>";
            echo "<td>" . htmlspecialchars($row['section']) . "</td>";
            echo "<td>" . htmlspecialchars($row['age']) . "</td>";
            echo "<td>
                <a href='edit.php?id=" . urlencode($row['id']) . "' class='action-btn edit-btn'>Edit</a>
                <a href='delete.php?id=" . urlencode($row['id']) . "' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this course?\"'>Delete</a>
              </td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No courses found.</p>";
    }
    ?>

    </div>
    <script>
    // Add click event to toggle the submenu
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

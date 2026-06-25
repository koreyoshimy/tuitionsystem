<?php
session_start();
include 'db.php';
$username = $_SESSION['username'];
// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if session is not active
    header("Location: login.php");
    exit();
    
}
$activePage = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Register new course</title>
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

        .form-container select {
            appearance: none; /* For better cross-browser styling */
            background-color: #fff;
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
        .toggle {
            cursor: pointer;
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
        <h1>Book Course</h1>

        <!-- Book New Course Module -->
        <div class="form-container">
            <h2>Book New Course</h2>
            <form action="bookcourse.php" method="POST">
            
            <label for="subject">Choose a subject:</label>
                <select name="subject" id="subject" required>
                    <option value="" disabled selected>Select a subject</option>
                    <option value="Bahasa Melayu">Bahasa Melayu</option>
                    <option value="English">English</option>
                    <option value="Science">Science</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Sejarah">Sejarah</option>
                </select><br>

                <label for="time">Time</label>
                <input type="text" id="time" name="time" required>

                <label for="section">Section</label>
                <input type="text" id="section" name="section" required>

                <label for="age">Age</label>
                <input type="number" id="age" name="age" required>

                <button type="submit">Book Course</button>
            </form>
        </div>
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

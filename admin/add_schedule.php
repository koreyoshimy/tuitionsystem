<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$teacherID = $_SESSION['username'];
$activePage = basename($_SERVER['PHP_SELF'], ".php");

// Handle form submission to save schedule data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $day = $_POST['day'];
    $time = $_POST['time'];
    $duration = $_POST['duration'];
    $room = $_POST['room'];
    $teacher = $_POST['teacher'];

    // Insert the schedule into the database
    $stmt = $conn->prepare("INSERT INTO schedules (class, subject, day, time, duration,room, teacher) VALUES (?, ?, ?, ?, ?,?, ?)");
    $stmt->bind_param("sssssss", $class, $subject, $day, $time, $duration,$room, $teacher);
    
    if ($stmt->execute()) {
        header("Location: manageschedule.php?success=Schedule added successfully!");
        exit();
    } else {
        $error = "Failed to add the schedule.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Schedule</title>
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
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #4a9cf0;
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #3a8ad0;
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        .error {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
            .toggle {
            cursor: pointer;
        }
.submenu {
    display: none;
    padding-left: 20px;
    margin-top: 5px;
}

.submenu li a {
    font-size: 14px;
    padding: 8px 16px;
    background-color: transparent;
    border-radius: 4px;
    color: #ddd;
    transition: background-color 0.3s, padding-left 0.3s;
}

.submenu li a:hover {
    background-color: #4a4366;
    padding-left: 22px;
}

/* Highlight active submenu correctly */
.submenu li a.active {
    background-color: #1e1a35;
    font-weight: bold;
    padding-left: 22px;
    color: #fff;
}
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <ul>
        <li class="toggle">
            <a href="javascript:void(0);"><i class="fa fa-user"></i>My Profile <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>"> <i class="fa fa-chart-line"></i>Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>"> <i class="fa fa-key"></i> Change Password</a></li>
            </ul></li>
            <li><a href="manageuser.php" class="<?php echo ($activePage === 'manageuser') ? 'active' : ''; ?>"><i class="fa fa-user-edit"></i>Manage User</a></li>
            <li><a href="manageschedule.php" class="<?php echo ($activePage === 'manageschedule') ? 'active' : ''; ?>"><i class="fa fa-calendar"></i>Manage Schedule</a></li>
            <li class="toggle">
            <a href="javascript:void(0);"><i class="fa fa-chart-bar"></i>Report</i></a>
            <ul class="submenu">
                <li><a href="payment_report.php" class="<?php echo ($activePage === 'payment_report') ? 'active' : ''; ?>"><i class="fa fa-credit-card"></i>Payment</a></li>
                <li><a href="attendance_report.php" class="<?php echo ($activePage === 'attendance_report') ? 'active' : ''; ?>"><i class="fa fa-calendar-check"></i> Attendance</a></li>
            </ul></li>     
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
    </ul>
    </div>

    <div class="main-content">
        <a href="manageschedule.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Schedule
        </a>
        
        <div class="form-container">
            <h2><i class="fas fa-calendar-plus"></i> Add New Schedule</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
    <label for="class">Class</label>
    <select id="class" name="class" required>
        <option value="">-- Select Class --</option>
        <option value="Year 1">Year 1</option>
        <option value="Year 2">Year 2</option>
        <option value="Year 3">Year 3</option>
        <option value="Year 4">Year 4</option>
        <option value="Year 5">Year 5</option>
        <option value="Year 6">Year 6</option>
    </select>
</div>

<div class="form-group">
    <label for="subject">Subject</label>
    <select id="subject" name="subject" required>
        <option value="">-- Select Subject --</option>
        <option value="Mathematics">Mathematics</option>
        <option value="Science">Science</option>
        <option value="Bahasa Melayu">Bahasa Melayu</option>
        <option value="English">English</option>
    </select>
</div>

                
                <div class="form-group">
                    <label for="day">Day</label>
                    <select id="day" name="day" required>
                        <option value="">Select a day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="time">Time</label>
                    <input type="time" id="time" name="time" required>
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration" min="60" max="180" value="120" required>
                </div>

                <div class="form-group">
            <label for="room">Room</label>
                    <select id="room" name="room" required>
                        <option value="">Select a room</option>
                        <option value="A">Room A</option>
                        <option value="B">Room B</option>
                        <option value="C">Room C</option>
                        <option value="D">Room D</option>
                        <option value="E">Room E</option>
                    </select>
            </div>
                
                <div class="form-group">
                    <label for="teacher">Teacher</label>
                    <input type="text" id="teacher" name="teacher" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Schedule
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle submenus
        document.querySelectorAll('.toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const submenu = this.querySelector('.submenu');
                if (submenu) {
                    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                }
            });
        });
    </script>
</body>
</html>
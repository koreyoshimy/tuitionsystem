<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$teacherID = $_SESSION['username'];
$activePage = basename($_SERVER['PHP_SELF'], ".php");

// Display success message if redirected from add/edit
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt = $conn->prepare($query);
// Fetch existing schedules
$schedules = [];
$result = $conn->query("SELECT * FROM schedules ORDER BY day, time");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Heuristic scheduling algorithm
function generateSchedule($schedules) {
    // Define time slots (8am to 5pm in 1-hour increments)
    $timeSlots = [];
    for ($i = 11; $i <= 17; $i++) {
        $timeSlots[] = sprintf("%02d:00:00", $i);
    }
    
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday','Saturday'];
    $scheduleGrid = [];
    
    // Initialize empty grid
    foreach ($timeSlots as $time) {
        $scheduleGrid[$time] = [];
        foreach ($days as $day) {
            $scheduleGrid[$time][$day] = null;
        }
    }
    
    // Place existing schedules using first-fit decreasing heuristic
    foreach ($schedules as $schedule) {
        $time = $schedule['time'];
        $day = $schedule['day'];
        $duration = (int)$schedule['duration'];
        
        // Find available time slot
        $foundSlot = false;
        foreach ($timeSlots as $slot) {
            if (strtotime($slot) >= strtotime($time)) {
                if ($scheduleGrid[$slot][$day] === null) {
                    // Mark slots based on duration (assuming 1-hour minimum)
                    $hoursNeeded = ceil($duration / 60);
                    $available = true;
                    
                    // Check if consecutive slots are available
                    for ($i = 0; $i < $hoursNeeded; $i++) {
                        $currentSlot = date('H:i:s', strtotime($slot) + $i * 3600);
                        if (!isset($scheduleGrid[$currentSlot][$day])) {
                            $available = false;
                            break;
                        }
                        if ($scheduleGrid[$currentSlot][$day] !== null) {
                            $available = false;
                            break;
                        }
                    }
                    
                    if ($available) {
                        // Mark all needed slots as occupied
                        for ($i = 0; $i < $hoursNeeded; $i++) {
                            $currentSlot = date('H:i:s', strtotime($slot) + $i * 3600);
                            $scheduleGrid[$currentSlot][$day] = $schedule;
                        }
                        $foundSlot = true;
                        break;
                    }
                }
            }
        }
        
        if (!$foundSlot) {
            // If no slot found, place in the original time (may overlap)
            $scheduleGrid[$time][$day] = $schedule;
        }
    }
    
    return $scheduleGrid;
}

$scheduleGrid = generateSchedule($schedules);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule</title>
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
            .toggle {
            cursor: pointer;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
        }

        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .schedule-table th {
            background-color: #4a9cf0;
            color: white;
            padding: 15px;
            text-align: center;
            position: sticky;
            top: 0;
        }

        .schedule-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            vertical-align: top;
            height: 80px;
        }

        .schedule-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .time-col {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .schedule-item {
            background-color: #e7f3ff;
            border-radius: 6px;
            padding: 10px;
            margin: 2px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .schedule-item:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .schedule-item h4 {
            margin: 0 0 5px 0;
            color: #0056b3;
        }

        .schedule-item p {
            margin: 3px 0;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 5px;
            margin-top: 8px;
            justify-content: center;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-slot {
            color: #6c757d;
            font-style: italic;
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
    <div class="navbar-custom d-flex justify-content-between">
    <h1>ACE Tuition Management System</h1>
    <div class="d-flex align-items-center gap-3 text-white">
        <div class="text-end">
            <div><?php echo htmlspecialchars($parent['username']); ?></div>
            <div style="font-size: 12px;"><?php echo htmlspecialchars($parent['role']); ?></div>
        </div>
        <a href="logout.php" class="text-white" title="Logout">
            <i class="fas fa-sign-out-alt fa-lg"></i>
        </a>
    </div>
</div>
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
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
    </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Class Schedule</h1>
            <a href="add_schedule.php" class="btn btn-add">
                <i class="fas fa-plus"></i> Add Schedule
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Monday</th>
                    <th>Tuesday</th>
                    <th>Wednesday</th>
                    <th>Thursday</th>
                    <th>Friday</th>
                    <th>Saturday</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scheduleGrid as $time => $days): ?>
                    <tr>
                        <td class="time-col"><?php echo date('h:i A', strtotime($time)); ?></td>
                        <?php foreach ($days as $day => $schedule): ?>
                            <td>
                                <?php if ($schedule && $schedule['time'] === $time): ?>
                                    <div class="schedule-item">
                                        <h4><?php echo htmlspecialchars($schedule['class']); ?></h4>
                                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($schedule['subject']); ?></p>
                                        <p><strong>Teacher:</strong> <?php echo htmlspecialchars($schedule['teacher']); ?></p>
                                        <p><strong>Room:</strong> <?php echo htmlspecialchars($schedule['room']); ?></p>
                                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($schedule['duration']); ?> mins</p>
                                        <div class="actions">
                                            <a href="edit_schedule.php?id=<?php echo $schedule['id']; ?>" class="btn btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_schedule.php?id=<?php echo $schedule['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this schedule?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php elseif ($schedule === null): ?>
                                    <div class="empty-slot">Available</div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
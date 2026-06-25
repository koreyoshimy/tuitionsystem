<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Fetch parent details
$username = $_SESSION['username'];
$query = "SELECT name, email,gender FROM users WHERE username = ?";
$stmt = $conn->prepare($query);

$username = $_SESSION['username']; 
$activePage = basename($_SERVER['PHP_SELF'], ".php");

$bookingsQuery = "SELECT 
                    s.id as booking_id,
                    s.subject_name,
                    s.subject_id,
                    s.time,
                    s.fee,
                    s.age,
                    c.id as child_id,
                    c.name as child_name
                 FROM subjects s
                 JOIN children c ON s.username = c.id
                 JOIN parent_child pc ON c.id = pc.child_id
                 WHERE pc.parent_username = ?
                 ORDER BY c.name, s.time";
$stmt = $conn->prepare($bookingsQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$bookingsResult = $stmt->get_result();
$bookings = $bookingsResult->fetch_all(MYSQLI_ASSOC);
$activePage = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Student Dashboard</title>
    <style>
        /* General Styling */
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
        .main-content h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .toggle {
            cursor: pointer;
        }

        /* Button Styling */
        .add-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .add-btn:hover {
            background-color: #0056b3;
        }

        .add-btn i {
            margin-right: 5px;
        }

        /* Bookings Table Styling */
        .bookings-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-top: 20px;
        }

        .bookings-container h2 {
            color: #444;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .bookings-table th {
            background-color: #3a8ad0;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }

        .bookings-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .bookings-table tr:hover {
            background-color: #f5f5f5;
        }

        .bookings-table tr:last-child td {
            border-bottom: none;
        }

        .no-bookings {
            color: #666;
            font-style: italic;
            padding: 20px;
            text-align: center;
        }

        /* Action Buttons */
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            transition: all 0.3s;
        }

        .edit-btn {
            background-color: #4caf50;
            color: white;
            border: 1px solid #4caf50;
        }

        .edit-btn:hover {
            background-color: #45a049;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
            border: 1px solid #f44336;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
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
    <h1>Registered Subjects</h1>
    <a href="course.php" class="add-btn"><i class="fa fa-plus"></i> Add New Course</a>

    <div class="bookings-container">
        <h2>Current Bookings</h2>
        <?php if (!empty($bookings)): ?>
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>Child</th>
                        <th>Subject</th>
                        <th>Code</th>
                        <th>Time</th>
                        <th>Fee</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['child_name']) ?></td>
                            <td><?= htmlspecialchars($booking['subject_name']) ?></td>
                            <td><?= htmlspecialchars($booking['subject_id']) ?></td>
                            <td><?= htmlspecialchars($booking['time']) ?></td>
                            <td>RM <?= htmlspecialchars($booking['fee']) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $booking['booking_id'] ?>" class="action-btn edit-btn">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <a href="delete.php?id=<?= $booking['booking_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this booking?')">
                                    <i class="fa fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-bookings">No bookings found.</p>
        <?php endif; ?>
        <?php
?>
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
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    // Redirect to login page if session is not active
    header("Location: login.php");
    exit();
}

// Fetch user details from the database
$username = $_SESSION['username']; // Use the username from the session
$query = "SELECT name, email, gender FROM users WHERE username = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("s", $username); // Bind the username
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc(); // Fetch the user details

    if (!$user) {
        // Handle case where user is not found in the database
        die("User not found in the database.");
    }
} else {
    die("Failed to prepare statement: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="//cdn.datatables.net/2.2.1/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Tecaher Dashboard</title>
    <style>
        /* General Styling */
        *{
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins" , sans-serif;
}
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
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
.toggle {
            cursor: pointer;
        }

        .main-content h1 {
            color: #333;
        }
        .user-info {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .user-info h2 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #444;
        }

        .user-info p {
            margin: 5px 0;
            color: #555;
        }
        .edit-btn {
    display: inline-flex;
    align-items: center;
    background-color: #007bff; /* Primary blue color */
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: background-color 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.edit-btn i {
    margin-right: 8px; /* Space between icon and text */
    font-size: 18px; /* Adjust icon size */
}

.edit-btn:hover {
    background-color: #0056b3; /* Darker blue on hover */
    text-decoration: none; /* Ensure no underline on hover */
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
        <h1>Your Dashboard</h1>
        <div class="user-info">
            <h2>User Information</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
            <!-- Add Edit Button -->
            <a href="editprofile.php" class="edit-btn">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
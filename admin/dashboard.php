<?php

include' db.php'; -
//Create connection
$conn = mysqli_connect($host, $username, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
//echo "Connected successfully";

 
// Function to get count from a table
function getCount($tableName, $conn) {
    $sql = "SELECT COUNT(*) as total FROM $tableName";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}
function countTeachers($conn) {
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username']; // this is your identifier
    $name = $_POST['name'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, gender=? WHERE username=?");
    $stmt->bind_param("ssss", $name, $email, $gender, $username);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
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
.stats-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

/* Individual stat card */
.stat-card {
    flex: 1 1 calc(25% - 20px);
    background-color: white;
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #3498db;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    min-width: 200px;
    height: 110px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

        /* Card specific styles */
        .card-1 { border-left-color: #3498db; }
        .card-2 { border-left-color: #e74c3c; }
        .card-3 { border-left-color: #2ecc71; }
        .card-4 { border-left-color: #f39c12; }
        table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-top: 30px;
}

th, td {
    padding: 14px 20px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

th {
    background-color: #2b2640;
    color: white;
    font-weight: 600;
    font-size: 15px;
}

tr:hover {
    background-color: #f1f5ff;
}

tr:last-child td {
    border-bottom: none;
}

input[type="text"], input[type="email"], select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    background-color: #f9f9f9;
}
input[readonly] {
    background-color: #e9ecef; /* light grey */
    cursor: not-allowed;
    color: #6c757d; /* muted text */
}
button.save-btn {
    background-color: #3498db;
    color: white;
    padding: 6px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button.save-btn:hover {
    background-color: #2980b9;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="navbar-custom d-flex justify-content-between">
    <h1>ACE Tuition Management System</h1>
    <div class="d-flex align-items-center gap-3 text-white">
        <div class="text-end">
            <div><?php echo htmlspecialchars($name); ?></div>
            <div style="font-size: 12px;">Admin</div>
        </div>
        <a href="logout.php" class="text-white" title="Logout">
            <i class="fas fa-sign-out-alt fa-lg"></i>
        </a>
    </div>
</div>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
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
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card card-1">
                    <div class="icon">👨‍🎓</div>
                    <h3>Total Students</h3>
                    <div class="value"><?php echo getCount('children', $conn); ?></div>
                </div>
                
                <div class="stat-card card-2">
                    <div class="icon">📚</div>
                    <h3>Total Subjects</h3>
                    <div class="value"><p>4</p></div>
                </div>
                <div class="stat-card card-3">
                    <h3>Total Teachers</h3>
                    <div class="value"><?php echo countTeachers($conn); ?></div>
                </div>
                  <div class="stat-card card-4">
                <div class="icon">🏫</div>  
                  <h3>Total Classes</h3>
                    <div class="value"><p>12</p></div>
                </div>
            </div>
            
            <!-- Additional content can be added here -->
             <table>
    <tr>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Gender</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <form method="POST">
            <tr>
                <td>
                    <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" data-original="<?= htmlspecialchars($row['name']) ?>">
                </td>
                <td>
                    <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" readonly>
                </td>
                <td>
                    <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" data-original="<?= htmlspecialchars($row['email']) ?>">
                </td>
                <td>
                    <select name="gender" data-original="<?= htmlspecialchars($row['gender']) ?>">
                        <option <?= $row['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option <?= $row['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </td>
                <td>
                    <div class="action-buttons">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" class="save-btn">Save</button>
                    </div>
                </td>
            </tr>
        </form>
    <?php } ?>
</table>
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

<?php
// Close the database connection
$conn->close();
?>
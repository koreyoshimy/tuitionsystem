<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch parent details
$username = $_SESSION['username'];
// Modify your original query to include role:
$query = "SELECT name, email, gender, role, username FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();
$GLOBALS['parent_data'] = $parent;


// 4. Verify data exists
if (!$parent) {
    die("User data not found for: " . htmlspecialchars($username));
}

// Fetch children for this parent
$childrenQuery = "SELECT c.id, c.name,c.gender,c.school, c.age 
                 FROM children c
                 JOIN parent_child pc ON c.id = pc.child_id
                 WHERE pc.parent_username = ?";
$stmt = $conn->prepare($childrenQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$childrenResult = $stmt->get_result();
$children = $childrenResult->fetch_all(MYSQLI_ASSOC);

//Total Subjects   Query
$subjectListQuery = "
    SELECT s.*
    FROM subjects s
    JOIN children c ON s.username = c.username
    JOIN parent_child pc ON c.id = pc.child_id
    WHERE pc.parent_username = ?
";

$stmt = $conn->prepare($subjectListQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$totalSubjects = $result->fetch_all(MYSQLI_ASSOC);
$activePage = basename($_SERVER['PHP_SELF'], ".php");

$query = "SELECT name, email, gender, role, username FROM users WHERE username = ?";
$parent = $result->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Parent Dashboard</title>
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

        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .child-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .child-info {
            flex: 1;
        }

        .child-name {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .child-details {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .child-actions {
            display: flex;
            gap: 10px;
        }

        .add-child-btn {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .summary-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .summary-card p {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .summary-card .icon {
            font-size: 30px;
            margin-bottom: 10px;
            color: #2b2640;
        }
         .toggle {
            cursor: pointer;
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
    </style>
</head>
<body>
   <div class="navbar-custom d-flex justify-content-between">
     <?php var_dump($parent); ?>
    <h1>ACE Tuition Management System</h1>
    <div class="d-flex align-items-center gap-3 text-white">
        <div class="text-end">
            <div><?php echo htmlspecialchars($GLOBALS['parent_data']['name']); ?></div>
            <div style="font-size: 12px;">Parent</div>
        </div>
        <a href="logout.php" class="text-white" title="Logout">
            <i class="fas fa-sign-out-alt fa-lg"></i>
        </a>
    </div>
</div>
    <div class="sidebar">
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
        <h1>Parent Dashboard</h1>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="icon"><i class="bi bi-people-fill"></i></div>
                <h3>Children Registered</h3>
                <p><?php echo count($children); ?></p>
            </div>
            <div class="summary-card">
                <div class="icon"><i class="bi bi-book-half"></i></div>
                <h3>Total Subjects</h3>
                <p><?php echo count($totalSubjects); ?></p> <!-- You can add actual subject count logic -->
            </div>
            <div class="summary-card">
                <div class="icon"><i class="bi bi-cash-stack"></i></div>
                <h3>Pending Payments</h3>
                <p>0</p> <!-- You can add actual payment status logic -->
            </div>
        </div>

        <!-- Parent Information -->
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Parent Information</h2>
                <a href="editprofile.php" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </a>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($GLOBALS['parent_data']['name']); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($GLOBALS['parent_data']['email']); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($GLOBALS['parent_data']['gender']); ?></p>
                </div>
            </div>
        </div>

        <!-- Children Section -->
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>My Children</h2>
                <a href="add_child.php" class="btn btn-success add-child-btn">
                    <i class="bi bi-plus-circle"></i> Add Child
                </a>
            </div>
            
            <?php if (count($children) > 0): ?>
                <?php foreach ($children as $child): ?>
                    <div class="child-card">
                        <div class="child-info">
                            <div class="child-name"><?php echo htmlspecialchars($child['name']); ?></div>
                            <div class="child-details">
                                
                                School: <?php echo htmlspecialchars($child['school']); ?>
                            </div>
                            <div class="child-details mt-2">
                                Age:<?php echo htmlspecialchars($child['age']); ?> years | 
                                Gender: <?php echo htmlspecialchars($child['gender']); ?>
                            </div>
                        </div>
                        <div class="child-actions">
                            <a href="child_details.php?id=<?php echo $child['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="edit_child.php?id=<?php echo $child['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    You haven't registered any children yet. Click "Add Child" to get started.
                </div>
            <?php endif; ?>
        </div>
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

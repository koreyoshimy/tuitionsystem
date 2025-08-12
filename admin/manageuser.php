<?php
session_start();
include 'db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt = $conn->prepare($query);
// Prepare and execute the statement using mysqli
$query = "SELECT email, username, name, role FROM users ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
     <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
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

        /* Button Styles */
        .btn {
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 6px;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-top: 20px;
        }

        /* DataTable Styling */
        #userTable {
            width: 100% !important;
            margin-top: 15px !important;
        }

        #userTable thead th {
            background-color:rgb(104, 103, 103);
            color: white;
            font-weight: 600;
            padding: 12px 15px;
        }

        #userTable tbody td {
            padding: 10px 15px;
            vertical-align: middle;
        }

        #userTable tbody tr:hover {
            background-color: #f1f7ff;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #000;
            padding: 6px 10px;
            font-size: 13px;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
            padding: 6px 10px;
            font-size: 13px;
        }

        .btn-edit:hover, .btn-danger:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        /* DataTable Controls */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 5px 10px;
            border-radius: 4px;
            margin: 0 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #4a9cf0 !important;
            color: white !important;
            border: 1px solid #4a9cf0;
        }

        .toggle {
            cursor: pointer;
        }
        .action-buttons .btn {
    padding: 6px 12px;
    font-size: 14px;
    border-radius: 6px;
    transition: background-color 0.3s, transform 0.2s;
}

.action-buttons .btn-warning {
    background-color: #ffc107;
    border: none;
    color: #000;
}

.action-buttons .btn-danger {
    background-color: #dc3545;
    border: none;
    color: #fff;
}

.action-buttons .btn:hover {
    opacity: 0.9;
    transform: scale(1.05);
}
.btn {
    display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 6px;
        }


        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
             margin-top: 50px;
            padding: 20px;
            margin-right: 50px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:hover {
            background-color: #f1f7ff;
            transition: background-color 0.3s ease;
        }
                .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 40px;
            background-color: #f0f4f8;
        }
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
    </style>
    <!-- Font Awesome CSS (Latest version) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    <!-- Sidebar -->
    <div class="sidebar">
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
        <h1>Manage Users</h1>
        <a href="add_user.php" class="add-btn" >
        <i class="fas fa-plus" style="margin-right: 8px;"></i> Add User
    </a>
        <div class="table-container">
            <table id="userTable" class="display">
                  <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- PHP loop to display users -->
                <?php
                foreach ($users as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . (isset($row['email']) ? htmlspecialchars($row['email']) : 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                    echo '<td>
                        <a href="edit_user.php?id=' . urlencode($row['username']) . '" class = "btn btn-edit"><i class="fas fa-edit"></i>Edit</a>
                        <a href="delete_user.php?id=' . urlencode($row['username']) . '" class = "btn btn-danger" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i>Delete</a>
                    </td>';
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
</tbody>

                </tbody>
            </table>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>
    
    <!-- Initialize DataTable -->
    <script>
        $(document).ready(function() {
            $('#userTable').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true
            });
        });
    </script>
</body>
</html>

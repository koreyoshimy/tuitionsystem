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
    <title>Attendance</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* General Styling */
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
    width: 250px;
    background-color:  #2b2640; /* Dark blue background */
    color: white;
    padding: 20px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    height: 100%;
    position: fixed;
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
    margin-left: 250px; /* Ensure the main content is not hidden behind the sidebar */
    display: flex;
    flex-direction: column;
    
    justify-content: flex-start;
    min-height: 100vh;
    background-color: #f0f4f8;
    padding: 40px;
    box-sizing: border-box;
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

        .qr-container {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .qr-container h5 {
            margin-bottom: 20px;
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

        .qr-container video {
            width: 100%;
            max-width: 400px;
            height: auto;
            border: 2px solid #ddd;
            border-radius: 5px;
        }

        .qr-detected-container {
            display: none;
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
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>">Change Password</a></li>
            </ul>      
        <li><a href="book.php" class="<?php echo ($activePage === 'book') ? 'active' : ''; ?>">Subject</a></li>
      
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>">Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>">Attendance</a></li>
        <li><a href="payment.php" class="<?php echo ($activePage === 'payment') ? 'active' : ''; ?>">Payment</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>">Log Out</a></li>
    </ul>
    </div>

    <div class="main-content">
        <h1>Attendance</h1>

        <div class="qr-container">
            <h5>Scan your QR Code for attendance</h5>
            <video id="interactive" class="viewport"></video>
            <div class="qr-detected-container">
                <form action="add-attendance.php" method="POST">
                    <h4>Student QR Detected!</h4>
                    <input type="hidden" id="detected-qr-code" name="qr_code">
                    <button type="submit">Submit Attendance</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <!-- instascan Js -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

    <script>
        function startScanner() {
            const scanner = new Instascan.Scanner({ video: document.getElementById('interactive') });

            scanner.addListener('scan', function (content) {
                document.getElementById('detected-qr-code').value = content;
                console.log('Scanned Content:', content);

                scanner.stop();
                document.querySelector('.scanner-con').style.display = 'none';
                document.querySelector('.qr-detected-container').style.display = 'block';
            });

            Instascan.Camera.getCameras()
                .then(function (cameras) {
                    if (cameras.length > 0) {
                        scanner.start(cameras[0]);
                    } else {
                        alert('No cameras found!');
                    }
                })
                .catch(function (error) {
                    console.error('Camera error:', error);
                    alert('Camera access error: ' + error);
                });
        }

        document.addEventListener('DOMContentLoaded', startScanner);
       
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

<?php
session_start();
// Proper error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Check if user is logged in and is a teacher
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Include database connection
require_once("db.php");
// Verify the user is a teacher
$stmt = $conn->prepare("SELECT username, name, role FROM users WHERE username = ? AND role = 'teacher'");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
if (!$teacher) {
    // Not a valid teacher
    session_destroy();
    header("Location: login.php");
    exit();
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['attendance'])) {
        $date = $_POST['attendance_date'];
        $day = date('l', strtotime($date));
        $class = $_POST['class'];
        $subject = $_POST['subject'];
        $teacher_username = $_SESSION['username'];
        
        // Process each student's attendance
        foreach ($_POST['attendance'] as $username => $status) {
            $status = ($status == 'present') ? 'present' : 'absent';
            
            // Get student name
            $stmt = $conn->prepare("SELECT name FROM children WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
            // Record attendance with teacher info and method
            $insert = $conn->prepare("INSERT INTO attendance 
                                    (username, stuName, class, subject, status, date, day, time, recorded_by, method) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'manual')");
            $time = date("H:i:s");
            $insert->bind_param("sssssssss", 
                $username, 
                $student['name'], 
                $class, 
                $subject, 
                $status, 
                $date, 
                $day, 
                $time,
                $teacher_username
            );
            $insert->execute();
        }
        
        $_SESSION['message'] = "Attendance recorded successfully!";
        header("Location: add-attendance.php");
        exit();
    }
    elseif (isset($_POST['generate_qr'])) {
        // QR code generation code
        $date = $_POST['qr_date'];
        $day = date('l', strtotime($date));
        $class = $_POST['class'];
        $subject = $_POST['subject'];
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));
        
        // First clear any existing QR codes for this class/date/subject
        $clear = $conn->prepare("DELETE FROM qr_codes WHERE class = ? AND subject = ? AND date = ?");
        $clear->bind_param("sss", $class, $subject, $date);
        $clear->execute();
        
        // Get all students in class
        $stmt = $conn->prepare("SELECT username FROM children WHERE class = ?");
        $stmt->bind_param("s", $class);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($student = $result->fetch_assoc()) {
            $random_code = bin2hex(random_bytes(16));
            
            // Insert QR code with additional info
            $insert = $conn->prepare("INSERT INTO qr_codes 
                                    (username, class, subject, date, day, code_value, expiry) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param("sssssss", $student['username'], $class, $subject, $date, $day, $random_code, $expiry);
            $insert->execute();
        }
        
        $_SESSION['message'] = "QR codes generated successfully!";
        header("Location: attendance.php");
        exit();
    }
}

// Get teacher's assigned classes (assuming teachers can have multiple classes)
$teacher_classes = [];
$stmt = $conn->prepare("SELECT class FROM teacher_classes WHERE teacher_username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $teacher_classes[] = $row['class'];
}

// Get subjects for the teacher
$subjects = [];
$stmt = $conn->prepare("SELECT subject FROM teacher_subjects WHERE teacher_username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row['subject'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Your existing styles here */
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

.navbar-custom h1 {
    font-size: 20px;
    margin: 0;
    color: white;
    font-weight: 600;
}

.toggle {
            cursor: pointer;
        }
        /* Modal styles */
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background-color: #2b2640;
            color: white;
            border-bottom: none;
        }
        .modal-body {
            padding: 25px;
        }
        .form-label {
            font-weight: 600;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Your sidebar and main container here --> 
         <!-- Top Navbar -->
    <div class="navbar-custom d-flex justify-content-between">
    <h1>ACE Tuition Management System</h1>
    <div class="d-flex align-items-center gap-3 text-white">
        <div class="text-end">
            <div><?php echo htmlspecialchars($name); ?></div> <!-- Use $username from session -->
            <div style="font-size: 12px;">Teacher</div> <!-- Hardcode role since we know it's parent -->
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
        <li><a href="classlist.php" class="<?php echo ($activePage === 'class') ? 'active' : ''; ?>"><i class="fas fa-book"></i> Class</a></li>     
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Attendance</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
    </div>
    <div class="main-content">
        <h2>Class Attendance</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <!-- Button to trigger QR generation modal -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#qrModal">
                    <i class="fas fa-qrcode"></i> Generate QR Codes
                </button>
                
                <!-- Button to trigger manual attendance modal -->
               <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#attendanceModal">
    <i class="fas fa-clipboard-check"></i> Mark Attendance Manually
</button>
            </div>
        </div>
        
        <!-- Display attendance records if a class is selected -->
        <?php if (isset($_GET['class']) && in_array($_GET['class'], $teacher_classes)): 
            $current_class = $_GET['class'];
            $current_date = $_GET['date'] ?? date('Y-m-d');
            $current_subject = $_GET['subject'] ?? '';
            
            // Get students in class
            $students = [];
            $stmt = $conn->prepare("SELECT username, name FROM children WHERE class = ? ORDER BY name");
            $stmt->bind_param("s", $current_class);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            
            // Check if QR codes exist for this class/date/subject
            $qr_codes_exist = false;
            if (!empty($current_subject)) {
                $check = $conn->prepare("SELECT COUNT(*) as count FROM qr_codes 
                                       WHERE class = ? AND subject = ? AND date = ? AND expiry > NOW()");
                $check->bind_param("sss", $current_class, $current_subject, $current_date);
                $check->execute();
                $result = $check->get_result();
                $qr_codes_exist = $result->fetch_assoc()['count'] > 0;
            }
            ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Class: <?= htmlspecialchars($current_class) ?> | Date: <?= htmlspecialchars($current_date) ?> | Subject: <?= htmlspecialchars($current_subject) ?></h4>
                </div>
                <div class="card-body">
                    <form method="post" action="add-attendance.php">s // Nested forms
                        <input type="hidden" name="attendance_date" id="attendance_date" value="<?= $current_date ?>">
                        <input type="hidden" name="class" id="class" value="<?= $current_class ?>">
                        <input type="hidden" name="subject" id="subject" value="<?= $current_subject ?>">
                        
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Attendance</th>
                                    <?php if ($qr_codes_exist): ?>
                                    <th>QR Code</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="attendance[<?= $student['username'] ?>]" 
                                                   value="present" checked>
                                            <label class="form-check-label">Present</label>
                                        </div>
                                    </td>
                                    <?php if ($qr_codes_exist): ?>
                                    <td>
                                        <?php
                                        $stmt = $conn->prepare("SELECT code_value FROM qr_codes 
                                                              WHERE username = ? AND class = ? AND subject = ? AND date = ? AND expiry > NOW()");
                                        $stmt->bind_param("ssss", $student['username'], $current_class, $current_subject, $current_date);
                                        $stmt->execute();
                                        $qr_result = $stmt->get_result();
                                        
                                        if ($qr_result->num_rows > 0) {
                                            $qr = $qr_result->fetch_assoc();
                                            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . 
                                                      urlencode($qr['code_value']);
                                            echo "<img src='$qr_url' alt='QR Code' class='img-thumbnail'>";
                                        }
                                        ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="attendance" class="btn btn-success">Save Attendance</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    
        <!-- Manual Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
             <form method="post" action="add-attendance.php">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel">Mark Attendance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="get" action="attendance.php">
                <div class="modal-body">
                    <!-- Date -->
                    <div class="mb-3">
                        <label for="attendance_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Class -->
                    <div class="mb-3">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class" required>
                            <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <!-- Cancel button -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Submit button -->
                    <button type="submit" class="btn btn-primary">Continue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- QR Generation Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">Generate QR Codes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="qr_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="qr_date" name="qr_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="qr_class" name="class" required>
                            <?php foreach ($teacher_classes as $class): ?>
                            <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="qr_subject" name="subject" required>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Preview QR Code Section -->
                    <div id="qrPreviewSection" style="display:none; text-align: center; margin-top:20px;">
                        <h5>QR Code Preview</h5>
                        <img id="qrImg" src="" alt="QR Code" class="img-thumbnail mb-3" style="width: 150px; height: 150px;">
                        <div class="mb-2"><strong>Date:</strong> <span id="preview_date"></span></div>
                        <div class="mb-2"><strong>Class:</strong> <span id="preview_class"></span></div>
                        <div class="mb-2"><strong>Subject:</strong> <span id="preview_subject"></span></div>
                    </div>

                    <!-- Hidden input to store generated code -->
                    <input type="hidden" id="generatedCode" name="generatedCode" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Preview Button -->
                    <button type="button" class="btn btn-secondary" onclick="generateQrCode()">Generate QR</button>
                    <!-- Submit Button -->
                    <button type="submit" name="generate_qr" class="btn btn-primary">Submit QR</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- Submit Button (now handled by JS, not form POST) -->
    <!-- <button type="button" id="submitGenerateBtn" class="btn btn-primary" onclick="submitGenerateQr()">Submit & Generate</button> -->
</div>
</div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const submenu = this.querySelector('.submenu');
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
    function generateRandomCode(length) {
        const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        let randomString = '';

        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * characters.length);
            randomString += characters.charAt(randomIndex);
        }

        return randomString;
    }
    function generateQrCode() {
        const selectedClass = document.getElementById('qr_class').value;
        const selectedSubject = document.getElementById('qr_subject').value;
        const selectedDate = document.getElementById('qr_date').value;

        if (selectedSubject === "" || selectedClass === "" || selectedDate === "") {
            alert("Please fill all fields before generating QR code.");
            return;
        }

        // Generate random code
        const randomCode = generateRandomCode(10);

        // Set hidden input so it will be submitted
        document.getElementById('generatedCode').value = randomCode;

        // Generate QR image URL
        const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(randomCode)}`;
        document.getElementById('qrImg').src = apiUrl;

        // Set preview info
        document.getElementById('preview_date').innerText = selectedDate;
        document.getElementById('preview_class').innerText = selectedClass;
        document.getElementById('preview_subject').innerText = selectedSubject;

        // Show QR preview section
        document.getElementById('qrPreviewSection').style.display = 'block';
    }

    // Optional: you can keep this function if later you want AJAX submit
    function submitGenerateQr() {
        alert("QR Codes generated successfully!");
        // No need for AJAX if using normal form POST
        // The form will submit and PHP will save the QR codes properly now
    }
        // Submit via AJAX (no page reload)
</script>
</body>
</html>
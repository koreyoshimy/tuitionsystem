<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$teacherID = $_SESSION['username'];

// Get the subject this teacher teaches
$subjectStmt = $conn->prepare("SELECT s.subject_id, s.subject_name FROM subjects s 
                               JOIN teachers t ON s.subject_id = t.subject 
                               WHERE t.teacherID = ?");
$subjectStmt->bind_param("s", $teacherID);
$subjectStmt->execute();
$teacherSubject = $subjectStmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $_POST['subject_id'];
    $exam = $_POST['exam'];
    $stuID = $_POST['stuID'];
    $score = $_POST['score'];

    // Get subject name
    $subjectQuery = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    $subjectQuery->bind_param("i", $subject_id);
    $subjectQuery->execute();
    $subjectRow = $subjectQuery->get_result()->fetch_assoc();
    $subjectName = $subjectRow['subject_name'];

    $stmt = $conn->prepare("INSERT INTO exams (subject_id, exam, subject, stuID, teacherID, score) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $subject_id, $exam, $subjectName, $stuID, $teacherID, $score);

    $message = $stmt->execute() ? "Exam result saved successfully!" : "Error: " . $conn->error;
}

// AJAX: get students registered in the subject
if (isset($_GET['getStudents']) && isset($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    $students = $conn->prepare("SELECT DISTINCT c.username, c.name 
                                FROM children c
                                JOIN student_subjects ss ON c.username = ss.studentID
                                WHERE ss.subject_id = ?");
    $students->bind_param("i", $subject_id);
    $students->execute();
    $result = $students->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($result);
    exit();
}

// Fetch exam results
$exams = $conn->prepare("SELECT DISTINCT e.id, e.exam, e.subject, e.score, s.subject_id, c.name AS student_name 
                         FROM exams e
                         JOIN subjects s ON e.testID = s.subject_id
                         JOIN children c ON e.stuID = c.username
                         WHERE e.teacherID = ?");
$exams->bind_param("s", $teacherID);
$exams->execute();
$examResults = $exams->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Performance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

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
        </style>
</head>
<body>
       <div class="navbar-custom d-flex justify-content-between">
    <h1>ACE Tuition Management System</h1>
    <div class="d-flex align-items-center gap-3 text-white">
        <div class="text-end">
            <div><?php echo htmlspecialchars($username); ?></div> <!-- Use $username from session -->
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
        <h2>Exam Performance</h2>

<a href="add_performance.php" class="btn btn-primary mb-3">Add Exam Result</a>


        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <table id="examsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Subject</th>
                    <th>Subject Code</th>
                    <th>Exam</th>
                    <th>Student</th>
                    <th>Score</th>
                    <th>Actions</th> 
                </tr>
            </thead>
            <tbody>
                <?php foreach ($examResults as $exam): ?>
                <tr>
                    <td><?= htmlspecialchars($exam['id']) ?></td>
                    <td><?= htmlspecialchars($exam['subject']) ?></td>
                    <td><?= htmlspecialchars($exam['subject_id']) ?></td>
                    <td><?= htmlspecialchars($exam['exam']) ?></td>
                    <td><?= htmlspecialchars($exam['student_name']) ?></td>
                    <td><?= htmlspecialchars($exam['score']) ?></td>
                    <td>
            <a href="edit_exam.php?id=<?= $exam['id'] ?>" class="btn btn-sm btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="delete_exam.php?id=<?= $exam['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this exam?');">
                <i class="fas fa-trash-alt"></i> Delete
            </a>
        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Add Exam Modal -->
        <!-- <div class="modal fade" id="addExamModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Exam Result</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if ($teacherSubject): ?>
                                <input type="hidden" name="subject_id" value="<?= $teacherSubject['subject_id'] ?>">
                                <p><strong>Subject:</strong> <?= htmlspecialchars($teacherSubject['subject_name']) ?></p>
                            <?php else: ?>
                                <p class="text-danger">No subject assigned to you.</p>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Exam Type</label>
                                <select name="exam" class="form-control" required>
                                    <option value="Test">Test</option>-00
                                    <option value="Quiz">Quiz</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Student</label>
                                <select name="stuID" id="studentSelect" class="form-control" required>
                                    <option value="">Loading...</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Score</label>
                                <input type="number" name="score" class="form-control" min="0" max="100" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div> -->
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#examsTable').DataTable();

        const subjectId = <?= json_encode($teacherSubject['subject_id'] ?? '') ?>;

        if (subjectId) {
            $.get('performance.php?getStudents=1&subject_id=' + subjectId, function(data) {
                const students = JSON.parse(data);
                const select = $('#studentSelect');
                select.empty().append('<option value="">Select Student</option>');

                students.forEach(student => {
                    select.append(`<option value="${student.username}">${student.name}</option>`);
                });
            });
        }
    });
    </script>
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

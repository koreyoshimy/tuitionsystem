<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacherID = $_SESSION['username'];

// Get all subjects taught by this teacher
$subjects = $conn->query("SELECT subject_id, subject_name FROM subjects 
                         WHERE teacher_id = '$teacherID' ORDER BY subject_name");

// Get students when a subject is selected
$students = [];
if (isset($_GET['subject_id'])) {
    $subjectID = $_GET['subject_id'];
    
    // Verify the subject belongs to this teacher
    $checkSubject = $conn->query("SELECT subject_id FROM subjects 
                                WHERE subject_id = $subjectID AND teacher_id = '$teacherID'");
    if ($checkSubject->num_rows == 0) {
        die("Invalid subject selection");
    }

    // Get all students for this subject (modified query)
    $students = $conn->query("SELECT c.username, c.name 
                             FROM children c
                             JOIN student_subjects ss ON c.username = ss.stuID
                             WHERE ss.subject_id = $subjectID
                             ORDER BY c.name");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $subjectID = $_POST['subject_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing attendance for this date and subject
        $deleteStmt = $conn->prepare("DELETE FROM attendance 
                                    WHERE date = ? AND subjectID = ? AND teacherID = ?");
        $deleteStmt->bind_param("sis", $date, $subjectID, $teacherID);
        $deleteStmt->execute();
        
        // Insert new attendance records
        $insertStmt = $conn->prepare("INSERT INTO attendance 
                                    (date, teacherID, subjectID, stuID, student_name, status) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($_POST['attendance'] as $stuID => $status) {
            // Get student name
            $studentName = '';
            foreach ($_POST['student_names'] as $id => $name) {
                if ($id == $stuID) {
                    $studentName = $name;
                    break;
                }
            }
            
            $insertStmt->bind_param(
                "ssisss", 
                $date, 
                $teacherID, 
                $subjectID, 
                $stuID, 
                $studentName, 
                $status
            );
            $insertStmt->execute();
        }
        
        $conn->commit();
        $success = "Attendance recorded successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error recording attendance: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .attendance-table th, .attendance-table td {
            vertical-align: middle;
        }
        .status-present { background-color: #d4edda; }
        .status-absent { background-color: #f8d7da; }
        .status-excused { background-color: #fff3cd; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Take Attendance</h3>
        </div>
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="subject_id">Subject</label>
                            <select name="subject_id" id="subject_id" class="form-control" required>
                                <option value="">-- Select Subject --</option>
                                <?php while($subject = $subjects->fetch_assoc()): ?>
                                    <option value="<?= $subject['subject_id'] ?>" 
                                        <?= isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['subject_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['subject_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            Load Students
                        </button>
                    </div>
                </div>
            </form>

            <?php if (!empty($students) && $students->num_rows > 0): ?>
            <form method="POST">
                <input type="hidden" name="subject_id" value="<?= $_GET['subject_id'] ?>">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered attendance-table">
                        <thead class="thead-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="35%">Student Name</th>
                                <th width="60%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php while($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td>
                                    <?= htmlspecialchars($student['name']) ?>
                                    <input type="hidden" name="student_names[<?= $student['username'] ?>]" 
                                           value="<?= htmlspecialchars($student['name']) ?>">
                                </td>
                                <td>
                                    <select name="attendance[<?= $student['username'] ?>]" 
                                            class="form-control status-select">
                                        <option value="Present" selected class="status-present">Present</option>
                                        <option value="Absent" class="status-absent">Absent</option>
                                        <option value="Excused" class="status-excused">Excused</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        Submit Attendance
                    </button>
                </div>
            </form>
            <?php elseif (isset($_GET['subject_id'])): ?>
                <div class="alert alert-info">No students found for this subject.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add visual feedback when status changes
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            this.className = 'form-control status-' + this.value.toLowerCase();
        });
    });
</script>
</body>
</html>
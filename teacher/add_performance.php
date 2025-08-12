<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$teacherID = $_SESSION['username'];

// Get the subject the teacher teaches
$subjectStmt = $conn->prepare("SELECT s.subject_id, s.subject_name, s.subject_id 
                               FROM subjects s 
                               JOIN teachers t ON s.subject_name = t.subject 
                               WHERE t.teacherID = ?");
$subjectStmt->bind_param("s", $teacherID);
$subjectStmt->execute();
$teacherSubject = $subjectStmt->get_result()->fetch_assoc();

// Get all students registered for this subject
$students = [];
if ($teacherSubject) {
    $studentStmt = $conn->prepare("SELECT DISTINCT c.username, c.name 
                                   FROM children c
                                   JOIN subjects ss ON c.username = ss.username
                                   WHERE ss.subject_id = ?");
    $studentStmt->bind_param("i", $teacherSubject['subject_id']);
    $studentStmt->execute();
    $students = $studentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $teacherSubject['subject_id'];
    $exam = $_POST['exam'];
    $scores = $_POST['scores']; // This will be an array
    
    foreach ($scores as $stuID => $score) {
        if (!empty($score)) {
            $stmt = $conn->prepare("INSERT INTO exams 
                                   (testID, exam, subject, stuID, teacherID, score) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", 
                $subject_id,
                $exam,
                $teacherSubject['subject_name'],
                $stuID,
                $teacherID,
                $score
            );
            $stmt->execute();
        }
    }
    
    $message = "Exam results saved successfully!";
      header("Location: performance.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Exam Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .score-input {
            width: 80px;
        }
        .table-container {
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="my-4">Add Exam Results</h2>
    <a href="performance.php" class="btn btn-secondary mb-3">Back</a>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Exam results saved successfully!</div>
    <?php endif; ?>

    <?php if ($teacherSubject): ?>
    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control" 
                       value="<?= htmlspecialchars($teacherSubject['subject_name']) ?>" readonly>
                <select name="exam" class="form-control" required>
                    <option value="">Select Exam Type</option>
                    <option value="Test">Test</option>
                    <option value="Quiz">Quiz</option>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Subject</th>
                        <th>Subject Code</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($student['username']) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td><?= htmlspecialchars($teacherSubject['subject_name']) ?></td>
                        <td><?= htmlspecialchars($teacherSubject['subject_id']) ?></td>
                        <td>
                            <input type="number" name="scores[<?= $student['username'] ?>]" 
                                   class="form-control score-input" min="0" max="100" value="0">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary">Save All Scores</button>
    </form>
    <?php else: ?>
        <div class="alert alert-danger">No subject is assigned to you.</div>
    <?php endif; ?>
</div>
</body>
</html>
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if (isset($_GET['subject_id']) && isset($_GET['exam_type'])) {
    $subject_id = $_GET['subject_id'];
    $exam_type = $_GET['exam_type'];
    
    // Get students registered for this subject
    $query = "SELECT DISTINCT c.username, c.name 
              FROM children c
              JOIN student_subjects ss ON c.username = ss.studentID
              WHERE ss.subject_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($students);
    exit();
}

header("HTTP/1.1 400 Bad Request");
exit();
?>
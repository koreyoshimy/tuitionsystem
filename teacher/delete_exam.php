<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$teacherID = $_SESSION['username'];

if (!isset($_GET['id'])) {
    die("Missing exam ID.");
}

$examID = $_GET['id'];

// Make sure exam belongs to the logged-in teacher
$deleteStmt = $conn->prepare("DELETE FROM exams WHERE id = ? AND teacherID = ?");
$deleteStmt->bind_param("is", $examID, $teacherID);

if ($deleteStmt->execute()) {
    header("Location: performance.php?deleted=1");
    exit();
} else {
    echo "Delete failed: " . $conn->error;
}
?>

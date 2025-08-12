<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Get the course ID from the URL
if (isset($_GET['id'])) {
    $courseId = $_GET['id'];
} else {
    echo "Course ID is required.";
    exit();
}

// Delete the course from the database
$query = "DELETE FROM subjects WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $courseId);

if ($stmt->execute()) {
    echo "Course deleted successfully!";
    header('Location: ./book.php'); // Redirect to the dashboard
    exit();
} else {
    echo "Error deleting course.";
}
?>

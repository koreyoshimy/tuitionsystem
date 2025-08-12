<?php
session_start();
include 'db.php';
$username = $_SESSION['username'];
// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
   
    $time = $_POST['time'];
    $section = $_POST['section'];
    $subject = $_POST['subject'];
    $age = $_POST['age'];

    // Insert data into the subjects table
    $sql = "INSERT INTO subjects (subject_name,time, section, age, username) 
    VALUES ( '$subject','$time', '$section','$age', '$username')";

if (mysqli_query($conn, $sql)) {
    // Redirect to course.php
    header("Location: dashboard.php");
    exit();
} else {
    echo "Error: " ;
}
}
?>

<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qr_code'])) {
        $qrCode = $_POST['qr_code'];

        // Get student details
        $selectStmt = $conn->prepare("SELECT username, name FROM children WHERE username = ?");
        $selectStmt->bind_param("s", $qrCode);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $username = $row["username"];
            $stuName = $row["name"];
            
            // Get current date and time
            $timestamp = date("Y-m-d H:i:s");

            // Insert attendance with all time fields
            $insertStmt = $conn->prepare("INSERT INTO student_attendance 
                                        (username, stuName,timestamp) 
                                        VALUES (?, ?, ?)");
            $insertStmt->bind_param("sssss", $username, $stuName, $timestamp);
            
            if ($insertStmt->execute()) {
                header("Location: attendance.php");
                exit();
            } else {
                echo "Error inserting attendance: " . $conn->error;
            }
        } else {
            echo "No student found with that QR Code.";
        }
    } else {
        echo "<script>alert('No QR code received.'); window.location.href = 'attendance.php';</script>";
    }
}
?>
<?php
session_start();
include("db.php");

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo "Not authorized";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $date = $_POST['qr_date'];
    $day = date('l', strtotime($date));
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

    // Clear existing QR codes
    $clear = $conn->prepare("DELETE FROM qr_codes WHERE class = ? AND subject = ? AND date = ?");
    $clear->bind_param("sss", $class, $subject, $date);
    $clear->execute();

    // Get all students in class
    $stmt = $conn->prepare("SELECT username FROM children WHERE class = ?");
    $stmt->bind_param("s", $class);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($student = $result->fetch_assoc()) {
        // Use provided generated code (you could generate per student if needed)
        $random_code = bin2hex(random_bytes(16)); // Optional: use per student or reuse $_POST['generatedCode']

        $insert = $conn->prepare("INSERT INTO qr_codes 
            (username, class, subject, date, day, code_value, expiry) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $insert->bind_param("sssssss", $student['username'], $class, $subject, $date, $day, $random_code, $expiry);
        $insert->execute();
    }

    echo "success";
}
?>

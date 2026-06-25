<?php
session_start();
include("db.php");

// Check if student is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    $qr_code = $_POST['qr_code'];
    $student_username = $_SESSION['username'];
    $student_name = $_SESSION['name']; // Assuming name is in session
    
    // 1. Verify QR code
    $stmt = $conn->prepare("SELECT q.*, t.username as teacher_username 
                          FROM qr_codes q
                          JOIN teacher_classes t ON q.class = t.class
                          WHERE q.code_value = ? AND q.expiry > NOW()");
    $stmt->bind_param("s", $qr_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die(json_encode(['success' => false, 'message' => 'Invalid or expired QR code']));
    }
    
    $qr_data = $result->fetch_assoc();
    
    // 2. Check student class
    $stmt = $conn->prepare("SELECT class FROM children WHERE username = ?");
    $stmt->bind_param("s", $student_username);
    $stmt->execute();
    $student_class = $stmt->get_result()->fetch_assoc()['class'];
    
    if ($student_class != $qr_data['class']) {
        die(json_encode(['success' => false, 'message' => 'You are not in this class']));
    }
    
    // 3. Check existing attendance
    $stmt = $conn->prepare("SELECT id FROM attendance 
                          WHERE username = ? AND date = ? AND subject = ?");
    $stmt->bind_param("sss", $student_username, $qr_data['date'], $qr_data['subject']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        die(json_encode(['success' => false, 'message' => 'Attendance already recorded']));
    }
    
    // 4. Record attendance
    $insert = $conn->prepare("INSERT INTO attendance 
                            (username, stuName, class, subject, status, date, day, time, recorded_by, method) 
                            VALUES (?, ?, ?, ?, 'present', ?, ?, ?, ?, 'qr')");
    $time = date("H:i:s");
    $day = date('l', strtotime($qr_data['date']));
    
    $insert->bind_param("ssssssss", 
        $student_username,
        $student_name,
        $qr_data['class'],
        $qr_data['subject'],
        $qr_data['date'],
        $day,
        $time,
        $qr_data['teacher_username']
    );
    
    if ($insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error recording attendance']);
    }
    
    exit();
}
?>
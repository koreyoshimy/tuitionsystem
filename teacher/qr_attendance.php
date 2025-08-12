<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qr_code'])) {
        $qr_code = $_POST['qr_code'];
        
        // Verify QR code
        $stmt = $conn->prepare("SELECT username FROM qr_codes 
                              WHERE code_value = ? AND used = 0 AND expiry > NOW()");
        $stmt->bind_param("s", $qr_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $username = $data['username'];
            
            // Get student details
            $stmt = $conn->prepare("SELECT name, class FROM children WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            
            // Record attendance
            $date = date("Y-m-d");
            $time = date("H:i:s");
            $insert = $conn->prepare("INSERT INTO attendance 
                                    (username, stuName, class, status, date, time) 
                                    VALUES (?, ?, ?, 'present', ?, ?)");
            $insert->bind_param("sssss", $username, $student['name'], $student['class'], $date, $time);
            $insert->execute();
            
            // Mark QR code as used
            $update = $conn->prepare("UPDATE qr_codes SET used = 1 WHERE code_value = ?");
            $update->bind_param("s", $qr_code);
            $update->execute();
            
            echo json_encode(['success' => true, 'message' => 'Attendance recorded!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired QR code']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No QR code provided']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance</title>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.4/dist/html5-qrcode.min.js"></script>
</head>
<body>
    <div style="width: 500px; margin: 0 auto; text-align: center;">
        <h2>Scan QR Code for Attendance</h2>
        <div id="qr-reader" style="width: 100%"></div>
        <div id="qr-reader-results"></div>
    </div>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanning
            html5QrcodeScanner.clear();
            
            // Send to server
            fetch('qr_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'qr_code=' + encodeURIComponent(decodedText)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('qr-reader-results').innerHTML = 
                    data.success ? 
                    '<p style="color: green;">' + data.message + '</p>' :
                    '<p style="color: red;">' + data.message + '</p>';
            });
        }

        var html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>
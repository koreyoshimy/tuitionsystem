<?php
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qr_code'])) {
        $qrCode = $_POST['qr_code'];

        $selectStmt = $conn->prepare("SELECT stuID FROM student_attendance WHERE generated_code = :generated_code");
        $selectStmt->bindParam(":generated_code", $qrCode, PDO::PARAM_STR);

        if ($selectStmt->execute()) {
            $result = $selectStmt->fetch();
            if ($result !== false) {
                $studentID = $result["stuID"];
                $timeIn =  date("Y-m-d H:i:s");
            } else {
                echo "No student found in QR Code";
            }
        } else {
            echo "Failed to execute the statement.";
        }


        try {
            $stmt = $conn->prepare("INSERT INTO attendance (stuID, time) VALUES (:tbl_student_id, :time)");
            
            $stmt->bindParam(":stuID", $stuID, PDO::PARAM_STR); 
            $stmt->bindParam(":time", $time, PDO::PARAM_STR); 

            $stmt->execute();

            header("Location: http://localhost/tms/student/attendance.php");

            exit();
        } catch (PDOException $e) {
            echo "Error:" . $e->getMessage();
        }

    } else {
        echo "
            <script>
                alert('Please fill in all fields!');
                window.location.href = 'http://localhost/tms/student/attendance.php';
            </script>
        ";
    }
}
?>

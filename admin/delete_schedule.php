<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if schedule ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manageschedule.php?error=No schedule ID provided");
    exit();
}

$schedule_id = $_GET['id'];
$username = $_SESSION['username'];

// Verify the user has permission to delete this schedule
// (Assuming teachers can only delete their own schedules)
$verifyQuery = "SELECT * FROM schedules WHERE id = ? AND teacher = ?";
$stmt = $conn->prepare($verifyQuery);
$stmt->bind_param("is", $schedule_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Schedule doesn't exist or doesn't belong to this teacher
    header("Location: manageschedule.php?error=Schedule not found or unauthorized");
    exit();
}

// Delete the schedule
$deleteQuery = "DELETE FROM schedules WHERE id = ?";
$stmt = $conn->prepare($deleteQuery);
$stmt->bind_param("i", $schedule_id);

if ($stmt->execute()) {
    // Success - redirect back to schedule page with success message
    header("Location: manageschedule.php?success=Schedule deleted successfully");
} else {
    // Error - redirect back with error message
    header("Location: manageschedule.php?error=Error deleting schedule: " . $conn->error);
}

$stmt->close();
$conn->close();
exit();
?>
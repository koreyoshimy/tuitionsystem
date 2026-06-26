<?php
// delete_user.php
require_once 'db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Get user ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
$user = $stmt->num_rows > 0;

if ($user) {
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $_SESSION['message'] = "User deleted successfully";
} else {
    $_SESSION['error'] = "User not found";
}

header("Location: manageuser.php");
exit;
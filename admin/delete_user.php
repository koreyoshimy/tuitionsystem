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
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($user) {
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "User deleted successfully";
} else {
    $_SESSION['error'] = "User not found";
}

header("Location: manageuser.php");
exit;
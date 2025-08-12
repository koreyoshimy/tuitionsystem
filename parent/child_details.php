<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Child ID not specified.");
}

$childId = intval($_GET['id']);

// Fetch child details
$query = "SELECT * FROM children WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $childId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Child not found.");
}

$child = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <a href="dashboard.php" class="btn btn-secondary mb-4">&larr; Back to Dashboard</a>
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Child Details</h3>
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($child['name']); ?></p>
            <p><strong>Birth Date:</strong> <?php echo htmlspecialchars($child['birth_date']); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($child['gender']); ?></p>
            <p><strong>School:</strong> <?php echo htmlspecialchars($child['school']); ?></p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

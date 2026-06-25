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

// Fetch child data
$query = "SELECT * FROM children WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $childId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Child not found.");
}

$child = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
  
    $school = trim($_POST['school']);

    $updateQuery = "UPDATE children SET name = ?, birth_date = ?, gender = ?,  school = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssssi", $name, $birth_date, $gender, $school, $childId);

    if ($updateStmt->execute()) {
        header("Location: dashboard.php?updated=1");
        exit();
    } else {
        $error = "Failed to update child information.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Child</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <style>{}
</style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <a href="dashboard.php" class="btn btn-secondary mb-4">&larr; Back to Dashboard</a>
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h3>Edit Child Information</h3>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($child['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Birth Date</label>
                    <input type="date" name="birth_date" class="form-control" value="<?php echo htmlspecialchars($child['birth_date']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control" required>
                        <option value="Male" <?php if ($child['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if ($child['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                        <option value="Other" <?php if ($child['gender'] == 'Other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">School</label>
                    <input type="text" name="school" class="form-control" value="<?php echo htmlspecialchars($child['school']); ?>" required>
                </div>
                <button type="submit" class="btn btn-success">Update Child</button>
                <button type="button" class="btn btn-danger" onclick="window.location.href='dashboard.php'">Cancel</button>

            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

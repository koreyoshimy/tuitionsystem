<?php
// add_user.php
require_once 'db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $role = trim($_POST['role']);

    // Basic validation
    if (empty($username) || empty($name) || empty($role)) {
        $error = "All fields are required.";
    } else {
        // Check for duplicate username
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, name, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $name, $role);

            if ($stmt->execute()) {
                $_SESSION['message'] = "User added successfully.";
                header("Location: manageuser.php");
                exit;
            } else {
                $error = "Database error. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Add New User</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="parent">Parent</option>
                    <option value="teacher" selected>Teacher</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
            <a href="manageuser.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
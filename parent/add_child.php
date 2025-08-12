<?php
session_start();
include 'db.php'; // your database connection

// Check if the parent is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $child_name = trim($_POST['child_name']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $grade = $_POST['grade'];
    $school = trim($_POST['school']);
    $relationship_type = $_POST['relationship_type'];

    $parent_username = $_SESSION['username'];

    // Insert into children table
    $childInsertQuery = "INSERT INTO children (name, birth_date, gender, grade, school) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($childInsertQuery);

    if ($stmt) {
        $stmt->bind_param("sssis", $child_name, $birth_date, $gender, $grade, $school);
        if ($stmt->execute()) {
            $child_id = $stmt->insert_id; // Get the ID of the newly inserted child

            // Now insert into parent_child table
            $linkQuery = "INSERT INTO parent_child (parent_username, child_id, relationship_type) VALUES (?, ?, ?)";
            $linkStmt = $conn->prepare($linkQuery);

            if ($linkStmt) {
                $linkStmt->bind_param("sis", $parent_username, $child_id, $relationship_type);
                if ($linkStmt->execute()) {
                    $message = "Child added successfully!";
                } else {
                    $message = "Failed to link child to parent: " . $linkStmt->error;
                }
                $linkStmt->close();
            } else {
                $message = "Failed to prepare link query: " . $conn->error;
            }
        } else {
            $message = "Failed to add child: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Failed to prepare child insert: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Child</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4">Add New Child</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="add_child.php">
        <div class="mb-3">
            <label for="child_name" class="form-label">Child's Name</label>
            <input type="text" class="form-control" id="child_name" name="child_name" required>
        </div>

        <div class="mb-3">
            <label for="birth_date" class="form-label">Birth Date</label>
            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
        </div>

        <div class="mb-3">
            <label for="gender" class="form-label">Gender</label>
            <select class="form-select" id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="female">Female</option>
                <option value="male">Male</option>
            </select>
    </div>
        <div class="mb-3">
            <label for="school" class="form-label">School</label>
            <input type="text" class="form-control" id="school" name="school" required>
        </div>

        <div class="mb-3">
            <label for="relationship_type" class="form-label">Relationship</label>
            <select class="form-select" id="relationship_type" name="relationship_type" required>
                <option value="">Select Relationship</option>
                <option value="Father">Father</option>
                <option value="Mother">Mother</option>
                <option value="Guardian">Guardian</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Add Child</button>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>

</body>
</html>

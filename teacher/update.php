<?php
session_start();
include("db.php");

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch the logged-in user's username from the session
$username = $_SESSION['username'];

// Fetch user details from the database using the username
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Failed to prepare the statement: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No user found with the given username.";
    exit();
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    // Validate inputs
    if (empty($name) || empty($email)) {
        echo "<script>alert('Name and email are required.');</script>";
    } else {
        // Update user data in the database
        $update_query = "UPDATE users SET name = ?, email = ?, gender=? WHERE username = ?";
        $stmt = $conn->prepare($update_query);

        if (!$stmt) {
            die("Failed to prepare the update statement: " . $conn->error);
        }

        $stmt->bind_param("ssss", $name, $email, $gender, $username);

        if ($stmt->execute()) {
            // Update session data
            $_SESSION['name'] = $name;

            echo "<script>
                alert('Profile updated successfully!');
                window.location.href = 'dashboard.php'; // Redirect after success
            </script>";
        } else {
            echo "<script>alert('Failed to update the profile.');</script>";
        }
    }
}
?>

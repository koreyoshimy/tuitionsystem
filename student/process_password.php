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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['password'];
    $new_password1 = $_POST['password1'];
    $new_password2 = $_POST['password2'];

    // Validate inputs
    if (empty($old_password) || empty($new_password1) || empty($new_password2)) {
        echo "<script>alert('All fields are required.');</script>";
    } elseif ($new_password1 !== $new_password2) {
        echo "<script>alert('New passwords do not match.');</script>";
    } else {
        // Fetch the current password from the database
        $query = "SELECT password FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            die("Failed to prepare the statement: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo "<script>alert('User not found.');</script>";
            exit();
        }

        $user = $result->fetch_assoc();
        $stored_password = $user['password'];

        // Verify the old password
        if ($old_password !== $stored_password) {
            echo "<script>alert('Old password is incorrect.');</script>";
        } else {
            // Update the password in the database
            $update_query = "UPDATE users SET password = ? WHERE username = ?";
            $stmt = $conn->prepare($update_query);

            if (!$stmt) {
                die("Failed to prepare the update statement: " . $conn->error);
            }

            $stmt->bind_param("ss", $new_password1, $username);

            if ($stmt->execute()) {
                echo "<script>
                    alert('Password changed successfully!');
                    window.location.href = 'editprofile.php'; // Redirect after success
                </script>";
            } else {
                echo "<script>alert('Failed to change the password.');</script>";
            }
        }
    }
}
?>

<?php
session_start();
include 'db.php'; // Include database connection

$error = $success_message = "";
$show_reset_form = false;

// Handle form submission for username and email verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['new_password'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if (empty($username) || empty($email)) {
        $error = "Both username and email are required.";
    } else {
        $query = "SELECT * FROM users WHERE username = ? AND email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $show_reset_form = true; // Display the reset password form
        } else {
            $error = "Username or email does not match.";
        }
    }
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
        $error = "Session expired. Please restart the process.";
    } else {
        $username = $_SESSION['username'];
        $email = $_SESSION['email'];
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        if ($new_password === $confirm_password) {
            $update_query = "UPDATE users SET password = ? WHERE username = ? AND email = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sss", $new_password, $username, $email);
            $update_stmt->execute();

            $success_message = "Your password has been successfully updated!";
            unset($_SESSION['username']);
            unset($_SESSION['email']);
        } else {
            $error = "Passwords do not match.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .logo img {
            max-width: 150px;
            margin: 20px auto;
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .input-group {
            margin: 10px 0;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
        }
        .input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .error, .success {
            font-size: 14px;
            margin-bottom: 15px;
            color: red;
        }
        .success {
            color: green;
        }
        .login-btn {
            width: 100%;
            padding: 10px;
            background-color: #4a9cf0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-btn:hover {
            background-color: #3a8ad0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="img/logo.jpg" alt="Logo">
        </div>
        <div class="header">Forgot Password</div>

        <?php if (!empty($error)) { echo "<div class='error'>$error</div>"; } ?>
        <?php if (!empty($success_message)) { echo "<div class='success'>$success_message</div>"; } ?>

        <?php if (!$show_reset_form && empty($success_message)) { ?>
        <!-- Username and Email Form -->
        <form method="POST" action="">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="login-btn">Submit</button>
        </form>
        <?php } ?>

        <?php if ($show_reset_form) { ?>
        <!-- Reset Password Form -->
        <form method="POST" action="">
            <div class="input-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="login-btn">Change</button>
        </form>
        <?php } ?>

        <div class="subheading">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>

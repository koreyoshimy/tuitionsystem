<?php
session_start();
include('db.php'); // Database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Sanitize inputs to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Query to verify username, password, and fetch role
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        // Authentication successful
        $userData = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $userData['username'];
        $_SESSION['name'] = $userData['name'];
        $_SESSION['role'] = $userData['role']; // Fetching user role

        // Redirect based on role
        switch ($userData['role']) {
            case 'student':
                header("Location: ./student/dashboard.php");
                break;
            case 'admin':
                header("Location: ./admin/dashboard.php");
                break;
            case 'teacher':
                header("Location: ./teacher/dashboard.php");
                break;
            case 'parent':
                header("Location: ./parent/dashboard.php");
                break;
            default:
                // Redirect to a generic error page if role is not recognized
                header("Location: error.php?message=Invalid role");
                break;
        }
        exit();
    } else {
        // Authentication failed
        $error = "Invalid username or password";
        header("Location: login.php?error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/tms/css/loginstyle.css">
    <title>Login Page</title>
    
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="img/logo.jpg" alt="Logo">
        </div>
        <div class="header">
            Login    
        </div>
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>
        <form method="POST" action="">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
            <button type="submit" class="login-btn">Login</button>
            <div class="signup">
                Not a Member? <a href="register.php">Sign Up</a>
            </div>
        </form>
    </div>
</body>
</html>

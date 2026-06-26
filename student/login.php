<?php
session_start();
include('db.php'); // Database connection file
require_once('../csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_verify();

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if ($userData && password_verify($password, $userData['password'])) {
        session_regenerate_id(true);
        $_SESSION['username'] = $userData['username'];
        $_SESSION['name'] = $userData['name'];
        $_SESSION['role'] = $userData['role'];

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
            <?php echo csrf_field(); ?>
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

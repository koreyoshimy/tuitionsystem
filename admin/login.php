<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to verify username and password
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $userData = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $userData['username'];
        $_SESSION['name'] = $userData['name'];
        header("Location: ./dashboard.php");
        exit(); // ✅ exit after redirect
    } else {
        // Redirect with error
        header("Location: login.php?error=Invalid+username+or+password");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
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

        form {
            margin-top: 20px;
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
            width: 80%; 
            max-width: 300px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin: 0 auto;
            display: block;
        }

        .forgot-password {
            margin: 15px 0;
        }

        .forgot-password a {
            color: #4a9cf0;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 80%; 
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

        .signup {
            margin-top: 15px;
            font-size: 14px;
        }

        .signup a {
            color: #4a9cf0;
            text-decoration: none;
        }

        .signup a:hover {
            text-decoration: underline;
        }

        .error-message {
            border: 2px solid red;
            background-color: white;
            color: red;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="img/logo.jpg" alt="Logo">
        </div>
        <div class="header">
           Admin Login    
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
            <a href="/tms/login.php">Student</a> | <a href="/tms/teacher/login.php">Teacher</a>
            </div>
        </form>
    </div>
</body>
</html>
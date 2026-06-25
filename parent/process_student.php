<?php
session_start();
include('db.php'); // Database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
<<<<<<< HEAD
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if ($userData && password_verify($password, $userData['password'])) {
        $_SESSION['username'] = $userData['username'];
        $_SESSION['name'] = $userData['name'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
        header("Location: login.php?error=" . urlencode($error));
=======
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
         // Authentication successful
         $userData = mysqli_fetch_assoc($result);
         $_SESSION['username'] = $userData['username'];
         $_SESSION['name'] = $userData['name'];
        header("Location: dashboard.php"); // Redirect to a welcome page on successful login
        exit();
    } else {
        // Authentication failed
        $error = "Invalid username or password";
        header("Location: login.php?error=" . urlencode($error)); // Redirect with error message
>>>>>>> 228a4315a4c55a5067cfe2fd21f8317fe8124e6d
        exit();
    }
}
?>

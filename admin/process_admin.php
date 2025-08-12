
<?php
session_start();

include 'db.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
         // Authentication successful
         $userData = mysqli_fetch_assoc($result);
         $_SESSION['username'] = $userData['username'];
         $_SESSION['name'] = $userData['name'];
        header("Location: admin.php"); // Redirect to a welcome page on successful login
        exit();
    } else {
        // Authentication failed
        $error_message = "Invalid username or password";
    }
}

?>
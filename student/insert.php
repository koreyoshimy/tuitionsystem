<?php
// Start the session
session_start();

// Include the database connection file
include "db.php"; // Make sure db.php contains your database connection logic

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim and sanitize input
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $password = trim($_POST['password']);
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $gender = trim(mysqli_real_escape_string($conn, $_POST['gender']));

    // Validate inputs
    if (empty($username) || empty($name) || empty($password) || empty($email) || empty($gender)) {
        echo "<script>alert('All fields are required.'); window.location.href='register.php';</script>";
        exit();
    }

    // Validate username (letters, numbers, underscores, 3-20 characters)
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        echo "<script>alert('Invalid username. Only letters, numbers, and underscores are allowed, and it must be 3-20 characters long.'); window.location.href='register.php';</script>";
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email address.'); window.location.href='register.php';</script>";
        exit();
    }

    // Validate password (min 8 characters, at least 1 letter and 1 number)
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
        echo "<script>alert('Password must be at least 8 characters long and include at least one letter and one number.'); window.location.href='register.php';</script>";
        exit();
    }

    // Hash the password before storing it in the database
    //$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check if the username already exists
    $checkQuery = "SELECT * FROM users WHERE username = '$username'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        // Username already exists
        echo "<script>alert('Username already exists! Please choose a different one.'); window.location.href='register.php';</script>";
        exit();
    }

    // Insert user into the database
    $query = "INSERT INTO users (username, name, password, email, gender) VALUES ('$username', '$name', '$password', '$email', '$gender')";
    if (mysqli_query($conn, $query)) {
        // Registration successful
        echo "<script>alert('Registration successful! Please log in.'); window.location.href='login.php';</script>";
        exit();
    } else {
        // Error inserting data
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Redirect if the page is accessed without submitting the form
    header("Location: register.php");
    exit();
}
?>

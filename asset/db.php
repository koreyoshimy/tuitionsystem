<?php
$host = "localhost";
$db_name = "tuitiondb";
$username = "root";
$password = "";

//Create connection
$conn = mysqli_connect($host, $username, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
//echo "Connected successfully";
?>
 
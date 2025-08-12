<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Get the course ID from the URL
if (isset($_GET['id'])) {
    $courseId = $_GET['id'];
} else {
    echo "Course ID is required.";
    exit();
}

// Fetch the course details from the database
$query = "SELECT * FROM subjects WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $courseId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No course found with the given ID.";
    exit();
}

$course = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get updated data from the form
    $subjectName = $_POST['subject_name'];
    $time = $_POST['time'];
    $subject_id = $_POST['subject_id'];
    $age = $_POST['age'];

    // Update the course details in the database
    $updateQuery = "UPDATE subjects SET subject_name = ?, time = ?, subject_id = ?, age = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssii", $subjectName, $time, $subject_id, $age, $courseId);
    
    if ($updateStmt->execute()) {
        echo "Course updated successfully!";
        header('Location: book.php'); // Redirect to the dashboard
        exit();
    } else {
        echo "Error updating course.";
    }
}

// Assuming you already have database connection ($conn) established



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="//cdn.datatables.net/2.2.1/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Add styling for the form */
               /* General Styling */
               *{
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins" , sans-serif;
}
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            background-color: #f4f4f4;
        }

        /* Sidebar Styling */
   
.sidebar {
    width: 250px;
    background-color:  #2b2640; /* Dark blue background */
    color: white;
    padding: 20px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    height: 100%;
    position: fixed;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 22px;
    font-weight: 600;
}

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    margin: 15px 0;
}

.sidebar ul li a {
    text-decoration: none;
    color: white;
    font-size: 16px;
    display: block;
    padding: 12px 18px;
    border-radius: 6px;
    transition: background-color 0.3s, padding-left 0.3s;
}

.sidebar ul li a:hover,
.sidebar ul li a.active {
    background-color: #002244; /* Slightly lighter dark blue for hover effect */
    padding-left: 25px; /* Slightly increase padding for hover effect */
}

/* Expandable Submenu Styling */
.sidebar ul .submenu {
    display: none;
    padding-left: 20px;
}

.sidebar ul .submenu li {
    margin: 5px 0;
}

.sidebar ul .submenu li a {
    font-size: 14px;
}

        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align:left;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

    </style>
</head>
<body>
<div class="sidebar">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <ul>
        <li class="toggle">
            <a href="javascript:void(0);">My Profile <i class="fa fa-caret-down"></i></a>
            <ul class="submenu">
                <li><a href="dashboard.php" class="<?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="changepassword.php" class="<?php echo ($activePage === 'changepassword') ? 'active' : ''; ?>">Change Password</a></li>
            </ul>      
        <li><a href="book.php" class="<?php echo ($activePage === 'book') ? 'active' : ''; ?>">Subject</a></li>
      
        <li><a href="performance.php" class="<?php echo ($activePage === 'performance') ? 'active' : ''; ?>">Performance</a></li>
        <li><a href="attendance.php" class="<?php echo ($activePage === 'attendance') ? 'active' : ''; ?>">Attendance</a></li>
        <li><a href="payment.php" class="<?php echo ($activePage === 'payment') ? 'active' : ''; ?>">Payment</a></li>
        <li><a href="logout.php" class="<?php echo ($activePage === 'logout') ? 'active' : ''; ?>">Log Out</a></li>
    </ul>
    </div>
<div class="container">
    <h1>Edit Course</h1>
    <form method="POST">
        <label for="subject_name">Subject Name:</label>
        <input type="text" id="subject_name" name="subject_name" value="<?php echo htmlspecialchars($course['subject_name']); ?>" required>

        <label for="time">Time:</label>
        <input type="text" id="time" name="time" value="<?php echo htmlspecialchars($course['time']); ?>" required>

        <label for="subject_id">Subject Code:</label>
        <input type="text" id="subject_id" name="subject_id" value="<?php echo htmlspecialchars($course['subject_id']); ?>" required>

        <label for="age">Age:</label>
        <input type="number" id="age" name="age" value="<?php echo isset($course['age']) ? htmlspecialchars($course['age']) : ''; ?>" required>
        <button type="submit">Update Course</button>
        <button type="button" onclick="window.location.href='book.php'">Cancel</button>
    </form>
</div>
<script>
    // Add click event to toggle the submenu
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const submenu = this.querySelector('.submenu');
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
</script>
</body>
</html>

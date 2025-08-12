<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$activePage = basename($_SERVER['PHP_SELF'], ".php");

// Get schedule ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manageschedule.php");
    exit();
}

$id = $_GET['id'];
$error = "";
$success = "";

// Fetch current schedule data
$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if (!$schedule) {
    header("Location: manageschedule.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $day = $_POST['day'];
    $time = $_POST['time'];
    $duration = $_POST['duration'];
    $room = $_POST['room'];
    $teacher = $_POST['teacher'];

    $updateStmt = $conn->prepare("UPDATE schedules SET class=?, subject=?, day=?, time=?, duration=?,room=?, teacher=? WHERE id=?");
    $updateStmt->bind_param("sssssssi", $class, $subject, $day, $time, $duration, $room, $teacher, $id);

    if ($updateStmt->execute()) {
        header("Location: manageschedule.php?success=Schedule updated successfully!");
        exit();
    } else {
        $error = "Failed to update the schedule.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Schedule</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            padding: 40px;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #aaa;
            border-radius: 5px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background: #4a9cf0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background: #3a8ad0;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2><i class="fas fa-edit"></i> Edit Schedule</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="class">Class</label>
            <select name="class" id="class" required>
                <option value="">-- Select Class --</option>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="Year <?php echo $i; ?>" <?php echo ($schedule['class'] === "Year $i") ? 'selected' : ''; ?>>
                        Year <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <select name="subject" id="subject" required>
                <?php
                $subjects = ["Mathematics", "Science", "Bahasa Melayu", "English"];
                foreach ($subjects as $subj):
                ?>
                    <option value="<?php echo $subj; ?>" <?php echo ($schedule['subject'] === $subj) ? 'selected' : ''; ?>>
                        <?php echo $subj; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="day">Day</label>
            <select name="day" id="day" required>
                <?php
                $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                foreach ($days as $d):
                ?>
                    <option value="<?php echo $d; ?>" <?php echo ($schedule['day'] === $d) ? 'selected' : ''; ?>>
                        <?php echo $d; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="time">Time</label>
            <input type="time" name="time" id="time" value="<?php echo $schedule['time']; ?>" required>
        </div>

        <div class="form-group">
            <label for="duration">Duration (minutes)</label>
            <input type="number" name="duration" id="duration" min="60" max="180" value="<?php echo $schedule['duration']; ?>" required>
        </div>
        
         <div class="form-group">
            <label for="room">Room</label>
            <select id="room" name="room" required>
                <option value="">Select a room</option>
                <option value="A" <?php echo ($schedule['room'] === 'A') ? 'selected' : ''; ?>>Room A</option>
                <option value="B" <?php echo ($schedule['room'] === 'B') ? 'selected' : ''; ?>>Room B</option>
                <option value="C" <?php echo ($schedule['room'] === 'C') ? 'selected' : ''; ?>>Room C</option>
                <option value="D" <?php echo ($schedule['room'] === 'D') ? 'selected' : ''; ?>>Room D</option>
                <option value="E" <?php echo ($schedule['room'] === 'E') ? 'selected' : ''; ?>>Room E</option>
            </select>
            </div>

        <div class="form-group">
            <label for="teacher">Teacher</label>
            <input type="text" name="teacher" id="teacher" value="<?php echo htmlspecialchars($schedule['teacher']); ?>" required>
        </div>
<div class="d-flex justify-content-between gap-2">
    <a href="manageschedule.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Update Schedule
    </button>
</div>

    </form>
</div>

</body>
</html>

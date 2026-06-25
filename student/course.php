<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$activePage = basename($_SERVER['PHP_SELF'], ".php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $year_level = $_POST['year'];
$subject_name = $_POST['subject'];
$available_time = $_POST['timeslot'];

    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO available_courses (username,subject_name, available_time, year_level) VALUES (?,?, ?,  ?)");
    $stmt->bind_param("ssss",$username, 
    $subject_name, $available_time, $year_level);
    
    // Default section (you can modify this as needed)
    $section = "A"; 
    
    if ($stmt->execute()) {
        header("Location: book.php");
        exit();
    } else {
        $error = "Error booking course: " . $conn->error;
    }
}

// Fetch available subjects and timeslots
$subjects = [];
$timeslots = [];
$result = $conn->query("SELECT * FROM available_courses");
while ($row = $result->fetch_assoc()) {
    $subjects[$row['year_level']][] = $row['subject_name'];
    $timeslots[$row['subject_name']][] = $row['available_time'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <title>Book New Course</title>
    <style>
        /* General Styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            background-color: #f4f4f4;
        }
        
        /* Sidebar Styling (same as before) */
        .sidebar {
            width: 250px;
            background-color: #2b2640;
            color: white;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            height: 100%;
            position: fixed;
        }
        
        /* Main Content Styling */
        .main-content {
            flex: 1;
            margin-left: 250px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-height: 100vh;
            background-color: #f0f4f8;
            padding: 40px;
            box-sizing: border-box;
        }
        
        .booking-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .booking-header h1 {
            color: #2b2640;
            margin-bottom: 10px;
        }
        
        .booking-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            color: #666;
        }
        
        .step.active .step-number {
            background-color: #2b2640;
            color: white;
        }
        
        .step.completed .step-number {
            background-color: #4CAF50;
            color: white;
        }
        
        .step-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #ddd;
            z-index: 0;
        }
        
        .step-progress {
            height: 100%;
            background-color: #4CAF50;
            width: 0%;
            transition: width 0.3s;
        }
        
        .booking-content {
            display: none;
        }
        
        .booking-content.active {
            display: block;
        }
        
        .option-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .option-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-card:hover {
            border-color: #2b2640;
            transform: translateY(-3px);
        }
        
        .option-card.selected {
            border-color: #2b2640;
            background-color: #f0f0ff;
        }
        
        .option-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .option-card h3 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .booking-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #2b2640;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a3450;
        }
        
        .btn-secondary {
            background-color: #ddd;
            color: #333;
        }
        
        .btn-secondary:hover {
            background-color: #ccc;
        }
        
        .btn:disabled {
            background-color: #eee;
            color: #aaa;
            cursor: not-allowed;
        }
        
        .summary-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .error-message {
            color: #f44336;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success-message {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 20px;
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

<div class="main-content">
    <div class="booking-container">
        <div class="booking-header">
            <h1>Book New Course</h1>
            <p>Select your preferred year, subject, and timeslot</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="booking-steps">
            <div class="step-line"><div class="step-progress" id="stepProgress"></div></div>
            <div class="step active" id="step1">
                <div class="step-number">1</div>
                <div>Select Year</div>
            </div>
            <div class="step" id="step2">
                <div class="step-number">2</div>
                <div>Select Subject</div>
            </div>
            <div class="step" id="step3">
                <div class="step-number">3</div>
                <div>Select Timeslot</div>
            </div>
            <div class="step" id="step4">
                <div class="step-number">4</div>
                <div>Confirmation</div>
            </div>
        </div>
        
        <form id="bookingForm" method="POST">
            <!-- Step 1: Select Year -->
            <div class="booking-content active" id="content1">
                <h2>Select Your Year</h2>
                <div class="option-grid">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="option-card" data-value="Year <?php echo $i; ?>">
                            <h3>Year <?php echo $i; ?></h3>
                            <p>Age <?php echo $i + 5; ?>-<?php echo $i + 6; ?></p>
                        </div>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="year" id="selectedYear">
            </div>
            
            <!-- Step 2: Select Subject -->
            <div class="booking-content" id="content2">
                <h2>Select Your Subject</h2>
                <div class="option-grid" id="subjectOptions">
                    <!-- Subjects will be loaded via JavaScript based on year selection -->
                </div>
                <input type="hidden" name="subject" id="selectedSubject">
            </div>
            
            <!-- Step 3: Select Timeslot -->
            <div class="booking-content" id="content3">
                <h2>Select Timeslot</h2>
                <div class="option-grid" id="timeslotOptions">
                    <!-- Timeslots will be loaded via JavaScript based on subject selection -->
                </div>
                <input type="hidden" name="timeslot" id="selectedTimeslot">
            </div>
            
            <!-- Step 4: Confirmation -->
            <div class="booking-content" id="content4">
                <h2>Confirm Your Booking</h2>
                <div class="summary-card">
                    <div class="summary-item">
                        <span>Year:</span>
                        <span id="summaryYear"></span>
                    </div>
                    <div class="summary-item">
                        <span>Subject:</span>
                        <span id="summarySubject"></span>
                    </div>
                    <div class="summary-item">
                        <span>Timeslot:</span>
                        <span id="summaryTimeslot"></span>
                    </div>
                </div>
                <p>By clicking "Confirm Booking", you agree to our terms and conditions.</p>
            </div>
            
            <div class="booking-nav">
                <button type="button" class="btn btn-secondary" id="prevBtn" disabled>Back</button>
                <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                <button type="submit" class="btn btn-primary" id="confirmBtn" style="display: none;">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const submenu = this.querySelector('.submenu');
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
    // Current step tracking
    let currentStep = 1;
    const totalSteps = 4;
    
    // DOM elements
    const stepElements = document.querySelectorAll('.step');
    const contentElements = document.querySelectorAll('.booking-content');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const confirmBtn = document.getElementById('confirmBtn');
    const stepProgress = document.getElementById('stepProgress');
    
    // Form data
    const formData = {
        year: '',
        subject: '',
        timeslot: ''
    };
    
    // Sample data - in a real app, this would come from your PHP backend
    const subjectsData = {
        'Year 1': ['Mathematics', 'English', 'Science', 'History'],
        'Year 2': ['Mathematics', 'English', 'Science', 'Geography'],
        'Year 3': ['Mathematics', 'English', 'Biology', 'Chemistry', 'Physics'],
        'Year 4': ['Mathematics', 'English', 'Biology', 'Chemistry', 'Physics', 'Additional Math'],
        'Year 5': ['Mathematics', 'English', 'Biology', 'Chemistry', 'Physics', 'Additional Math'],
        'Year 6': ['Mathematics', 'English', 'Biology', 'Chemistry', 'Physics', 'Additional Math']
    };
    
    const timeslotsData = {
        'Mathematics': ['Mon 14:00-16:00', 'Wed 14:00-16:00', 'Fri 14:00-16:00'],
        'English': ['Tue 14:30-16:00', 'Thu 14:30-16:00'],
        'Science': ['Mon 14:00-16:00', 'Wed 19:00-21:00'],
        'History': ['Tue 19:00-21:00', 'Fri 19:00-21:00'],
        'Geography': ['Wed 18:00-20:00', 'Fri 17:00-19:00'],
        'Biology': ['Mon 19:00-21:00', 'Thu 17:00-19:00'],
        'Chemistry': ['Tue 19:00-21:00', 'Fri 14:00-16:00'],
        'Physics': ['Wed 17:00-19:00', 'Thu 14:00-16:00'],
        'Additional Math': ['Mon 10:00-12:00', 'Thu 19:00-21:00']
    };
    
    // Initialize the booking process
    function initBooking() {
        // Year selection
        const yearCards = document.querySelectorAll('#content1 .option-card');
        yearCards.forEach(card => {
            card.addEventListener('click', function() {
                yearCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                formData.year = this.dataset.value;
                document.getElementById('selectedYear').value = this.dataset.value;
                enableNextButton();
            });
        });
        
        // Navigation buttons
        prevBtn.addEventListener('click', prevStep);
        nextBtn.addEventListener('click', nextStep);
        
        updateStepDisplay();
    }
    
    // Move to next step
    function nextStep() {
        if (currentStep < totalSteps) {
            // Validate current step before proceeding
            if (validateStep(currentStep)) {
                currentStep++;
                updateStepDisplay();
                
                // Load dynamic content for next step
                if (currentStep === 2) {
                    loadSubjects();
                } else if (currentStep === 3) {
                    loadTimeslots();
                } else if (currentStep === 4) {
                    showSummary();
                }
            }
        }
    }
    
    // Move to previous step
    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateStepDisplay();
        }
    }
    
    // Update UI based on current step
    function updateStepDisplay() {
        // Update step indicators
        stepElements.forEach((step, index) => {
            if (index + 1 < currentStep) {
                step.classList.remove('active');
                step.classList.add('completed');
            } else if (index + 1 === currentStep) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });
        
        // Update progress bar
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        stepProgress.style.width = `${progress}%`;
        
        // Show/hide content sections
        contentElements.forEach((content, index) => {
            if (index + 1 === currentStep) {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });
        
        // Update navigation buttons
        prevBtn.disabled = currentStep === 1;
        
        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            confirmBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            confirmBtn.style.display = 'none';
            nextBtn.disabled = !isStepComplete(currentStep);
        }
    }
    
    // Validate current step
    function validateStep(step) {
        switch (step) {
            case 1:
                if (!formData.year) {
                    alert('Please select a year');
                    return false;
                }
                return true;
            case 2:
                if (!formData.subject) {
                    alert('Please select a subject');
                    return false;
                }
                return true;
            case 3:
                if (!formData.timeslot) {
                    alert('Please select a timeslot');
                    return false;
                }
                return true;
            default:
                return true;
        }
    }
    
    // Check if current step is complete
    function isStepComplete(step) {
        switch (step) {
            case 1: return !!formData.year;
            case 2: return !!formData.subject;
            case 3: return !!formData.timeslot;
            default: return true;
        }
    }
    
    // Enable next button when selection is made
    function enableNextButton() {
        nextBtn.disabled = !isStepComplete(currentStep);
    }
    
    // Load subjects based on selected year
    function loadSubjects() {
        const subjectOptions = document.getElementById('subjectOptions');
        subjectOptions.innerHTML = '';
        
        if (formData.year && subjectsData[formData.year]) {
            subjectsData[formData.year].forEach(subject => {
                const card = document.createElement('div');
                card.className = 'option-card';
                card.dataset.value = subject;
                card.innerHTML = `<h3>${subject}</h3>`;
                
                card.addEventListener('click', function() {
                    document.querySelectorAll('#content2 .option-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    formData.subject = this.dataset.value;
                    document.getElementById('selectedSubject').value = this.dataset.value;
                    enableNextButton();
                });
                
                subjectOptions.appendChild(card);
            });
        }
    }
    
    // Load timeslots based on selected subject
    function loadTimeslots() {
        const timeslotOptions = document.getElementById('timeslotOptions');
        timeslotOptions.innerHTML = '';
        
        if (formData.subject && timeslotsData[formData.subject]) {
            timeslotsData[formData.subject].forEach(timeslot => {
                const card = document.createElement('div');
                card.className = 'option-card';
                card.dataset.value = timeslot;
                card.innerHTML = `<h3>${timeslot}</h3>`;
                
                card.addEventListener('click', function() {
                    document.querySelectorAll('#content3 .option-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    formData.timeslot = this.dataset.value;
                    document.getElementById('selectedTimeslot').value = this.dataset.value;
                    enableNextButton();
                });
                
                timeslotOptions.appendChild(card);
            });
        }
    }
    
    // Show summary of selections
    function showSummary() {
        document.getElementById('summaryYear').textContent = formData.year;
        document.getElementById('summarySubject').textContent = formData.subject;
        document.getElementById('summaryTimeslot').textContent = formData.timeslot;
    }
    
    // Initialize the booking process when page loads
    document.addEventListener('DOMContentLoaded', initBooking);
</script>
</body>
</html>
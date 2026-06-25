<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

$activePage = basename($_SERVER['PHP_SELF'], ".php");

// Fetch parent's children with their ages
$childrenQuery = "SELECT c.id, c.name, c.age FROM children c 
                 JOIN parent_child pc ON c.id = pc.child_id 
                 JOIN parents p ON pc.parent_username = p.username 
                 WHERE p.username = ?";
$stmt = $conn->prepare($childrenQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$childrenResult = $stmt->get_result();
$children = $childrenResult->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $child_id = $_POST['child_id'];
    $subject_name = $_POST['subject'];
    $available_time = $_POST['timeslot'];
    
    // Determine subject code based on subject name
    $subject_id = '';
    switch ($subject_name) {
        case 'Mathematics':
            $subject_id = '20300';
            break;
        case 'Bahasa Melayu':
            $subject_id = '20103';
            break;
        case 'English':
            $subject_id = '20105';
            break;
        case 'Science':
            $subject_id = '20402';
            break;
        default:
            $subject_id = '0000';
    }    
    
    $subjectSchedules = [
    'Mathematics' => [
        ['day' => 'Wednesday', 'time' => '02:00 PM - 04:00 PM'],
        ['day'=> 'Thursday', 'time'=> '03:00 PM - 05:00 PM'],
        ['day'=> 'Friday', 'time'=> '03:00 PM - 05:00 PM'],
        ['day'=> 'Saturday', 'time'=> '02:00 PM - 04:00 PM']// ...
    ],
    // ...

    'Bahasa Melayu'=> [
        ['day'=> 'Monday', 'time'=> '03:00 PM - 05:00 PM'],
        ['day'=> 'Tuesday', 'time'=> '03:00 PM - 05:00 PM'],
        ['day'=> 'Wednesday', 'time'=> '03:00 PM - 05:00 PM'],
        ['day'=> 'Saturday', 'time'=> '10:00 AM - 12:00 PM'],
    ],
    'English'=> [
        ['day'=> 'Monday', 'time'=> '09:00 AM - 11:00 AM'],
        ['day'=> 'Tuesday', 'time'=> '09:00 AM - 11:00 AM'],
        ['day'=> 'Thursday', 'time'=> '02:00 PM - 04:00 PM'],
        ['day'=> 'Saturday', 'time'=> '09:00 AM - 11:00 AM'],
    ],
    'Science'=> [
        ['day'=> 'Tuesday', 'time'=> '02:00 PM - 04:00 PM'],
        ['day'=> 'Wednesday', 'time'=> '09:00 AM - 11:00 AM'],
        ['day'=> 'FFriday', 'time'=> '02:00 PM - 04:00 PM'],
        ['day'=> 'Saturday', 'time'=> '11:00 AM - 1:00 PM']
    ]
    ];
    $subjects = array_keys($subjectSchedules);

    // Get child's age
    $child_age = 0;
    foreach ($children as $child) {
        if ($child['id'] == $child_id) {
            $child_age = $child['age'];
            break;
        }
    }
    
    // Calculate year based on age (assuming age 7 is year 1, age 8 is year 2, etc.)
    $stmt = $conn->prepare("INSERT INTO subjects (username, subject_name, subject_id, time, fee, age) VALUES (?, ?, ?, ?, ?, ?)");
    $fee = 45; // Fixed fee
    $time = $available_time;
    $age = $child_age;
    $stmt->bind_param("ssssdi", $child_id, $subject_name, $subject_id, $time, $fee, $age);

    if ($stmt->execute()) {
        header("Location: book.php?success=1");
        exit();
    } else {
        $error = "Error booking course: " . $conn->error;
    }
}

// Fetch available subjects and timeslots
$subjects = [];
$timeslots = [];
$result = $conn->query("SELECT DISTINCT subject_name FROM subjects");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row['subject_name'];
}

// Define available days and time slots
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$timeSlots = [
    '09:00 AM - 10:30 AM',
    '02:00 PM - 03:30 PM', 
    '04:00 PM - 05:30 PM'
];

$timeslots = [];
foreach ($days as $day) {
    // Randomly select 1-3 time slots per day
    $slotsForDay = array_rand(array_flip($timeSlots), rand(1, 3));
    if (!is_array($slotsForDay)) {
        $slotsForDay = [$slotsForDay];
    }
    
    foreach ($slotsForDay as $slot) {
        $timeslots[] = [
            'display' => "$day $slot",
            'day' => $day,
            'time' => $slot
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Course for Child</title>
    <style>
        .booking-content { display: none; }
        .booking-content.active { display: block; }
        .option-card { padding: 10px; border: 1px solid #ccc; margin: 5px; cursor: pointer; border-radius: 5px; }
        .option-card.selected { background-color: #cce5ff; }
        .step { display: inline-block; padding: 10px; }
        .step.active { font-weight: bold; }
        .step.completed { color: green; }
        .btn { padding: 10px 20px; margin: 5px; }
        .summary-card { padding: 10px; border: 1px solid #ccc; margin: 10px 0; }
        .summary-item { margin-bottom: 5px; }
        #bookingForm {
        max-width: 800px;
        margin: 0 auto;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    /* Steps navigation */
    .steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
    }
    
    .steps:after {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        right: 0;
        height: 2px;
        background: #ddd;
        z-index: 1;
    }
    
    .step {
        position: relative;
        z-index: 2;
        background: white;
        padding: 10px 15px;
        border-radius: 20px;
        color: #777;
        border: 1px solid #ddd;
    }
    
    .step.active {
        font-weight: bold;
        color: white;
        background-color: #3a8ad0;
        border-color: #3a8ad0;
    }
    
    .step.completed {
        color: white;
        background-color: #4caf50;
        border-color: #4caf50;
    }
    
    /* Option cards styling */
    .option-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    
    .option-card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .option-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-color: #bbd6f7;
    }
    
    .option-card.selected {
        background-color: #e6f0ff;
        border-color: #3a8ad0;
        box-shadow: 0 5px 15px rgba(58,138,208,0.2);
    }
    
    .option-icon {
        font-size: 24px;
        margin-bottom: 10px;
        color: #3a8ad0;
    }
    
    .option-title {
        font-weight: bold;
        margin-bottom: 5px;
        color: #444;
    }
    
    .option-desc {
        font-size: 12px;
        color: #777;
    }
    
    /* Button styling */
    .btn {
        padding: 10px 20px;
        margin: 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s;
    }
    
    #prevBtn {
        background-color: #f1f1f1;
        color: #555;
    }
    
    #prevBtn:hover {
        background-color: #e0e0e0;
    }
    
    #nextBtn {
        background-color: #3a8ad0;
        color: white;
    }
    
    #nextBtn:hover {
        background-color: #2c7cb8;
    }
    
    #confirmBtn {
        background-color: #4caf50;
        color: white;
    }
    
    #confirmBtn:hover {
        background-color: #45a049;
    }
    
    /* Child select dropdown styling */
    #childSelect {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        margin-top: 10px;
    }
    
    /* Summary card styling */
    .summary-card {
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .summary-item strong {
        color: #555;
    }
    /* Timeslot specific styling */
.timeslot-day {
    font-weight: bold;
    color: #3a8ad0;
    margin-bottom: 5px;
}
.timeslot-time {
    color: #555;
    font-size: 0.9em;
    display: block;
}
    </style>
</head>
<body>

<h1>Book Course for Your Child</h1>

<?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
<?php if (isset($_GET['success'])) echo "<p style='color: green;'>Course booked successfully!</p>"; ?>

<form method="POST" id="bookingForm">
    <div class="steps">
        <div class="step" id="step1">Select Child</div>
        <div class="step" id="step2">Select Subject</div>
        <div class="step" id="step3">Select Timeslot</div>
        <div class="step" id="step4">Confirmation</div>
    </div>

    <!-- Step 1 -->
    <div class="booking-content active" id="content1">
        <h2>Select Your Child</h2>
        <select name="child_id" id="childSelect" required>
            <option value="">-- Select Child --</option>
            <?php foreach ($children as $child): ?>
                <option value="<?php echo $child['id']; ?>" data-age="<?php echo $child['age']; ?>">
                    <?php echo htmlspecialchars($child['name']); ?> (Age: <?php echo $child['age']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Step 2 -->
    <div class="booking-content" id="content2">
    <h2>Select Subject</h2>
    <div id="subjectOptions" class="option-container">
        <div class="option-card" data-value="Mathematics" data-code="20300">
            <div class="option-icon"><i class="fa fa-calculator"></i></div>
            <div class="option-title">Mathematics</div>
            <div class="option-desc">Number operations, algebra, geometry</div>
        </div>
        <div class="option-card" data-value="Bahasa Melayu" data-code="20103">
            <div class="option-icon"><i class="fa fa-book"></i></div>
            <div class="option-title">Bahasa Melayu</div>
            <div class="option-desc">Malay language and literature</div>
        </div>
        <div class="option-card" data-value="English" data-code="20105">
            <div class="option-icon"><i class="fa fa-language"></i></div>
            <div class="option-title">English</div>
            <div class="option-desc">English language and literature</div>
        </div>
        <div class="option-card" data-value="Science" data-code="20402">
            <div class="option-icon"><i class="fa fa-flask"></i></div>
            <div class="option-title">Science</div>
            <div class="option-desc">Physics, chemistry, and biology</div>
        </div>
    </div>
    <input type="hidden" name="subject" id="selectedSubject">
</div>

    <!-- Step 3 -->
<div class="booking-content" id="content3">
    <h2>Select Timeslot</h2>
    <div id="timeslotOptions" class="option-container">
        <input type="hidden" name="day" id="selectedDay">
        <input type="hidden" name="time" id="selectedTime">
    </div>
    <!-- Step 4 -->
    <div class="booking-content" id="content4">
        <h2>Confirm Your Booking</h2>
        <div class="summary-card">
            <div class="summary-item"><strong>Child:</strong> <span id="summaryChild"></span></div>
            <div class="summary-item"><strong>Age:</strong> <span id="summaryAge"></span></div>
            <div class="summary-item"><strong>Year:</strong> <span id="summaryYear"></span></div>
            <div class="summary-item"><strong>Subject:</strong> <span id="summarySubject"></span></div>
            <div class="summary-item"><strong>Subject Code:</strong> <span id="summarySubjectCode"></span></div>
            <div class="summary-item"><strong>Timeslot:</strong> <span id="summaryTimeslot"></span></div>
            <div class="summary-item"><strong>Fee:</strong> RM<span id="summaryFee"></span></div>
        </div>
    </div>

    <button type="button" class="btn" id="prevBtn">Back</button>
    <button type="button" class="btn" id="nextBtn">Next</button>
    <button type="submit" class="btn" id="confirmBtn" style="display:none;">Confirm Booking</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentStep = 1;
    const totalSteps = 4;
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const confirmBtn = document.getElementById('confirmBtn');
    const childSelect = document.getElementById('childSelect');

    const formData = {
        child_id: '',
        child_name: '',
        child_age: 0,
        year: 0,
        subject: '',
        subject_id: '',
        timeslot: '',
        fee: 45
    };

    // Define subject schedules (matches PHP array)
    const subjectSchedules = {
        'Mathematics': [
            {day: 'Wednesday', time: '02:00 PM - 04:00 PM'},
            {day: 'Thursday', time: '03:00 PM - 05:00 PM'},
            {day: 'Friday', time: '03:00 PM - 05:00 PM'},
            {day: 'Saturday', time: '02:00 PM - 04:00 PM'}
        ],
        'Bahasa Melayu': [
            {day: 'Monday', time: '03:00 PM - 05:00 PM'},
            {day: 'Tuesday', time: '03:00 PM - 05:00 PM'},
            {day: 'Wednesday', time: '03:00 PM - 05:00 PM'},
            {day: 'Saturday', time: '10:00 AM - 12:00 PM'}
        ],
        'English': [
            {day: 'Monday', time: '09:00 AM - 11:00 AM'},
            {day: 'Tuesday', time: '09:00 AM - 11:00 AM'},
            {day: 'Thursday', time: '02:00 PM - 04:00 PM'},
            {day: 'Saturday', time: '09:00 AM - 11:00 AM'}
        ],
        'Science': [
            {day: 'Tuesday', time: '02:00 PM - 04:00 PM'},
            {day: 'Wednesday', time: '09:00 AM - 11:00 AM'},
            {day: 'Friday', time: '02:00 PM - 04:00 PM'},
            {day: 'Saturday', time: '11:00 AM - 01:00 PM'}
        ]
    };
    
    function showStep() {
        document.querySelectorAll('.booking-content').forEach((content, idx) => {
            content.classList.toggle('active', idx + 1 === currentStep);
        });
        document.querySelectorAll('.step').forEach((step, idx) => {
            step.classList.toggle('active', idx + 1 === currentStep);
            step.classList.toggle('completed', idx + 1 < currentStep);
        });

        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-block';
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-block';
        confirmBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';
        
        // Validate required fields before allowing to proceed
        if (currentStep === 1) {
            nextBtn.disabled = !formData.child_id;
        } else if (currentStep === 2) {
            nextBtn.disabled = !formData.subject;
        } else if (currentStep === 3) {
            nextBtn.disabled = !formData.timeslot;
        }
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep();
            if (currentStep === 4) fillSummary();
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            showStep();
        }
    }

    function fillSummary() {
        document.getElementById('summaryChild').textContent = formData.child_name;
        document.getElementById('summaryAge').textContent = formData.child_age;
        document.getElementById('summaryYear').textContent = formData.year;
        document.getElementById('summarySubject').textContent = formData.subject;
        document.getElementById('summarySubjectCode').textContent = formData.subject_id;
        document.getElementById('summaryTimeslot').textContent = formData.timeslot;
        document.getElementById('summaryFee').textContent = formData.fee;
    }

    // Child selection
    // Child selection
childSelect.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    if (this.value) {
        formData.child_id = this.value;
        formData.child_name = selectedOption.text.split(' (Age:')[0];
        formData.child_age = parseInt(selectedOption.dataset.age);
        formData.year = formData.child_age - 6;
        nextBtn.disabled = false; // Enable the next button
    } else {
        nextBtn.disabled = true;
    }
});
    // Subject selection
    document.querySelectorAll('#subjectOptions .option-card').forEach(card => {
        card.addEventListener('click', function () {
            document.querySelectorAll('#subjectOptions .option-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            formData.subject = this.dataset.value;
            formData.subject_id = this.dataset.code;
            document.getElementById('selectedSubject').value = this.dataset.value;
            nextBtn.disabled = false;
        });
    });

    // Timeslot selection
    document.querySelectorAll('#timeslotOptions .option-card').forEach(card => {
        card.addEventListener('click', function () {
            document.querySelectorAll('#timeslotOptions .option-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            formData.timeslot = this.dataset.value;
            document.getElementById('selectedTimeslot').value = this.dataset.value;
            nextBtn.disabled = false;
        });
    });
    // Subject selection
document.querySelectorAll('#subjectOptions .option-card').forEach(card => {
    card.addEventListener('click', function () {
        document.querySelectorAll('#subjectOptions .option-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        formData.subject = this.dataset.value;
        formData.subject_id = this.dataset.code;
        document.getElementById('selectedSubject').value = this.dataset.value;
        nextBtn.disabled = false;
        
        // Generate timeslots for the selected subject
        const timeslotContainer = document.getElementById('timeslotOptions');
        timeslotContainer.innerHTML = '';
        
        if (subjectSchedules[formData.subject]) {
            subjectSchedules[formData.subject].forEach(slot => {
                const slotValue = `${slot.day} ${slot.time}`;
                const card = document.createElement('div');
                card.className = 'option-card';
                card.dataset.value = slotValue;
                card.innerHTML = `
                    <div class="timeslot-day">${slot.day}</div>
                    <div class="timeslot-time">${slot.time}</div>
                `;
                timeslotContainer.appendChild(card);
            });
        }
    });
});

    // Show initial step

showStep();
    // Attach event listeners for the buttons
    // Attach event listeners for the buttons
nextBtn.addEventListener('click', nextStep);
prevBtn.addEventListener('click', prevStep);
});
    
</script>

<!-- ADD THIS DISPLAY CODE RIGHT HERE: -->

</body>
</html>
</div>
</body>
</html>
<?php
session_start();
include 'db.php';
$username = $_SESSION['username'];

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if session is not active
    header("Location: login.php");
    exit();
}

// Determine active page
$activePage = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* General Styling */
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
        /* Main Content Styling */
        .main-content {
            flex: 1;
    margin-left: 250px; /* Ensure the main content is not hidden behind the sidebar */
    display: flex;
    flex-direction: column;
    
    justify-content: flex-start;
    min-height: 100vh;
    background-color: #f0f4f8;
    padding: 40px;
    box-sizing: border-box;
        }

        .main-content h1 {
            color: #333;
        }

        .payment-container {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .payment-container h5 {
            margin-bottom: 20px;
        }

        .payment-container button {
            background-color: #4a9cf0;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 16px;
        }

        .payment-container button:hover {
            background-color: #3a8ad0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .total {
            font-weight: bold;
        }

        .month-select {
            margin-bottom: 10px;
        }

        .pay-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .toggle {
            cursor: pointer;
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
        <h1>Payment</h1>

        <div class="payment-container">
        
        <h2>Payment</h2>

        <div class="month-select">
            <label for="month">Select Month:</label>
            <select id="month">
                <option value="january">January</option>
                <option value="february">February</option>
                <option value="march">March</option>
                </select>
        </div>

        <table>
        <thead>
            <tr>
                <th></th>
                <th>No.</th>
                <th>Subject</th>
                <th>Fees Amount</th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch subjects and fees
            $query = "SELECT id, subject_name, fee FROM subjects";
            $result = $conn->query($query);
            $total = 0;
            $counter = 1;
            if ($result->num_rows > 0) {
                
                while ($row = $result->fetch_assoc()) {
                    $total += $row['fee']; 
                    echo "<tr>";
                    echo "<td><input type='checkbox' class='fee-checkbox' data-fee='" . $row['fee'] . "'></td>";
                    echo "<td>" . $counter++ . "</td>";
                    
                    echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
                    echo "<td>RM" . number_format($row['fee'], 2) . "</td>";
                    echo "<input type='hidden' name='subject_ids[]' value='" . $row['id'] . "'>";
                    echo "<input type='hidden' name='fees[]' value='" . $row['fee'] . "'>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No subjects found.</td></tr>";
            }
            ?>
            <tfoot>
        <tr>
            <td colspan="3" style="font-weight: bold;">Total</td>
            <td id="total-fee" style="font-weight: bold;">RM0.00</td>
        </tr>
        </tbody>
    </table>

    <button type="submit" class="pay-button">Pay</button>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script>
        function pay() {
            // Get the selected month
            var selectedMonth = document.getElementById("month").value;

            // Display a confirmation message with the selected month
            alert("You are paying for the month of " + selectedMonth + ".");

            // You can add further payment processing logic here, such as:
            // - Displaying a payment gateway
            // - Sending a payment request to a server
        }
        document.addEventListener("DOMContentLoaded", function () {
    // Get all checkbox elements
    const checkboxes = document.querySelectorAll(".fee-checkbox");
    const totalFeeElement = document.getElementById("total-fee");

    // Function to calculate total based on selected checkboxes
    function calculateTotal() {
        let total = 0;
        checkboxes.forEach((checkbox) => {
            if (checkbox.checked) {
                total += parseFloat(checkbox.dataset.fee);
            }
        });
        totalFeeElement.textContent = "RM" + total.toFixed(2);
    }

    // Add event listeners to checkboxes
    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", calculateTotal);
    });
});

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

<?php
// Start the session
session_start();

// Set session timeout duration (in seconds)
$timeout_duration = 120; // 2 minutes

// Check if the session is set and validate timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // If the session has expired, destroy the session and redirect to login page
    session_unset();
    session_destroy();
    header("Location: logincheck.php"); // Redirect to the login page
    exit();
} else {
    // If the session is valid, update last activity time
    $_SESSION['last_activity'] = time();
}

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    // If not, redirect to login page
    header("Location: logincheck.php");
    exit();
}

// Get the username and user type from session
$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-primary text-white text-center py-3">
        <h2>VELAGAPUDI RAMAKRISHNA SIDDHARTHA ENGINEERING COLLEGE</h2>
        <h3>(Deemed to be University)</h3>
        <h2>Student Information System</h2>
    </header>

    <div class="container-fluid d-flex flex-grow-1">
        <div class="row w-100 flex-grow-1">
            <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white" onclick="loadContent('home')">Home</a></li>
                    <li><a href="#" class="text-white" onclick="loadContent('Check Attendance')">Check Attendance</a></li>
                    <li><a href="#" class="text-white" onclick="loadContent('Course Enrollment')">Course Enrollment</a></li>
                    <li><a href="#" class="text-white" onclick="loadContent('CA Marks')">CA Marks</a></li>
                    <li><a href="#" class="text-white" onclick="loadContent('SE Marks Report')">SE Marks Report</a></li>
                    <li><a href="logout.php" class="text-white" onclick="loadContent('Logout')">Logout</a></li>
                </ul>
            </nav>

            <div class="col-md-9 col-lg-10" id="content-area">
                <br><br><br><br><br><br><br>
                <center>
                    <h2>Welcome to Student Information System - VRSEC</h2>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                    <p><strong>User Type:</strong> <?php echo htmlspecialchars($user_type); ?></p>
                </center>
            </div>
        </div>
    </div>

    <footer class="bg-primary text-white text-center">
        <p>© 2024 Copyrights reserved - Developed by Dept. of IT</p>
    </footer>

    <!-- Include Bootstrap JS and Popper.js -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="load.js"></script>
</body>
</html>
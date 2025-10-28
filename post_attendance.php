<?php
// Start the session
session_start();
include 'connection.php';

$timeout_duration = 10; // 10 seconds for demo, you can change this

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

// Get faculty ID from session
$faculty_id = $_SESSION['username'];

// Example semester, branch, and academic year
$ay = '2024-2025';
$sem = 1;
$branch = 'it';

// Fetch the courses for the faculty
$query = "SELECT course_id, section FROM course_faculty WHERE faculty_id = ? AND ay = ? AND sem = ? AND branch = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $faculty_id, $ay, $sem, $branch);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Attendance</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Your custom styles if needed -->
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-primary text-white text-center py-3">
        <h2>VELAGAPUDI RAMAKRISHNA SIDDHARTHA ENGINEERING COLLEGE</h2>
        <h3>(Deemed to be University)</h3>
        <h2>Student Information System</h2>
    </header>

    <div class="container-fluid d-flex flex-grow-1">
        <div class="row w-100 flex-grow-1">
            <!-- Include Faculty Menu -->
            <?php include 'faculty_menu.php'; ?> <!-- Ensure this file exists and is correct -->

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10">
                <div class="container mt-5">
                    <h3>Select Course for Posting Attendance</h3>
                    <ul class="list-unstyled">
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <li class="mb-2">
                                    <!-- Create a form for each course -->
                                    <form method="POST" action="attendance_form.php">
                                        <input type="hidden" name="course_id" value="<?= htmlspecialchars($course['course_id']) ?>">
                                        <input type="hidden" name="section" value="<?= htmlspecialchars($course['section']) ?>">
                                        <input type="hidden" name="sem" value="<?= htmlspecialchars($sem) ?>">
                                        <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
                                        <input type="hidden" name="ay" value="<?= htmlspecialchars($ay) ?>">
                                        <button type="submit" class="btn btn-primary">
                                            Course: <?= htmlspecialchars($course['course_id']) ?> | Section: <?= htmlspecialchars($course['section']) ?> | Branch: <?= htmlspecialchars($branch) ?> | Sem: <?= htmlspecialchars($sem) ?> | AY: <?= htmlspecialchars($ay) ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No courses assigned for this semester.</p>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-primary text-white text-center py-3">
        <p>© 2024 Copyrights reserved - Developed by Dept. of IT</p>
    </footer>
</body>
</html>

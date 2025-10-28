<?php
session_start();
require_once 'connection.php';

// If not logged in, redirect
if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Fetch courses for this faculty
$sql = "SELECT c.course_id, c.name, c.type, f.section, f.AY
        FROM fvc f
        INNER JOIN courses c ON f.course_id = c.course_id
        WHERE f.faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Lab Marks</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .course-btn {
            margin: 10px;
            padding: 15px 25px;
            font-size: 16px;
            border-radius: 8px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>(Deemed to be University)</h3>
    <h2>Post Lab Marks</h2>
</header>

<div class="container-fluid d-flex flex-grow-1">
    <div class="row w-100 flex-grow-1">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <ul class="list-unstyled">
                <li><a href="facultymain.php" class="text-white">Home</a></li>
                <li><a href="post_lab_marks.php" class="text-white">Post Lab Marks</a></li>
                <li><a href="post_internal_marks.php" class="text-white">Post Internal Marks</a></li>
                <li><a href="check_students_attendance.php" class="text-white">Check Students' Attendance</a></li>
                <li><a href="check_students_marks.php" class="text-white">Check Students' Marks</a></li>
                <li><a href="reports.php" class="text-white">Reports</a></li>
                <li><a href="logout.php" class="text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="col-md-9 col-lg-10" id="content-area">
            <center>
                <h3 class="mt-3">Select a Course to Enter Marks</h3>
                <?php if (count($courses) > 0): ?>
                    <div class="d-flex flex-wrap justify-content-center mt-4">
                        <?php foreach ($courses as $course): ?>
                            <form action="enter_marks.php" method="get" style="display:inline;">
                                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                <input type="hidden" name="section" value="<?php echo htmlspecialchars($course['section']); ?>">
                                <input type="hidden" name="AY" value="<?php echo htmlspecialchars($course['AY']); ?>">
                                <button type="submit" class="btn btn-primary course-btn">
                                    <?php echo htmlspecialchars($course['name']); ?> 
                                    (<?php echo htmlspecialchars($course['type']); ?>, 
                                     Sec: <?php echo htmlspecialchars($course['section']); ?>, 
                                     AY: <?php echo htmlspecialchars($course['AY']); ?>)
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mt-4 text-danger">No courses assigned to you.</p>
                <?php endif; ?>
            </center>
        </div>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© 2024 Copyrights reserved - Developed by Dept. of IT</p>
</footer>
</body>
</html>

<?php
session_start();
require_once 'connection.php';

// Redirect if not logged in
if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Fetch all courses assigned to faculty
$sql = "SELECT c.course_id, c.name, c.type, f.section, f.AY, f.dept, f.faculty_id
        FROM fvc f
        INNER JOIN courses c ON f.course_id = c.course_id
        WHERE f.faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Filter only section 1 courses
$section1_courses = array_filter($courses, function($course) {
    return trim($course['section']) === '1';
});

// If no section 1 courses, find the faculty name who has Section 1 assigned
$section1_faculty_name = "";
if (count($section1_courses) === 0) {
    $sql2 = "SELECT DISTINCT f.faculty_id, fac.name 
             FROM fvc f
             INNER JOIN faculty fac ON f.faculty_id = fac.faculty_id
             WHERE f.section = '1'";
    $result2 = $conn->query($sql2);
    if ($result2 && $result2->num_rows > 0) {
        $row = $result2->fetch_assoc();
        $section1_faculty_name = $row['name'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Lab Marks</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
.course-btn {
    margin: 10px;
    padding: 15px 25px;
    font-size: 16px;
    border-radius: 8px;
    min-width: 320px;
    white-space: normal;
}
.sidebar a {
    color: white;
    text-decoration: none;
}
.sidebar a:hover {
    text-decoration: underline;
}
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>(Deemed to be University)</h3>
    <h2>Print Lab Marks</h2>
</header>

<div class="container-fluid d-flex flex-grow-1">
    <div class="row w-100 flex-grow-1">

        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 text-center" id="content-area">
            <h4 class="mt-4">Select a Course to Print Finalized Marks (Section 1 Only)</h4>

            <?php if (count($section1_courses) > 0): ?>
                <div class="d-flex flex-wrap justify-content-center mt-4">
                    <?php foreach ($section1_courses as $course): ?>
                        <form action="print_action_dept.php" method="get" target="_blank" style="display:inline;">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <input type="hidden" name="section" value="<?php echo htmlspecialchars($course['section']); ?>">
                            <input type="hidden" name="AY" value="<?php echo htmlspecialchars($course['AY']); ?>">
                            <input type="hidden" name="dept" value="<?php echo htmlspecialchars($course['dept']); ?>">

                            <button type="submit" class="btn btn-primary course-btn">
                                <?php echo htmlspecialchars($course['name']); ?> 
                                (<?php echo htmlspecialchars($course['type']); ?>, 
                                Sec: <?php echo htmlspecialchars($course['section']); ?>, 
                                Dept: <?php echo htmlspecialchars($course['dept']); ?>, 
                                AY: <?php echo htmlspecialchars($course['AY']); ?>)
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="alert alert-danger mt-5" role="alert">
                    <h5>You don’t have permission to take printout.</h5>
                    <p>Only Section 1 should be printing.</p>
                    <?php if (!empty($section1_faculty_name)): ?>
                        <p><strong>Section 1 is assigned to:</strong> <?php echo htmlspecialchars($section1_faculty_name); ?></p>
                    <?php else: ?>
                        <p><strong>No internal faculty assigned for Section 1 currently.</strong></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© 2024 - Developed by Dept. of IT</p>
</footer>
</body>
</html>

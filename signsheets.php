<?php
session_start();
require_once 'connection.php';

/* =======================
   ✅ Authentication Check
   ======================= */
if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

/* ==========================================
   ✅ SQL Query to Fetch Assigned Courses
   ==========================================
   - Selects all unique courses for the logged-in faculty
   - Counts marks from `marks` table based on matching course_id, section, dept, AY
   - Fix: CAST(f.dept AS UNSIGNED) to match marks.dept (INT)
   - Orders by numeric section value
*/
$sql = "
    SELECT DISTINCT 
        c.course_id, 
        c.name, 
        c.type, 
        f.section, 
        f.AY, 
        f.dept,
        (
            SELECT COUNT(*) 
            FROM marks m 
            WHERE m.course_id = f.course_id 
              AND m.section = f.section 
              AND m.dept = CAST(f.dept AS UNSIGNED)
              AND m.AY = f.AY
        ) AS marks_count
    FROM fvc f
    INNER JOIN courses c ON f.course_id = c.course_id
    WHERE f.faculty_id = ?
    ORDER BY CAST(f.section AS UNSIGNED) ASC
";

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
<title>Summative Assessment Marks</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
body {
    background-color: #f7f9fc;
}
.course-btn {
    margin: 10px;
    padding: 15px 25px;
    font-size: 16px;
    border-radius: 8px;
    min-width: 320px;
    white-space: normal;
    transition: transform 0.2s, background-color 0.2s;
}
.course-btn:hover {
    transform: scale(1.05);
}
.sidebar a {
    color: white;
    text-decoration: none;
}
.sidebar a:hover {
    text-decoration: underline;
}

/* ✅ Color Adjustments */
.btn-warning {
    background-color: #ffca2c;
    border-color: #ffc107;
    color: black;
}
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>(Deemed to be University)</h3>
    <h2>Summative</h2>
</header>

<div class="container-fluid d-flex flex-grow-1">
    <div class="row w-100 flex-grow-1">

        <!-- ✅ Sidebar -->
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>

        <!-- ✅ Main Content -->
        <div class="col-md-9 col-lg-10 text-center" id="content-area">
            <h4 class="mt-4">Select a Course to Print Attendance Sheets</h4>
            <?php if (count($courses) > 0): ?>
                <div class="d-flex flex-wrap justify-content-center mt-4">
                    <?php foreach ($courses as $course): ?>
                        <form action="generate_signature_sheet.php" method="get" target="_blank" style="display:inline;">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <input type="hidden" name="section" value="<?php echo htmlspecialchars($course['section']); ?>">
                            <input type="hidden" name="AY" value="<?php echo htmlspecialchars($course['AY']); ?>">
                            <input type="hidden" name="dept" value="<?php echo htmlspecialchars($course['dept']); ?>">
                            <button type="submit" class="btn btn-warning course-btn">
                                <strong><?php echo htmlspecialchars($course['name']); ?></strong><br>
                                (<?php echo htmlspecialchars($course['type']); ?>, 
                                Sec: <?php echo htmlspecialchars($course['section']); ?>, 
                                Dept: <?php echo htmlspecialchars($course['dept']); ?>, 
                                AY: <?php echo htmlspecialchars($course['AY']); ?>)
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-4 text-danger">
                    No courses assigned to you.<br>
                    If this is unexpected, please contact the Academic Section.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© 2024 - Developed by Dept. of IT</p>
</footer>

</body>
</html>

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
<title>Post Lab Marks</title>
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

/* ✅ Lightbox Popup */
#popup-overlay {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    align-items: center;
    justify-content: center;
}
#popup-box {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
    animation: fadeIn 0.3s ease-in-out;
}
#popup-box h4 {
    margin-bottom: 15px;
    color: #28a745;
}
#popup-box button {
    margin-top: 10px;
}
@keyframes fadeIn {
    from {opacity: 0; transform: scale(0.9);}
    to {opacity: 1; transform: scale(1);}
}

/* ✅ Color Adjustments */
.btn-warning {
    background-color: #ffca2c;
    border-color: #ffc107;
    color: black;
}
.btn-success {
    background-color: #28a745;
    border-color: #218838;
    color: white;
}
.btn-success[disabled] {
    opacity: 0.8;
    cursor: not-allowed;
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

        <!-- ✅ Sidebar -->
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>

        <!-- ✅ Main Content -->
        <div class="col-md-9 col-lg-10 text-center" id="content-area">
            <h4 class="mt-4">Select a Course to Enter Marks</h4>
            <?php if (count($courses) > 0): ?>
                <div class="d-flex flex-wrap justify-content-center mt-4">
                    <?php foreach ($courses as $course): ?>
                        <?php if ($course['marks_count'] > 0): ?>
                            <!-- ✅ Marks already posted -->
                            <button type="button" 
                                    class="btn btn-success course-btn" 
                                    onclick="showPopup('<?php echo htmlspecialchars($course['name']); ?>', '<?php echo htmlspecialchars($course['section']); ?>')"
                                    disabled>
                                <strong><?php echo htmlspecialchars($course['name']); ?></strong><br>
                                (<?php echo htmlspecialchars($course['type']); ?>, 
                                Sec: <?php echo htmlspecialchars($course['section']); ?>, 
                                Dept: <?php echo htmlspecialchars($course['dept']); ?>, 
                                AY: <?php echo htmlspecialchars($course['AY']); ?>)
                            </button>
                        <?php else: ?>
                            <!-- ⚠️ Marks not posted -->
                            <form action="enter_marks_1.php" method="get" style="display:inline;">
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
                        <?php endif; ?>
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

<!-- ✅ Lightbox Popup -->
<div id="popup-overlay">
    <div id="popup-box">
        <h4>Marks Already Posted</h4>
        <p id="popup-message">The marks for this course have already been submitted.</p>
        <p>To make changes, please contact the Autonomous Section.</p>
        <button class="btn btn-primary" onclick="closePopup()">Close</button>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© 2024 - Developed by Dept. of IT</p>
</footer>

<script>
function showPopup(courseName, section) {
    document.getElementById("popup-message").innerText = 
        `The marks for "${courseName}" (Section ${section}) have already been submitted.`;
    document.getElementById("popup-overlay").style.display = "flex";
}
function closePopup() {
    document.getElementById("popup-overlay").style.display = "none";
}
</script>

</body>
</html>

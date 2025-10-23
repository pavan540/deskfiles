<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

$sql = "SELECT c.course_id, c.name, c.type, f.section, f.dept, f.AY
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
<title>Edit Marks - Select Course</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
.course-btn { margin: 10px; padding: 15px 25px; font-size: 16px; border-radius: 8px; }
.sidebar a { color: white; text-decoration: none; }
.sidebar a:hover { text-decoration: underline; }
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
<h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
<h3>Edit Marks</h3>
</header>

<div class="container-fluid d-flex flex-grow-1">
<div class="row w-100 flex-grow-1">
   <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>

    <div class="col-md-9 col-lg-10">
        <center>
            <h4 class="mt-4">Select Course to Edit Marks</h4>
            <?php if (count($courses) > 0): ?>
            <div class="d-flex flex-wrap justify-content-center mt-4">
                <?php foreach ($courses as $c): ?>
                    <form action="edit_marks_action.php" method="get" style="display:inline;">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($c['course_id']); ?>">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($c['section']); ?>">
                        <input type="hidden" name="dept" value="<?php echo htmlspecialchars($c['dept']); ?>">
                        <input type="hidden" name="AY" value="<?php echo htmlspecialchars($c['AY']); ?>">
                        <button type="submit" class="btn btn-primary course-btn">
                            <?php echo htmlspecialchars($c['name']); ?> 
                            (<?php echo htmlspecialchars($c['type']); ?>, 
                             Sec: <?php echo htmlspecialchars($c['section']); ?>, 
                             Dept: <?php echo htmlspecialchars($c['dept']); ?>, 
                             AY: <?php echo htmlspecialchars($c['AY']); ?>)
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p class="mt-4 text-danger">No courses assigned.</p>
            <?php endif; ?>
        </center>
    </div>
</div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
<p>© 2024 - Developed by Dept. of IT</p>
</footer>
</body>
</html>

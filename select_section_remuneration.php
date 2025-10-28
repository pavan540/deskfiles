<?php
session_start();
require_once 'connection.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html"); exit();
}
$faculty_id = (string)$_SESSION['faculty_id'];

/* Fetch assigned course-sections from fvc for this faculty */
$sql = "
    SELECT DISTINCT
        f.course_id,
        c.name AS course_name,
        c.type AS course_type,
        f.section,
        f.dept,
        f.AY
    FROM fvc f
    JOIN courses c ON c.course_id = f.course_id
    WHERE f.faculty_id = ?
    ORDER BY CAST(f.section AS UNSIGNED) ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Helper: check if marks finalized for a given section */
function section_has_finalized_marks(mysqli $conn, $course_id, $section, $dept, $AY): bool {
    $sql = "SELECT 1 FROM marks WHERE course_id=? AND section=? AND dept=? AND AY=? AND is_finalized=1 LIMIT 1";
    $st = $conn->prepare($sql);
    $st->bind_param('ssis', $course_id, $section, $dept, $AY);
    $st->execute();
    $ok = $st->get_result()->num_rows > 0;
    $st->close();
    return $ok;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Generate Remuneration - Select Section</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
body { background: #f7f9fc; }
.course-btn { margin:10px; padding:15px 22px; font-size:16px; border-radius:8px; min-width:320px; white-space:normal; transition: transform .15s; }
.course-btn:hover { transform: scale(1.03); }
.btn-disabled { background:#d6d6d6; border-color:#cfcfcf; color:#666; cursor:not-allowed; }
.sidebar a{ color:white; text-decoration:none; }
.sidebar a:hover{ text-decoration:underline; }
.header { background:#007bff; color:white; padding:16px; text-align:center; }
.footer { background:#007bff; color:white; padding:8px; text-align:center; }
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="header">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>Post Lab - Generate Remuneration</h3>
</header>

<div class="container-fluid d-flex flex-grow-1">
  <div class="row w-100">
    <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3 sidebar">
        <?php include('faculty_menu.php'); ?>
    </nav>

    <main class="col-md-9 col-lg-10 p-4">
        <h4>Select Section (only sections with finalized marks are active)</h4>
        <?php if (count($rows)===0): ?>
            <p class="text-danger">No courses/sections assigned to you. Contact Academic Section if this seems incorrect.</p>
        <?php else: ?>
            <div class="d-flex flex-wrap justify-content-start mt-3">
                <?php foreach($rows as $r):
                    $has = section_has_finalized_marks($conn, $r['course_id'], $r['section'], $r['dept'], $r['AY']);
                ?>
                    <?php if ($has): ?>
                        <form action="generate_remuneration_section.php" method="get" style="display:inline-block;">
                            <input type="hidden" name="course_id" value="<?= e($r['course_id']) ?>">
                            <input type="hidden" name="section" value="<?= e($r['section']) ?>">
                            <input type="hidden" name="dept" value="<?= e($r['dept']) ?>">
                            <input type="hidden" name="AY" value="<?= e($r['AY']) ?>">
                            <button type="submit" class="btn btn-warning course-btn">
                                <strong><?= e($r['course_name']) ?></strong><br>
                                (<?= e($r['course_type']) ?>, Sec: <?= e($r['section']) ?>, Dept: <?= e($r['dept']) ?>, AY: <?= e($r['AY']) ?>)
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="course-btn btn-disabled" title="Marks not finalized for this section">
                            <strong><?= e($r['course_name']) ?></strong><br>
                            (<?= e($r['course_type']) ?>, Sec: <?= e($r['section']) ?>, Dept: <?= e($r['dept']) ?>, AY: <?= e($r['AY']) ?>)
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
  </div>
</div>

<footer class="footer mt-auto">
    <p>© <?=date('Y')?> - Developed by Dept. of IT</p>
</footer>
</body>
</html>

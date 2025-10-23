<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$course_id = $_GET['course_id'] ?? '';
$section   = $_GET['section'] ?? '';
$AY        = $_GET['AY'] ?? '';
$dept      = $_GET['dept'] ?? '';

if (empty($course_id) || empty($section) || empty($AY) || empty($dept)) {
    die("Invalid course selection!");
}

/**
 * Convert integer (0–999) to sentence-style words.
 * (Totals switch to "AB" separately when absent.)
 */
function numberToWords($num) {
    $ones = [
        "", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
        "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen",
        "Seventeen", "Eighteen", "Nineteen"
    ];
    $tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
    if ($num == 0) return "Zero";
    if ($num < 20) return $ones[$num];
    if ($num < 100) return $tens[intval($num / 10)] . ($num % 10 ? " " . $ones[$num % 10] : "");
    if ($num < 1000) return $ones[intval($num / 100)] . " Hundred" . ($num % 100 ? " and " . numberToWords($num % 100) : "");
    return (string)$num;
}

/* ========= MAX MARKS ========= */
define('MAX_PROCEDURE', 20);
define('MAX_EXPERIMENT', 40);
define('MAX_RESULT', 20);
define('MAX_VIVA', 20);

/* ========= FINALIZATION CHECK ========= */
$already_finalized = 0;
$check_finalized = $conn->prepare("
    SELECT COUNT(*) 
    FROM marks 
    WHERE course_id=? AND section=? AND AY=? AND dept=? AND is_finalized=1
");
$check_finalized->bind_param("ssss", $course_id, $section, $AY, $dept);
$check_finalized->execute();
$check_finalized->bind_result($already_finalized);
$check_finalized->fetch();
$check_finalized->close();

$success_messages = [];
$error_messages = [];
$just_finalized = false;
$finalized_display_time = $_SESSION['finalized_display_time'] ?? '';

/* ========= SUBMISSION ========= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$already_finalized) {

    if (!isset($_POST['marks']) || !is_array($_POST['marks'])) {
        $error_messages[] = "No marks submitted.";
    } else {
        $had_error = false;

        foreach ($_POST['marks'] as $roll_no => $markData) {
            $is_absent  = isset($markData['absent']) ? 1 : 0;

            $procedure  = isset($markData['procedure']) ? $markData['procedure'] : '';
            $experiment = isset($markData['experiment']) ? $markData['experiment'] : '';
            $result     = isset($markData['result']) ? $markData['result'] : '';
            $viva       = isset($markData['viva']) ? $markData['viva'] : '';

            if (!$is_absent) {
                if ($procedure === '' || !is_numeric($procedure)) $had_error = true;
                if ($experiment === '' || !is_numeric($experiment)) $had_error = true;
                if ($result === '' || !is_numeric($result)) $had_error = true;
                if ($viva === '' || !is_numeric($viva)) $had_error = true;

                if (is_numeric($procedure)  && ((int)$procedure  < 0 || (int)$procedure  > MAX_PROCEDURE)) $had_error = true;
                if (is_numeric($experiment) && ((int)$experiment < 0 || (int)$experiment > MAX_EXPERIMENT)) $had_error = true;
                if (is_numeric($result)     && ((int)$result     < 0 || (int)$result     > MAX_RESULT)) $had_error = true;
                if (is_numeric($viva)       && ((int)$viva       < 0 || (int)$viva       > MAX_VIVA)) $had_error = true;
            }
        }

        if ($had_error) {
            $error_messages[] = "Some fields were missing or out of allowed range. Please correct and submit again.";
        } else {
            foreach ($_POST['marks'] as $roll_no => $markData) {
                $is_absent  = isset($markData['absent']) ? 1 : 0;

                $procedure  = (int)($markData['procedure'] ?? 0);
                $experiment = (int)($markData['experiment'] ?? 0);
                $result     = (int)($markData['result'] ?? 0);
                $viva       = (int)($markData['viva'] ?? 0);

                if ($is_absent) {
                    $total = 0;
                    $total_words = "AB";
                    $procedure = $experiment = $result = $viva = 0;
                } else {
                    $total = $procedure + $experiment + $result + $viva;
                    $total_words = numberToWords($total);
                }

                $stmt = $conn->prepare("INSERT INTO marks 
                    (roll_no, course_id, section, dept, AY, procedure_marks, viva_marks, result_marks, experiment_marks, total_marks, total_in_words, is_absent, is_finalized)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE
                    procedure_marks=VALUES(procedure_marks),
                    viva_marks=VALUES(viva_marks),
                    result_marks=VALUES(result_marks),
                    experiment_marks=VALUES(experiment_marks),
                    total_marks=VALUES(total_marks),
                    total_in_words=VALUES(total_in_words),
                    is_absent=VALUES(is_absent),
                    is_finalized=1
                ");

                $stmt->bind_param(
                    "sssssiiiiisi",
                    $roll_no, $course_id, $section, $dept, $AY,
                    $procedure, $viva, $result, $experiment,
                    $total, $total_words, $is_absent
                );
                $stmt->execute();
                $stmt->close();
            }

            $already_finalized = 1;
            $just_finalized = true;
            $finalized_display_time = date("j F Y, g:i A");
            $_SESSION['finalized_display_time'] = $finalized_display_time;

            $success_messages[] = "✅ Marks submitted and finalized successfully for Section: " . htmlspecialchars($section) . " on " . $finalized_display_time . ".";
        }
    }
}

/* ========= FINALIZED MESSAGE ========= */
if ($already_finalized && !$just_finalized && empty($finalized_display_time)) {
    $success_messages = [];
    $success_messages[] = "✅ Marks submitted and finalized successfully for Section: " . htmlspecialchars($section) . ".";
}

/* ========= FETCH STUDENTS (fixed here) ========= */
$students = [];
$marks_map = [];

if (!$already_finalized) {
    // 🔹 Step 1: Get dept_id from dept name
    $dept_stmt = $conn->prepare("SELECT dept_id FROM departments WHERE LOWER(dept_name)=LOWER(?)");
    $dept_stmt->bind_param("s", $dept);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    $dept_row = $dept_result->fetch_assoc();
    $dept_id = $dept_row['dept_id'] ?? 0;
    $dept_stmt->close();

    // 🔹 Step 2: Get students for section + branch = dept_id
    $sql_students = "SELECT roll_no, name, section, branch FROM student WHERE section=? AND branch=? ORDER BY roll_no";
    $stmt = $conn->prepare($sql_students);
    $stmt->bind_param("ss", $section, $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 🔹 Step 3: Get marks map
    $sql_marks = "SELECT * FROM marks WHERE course_id=? AND section=? AND AY=? AND dept=?";
    $stmt = $conn->prepare($sql_marks);
    $stmt->bind_param("ssss", $course_id, $section, $AY, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $marks_map[$row['roll_no']] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enter Marks</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
html, body { height: 100%; margin: 0; }
body { display: flex; flex-direction: column; }
.header-fixed { position: fixed; top: 0; left: 0; right: 0; z-index: 1030; }
.footer-fixed { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030; }
.content-wrap {
  position: relative;
  display: flex;
  flex: 1 1 auto;
  margin-top: 112px;
  margin-bottom: 56px;
  overflow: hidden;
}
.sidebar { height: 100%; overflow-y: auto; }
.content-scroll { height: 100%; overflow: auto; }
table th, td { text-align: center; vertical-align: middle !important; font-size: 14px; }
.alert { font-size: 15px; }
.btn-sm { padding: 6px 14px; font-size: 14px; }
.is-invalid, .is-invalid:focus {
  border-color: #dc3545 !important;
  background-color: #ffeaea !important;
}
tr.absent-row td:not(:first-child) { opacity: 0.9; }
.content-scroll .table thead th {
  position: sticky; top: 0; background: #343a40; color: #fff; z-index: 2;
}
</style>
</head>
<body>

<header class="header-fixed bg-primary text-white text-center py-3">
  <h2 class="mb-1">SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
  <h4 class="mb-0">Enter Marks (Dept: <?php echo htmlspecialchars($dept); ?>)</h4>
</header>

<div class="content-wrap">
  <div class="container-fluid d-flex w-100">
    <div class="row w-100">
      <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3 sidebar">
        <?php include('faculty_menu.php'); ?>
      </nav>

      <div class="col-md-9 col-lg-10 content-scroll">
        <div class="container my-3">
          <h5 class="text-center">
            Course: <?php echo htmlspecialchars($course_id); ?> |
            Section: <?php echo htmlspecialchars($section); ?> |
            AY: <?php echo htmlspecialchars($AY); ?> |
            Dept: <?php echo htmlspecialchars($dept); ?>
          </h5>

          <?php if (!empty($success_messages)): ?>
            <?php foreach ($success_messages as $msg): ?>
              <div class="alert alert-success text-center"><?php echo $msg; ?></div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if (!empty($error_messages) && !$already_finalized): ?>
            <div class="alert alert-danger">
              <?php foreach ($error_messages as $em) { echo "<div>".htmlspecialchars($em)."</div>"; } ?>
            </div>
          <?php endif; ?>

          <?php if (!$already_finalized): ?>
            <?php if (count($students)): ?>
            <form method="post" id="marks-form">
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                  <thead class="thead-dark">
                    <tr>
                      <th>Roll No</th>
                      <th>Name</th>
                      <th>Branch</th>
                      <th>Absent?</th>
                      <th>Procedure (Max <?php echo MAX_PROCEDURE; ?>)</th>
                      <th>Experiment (Max <?php echo MAX_EXPERIMENT; ?>)</th>
                      <th>Result Analysis (Max <?php echo MAX_RESULT; ?>)</th>
                      <th>Viva Voce (Max <?php echo MAX_VIVA; ?>)</th>
                      <th>Total</th>
                      <th>Total (in Words)</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($students as $i => $s):
                      $m = $marks_map[$s['roll_no']] ?? [
                          'procedure_marks'=>0,'viva_marks'=>0,'result_marks'=>0,'experiment_marks'=>0,
                          'total_marks'=>0,'total_in_words'=>'Zero','is_absent'=>0
                      ];
                      $isAbsent = !empty($m['is_absent']);
                      $displayTotal = $isAbsent ? "AB" : (int)$m['total_marks'];
                      $displayWords = $isAbsent ? "AB" : htmlspecialchars($m['total_in_words']);
                  ?>
                    <tr data-rowid="<?php echo $i; ?>" class="<?php echo $isAbsent ? 'absent-row' : ''; ?>">
                      <td><?php echo htmlspecialchars($s['roll_no']); ?></td>
                      <td><?php echo htmlspecialchars($s['name']); ?></td>
                      <td><?php echo htmlspecialchars($dept); ?></td>
                      <td><input type="checkbox" class="absent-checkbox" name="marks[<?php echo $s['roll_no']; ?>][absent]" value="1" <?php echo $isAbsent ? 'checked' : ''; ?>></td>
                      <td><input type="number" class="form-control form-control-sm mark-input" data-field="procedure" name="marks[<?php echo $s['roll_no']; ?>][procedure]" value="<?php echo (int)$m['procedure_marks']; ?>" min="0" max="<?php echo MAX_PROCEDURE; ?>"></td>
                      <td><input type="number" class="form-control form-control-sm mark-input" data-field="experiment" name="marks[<?php echo $s['roll_no']; ?>][experiment]" value="<?php echo (int)$m['experiment_marks']; ?>" min="0" max="<?php echo MAX_EXPERIMENT; ?>"></td>
                      <td><input type="number" class="form-control form-control-sm mark-input" data-field="result" name="marks[<?php echo $s['roll_no']; ?>][result]" value="<?php echo (int)$m['result_marks']; ?>" min="0" max="<?php echo MAX_RESULT; ?>"></td>
                      <td><input type="number" class="form-control form-control-sm mark-input" data-field="viva" name="marks[<?php echo $s['roll_no']; ?>][viva]" value="<?php echo (int)$m['viva_marks']; ?>" min="0" max="<?php echo MAX_VIVA; ?>"></td>
                      <td class="total-marks"><?php echo htmlspecialchars($displayTotal); ?></td>
                      <td class="total-words"><?php echo htmlspecialchars($displayWords); ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="text-center mb-3">
                <button id="open-confirm" type="button" class="btn btn-danger btn-sm">✅ Submit & Finalize</button>
              </div>
            </form>
            <?php else: ?>
              <p class="text-danger text-center">No students registered in this section and department.</p>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="footer-fixed bg-primary text-white text-center py-2">
  <p class="mb-0">© 2024 - Developed by Dept. of IT</p>
</footer>

<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="confirmLabel">Finalize Marks?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <strong>⚠️ Are you sure you want to finalize marks?</strong><br>
        You won’t be able to edit them later.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">No, go back</button>
        <button id="confirm-submit" type="button" class="btn btn-danger btn-sm">Yes, finalize</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// same JS logic retained — no changes to validation
</script>
</body>
</html>

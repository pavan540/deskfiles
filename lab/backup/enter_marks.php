<?php
session_start();
require_once 'connection.php';

// Redirect if not logged in
if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$course_id = $_GET['course_id'] ?? '';
$section   = $_GET['section'] ?? '';
$AY        = $_GET['AY'] ?? '';

if (empty($course_id) || empty($section) || empty($AY)) {
    die("Invalid course selection!");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST['marks'] as $roll_no => $markData) {
        $procedure  = intval($markData['procedure']);
        $viva       = intval($markData['viva']);
        $result     = intval($markData['result']);
        $experiment = intval($markData['experiment']);
        $is_absent  = isset($markData['absent']) && $markData['absent'] == "1" ? 1 : 0;

        // Skip finalized rows
        $check = $conn->prepare("SELECT is_finalized FROM marks WHERE roll_no=? AND course_id=? AND section=? AND AY=?");
        $check->bind_param("ssss", $roll_no, $course_id, $section, $AY);
        $check->execute();
        $check->bind_result($is_finalized);
        $check->fetch();
        $check->close();

        if ($is_finalized == 1) {
            continue;
        }

        // Insert/update marks
        $stmt = $conn->prepare("INSERT INTO marks 
            (roll_no, course_id, section, AY, procedure_marks, viva_marks, result_marks, experiment_marks, is_absent, is_finalized)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE
            procedure_marks = VALUES(procedure_marks),
            viva_marks = VALUES(viva_marks),
            result_marks = VALUES(result_marks),
            experiment_marks = VALUES(experiment_marks),
            is_absent = VALUES(is_absent)");
        $stmt->bind_param("ssssiiiii", $roll_no, $course_id, $section, $AY, $procedure, $viva, $result, $experiment, $is_absent);
        $stmt->execute();
        $stmt->close();
    }

    // Finalize if "Submit Marks" pressed
    if (isset($_POST['finalize'])) {
        $finalize = $conn->prepare("UPDATE marks SET is_finalized = 1 
            WHERE course_id=? AND section=? AND AY=?");
        $finalize->bind_param("sss", $course_id, $section, $AY);
        $finalize->execute();
        $finalize->close();

        echo "<script>alert('Marks have been submitted and finalized.'); window.location.href='post_lab_marks.php';</script>";
        exit();
    }

    echo "<script>alert('Marks saved successfully!'); window.location.href='enter_marks.php?course_id=$course_id&section=$section&AY=$AY';</script>";
    exit();
}

// Fetch students + marks
$sql = "SELECT s.roll_no, s.name, s.section, s.branch,
        m.procedure_marks, m.viva_marks, m.result_marks, m.experiment_marks, 
        m.is_absent, m.is_finalized
        FROM svc v
        INNER JOIN student s ON v.roll_no = s.roll_no
        LEFT JOIN marks m ON m.roll_no = s.roll_no 
            AND m.course_id = v.course_id AND m.AY = v.AY AND m.section = s.section
        WHERE v.course_id = ? AND v.AY = ? AND s.section = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $course_id, $AY, $section);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Marks</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        table th, table td {
            text-align: center;
            vertical-align: middle !important;
            padding: 6px 8px;
            font-size: 14px;
        }
        #content-area .scrollable {
            flex-grow: 1;
            overflow: auto;
        }
    </style>
    <script>
    // Convert number to words (basic)
    function numberToWords(num) {
        const a = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
                   "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen",
                   "Seventeen", "Eighteen", "Nineteen"];
        const b = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
        if (num === 0) return "Zero";
        if (num < 20) return a[num];
        if (num < 100) return b[Math.floor(num / 10)] + (num % 10 ? " " + a[num % 10] : "");
        if (num < 1000) return a[Math.floor(num / 100)] + " Hundred" + (num % 100 ? " and " + numberToWords(num % 100) : "");
        return num.toString();
    }

    function calculateTotal(rowId) {
        const row = document.getElementById("row-" + rowId);
        if (!row) return;
        const inputs = row.querySelectorAll("input[type='number']");
        let total = 0;
        inputs.forEach(inp => {
            if (!inp.disabled && inp.value !== "") total += parseInt(inp.value) || 0;
        });
        row.querySelector(".total-marks").innerText = total;
        row.querySelector(".total-words").innerText = numberToWords(total);
    }

    function toggleMarks(rowId, checked) {
        const inputs = document.querySelectorAll("#row-"+rowId+" input[type='number']");
        inputs.forEach(inp => inp.disabled = checked);
        row = document.getElementById("row-"+rowId);
        row.querySelector(".total-marks").innerText = checked ? "-" : "0";
        row.querySelector(".total-words").innerText = checked ? "-" : "Zero";
    }
    </script>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>(Deemed to be University)</h3>
    <h2>Enter Marks</h2>
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

        <!-- Scrollable Content -->
        <div class="col-md-9 col-lg-10 d-flex flex-column" id="content-area">
            <h5 class="text-center mt-3">Course: <?php echo htmlspecialchars($course_id); ?> | 
                Section: <?php echo htmlspecialchars($section); ?> | 
                AY: <?php echo htmlspecialchars($AY); ?></h5>

            <div class="scrollable mt-3">
                <?php if (count($students) > 0): ?>
                    <form method="post">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Name</th>
                                        <th>Branch</th>
                                        <th>Absent?</th>
                                        <th>Procedure</th>
                                        <th>Viva</th>
                                        <th>Result</th>
                                        <th>Experiment</th>
                                        <th>Total</th>
                                        <th>Total in Words</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): 
                                        $total = $student['procedure_marks'] + $student['viva_marks'] + $student['result_marks'] + $student['experiment_marks'];
                                    ?>
                                        <tr id="row-<?php echo $index; ?>">
                                            <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['branch']); ?></td>
                                            <td>
                                                <input type="checkbox" name="marks[<?php echo $student['roll_no']; ?>][absent]" value="1"
                                                    <?php echo $student['is_absent'] ? 'checked' : ''; ?>
                                                    onchange="toggleMarks(<?php echo $index; ?>, this.checked)">
                                            </td>
                                            <td><input type="number" name="marks[<?php echo $student['roll_no']; ?>][procedure]" class="form-control form-control-sm"
                                                       value="<?php echo htmlspecialchars($student['procedure_marks']); ?>"
                                                       <?php echo $student['is_absent'] ? 'disabled' : ''; ?>
                                                       oninput="calculateTotal(<?php echo $index; ?>)"></td>
                                            <td><input type="number" name="marks[<?php echo $student['roll_no']; ?>][viva]" class="form-control form-control-sm"
                                                       value="<?php echo htmlspecialchars($student['viva_marks']); ?>"
                                                       <?php echo $student['is_absent'] ? 'disabled' : ''; ?>
                                                       oninput="calculateTotal(<?php echo $index; ?>)"></td>
                                            <td><input type="number" name="marks[<?php echo $student['roll_no']; ?>][result]" class="form-control form-control-sm"
                                                       value="<?php echo htmlspecialchars($student['result_marks']); ?>"
                                                       <?php echo $student['is_absent'] ? 'disabled' : ''; ?>
                                                       oninput="calculateTotal(<?php echo $index; ?>)"></td>
                                            <td><input type="number" name="marks[<?php echo $student['roll_no']; ?>][experiment]" class="form-control form-control-sm"
                                                       value="<?php echo htmlspecialchars($student['experiment_marks']); ?>"
                                                       <?php echo $student['is_absent'] ? 'disabled' : ''; ?>
                                                       oninput="calculateTotal(<?php echo $index; ?>)"></td>
                                            <td class="total-marks"><?php echo $student['is_absent'] ? '-' : $total; ?></td>
                                            <td class="total-words"><?php echo $student['is_absent'] ? '-' : "<script>document.write(numberToWords($total));</script>"; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mb-3">
                            <button type="submit" name="save" class="btn btn-success btn-sm">Save Marks</button>
                            <button type="submit" name="finalize" class="btn btn-danger btn-sm">Submit Marks</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-danger text-center mt-4">No students registered for this course.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© 2024 Copyrights reserved - Developed by Dept. of IT</p>
</footer>
<script>
// Initialize totals
<?php foreach ($students as $index => $s): ?>
    calculateTotal(<?php echo $index; ?>);
<?php endforeach; ?>
</script>
</body>
</html>

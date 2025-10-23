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

$error_messages = [];
$success_messages = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST['marks'] as $roll_no => $markData) {
        $procedure  = intval($markData['procedure'] ?? 0);
        $viva       = intval($markData['viva'] ?? 0);
        $result     = intval($markData['result'] ?? 0);
        $experiment = intval($markData['experiment'] ?? 0);
        $is_absent  = isset($markData['absent']) && $markData['absent'] == "1" ? 1 : 0;

        $check = $conn->prepare("SELECT is_finalized FROM marks WHERE roll_no=? AND course_id=? AND section=? AND AY=? AND dept=?");
        $check->bind_param("sssss", $roll_no, $course_id, $section, $AY, $dept);
        $check->execute();
        $check->bind_result($is_finalized);
        $check->fetch();
        $check->close();

        if ($is_finalized == 1) continue;

        $stmt = $conn->prepare("INSERT INTO marks 
            (roll_no, course_id, section, dept, AY, procedure_marks, viva_marks, result_marks, experiment_marks, is_absent, is_finalized)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE
            procedure_marks = VALUES(procedure_marks),
            viva_marks = VALUES(viva_marks),
            result_marks = VALUES(result_marks),
            experiment_marks = VALUES(experiment_marks),
            is_absent = VALUES(is_absent)");
        $stmt->bind_param("ssssssiiiii", $roll_no, $course_id, $section, $dept, $AY, $procedure, $viva, $result, $experiment, $is_absent);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['finalize'])) {
        $finalize = $conn->prepare("UPDATE marks SET is_finalized = 1 WHERE course_id=? AND section=? AND AY=? AND dept=?");
        $finalize->bind_param("ssss", $course_id, $section, $AY, $dept);
        $finalize->execute();
        $finalize->close();
        $success_messages[] = "Marks finalized successfully for Dept: $dept.";
    } else {
        $success_messages[] = "Marks saved successfully for Dept: $dept.";
    }
}

$sql = "SELECT s.roll_no, s.name, s.section, s.branch,
        COALESCE(m.procedure_marks, 0) AS procedure_marks,
        COALESCE(m.viva_marks, 0) AS viva_marks,
        COALESCE(m.result_marks, 0) AS result_marks,
        COALESCE(m.experiment_marks, 0) AS experiment_marks,
        COALESCE(m.is_absent, 0) AS is_absent,
        COALESCE(m.is_finalized, 0) AS is_finalized
        FROM svc v
        INNER JOIN student s ON v.roll_no = s.roll_no
        LEFT JOIN marks m ON m.roll_no = s.roll_no 
            AND m.course_id = v.course_id 
            AND m.AY = v.AY 
            AND m.section = s.section
            AND m.dept = v.dept
        WHERE v.course_id = ? AND v.AY = ? AND s.section = ? AND v.dept = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $course_id, $AY, $section, $dept);
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
<title>Enter Marks</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
table th, td { text-align: center; vertical-align: middle !important; font-size: 14px; }
.sidebar a { color: white; text-decoration: none; }
.sidebar a:hover { text-decoration: underline; }
.alert { font-size: 15px; }
.btn-sm { padding: 6px 14px; font-size: 14px; }
</style>
<script>
function numberToWords(num) {
    const a=["","One","Two","Three","Four","Five","Six","Seven","Eight","Nine","Ten","Eleven","Twelve","Thirteen","Fourteen","Fifteen","Sixteen","Seventeen","Eighteen","Nineteen"];
    const b=["","","Twenty","Thirty","Forty","Fifty","Sixty","Seventy","Eighty","Ninety"];
    if(num===0)return"Zero";
    if(num<20)return a[num];
    if(num<100)return b[Math.floor(num/10)] + (num%10?" "+a[num%10]:"");
    if(num<1000)return a[Math.floor(num/100)]+" Hundred"+(num%100?" and "+numberToWords(num%100):"");
    return num.toString();
}
function calculateTotal(id){
    const r=document.getElementById("row-"+id);
    let t=0;
    r.querySelectorAll("input[type='number']").forEach(i=>{if(!i.disabled)t+=parseInt(i.value)||0});
    r.querySelector(".total-marks").innerText=t;
    r.querySelector(".total-words").innerText=numberToWords(t);
}
function toggleMarks(id,c){
    const inputs=document.querySelectorAll("#row-"+id+" input[type='number']");
    inputs.forEach(i=>i.disabled=c);
    const r=document.getElementById("row-"+id);
    r.querySelector(".total-marks").innerText=c?"-":"0";
    r.querySelector(".total-words").innerText=c?"-":"Zero";
}
</script>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
<h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
<h4>Enter Marks (Dept: <?php echo htmlspecialchars($dept); ?>)</h4>
</header>

<div class="container-fluid d-flex flex-grow-1">
    <div class="row w-100 flex-grow-1">
        <!-- Sidebar -->
         <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>

        <!-- Content Area -->
        <div class="col-md-9 col-lg-10">
            <div class="container my-3">
                <h5 class="text-center">Course: <?php echo $course_id; ?> | Section: <?php echo $section; ?> | AY: <?php echo $AY; ?></h5>

                <?php foreach ($success_messages as $msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endforeach; ?>
                <?php foreach ($error_messages as $msg): ?><div class="alert alert-danger"><?php echo $msg; ?></div><?php endforeach; ?>

                <?php if (count($students)): ?>
                <form method="post">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Roll No</th><th>Name</th><th>Branch</th><th>Absent?</th>
                                    <th>Procedure</th><th>Viva</th><th>Result</th><th>Experiment</th>
                                    <th>Total</th><th>Total (in Words)</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($students as $i=>$s): 
                            $total=$s['is_absent']?0:($s['procedure_marks']+$s['viva_marks']+$s['result_marks']+$s['experiment_marks']); ?>
                            <tr id="row-<?php echo $i; ?>">
                                <td><?php echo $s['roll_no']; ?></td>
                                <td><?php echo $s['name']; ?></td>
                                <td><?php echo $s['branch']; ?></td>
                                <td><input type="checkbox" name="marks[<?php echo $s['roll_no']; ?>][absent]" value="1" <?php echo $s['is_absent']?'checked':''; ?> onchange="toggleMarks(<?php echo $i; ?>,this.checked)"></td>
                                <td><input type="number" name="marks[<?php echo $s['roll_no']; ?>][procedure]" value="<?php echo $s['procedure_marks']; ?>" class="form-control form-control-sm" oninput="calculateTotal(<?php echo $i; ?>)"></td>
                                <td><input type="number" name="marks[<?php echo $s['roll_no']; ?>][viva]" value="<?php echo $s['viva_marks']; ?>" class="form-control form-control-sm" oninput="calculateTotal(<?php echo $i; ?>)"></td>
                                <td><input type="number" name="marks[<?php echo $s['roll_no']; ?>][result]" value="<?php echo $s['result_marks']; ?>" class="form-control form-control-sm" oninput="calculateTotal(<?php echo $i; ?>)"></td>
                                <td><input type="number" name="marks[<?php echo $s['roll_no']; ?>][experiment]" value="<?php echo $s['experiment_marks']; ?>" class="form-control form-control-sm" oninput="calculateTotal(<?php echo $i; ?>)"></td>
                                <td class="total-marks"><?php echo $total; ?></td>
                                <td class="total-words"><script>document.write(numberToWords(<?php echo $total; ?>));</script></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mb-3">
                        <button type="submit" name="save" class="btn btn-success btn-sm">💾 Save Marks (<?php echo $dept; ?>)</button>
                        <button type="submit" name="finalize" class="btn btn-danger btn-sm">✅ Submit Marks (<?php echo $dept; ?>)</button>
                    </div>
                </form>
                <?php else: ?><p class="text-danger text-center">No students registered.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
<p>© 2024 - Developed by Dept. of IT</p>
</footer>
<script><?php foreach($students as $i=>$s){echo "calculateTotal($i);";} ?></script>
</body>
</html>

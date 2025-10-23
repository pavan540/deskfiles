<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$course_id = $_GET['course_id'] ?? '';
$section = $_GET['section'] ?? '';
$dept = $_GET['dept'] ?? '';
$AY = $_GET['AY'] ?? '';

if (empty($course_id) || empty($section) || empty($dept) || empty($AY)) {
    die("Invalid course selection!");
}

$success = $error = "";

// ✅ Handle Update of one row
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['roll_no'])) {
    $roll_no = $_POST['roll_no'];
    $procedure = intval($_POST['procedure'] ?? 0);
    $viva = intval($_POST['viva'] ?? 0);
    $result = intval($_POST['result'] ?? 0);
    $experiment = intval($_POST['experiment'] ?? 0);
    $is_absent = isset($_POST['absent']) ? 1 : 0;

    $total = $is_absent ? 0 : $procedure + $viva + $result + $experiment;

    function numberToWords($num) {
        $ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
            "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen",
            "Seventeen", "Eighteen", "Nineteen"];
        $tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
        if ($num == 0) return "Zero";
        if ($num < 20) return $ones[$num];
        if ($num < 100) return $tens[intval($num / 10)] . ($num % 10 ? " " . $ones[$num % 10] : "");
        if ($num < 1000) return $ones[intval($num / 100)] . " Hundred" . ($num % 100 ? " and " . numberToWords($num % 100) : "");
        return (string)$num;
    }
    $total_words = numberToWords($total);

    $stmt = $conn->prepare("UPDATE marks 
        SET procedure_marks=?, viva_marks=?, result_marks=?, experiment_marks=?, 
            total_marks=?, total_in_words=?, is_absent=? 
        WHERE roll_no=? AND course_id=? AND section=? AND dept=? AND AY=?");
    $stmt->bind_param("iiiiisisssss", 
        $procedure, $viva, $result, $experiment, 
        $total, $total_words, $is_absent, 
        $roll_no, $course_id, $section, $dept, $AY);

    if ($stmt->execute()) {
        $success = "✅ Marks updated successfully for Roll No: $roll_no";
    } else {
        $error = "❌ Error updating marks: " . $conn->error;
    }
    $stmt->close();
}

// ✅ Fetch data
$sql = "SELECT m.roll_no, s.name, s.branch, 
        m.procedure_marks, m.viva_marks, m.result_marks, m.experiment_marks,
        m.total_marks, m.total_in_words, m.is_absent
        FROM marks m
        JOIN student s ON m.roll_no = s.roll_no
        WHERE m.course_id=? AND m.section=? AND m.dept=? AND m.AY=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $course_id, $section, $dept, $AY);
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
<title>Edit Marks</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
table th, td { text-align: center; vertical-align: middle !important; font-size: 14px; }
.alert { font-size: 15px; margin-top: 10px; }
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>Edit Marks - <?php echo "$course_id (Sec: $section, Dept: $dept, AY: $AY)"; ?></h3>
</header>

<div class="container-fluid d-flex flex-grow-1">
<div class="row w-100 flex-grow-1">
    <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>


    <!-- Content -->
    <div class="col-md-9 col-lg-10">
        <div class="container mt-4">
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Procedure</th>
                            <th>Viva</th>
                            <th>Result</th>
                            <th>Experiment</th>
                            <th>Total</th>
                            <th>Total (in Words)</th>
                            <th>Absent?</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <form method="post">
                            <tr>
                                <td><?php echo $s['roll_no']; ?><input type="hidden" name="roll_no" value="<?php echo $s['roll_no']; ?>"></td>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><?php echo htmlspecialchars($s['branch']); ?></td>
                                <td><input type="number" name="procedure" value="<?php echo $s['procedure_marks']; ?>" class="form-control form-control-sm"></td>
                                <td><input type="number" name="viva" value="<?php echo $s['viva_marks']; ?>" class="form-control form-control-sm"></td>
                                <td><input type="number" name="result" value="<?php echo $s['result_marks']; ?>" class="form-control form-control-sm"></td>
                                <td><input type="number" name="experiment" value="<?php echo $s['experiment_marks']; ?>" class="form-control form-control-sm"></td>
                                <td><?php echo $s['total_marks']; ?></td>
                                <td><?php echo htmlspecialchars($s['total_in_words']); ?></td>
                                <td><input type="checkbox" name="absent" <?php echo $s['is_absent'] ? 'checked' : ''; ?>></td>
                                <td><button type="submit" class="btn btn-success btn-sm">Update</button></td>
                            </tr>
                        </form>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
<p>© 2024 - Developed by Dept. of IT</p>
</footer>
</body>
</html>

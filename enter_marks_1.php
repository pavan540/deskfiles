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

/* ========= GET DEPT ID (Supports both numeric and name) ========= */
if (is_numeric($dept)) {
    $dept_id = (int)$dept;
    $stmt = $conn->prepare("SELECT dept_name FROM departments WHERE dept_id=?");
    $stmt->bind_param("i", $dept_id);
} else {
    $stmt = $conn->prepare("SELECT dept_id, dept_name FROM departments WHERE LOWER(dept_name)=LOWER(?)");
    $stmt->bind_param("s", $dept);
}
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (is_numeric($dept)) {
    $dept_name = $row['dept_name'] ?? "Unknown";
} else {
    $dept_id = $row['dept_id'] ?? 0;
    $dept_name = $row['dept_name'] ?? $dept;
}
$stmt->close();
if (empty($dept_id)) die("Invalid department name or code: " . htmlspecialchars($dept));

/* ========= FINALIZATION CHECK ========= */
$already_finalized = 0;
$check = $conn->prepare("SELECT COUNT(*) FROM marks WHERE course_id=? AND section=? AND AY=? AND dept=? AND is_finalized=1");
$check->bind_param("sssi", $course_id, $section, $AY, $dept_id);
$check->execute();
$check->bind_result($already_finalized);
$check->fetch();
$check->close();

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

        foreach ($_POST['marks'] as $roll_no => $m) {
            $is_abs = isset($m['absent']) ? 1 : 0;
            $p = $m['procedure'] ?? '';
            $e = $m['experiment'] ?? '';
            $r = $m['result'] ?? '';
            $v = $m['viva'] ?? '';
            if (!$is_abs) {
                if ($p === '' || !is_numeric($p) || $p < 0 || $p > MAX_PROCEDURE) $had_error = true;
                if ($e === '' || !is_numeric($e) || $e < 0 || $e > MAX_EXPERIMENT) $had_error = true;
                if ($r === '' || !is_numeric($r) || $r < 0 || $r > MAX_RESULT) $had_error = true;
                if ($v === '' || !is_numeric($v) || $v < 0 || $v > MAX_VIVA) $had_error = true;
            }
        }

        if ($had_error) {
            $error_messages[] = "Some fields were missing or out of allowed range. Please correct and submit again.";
        } else {
            foreach ($_POST['marks'] as $roll_no => $m) {
                $is_abs = isset($m['absent']) ? 1 : 0;
                $p = (int)($m['procedure'] ?? 0);
                $e = (int)($m['experiment'] ?? 0);
                $r = (int)($m['result'] ?? 0);
                $v = (int)($m['viva'] ?? 0);

                if ($is_abs) {
                    $total = 0;
                    $words = "AB";
                    $p = $e = $r = $v = 0;
                } else {
                    $total = $p + $e + $r + $v;
                    $words = numberToWords($total);
                }

                $ins = $conn->prepare("INSERT INTO marks
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
                    is_finalized=1");
                $ins->bind_param("sssiiiiiisii",
                    $roll_no, $course_id, $section, $dept_id, $AY,
                    $p, $v, $r, $e, $total, $words, $is_abs
                );
                $ins->execute();
                $ins->close();
            }

            $already_finalized = 1;
            $just_finalized = true;
            $finalized_display_time = date("j F Y, g:i A");
            $_SESSION['finalized_display_time'] = $finalized_display_time;
            $success_messages[] = "✅ Marks submitted and finalized successfully for Section: " . htmlspecialchars($section) . " on " . $finalized_display_time . ".";
        }
    }
}

/* ========= FETCH STUDENTS ========= */
$students = [];
$marks_map = [];
if (!$already_finalized) {
    $s1 = $conn->prepare("SELECT roll_no, name, section, branch FROM student WHERE section=? AND branch=? ORDER BY roll_no");
    $s1->bind_param("si", $section, $dept_id);
    $s1->execute();
    $res = $s1->get_result();
    $students = $res->fetch_all(MYSQLI_ASSOC);
    $s1->close();

    $s2 = $conn->prepare("SELECT * FROM marks WHERE course_id=? AND section=? AND AY=? AND dept=?");
    $s2->bind_param("sssi", $course_id, $section, $AY, $dept_id);
    $s2->execute();
    $res = $s2->get_result();
    while ($r = $res->fetch_assoc()) $marks_map[$r['roll_no']] = $r;
    $s2->close();
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
html,body{height:100%;margin:0;}
body{display:flex;flex-direction:column;}
.header-fixed{position:fixed;top:0;left:0;right:0;z-index:1030;}
.footer-fixed{position:fixed;bottom:0;left:0;right:0;z-index:1030;}
.content-wrap{position:relative;display:flex;flex:1 1 auto;margin-top:112px;margin-bottom:56px;overflow:hidden;}
.sidebar{height:100%;overflow-y:auto;}
.content-scroll{height:100%;overflow:auto;}
table th,td{text-align:center;vertical-align:middle!important;font-size:14px;}
.alert{font-size:15px;}
.btn-sm{padding:6px 14px;font-size:14px;}
.is-invalid{border-color:#dc3545!important;background-color:#ffeaea!important;}
tr.absent-row td:not(:first-child){opacity:0.7;}
thead th{position:sticky;top:0;background:#343a40;color:#fff;}
</style>
</head>
<body>
<header class="header-fixed bg-primary text-white text-center py-3">
  <h2 class="mb-1">SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
  <h4 class="mb-0">Enter Marks (Dept: <?php echo htmlspecialchars($dept_name); ?>)</h4>
</header>
<div class="content-wrap">
<div class="container-fluid d-flex w-100">
<div class="row w-100">
<nav class="col-md-3 col-lg-2 bg-secondary text-white p-3 sidebar"><?php include('faculty_menu.php'); ?></nav>
<div class="col-md-9 col-lg-10 content-scroll">
<div class="container my-3">
<h5 class="text-center">
Course: <?php echo htmlspecialchars($course_id); ?> |
Section: <?php echo htmlspecialchars($section); ?> |
AY: <?php echo htmlspecialchars($AY); ?> |
Dept: <?php echo htmlspecialchars($dept_name); ?>
</h5>

<?php foreach($success_messages as $msg): ?>
<div class="alert alert-success text-center"><?php echo $msg; ?></div>
<?php endforeach; ?>

<?php if(!empty($error_messages) && !$already_finalized): ?>
<div class="alert alert-danger"><?php foreach($error_messages as $em){echo "<div>".htmlspecialchars($em)."</div>";} ?></div>
<?php endif; ?>

<?php if(!$already_finalized): if(count($students)): ?>
<form method="post" id="marks-form">
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead class="thead-dark">
<tr>
<th>Roll No</th><th>Name</th><th>Branch</th><th>Absent?</th>
<th>Procedure (Max <?php echo MAX_PROCEDURE; ?>)</th>
<th>Experiment (Max <?php echo MAX_EXPERIMENT; ?>)</th>
<th>Result Analysis (Max <?php echo MAX_RESULT; ?>)</th>
<th>Viva Voce (Max <?php echo MAX_VIVA; ?>)</th>
<th>Total</th><th>Total (in Words)</th>
</tr></thead><tbody>
<?php foreach($students as $i=>$s):
$m=$marks_map[$s['roll_no']]??['procedure_marks'=>0,'viva_marks'=>0,'result_marks'=>0,'experiment_marks'=>0,'total_marks'=>0,'total_in_words'=>'Zero','is_absent'=>0];
$isA=!empty($m['is_absent']);$t=$isA?"AB":(int)$m['total_marks'];$w=$isA?"AB":htmlspecialchars($m['total_in_words']);?>
<tr class="<?php echo $isA?'absent-row':'';?>">
<td><?php echo htmlspecialchars($s['roll_no']);?></td>
<td><?php echo htmlspecialchars($s['name']);?></td>
<td><?php echo htmlspecialchars($dept_name);?></td>
<td><input type="checkbox" class="absent-checkbox" name="marks[<?php echo $s['roll_no'];?>][absent]" value="1" <?php echo $isA?'checked':'';?>></td>
<td><input type="number" class="form-control form-control-sm mark-input" data-max="<?php echo MAX_PROCEDURE;?>" name="marks[<?php echo $s['roll_no'];?>][procedure]" value="<?php echo (int)$m['procedure_marks'];?>"></td>
<td><input type="number" class="form-control form-control-sm mark-input" data-max="<?php echo MAX_EXPERIMENT;?>" name="marks[<?php echo $s['roll_no'];?>][experiment]" value="<?php echo (int)$m['experiment_marks'];?>"></td>
<td><input type="number" class="form-control form-control-sm mark-input" data-max="<?php echo MAX_RESULT;?>" name="marks[<?php echo $s['roll_no'];?>][result]" value="<?php echo (int)$m['result_marks'];?>"></td>
<td><input type="number" class="form-control form-control-sm mark-input" data-max="<?php echo MAX_VIVA;?>" name="marks[<?php echo $s['roll_no'];?>][viva]" value="<?php echo (int)$m['viva_marks'];?>"></td>
<td class="total-marks"><?php echo htmlspecialchars($t);?></td>
<td class="total-words"><?php echo htmlspecialchars($w);?></td>
</tr>
<?php endforeach;?>
</tbody></table></div>
<div class="text-center mb-3"><button id="open-confirm" type="button" class="btn btn-danger btn-sm">✅ Submit & Finalize</button></div>
</form>
<?php else: ?><p class="text-danger text-center">No students registered.</p><?php endif; endif;?>
</div></div></div></div></div>

<footer class="footer-fixed bg-primary text-white text-center py-2"><p class="mb-0">© 2024 - Developed by Dept. of IT</p></footer>

<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
<div class="modal-header bg-warning"><h5 class="modal-title">Finalize Marks?</h5>
<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
<div class="modal-body"><strong>⚠️ Are you sure you want to finalize marks?</strong><br>You won’t be able to edit them later.</div>
<div class="modal-footer"><button class="btn btn-secondary btn-sm" data-dismiss="modal">No</button><button id="confirm-submit" class="btn btn-danger btn-sm">Yes, finalize</button></div>
</div></div></div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function(){
  // ✅ Open confirm modal
  $('#open-confirm').on('click',function(){
    let invalid=false;
    $('.mark-input').each(function(){
      let max=parseInt($(this).data('max'));
      let val=$(this).val();
      if(val===''||isNaN(val))return;
      if(parseInt(val)<0||parseInt(val)>max){$(this).addClass('is-invalid');invalid=true;}
      else $(this).removeClass('is-invalid');
    });
    if(invalid){
      alert("⚠️ Some marks are out of allowed range. Please correct them before finalizing.");
      return;
    }
    $('#confirmModal').modal('show');
  });

  // ✅ Submit after confirm
  $('#confirm-submit').on('click',function(){
    $('#confirmModal').modal('hide');
    $('#marks-form').submit();
  });

  // ✅ Disable inputs if absent checked
  $('.absent-checkbox').on('change',function(){
    let row=$(this).closest('tr');
    if(this.checked){row.addClass('absent-row');row.find('.mark-input').val('').prop('disabled',true);}
    else{row.removeClass('absent-row');row.find('.mark-input').prop('disabled',false);}
  });
});
</script>
</body>
</html>


<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function(){

  /* 🔹 Helper: Convert number (0–999) to words */
  function numberToWords(num){
    const ones=["","One","Two","Three","Four","Five","Six","Seven","Eight","Nine","Ten",
      "Eleven","Twelve","Thirteen","Fourteen","Fifteen","Sixteen","Seventeen","Eighteen","Nineteen"];
    const tens=["","","Twenty","Thirty","Forty","Fifty","Sixty","Seventy","Eighty","Ninety"];
    num=parseInt(num);
    if(isNaN(num)||num<0)return "";
    if(num===0)return "Zero";
    if(num<20)return ones[num];
    if(num<100)return tens[Math.floor(num/10)]+(num%10?" "+ones[num%10]:"");
    if(num<1000)return ones[Math.floor(num/100)]+" Hundred"+(num%100?" and "+numberToWords(num%100):"");
    return num.toString();
  }

  /* 🔹 Real-time validation + total calculation */
  $('.mark-input').on('input', function(){
    const row=$(this).closest('tr');
    if(row.find('.absent-checkbox').prop('checked'))return; // skip absent
    const inputs=row.find('.mark-input');
    let total=0,valid=true;
    inputs.each(function(){
      const max=parseInt($(this).data('max'));
      const val=$(this).val();
      if(val===''||isNaN(val))return;
      const num=parseInt(val);
      if(num<0||num>max){
        $(this).addClass('is-invalid');
        valid=false;
      }else{
        $(this).removeClass('is-invalid');
        total+=num;
      }
    });
    if(valid){
      row.find('.total-marks').text(total);
      row.find('.total-words').text(numberToWords(total));
    }else{
      row.find('.total-marks').text('—');
      row.find('.total-words').text('');
    }
  });

  /* 🔹 Absent checkbox toggle */
  $('.absent-checkbox').on('change', function(){
    const row=$(this).closest('tr');
    if(this.checked){
      row.addClass('absent-row');
      row.find('.mark-input').val('').prop('disabled',true).removeClass('is-invalid');
      row.find('.total-marks').text('AB');
      row.find('.total-words').text('AB');
    }else{
      row.removeClass('absent-row');
      row.find('.mark-input').prop('disabled',false);
      row.find('.total-marks').text('0');
      row.find('.total-words').text('Zero');
    }
  });

  /* 🔹 Submit: Confirm modal */
  $('#open-confirm').on('click',function(){
    let invalid=false;
    $('.mark-input').each(function(){
      const max=parseInt($(this).data('max'));
      const val=$(this).val();
      if(val===''||isNaN(val))return;
      const num=parseInt(val);
      if(num<0||num>max){$(this).addClass('is-invalid');invalid=true;}
      else $(this).removeClass('is-invalid');
    });
    if(invalid){
      alert("⚠️ Some marks are out of allowed range. Please correct them before finalizing.");
      return;
    }
    $('#confirmModal').modal('show');
  });

  /* 🔹 Confirm finalize */
  $('#confirm-submit').on('click',function(){
    $('#confirmModal').modal('hide');
    $('#marks-form').submit();
  });

});
</script>

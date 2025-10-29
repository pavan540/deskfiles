<?php
/*******************************************************
 * generate_remuneration_section.php
 * Generates section-level remuneration (Examiner1, Examiner2, Staff, Consolidated)
 * Production-ready single-file version (keeps original behavior, stores staff IDs)
 *******************************************************/
declare(strict_types=1);
session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/fpdf/fpdf.php';

/* ---------- small helpers ---------- */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_login(): string {
    if (!isset($_SESSION['faculty_id'])) {
        header("Location: login.html");
        exit();
    }
    return (string)$_SESSION['faculty_id'];
}

function csrf_token_ensure(): void {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_get(): string { csrf_token_ensure(); return (string)$_SESSION['csrf_token']; }
function csrf_require_post(): void {
    $t = (string)($_POST['csrf_token'] ?? '');
    if (!$t || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $t)) {
        http_response_code(419);
        exit('Invalid CSRF token');
    }
}

/* ---------- get department name ---------- */
function get_department_name(mysqli $conn, int $dept_id): string {
    $st = $conn->prepare("SELECT dept_name FROM departments WHERE dept_id=? LIMIT 1");
    if (!$st) return (string)$dept_id;
    $st->bind_param('i', $dept_id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    return $r['dept_name'] ?? (string)$dept_id;
}

/* ---------- authorization ---------- */
function require_course_authorized(mysqli $conn, string $faculty_id, string $course_id, string $section, string $dept, string $AY): array {
    $dept_int = (int)$dept;
    $sql = "SELECT * FROM fvc WHERE faculty_id=? AND course_id=? AND section=? AND dept=? AND AY=? LIMIT 1";
    $st = $conn->prepare($sql);
    if (!$st) { http_response_code(500); exit('DB prepare error'); }
    $st->bind_param('sssis', $faculty_id, $course_id, $section, $dept_int, $AY);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) { http_response_code(403); exit('Permission denied: you are not mapped to this section.'); }
    return $row;
}

/* ---------- fetch faculty ---------- */
function get_faculty_by_id(mysqli $conn, string $fid): array {
    $st = $conn->prepare("SELECT faculty_id, name, designation, department, account_number, ifsc_code, bank_branch_name FROM faculty WHERE faculty_id=? LIMIT 1");
    if (!$st) { return ['faculty_id'=>$fid,'name'=>'','designation'=>'','department'=>'','account_number'=>'','ifsc_code'=>'','bank_branch_name'=>'']; }
    $st->bind_param('s', $fid);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    return $r ?: ['faculty_id'=>$fid,'name'=>'','designation'=>'','department'=>'','account_number'=>'','ifsc_code'=>'','bank_branch_name'=>''];
}

/* ---------- total candidates ---------- */
function get_total_candidates_section(mysqli $conn, string $course_id, string $section, string $dept, string $AY): int {
    $dept_int = (int)$dept;
    $sql = "SELECT COUNT(*) AS c FROM marks WHERE course_id=? AND section=? AND dept=? AND AY=? AND is_finalized=1 AND is_absent=0";
    $st = $conn->prepare($sql);
    if (!$st) return 0;
    $st->bind_param('ssis', $course_id, $section, $dept_int, $AY);
    $st->execute();
    $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    if ($c > 0) return $c;

    $st = $conn->prepare("SELECT COUNT(*) AS c FROM svc WHERE course_id=? AND section=? AND dept=? AND AY=?");
    if (!$st) return 0;
    $st->bind_param('ssis', $course_id, $section, $dept_int, $AY);
    $st->execute();
    $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    return $c;
}

/* ---------- number to words (Indian) ---------- */
function number_to_words_indian(int $num): string {
    $ones = ["", "One","Two","Three","Four","Five","Six","Seven","Eight","Nine","Ten","Eleven",
        "Twelve","Thirteen","Fourteen","Fifteen","Sixteen","Seventeen","Eighteen","Nineteen"];
    $tens = ["", "", "Twenty","Thirty","Forty","Fifty","Sixty","Seventy","Eighty","Ninety"];
    if ($num==0) return "Zero";
    $get2 = function($n) use($ones,$tens){
        if ($n<20) return $ones[$n];
        $t=$tens[intval($n/10)];
        $o=$ones[$n%10];
        return trim($t . ($o?" ".$o:""));
    };
    $get3 = function($n) use($ones,$get2){
        $h = intdiv($n,100);
        $r = $n%100;
        $str = "";
        if ($h>0) $str .= $ones[$h]." Hundred";
        if ($r>0) $str .= ($str?" ":"").$get2($r);
        return $str;
    };
    $words = "";
    $crores = intdiv($num, 10000000); $num %= 10000000;
    $lakhs  = intdiv($num, 100000);   $num %= 100000;
    $thou   = intdiv($num, 1000);     $num %= 1000;
    $hund   = $num;
    if ($crores) $words .= number_to_words_indian($crores)." Crore";
    if ($lakhs)  $words .= ($words?" ":"").number_to_words_indian($lakhs)." Lakh";
    if ($thou)   $words .= ($words?" ":"").number_to_words_indian($thou)." Thousand";
    if ($hund)   $words .= ($words?" ":"").$get3($hund);
    return $words;
}

/* ---------- MAIN ---------- */
$logged_faculty = require_login();

/* ---------- POST (Generate PDF) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    csrf_require_post();
    $faculty = require_login();

    $course_id    = trim((string)($_POST['course_id'] ?? ''));
    $section      = trim((string)($_POST['section'] ?? ''));
    $dept         = trim((string)($_POST['dept'] ?? ''));
    $AY           = trim((string)($_POST['AY'] ?? ''));
    $examiner1_id = trim((string)($_POST['examiner1_id'] ?? ''));
    $examiner2_id = trim((string)($_POST['examiner2_id'] ?? ''));

    // IDs from hidden fields
    $tech_id = trim((string)($_POST['tech_id'] ?? ''));
    $deo_id  = trim((string)($_POST['deo_id'] ?? ''));
    $peon_id = trim((string)($_POST['peon_id'] ?? ''));

    if ($course_id==='' || $section==='' || $dept==='' || $AY==='') {
        exit('Missing required fields.');
    }
    if ($tech_id==='' || $deo_id==='' || $peon_id==='') {
        exit('Please select valid staff from the suggestion list.');
    }

    $dept_int = (int)$dept;
    require_course_authorized($conn, $faculty, $course_id, $section, $dept, $AY);

    // Ensure marks finalized
    $st = $conn->prepare("SELECT 1 FROM marks WHERE course_id=? AND section=? AND dept=? AND AY=? AND is_finalized=1 LIMIT 1");
    $st->bind_param('ssis', $course_id, $section, $dept_int, $AY);
    $st->execute();
    if ($st->get_result()->num_rows === 0) {
        $st->close();
        exit('Marks not finalized for this section.');
    }
    $st->close();

    $total_candidates = get_total_candidates_section($conn, $course_id, $section, $dept, $AY);

    // Insert / Update remuneration using *_id columns
    $chk = $conn->prepare("SELECT id FROM remuneration WHERE course_id=? AND section=? AND dept=? AND AY=? LIMIT 1");
    $chk->bind_param('ssis', $course_id, $section, $dept_int, $AY);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($exists) {
        $upd = $conn->prepare("UPDATE remuneration
            SET faculty_id=?, examiner1_id=?, examiner2_id=?, tech_id=?, deo_id=?, peon_id=?, total_candidates=?, updated_at=NOW()
            WHERE course_id=? AND section=? AND dept=? AND AY=?");
        if (!$upd) { http_response_code(500); exit('DB prepare error (update).'); }
        $upd->bind_param('ssssssissis',
            $faculty, $examiner1_id, $examiner2_id,
            $tech_id, $deo_id, $peon_id,
            $total_candidates, $course_id, $section, $dept_int, $AY);
        $upd->execute();
        $upd->close();
    } else {
        $ins = $conn->prepare("INSERT INTO remuneration
            (course_id, section, dept, AY, faculty_id, examiner1_id, examiner2_id, tech_id, deo_id, peon_id, total_candidates, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$ins) { http_response_code(500); exit('DB prepare error (insert).'); }
        $ins->bind_param('ssisssssssi',
            $course_id, $section, $dept_int, $AY,
            $faculty, $examiner1_id, $examiner2_id,
            $tech_id, $deo_id, $peon_id, $total_candidates);
        $ins->execute();
        $ins->close();
    }

    // Fetch course and names for PDF
    $st = $conn->prepare("SELECT name FROM courses WHERE course_id=? LIMIT 1");
    $st->bind_param('s', $course_id);
    $st->execute();
    $course = $st->get_result()->fetch_assoc() ?: ['name'=>''];
    $st->close();

    $exam1 = get_faculty_by_id($conn, $examiner1_id);
    $exam2 = get_faculty_by_id($conn, $examiner2_id);
    $tech  = get_faculty_by_id($conn, $tech_id);
    $deo   = get_faculty_by_id($conn, $deo_id);
    $peon  = get_faculty_by_id($conn, $peon_id);

    $tech_name = $tech['name'] ?: $tech_id;
    $deo_name  = $deo['name']  ?: $deo_id;
    $peon_name = $peon['name'] ?: $peon_id;

    // Rates
    $RATE_EXAM = 20.00; $MIN_EXAM = 200.00;
    $RATE_TECH = 6.00;  $MIN_TECH = 50.00;
    $RATE_DEO  = 4.00;  $MIN_DEO  = 30.00;
    $RATE_PEON = 2.50;  $MIN_PEON = 10.00;

    $amt_exam1 = max($total_candidates * $RATE_EXAM, $MIN_EXAM);
    $amt_exam2 = max($total_candidates * $RATE_EXAM, $MIN_EXAM);
    $amt_tech  = max($total_candidates * $RATE_TECH, $MIN_TECH);
    $amt_deo   = max($total_candidates * $RATE_DEO, $MIN_DEO);
    $amt_peon  = max($total_candidates * $RATE_PEON, $MIN_PEON);
    $staff_total = $amt_tech + $amt_deo + $amt_peon;

    // PDF
    $pdf = new FPDF('P','mm','A4');
    $pdf->SetMargins(10,12,10);
    $pdf->AddPage();

    // Header
    if (file_exists(__DIR__ . '/logo.png')) {
        $pdf->Image(__DIR__ . '/logo.png', 15, 12, 22);
    }
    $pdf->SetXY(15, 12);
    $pdf->SetFont('Times', 'B', 14);
    $pdf->Cell(0, 7, 'SIDDHARTHA ACADEMY OF HIGHER EDUCATION', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(0, 5, 'An Institution Deemed to be University', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 8.5);
    $pdf->Cell(0, 5, '(Under Section 3 of UGC Act, 1956)', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->Cell(0, 5, 'Kanuru, Vijayawada - 520007, A.P.  www.vrsiddhartha.ac.in', 0, 1, 'C');
    $pdf->Ln(10);
$pdf->SetXY(-72, 17);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->Cell(0, 4, '91 866 2582333', 0, 2, 'R');
        $pdf->Cell(0, 4, '866 2582334', 0, 2, 'R');
        $pdf->Cell(0, 4, '866 2584930', 0, 1, 'R');
$pdf->Ln(10);
    // Title
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'CONSOLIDATED REMUNERATION BILL (EXAMINERS + STAFF)',0,1,'C');
    $pdf->Ln(4);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(45,6,'Course',0,0);       $pdf->Cell(0,6,': '.$course['name'].' ('.$course_id.')',0,1);
    $pdf->Cell(45,6,'Section',0,0);      $pdf->Cell(0,6,': '.$section,0,1);
    $dept_name = get_department_name($conn, $dept_int);
    $pdf->Cell(45,6,'Department',0,0);   $pdf->Cell(0,6,': '.$dept_name,0,1);
    $pdf->Cell(45,6,'Academic Year',0,0);$pdf->Cell(0,6,': '.$AY,0,1);
    $pdf->Cell(45,6,'Total Candidates Present',0,0); $pdf->Cell(0,6,': '.$total_candidates,0,1);

    $pdf->Ln(4);
    $pdf->SetFont('Arial','B',10);
    $w = [12, 62, 45, 48, 23];
    $heads = ['Sl.No','Name','Designation','No. of Candidates x Rate','Amount (Rs.)'];
    foreach ($heads as $i => $h) $pdf->Cell($w[$i],8,$h,1,0,'C');
    $pdf->Ln();

    $addRow = function($pdfRef, $w, $slno, $name, $designation, $cand_str, $amount) {
        $pdfRef->SetFont('Arial','',9.5);
        $pdfRef->Cell($w[0],8,$slno,1,0,'C');
        $pdfRef->Cell($w[1],8,$name,1,0);
        $pdfRef->Cell($w[2],8,$designation,1,0);
        $pdfRef->Cell($w[3],8,$cand_str,1,0,'C');
        $pdfRef->Cell($w[4],8,number_format($amount,2),1,1,'R');
    };

    $sl = 1;
    $addRow($pdf, $w, $sl++, $exam1['name'] ?: $examiner1_id, $exam1['designation'] ?: 'Examiner', "{$total_candidates} x ".number_format($RATE_EXAM,2), $amt_exam1);
    $addRow($pdf, $w, $sl++, $exam2['name'] ?: $examiner2_id, $exam2['designation'] ?: 'Examiner', "{$total_candidates} x ".number_format($RATE_EXAM,2), $amt_exam2);
    $addRow($pdf, $w, $sl++, $tech_name, 'Lab Technician', "{$total_candidates} x ".number_format($RATE_TECH,2), $amt_tech);
    $addRow($pdf, $w, $sl++, $deo_name, 'Clerk / DEO', "{$total_candidates} x ".number_format($RATE_DEO,2), $amt_deo);
    $addRow($pdf, $w, $sl++, $peon_name, 'Attender / Peon', "{$total_candidates} x ".number_format($RATE_PEON,2), $amt_peon);

    $consolidated_total = $amt_exam1 + $amt_exam2 + $amt_tech + $amt_deo + $amt_peon;
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(array_sum(array_slice($w,0,4)),8,'Grand Total:',1,0,'R');
    $pdf->Cell($w[4],8,'Rs. '.number_format($consolidated_total,2),1,1,'R');

    $pdf->Ln(5);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6,'Amount in words: '.number_to_words_indian((int)round($consolidated_total)).' Rupees Only',0,'L');

    $pdf->Ln(10);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(60,6,'Internal Examiner',0,0);
    $pdf->Cell(70,6,'Head Of The Department',0,0,'C');
    $pdf->Cell(0,6,'Signature of the Dean',0,1,'R');
    $pdf->Ln(8);
    $pdf->Cell(60,6,'Checked by:',0,0);
    $pdf->Cell(70,6,'Verified by:',0,0,'C');
    $pdf->Cell(0,6,'Controller of Examinations',0,1,'R');

    $filename = "Remuneration_{$course_id}_Sec{$section}_Dept{$dept}_{$AY}_Consolidated.pdf";
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.$filename.'"');
    $pdf->Output('I', $filename);
    exit();
}

/* ---------- GET (Render Form) ---------- */
$course_id = trim((string)($_GET['course_id'] ?? ''));
$section   = trim((string)($_GET['section'] ?? ''));
$dept      = trim((string)($_GET['dept'] ?? ''));
$AY        = trim((string)($_GET['AY'] ?? ''));

if ($course_id==='' || $section==='' || $dept==='' || $AY==='') {
    http_response_code(400);
    exit('Missing parameters.');
}

$fvc = require_course_authorized($conn, $logged_faculty, $course_id, $section, $dept, $AY);
$dept_int = (int)$dept;

$st = $conn->prepare("SELECT 1 FROM marks WHERE course_id=? AND section=? AND dept=? AND AY=? AND is_finalized=1 LIMIT 1");
$st->bind_param('ssis', $course_id, $section, $dept_int, $AY);
$st->execute();
$ok = $st->get_result()->num_rows > 0;
$st->close();
if (!$ok) exit("<h3>Marks not finalized yet. Remuneration can only be generated after finalization.</h3>");

$st = $conn->prepare("SELECT name, type FROM courses WHERE course_id=? LIMIT 1");
$st->bind_param('s', $course_id);
$st->execute();
$course = $st->get_result()->fetch_assoc() ?: ['name'=>'','type'=>''];
$st->close();

$examiner1_id = (string)($fvc['faculty_id'] ?? '');
$examiner2_id = (string)($fvc['ext_faculty_id'] ?? $examiner1_id);
$exam1 = get_faculty_by_id($conn, $examiner1_id);
$exam2 = get_faculty_by_id($conn, $examiner2_id);

$total_candidates = get_total_candidates_section($conn, $course_id, $section, $dept, $AY);
$csrf = csrf_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate Remuneration</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
body{background-color:#f7f9fc;}
.sidebar a{color:white;text-decoration:none;}
.sidebar a:hover{text-decoration:underline;}
.ui-autocomplete { max-height:200px; overflow-y:auto; overflow-x:hidden; z-index:1051 !important; font-size:0.95rem; }
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>(Deemed to be University)</h3>
    <h2>Generate Remuneration (Section-wise)</h2>
</header>

<div class="container-fluid d-flex flex-grow-1">
    <div class="row w-100 flex-grow-1">
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3 sidebar">
            <?php include('faculty_menu.php'); ?>
        </nav>

        <div class="col-md-9 col-lg-10 text-center" id="content-area">
            <div class="container mt-4">
                <h4 class="mb-3 text-dark">Remuneration Entry for <?= e($course['name']) ?> (Sec <?= e($section) ?>)</h4>
                <p><strong>Dept:</strong> <?= e($dept) ?> &nbsp; | &nbsp; <strong>AY:</strong> <?= e($AY) ?></p>
                <p><strong>Total Candidates:</strong> <?= e((string)$total_candidates) ?></p>
                <hr>

                <form method="post" target="_blank" autocomplete="off" novalidate class="text-left w-75 mx-auto">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="course_id" value="<?= e($course_id) ?>">
                    <input type="hidden" name="section" value="<?= e($section) ?>">
                    <input type="hidden" name="dept" value="<?= e($dept) ?>">
                    <input type="hidden" name="AY" value="<?= e($AY) ?>">
                    <input type="hidden" name="examiner1_id" value="<?= e($examiner1_id) ?>">
                    <input type="hidden" name="examiner2_id" value="<?= e($examiner2_id) ?>">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Examiner 1</label>
                            <input type="text" class="form-control" value="<?= e($exam1['name']).' — '.e($exam1['designation']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Examiner 2</label>
                            <input type="text" class="form-control" value="<?= e($exam2['name']).' — '.e($exam2['designation']) ?>" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                       <div class="form-group col-md-4 position-relative">
    <label>Skilled Assistant / Lab Technician</label>
    <input type="text" id="tech_name" class="form-control" required placeholder="Start typing name...">
    <input type="hidden" id="tech_id" name="tech_id">
</div>

<div class="form-group col-md-4 position-relative">
    <label>DEO / Clerk</label>
    <input type="text" id="deo_name" class="form-control" required placeholder="Start typing name...">
    <input type="hidden" id="deo_id" name="deo_id">
</div>

<div class="form-group col-md-4 position-relative">
    <label>Attender / Peon</label>
    <input type="text" id="peon_name" class="form-control" required placeholder="Start typing name...">
    <input type="hidden" id="peon_id" name="peon_id">
</div>

                    </div>

                    <div class="alert alert-info small">Examiner details are auto-fetched. Choose staff manually.</div>

                    <div class="text-center">
                        <button type="submit" name="generate_pdf" class="btn btn-primary">Generate Remuneration (Sec)</button>
                        <a href="select_section_remuneration.php" class="btn btn-light">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© <?= date('Y') ?> - Developed by Dept. of IT</p>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- jQuery UI (for autocomplete) -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script>
$(function() {
    function initFacultyAutocomplete(nameInputId, idHiddenId) {
        const $nameInput = $("#" + nameInputId);
        const $idHidden = $("#" + idHiddenId);

        $nameInput.autocomplete({
            minLength: 2,
            delay: 300,
            source: function(request, response) {
                $.ajax({
                    url: "faculty_search.php",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) {
                        response(data);
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            select: function(event, ui) {
                $nameInput.val(ui.item.value);
                $idHidden.val(ui.item.id || "");  // store faculty_id
                return false;
            }
        }).autocomplete("instance")._renderItem = function(ul, item) {
            return $("<li>")
                .append("<div>" + item.label + "</div>")
                .appendTo(ul);
        };
    }

    initFacultyAutocomplete("tech_name", "tech_id");
    initFacultyAutocomplete("deo_name", "deo_id");
    initFacultyAutocomplete("peon_name", "peon_id");
});
</script>



</body>
</html>

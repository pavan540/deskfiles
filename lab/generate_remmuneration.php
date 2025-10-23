<?php
/*******************************************************
 * generate_remuneration_course.php
 * Purpose: Generate ONE consolidated remuneration bill
 *          for the entire course (all sections of a dept)
 * Auth: Logged-in faculty must be mapped to this course
 *       (any section) in fvc for the given dept & AY.
 *******************************************************/
declare(strict_types=1);
session_start();
require_once __DIR__ . '/connection.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ----------------------- Session & CSRF ----------------------- */
function require_login(): string {
    if (!isset($_SESSION['faculty_id'])) {
        header("Location: login.html"); exit();
    }
    return (string)$_SESSION['faculty_id'];
}
function csrf_token_ensure(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
function csrf_get(): string { csrf_token_ensure(); return (string)$_SESSION['csrf_token']; }
function csrf_require_post(): void {
    $t = (string)($_POST['csrf_token'] ?? '');
    if (!$t || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $t)) {
        http_response_code(419);
        echo "<h3 style='font-family:system-ui'>Invalid or missing CSRF token.</h3>";
        exit();
    }
}

/* ----------------------- Authorization ----------------------- */
function require_course_authorized(mysqli $conn, string $faculty_id, string $course_id, string $dept, string $AY): void {
    $sql = "SELECT 1 FROM fvc WHERE faculty_id=? AND course_id=? AND dept=? AND AY=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { http_response_code(500); exit('DB error'); }
    $stmt->bind_param('ssss', $faculty_id, $course_id, $dept, $AY);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if (!$ok){
        http_response_code(403);
        echo "<h3 style='font-family:system-ui'>Permission denied: you are not mapped to this course for {$dept}, AY {$AY}.</h3>";
        exit();
    }
}

/* ----------------------- Data Access ----------------------- */
function get_course(mysqli $conn, string $course_id): array {
    $stmt = $conn->prepare("SELECT course_id, name, type FROM courses WHERE course_id=?");
    $stmt->bind_param('s', $course_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ?: ['course_id'=>$course_id, 'name'=>'', 'type'=>''];
}
function get_programme_from_fvc(mysqli $conn, string $course_id, string $dept, string $AY): array {
    $sql = "SELECT p.programme_name, p.level
            FROM fvc f
            LEFT JOIN programmes p ON p.programme_id=f.programme_id
            WHERE f.course_id=? AND f.dept=? AND f.AY=?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $course_id, $dept, $AY);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ?: ['programme_name'=>'B.Tech','level'=>'UG'];
}
function get_exam_date_range_all_sections(mysqli $conn, string $course_id, string $dept, string $AY): array {
    $sql = "SELECT MIN(exam_date) AS d1, MAX(exam_date) AS d2
            FROM fvc_schedule
            WHERE course_id=? AND dept=? AND AY=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $course_id, $dept, $AY);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r || !$r['d1']) return ['', ''];
    return [$r['d1'], $r['d2']];
}
function get_total_candidates_present(mysqli $conn, string $course_id, string $dept, string $AY): int {
    // Prefer finalized marks, not absent, across ALL sections
    $sql = "SELECT COUNT(*) AS c FROM marks
            WHERE course_id=? AND dept=? AND AY=? AND is_finalized=1 AND is_absent=0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $course_id, $dept, $AY);
    $stmt->execute();
    $c = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    if ($c>0) return $c;

    // Fallback: roster (svc) for this course/dept/AY (all sections)
    $sql = "SELECT COUNT(*) AS c FROM svc WHERE course_id=? AND dept=? AND AY=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $course_id, $dept, $AY);
    $stmt->execute();
    $c = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    return $c;
}
function get_faculty_by_id(mysqli $conn, string $fid): array {
    $stmt = $conn->prepare("SELECT name, designation, department FROM faculty WHERE faculty_id=?");
    $stmt->bind_param('s', $fid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ?: ['name'=>'','designation'=>'','department'=>''];
}

/* ----------------------- Utils ----------------------- */
function number_to_words_indian(int $num): string {
    $ones = ["", "One","Two","Three","Four","Five","Six","Seven","Eight","Nine","Ten","Eleven",
        "Twelve","Thirteen","Fourteen","Fifteen","Sixteen","Seventeen","Eighteen","Nineteen"];
    $tens = ["", "", "Twenty","Thirty","Forty","Fifty","Sixty","Seventy","Eighty","Ninety"];
    if ($num==0) return "Zero";
    $words = "";

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

    $crores = intdiv($num, 10000000); $num%=10000000;
    $lakhs  = intdiv($num, 100000);   $num%=100000;
    $thou   = intdiv($num, 1000);     $num%=1000;
    $hund   = $num;

    if ($crores) $words .= number_to_words_indian($crores)." Crore";
    if ($lakhs)  $words .= ($words?" ":"").number_to_words_indian($lakhs)." Lakh";
    if ($thou)   $words .= ($words?" ":"").number_to_words_indian($thou)." Thousand";
    if ($hund)   $words .= ($words?" ":"").$get3($hund);
    return $words;
}

/* ----------------------- POST: Generate PDF ----------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['__make_pdf'])) {
    $faculty_id = require_login();
    csrf_require_post();

    $course_id = trim((string)($_POST['course_id'] ?? ''));
    $AY        = trim((string)($_POST['AY'] ?? ''));
    $dept      = trim((string)($_POST['dept'] ?? ''));

    $tech_name = trim((string)($_POST['tech_name'] ?? ''));
    $deo_name  = trim((string)($_POST['deo_name'] ?? ''));
    $peon_name = trim((string)($_POST['peon_name'] ?? ''));

    if ($course_id==='' || $AY==='' || $dept==='' || $tech_name==='' || $deo_name==='' || $peon_name==='') {
        echo "<script>alert('All fields are required.');history.back();</script>";
        exit();
    }

    require_course_authorized($conn, $faculty_id, $course_id, $dept, $AY);

    $course = get_course($conn, $course_id);
    $prog   = get_programme_from_fvc($conn, $course_id, $dept, $AY);
    [$d1,$d2] = get_exam_date_range_all_sections($conn, $course_id, $dept, $AY);
    $total_candidates = get_total_candidates_present($conn, $course_id, $dept, $AY);

    // Rates (B.Tech lab per candidate) — keep configurable later via DB if needed.
    $RATE_TECH = 6.00;  $MIN_TECH = 50.00;
    $RATE_DEO  = 4.00;  $MIN_DEO  = 30.00;
    $RATE_PEON = 2.50;  $MIN_PEON = 10.00;

    $amt_tech = max($total_candidates * $RATE_TECH, $MIN_TECH);
    $amt_deo  = max($total_candidates * $RATE_DEO,  $MIN_DEO);
    $amt_peon = max($total_candidates * $RATE_PEON, $MIN_PEON);
    $grand    = $amt_tech + $amt_deo + $amt_peon;

    $nxr = function(float $rate) use($total_candidates){
        return "{$total_candidates} x Rs. ".number_format($rate,2,'.','');
    };

    $dateText = '';
    if ($d1 && $d2) {
        $dateText = ($d1===$d2) ? date('d-m-Y', strtotime($d1))
                                : date('d-m-Y', strtotime($d1))." to ".date('d-m-Y', strtotime($d2));
    }
    $grand_words = number_to_words_indian((int)round($grand))." Rupees Only";

    require_once __DIR__ . '/fpdf/fpdf.php';

    class PDF extends FPDF {
        function headerBlock(){
            // If logo exists, place it (optional)
            if (file_exists(__DIR__.'/logo.png')) {
                $this->Image(__DIR__.'/logo.png', 12, 10, 18);
            }
            $this->SetFont('Arial','B',18);
            $this->Cell(0,8,'SIDDHARTHA',0,1,'C');
            $this->SetFont('Arial','B',11);
            $this->Cell(0,6,'ACADEMY OF HIGHER EDUCATION',0,1,'C');
            $this->SetFont('Arial','',8);
            $this->Cell(0,5,'(An Institution Deemed to be University)',0,1,'C');
            $this->SetFont('Arial','',8);
            $this->Cell(0,5,'Kanuru, Vijayawada - 520 007, A.P. (UGC Act, 1956)',0,1,'C');
            $this->Ln(2);
        }
        function tableHeader(){
            $this->SetFont('Arial','B',10);
            $w = [12, 70, 35, 38, 25, 20]; // SL, Name, Desig, No. x Rs, Total, Sign
            $heads = ['Sl.No','Name','Designation','No. of candidates X Rs','Total Amount','Signature'];
            foreach($heads as $i=>$h){ $this->Cell($w[$i],8,$h,1,0,'C'); }
            $this->Ln();
            return $w;
        }
        function ratesBlock(){
            $this->Ln(4);
            $this->SetFont('Arial','B',10);
            $this->Cell(0,6,'RATES OF REMUNERATION FOR B.TECH LAB (Course-wise total)',0,1);
            $this->SetFont('Arial','',10);
            $this->Cell(140,6,'1. LAB Technician/Jr. Programmer/Programmer',0,0);
            $this->Cell(0,6,': Per Candidate Rs. 6/- (Minimum Rs.50/-)',0,1);
            $this->Cell(140,6,'2. Clerk / Store Keeper',0,0);
            $this->Cell(0,6,': Per Candidate Rs. 4/- (Minimum Rs.30/-)',0,1);
            $this->Cell(140,6,'3. Attender / Peon',0,0);
            $this->Cell(0,6,': Per Candidate Rs. 2.50/- (Minimum Rs.10/-)',0,1);

            $this->Ln(2);
            $this->SetFont('Arial','',10);
            $this->Cell(95,6,'M.TECH (Technician)',0,0);
            $this->Cell(0,6,': Minimum Rs. 200/-   |   Per Candidate: Rs. 8/-',0,1);
        }
        function footerSignatures(){
            $this->Ln(8);
            $this->SetFont('Arial','',10);
            $this->Cell(60,6,'Internal Examiner',0,0,'L');
            $this->Cell(70,6,'Head Of The Department',0,0,'C');
            $this->Cell(0,6,'Signature of the Dean',0,1,'R');

            $this->Ln(10);
            $this->SetFont('Arial','',10);
            $this->Cell(60,6,'Checked by:',0,0,'L');
            $this->Cell(70,6,'Verified by:',0,0,'C');
            $this->Cell(0,6,'Controller of Examinations',0,1,'R');
        }
    }

    $pdf = new PDF('P','mm','A4');
    $pdf->SetMargins(10,10,10);
    $pdf->AddPage();

    // Header & title
    $pdf->headerBlock();
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,'COURSE-LEVEL REMUNERATION BILL (Skilled Assistant, DEO & Peon)',0,1,'C');
    $pdf->Ln(2);

    // Top info
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(45,6,'Programme',0,0);
    $pdf->Cell(0,6,': '.$prog['programme_name'].' — AY '.$AY,0,1);

    $pdf->Cell(45,6,'Department',0,0);
    $pdf->Cell(0,6,': '.$dept,0,1);

    $pdf->Cell(45,6,'Course',0,0);
    $pdf->Cell(0,6,': '.$course['name'].' ('.$course['course_id'].')',0,1);

    $pdf->Cell(45,6,'Total Candidates Present',0,0);
    $pdf->Cell(0,6,': '.$total_candidates,0,1);

    $pdf->Cell(45,6,'Exam Dates',0,0);
    $pdf->Cell(0,6,': '.($dateText ?: '________________'),0,1);

    $pdf->Ln(1);
    $w = $pdf->tableHeader();
    $pdf->SetFont('Arial','',10);

    $row = function($sl,$name,$desig,$nxr,$total) use($pdf,$w){
        $pdf->Cell($w[0],8,$sl,1,0,'C');
        $pdf->Cell($w[1],8,$name,1,0);
        $pdf->Cell($w[2],8,$desig,1,0);
        $pdf->Cell($w[3],8,$nxr,1,0,'C');
        $pdf->Cell($w[4],8,'Rs. '.number_format($total,2),1,0,'R');
        $pdf->Cell($w[5],8,'',1,1);
    };

    // 3 consolidated rows (course-level)
    $row(1, $tech_name, 'Lab Technician',   $nxr($RATE_TECH), $amt_tech);
    $row(2, $deo_name,  'Clerk / DEO',      $nxr($RATE_DEO),  $amt_deo);
    $row(3, $peon_name, 'Attender / Peon',  $nxr($RATE_PEON), $amt_peon);

    // Grand total
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell($w[0]+$w[1]+$w[2]+$w[3],8,'Grand Total:',1,0,'R');
    $pdf->Cell($w[4],8,'Rs. '.number_format($grand,2),1,0,'R');
    $pdf->Cell($w[5],8,'',1,1);

    $pdf->Ln(3);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,6,'Rs. in words: '.$grand_words,0,1);
    $pdf->Cell(0,6,'Date of Submission: _______________________',0,1);

    $pdf->footerSignatures();
    $pdf->Ln(4);
    $pdf->ratesBlock();

    $filename = 'Remuneration_COURSE_'.$course['course_id'].'_'.$dept.'_'.$AY.'.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.$filename.'"');
    $pdf->Output('I', $filename);
    exit();
}

/* ----------------------- GET: Show Form ----------------------- */
$faculty_id = require_login();

$course_id = trim((string)($_GET['course_id'] ?? ''));
$AY        = trim((string)($_GET['AY'] ?? ''));
$dept      = trim((string)($_GET['dept'] ?? ''));

if ($course_id==='' || $AY==='' || $dept==='') {
    http_response_code(400);
    echo "<h3 style='font-family:system-ui'>Missing parameters. Required: course_id, AY, dept.</h3>";
    exit();
}

require_course_authorized($conn, $faculty_id, $course_id, $dept, $AY);

$course = get_course($conn, $course_id);
$prog   = get_programme_from_fvc($conn, $course_id, $dept, $AY);
[$d1,$d2] = get_exam_date_range_all_sections($conn, $course_id, $dept, $AY);
$total_candidates = get_total_candidates_present($conn, $course_id, $dept, $AY);
$csrf = csrf_get();

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Generate Course Remuneration</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
body{background:#f8f9fa}
.card{border-radius:12px}
.suggest-box{position:relative}
.suggest-list{position:absolute; z-index:1000; background:#fff; width:100%; border:1px solid #ced4da; border-top:none; max-height:220px; overflow:auto}
.suggest-item{padding:8px 10px; cursor:pointer}
.suggest-item:hover{background:#f1f3f5}
.small-note{color:#6c757d; font-size:0.9rem}
</style>
</head>
<body class="py-4">
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-3">Course-level Remuneration (Skilled Assistant, DEO & Peon)</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Programme:</strong> <?= e($prog['programme_name']); ?> (<?= e($prog['level']); ?>)</p>
                    <p><strong>Course:</strong> <?= e($course['name']); ?> (<?= e($course['course_id']); ?>)</p>
                    <p><strong>Department:</strong> <?= e($dept); ?> &nbsp; | &nbsp; <strong>AY:</strong> <?= e($AY); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Total Candidates Present (All Sections):</strong> <?= e((string)$total_candidates); ?></p>
                    <p><strong>Exam Dates (All Sections):</strong>
                        <?php
                        if ($d1 && $d2) {
                            echo e(($d1===$d2) ? date('d-m-Y',strtotime($d1)) : date('d-m-Y',strtotime($d1)).' to '.date('d-m-Y',strtotime($d2)));
                        } else { echo '—'; }
                        ?>
                    </p>
                    <p class="text-muted small mb-0">Only faculty mapped to this course (any section) can generate the bill.</p>
                </div>
            </div>
            <hr>

            <form method="post" target="_blank" autocomplete="off" novalidate>
                <input type="hidden" name="__make_pdf" value="1">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="course_id" value="<?= e($course_id) ?>">
                <input type="hidden" name="AY" value="<?= e($AY) ?>">
                <input type="hidden" name="dept" value="<?= e($dept) ?>">

                <div class="form-row">
                    <div class="form-group col-md-4 suggest-box">
                        <label>Skilled Assistant / Lab Technician</label>
                        <input type="text" id="tech_name" name="tech_name" class="form-control" placeholder="Type name…" required maxlength="80">
                        <div class="suggest-list d-none" id="sug-tech"></div>
                        <div class="small-note">Start typing to search faculty by name/department.</div>
                    </div>
                    <div class="form-group col-md-4 suggest-box">
                        <label>DEO / Clerk</label>
                        <input type="text" id="deo_name" name="deo_name" class="form-control" placeholder="Type name…" required maxlength="80">
                        <div class="suggest-list d-none" id="sug-deo"></div>
                        <div class="small-note">Suggestions from faculty table.</div>
                    </div>
                    <div class="form-group col-md-4 suggest-box">
                        <label>Attender / Peon</label>
                        <input type="text" id="peon_name" name="peon_name" class="form-control" placeholder="Type name…" required maxlength="80">
                        <div class="suggest-list d-none" id="sug-peon"></div>
                        <div class="small-note">Pick the correct person.</div>
                    </div>
                </div>

                <div class="alert alert-info small">
                    <strong>Rates (B.Tech Lab):</strong>
                    Technician: ₹6 (Min ₹50), DEO: ₹4 (Min ₹30), Peon: ₹2.5 (Min ₹10).<br>
                    <strong>M.Tech Technician:</strong> Min ₹200, ₹8 per candidate. (Shown on the PDF footer.)
                </div>

                <button type="submit" class="btn btn-primary">Generate Course PDF in New Tab</button>
                <button type="button" class="btn btn-light" onclick="history.back()">Back</button>
            </form>
        </div>
    </div>
</div>

<script>
// Simple debounce
function debounce(fn, delay){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn.apply(null,a), delay); }; }
function bindSuggest(inputId, listId){
    const input = document.getElementById(inputId);
    const list  = document.getElementById(listId);
    let last = '';
    const hide = ()=>{ list.classList.add('d-none'); list.innerHTML=''; };
    const show = ()=>{ list.classList.remove('d-none'); };

    async function lookup(term){
        try{
            const r = await fetch('faculty_search.php?term='+encodeURIComponent(term));
            if (!r.ok) { hide(); return; }
            const data = await r.json();
            if (!Array.isArray(data) || data.length===0){ hide(); return; }
            let html = '';
            for (const it of data){
                const label = (it.label||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                const value = (it.value||'').replace(/"/g,'&quot;');
                html += `<div class="suggest-item" data-val="${value}">${label}</div>`;
            }
            list.innerHTML = html; show();
        }catch{ hide(); }
    }

    input.addEventListener('input', debounce(function(){
        const term = input.value.trim();
        if (!term || term===last){ if(!term) hide(); return; }
        last = term;
        if (term.length < 2){ hide(); return; }
        lookup(term);
    }, 250));

    list.addEventListener('click', (ev)=>{
        const el = ev.target.closest('.suggest-item'); if(!el) return;
        input.value = el.getAttribute('data-val') || ''; hide();
    });
    input.addEventListener('blur', ()=> setTimeout(hide, 200));
}
bindSuggest('tech_name','sug-tech');
bindSuggest('deo_name','sug-deo');
bindSuggest('peon_name','sug-peon');
</script>
</body>
</html>

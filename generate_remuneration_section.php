<?php
/*******************************************************
 * generate_remuneration_section.php
 * Generate section-level remuneration (Examiner1, Examiner2, Staff, Consolidated)
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
/* ---------- get department name by id ---------- */
function get_department_name(mysqli $conn, int $dept_id): string {
    $st = $conn->prepare("SELECT dept_name FROM departments WHERE dept_id=? LIMIT 1");
    if (!$st) return (string)$dept_id;
    $st->bind_param('i', $dept_id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    return $r['dept_name'] ?? (string)$dept_id;
}

/* ---------- authorization (fvc) ---------- */
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

/* ---------- total candidates for this section ---------- */
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

    // fallback to roster (svc)
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

/* ---------- Main ---------- */
$logged_faculty = require_login();

/* ---------- GET: display form ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $course_id = trim((string)($_GET['course_id'] ?? ''));
    $section   = trim((string)($_GET['section'] ?? ''));
    $dept      = trim((string)($_GET['dept'] ?? ''));
    $AY        = trim((string)($_GET['AY'] ?? ''));

    if ($course_id==='' || $section==='' || $dept==='' || $AY==='') {
        http_response_code(400);
        exit('Missing parameters: course_id, section, dept, AY are required.');
    }

    // authorization
    $fvc = require_course_authorized($conn, $logged_faculty, $course_id, $section, $dept, $AY);
    $dept_int = (int)$dept;

    // ensure marks finalized for this section
    $st = $conn->prepare("SELECT 1 FROM marks WHERE course_id=? AND section=? AND dept=? AND AY=? AND is_finalized=1 LIMIT 1");
    $st->bind_param('ssis', $course_id, $section, $dept_int, $AY);
    $st->execute();
    $ok = $st->get_result()->num_rows > 0;
    $st->close();
    if (!$ok) {
        exit("<h3>Marks for this section are not finalized yet. Remuneration can only be generated after finalization.</h3>");
    }

    // course info
    $st = $conn->prepare("SELECT name, type FROM courses WHERE course_id=? LIMIT 1");
    $st->bind_param('s', $course_id);
    $st->execute();
    $course = $st->get_result()->fetch_assoc() ?: ['name'=>'','type'=>''];
    $st->close();

    // examiners from fvc
    $examiner1_id = (string)($fvc['faculty_id'] ?? '');
    $examiner2_id = (string)($fvc['ext_faculty_id'] ?? $examiner1_id);
    $exam1 = get_faculty_by_id($conn, $examiner1_id);
    $exam2 = get_faculty_by_id($conn, $examiner2_id);

    $total_candidates = get_total_candidates_section($conn, $course_id, $section, $dept, $AY);
    $csrf = csrf_get();

    // render form (Bootstrap + autocomplete)
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Generate Remuneration - <?= e($course['name']) ?> (Sec <?= e($section) ?>)</title>
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
            body{background:#f8f9fa}
            .header{background:#007bff;color:#fff;padding:14px;text-align:center}
            .footer{background:#007bff;color:#fff;padding:8px;text-align:center}
            .suggest-list{border:1px solid #ced4da;background:#fff;border-radius:4px}
            .list-group-item{cursor:pointer;padding:8px 12px}
            .list-group-item:hover{background:#f1f3f5}
        </style>
    </head>
    <body class="py-3">
    <div class="container">
        <div class="header">
            <h4>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h4>
            <div>Generate Remuneration (3-Page PDF)</div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <?php
                // If you have a faculty_menu.php include, keep it; otherwise comment/remove.
                if (file_exists(__DIR__.'/faculty_menu.php')) include('faculty_menu.php');
                ?>
            </div>

            <div class="col-md-9">
                <div class="card p-3 shadow-sm">
                    <h5>Course: <?= e($course['name']) ?> (<?= e($course_id) ?>) — Section <?= e($section) ?></h5>
                    <p><strong>Dept:</strong> <?= e($dept) ?> &nbsp; | &nbsp; <strong>AY:</strong> <?= e($AY) ?></p>
                    <p><strong>Total Candidates (finalized & present):</strong> <?= e((string)$total_candidates) ?></p>
                    <hr>

                    <form method="post" target="_blank" autocomplete="off" novalidate>
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
                                <!--<small class="form-text text-muted">Auto fetched from allocation (fvc.faculty_id)</small> -->
                            </div>

                            <div class="form-group col-md-6">
                                <label>Examiner 2</label>
                                <input type="text" class="form-control" value="<?= e($exam2['name']).' — '.e($exam2['designation']) ?>" readonly>
                              <!--  <small class="form-text text-muted">Auto fetched from allocation (fvc.ext_faculty_id)</small> -->
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4 position-relative">
                                <label>Skilled Assistant / Lab Technician</label>
                                <input type="text" id="tech_name" name="tech_name" class="form-control" required maxlength="100" placeholder="Start typing name...">
                            </div>
                            <div class="form-group col-md-4 position-relative">
                                <label>DEO / Clerk</label>
                                <input type="text" id="deo_name" name="deo_name" class="form-control" required maxlength="100" placeholder="Start typing name...">
                            </div>
                            <div class="form-group col-md-4 position-relative">
                                <label>Attender / Peon</label>
                                <input type="text" id="peon_name" name="peon_name" class="form-control" required maxlength="100" placeholder="Start typing name...">
                            </div>
                        </div>

                        <div class="alert alert-info small">
                            Examiner details are auto-fetched. Use the fields above to choose staff (suggestions from faculty table).
                        </div>

                        <button type="submit" name="generate_pdf" class="btn btn-primary">Generate Remmuneration(sec)</button>
                        <a href="select_section_remuneration.php" class="btn btn-light">Back</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="footer mt-3">
            <p>© <?= date('Y') ?> - Dept. of IT</p>
        </div>
    </div>

    <!-- Autocomplete script (uses faculty_search.php) -->
    <script>
    function debounce(fn, delay) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
    }
    function bindSuggest(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const container = document.createElement('div');
        container.className = 'suggest-list list-group position-absolute w-100 shadow-sm';
        container.style.zIndex = '2000';
        container.style.maxHeight = '220px';
        container.style.overflowY = 'auto';
        container.style.display = 'none';
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(container);

        async function lookup(term) {
            if (term.length < 2) { container.innerHTML = ''; container.style.display = 'none'; return; }
            try {
                const res = await fetch('faculty_search.php?term=' + encodeURIComponent(term), { credentials: 'include' });
                if (!res.ok) return;
                const data = await res.json();
                if (!Array.isArray(data) || data.length === 0) { container.innerHTML = ''; container.style.display = 'none'; return; }
                container.innerHTML = '';
                for (const it of data) {
                    const el = document.createElement('div');
                    el.className = 'list-group-item list-group-item-action';
                    el.textContent = it.label;
                    el.onclick = () => {
                        input.value = it.value;
                        container.innerHTML = '';
                        container.style.display = 'none';
                    };
                    container.appendChild(el);
                }
                container.style.display = 'block';
            } catch (err) {
                console.error(err);
            }
        }

        input.addEventListener('input', debounce(() => lookup(input.value.trim()), 250));
        input.addEventListener('blur', () => setTimeout(() => { container.innerHTML = ''; container.style.display = 'none'; }, 200));
    }

    bindSuggest('tech_name');
    bindSuggest('deo_name');
    bindSuggest('peon_name');
    </script>
    </body>
    </html>
    <?php
    exit();
}

/* ---------- POST: insert record & generate PDF ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn; // ensure we have DB connection in this scope
    csrf_require_post();
    $faculty = require_login();

    $course_id    = trim((string)($_POST['course_id'] ?? ''));
    $section      = trim((string)($_POST['section'] ?? ''));
    $dept         = trim((string)($_POST['dept'] ?? ''));
    $AY           = trim((string)($_POST['AY'] ?? ''));
    $examiner1_id = trim((string)($_POST['examiner1_id'] ?? ''));
    $examiner2_id = trim((string)($_POST['examiner2_id'] ?? ''));
    $tech_name    = trim((string)($_POST['tech_name'] ?? ''));
    $deo_name     = trim((string)($_POST['deo_name'] ?? ''));
    $peon_name    = trim((string)($_POST['peon_name'] ?? ''));

    if ($course_id==='' || $section==='' || $dept==='' || $AY==='' || $tech_name==='' || $deo_name==='' || $peon_name==='') {
        exit('Missing required fields.');
    }
    $dept_int = (int)$dept;

    // Authorization
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

    // total candidates
    $total_candidates = get_total_candidates_section($conn, $course_id, $section, $dept, $AY);

    // Insert into remuneration table if not exists
   // --- Upsert remuneration record ---
$chk = $conn->prepare("SELECT id FROM remuneration WHERE course_id=? AND section=? AND dept=? AND AY=? LIMIT 1");
if (!$chk) { http_response_code(500); exit('DB prepare error'); }
$chk->bind_param('ssis', $course_id, $section, $dept_int, $AY);
$chk->execute();
$exists = $chk->get_result()->fetch_assoc();
$chk->close();

if ($exists) {
    // Update existing record
    $upd = $conn->prepare("UPDATE remuneration 
                           SET faculty_id=?, examiner1_id=?, examiner2_id=?, tech_name=?, deo_name=?, peon_name=?, total_candidates=?, updated_at=NOW()
                           WHERE course_id=? AND section=? AND dept=? AND AY=?");
    if (!$upd) { http_response_code(500); exit('DB prepare error'); }
    $upd->bind_param('ssssssissis',
        $faculty,
        $examiner1_id,
        $examiner2_id,
        $tech_name,
        $deo_name,
        $peon_name,
        $total_candidates,
        $course_id,
        $section,
        $dept_int,
        $AY
    );
    $upd->execute();
    $upd->close();
} else {
    // Insert new record
    $ins = $conn->prepare("INSERT INTO remuneration(course_id, section, dept, AY, faculty_id, examiner1_id, examiner2_id, tech_name, deo_name, peon_name, total_candidates, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$ins) { http_response_code(500); exit('DB prepare error'); }
    $ins->bind_param('ssisssssssi',
        $course_id,
        $section,
        $dept_int,
        $AY,
        $faculty,
        $examiner1_id,
        $examiner2_id,
        $tech_name,
        $deo_name,
        $peon_name,
        $total_candidates
    );
    $ins->execute();
    $ins->close();
}


    // fetch course & faculty details for PDF
    $st = $conn->prepare("SELECT name FROM courses WHERE course_id=? LIMIT 1");
    $st->bind_param('s', $course_id);
    $st->execute();
    $course = $st->get_result()->fetch_assoc() ?: ['name'=>''];
    $st->close();

    $exam1 = get_faculty_by_id($conn, $examiner1_id);
    $exam2 = get_faculty_by_id($conn, $examiner2_id);

    // Rates (adjust as required)
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

    // PDF generation (FPDF)
   // ---------- Simplified PDF: Only CONSOLIDATED REMUNERATION BILL ----------

// PDF generation (FPDF)
$pdf = new FPDF('P','mm','A4');
$pdf->SetMargins(10,12,10);

// Header helper
// ---------- Enhanced Header ----------
$addHeader = function($pdf) {
    // Logo (left side)
    if (file_exists(__DIR__ . '/logo.png')) {
        $pdf->Image(__DIR__ . '/logo.png', 15, 12, 22);
    }

    // University Name & Details (center)
    $pdf->SetXY(15, 12);
    $pdf->SetFont('Times', 'B', 14);
    $pdf->Cell(0, 7, 'SIDDHARTHA ACADEMY OF HIGHER EDUCATION', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(0, 5, 'An Institution Deemed to be University', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 8.5);
    $pdf->Cell(0, 5, '(Under Section 3 of UGC Act, 1956)', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->Cell(0, 5, 'Kanuru, Vijayawada - 520007, A.P.  www.vrsiddhartha.ac.in', 0, 1, 'C');

    // Contact Info (right side)
    $pdf->SetXY(-65, 12);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(50, 5, '91 866 2582333', 0, 2, 'R');
    $pdf->Cell(50, 5, '866 2582334', 0, 2, 'R');
    $pdf->Cell(50, 5, '866 2584930', 0, 1, 'R');

    // Horizontal line under header
    $pdf->Ln(3);
    
    $pdf->Ln(6);
};


// Start PDF
$pdf->AddPage();
$addHeader($pdf);

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

// ====== Retrieve exam dates ======
$dates_list = [];
$st = $conn->prepare("
    SELECT DISTINCT exam_date, session 
    FROM fvc_schedule 
    WHERE course_id=? AND section=? AND dept=? AND AY=? 
    ORDER BY exam_date ASC
");
if ($st) {
    $st->bind_param('ssis', $course_id, $section, $dept_int, $AY);
    $st->execute();
    $res = $st->get_result();
    while ($r = $res->fetch_assoc()) {
        $date_str = '';
        if (!empty($r['exam_date'])) {
            $date_str = date('d-m-Y', strtotime($r['exam_date']));
        }
        if (!empty($r['session'])) {
            $date_str .= ' (' . strtoupper(trim($r['session'])) . ')';
        }
        if ($date_str) $dates_list[] = $date_str;
    }
    $st->close();
}

if ($dates_list) {
    $pdf->Cell(45, 6, 'Exam Dates', 0, 0);
    $pdf->MultiCell(0, 6, ': ' . implode(', ', $dates_list), 0, 'L');
}
$pdf->Ln(4);

// ===== Table Layout =====
$pdf->SetFont('Arial','B',10);
// Adjusted column widths (slightly narrower and balanced)
$w = [12, 62, 45, 48, 23];
$heads = ['Sl.No','Name','Designation','No. of Candidates x Rate','Amount (Rs.)'];

foreach ($heads as $i => $h) $pdf->Cell($w[$i],8,$h,1,0,'C');
$pdf->Ln();

// Row helper
$addRow = function($pdfRef, $w, $slno, $name, $designation, $cand_str, $amount) {
    $pdfRef->SetFont('Arial','',9.5);
    $pdfRef->Cell($w[0],8,$slno,1,0,'C');
    $pdfRef->Cell($w[1],8,$name,1,0);
    $pdfRef->Cell($w[2],8,$designation,1,0);
    $pdfRef->Cell($w[3],8,$cand_str,1,0,'C');
    $pdfRef->Cell($w[4],8,number_format($amount,2),1,1,'R');
};

// Add rows
$sl = 1;
$addRow($pdf, $w, $sl++, $exam1['name'] ?: $examiner1_id, $exam1['designation'] ?: 'Examiner', "{$total_candidates} x ".number_format($RATE_EXAM,2), $amt_exam1);
$addRow($pdf, $w, $sl++, $exam2['name'] ?: $examiner2_id, $exam2['designation'] ?: 'Examiner', "{$total_candidates} x ".number_format($RATE_EXAM,2), $amt_exam2);
$addRow($pdf, $w, $sl++, $tech_name, 'Lab Technician', "{$total_candidates} x ".number_format($RATE_TECH,2), $amt_tech);
$addRow($pdf, $w, $sl++, $deo_name, 'Clerk / DEO', "{$total_candidates} x ".number_format($RATE_DEO,2), $amt_deo);
$addRow($pdf, $w, $sl++, $peon_name, 'Attender / Peon', "{$total_candidates} x ".number_format($RATE_PEON,2), $amt_peon);

// Grand Total
$consolidated_total = $amt_exam1 + $amt_exam2 + $amt_tech + $amt_deo + $amt_peon;
$pdf->SetFont('Arial','B',10);
$pdf->Cell(array_sum(array_slice($w,0,4)),8,'Grand Total:',1,0,'R');
$pdf->Cell($w[4],8,'Rs. '.number_format($consolidated_total,2),1,1,'R');

$pdf->Ln(5);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,'Amount in words: '.number_to_words_indian((int)round($consolidated_total)).' Rupees Only',0,'L');

// Footer
$pdf->Ln(10);
$pdf->SetFont('Arial','',10);
$pdf->Cell(60,6,'Internal Examiner',0,0);
$pdf->Cell(70,6,'Head Of The Department',0,0,'C');
$pdf->Cell(0,6,'Signature of the Dean',0,1,'R');
$pdf->Ln(8);
$pdf->Cell(60,6,'Checked by:',0,0);
$pdf->Cell(70,6,'Verified by:',0,0,'C');
$pdf->Cell(0,6,'Controller of Examinations',0,1,'R');

// Output final PDF
$filename = "Remuneration_{$course_id}_Sec{$section}_Dept{$dept}_{$AY}_Consolidated.pdf";
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="'.$filename.'"');
$pdf->Output('I', $filename);
exit();

}

http_response_code(405);
echo "Method not allowed.";
?>

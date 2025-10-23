<?php
session_start();
require_once 'connection.php';
require_once 'fpdf/fpdf.php';

/* ============================== Auth ============================== */
if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}
$logged_in_faculty_id = $_SESSION['faculty_id'];

/* ============================== Inputs ============================== */
$course_id = $_SESSION['course_id'] ?? ($_GET['course_id'] ?? '');
$dept      = $_SESSION['dept']      ?? ($_GET['dept']      ?? '');
$AY        = $_SESSION['AY']        ?? ($_GET['AY']        ?? '');

if ($course_id === '' || $dept === '' || $AY === '') {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Invalid request! Missing course/department/AY.</h3>");
}

/* ============================== Helper: Group roll numbers ============================== */
function groupRollNumbersIntoRanges(array $rolls): string {
    if (empty($rolls)) return '';
    $buckets = [];
    foreach ($rolls as $r) {
        $r = trim((string)$r);
        if (preg_match('/^(.*?)(\d+)$/', $r, $m)) {
            $prefix = $m[1];
            $numStr = $m[2];
            $numInt = intval($numStr);
            $buckets[$prefix][] = [$numStr, $numInt, $r];
        } else {
            $prefix = $r . '#raw';
            $buckets[$prefix][] = ['', 0, $r];
        }
    }
    $parts = [];
    foreach ($buckets as $prefix => $items) {
        usort($items, function($a, $b) {
            if ($a[1] === $b[1]) return strcmp($a[2], $b[2]);
            return $a[1] <=> $b[1];
        });
        $i = 0; $n = count($items);
        while ($i < $n) {
            $start = $items[$i];
            $j = $i;
            while ($j + 1 < $n && $items[$j+1][1] === $items[$j][1] + 1 && $start[0] !== '' && $items[$j+1][0] !== '') {
                $j++;
            }
            if ($j === $i || $start[0] === '') {
                $parts[] = $items[$i][2];
            } else {
                $parts[] = $items[$i][2] . ' - ' . $items[$j][2];
            }
            $i = $j + 1;
        }
    }
    return implode(", ", $parts);
}

/* ============================== STEP 1: Metadata ============================== */
$sql_meta = "
    SELECT 
        fvc.sem, fvc.mon_year, fvc.faculty_id, fvc.ext_faculty_id, 
        p.programme_name, s.school_name, d.dept_name, c.name AS course_name
    FROM fvc 
    LEFT JOIN programmes p ON fvc.programme_id = p.programme_id
    LEFT JOIN departments d ON p.dept_id = d.dept_id
    LEFT JOIN schools s ON p.school_id = s.school_id
    LEFT JOIN courses c ON fvc.course_id = c.course_id
    WHERE fvc.course_id=? AND fvc.dept=? AND fvc.AY=? AND fvc.section='1'
    LIMIT 1";
$stmt = $conn->prepare($sql_meta);
$stmt->bind_param("sss", $course_id, $dept, $AY);
$stmt->execute();
$fvc_meta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fvc_meta) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>FVC metadata not found.</h3>");
}

$programme   = $fvc_meta['programme_name'] ?? 'N/A';
$school_name = $fvc_meta['school_name'] ?? 'N/A';
$dept_name   = $fvc_meta['dept_name'] ?? $dept;
$sem         = intval($fvc_meta['sem'] ?? 1);
$mon_year    = trim((string)($fvc_meta['mon_year'] ?? ''));
$internal_id = trim((string)($fvc_meta['faculty_id'] ?? ''));
$external_id = trim((string)($fvc_meta['ext_faculty_id'] ?? ''));
$course_name = $fvc_meta['course_name'] ?? 'Lab Course';

$year_map = [1=>"I",2=>"I",3=>"II",4=>"II",5=>"III",6=>"III",7=>"IV",8=>"IV"];
$yearRoman = $year_map[$sem] ?? "I";

/* ============================== STEP 2: Schedule Info ============================== */
$schedules = [];
$stmt = $conn->prepare("SELECT exam_date, session, remarks, start_roll_no, end_roll_no 
                        FROM fvc_schedule 
                        WHERE course_id=? AND dept=? AND AY=? 
                        ORDER BY exam_date ASC, session ASC");
$stmt->bind_param("sss", $course_id, $dept, $AY);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $schedules[] = [
        'date' => date("d-m-Y", strtotime($row['exam_date'])),
        'session' => $row['session'],
        'remarks' => $row['remarks'],
        'start' => $row['start_roll_no'],
        'end' => $row['end_roll_no']
    ];
}
$stmt->close();

/* ============================== STEP 3: Students ============================== */
$sql_students = "SELECT roll_no, is_absent 
                 FROM marks 
                 WHERE course_id=? AND dept=? AND AY=? AND is_finalized=1
                 ORDER BY roll_no ASC";
$stmt = $conn->prepare($sql_students);
$stmt->bind_param("sss", $course_id, $dept, $AY);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($rows)) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>No finalized student records found.</h3>");
}

$registered = count($rows);
$present_rolls = [];
$absent_rolls  = [];
foreach ($rows as $r) {
    if (intval($r['is_absent']) === 1) {
        $absent_rolls[] = $r['roll_no'];
    } else {
        $present_rolls[] = $r['roll_no'];
    }
}
$total_present = count($present_rolls);
$total_absent  = count($absent_rolls);

$present_ranges_str = groupRollNumbersIntoRanges($present_rolls);
sort($absent_rolls, SORT_NATURAL);
$absent_str = empty($absent_rolls) ? 'None' : implode(", ", $absent_rolls);

/* ============================== STEP 4: Faculty Info ============================== */
function get_faculty_info($conn, $faculty_id) {
    if (!$faculty_id) return ['name' => 'N/A', 'designation' => 'N/A'];
    $stmt = $conn->prepare("SELECT name, designation FROM faculty WHERE faculty_id=?");
    $stmt->bind_param("s", $faculty_id);
    $stmt->execute();
    $fac = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [
        'name' => $fac['name'] ?? 'N/A',
        'designation' => $fac['designation'] ?? 'N/A'
    ];
}

$internal_fac = get_faculty_info($conn, $internal_id);
$external_fac = get_faculty_info($conn, $external_id);

/* ============================== PDF Generation ============================== */
class PDF extends FPDF {
    public $course_id; public $programme; public $yearRoman; public $sem;
    public $mon_year; public $school_name; public $dept_name; public $course_name;
    public $schedules;

    function Header() {
    // ====== Logo on the left ======
    if (file_exists('logo.png')) {
        $this->Image('logo.png', 15, 12, 22);
    }

    // ====== University name (center) ======
    $this->SetXY(15, 12);
    $this->SetFont('Times', 'B', 14);
    $this->Cell(0, 7, 'SIDDHARTHA ACADEMY OF HIGHER EDUCATION', 0, 1, 'C');

    // Subtitles
    $this->SetFont('Arial', '', 9);
    $this->Cell(0, 5, 'An Institution Deemed to be University', 0, 1, 'C');
    $this->SetFont('Arial', 'I', 8.5);
    $this->Cell(0, 5, '(Under Section 3 of UGC Act, 1956)', 0, 1, 'C');
    $this->SetFont('Arial', '', 8.5);
    $this->Cell(0, 5, 'Kanuru, Vijayawada - 520007, AP. www.vrsiddhartha.ac.in', 0, 1, 'C');

    // ====== Phone numbers (right aligned) ======
    $this->SetXY(-65, 12);
    $this->SetFont('Arial', '', 9);
    $this->Cell(50, 5, '91 866 2582333', 0, 2, 'R');
    $this->Cell(50, 5, '866 2582334', 0, 2, 'R');
    $this->Cell(50, 5, '866 2584930', 0, 1, 'R');

    $this->Ln(6);

    // ====== Title ======
    $this->SetFont('Times', 'B', 13);
    $this->Cell(0, 7, 'ABSENT STATEMENT', 0, 1, 'C');
    $this->Ln(4);

    // ====== Examination details ======
    $this->SetFont('Arial', 'B', 10);
    $line = "Examination: {$this->programme}, Year {$this->yearRoman} Sem {$this->sem}";
    if (!empty($this->mon_year)) {
        $line .= "   |   Month & Year: {$this->mon_year}";
    }
    $this->Cell(0, 6, $line, 0, 1, 'L');

    $this->SetFont('Arial', '', 10);
    $this->Cell(0, 6, "School: {$this->school_name}", 0, 1, 'L');
    $this->Cell(0, 6, "Department: {$this->dept_name}", 0, 1, 'L');

    $this->Ln(4);

    // ====== Schedule Table Header ======
    $this->SetFont('Arial', 'B', 10);
    $this->Cell(90, 8, 'Sub. Code & Title of the Subject', 1, 0, 'C');
    $this->Cell(90, 8, 'Exam Dates, Time Slots, Batches & Roll Range', 1, 1, 'C');

    // ====== Build schedule text ======
    $schedule_text = "";
    foreach ($this->schedules as $s) {
        $schedule_text .= "{$s['date']} ({$s['session']}) - {$s['remarks']} [{$s['start']} - {$s['end']}]\n";
    }

    // ====== Calculate height based on number of schedule rows ======
    $lines = count($this->schedules);
    if ($lines == 0) $lines = 1;
    $lineHeight = 6;
    $totalHeight = $lines * $lineHeight;

    // ====== Left cell (subject) ======
    $this->SetFont('Arial', '', 9);
    $x = $this->GetX();
    $y = $this->GetY();
    $this->MultiCell(90, $totalHeight, "{$this->course_id} - {$this->course_name}", 1, 'C');

    // ====== Right cell (schedule info) ======
    $this->SetXY($x + 90, $y);
    $this->MultiCell(90, $lineHeight, trim($schedule_text), 1, 'L');

    $this->Ln(5);
}


    function Footer() {
        $this->SetY(-35);
        $this->SetFont('Arial', '', 9);
      
        $this->Ln(2);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, 'Generated on: ' . date('j F Y, g:i A'), 0, 0, 'L');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->course_id = $course_id;
$pdf->programme = $programme;
$pdf->yearRoman = $yearRoman;
$pdf->sem = $sem;
$pdf->mon_year = $mon_year;
$pdf->school_name = $school_name;
$pdf->dept_name = $dept_name;
$pdf->course_name = $course_name;
$pdf->schedules = $schedules;

$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

// Summary table
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(60, 8, 'Number of Candidates Registered (01)', 1, 0, 'C');
$pdf->Cell(60, 8, 'Number of Candidates Present (02)', 1, 0, 'C');
$pdf->Cell(60, 8, 'Number of Candidates Absent (03)', 1, 1, 'C');

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(60, 8, $registered, 1, 0, 'C');
$pdf->Cell(60, 8, $total_present, 1, 0, 'C');
$pdf->Cell(60, 8, $total_absent, 1, 1, 'C');

// Remarks
$pdf->Cell(180, 8, 'Remarks (if the totals do not agree with columns)', 1, 1, 'L');
$pdf->Ln(2);

// Present rolls
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 6, '01. Register Number of Candidates Present:', 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell(0, 6, $present_ranges_str !== '' ? $present_ranges_str : 'None', 1, 'L');
$pdf->Ln(3);

// Absent rolls
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 6, '02. Register Number of Candidates Absent:', 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell(0, 6, $absent_str, 1, 'L');
$pdf->Ln(6);

// Signatures
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, 'SIGNATURE OF THE EXAMINER 01: ________________________________', 0, 1, 'L');
$pdf->Cell(0, 6, "   Faculty ID: {$internal_id}   Name: {$internal_fac['name']}   Designation: {$internal_fac['designation']}", 0, 1, 'L');
$pdf->Ln(3);
$pdf->Cell(0, 6, 'SIGNATURE OF THE EXAMINER 02: ________________________________', 0, 1, 'L');
$pdf->Cell(0, 6, "   Faculty ID: {$external_id}   Name: {$external_fac['name']}   Designation: {$external_fac['designation']}", 0, 1, 'L');

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, '3. Total Number of Answer Books enclosed: ________', 0, 1, 'L');
$pdf->Cell(0, 6, '4. Total Number of Answer Books Returned: ________', 0, 1, 'L');
$pdf->Cell(0, 6, '5. Station: Kanuru, Vijayawada', 0, 1, 'L');
$pdf->Cell(0, 6, '6. Date and Hour: ____________________', 0, 1, 'L');
$pdf->Ln(10);
$pdf->Cell(0, 6, 'Signature of the Dean: ________________________________', 0, 1, 'R');
$pdf->Ln(15);
$pdf->Cell(0, 6, 'Checked by: ____________________           Verified by: ____________________           Controller of Examinations', 0, 1, 'C');
$filename = "Attendance_Statement_{$course_id}_{$dept}_{$AY}.pdf";
$pdf->Output('I', $filename);
exit;
?>

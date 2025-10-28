<?php
/********************************************
 * generate_signature_sheet.php
 * Generates a signature sheet (25 candidates per page)
 ********************************************/
session_start();
require_once 'connection.php';
require_once 'fpdf/fpdf.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];
$course_id  = $_GET['course_id'] ?? '';
$section    = $_GET['section'] ?? '';
$AY         = $_GET['AY'] ?? '';
$dept       = $_GET['dept'] ?? '';

if (empty($course_id) || empty($section) || empty($AY) || empty($dept)) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Invalid parameters provided!</h3>");
}

/* ---------- Fetch course, department, sem (and derive year) ---------- */
$stmt = $conn->prepare("
    SELECT c.name AS course_name, c.type AS course_type, d.dept_name, f.sem
    FROM courses c
    JOIN departments d ON d.dept_id = ?
    JOIN fvc f ON f.course_id = c.course_id
    WHERE c.course_id = ? AND f.dept = ? AND f.AY = ?
    LIMIT 1
");
$stmt->bind_param("isis", $dept, $course_id, $dept, $AY);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$course_name = $info['course_name'] ?? 'Unknown Course';
$course_type = $info['course_type'] ?? '';
$dept_name   = $info['dept_name'] ?? 'Unknown Department';
$sem         = $info['sem'] ?? '';
$year        = '';
if (!empty($sem) && is_numeric($sem)) {
    $yearNum = ceil(intval($sem) / 2);
    $year = match ($yearNum) {
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        default => (string)$yearNum
    };
}

/* ---------- Fetch student list ---------- */
$stmt = $conn->prepare("
    SELECT roll_no, name 
    FROM student 
    WHERE section=? AND branch=? 
    ORDER BY roll_no ASC
");
$stmt->bind_param("si", $section, $dept);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// ✅ Summary data
$total_students = count($students);
$absent_students = array_filter($students, fn($s) => isset($s['is_absent']) && $s['is_absent'] == 1);
$absent_count = count($absent_students);
$absent_rolls = implode(', ', array_column($absent_students, 'roll_no'));

if (empty($students)) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>No students found for Section {$section}.</h3>");
}

/* ---------- PDF CLASS ---------- */
class PDF extends FPDF {
    public $course_id;
    public $course_name;
    public $course_type;
    public $dept_name;
    public $section;
    public $AY;
    public $faculty_id;
    public $year;
    public $sem;

    function Header() {
        if (file_exists('logo.png')) {
            $this->Image('logo.png', 15, 11, 22);
        }
        $this->SetXY(43, 13);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(0, 6, 'SIDDHARTHA ACADEMY OF HIGHER EDUCATION', 0, 1, 'L');

        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4.5, 'An Institution Deemed to be University', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 8.5);
        $this->Cell(0, 4.5, '(Under Section 3 of UGC Act, 1956)', 0, 1, 'C');
        $this->SetFont('Arial', '', 8.5);
        $this->Cell(0, 4.5, 'Kanuru, Vijayawada - 520007, AP. www.vrsiddhartha.ac.in', 0, 1, 'C');

        $this->SetXY(-72, 17);
        $this->SetFont('Arial', '', 8.5);
        $this->Cell(0, 4, '91 866 2582333', 0, 2, 'R');
        $this->Cell(0, 4, '866 2582334', 0, 2, 'R');
        $this->Cell(0, 4, '866 2584930', 0, 1, 'R');

        $this->Ln(6);
        $this->SetFont('Times', 'B', 11);
        $this->Cell(0, 5, 'LAB ATTENDANCE SHEET', 0, 1, 'C');
        $this->Ln(2);

        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4.5, utf8_decode("Course: {$this->course_id} - {$this->course_name} ({$this->course_type}) - Regular "), 0, 1, 'C');
        $this->Cell(0, 4.5, "Dept: {$this->dept_name}   |   Year: {$this->year}   |   Sem: {$this->sem}   |   Section: {$this->section}   |   AY: {$this->AY}", 0, 1, 'C');

        $this->Ln(3);
        $this->SetDrawColor(120, 120, 120);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(10, 7, 'S.No', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Roll No', 1, 0, 'C', true);
        $this->Cell(80, 7, 'Name of the Student', 1, 0, 'C', true);
        $this->Cell(30, 7, 'Booklet No.', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Signature', 1, 1, 'C', true);
    }

    function Footer() {
        $this->SetY(-18);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(90, 90, 90);
        date_default_timezone_set('Asia/Kolkata');
        $this->Cell(0, 5, 'Generated on: '.date('j F Y, g:i A').' )', 0, 0, 'L');
        $this->Cell(0, 5, 'Page '.$this->PageNo().'/{nb}', 0, 1, 'R');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, "Faculty ID: ".$this->faculty_id, 0, 0, 'L');
    }

    function NbLines($w, $txt) {
        $cw = $this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb-1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) $i++; }
                else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }

    function GetPageBreakTriggerSafe() {
        return $this->PageBreakTrigger;
    }
}

/* ---------- PDF GENERATION ---------- */
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->SetFont('Arial', '', 9);

$pdf->course_id   = $course_id;
$pdf->course_name = $course_name;
$pdf->course_type = $course_type;
$pdf->dept_name   = $dept_name;
$pdf->section     = $section;
$pdf->AY          = $AY;
$pdf->faculty_id  = $faculty_id;
$pdf->year        = $year;
$pdf->sem         = $sem;

$rowsPerPage = 25;
$total = count($students);
$pages = ceil($total / $rowsPerPage);
$serial = 1;

for ($p = 0; $p < $pages; $p++) {
    $pageStudents = array_slice($students, $p * $rowsPerPage, $rowsPerPage);
    $pdf->AddPage();

    foreach ($pageStudents as $stu) {
        $roll = $stu['roll_no'];
        $name = utf8_decode($stu['name']);

        $snoW = 10;
        $rollW = 25;
        $nameW = 80;
        $bookletW = 30;
        $signW = 35;

        $lineHeight = 7.8;
        $nb = $pdf->NbLines($nameW, $name);
        $rowHeight = max($lineHeight, $nb * $lineHeight);

        if ($pdf->GetY() + $rowHeight > $pdf->GetPageBreakTriggerSafe()) {
            $pdf->AddPage();
        }

        $pdf->Cell($snoW, $rowHeight, $serial++, 1, 0, 'C');
        $pdf->Cell($rollW, $rowHeight, $roll, 1, 0, 'C');

        $xName = $pdf->GetX();
        $yName = $pdf->GetY();
        $pdf->MultiCell($nameW, $lineHeight, $name, 1, 'L');
        $pdf->SetXY($xName + $nameW, $yName);
        $pdf->Cell($bookletW, $rowHeight, '', 1, 0, 'C');
        $pdf->Cell($signW, $rowHeight, '', 1, 1, 'C');
    }
}

// ✅ Add final summary on last page
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, "Total No. of Students allotted in this section :  ____________________", 0, 1, 'L');
$pdf->Cell(0, 6, "No. of Students Absent :  ____________________", 0, 1, 'L');
$pdf->Ln(3);
$pdf->Cell(0, 6, "Roll Number(s) of Absent Students :  ____________________", 0, 1, 'L');
$pdf->Ln(3);
$pdf->Cell(0, 6, "Signature of the Examiner 1 :  ____________________", 0, 1, 'L');
$pdf->Ln(3);
$pdf->Cell(0, 6, "Name of the Examiner 1 :  ____________________", 0, 1, 'L');

$filename = "Attendance_Sheet_{$course_id}_Sec{$section}.pdf";
$pdf->Output('I', $filename);
exit;
?>

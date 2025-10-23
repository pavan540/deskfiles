<?php
session_start();
require_once 'connection.php';
require_once 'fpdf/fpdf.php';

// Redirect if not logged in
if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Get parameters
$course_id = $_GET['course_id'] ?? '';
$section   = $_GET['section'] ?? '';
$AY        = $_GET['AY'] ?? '';
$dept      = $_GET['dept'] ?? '';

if (empty($course_id) || empty($section) || empty($AY) || empty($dept)) {
    die("Invalid course selection!");
}

// Fetch internal and external faculty IDs from fvc table
$sql_fvc = "SELECT faculty_id, ext_faculty_id, mon_year FROM fvc 
            WHERE course_id = ? AND section = ? AND dept = ? AND AY = ?";
$stmt = $conn->prepare($sql_fvc);
$stmt->bind_param("ssss", $course_id, $section, $dept, $AY);
$stmt->execute();
$fvc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fvc) {
    die("No faculty verification record found for this course/section.");
}

$internal_id = $fvc['faculty_id'];
$external_id = $fvc['ext_faculty_id'];

// Fetch internal faculty details
$sql_fac = "SELECT name, designation FROM faculty WHERE faculty_id = ?";
$stmt = $conn->prepare($sql_fac);
$stmt->bind_param("s", $internal_id);
$stmt->execute();
$internal = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch external faculty details
$sql_ext = "SELECT name, designation FROM faculty WHERE faculty_id = ?";
$stmt = $conn->prepare($sql_ext);
$stmt->bind_param("s", $external_id);
$stmt->execute();
$external = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch course details
$sql_course = "SELECT name, type FROM courses WHERE course_id = ?";
$stmt = $conn->prepare($sql_course);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch finalized marks
$sql = "SELECT s.roll_no, s.name, m.total_marks, m.total_in_words
        FROM marks m
        INNER JOIN student s ON m.roll_no = s.roll_no
        WHERE m.course_id=? AND m.section=? AND m.AY=? AND m.dept=? AND m.is_finalized=1
        ORDER BY s.roll_no";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $course_id, $section, $AY, $dept);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// -----------------------------
// Custom PDF class
// -----------------------------
class PDF extends FPDF {
    public $internal;
    public $external;

    function Header() {
        global $course_id, $section, $dept, $AY, $course;

        $this->Image('logo.png', 15, 11, 22);
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

        //$this->Line(15, 42, 195, 42);
        $this->Ln(6);

        $this->SetFont('Times', 'B', 11);
        $this->Cell(0, 5, 'SUMMATIVE ASSESSMENT MARKS REPORT', 0, 1, 'C');
        $this->Ln(2);

        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4.5, utf8_decode("Course: {$course_id} - {$course['name']} ({$course['type']})"), 0, 1, 'C');
        $this->Cell(0, 4.5, "Section: {$section}   |   Dept: {$dept}   |   Academic Year: {$AY}", 0, 1, 'C');

        $this->Ln(3);
       $this->SetDrawColor(120, 120, 120);
       $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(3);

        $this->TableHeader();
    }

    function TableHeader() {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(10, 7, 'S.No', 1, 0, 'C', true);
        $this->Cell(22, 7, 'Roll No', 1, 0, 'C', true);
        $this->Cell(95, 7, 'Name of the Student', 1, 0, 'C', true);
        $this->Cell(18, 7, 'Total', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Total (in Words)', 1, 1, 'C', true);
    }

 function Footer() {
    $this->SetY(-38); // footer position from bottom
    $this->SetDrawColor(0, 0, 0);
    $this->SetFont('Arial', '', 8);

    $boxWidth = 85;
    $boxHeight = 22;
    $y = $this->GetY();
    $xLeft = 20;
    $xRight = 110;

    $lineHeight = 4; // 👈 Increased line spacing

    // ===== LEFT BOX (Internal Examiner) =====
    $this->Rect($xLeft, $y, $boxWidth, $boxHeight);
    $this->SetXY($xLeft + 5, $y + 3);

    $this->Cell($boxWidth - 10, $lineHeight, "Signature: ____________________", 0, 1, 'L');
    $this->SetX($xLeft + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Faculty ID: " . $this->internal['faculty_id']."(Examiner 2)", 0, 1, 'L');

    $this->SetFont('Arial', 'B', 8.5);
    $this->SetX($xLeft + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Name: " . $this->internal['name'], 0, 1, 'L');

    $this->SetFont('Arial', '', 8);
    $this->SetX($xLeft + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Designation: " . $this->internal['designation'], 0, 1, 'L');

    // ===== RIGHT BOX (External Examiner) =====
    $this->Rect($xRight, $y, $boxWidth, $boxHeight);
    $this->SetXY($xRight + 5, $y + 3);

    $this->Cell($boxWidth - 10, $lineHeight, "Signature: ____________________", 0, 1, 'L');
    $this->SetX($xRight + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Faculty ID: " . $this->external['faculty_id']."(Examiner 2)", 0, 1, 'L');

    $this->SetFont('Arial', 'B', 8.5);
    $this->SetX($xRight + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Name: " . $this->external['name'], 0, 1, 'L');

    $this->SetFont('Arial', '', 8);
    $this->SetX($xRight + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Designation: " . $this->external['designation'], 0, 1, 'L');

    // ===== PAGE NUMBER =====
    $this->SetY(-10);
    $this->SetFont('Arial', 'I', 7.5);
    $this->SetTextColor(90, 90, 90);
    $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
}


}

// -----------------------------
// PDF generation
// -----------------------------
$pdf = new PDF();
$pdf->internal = [
    'faculty_id' => $internal_id,
    'name' => ucwords(strtolower($internal['name'] ?? 'Internal Faculty')),
    'designation' => $internal['designation'] ?? 'N/A'
];
$pdf->external = [
    'faculty_id' => $external_id,
    'name' => ucwords(strtolower($external['name'] ?? 'External Faculty')),
    'designation' => $external['designation'] ?? 'N/A'
];

$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 45);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8.5);

$serial = 1;
$rowHeight = 7;
foreach ($students as $stu) {
    if ($pdf->GetY() + $rowHeight > 240) $pdf->AddPage();
    $pdf->Cell(10, $rowHeight, $serial++, 1, 0, 'C');
    $pdf->Cell(22, $rowHeight, $stu['roll_no'], 1, 0, 'C');
    $pdf->Cell(95, $rowHeight, utf8_decode($stu['name']), 1, 0, 'C');
    $pdf->Cell(18, $rowHeight, $stu['total_marks'], 1, 0, 'C');
    $pdf->Cell(40, $rowHeight, utf8_decode($stu['total_in_words']), 1, 1, 'C');
}

$filename = "LAB_Final_{$course_id}_{$section}_{$dept}_{$AY}.pdf";
$pdf->Output('I', $filename);
exit;
?>

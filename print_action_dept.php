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
$AY        = $_GET['AY'] ?? '';
$dept      = $_GET['dept'] ?? '';

if (empty($course_id) || empty($AY) || empty($dept)) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Invalid course selection!</h3>");
}

// -----------------------------------------------------------
// Helper: Convert a numeric value into capitalized words for each digit
// e.g. 574 -> "Five Seven Four"
// -----------------------------------------------------------
function numberToDigitWords($num) {
    $s = (string) $num;
    $digitsOnly = preg_replace('/[^0-9]/', '', $s);
    if ($digitsOnly === '') return '';
    $map = [
        '0' => 'Zero', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four',
        '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine'
    ];
    $chars = str_split($digitsOnly);
    $words = array_map(function($d) use ($map) { return $map[$d]; }, $chars);
    return implode(' ', $words);
}

// -----------------------------------------------------------
// STEP 1: Get all sections
// -----------------------------------------------------------
$sql_sections = "SELECT DISTINCT section FROM fvc 
                 WHERE course_id=? AND dept=? AND AY=?
                 ORDER BY section ASC";
$stmt = $conn->prepare($sql_sections);
$stmt->bind_param("sss", $course_id, $dept, $AY);
$stmt->execute();
$res = $stmt->get_result();
$sections = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($sections)) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>No sections found for this course and department.</h3>");
}

// -----------------------------------------------------------
// STEP 2: Check marks entry
// -----------------------------------------------------------
$unfinalized_sections = [];
foreach ($sections as $sec) {
    $section = $sec['section'];
    $sql_check = "SELECT COUNT(*) as count FROM marks 
                  WHERE course_id=? AND dept=? AND AY=? AND section=? AND is_finalized=1";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("ssss", $course_id, $dept, $AY, $section);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($result) || intval($result['count']) == 0) {
        $unfinalized_sections[] = $section;
    }
}

if (!empty($unfinalized_sections)) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Department Marks Report</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>(Deemed to be University)</h3>
    <h2>Print Department Marks</h2>
</header>
<div class="container-fluid d-flex flex-grow-1">
    <div class="row w-100 flex-grow-1">
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>
        <div class="col-md-9 col-lg-10 text-center" id="content-area">
            <div class="mt-5">
                <h3 class="text-danger">Cannot Generate Report</h3>
                <p>The following sections have not finalized their marks:</p>
                <ul class="list-unstyled d-inline-block text-left text-danger font-weight-bold">
                    <?php foreach ($unfinalized_sections as $s): ?>
                        <li>Section <?php echo htmlspecialchars($s); ?> - Marks not entered/finalized.</li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-3 text-dark">Please finalize all sections before generating the department report.</p>
            </div>
        </div>
    </div>
</div>
<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© 2024 - Developed by Dept. of IT</p>
</footer>
</body>
</html>
<?php
    exit();
}

// -----------------------------------------------------------
// STEP 4: Fetch all finalized students
// -----------------------------------------------------------
$sql_students = "SELECT s.roll_no, s.name, m.section, m.total_marks, m.total_in_words, m.is_absent
                 FROM marks m
                 INNER JOIN student s ON m.roll_no = s.roll_no
                 WHERE m.course_id=? AND m.dept=? AND m.AY=? AND m.is_finalized=1
                 ORDER BY s.roll_no ASC";
$stmt = $conn->prepare($sql_students);
$stmt->bind_param("sss", $course_id, $dept, $AY);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($students)) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>No finalized students found for this course and department.</h3>");
}

// -----------------------------------------------------------
// STEP 5: Examiner details
// -----------------------------------------------------------
$sql_fvc = "SELECT section, faculty_id, ext_faculty_id, mon_year
            FROM fvc WHERE course_id=? AND dept=? AND AY=?";
$stmt = $conn->prepare($sql_fvc);
$stmt->bind_param("sss", $course_id, $dept, $AY);
$stmt->execute();
$res = $stmt->get_result();

$examiner_map = [];
while ($row = $res->fetch_assoc()) {
    $examiner_map[$row['section']] = [
        'faculty_id' => $row['faculty_id'] ?? '',
        'ext_faculty_id' => $row['ext_faculty_id'] ?? '',
        'mon_year' => $row['mon_year'] ?? ''
    ];
}
$stmt->close();

if (empty($examiner_map)) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Faculty verification data missing for this course.</h3>");
}

// -----------------------------------------------------------
// STEP 6: Faculty details cache
// -----------------------------------------------------------
function getFaculty($conn, $fid) {
    $sql = "SELECT name, designation FROM faculty WHERE faculty_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $fid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: ['name' => 'N/A', 'designation' => 'N/A'];
}

$faculty_cache = [];
foreach ($examiner_map as $sec => $pair) {
    if (!empty($pair['faculty_id']) && !isset($faculty_cache[$pair['faculty_id']])) {
        $faculty_cache[$pair['faculty_id']] = getFaculty($conn, $pair['faculty_id']);
    }
    if (!empty($pair['ext_faculty_id']) && !isset($faculty_cache[$pair['ext_faculty_id']])) {
        $faculty_cache[$pair['ext_faculty_id']] = getFaculty($conn, $pair['ext_faculty_id']);
    }
}

// -----------------------------------------------------------
// STEP 7: Course details
// -----------------------------------------------------------
$sql_course = "SELECT name, type FROM courses WHERE course_id=?";
$stmt = $conn->prepare($sql_course);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// -----------------------------------------------------------
// STEP 8: PDF Definition
// -----------------------------------------------------------
class PDF extends FPDF {
    public $course;
    public $dept;
    public $AY;
    public $course_id;
    public $mon_year;
    public $internal;
    public $external;

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
        $this->Cell(0, 5, 'SUMMATIVE ASSESSMENT MARKS REPORT', 0, 1, 'C');
        $this->Ln(2);

        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4.5, utf8_decode("Course: {$this->course_id} - " . ($this->course['name'] ?? '') . " (" . ($this->course['type'] ?? '') . ")"), 0, 1, 'C');
        $this->Cell(0, 4.5, "Dept: {$this->dept}   |   Academic Year: {$this->AY}", 0, 1, 'C');
        if (!empty($this->mon_year))
            $this->Cell(0, 4.5, "Month & Year: {$this->mon_year}", 0, 1, 'C');

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

    public function getPageBreakTrigger() {
        return $this->PageBreakTrigger;
    }

    function NbLines($w, $txt) {
        $cw = $this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += isset($cw[$c]) ? $cw[$c] : $cw[' '];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

    function Footer() {
    // --- Signature boxes section (unchanged) ---
    $this->SetY(-38);
    $this->SetDrawColor(0, 0, 0);
    $this->SetFont('Arial', '', 8);

    $boxWidth = 85;
    $boxHeight = 22;
    $y = $this->GetY();
    $xLeft = 20;
    $xRight = 110;
    $lineHeight = 4;

    $this->Rect($xLeft, $y, $boxWidth, $boxHeight);
    $this->SetXY($xLeft + 5, $y + 3);
    $this->Cell($boxWidth - 10, $lineHeight, "Signature: ____________________", 0, 1, 'L');
    $this->SetX($xLeft + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Faculty ID: " . ($this->internal['faculty_id'] ?? '') . " (Examiner 1)", 0, 1, 'L');
    $this->SetFont('Arial', 'B', 8.5);
    $this->SetX($xLeft + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Name: " . ($this->internal['name'] ?? ''), 0, 1, 'L');
    $this->SetFont('Arial', '', 8);
    $this->SetX($xLeft + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Designation: " . ($this->internal['designation'] ?? ''), 0, 1, 'L');

    $this->Rect($xRight, $y, $boxWidth, $boxHeight);
    $this->SetXY($xRight + 5, $y + 3);
    $this->Cell($boxWidth - 10, $lineHeight, "Signature: ____________________", 0, 1, 'L');
    $this->SetX($xRight + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Faculty ID: " . ($this->external['faculty_id'] ?? '') . " (Examiner 2)", 0, 1, 'L');
    $this->SetFont('Arial', 'B', 8.5);
    $this->SetX($xRight + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Name: " . ($this->external['name'] ?? ''), 0, 1, 'L');
    $this->SetFont('Arial', '', 8);
    $this->SetX($xRight + 5);
    $this->Cell($boxWidth - 10, $lineHeight, "Designation: " . ($this->external['designation'] ?? ''), 0, 1, 'L');

    // --- Timestamp + Page number line ---
    $this->SetY(-10);
    $this->SetFont('Arial', 'I', 7.5);
    $this->SetTextColor(90, 90, 90);
    date_default_timezone_set('Asia/Kolkata');
    $timestamp = date("j F Y, g:i A");
    $this->Cell(0, 5, "Report generated on {$timestamp}", 0, 0, 'L'); // ✅ Dynamic time (left)
    $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R'); // ✅ Page number (right)
}

}

// -----------------------------------------------------------
// STEP 9: PDF Generation
// -----------------------------------------------------------
$pdf = new PDF();
$pdf->course = $course;
$pdf->dept = $dept;
$pdf->AY = $AY;
$pdf->course_id = $course_id;
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 45);
$pdf->SetFont('Arial', '', 8.5);

$rowsPerPage = 25;
$totalStudents = count($students);
$totalPages = ceil($totalStudents / $rowsPerPage);
$serial = 1;

for ($p = 0; $p < $totalPages; $p++) {
    $pageStudents = array_slice($students, $p * $rowsPerPage, $rowsPerPage);

    $sectionCount = [];
    foreach ($pageStudents as $stu) {
        $secKey = $stu['section'] ?? '';
        $sectionCount[$secKey] = ($sectionCount[$secKey] ?? 0) + 1;
    }
    if (!empty($sectionCount)) {
        arsort($sectionCount);
        $majorSec = array_key_first($sectionCount);
    } else {
        $majorSec = array_key_first($examiner_map);
    }

    $exm = $examiner_map[$majorSec] ?? reset($examiner_map);
    $pdf->mon_year = $exm['mon_year'] ?? '';
    $pdf->internal = [
        'faculty_id' => $exm['faculty_id'] ?? '',
        'name' => ucwords(strtolower($faculty_cache[$exm['faculty_id']]['name'] ?? 'Internal Faculty')),
        'designation' => $faculty_cache[$exm['faculty_id']]['designation'] ?? 'N/A'
    ];
    $pdf->external = [
        'faculty_id' => $exm['ext_faculty_id'] ?? '',
        'name' => ucwords(strtolower($faculty_cache[$exm['ext_faculty_id']]['name'] ?? 'External Faculty')),
        'designation' => $faculty_cache[$exm['ext_faculty_id']]['designation'] ?? 'N/A'
    ];

    $pdf->AddPage();

    foreach ($pageStudents as $stu) {
        $name = utf8_decode($stu['name'] ?? '');
        $roll = $stu['roll_no'] ?? '';
        $is_absent = $stu['is_absent'] ?? 0;

        // 🆕 Show AB if absent
        if ($is_absent == 1) {
            $marks = "AB";
            $words = "AB";
        } else {
            $marks = $stu['total_marks'] ?? '';
            if ($marks !== null && $marks !== '' && preg_match('/\d/', (string)$marks)) {
                $wordsDigits = numberToDigitWords($marks);
            } else {
                $wordsDigits = $stu['total_in_words'] ?? '';
            }
            $words = utf8_decode($wordsDigits);
        }

        $snoW = 10;
        $rollW = 22;
        $nameW = 95;
        $marksW = 18;
        $wordsW = 40;

        $lineHeight = 7;
        $nb = $pdf->NbLines($nameW, $name);
        $rowHeight = max($lineHeight, $nb * $lineHeight);

        if ($pdf->GetY() + $rowHeight > $pdf->getPageBreakTrigger()) {
            $pdf->AddPage();
        }

        $pdf->Cell($snoW, $rowHeight, $serial++, 1, 0, 'C');
        $pdf->Cell($rollW, $rowHeight, $roll, 1, 0, 'C');

        $xName = $pdf->GetX();
        $yName = $pdf->GetY();
        $pdf->MultiCell($nameW, $lineHeight, $name, 1, 'L');

        $pdf->SetXY($xName + $nameW, $yName);
        $pdf->Cell($marksW, $rowHeight, $marks, 1, 0, 'C');
        $pdf->Cell($wordsW, $rowHeight, $words, 1, 1, 'C');
    }
}

$filename = "LAB_Final_{$course_id}_{$dept}_{$AY}.pdf";
$pdf->Output('I', $filename);
exit;
?>

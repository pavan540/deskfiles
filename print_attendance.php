<?php
declare(strict_types=1);
session_start();
require_once 'connection.php';
require_once 'fpdf/fpdf.php';
date_default_timezone_set('Asia/Kolkata'); // Chennai timezone

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}

$logged_in_faculty_id = $_SESSION['faculty_id'];
$course_id = $_SESSION['course_id'] ?? ($_GET['course_id'] ?? '');
$dept      = $_SESSION['dept']      ?? ($_GET['dept']      ?? '');
$AY        = $_SESSION['AY']        ?? ($_GET['AY']        ?? '');

if ($course_id === '' || $dept === '' || $AY === '') {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Invalid request! Missing course/department/AY.</h3>");
}

/* ============================== Ensure all sections finalized ============================== */
$sql_sections = "SELECT DISTINCT section, faculty_id, ext_faculty_id FROM fvc WHERE course_id=? AND dept=? AND AY=? ORDER BY section ASC";
$stmt = $conn->prepare($sql_sections);
$stmt->bind_param("sss", $course_id, $dept, $AY);
$stmt->execute();
$res_sections = $stmt->get_result();
$sections = [];
while ($row = $res_sections->fetch_assoc()) {
    $sections[] = $row;
}
$stmt->close();

$unfinalized_sections = [];
foreach ($sections as $sec) {
    $section_num = trim($sec['section']);
    $q = $conn->prepare("SELECT COUNT(*) AS finalized FROM marks WHERE course_id=? AND dept=? AND AY=? AND section=? AND is_finalized=1");
    $q->bind_param("ssss", $course_id, $dept, $AY, $section_num);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    if (intval($row['finalized']) === 0) {
        $unfinalized_sections[] = $section_num;
    }
}

if (!empty($unfinalized_sections)) {
    echo "<div style='margin:60px auto;max-width:700px;border:2px solid #dc3545;padding:25px;border-radius:10px;text-align:center;font-family:Arial'>";
    echo "<h2 style='color:#dc3545;'>⚠ Cannot Generate Absent Statement</h2>";
    echo "<p>Marks not finalized for section(s): <b>".implode(', ',$unfinalized_sections)."</b></p>";
    echo "<a href='print_lab_marks.php' style='background:#007bff;color:white;padding:8px 16px;border-radius:6px;text-decoration:none;'>← Back</a>";
    echo "</div>";
    exit;
}

/* ============================== Helpers ============================== */
function get_faculty_info($conn, $faculty_id) {
    if (!$faculty_id) return ['name' => 'N/A'];
    $stmt = $conn->prepare("SELECT name FROM faculty WHERE faculty_id=?");
    $stmt->bind_param("s", $faculty_id);
    $stmt->execute();
    $fac = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['name' => $fac['name'] ?? 'N/A'];
}

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
        usort($items, fn($a,$b)=>$a[1]<=>$b[1]);
        $i=0;$n=count($items);
        while($i<$n){
            $start=$items[$i];$j=$i;
            while($j+1<$n && $items[$j+1][1]===$items[$j][1]+1)$j++;
            $parts[]=$j===$i?$items[$i][2]:$items[$i][2].' - '.$items[$j][2];
            $i=$j+1;
        }
    }
    return implode(", ", $parts);
}

/* ============================== Metadata ============================== */
$sql_meta = "
SELECT fvc.sem,fvc.mon_year,p.programme_name,s.school_name,d.dept_name,c.name AS course_name
FROM fvc
LEFT JOIN programmes p ON fvc.programme_id=p.programme_id
LEFT JOIN departments d ON p.dept_id=d.dept_id
LEFT JOIN schools s ON p.school_id=s.school_id
LEFT JOIN courses c ON fvc.course_id=c.course_id
WHERE fvc.course_id=? AND fvc.dept=? AND fvc.AY=? AND fvc.section='1' LIMIT 1";
$stmt=$conn->prepare($sql_meta);
$stmt->bind_param("sss",$course_id,$dept,$AY);
$stmt->execute();
$fvc_meta=$stmt->get_result()->fetch_assoc();
$stmt->close();

$programme=$fvc_meta['programme_name']??'N/A';
$school_name=$fvc_meta['school_name']??'N/A';
$dept_name=$fvc_meta['dept_name']??$dept;
$sem=intval($fvc_meta['sem']??1);
$mon_year=$fvc_meta['mon_year']??'';
$course_name=$fvc_meta['course_name']??'Lab Course';
$year_map=[1=>"I",2=>"I",3=>"II",4=>"II",5=>"III",6=>"III",7=>"IV",8=>"IV"];
$yearRoman=$year_map[$sem]??"I";

/* ============================== Schedule Info ============================== */
$schedules=[];
$stmt=$conn->prepare("SELECT exam_date,session,remarks,start_roll_no,end_roll_no 
                      FROM fvc_schedule 
                      WHERE course_id=? AND dept=? AND AY=? 
                      ORDER BY exam_date ASC,session ASC");
$stmt->bind_param("sss",$course_id,$dept,$AY);
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_assoc()){
    $schedules[]=[
        'date'=>date("d-m-Y",strtotime($row['exam_date'])),
        'session'=>$row['session'],
        'remarks'=>$row['remarks'],
        'start'=>$row['start_roll_no'],
        'end'=>$row['end_roll_no']
    ];
}
$stmt->close();

/* ============================== Student data ============================== */
$sql_students="SELECT roll_no,is_absent FROM marks WHERE course_id=? AND dept=? AND AY=? AND is_finalized=1 ORDER BY roll_no ASC";
$stmt=$conn->prepare($sql_students);
$stmt->bind_param("sss",$course_id,$dept,$AY);
$stmt->execute();
$res=$stmt->get_result();
$rows=$res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$registered=count($rows);
$present_rolls=[];$absent_rolls=[];
foreach($rows as $r){
    if(intval($r['is_absent'])===1)$absent_rolls[]=$r['roll_no'];
    else $present_rolls[]=$r['roll_no'];
}
$total_present=count($present_rolls);
$total_absent=count($absent_rolls);
$present_ranges_str=groupRollNumbersIntoRanges($present_rolls);
sort($absent_rolls,SORT_NATURAL);
$absent_str=empty($absent_rolls)?'None':implode(", ",$absent_rolls);

/* ============================== PDF ============================== */
class PDF extends FPDF{
    public $course_id,$programme,$yearRoman,$sem,$mon_year,$school_name,$dept_name,$course_name,$schedules;
    function Header(){
        if(file_exists('logo.png'))$this->Image('logo.png',15,12,22);
        $this->SetFont('Times','B',14);
        $this->Cell(0,7,'SIDDHARTHA ACADEMY OF HIGHER EDUCATION',0,1,'C');
        $this->SetFont('Arial','',9);
        $this->Cell(0,5,'An Institution Deemed to be University',0,1,'C');
        $this->SetFont('Arial','I',8.5);
        $this->Cell(0,5,'(Under Section 3 of UGC Act, 1956)',0,1,'C');
        $this->SetFont('Arial','',8.5);
        $this->Cell(0,5,'Kanuru, Vijayawada - 520007, AP. www.siddhartha.edu.in',0,1,'C');
        $this->Ln(4);
        $this->SetFont('Times','B',13);
        $this->Cell(0,7,'ABSENT STATEMENT',0,1,'C');
        // Use a thin gray line for separation (optional)
    $this->SetDrawColor(180, 180, 180);
    $this->Line(15, $this->GetY(), 195, $this->GetY());
    $this->Ln(3);
        $this->Ln(4);
    }
   function Footer() {
    // Move 15mm from the bottom
    $this->SetY(-15);

    // Use a thin gray line for separation (optional)
    $this->SetDrawColor(180, 180, 180);
    $this->Line(15, $this->GetY(), 195, $this->GetY());
    $this->Ln(3);

    // Font for footer text
    $this->SetFont('Arial', 'I', 8);

    // Left: Generated date/time
    $this->Cell(0, 5, 'Generated on: ' . date('j F Y, g:i A'), 0, 0, 'L');

    // Right: Page number
    $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
}

}

$pdf=new PDF();
$pdf->AliasNbPages();
$pdf->SetMargins(15,15,15);
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

/* === Examination Details Block === */
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,"Examination: {$programme}, Year {$yearRoman} Sem {$sem}  |  Month & Year: {$mon_year}",0,1,'L');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,"School: {$school_name}",0,1,'L');
$pdf->Cell(0,6,"Department: {$dept_name}",0,1,'L');
$pdf->Ln(4);

/* === Subject Table === */

/* === Subject and Schedule Table (Full Borders Fixed) === */
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(90, 8, 'Sub. Code & Title of the Subject', 1, 0, 'C');
$pdf->Cell(90, 8, 'Exam Dates, Time Slots, Batches & Roll Range', 1, 1, 'C');

// Build schedule text
$schedule_text = '';
foreach ($schedules as $s) {
    $schedule_text .= "{$s['date']} ({$s['session']}) - [{$s['start']} - {$s['end']}]\n";
}

// Calculate height based on number of schedule lines
$lineHeight = 6;
$numLines = max(1, count($schedules));
$totalHeight = $lineHeight * $numLines;

// Left cell (subject)
$pdf->SetFont('Arial', '', 9);
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->MultiCell(90, $totalHeight, "{$course_id} - {$course_name}", 1, 'C');

// Right cell (exam schedule)
$pdf->SetXY($x + 90, $y);
$pdf->MultiCell(90, $lineHeight, trim($schedule_text), 0, 'L');  // no border yet

// Manually draw borders for the right column (to align perfectly)
$currentY = $pdf->GetY();
$pdf->Rect($x + 90, $y, 90, $currentY - $y);  // full right column box
$pdf->Rect($x, $y, 180, $currentY - $y);      // full outer border (safety)
$pdf->Ln(5);



/* === Candidates Summary === */
$pdf->SetFont('Arial','B',9);
$pdf->Cell(60,8,'Number of Candidates Registered (01)',1,0,'C');
$pdf->Cell(60,8,'Number of Candidates Present (02)',1,0,'C');
$pdf->Cell(60,8,'Number of Candidates Absent (03)',1,1,'C');
$pdf->SetFont('Arial','',9);
$pdf->Cell(60,8,$registered,1,0,'C');
$pdf->Cell(60,8,$total_present,1,0,'C');
$pdf->Cell(60,8,$total_absent,1,1,'C');
$pdf->Cell(180,8,'Remarks (if the totals do not agree with columns)',1,1,'L');
$pdf->Ln(3);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,6,'01. Register Number of Candidates Present:',0,1,'L');
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,$present_ranges_str?:'None',1,'L');
$pdf->Ln(3);
$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,6,'02. Register Number of Candidates Absent:',0,1,'L');
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,$absent_str,1,'L');
$pdf->Ln(8);

/* === Dynamic Section-wise Examiner Table === */
/* === Dynamic Section-wise Examiner Table (Full-width and Perfect Alignment) === */
$pdf->SetFont('Arial', 'B', 10);

// Define full table width (same as text area)
$tableWidth = 180; // standard width between margins (A4 with 15mm margins)
$col_section = 15;   // Section
$col_examiner = 45;  // Examiner name
$col_sign = 30;      // Signature (wider for 2-inch space)
$totalWidth = ($col_section + $col_examiner + $col_sign + $col_examiner + $col_sign);

// Ensure columns add up to total width
$extra = $tableWidth - $totalWidth;
if (abs($extra) > 0.1) {
    $col_sign += $extra / 2; // Adjust slightly if needed
}

// Header row
$pdf->Cell($col_section, 8, 'Section', 1, 0, 'C');
$pdf->Cell($col_examiner, 8, 'Examiner 1', 1, 0, 'C');
$pdf->Cell($col_sign, 8, 'Signature', 1, 0, 'C');
$pdf->Cell($col_examiner, 8, 'Examiner 2', 1, 0, 'C');
$pdf->Cell($col_sign, 8, 'Signature', 1, 1, 'C');

$pdf->SetFont('Arial', '', 9);

foreach ($sections as $sec) {
    $int_fac = get_faculty_info($conn, $sec['faculty_id']);
    $ext_fac = get_faculty_info($conn, $sec['ext_faculty_id']);
    $section = $sec['section'];
    $examiner1 = $int_fac['name'];
    $examiner2 = $ext_fac['name'];

    // Calculate max row height
    $lineHeight = 6;
    $nb1 = ceil($pdf->GetStringWidth($examiner1) / ($col_examiner - 2));
    $nb2 = ceil($pdf->GetStringWidth($examiner2) / ($col_examiner - 2));
    $maxLines = max(1, $nb1, $nb2);
    $rowHeight = $lineHeight * $maxLines;

    // Save starting X/Y
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Draw section
    $pdf->Cell($col_section, $rowHeight, $section, 1, 0, 'C');

    // Examiner 1
    $pdf->MultiCell($col_examiner, $lineHeight, $examiner1, 0, 'L');
    $pdf->SetXY($x + $col_section + $col_examiner, $y);

    // Signature 1
    $pdf->Cell($col_sign, $rowHeight, '', 1, 0, 'C');

    // Examiner 2
    $pdf->MultiCell($col_examiner, $lineHeight, $examiner2, 0, 'L');
    $pdf->SetXY($x + $col_section + $col_examiner + $col_sign + $col_examiner, $y);

    // Signature 2
    $pdf->Cell($col_sign, $rowHeight, '', 1, 0, 'C');

    // Borders for multicells
    $pdf->Rect($x + $col_section, $y, $col_examiner, $rowHeight);
    $pdf->Rect($x + $col_section + $col_examiner + $col_sign, $y, $col_examiner, $rowHeight);

    // Move to next line
    $pdf->Ln($rowHeight);
}



/* === Footer info same as before === */
$pdf->Ln(10);
$pdf->Cell(0,6,'3. Total Number of Answer Books enclosed: ________',0,1,'L');
$pdf->Cell(0,6,'4. Total Number of Answer Books Returned: ________',0,1,'L');
$pdf->Cell(0,6,'5. Station: Kanuru, Vijayawada',0,1,'L');
$pdf->Cell(0,6,'6. Date and Hour: ____________________',0,1,'L');
$pdf->Ln(10);
$pdf->Cell(0,6,'Signature of the Dean: ________________________________',0,1,'R');
$pdf->Ln(12);
$pdf->Cell(0,6,'Checked by: ____________________           Verified by: ____________________           Controller of Examinations',0,1,'C');

$filename="Attendance_Statement_{$course_id}_{$dept}_{$AY}.pdf";
$pdf->Output('I',$filename);
exit;
?>

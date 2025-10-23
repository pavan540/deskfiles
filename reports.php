<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require('connection.php');

// Function to get dropdown options
function getDropdownOptions($conn, $table, $column, $where = '') {
    $sql = "SELECT DISTINCT $column FROM $table $where ORDER BY $column";
    $result = $conn->query($sql);
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row[$column];
        }
    } else {
        echo "Error in getDropdownOptions: " . $conn->error;
    }
    return $options;
}

// Get dropdown options 
$course_ids = getDropdownOptions($conn, 'course_faculty', 'course_id');
$branches = getDropdownOptions($conn, 'course_faculty', 'branch');
$sections = ['A', 'B', 'C'];

// Debug: Print dropdown options
echo "Course IDs: " . implode(", ", $course_ids) . "<br>";
echo "Branches: " . implode(", ", $branches) . "<br>";

$attendance_data = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "Form submitted<br>";
    
    $course_id = $_POST['course_id'];
    $semester = $_POST['semester'];
    $branch = $_POST['branch'];
    $section = $_POST['section'];
    $academic_year = '2024_2025';
    
    echo "Selected: Course ID: $course_id, Semester: $semester, Branch: $branch, Section: $section<br>";
    
    // Get distinct attendance dates
    $date_sql = "SELECT DISTINCT attendance_date FROM att_it_2024_2025_1 WHERE courseid = ? ORDER BY attendance_date";
    $date_stmt = $conn->prepare($date_sql);
    if (!$date_stmt) {
        echo "Prepare failed for date query: (" . $conn->errno . ") " . $conn->error;
    } else {
        $date_stmt->bind_param("s", $course_id);
        $date_stmt->execute();
        $date_result = $date_stmt->get_result();
        $dates = [];
        while ($date_row = $date_result->fetch_assoc()) {
            $dates[] = $date_row['attendance_date'];
        }
        $date_stmt->close();
        echo "Dates found: " . implode(", ", $dates) . "<br>";
    }

    // Get attendance data
    $sql = "SELECT a.roll_no, s.sname, a.attendance_date, a.status
            FROM att_it_2024_2025_1 a
            JOIN student s ON a.roll_no = s.roll_no
            JOIN stucoursereg scr ON a.roll_no = scr.roll_no AND a.courseid = scr.courseid
            WHERE a.courseid = ? AND scr.sem = ? AND scr.branch = ? AND s.sec = ? AND scr.ay = ?
            ORDER BY a.roll_no, a.attendance_date";
    
    echo "SQL Query: " . $sql . "<br>";
    echo "Parameters: courseid=$course_id, sem=$semester, branch=$branch, sec=$section, ay=$academic_year<br>";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Prepare failed for attendance query: (" . $conn->errno . ") " . $conn->error;
    } else {
        $stmt->bind_param("sisss", $course_id, $semester, $branch, $section, $academic_year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            echo "Query executed successfully. Fetching results...<br>";
            $row_count = 0;
            while ($row = $result->fetch_assoc()) {
                $row_count++;
                $roll_no = $row['roll_no'];
                if (!isset($attendance_data[$roll_no])) {
                    $attendance_data[$roll_no] = [
                        'name' => $row['sname'],
                        'dates' => array_fill_keys($dates, '-'),
                        'present' => 0,
                        'total' => count($dates)
                    ];
                }
                $attendance_data[$roll_no]['dates'][$row['attendance_date']] = $row['status'];
                if (strtoupper($row['status']) == 'Y') {
                    $attendance_data[$roll_no]['present']++;
                }
                
                // Debug: Print first few rows
                if ($row_count <= 5) {
                    echo "Row $row_count: " . print_r($row, true) . "<br>";
                }
            }
            echo "Total rows fetched: $row_count<br>";
            echo "Attendance data processed for " . count($attendance_data) . " students.<br>";
        } else {
            echo "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Attendance Report</h1>
    
    <form method="post">
        <label for="course_id">Course ID:</label>
        <select name="course_id" required>
            <option value="">Select Course ID</option>
            <?php foreach ($course_ids as $id): ?>
                <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($id); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="semester">Semester:</label>
        <select name="semester" required>
            <option value="">Select Semester</option>
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>

        <label for="branch">Branch:</label>
        <select name="branch" required>
            <option value="">Select Branch</option>
            <?php foreach ($branches as $branch): ?>
                <option value="<?php echo htmlspecialchars($branch); ?>"><?php echo htmlspecialchars($branch); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="section">Section:</label>
        <select name="section" required>
            <option value="">Select Section</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="submit" value="Generate Report">
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <?php if (!empty($attendance_data)): ?>
            <h2>Attendance Report for <?php echo htmlspecialchars("$course_id - Semester $semester - $branch - Section $section"); ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Name</th>
                        <?php foreach ($dates as $date): ?>
                            <th><?php echo htmlspecialchars($date); ?></th>
                        <?php endforeach; ?>
                        <th>Total Present</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_data as $roll_no => $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($roll_no); ?></td>
                            <td><?php echo htmlspecialchars($data['name']); ?></td>
                            <?php foreach ($dates as $date): ?>
                                <td><?php echo htmlspecialchars($data['dates'][$date]); ?></td>
                            <?php endforeach; ?>
                            <td><?php echo htmlspecialchars($data['present']) . ' / ' . htmlspecialchars($data['total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No attendance data found for the selected criteria.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>

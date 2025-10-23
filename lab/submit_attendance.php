<?php
session_start();
include 'connection.php';

// Check if the session is valid
if (!isset($_SESSION['username'])) {
    header("Location: logincheck.php");
    exit();
}

// Get form data
$course_id = $_POST['course_id'] ?? '';
$section = $_POST['section'] ?? '';
$sem = $_POST['sem'] ?? '';
$branch = $_POST['branch'] ?? '';
$ay = $_POST['ay'] ?? '';
$attendance_date = $_POST['attendance_date'] ?? '';
$periods = $_POST['periods'] ?? [];
$absent_students = explode(',', $_POST['absent_students'] ?? '');

// Ensure 'all_students' exists
if (!isset($_POST['all_students']) || empty($_POST['all_students'])) {
    echo "<script>alert('Error: Student list is missing.'); window.location.href='post_attendance.php';</script>";
    exit();
}

// Get all students
$all_students = explode(',', $_POST['all_students']);

// Create table name with pattern att_branch_ay_sem
$table_name = "att_" . $branch . "_" . str_replace('-', '_', $ay) . "_" . $sem;

// Prepare to insert attendance into the database
$inserted = false;
foreach ($periods as $period) {
    foreach ($all_students as $roll_no) {
        // Check if the student is absent
        $status = in_array($roll_no, $absent_students) ? 'n' : 'y';

        // Insert attendance into dynamically generated table
        $query = "INSERT INTO $table_name (roll_no, courseid, attendance_date, period, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo "<script>alert('Error: Could not prepare SQL statement.'); window.location.href='post_attendance.php';</script>";
            exit();
        }
        $stmt->bind_param("sssis", $roll_no, $course_id, $attendance_date, $period, $status);
        if ($stmt->execute()) {
            $inserted = true;
        } else {
            echo "<script>alert('Error: Attendance submission failed.'); window.location.href='post_attendance.php';</script>";
            exit();
        }
    }
}

// Show success message if inserted
if ($inserted) {
    echo "<script>
        alert('Attendance has been successfully recorded.');
        setTimeout(function() {
            window.location.href = 'post_attendance.php';
        }, 5000);
    </script>";
} else {
    echo "<script>
        alert('No attendance data was recorded. Please try again.');
        window.location.href = 'post_attendance.php';
    </script>";
}
?>

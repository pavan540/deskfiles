<?php
// Start the session
session_start();
include 'connection.php';

// Check if the session is set and valid
if (!isset($_SESSION['username'])) {
    header("Location: logincheck.php");
    exit();
}

// Get the POST parameters (course_id, section, sem, branch, ay)
$course_id = $_POST['course_id'] ?? '';
$section = $_POST['section'] ?? '';
$sem = $_POST['sem'] ?? '';
$branch = $_POST['branch'] ?? '';
$ay = $_POST['ay'] ?? '';

// Fetch students from the stucoursereg and student tables
$query = "
    SELECT s.roll_no, s.sname 
    FROM stucoursereg sc
    JOIN student s ON sc.roll_no = s.roll_no
    WHERE sc.courseid = ? AND sc.ay = ? AND sc.branch = ? AND sc.sem = ? AND s.sec = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $course_id, $ay, $branch, $sem, $section);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .student-btn {
            margin: 5px;
            padding: 2px 10px;
            width: auto;
            height: 30px;
            text-align: center;
            border-radius: 5px;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .present-btn {
            background-color: #28a745;
            color: white;
        }

        .absent-btn {
            background-color: #dc3545;
            color: white;
        }

        .student-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        #date-and-period {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .period-checkbox {
            margin-left: 20px;
        }

        #present-box, #absent-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
            min-height: 80px;
        }

        .btn-box {
            display: flex;
            flex-wrap: wrap;
        }

        .day-display {
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-primary text-white text-center py-3">
        <h2>VELAGAPUDI RAMAKRISHNA SIDDHARTHA ENGINEERING COLLEGE</h2>
        <h3>(Deemed to be University)</h3>
        <h2>Student Information System</h2>
    </header>

    <div class="container-fluid d-flex flex-grow-1">
        <div class="row w-100 flex-grow-1">
            <?php include 'faculty_menu.php'; ?>

            <div class="col-md-9 col-lg-10">
                <div class="container mt-5">
                    <h3>Attendance for Course: <?= htmlspecialchars($course_id) ?> | Section: <?= htmlspecialchars($section) ?> | Branch: <?= htmlspecialchars($branch) ?> | Sem: <?= htmlspecialchars($sem) ?> | AY: <?= htmlspecialchars($ay) ?></h3>

                    <form method="post" action="submit_attendance.php" onsubmit="return confirmSubmission()">
                        <div id="date-and-period">
                            <div>
                                <label for="attendance-date">Select Date: </label>
                                <input type="date" id="attendance-date" name="attendance_date" onchange="displayDay()" required>
                                <span id="day-display" class="day-display"></span>
                            </div>

                            <div class="period-checkbox">
                                <label>Select Period: </label>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="periods[]" value="<?= $i ?>"> <?= $i ?>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div id="present-box">
                            <h4>Present Students</h4>
                            <div class="btn-box" id="present-students">
                                <?php foreach ($students as $student): ?>
                                    <button type="button" class="student-btn present-btn" id="student-<?= htmlspecialchars($student['roll_no']) ?>" onclick="moveToAbsent(this)">
                                        <?= htmlspecialchars($student['roll_no']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="absent-box">
                            <h4>Absent Students</h4>
                            <div class="btn-box" id="absent-students"></div>
                        </div>

                        <!-- Hidden inputs for POST parameters -->
                        <input type="hidden" name="course_id" value="<?= htmlspecialchars($course_id) ?>">
                        <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                        <input type="hidden" name="sem" value="<?= htmlspecialchars($sem) ?>">
                        <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
                        <input type="hidden" name="ay" value="<?= htmlspecialchars($ay) ?>">
                        
                        <!-- Hidden input to capture absent students -->
                        <input type="hidden" name="absent_students" id="absent_students_input">

                        <!-- Hidden input for all students (present + absent) -->
                        <input type="hidden" name="all_students" id="all_students_input" value="">

                        <button type="submit" class="btn btn-primary">Submit Attendance</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-primary text-white text-center py-3">
        <p>© 2024 Copyrights reserved - Developed by Dept. of IT</p>
    </footer>

    <script>
        function displayDay() {
            var date = document.getElementById("attendance-date").value;
            var dayDisplay = document.getElementById("day-display");
            if (date) {
                var dayOfWeek = new Date(date).toLocaleString('en-us', { weekday: 'long' });
                dayDisplay.textContent = dayOfWeek;
            }
        }

        function moveToAbsent(button) {
            var absentStudentsDiv = document.getElementById("absent-students");
            var presentStudentsDiv = document.getElementById("present-students");

            // Toggle between present and absent
            if (button.classList.contains('present-btn')) {
                button.classList.remove('present-btn');
                button.classList.add('absent-btn');
                absentStudentsDiv.appendChild(button);
            } else {
                button.classList.remove('absent-btn');
                button.classList.add('present-btn');
                presentStudentsDiv.appendChild(button);
            }
        }

        function confirmSubmission() {
            var absentStudents = [];
            var absentButtons = document.querySelectorAll("#absent-students .student-btn");
            absentButtons.forEach(function(button) {
                absentStudents.push(button.textContent.trim());
            });

            var presentStudents = [];
            var presentButtons = document.querySelectorAll("#present-students .student-btn");
            presentButtons.forEach(function(button) {
                presentStudents.push(button.textContent.trim());
            });

            // Add all students (both present and absent) to hidden input
            var allStudents = absentStudents.concat(presentStudents);
            document.getElementById("all_students_input").value = allStudents.join(',');

            // Add absent students to hidden input
            document.getElementById("absent_students_input").value = absentStudents.join(',');

            if (absentStudents.length > 0) {
                if (!confirm("Absent students: " + absentStudents.join(', ') + "\nDo you want to submit?")) {
                    return false; // Cancel submission if not confirmed
                }
            }

            return true;
        }
    </script>
</body>
</html>

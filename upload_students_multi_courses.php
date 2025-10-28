<?php
require_once 'connection.php';
require 'vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_files'])) {
    $files = $_FILES['excel_files'];

    for ($i = 0; $i < count($files['name']); $i++) {
        $fileTmpPath = $files['tmp_name'][$i];
        $fileName = $files['name'][$i];

        if (is_uploaded_file($fileTmpPath)) {
            try {
                $spreadsheet = IOFactory::load($fileTmpPath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                $insertedCount = 0;
                $skippedCount = 0;

                // Loop through rows (skip header row)
                for ($r = 1; $r < count($rows); $r++) {
                    $row = $rows[$r];
                    if (empty($row[1])) continue; // Skip empty roll number rows

                    // Read columns from Excel (0-based index)
                    $roll_no     = trim($row[1]);
                    $name        = trim($row[2]);
                    $section     = trim($row[3]);
                    $branch      = trim($row[4]);
                    $course_ids  = trim($row[5]);
                    $course_names = trim($row[6]);
                    $type        = trim($row[7]);
                    $dept        = trim($row[8]);
                    $sem         = trim($row[9]);
                    $AY          = trim($row[10]);

                    // Split comma-separated courses
                    $courseIdArr = array_map('trim', explode(',', $course_ids));
                    $courseNameArr = array_map('trim', explode(',', $course_names));

                    if (count($courseIdArr) != count($courseNameArr)) {
                        echo "<p>⚠️ Row $r skipped — Course IDs and Names mismatch.</p>";
                        continue;
                    }

                    // === 1️⃣ Insert Student (no email now) ===
                    $stmt1 = $conn->prepare("INSERT IGNORE INTO student (roll_no, name, section, branch) VALUES (?, ?, ?, ?)");
                    $stmt1->bind_param("ssss", $roll_no, $name, $section, $branch);
                    $stmt1->execute();

                    // === 2️⃣ Insert Courses & SVC Records ===
                    for ($c = 0; $c < count($courseIdArr); $c++) {
                        $course_id = $courseIdArr[$c];
                        $course_name = $courseNameArr[$c];

                        // Insert into courses
                        $stmt2 = $conn->prepare("INSERT IGNORE INTO courses (course_id, name, type) VALUES (?, ?, ?)");
                        $stmt2->bind_param("sss", $course_id, $course_name, $type);
                        $stmt2->execute();

                        // Insert into svc (with semester)
                        $stmt3 = $conn->prepare("INSERT IGNORE INTO svc (roll_no, course_id, section, dept, AY, sem)
                                                 VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt3->bind_param("ssssss", $roll_no, $course_id, $section, $dept, $AY, $sem);
                        if ($stmt3->execute()) {
                            $insertedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                }

                echo "<p>✅ File <b>$fileName</b> uploaded successfully.<br>";
                echo "➡️ $insertedCount records inserted, $skippedCount skipped (duplicates ignored).</p>";

            } catch (Exception $e) {
                echo "<p>❌ Error processing file <b>$fileName</b>: " . $e->getMessage() . "</p>";
            }
        }
    }
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Students with Multiple Courses (With Semester)</title>
    <style>
        body { font-family: Arial; background: #f8f9fa; margin: 30px; }
        form { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type=file] { margin: 10px 0; }
        input[type=submit] {
            background: #007bff; color: white;
            padding: 10px 20px; border: none;
            border-radius: 5px; cursor: pointer;
        }
        input[type=submit]:hover { background: #0056b3; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>📘 Upload Students with Multiple Courses (Comma-Separated, With Semester)</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="excel_files[]" multiple accept=".xls,.xlsx" required><br><br>
        <input type="submit" value="Upload & Insert Data">
    </form>
</body>
</html>
<?php
}
?>

<?php
session_start();
require_once 'connection.php';

/* ============================== Auth ============================== */
if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.html");
    exit();
}
$logged_in_faculty_id = $_SESSION['faculty_id'];

date_default_timezone_set('Asia/Kolkata');
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Map full department names to short codes (used for student.branch & svc.dept) */
function dept_short_from_name($name){
    static $map = [
        'Information Technology' => 'IT',
        'Computer Science and Engineering' => 'CSE',
        'Electronics and Communication Engineering' => 'ECE',
        'Electrical and Electronics Engineering' => 'EEE',
        'Mechanical Engineering' => 'ME',
        'Civil Engineering' => 'CE',
        'Chemistry' => 'CHEM',
        'Mathematics' => 'MATH',
        'Physics' => 'PHY',
        'Computer Applications' => 'CA',
        'Business Administration' => 'BA',
        'Arts & Commerce' => 'A&C',
        'Master of Law' => 'LAW',
        'English' => 'ENG',
        'Electronics and Instrumentation Engineering' => 'EIE',
    ];
    return $map[$name] ?? strtoupper(substr($name, 0, 3));
}

/* ============================== AJAX ============================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    try {
        $mode = $_GET['mode'] ?? '';

        // 1) School → Departments + broad Programmes
        if ($mode === 'schoolCascade') {
            $sid = (int)($_GET['school_id'] ?? 0);
            $dept_opts = "<option value=''>-- Select Department --</option>";
            $prog_opts = "<option value=''>-- Select Programme --</option>";

            if ($sid > 0) {
                $q = $GLOBALS['conn']->prepare("SELECT dept_id, dept_name FROM departments WHERE school_id=? ORDER BY dept_name");
                $q->bind_param("i", $sid);
                $q->execute();
                $r = $q->get_result();
                while ($row = $r->fetch_assoc()) {
                    $dept_opts .= "<option value='".(int)$row['dept_id']."'>".h($row['dept_name'])."</option>";
                }
                $q->close();

                $p = $GLOBALS['conn']->prepare("SELECT programme_id, programme_name FROM programmes WHERE school_id=? ORDER BY programme_name");
                $p->bind_param("i", $sid);
                $p->execute();
                $rp = $p->get_result();
                while ($row = $rp->fetch_assoc()) {
                    $prog_opts .= "<option value='".(int)$row['programme_id']."'>".h($row['programme_name'])."</option>";
                }
                $p->close();
            }

            echo json_encode(['ok'=>true, 'departments'=>$dept_opts, 'programmes'=>$prog_opts]);
            exit;
        }

        // 2) Department → Filtered Programmes
        if ($mode === 'deptProgrammes') {
            $sid = (int)($_GET['school_id'] ?? 0);
            $did = (int)($_GET['dept_id'] ?? 0);
            $prog_opts = "<option value=''>-- Select Programme --</option>";

            if ($sid > 0 && $did > 0) {
                $p = $GLOBALS['conn']->prepare("SELECT programme_id, programme_name FROM programmes WHERE school_id=? AND dept_id=? ORDER BY programme_name");
                $p->bind_param("ii", $sid, $did);
                $p->execute();
                $rp = $p->get_result();
                while ($row = $rp->fetch_assoc()) {
                    $prog_opts .= "<option value='".(int)$row['programme_id']."'>".h($row['programme_name'])."</option>";
                }
                $p->close();
            }
            echo json_encode(['ok'=>true, 'programmes'=>$prog_opts]);
            exit;
        }

        // 3) Sections suggestions
        // 3) Sections suggestions - ALWAYS show default 1–10
if ($mode === 'sections') {
    $sections = [];
    for ($i = 1; $i <= 10; $i++) {
        $sections[(string)$i] = true;
    }

    $opts = "<option value=''>-- Select Section --</option>";
    foreach (array_keys($sections) as $sec) {
        $opts .= "<option value='".h($sec)."'>".h($sec)."</option>";
    }

    echo json_encode(['ok'=>true, 'sections'=>$opts]);
    exit;
}


        // 4) Fetch ALL rolls from SVC for the selection (no student fallback)
        if ($mode === 'svcRolls') {
            $course_id = trim($_GET['course_id'] ?? '');
            $dept_name = trim($_GET['dept_name'] ?? '');
            $section   = trim($_GET['section'] ?? '');
            $AY        = trim($_GET['AY'] ?? '');

            if (!$course_id || !$dept_name || !$section || !$AY) {
                echo json_encode(['ok'=>false, 'error'=>'Missing parameters']);
                exit;
            }

            $dept_short = dept_short_from_name($dept_name); // svc.dept uses short codes like IT, CSE...
            $rolls = [];

            $stmt = $GLOBALS['conn']->prepare("
                SELECT roll_no
                FROM svc
                WHERE course_id=? AND dept=? AND section=? AND AY=?
                ORDER BY roll_no
            ");
            $stmt->bind_param("ssss", $course_id, $dept_short, $section, $AY);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $rolls[] = $row['roll_no'];
            }
            $stmt->close();

            echo json_encode(['ok'=>true, 'count'=>count($rolls), 'rolls'=>$rolls, 'dept_short'=>$dept_short]);
            exit;
        }

        echo json_encode(['ok'=>false, 'error'=>'Unknown mode']);
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}

/* ============================== Ensure fvc_schedule exists ============================== */
$conn->query("
CREATE TABLE IF NOT EXISTS fvc_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id VARCHAR(20) NOT NULL,
    dept VARCHAR(100) NOT NULL,
    AY VARCHAR(20) NOT NULL,
    section VARCHAR(10) NOT NULL,
    start_roll_no VARCHAR(20) NOT NULL,
    end_roll_no VARCHAR(20) NOT NULL,
    exam_date DATE NOT NULL,
    session ENUM('FN','AN') NOT NULL,
    remarks VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tuple (course_id, section, dept, AY),
    CONSTRAINT fk_fvc_sched FOREIGN KEY (course_id, section, dept, AY)
      REFERENCES fvc(course_id, section, dept, AY)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
");

/* ============================== Load dropdown data ============================== */
$courses  = $conn->query("SELECT course_id, name FROM courses ORDER BY course_id")->fetch_all(MYSQLI_ASSOC);
$faculties= $conn->query("SELECT faculty_id, name, designation FROM faculty ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$schools  = $conn->query("SELECT school_id, school_name FROM schools ORDER BY school_name")->fetch_all(MYSQLI_ASSOC);

/* ============================== AY list ============================== */
$now = new DateTime();
$baseY = (int)$now->format('Y');
$ay_list = [];
for ($i=0;$i<5;$i++){
    $y1 = $baseY + $i;
    $ay_list[] = sprintf("%d-%02d", $y1, ($y1+1)%100);
}

$months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
$year_opts = [];
for ($y=$baseY-1; $y <= $baseY+6; $y++) $year_opts[] = $y;

/* ============================== Handle POST (insert/update) ============================== */
$alert = ['type'=>'', 'msg'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Core selections
    $school_id    = (int)($_POST['school_id'] ?? 0);
    $dept_id      = (int)($_POST['dept_id'] ?? 0);
    $programme_id = (int)($_POST['programme_id'] ?? 0);

    $course_id = trim($_POST['course_id'] ?? '');
    $AY        = trim($_POST['AY'] ?? '');
    $section   = trim($_POST['section'] ?? '');
    $sem       = trim($_POST['sem'] ?? '');
    $mon_sel   = (int)($_POST['mon_sel'] ?? 0);
    $yr_sel    = (int)($_POST['yr_sel'] ?? 0);

    $int_fid   = trim($_POST['faculty_id'] ?? '');
    $ext_fid   = trim($_POST['ext_faculty_id'] ?? '');

    // Batches
    $starts   = $_POST['batch_start_roll'] ?? [];
    $ends     = $_POST['batch_end_roll'] ?? [];
    $dates    = $_POST['batch_date'] ?? [];
    $sessions = $_POST['batch_session'] ?? [];
    $remarks  = $_POST['batch_remarks'] ?? [];

    $replace_existing = isset($_POST['replace_existing']) ? 1 : 0;

    // Compose Month, Year -> "Month, YYYY"
    $mon_year = (isset($months[$mon_sel]) && $yr_sel>0) ? ($months[$mon_sel].", ".$yr_sel) : '';

    // Resolve dept text & programme name (dept short is used in fvc/fvc_schedule here)
    $dept_name = '';
    if ($dept_id > 0) {
        $st = $conn->prepare("SELECT dept_name FROM departments WHERE dept_id=?");
        $st->bind_param("i", $dept_id);
        $st->execute();
        $st->bind_result($dept_name);
        $st->fetch();
        $st->close();
    }
    $dept_text = ($dept_name!=='') ? dept_short_from_name($dept_name) : '';

    $prog_name = '';
    if ($programme_id > 0) {
        $st = $conn->prepare("SELECT programme_name FROM programmes WHERE programme_id=?");
        $st->bind_param("i", $programme_id);
        $st->execute();
        $st->bind_result($prog_name);
        $st->fetch();
        $st->close();
    }

    // Validation
    if ($school_id<=0 || $dept_id<=0 || $programme_id<=0 || $course_id==='' || $AY==='' || $section==='' || $sem==='' || $int_fid==='' || $mon_year==='') {
        $alert = ['type'=>'danger','msg'=>'Please fill all required fields marked with *.'];
    } else {
        $conn->begin_transaction();
        try {
            // Upsert into fvc (PK: course_id, section, dept, AY)
      $sql = "INSERT INTO fvc (faculty_id, ext_faculty_id, course_id, section, dept, sem, programme_id, AY, mon_year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          faculty_id=VALUES(faculty_id),
          ext_faculty_id=VALUES(ext_faculty_id),
          sem=VALUES(sem),
          programme_id=VALUES(programme_id),
          mon_year=VALUES(mon_year)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssiss",
    $int_fid, $ext_fid, $course_id, $section, $dept_text, $sem, $programme_id, $AY, $mon_year
);

            $stmt->execute();
            $stmt->close();

            // Replace existing schedules if requested
            if ($replace_existing) {
                $del = $conn->prepare("DELETE FROM fvc_schedule WHERE course_id=? AND dept=? AND AY=? AND section=?");
                $del->bind_param("ssss", $course_id, $dept_text, $AY, $section);
                $del->execute();
                $del->close();
            }

            // Insert schedule batches
            $ins = $conn->prepare("INSERT INTO fvc_schedule
                (course_id, dept, AY, section, start_roll_no, end_roll_no, exam_date, session, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $inserted = 0;
            $rows = max(count($starts), count($ends), count($dates), count($sessions));
            for ($i=0; $i<$rows; $i++){
                $sr = trim($starts[$i] ?? '');
                $er = trim($ends[$i] ?? '');
                $dt = trim($dates[$i] ?? '');
                $ss = trim($sessions[$i] ?? '');
                $rm = trim($remarks[$i] ?? '');

                if ($sr==='' || $er==='' || $dt==='' || !in_array($ss, ['FN','AN'], true)) continue;

                $ins->bind_param("sssssssss", $course_id, $dept_text, $AY, $section, $sr, $er, $dt, $ss, $rm);
                $ins->execute();
                if ($ins->affected_rows>0) $inserted++;
            }
            $ins->close();

            $conn->commit();
            $alert = ['type'=>'success','msg'=>"Saved successfully for <strong>".h($course_id)." / ".h($dept_text)." / ".h($AY)." / Sec ".h($section)."</strong>. Inserted <strong>{$inserted}</strong> schedule batch(es)."];
        } catch (Throwable $e) {
            $conn->rollback();
            $alert = ['type'=>'danger','msg'=>'Error while saving: '.h($e->getMessage())];
        }
    }
}

/* ============================== Preserve POST for server re-render ============================== */
function old($k,$d=''){ return h($_POST[$k] ?? $d); }

$pre_depts = [];
$pre_progs = [];
if ((int)old('school_id',0) > 0) {
    $sid = (int)old('school_id',0);

    // Departments under selected school
    $q = $conn->prepare("SELECT dept_id, dept_name FROM departments WHERE school_id=? ORDER BY dept_name");
    $q->bind_param("i",$sid);
    $q->execute(); $r=$q->get_result(); $pre_depts=$r->fetch_all(MYSQLI_ASSOC); $q->close();

    // Programmes under selected school (broad)
    $p = $conn->prepare("SELECT programme_id, programme_name FROM programmes WHERE school_id=? ORDER BY programme_name");
    $p->bind_param("i",$sid);
    $p->execute(); $rp=$p->get_result(); $pre_progs=$rp->fetch_all(MYSQLI_ASSOC); $p->close();

    // If a department is already chosen, refine programmes to that dept + school
    if ((int)old('dept_id',0) > 0) {
        $did = (int)old('dept_id',0);
        $p = $conn->prepare("SELECT programme_id, programme_name FROM programmes WHERE school_id=? AND dept_id=? ORDER BY programme_name");
        $p->bind_param("ii",$sid,$did);
        $p->execute(); $rp=$p->get_result(); $pre_progs=$rp->fetch_all(MYSQLI_ASSOC); $p->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assign Faculty & Schedule Lab Exams</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet"
 href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
    .required:after { content:" *"; color:#dc3545; }
    .table thead th { white-space: nowrap; }
    .sticky-actions { position: sticky; bottom: 0; background: #fff; padding: .75rem 0; }
    .muted { color:#6c757d; }
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
    <h3>(Deemed to be University)</h3>
    <h2>Assign Faculty & Schedule Lab Exams</h2>
</header>

<div class="container-fluid d-flex flex-grow-1">
    <div class="row w-100 flex-grow-1">
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php if (file_exists('faculty_menu.php')) include('faculty_menu.php'); ?>
        </nav>

        <main class="col-md-9 col-lg-10 py-4">
            <?php if ($alert['msg']): ?>
                <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert['msg']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off" novalidate>
                <div class="card shadow-sm mb-4">
                    <div class="card-header font-weight-bold">Faculty Assignment (FVC)</div>
                    <div class="card-body">
                        <!-- Row 1: School / Department / Programme -->
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="required">School</label>
                                <select name="school_id" id="school" class="form-control" required>
                                    <option value="">-- Select --</option>
                                    <?php foreach ($schools as $s): ?>
                                        <option value="<?php echo (int)$s['school_id']; ?>" <?php echo (old('school_id')==$s['school_id'])?'selected':''; ?>>
                                            <?php echo h($s['school_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label class="required">Department</label>
                                <select name="dept_id" id="department" class="form-control" required>
                                    <?php if (!empty($pre_depts)): ?>
                                        <option value="">-- Select Department --</option>
                                        <?php foreach($pre_depts as $d): ?>
                                            <option value="<?php echo (int)$d['dept_id']; ?>" <?php echo (old('dept_id')==$d['dept_id'])?'selected':''; ?>>
                                                <?php echo h($d['dept_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">-- Select School First --</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label class="required">Programme</label>
                                <select name="programme_id" id="programme" class="form-control" required>
                                    <?php if (!empty($pre_progs)): ?>
                                        <option value="">-- Select Programme --</option>
                                        <?php foreach($pre_progs as $p): ?>
                                            <option value="<?php echo (int)$p['programme_id']; ?>" <?php echo (old('programme_id')==$p['programme_id'])?'selected':''; ?>>
                                                <?php echo h($p['programme_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">-- Select School First --</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Row 2: Course / AY / Section -->
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="required">Course ID</label>
                                <select name="course_id" id="course_id" class="form-control" required>
                                    <option value="">-- Select --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo h($c['course_id']); ?>" <?php echo (old('course_id')===$c['course_id'])?'selected':''; ?>>
                                            <?php echo h($c['course_id'].' - '.$c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label class="required">Academic Year (AY)</label>
                                <select name="AY" id="AY" class="form-control" required>
                                    <option value="">-- Select AY --</option>
                                    <?php foreach ($ay_list as $ay): ?>
                                        <option value="<?php echo h($ay); ?>" <?php echo (old('AY')===$ay)?'selected':''; ?>>
                                            <?php echo h($ay); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label class="required">Section</label>
                                <select name="section" id="section" class="form-control" required>
                                    <?php
                                    if (old('section','') !== '') {
                                        echo "<option value='".old('section')."' selected>".old('section')."</option>";
                                    } else {
                                        echo "<option value=''>-- Select Section --</option>";
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Populates from FVC → Student(branch) → default 1–5.</small>
                            </div>
                        </div>

                        <!-- Row 3: Semester / Month / Year -->
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="required">Semester</label>
                                <select name="sem" id="sem" class="form-control" required>
                                    <?php for ($i=1;$i<=8;$i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (old('sem')==$i)?'selected':''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="required">Exam Month</label>
                                <select name="mon_sel" id="mon_sel" class="form-control" required>
                                    <option value="">-- Month --</option>
                                    <?php foreach ($months as $num=>$name): ?>
                                        <option value="<?php echo $num; ?>" <?php echo ((int)old('mon_sel',0)===$num)?'selected':''; ?>><?php echo h($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="required">Exam Year</label>
                                <select name="yr_sel" id="yr_sel" class="form-control" required>
                                    <option value="">-- Year --</option>
                                    <?php foreach ($year_opts as $yy): ?>
                                        <option value="<?php echo $yy; ?>" <?php echo ((int)old('yr_sel',0)===$yy)?'selected':''; ?>><?php echo $yy; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Stored as "Month, YYYY".</small>
                            </div>
                        </div>

                        <!-- Row 4: Faculty selection and toggle -->
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="required">Internal Faculty ID</label>
                                <select name="faculty_id" class="form-control" required>
                                    <option value="">-- Select --</option>
                                    <?php foreach ($faculties as $f): ?>
                                        <option value="<?php echo h($f['faculty_id']); ?>" <?php echo (old('faculty_id',$logged_in_faculty_id)===$f['faculty_id'])?'selected':''; ?>>
                                            <?php echo h($f['name'].' ('.$f['faculty_id'].')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Defaulted to logged-in: <?php echo h($logged_in_faculty_id); ?></small>
                            </div>
                            <div class="form-group col-md-6">
                                <label>External Faculty ID</label>
                                <select name="ext_faculty_id" class="form-control">
                                    <option value="">-- Optional --</option>
                                    <?php foreach ($faculties as $f): ?>
                                        <option value="<?php echo h($f['faculty_id']); ?>" <?php echo (old('ext_faculty_id')===$f['faculty_id'])?'selected':''; ?>>
                                            <?php echo h($f['name'].' ('.$f['faculty_id'].')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="replace_existing" name="replace_existing" <?php echo isset($_POST['replace_existing'])?'checked':''; ?>>
                                    <label class="custom-control-label" for="replace_existing" title="Delete existing batches for this Course/Dept/AY/Section before inserting new">
                                        Replace existing schedules
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Batches -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <span class="font-weight-bold">Exam Schedule (Batches)</span>
                        <div>
                            <label class="mb-0 mr-2">Auto batch size</label>
                            <input type="number" id="autoBatchSize" value="30" min="1" class="form-control d-inline-block" style="width:90px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-2" id="rebuildBatchesBtn">Rebuild Batches</button>
                            <small id="svcInfo" class="muted ml-3"></small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-2" id="batchesTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="required">Start Roll No</th>
                                        <th class="required">End Roll No</th>
                                        <th class="required">Exam Date</th>
                                        <th class="required">Session</th>
                                        <th>Remarks</th>
                                        <th style="width:60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rows_count = max(1, is_array($_POST['batch_start_roll'] ?? null) ? count($_POST['batch_start_roll']) : 1);
                                    for ($i=0; $i < $rows_count; $i++):
                                        $sr = h($_POST['batch_start_roll'][$i] ?? '');
                                        $er = h($_POST['batch_end_roll'][$i] ?? '');
                                        $dt = h($_POST['batch_date'][$i] ?? '');
                                        $ss = h($_POST['batch_session'][$i] ?? '');
                                        $rm = h($_POST['batch_remarks'][$i] ?? '');
                                    ?>
                                    <tr>
                                        <td><input type="text" name="batch_start_roll[]" class="form-control" placeholder="25EU08001" value="<?php echo $sr; ?>"></td>
                                        <td><input type="text" name="batch_end_roll[]" class="form-control" placeholder="25EU08036" value="<?php echo $er; ?>"></td>
                                        <td><input type="date" name="batch_date[]" class="form-control" value="<?php echo $dt; ?>"></td>
                                        <td>
                                            <select name="batch_session[]" class="form-control">
                                                <option value="">--</option>
                                                <option value="FN" <?php echo ($ss==='FN')?'selected':''; ?>>FN</option>
                                                <option value="AN" <?php echo ($ss==='AN')?'selected':''; ?>>AN</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="batch_remarks[]" class="form-control" placeholder="Batch" value="<?php echo $rm; ?>"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">&times;</button>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addRowBtn">+ Add Batch</button>
                        <div class="mt-2 muted" id="svcWarning" style="display:none;"></div>
                    </div>
                </div>

                <div class="sticky-actions">
                    <button type="submit" class="btn btn-success btn-lg">Save All</button>
                </div>
            </form>
        </main>
    </div>
</div>

<footer class="bg-primary text-white text-center py-2 mt-auto">
    <p>© <?php echo date('Y'); ?> - Developed by Dept. of IT</p>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
// Add/Remove batch rows
(function(){
    const addRowBtn = document.getElementById('addRowBtn');
    const tableBody = document.querySelector('#batchesTable tbody');
    addRowBtn.addEventListener('click', function(){
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="batch_start_roll[]" class="form-control" placeholder="25EU08001"></td>
            <td><input type="text" name="batch_end_roll[]" class="form-control" placeholder="25EU08036"></td>
            <td><input type="date" name="batch_date[]" class="form-control"></td>
            <td>
                <select name="batch_session[]" class="form-control">
                    <option value="">--</option>
                    <option value="FN">FN</option>
                    <option value="AN">AN</option>
                </select>
            </td>
            <td><input type="text" name="batch_remarks[]" class="form-control" placeholder="Batch"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">&times;</button>
            </td>
        `;
        tableBody.appendChild(tr);
    });
})();
function removeRow(btn){
    const tr = btn.closest('tr');
    const tbody = tr.parentNode;
    if (tbody.children.length > 1) tbody.removeChild(tr);
    else tr.querySelectorAll('input,select').forEach(el => el.value = '');
}

// School change → load departments & broad programmes
$('#school').on('change', function(){
    const sid = $(this).val();
    $('#department').html("<option value=''>Loading...</option>");
    $('#programme').html("<option value=''>Loading...</option>");
    $('#section').html("<option value=''>-- Select Section --</option>");
    clearSvcInfo();
    if(!sid){
        $('#department').html("<option value=''>-- Select School First --</option>");
        $('#programme').html("<option value=''>-- Select School First --</option>");
        return;
    }
    $.get('assign_fvc.php', {ajax:1, mode:'schoolCascade', school_id: sid}, function(resp){
        if(resp.ok){
            $('#department').html(resp.departments);
            $('#programme').html(resp.programmes);
        } else {
            $('#department').html("<option value=''>-- Error --</option>");
            $('#programme').html("<option value=''>-- Error --</option>");
        }
    }, 'json');
});

// Department change → refine programmes to BOTH school & dept
$('#department').on('change', function(){
    const did = $(this).val();
    const sid = $('#school').val();
    $('#programme').html("<option value=''>Loading...</option>");
    $('#section').html("<option value=''>-- Select Section --</option>");
    clearSvcInfo();
    if(!did || !sid){
        $('#programme').html("<option value=''>-- Select Department First --</option>");
        return;
    }
    $.get('assign_fvc.php', {ajax:1, mode:'deptProgrammes', dept_id: did, school_id: sid}, function(resp){
        if(resp.ok){
            $('#programme').html(resp.programmes);
        } else {
            $('#programme').html("<option value=''>-- Error --</option>");
        }
    }, 'json');
});

// Change handlers to refresh section suggestions
function refreshSections(){
    const payload = {
        ajax: 1,
        mode: 'sections',
        course_id: $('#course_id').val(),
        dept_id: $('#department').val(),
        AY: $('#AY').val(),
        programme_id: $('#programme').val()
    };
    $('#section').html("<option value=''>-- Select Section --</option>");
    clearSvcInfo();
    if(!payload.course_id || !payload.dept_id || !payload.AY){
        return;
    }
    $('#section').html("<option value=''>Loading...</option>");
    $.get('assign_fvc.php', payload, function(resp){
        if(resp.ok){
            $('#section').html(resp.sections);
        } else {
            $('#section').html("<option value=''>-- Select Section --</option>");
        }
    }, 'json');
}
$('#course_id,#department,#AY,#programme').on('change', refreshSections);

// ======== SVC Rolls → auto-build batches (no student fallback) ========
let lastSvcRolls = [];
function clearSvcInfo(){
    lastSvcRolls = [];
    $('#svcInfo').text('');
    $('#svcWarning').hide().text('');
}
function fetchSvcRollsAndBuild(){
    const payload = {
        ajax: 1,
        mode: 'svcRolls',
        course_id: $('#course_id').val(),
        dept_name: $('#department option:selected').text(),
        section: $('#section').val(),
        AY: $('#AY').val()
    };
    if(!payload.course_id || !payload.dept_name || !payload.section || !payload.AY){
        clearSvcInfo();
        return;
    }
    $.get('assign_fvc.php', payload, function(resp){
        if(resp.ok){
            lastSvcRolls = resp.rolls || [];
            const count = resp.count || 0;
            const info = count ? `Loaded ${count} roll(s) from SVC (${resp.dept_short}), range: ${lastSvcRolls[0]} → ${lastSvcRolls[count-1]}` : 'No SVC rolls found for this selection.';
            $('#svcInfo').text(info);
            if(count === 0){
                $('#svcWarning').show().text('No students found in SVC for this Course/Dept/AY/Section. Batches not auto-filled.');
                return;
            }
            buildBatchesFromSvc();
        } else {
            clearSvcInfo();
            $('#svcWarning').show().text('Error loading SVC rolls: '+(resp.error || 'Unknown'));
        }
    }, 'json');
}
function buildBatchesFromSvc(){
    const size = Math.max(1, parseInt($('#autoBatchSize').val(),10) || 30);
    const tbody = $('#batchesTable tbody');
    tbody.empty();
    for(let i=0;i<lastSvcRolls.length;i+=size){
        const chunk = lastSvcRolls.slice(i, i+size);
        const start = chunk[0];
        const end   = chunk[chunk.length-1];
        const tr = $(`
            <tr>
                <td><input type="text" name="batch_start_roll[]" class="form-control" value="${start}"></td>
                <td><input type="text" name="batch_end_roll[]" class="form-control" value="${end}"></td>
                <td><input type="date" name="batch_date[]" class="form-control"></td>
                <td>
                    <select name="batch_session[]" class="form-control">
                        <option value="">--</option>
                        <option value="FN">FN</option>
                        <option value="AN">AN</option>
                    </select>
                </td>
                <td><input type="text" name="batch_remarks[]" class="form-control" placeholder="Batch"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">&times;</button>
                </td>
            </tr>
        `);
        tbody.append(tr);
    }
}
$('#rebuildBatchesBtn').on('click', function(){
    if(lastSvcRolls.length === 0){
        $('#svcWarning').show().text('Nothing to rebuild: load SVC data by selecting all fields including Section.');
        return;
    }
    buildBatchesFromSvc();
});
$('#section').on('change', fetchSvcRollsAndBuild);

// Auto-load sections if form had initial values (postback)
$(function(){
    if($('#course_id').val() && $('#department').val() && $('#AY').val()){
        refreshSections();
    }
    // If section already selected (postback), also load SVC rolls
    if($('#course_id').val() && $('#department').val() && $('#AY').val() && $('#section').val()){
        fetchSvcRollsAndBuild();
    }
});
</script>
</body>
</html>

<?php
require_once 'connection.php';
header('Content-Type: application/json');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
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

try {
    $mode = $_GET['mode'] ?? '';

    if ($mode === 'schoolCascade') {
        $sid = (int)($_GET['school_id'] ?? 0);
        $dept_opts = "<option value=''>-- Select Department --</option>";
        $prog_opts = "<option value=''>-- Select Programme --</option>";

        if ($sid > 0) {
            $q = $conn->prepare("SELECT dept_id, dept_name FROM departments WHERE school_id=? ORDER BY dept_name");
            $q->bind_param("i", $sid);
            $q->execute();
            $r = $q->get_result();
            while ($row = $r->fetch_assoc()) {
                $dept_opts .= "<option value='".(int)$row['dept_id']."'>".h($row['dept_name'])."</option>";
            }
            $q->close();

            $p = $conn->prepare("SELECT programme_id, programme_name FROM programmes WHERE school_id=? ORDER BY programme_name");
            $p->bind_param("i", $sid);
            $p->execute();
            $rp = $p->get_result();
            while ($row = $rp->fetch_assoc()) {
                $prog_opts .= "<option value='".(int)$row['programme_id']."'>".h($row['programme_name'])."</option>";
            }
            $p->close();
        }

        echo json_encode(['ok'=>true,'departments'=>$dept_opts,'programmes'=>$prog_opts]);
        exit;
    }

    if ($mode === 'deptProgrammes') {
        $sid = (int)($_GET['school_id'] ?? 0);
        $did = (int)($_GET['dept_id'] ?? 0);
        $prog_opts = "<option value=''>-- Select Programme --</option>";

        if ($sid > 0 && $did > 0) {
            $p = $conn->prepare("SELECT programme_id, programme_name FROM programmes WHERE school_id=? AND dept_id=? ORDER BY programme_name");
            $p->bind_param("ii", $sid, $did);
            $p->execute();
            $rp = $p->get_result();
            while ($row = $rp->fetch_assoc()) {
                $prog_opts .= "<option value='".(int)$row['programme_id']."'>".h($row['programme_name'])."</option>";
            }
            $p->close();
        }

        echo json_encode(['ok'=>true,'programmes'=>$prog_opts]);
        exit;
    }

    if ($mode === 'sections') {
        $course_id = trim($_GET['course_id'] ?? '');
        $dept_id = (int)($_GET['dept_id'] ?? 0);
        $AY = trim($_GET['AY'] ?? '');
        $programme_id = (int)($_GET['programme_id'] ?? 0);

        $dept_name = '';
        if ($dept_id > 0) {
            $s = $conn->prepare("SELECT dept_name FROM departments WHERE dept_id=?");
            $s->bind_param("i", $dept_id);
            $s->execute();
            $s->bind_result($dept_name);
            $s->fetch();
            $s->close();
        }
        $dept_short = dept_short_from_name($dept_name);

        $prog_name = '';
        if ($programme_id > 0) {
            $s = $conn->prepare("SELECT programme_name FROM programmes WHERE programme_id=?");
            $s->bind_param("i", $programme_id);
            $s->execute();
            $s->bind_result($prog_name);
            $s->fetch();
            $s->close();
        }

        $sections = [];
        if ($course_id && $dept_name && $AY) {
            $sql = "SELECT DISTINCT section FROM fvc WHERE course_id=? AND dept=? AND AY=?";
            if ($programme_id > 0) {
                $sql .= " AND (programme_id=? OR programme=?)";
                $q = $conn->prepare($sql);
                $q->bind_param("sssis", $course_id, $dept_name, $AY, $programme_id, $prog_name);
            } else {
                $q = $conn->prepare($sql);
                $q->bind_param("sss", $course_id, $dept_name, $AY);
            }
            $q->execute();
            $r = $q->get_result();
            while ($row = $r->fetch_assoc()) {
                $sections[trim($row['section'])] = true;
            }
            $q->close();
        }

        if (empty($sections) && $dept_short) {
            $q = $conn->prepare("SELECT DISTINCT section FROM student WHERE branch=? ORDER BY CAST(section AS UNSIGNED), section");
            $q->bind_param("s", $dept_short);
            $q->execute();
            $r = $q->get_result();
            while ($row = $r->fetch_assoc()) {
                $sections[trim($row['section'])] = true;
            }
            $q->close();
        }

        if (empty($sections)) { for ($i=1;$i<=5;$i++) $sections[(string)$i]=true; }

        $opts = "<option value=''>-- Select Section --</option>";
        foreach (array_keys($sections) as $sec) {
            $opts .= "<option value='".h($sec)."'>".h($sec)."</option>";
        }

        echo json_encode(['ok'=>true,'sections'=>$opts]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Unknown mode']);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

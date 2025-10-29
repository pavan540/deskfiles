<?php
/*******************************************************
 * faculty_search.php
 * Secure AJAX endpoint for faculty/staff name suggestions
 * Used for remuneration form autocomplete fields
 *******************************************************/
declare(strict_types=1);
session_start();
require_once __DIR__ . '/connection.php';

header('Content-Type: application/json; charset=utf-8');

/* ---------- 1. Session Check ---------- */
if (!isset($_SESSION['faculty_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

/* ---------- 2. Input Validation ---------- */
$term = trim((string)($_GET['term'] ?? ''));
if ($term === '') {
    echo json_encode([]); // empty query → no suggestions
    exit();
}
if (mb_strlen($term) > 64) {
    http_response_code(400);
    echo json_encode(['error' => 'Query too long']);
    exit();
}

/* ---------- 3. Fetch matching faculty/staff ---------- */
try {
    $like = '%' . $term . '%';

    // You can modify this query if you want to include non-teaching staff table also
    $sql = "SELECT faculty_id, name, designation, department
            FROM faculty
            WHERE name LIKE ? OR department LIKE ?
            ORDER BY name ASC
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Database prepare failed.');
    }

    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $rs = $stmt->get_result();

    $out = [];
    while ($row = $rs->fetch_assoc()) {
        $fid          = (string)($row['faculty_id'] ?? '');
        $name         = (string)($row['name'] ?? '');
        $designation  = (string)($row['designation'] ?? '');
        $department   = (string)($row['department'] ?? '');

        // This ensures autocomplete shows name + details, and returns ID
        $out[] = [
            'id'    => $fid, // ✅ faculty_id used by hidden field
            'label' => $name . ' — ' . $designation . ' (' . $department . ')',
            'value' => $name,
        ];
    }
    $stmt->close();

    echo json_encode($out, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Log internally if needed
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}

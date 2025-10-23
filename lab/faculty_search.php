<?php
/*******************************************************
 * faculty_search.php
 * Secure AJAX endpoint for faculty name suggestions
 *******************************************************/
declare(strict_types=1);
session_start();
require_once __DIR__ . '/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['faculty_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$term = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
if ($term === '') {
    echo json_encode([]); // empty -> no suggestions
    exit();
}
if (mb_strlen($term) > 64) { // hard limit to avoid abuse
    http_response_code(400);
    echo json_encode(['error' => 'Query too long']);
    exit();
}

try {
    // Prepared statement for LIKE with wildcards
    $like = '%' . $term . '%';
    $sql = "SELECT name, designation, department 
            FROM faculty 
            WHERE name LIKE ? OR department LIKE ?
            ORDER BY name ASC
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { throw new RuntimeException('Prepare failed'); }
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $rs = $stmt->get_result();

    $out = [];
    while ($row = $rs->fetch_assoc()) {
        $name = (string)($row['name'] ?? '');
        $designation = (string)($row['designation'] ?? '');
        $department = (string)($row['department'] ?? '');
        $out[] = [
            'label' => $name . ' — ' . $designation . ' (' . $department . ')',
            'value' => $name,
        ];
    }
    $stmt->close();

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}

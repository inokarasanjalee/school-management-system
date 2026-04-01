<?php
// [Vuln 5 Fix] Parameter Tampering — input validation + safe error handling
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

include '../db.php';

// [Vuln 5 Fix] Null-coalescing read + trim + length validation
$census = trim($_POST['census'] ?? '');
if (empty($census) || strlen($census) > 20) {
    exit(); // Return empty — JS handles this gracefully
}

$stmt = $conn->prepare("SELECT school_name FROM schools WHERE census_number = ?");
$stmt->bind_param("s", $census);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // [Vuln 5 Fix] Encode output to prevent XSS via school name
    echo htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8');
}
?>

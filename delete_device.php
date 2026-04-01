<?php
// [Vuln 6 Fix] Secure session cookie
require_once '../session_config.php';

if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

// [Vuln 1 Fix] CSRF protection for delete action via GET parameter token
// Delete is triggered by a GET link — we use a per-action token stored in session
// Check that the token in the URL matches the session CSRF token
if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'])) {
    header("Location: ../home.php?error=Invalid request. Please try again.");
    exit();
}

include '../db.php';

// [Vuln 5 Fix] Cast id to integer to prevent injection via tampered GET param
$id     = intval($_GET['id'] ?? 0);
$census = $_SESSION['census_number'];

if ($id <= 0) {
    header("Location: ../home.php?error=Invalid device ID.");
    exit();
}

$stmt = $conn->prepare("DELETE FROM devices WHERE id = ? AND census_number = ?");
$stmt->bind_param("is", $id, $census);

if ($stmt->execute()) {
    header("Location: ../home.php?tab=view-devices&msg=Device deleted successfully");
} else {
    error_log("Device delete error: " . $conn->error);
    header("Location: ../home.php?error=Failed to delete device. Please try again.");
}
?>

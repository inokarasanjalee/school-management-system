<?php
// [Vuln 6 Fix] Secure session cookie
require_once '../session_config.php';

if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

// [Vuln 1 Fix] Validate CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header("Location: ../home.php?error=Invalid request. Please try again.");
    exit();
}

include '../db.php';

$census = $_SESSION['census_number'];

// [Vuln 5 Fix] Safe null-coalescing reads + trim for all POST parameters
$device_type   = trim($_POST['device_type']   ?? '');
$device_name   = trim($_POST['device_name']   ?? '');
$serial_number = trim($_POST['serial_number'] ?? '');
$purchase_date = trim($_POST['purchase_date'] ?? '');
$condition     = trim($_POST['condition']     ?? '');
$status        = trim($_POST['status']        ?? '');
$notes         = trim($_POST['notes']         ?? '');

// [Vuln 5 Fix] Whitelist validation for enum/select fields
$allowed_types      = ['Computer', 'Laptop', 'Tablet', 'Projector', 'Printer', 'Scanner', 'Other'];
$allowed_conditions = ['Excellent', 'Good', 'Fair', 'Needs Repair'];
$allowed_statuses   = ['Active', 'In Repair', 'Retired'];

if (empty($device_type) || !in_array($device_type, $allowed_types)) {
    header("Location: ../home.php?error=Invalid device type.");
    exit();
}
if (empty($device_name) || strlen($device_name) > 100) {
    header("Location: ../home.php?error=Device name is required and must be under 100 characters.");
    exit();
}
if (!in_array($condition, $allowed_conditions)) {
    header("Location: ../home.php?error=Invalid condition value.");
    exit();
}
if (!in_array($status, $allowed_statuses)) {
    header("Location: ../home.php?error=Invalid status value.");
    exit();
}

// [Vuln 5 Fix] Validate purchase_date format if provided
$purchase_date_val = null;
if (!empty($purchase_date)) {
    $d = DateTime::createFromFormat('Y-m-d', $purchase_date);
    if ($d && $d->format('Y-m-d') === $purchase_date) {
        $purchase_date_val = $purchase_date;
    }
}

$stmt = $conn->prepare(
    "INSERT INTO devices (census_number, device_type, device_name, serial_number, purchase_date, `condition`, status, notes) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssssssss", $census, $device_type, $device_name, $serial_number, $purchase_date_val, $condition, $status, $notes);

if ($stmt->execute()) {
    header("Location: ../home.php?tab=view-devices&msg=Device added successfully");
} else {
    // [Vuln 5 Fix] Log error internally — never expose DB error to user
    error_log("Device insert error: " . $conn->error);
    header("Location: ../home.php?error=Failed to add device. Please try again.");
}
?>

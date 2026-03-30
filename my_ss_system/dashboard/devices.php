<?php
session_start();
if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

include '../db.php';

$census = $_SESSION['census_number'];
$device_type = $_POST['device_type'];
$device_name = $_POST['device_name'];
$serial_number = $_POST['serial_number'] ?? '';
$purchase_date = $_POST['purchase_date'] ?? null;
$condition = $_POST['condition'] ?? '';
$status = $_POST['status'] ?? '';
$notes = $_POST['notes'] ?? '';

$stmt = $conn->prepare("INSERT INTO devices (census_number, device_type, device_name, serial_number, purchase_date, `condition`, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $census, $device_type, $device_name, $serial_number, $purchase_date, $condition, $status, $notes);

if ($stmt->execute()) {
    header("Location: ../home.php?tab=view-devices&msg=Device added successfully");
} else {
    die("Error adding device: " . $conn->error);
}
?>
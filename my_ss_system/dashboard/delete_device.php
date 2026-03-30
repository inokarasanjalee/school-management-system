<?php
session_start();
if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

include '../db.php';

$id = $_GET['id'] ?? 0;
$census = $_SESSION['census_number'];

$stmt = $conn->prepare("DELETE FROM devices WHERE id = ? AND census_number = ?");
$stmt->bind_param("is", $id, $census);

if ($stmt->execute()) {
    header("Location: ../home.php?tab=view-devices&msg=Device deleted successfully");
} else {
    die("Error deleting device: " . $conn->error);
}
?>
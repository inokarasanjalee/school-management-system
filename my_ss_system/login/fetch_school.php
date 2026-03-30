<?php
include '../db.php';

$census = $_POST['census'];
$stmt = $conn->prepare("SELECT school_name FROM schools WHERE census_number=?");
$stmt->bind_param("s", $census);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo $row['school_name'];
}
?>
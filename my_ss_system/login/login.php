<?php
session_start();
include '../db.php';

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if (empty($email) || empty($pass)) {
    header("Location: ../index.php?error=Email and password are required.");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: ../index.php?error=Email not found. Please register first.");
    exit();
}

if (!password_verify($pass, $user['password'])) {
    header("Location: ../index.php?error=Incorrect password. Please try again.");
    exit();
}

if (!$user['is_verified']) {
    header("Location: ../index.php?error=Please verify your email first. Check your inbox.");
    exit();
}

$_SESSION['census_number'] = $user['census_number'];
header("Location: ../home.php");
exit();
?>

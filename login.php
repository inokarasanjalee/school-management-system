<?php
// [Vuln 6 Fix] Secure session cookie
require_once '../session_config.php';

// [Vuln 1 Fix] Validate CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header("Location: ../index.php?error=Invalid request. Please try again.");
    exit();
}

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
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../index.php?error=Email not found. Please register first.");
    exit();
}

if (empty($user['password']) && !empty($user['google_id'])) {
    header("Location: ../index.php?error=This account uses Google Login. Please click 'Sign in with Google'.");
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

// [Vuln 1 Fix] Regenerate CSRF token after successful login (session fixation defense)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['census_number'] = $user['census_number'];
header("Location: ../home.php");
exit();
?>

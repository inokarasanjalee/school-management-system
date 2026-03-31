<?php
// [Vuln 6 Fix] Secure session
require_once '../session_config.php';
// [Vuln 2 Fix] Security headers
require_once '../security_headers.php';

// [Vuln 1 Fix] Validate CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header("Location: ../index.php?error=Invalid request. Please try again.");
    exit();
}

include '../db.php';

// [Vuln 5 Fix] Safe parameter read
$email = trim($_POST['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../index.php?error=Please provide a valid email address.");
    exit();
}

$stmt = $conn->prepare("SELECT census_number FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // [Vuln 5 Fix] Don't reveal whether email exists — show same message either way
    // (prevents email enumeration)
    header("Location: ../index.php?msg=If that email is registered, a reset link has been sent.");
    exit();
}

$token   = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = $conn->prepare("REPLACE INTO password_resets (census_number, email, token, expires_at) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user['census_number'], $email, $token, $expires);
$stmt->execute();

$link    = "http://localhost/my_ss_system/login/reset_password.php?token=" . urlencode($token);
include '../email.php';

$message = "
<h2>Password Reset Request</h2>
<p>You requested to reset your password. Click the link below to set a new password:</p>
<p><a href='$link' style='display:inline-block;padding:10px 20px;background:#007bff;color:#fff;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
<p>Or copy and paste this link: $link</p>
<p>This link will expire in 30 minutes.</p>
<p>If you didn't request this, please ignore this email.</p>
";

sendMail($email, "Reset Your Password", $message);

header("Location: ../index.php?msg=If that email is registered, a reset link has been sent.");
exit();
?>

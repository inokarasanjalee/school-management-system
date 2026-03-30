<?php
include '../db.php';
session_start();

$census = trim($_POST['census'] ?? '');
$email  = trim($_POST['email'] ?? '');
$pass   = $_POST['password'] ?? '';
$conf   = $_POST['confirm'] ?? '';

// Validate census number
$check_census = $conn->prepare("SELECT school_name FROM schools WHERE census_number = ?");
$check_census->bind_param("s", $census);
$check_census->execute();
$census_result = $check_census->get_result();
$school_row = $census_result->fetch_assoc();

if (!$school_row) {
    header("Location: ../index.php?error=Invalid Census Number. Please check and try again.");
    exit();
}

$school_name = $school_row['school_name'];

if (strlen($pass) < 6) {
    header("Location: ../index.php?error=Password must be at least 6 characters.");
    exit();
}

if ($pass !== $conf) {
    header("Location: ../index.php?error=Passwords do not match.");
    exit();
}

// Check census not already registered
$check_cn = $conn->prepare("SELECT census_number FROM users WHERE census_number = ?");
$check_cn->bind_param("s", $census);
$check_cn->execute();
$check_cn->store_result();
if ($check_cn->num_rows > 0) {
    header("Location: ../index.php?error=This census number is already registered.");
    exit();
}

// Check email not already used
$check_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
$check_email->bind_param("s", $email);
$check_email->execute();
$check_email->store_result();
if ($check_email->num_rows > 0) {
    header("Location: ../index.php?error=Email already registered. Please use a different email.");
    exit();
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (census_number, school_name, email, password, is_verified) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param("ssss", $census, $school_name, $email, $hash);

if ($stmt->execute()) {
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt2 = $conn->prepare("INSERT INTO email_verifications (census_number, email, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("ssss", $census, $email, $token, $expires);
    $stmt2->execute();

    $link    = "http://localhost/my_ss_system/login/verify_email.php?token=" . urlencode($token);
    include '../email.php';
    $message = "
    <h2>Welcome to School Management System</h2>
    <p>Thank you for registering. Please verify your email by clicking below:</p>
    <p><a href='$link' style='display:inline-block;padding:10px 20px;background:#007bff;color:#fff;text-decoration:none;border-radius:5px;'>Verify Email</a></p>
    <p>Or copy this link: $link</p>
    <p>This link expires in 24 hours.</p>
    ";

    if (sendMail($email, "Verify Your Account", $message)) {
        header("Location: ../index.php?msg=Registration successful! Please check your email to verify your account.");
    } else {
        header("Location: ../index.php?error=Registered but verification email could not be sent. Contact support.");
    }
    exit();
} else {
    header("Location: ../index.php?error=Registration failed. Please try again.");
    exit();
}
?>

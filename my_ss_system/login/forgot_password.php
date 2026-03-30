<?php
include '../db.php';

$email = $_POST['email'];

$stmt = $conn->prepare("SELECT census_number FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Email not found in our system.");
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = $conn->prepare("REPLACE INTO password_resets (census_number, email, token, expires_at) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user['census_number'], $email, $token, $expires);
$stmt->execute();

$link = "http://localhost/my_ss_system/login/reset_password.php?token=" . urlencode($token);

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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow text-center">
                    <div class="card-body">
                        <h4 class="text-success">Reset Link Sent!</h4>
                        <p class="mt-3">A password reset link has been sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                        <p>Please check your email inbox.</p>
                        <a href="../index.php" class="btn btn-primary mt-2">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
include '../db.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: ../index.php?error=Invalid verification link.");
    exit();
}

// Find the verification record
$stmt = $conn->prepare("SELECT census_number, email, expires_at FROM email_verifications WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Check if token is expired
    if (strtotime($row['expires_at']) < time()) {
        // Delete expired token
        $delete = $conn->prepare("DELETE FROM email_verifications WHERE token = ?");
        $delete->bind_param("s", $token);
        $delete->execute();
        header("Location: ../index.php?error=Verification link has expired. Please request a new one.");
        exit();
    }
    
    // Update user as verified
    $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE census_number = ?");
    $update->bind_param("s", $row['census_number']);
    $update->execute();
    
    // Delete verification record
    $delete = $conn->prepare("DELETE FROM email_verifications WHERE token = ?");
    $delete->bind_param("s", $token);
    $delete->execute();
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Email Verified - School System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 64px;"></i>
                            </div>
                            <h3 class="text-success">Email Verified Successfully!</h3>
                            <p class="mt-3">Your email has been verified. You can now login to your account.</p>
                            <a href="../index.php" class="btn btn-primary mt-3">Go to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    header("Location: ../index.php?error=Invalid verification link.");
    exit();
}
?>
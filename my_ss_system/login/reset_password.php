<?php
include '../db.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_pass = $_POST['password'];
    $confirm = $_POST['confirm'];
    
    if ($new_pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Verify token
        $stmt = $conn->prepare("SELECT census_number FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $update = $conn->prepare("UPDATE users SET password = ? WHERE census_number = ?");
            $update->bind_param("ss", $hash, $row['census_number']);
            $update->execute();
            
            // Delete used token
            $delete = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $delete->bind_param("s", $token);
            $delete->execute();
            
            $success = true;
        } else {
            $error = "Invalid or expired reset link.";
        }
    }
}

// Check if token is valid for form display
$valid = false;
if ($_SERVER['REQUEST_METHOD'] != 'POST' && $token) {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $valid = true;
    } else {
        $error = "Invalid or expired reset link.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0">Reset Password</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success) && $success): ?>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-check-circle"></i> Password reset successful!
                            </div>
                            <div class="text-center mt-3">
                                <a href="../index.php" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php elseif(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                            <div class="text-center mt-3">
                                <a href="../index.php" class="btn btn-primary">Back to Login</a>
                            </div>
                        <?php elseif($valid): ?>
                            <form method="POST">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                No valid reset token found.
                            </div>
                            <div class="text-center mt-3">
                                <a href="../index.php" class="btn btn-primary">Back to Login</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
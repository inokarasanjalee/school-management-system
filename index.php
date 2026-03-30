<?php
session_start();
if (isset($_SESSION['census_number'])) {
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School System Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- FIX: Load Google GSI script properly with onload callback -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        .nav-tabs .nav-link { color: #495057; font-weight: 500; }
        .nav-tabs .nav-link.active { color: #0d6efd; font-weight: 600; }
        .card { border: none; }
        .card-body { padding: 2rem; }
        .password-toggle { position: relative; }
        .password-toggle .toggle-icon {
            position: absolute; right: 12px; top: 70%;
            transform: translateY(-50%); cursor: pointer;
            color: #6c757d; opacity: 0.7; transition: opacity 0.2s;
        }
        .password-toggle .toggle-icon:hover { opacity: 1; }
        .btn-primary, .btn-success { padding: 10px; font-weight: 500; }
        .g_id_signin { display: flex; justify-content: center; }
        hr { margin: 1.5rem 0; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-6">

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-lg">
<div class="card-body">

<ul class="nav nav-tabs nav-justified mb-4" id="authTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
            <i class="bi bi-box-arrow-in-right"></i> Login
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
            <i class="bi bi-person-plus"></i> Register
        </button>
    </li>
</ul>

<div class="tab-content" id="authTabContent">

    <!-- LOGIN FORM TAB -->
    <div class="tab-pane fade show active" id="login" role="tabpanel">
        <form action="login/login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3 password-toggle">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter your password" required>
                <span class="toggle-icon" onclick="togglePassword('loginPassword')">👁️</span>
            </div>
            <button class="btn btn-success w-100">Login</button>
        </form>

        <div class="text-center mt-3">
            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot Password?</a>
        </div>

        <hr>
        <h5 class="text-center mb-3">Or Login With</h5>

        <!-- FIX: Single g_id_onload div - only one per page needed -->
        <div id="g_id_onload"
             data-client_id="388191385158-4cmh22n4s5eaaer5sb0lfqkt7beepq54.apps.googleusercontent.com"
             data-login_uri="http://localhost/my_ss_system/login/google_callback.php"
             data-auto_prompt="false"
             data-auto_select="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-size="large"
             data-theme="outline"
             data-text="sign_in_with"
             data-shape="rectangular"
             data-logo_alignment="left"
             data-width="300">
        </div>
    </div>

    <!-- REGISTER FORM TAB -->
    <div class="tab-pane fade" id="register" role="tabpanel">
        <form action="login/register.php" method="POST" id="registerForm">
            <div class="mb-3">
                <label class="form-label">Census Number</label>
                <input type="text" name="census" id="census" class="form-control" placeholder="Enter your school census number" required>
                <div id="schoolPreview" class="form-text text-muted mt-1"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">School Name</label>
                <input type="text" name="school" id="school" class="form-control" placeholder="School name will auto-fill" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3 password-toggle">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="regPassword" class="form-control" placeholder="Create a password" required>
                <span class="toggle-icon" onclick="togglePassword('regPassword')">👁️</span>
            </div>
            <div class="mb-3 password-toggle">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm" id="confirmPassword" class="form-control" placeholder="Confirm your password" required>
                <span class="toggle-icon" onclick="togglePassword('confirmPassword')">👁️</span>
            </div>
            <div class="mb-3 form-text">
                <small>Password must be at least 6 characters long.</small>
            </div>
            <button class="btn btn-primary w-100">Create Account</button>
        </form>

        <hr>
        <h5 class="text-center mb-3">Or Register With</h5>
        <!-- FIX: Only show the button here, g_id_onload already declared above (it works for both tabs) -->
        <div class="g_id_signin"
             data-type="standard"
             data-size="large"
             data-theme="outline"
             data-text="signup_with"
             data-shape="rectangular"
             data-logo_alignment="left"
             data-width="300">
        </div>
        <div class="text-center mt-2">
            <small class="text-muted">By registering, you agree to our Terms of Service</small>
        </div>
    </div>

</div>
</div>
</div>

</div>
</div>
</div>

<!-- FORGOT PASSWORD MODAL -->
<div class="modal fade" id="forgotModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form action="login/forgot_password.php" method="POST">
<div class="modal-header">
    <h5 class="modal-title">Reset Password</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <p>Enter your email address and we'll send you a link to reset your password.</p>
    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button class="btn btn-primary">Send Reset Link</button>
</div>
</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}

// Auto-fetch school name when census number is typed
document.getElementById("census").addEventListener("keyup", function(){
    const census = this.value.trim();
    const schoolPreview = document.getElementById("schoolPreview");
    const schoolField = document.getElementById("school");

    if (census.length > 0) {
        fetch("login/fetch_school.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "census=" + encodeURIComponent(census)
        })
        .then(res => res.text())
        .then(data => {
            if (data && data.trim().length > 0) {
                schoolField.value = data.trim();
                schoolPreview.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> School found: ' + data.trim();
                schoolPreview.className = "form-text text-success mt-1";
            } else {
                schoolField.value = "";
                schoolPreview.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger"></i> Invalid census number';
                schoolPreview.className = "form-text text-danger mt-1";
            }
        })
        .catch(() => {
            schoolField.value = "";
            schoolPreview.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger"></i> Error fetching school data';
            schoolPreview.className = "form-text text-danger mt-1";
        });
    } else {
        schoolField.value = "";
        schoolPreview.innerHTML = "";
    }
});

// Password validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('regPassword').value;
    const confirm  = document.getElementById('confirmPassword').value;
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long');
        return false;
    }
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
    }
});

document.getElementById('register-tab').addEventListener('click', function() {
    setTimeout(() => document.getElementById('census').focus(), 100);
});
</script>

</body>
</html>

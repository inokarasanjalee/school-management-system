<?php
// [Vuln 6 Fix] Secure session cookie
require_once 'session_config.php';
// [Vuln 2 Fix] CSP + Clickjacking headers
require_once 'security_headers.php';

if (!isset($_SESSION['google_temp_email'])) {
    header("Location: index.php");
    exit();
}

// [Vuln 1 Fix] Generate CSRF token for the census verification form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // [Vuln 1 Fix] Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: index.php?error=Invalid request. Please try again.");
        exit();
    }

    // [Vuln 5 Fix] Safe parameter read + length validation
    $census = trim($_POST['census'] ?? '');
    if (empty($census) || strlen($census) > 20) {
        $error = "Please enter a valid census number.";
    } else {
        $stmt = $conn->prepare("SELECT school_name FROM schools WHERE census_number = ?");
        $stmt->bind_param("s", $census);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $checkUser = $conn->prepare("SELECT census_number FROM users WHERE census_number = ?");
            $checkUser->bind_param("s", $census);
            $checkUser->execute();
            $checkUser->store_result();

            if ($checkUser->num_rows > 0) {
                $error = "This census number is already registered. Please contact support.";
            } else {
                $stmt2 = $conn->prepare(
                    "INSERT INTO users (census_number, school_name, email, password, is_verified, google_id) 
                     VALUES (?, ?, ?, '', 1, ?)"
                );
                $stmt2->bind_param("ssss", $census, $row['school_name'],
                    $_SESSION['google_temp_email'], $_SESSION['google_temp_id']);

                if ($stmt2->execute()) {
                    $_SESSION['census_number'] = $census;
                    // [Vuln 1 Fix] Regenerate CSRF token after login
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    unset($_SESSION['google_temp_email'], $_SESSION['google_temp_name'], $_SESSION['google_temp_id']);
                    header("Location: home.php");
                    exit();
                } else {
                    error_log("Google registration error: " . $conn->error);
                    $error = "Registration failed. Please try again.";
                }
            }
        } else {
            $error = "Invalid Census Number. Please check and try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify School - School System</title>
    <!-- [Vuln 4 Fix] SRI hash on Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0">Complete Your Registration</h4>
                    </div>
                    <div class="card-body">
                        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['google_temp_name'] ?? ''); ?></strong>!</p>
                        <p>Please enter your school's census number to complete registration.</p>

                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- [Vuln 1 Fix] CSRF token hidden field -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="mb-3">
                                <label class="form-label">Census Number</label>
                                <input type="text" name="census" id="census" class="form-control" required maxlength="20">
                                <div id="schoolInfo" class="mt-2 small"></div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Verify & Continue</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="index.php" class="text-muted small">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById("census").addEventListener("keyup", function(){
        const val = this.value.trim();
        if (!val) { document.getElementById("schoolInfo").innerHTML = ""; return; }
        fetch("login/fetch_school.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "census=" + encodeURIComponent(val)
        })
        .then(res => res.text())
        .then(data => {
            const el = document.getElementById("schoolInfo");
            if (data && data.trim()) {
                el.innerHTML = '<span class="text-success">✔ ' + data.trim() + '</span>';
            } else {
                el.innerHTML = '<span class="text-danger">✘ Census number not found</span>';
            }
        });
    });
    </script>
</body>
</html>

<?php
session_start();

// FIX: Redirect if no Google temp session exists
if (!isset($_SESSION['google_temp_email'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $census = trim($_POST['census']);

    // Verify census exists in schools table
    $stmt = $conn->prepare("SELECT school_name FROM schools WHERE census_number = ?");
    $stmt->bind_param("s", $census);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // FIX: Check if census_number already registered
        $checkUser = $conn->prepare("SELECT census_number FROM users WHERE census_number = ?");
        $checkUser->bind_param("s", $census);
        $checkUser->execute();
        $checkUser->store_result();

        if ($checkUser->num_rows > 0) {
            $error = "This census number is already registered. Please contact support.";
        } else {
            $stmt2 = $conn->prepare("INSERT INTO users (census_number, school_name, email, password, is_verified, google_id) VALUES (?, ?, ?, '', 1, ?)");
            $stmt2->bind_param("ssss", $census, $row['school_name'], $_SESSION['google_temp_email'], $_SESSION['google_temp_id']);

            if ($stmt2->execute()) {
                $_SESSION['census_number'] = $census;
                unset($_SESSION['google_temp_email']);
                unset($_SESSION['google_temp_name']);
                unset($_SESSION['google_temp_id']);
                header("Location: home.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    } else {
        $error = "Invalid Census Number. Please check and try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify School - School System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                            <div class="mb-3">
                                <label class="form-label">Census Number</label>
                                <input type="text" name="census" id="census" class="form-control" required>
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
        // FIX: correct path - fetch_school is in login/ folder
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

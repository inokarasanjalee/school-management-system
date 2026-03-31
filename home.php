<?php
// [Vuln 6 Fix] Secure session cookie
require_once 'session_config.php';
// [Vuln 2 Fix] CSP + Clickjacking headers
require_once 'security_headers.php';

if (!isset($_SESSION['census_number'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// [Vuln 1 Fix] Ensure CSRF token present for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$stmt = $conn->prepare("SELECT * FROM users WHERE census_number = ?");
$stmt->bind_param("s", $_SESSION['census_number']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM schools WHERE census_number = ?");
$stmt->bind_param("s", $user['census_number']);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();

// CSP FIX: Pass tab parameter to JS via a data attribute on body instead of inline script
$active_tab = htmlspecialchars($_GET['tab'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - School System</title>
    <!-- [Vuln 4 Fix] SRI hashes on CDN links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          integrity="sha384-tViUnnbplMdV7RkSHMQ7eWMGarLITiIGwAJMIFYJPPRtQkz5VN19N3DEqxYdIfqo"
          crossorigin="anonymous">
</head>
<!-- CSP FIX: Active tab passed via data attribute — no inline script needed -->
<body class="bg-light" data-active-tab="<?php echo $active_tab; ?>">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">School Management System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-white">Welcome, <?php echo htmlspecialchars($user['school_name']); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="login/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h5 class="card-title">School Information</h5>
            <p class="card-text">
                <strong>Census Number:</strong> <?php echo htmlspecialchars($user['census_number']); ?><br>
                <strong>School Name:</strong> <?php echo htmlspecialchars($school['school_name'] ?? $user['school_name']); ?><br>
                <?php if($school): ?>
                <strong>Address:</strong> <?php echo htmlspecialchars($school['school_address'] ?? 'N/A'); ?><br>
                <strong>Type:</strong> <?php echo htmlspecialchars($school['school_type'] ?? 'N/A'); ?><br>
                <strong>Zone:</strong> <?php echo htmlspecialchars($school['zone'] ?? 'N/A'); ?><br>
                <strong>Division:</strong> <?php echo htmlspecialchars($school['division'] ?? 'N/A'); ?><br>
                <strong>District:</strong> <?php echo htmlspecialchars($school['district'] ?? 'N/A'); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="add-device-tab" data-bs-toggle="tab"
                    data-bs-target="#add-device" type="button" role="tab">
                <i class="bi bi-plus-circle"></i> Add Device
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="view-devices-tab" data-bs-toggle="tab"
                    data-bs-target="#view-devices" type="button" role="tab">
                <i class="bi bi-list-ul"></i> View Devices
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="feedback-tab" data-bs-toggle="tab"
                    data-bs-target="#feedback" type="button" role="tab">
                <i class="bi bi-envelope"></i> Send Feedback
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab"
                    data-bs-target="#profile" type="button" role="tab">
                <i class="bi bi-person"></i> My Profile
            </button>
        </li>
    </ul>

    <div class="tab-content p-4 bg-white shadow rounded-bottom" id="myTabContent">

        <!-- Add Device Tab -->
        <div class="tab-pane fade show active" id="add-device" role="tabpanel">
            <h4>Add School Device Information</h4>
            <form action="dashboard/devices.php" method="POST" class="mt-3">
                <!-- [Vuln 1 Fix] CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Device Type</label>
                        <select name="device_type" class="form-control" required>
                            <option value="">Select Device Type</option>
                            <option value="Computer">Computer</option>
                            <option value="Laptop">Laptop</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Projector">Projector</option>
                            <option value="Printer">Printer</option>
                            <option value="Scanner">Scanner</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Device Name/Model</label>
                        <input type="text" name="device_name" class="form-control" required maxlength="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-control">
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Needs Repair">Needs Repair</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="In Repair">In Repair</option>
                            <option value="Retired">Retired</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Additional Notes</label>
                        <textarea name="notes" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Device</button>
            </form>
        </div>

        <!-- View Devices Tab -->
        <div class="tab-pane fade" id="view-devices" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>My School Devices</h4>
                <div>
                    <input type="text" id="searchDevice" class="form-control" placeholder="Search devices...">
                </div>
            </div>
            <div id="devicesList">
                <div class="text-center">Loading devices...</div>
            </div>
        </div>

        <!-- Feedback Tab -->
        <div class="tab-pane fade" id="feedback" role="tabpanel">
            <h4>Send Feedback</h4>
            <form action="dashboard/feedback.php" method="POST" class="mt-3">
                <!-- [Vuln 1 Fix] CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" required maxlength="200">
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="5" required maxlength="5000"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Feedback</button>
            </form>
        </div>

        <!-- Profile Tab -->
        <div class="tab-pane fade" id="profile" role="tabpanel">
            <h4>My Profile</h4>
            <div class="row mt-3">
                <div class="col-md-6">
                    <p><strong>Census Number:</strong> <?php echo htmlspecialchars($user['census_number']); ?></p>
                    <p><strong>School Name:</strong> <?php echo htmlspecialchars($user['school_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Account Type:</strong> <?php echo $user['google_id'] ? 'Google Account' : 'Regular Account'; ?></p>
                    <p><strong>Verified:</strong> <?php echo $user['is_verified'] ? 'Yes' : 'No'; ?></p>
                    <p><strong>Joined:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                </div>
                <div class="col-md-6">
                    <?php if($school): ?>
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">School Details</h6>
                            <p class="card-text small">
                                <strong>Address:</strong> <?php echo htmlspecialchars($school['school_address'] ?? 'N/A'); ?><br>
                                <strong>Type:</strong> <?php echo htmlspecialchars($school['school_type'] ?? 'N/A'); ?><br>
                                <strong>Zone:</strong> <?php echo htmlspecialchars($school['zone'] ?? 'N/A'); ?><br>
                                <strong>Division:</strong> <?php echo htmlspecialchars($school['division'] ?? 'N/A'); ?><br>
                                <strong>Province:</strong> <?php echo htmlspecialchars($school['province'] ?? 'N/A'); ?><br>
                                <strong>District:</strong> <?php echo htmlspecialchars($school['district'] ?? 'N/A'); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- [Vuln 4 Fix] SRI hash on Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
<!-- CSP FIX: All inline JavaScript moved to external file — removes 'unsafe-inline' from script-src -->
<script src="assets/js/dashboard.js"></script>
</body>
</html>

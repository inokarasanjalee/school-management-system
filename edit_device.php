<?php
// [Vuln 6 Fix] Secure session cookie
require_once '../session_config.php';
// [Vuln 2 Fix] CSP + Clickjacking headers
require_once '../security_headers.php';

if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

// [Vuln 1 Fix] Ensure CSRF token exists in session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

include '../db.php';

// [Vuln 5 Fix] Cast id to integer — prevents parameter tampering via GET
$id     = intval($_GET['id'] ?? 0);
$census = $_SESSION['census_number'];

if ($id <= 0) {
    header("Location: ../home.php?error=Invalid device ID.");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM devices WHERE id = ? AND census_number = ?");
$stmt->bind_param("is", $id, $census);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
    header("Location: ../home.php?error=Device not found.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // [Vuln 1 Fix] Validate CSRF token on form submission
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../home.php?error=Invalid request.");
        exit();
    }

    // [Vuln 5 Fix] Safe reads + whitelist validation
    $device_type   = trim($_POST['device_type']   ?? '');
    $device_name   = trim($_POST['device_name']   ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $purchase_date = trim($_POST['purchase_date'] ?? '');
    $condition     = trim($_POST['condition']     ?? '');
    $status        = trim($_POST['status']        ?? '');
    $notes         = trim($_POST['notes']         ?? '');

    $allowed_types      = ['Computer', 'Laptop', 'Tablet', 'Projector', 'Printer', 'Scanner', 'Other'];
    $allowed_conditions = ['Excellent', 'Good', 'Fair', 'Needs Repair'];
    $allowed_statuses   = ['Active', 'In Repair', 'Retired'];

    if (!in_array($device_type, $allowed_types) || empty($device_name) ||
        !in_array($condition, $allowed_conditions) || !in_array($status, $allowed_statuses)) {
        $error = "Invalid input values. Please check your selections.";
    } else {
        $purchase_date_val = null;
        if (!empty($purchase_date)) {
            $d = DateTime::createFromFormat('Y-m-d', $purchase_date);
            if ($d && $d->format('Y-m-d') === $purchase_date) {
                $purchase_date_val = $purchase_date;
            }
        }

        $update = $conn->prepare(
            "UPDATE devices SET device_type=?, device_name=?, serial_number=?, purchase_date=?, 
             `condition`=?, status=?, notes=? WHERE id=? AND census_number=?"
        );
        $update->bind_param("sssssssis", $device_type, $device_name, $serial_number,
            $purchase_date_val, $condition, $status, $notes, $id, $census);

        if ($update->execute()) {
            header("Location: ../home.php?tab=view-devices&msg=Device updated successfully");
            exit();
        } else {
            error_log("Device update error: " . $conn->error);
            $error = "Error updating device. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Device</title>
    <!-- [Vuln 4 Fix] SRI hash on Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Device</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- [Vuln 1 Fix] CSRF token hidden field -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Device Type</label>
                                    <select name="device_type" class="form-control" required>
                                        <?php foreach(['Computer','Laptop','Tablet','Projector','Printer','Scanner','Other'] as $t): ?>
                                        <option value="<?php echo $t; ?>" <?php echo $device['device_type']==$t?'selected':''; ?>>
                                            <?php echo $t; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Device Name/Model</label>
                                    <input type="text" name="device_name" class="form-control" maxlength="100"
                                           value="<?php echo htmlspecialchars($device['device_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Serial Number</label>
                                    <input type="text" name="serial_number" class="form-control" maxlength="100"
                                           value="<?php echo htmlspecialchars($device['serial_number']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-control"
                                           value="<?php echo htmlspecialchars($device['purchase_date'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Condition</label>
                                    <select name="condition" class="form-control">
                                        <?php foreach(['Excellent','Good','Fair','Needs Repair'] as $c): ?>
                                        <option value="<?php echo $c; ?>" <?php echo $device['condition']==$c?'selected':''; ?>>
                                            <?php echo $c; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <?php foreach(['Active','In Repair','Retired'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $device['status']==$s?'selected':''; ?>>
                                            <?php echo $s; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea name="notes" class="form-control" rows="3" maxlength="1000"><?php
                                        echo htmlspecialchars($device['notes']);
                                    ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Device</button>
                            <a href="../home.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [Vuln 4 Fix] SRI hash on Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>
</body>
</html>

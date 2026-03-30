<?php
session_start();
if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

include '../db.php';

$id = $_GET['id'] ?? 0;
$census = $_SESSION['census_number'];

// Get device details
$stmt = $conn->prepare("SELECT * FROM devices WHERE id = ? AND census_number = ?");
$stmt->bind_param("is", $id, $census);
$stmt->execute();
$result = $stmt->get_result();
$device = $result->fetch_assoc();

if (!$device) {
    die("Device not found.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $device_type = $_POST['device_type'];
    $device_name = $_POST['device_name'];
    $serial_number = $_POST['serial_number'];
    $purchase_date = $_POST['purchase_date'] ?: null;
    $condition = $_POST['condition'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    $update = $conn->prepare("UPDATE devices SET device_type=?, device_name=?, serial_number=?, purchase_date=?, `condition`=?, status=?, notes=? WHERE id=? AND census_number=?");
    $update->bind_param("sssssssis", $device_type, $device_name, $serial_number, $purchase_date, $condition, $status, $notes, $id, $census);
    
    if ($update->execute()) {
        header("Location: ../home.php?tab=view-devices&msg=Device updated successfully");
        exit();
    } else {
        $error = "Error updating device: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Device</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Device Type</label>
                                    <select name="device_type" class="form-control" required>
                                        <option value="Computer" <?php echo $device['device_type'] == 'Computer' ? 'selected' : ''; ?>>Computer</option>
                                        <option value="Laptop" <?php echo $device['device_type'] == 'Laptop' ? 'selected' : ''; ?>>Laptop</option>
                                        <option value="Tablet" <?php echo $device['device_type'] == 'Tablet' ? 'selected' : ''; ?>>Tablet</option>
                                        <option value="Projector" <?php echo $device['device_type'] == 'Projector' ? 'selected' : ''; ?>>Projector</option>
                                        <option value="Printer" <?php echo $device['device_type'] == 'Printer' ? 'selected' : ''; ?>>Printer</option>
                                        <option value="Scanner" <?php echo $device['device_type'] == 'Scanner' ? 'selected' : ''; ?>>Scanner</option>
                                        <option value="Other" <?php echo $device['device_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Device Name/Model</label>
                                    <input type="text" name="device_name" class="form-control" value="<?php echo htmlspecialchars($device['device_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Serial Number</label>
                                    <input type="text" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($device['serial_number']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-control" value="<?php echo $device['purchase_date']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Condition</label>
                                    <select name="condition" class="form-control">
                                        <option value="Excellent" <?php echo $device['condition'] == 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                                        <option value="Good" <?php echo $device['condition'] == 'Good' ? 'selected' : ''; ?>>Good</option>
                                        <option value="Fair" <?php echo $device['condition'] == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                        <option value="Needs Repair" <?php echo $device['condition'] == 'Needs Repair' ? 'selected' : ''; ?>>Needs Repair</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="Active" <?php echo $device['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="In Repair" <?php echo $device['status'] == 'In Repair' ? 'selected' : ''; ?>>In Repair</option>
                                        <option value="Retired" <?php echo $device['status'] == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($device['notes']); ?></textarea>
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
</body>
</html>
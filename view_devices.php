<?php
// [Vuln 6 Fix] Secure session cookie
require_once '../session_config.php';

if (!isset($_SESSION['census_number'])) {
    exit("Unauthorized");
}

include '../db.php';

$census = $_SESSION['census_number'];

// [Vuln 5 Fix] Safe null-coalescing read + trim for search parameter
$search = trim($_GET['search'] ?? '');

$query  = "SELECT * FROM devices WHERE census_number = ?";
$params = [$census];
$types  = "s";

if (!empty($search)) {
    // [Vuln 5 Fix] Limit search length to prevent excessive queries
    $search      = substr($search, 0, 100);
    $query      .= " AND (device_name LIKE ? OR device_type LIKE ? OR serial_number LIKE ?)";
    $search_term = "%$search%";
    $params[]    = $search_term;
    $params[]    = $search_term;
    $params[]    = $search_term;
    $types       .= "sss";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// [Vuln 1 Fix] Include CSRF token in delete link so delete_device.php can verify it
$csrf_token = $_SESSION['csrf_token'] ?? '';

if ($result->num_rows == 0) {
    echo '<div class="alert alert-info">No devices found.</div>';
} else {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Device Name</th>
                    <th>Serial Number</th>
                    <th>Purchase Date</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo (int)$row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['device_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['device_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                    <td><?php echo $row['purchase_date'] ? htmlspecialchars(date('Y-m-d', strtotime($row['purchase_date']))) : '-'; ?></td>
                    <td>
                        <span class="badge bg-<?php
                            echo $row['condition'] == 'Excellent' ? 'success' :
                                ($row['condition'] == 'Good'      ? 'info'    :
                                ($row['condition'] == 'Fair'      ? 'warning' : 'danger'));
                        ?>">
                            <?php echo htmlspecialchars($row['condition']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'success' : 'secondary'; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_device.php?id=<?php echo (int)$row['id']; ?>"
                           class="btn btn-sm btn-primary">Edit</a>
                        <!-- [Vuln 1 Fix] CSRF token appended to delete link -->
                        <a href="delete_device.php?id=<?php echo (int)$row['id']; ?>&csrf_token=<?php echo urlencode($csrf_token); ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure you want to delete this device?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>

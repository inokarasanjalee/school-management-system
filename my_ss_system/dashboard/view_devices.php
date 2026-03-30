<?php
session_start();
if (!isset($_SESSION['census_number'])) {
    exit("Unauthorized");
}

include '../db.php';

$census = $_SESSION['census_number'];
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM devices WHERE census_number = ?";
$params = [$census];
$types = "s";

if (!empty($search)) {
    $query .= " AND (device_name LIKE ? OR device_type LIKE ? OR serial_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

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
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['device_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['device_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                    <td><?php echo $row['purchase_date'] ? date('Y-m-d', strtotime($row['purchase_date'])) : '-'; ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $row['condition'] == 'Excellent' ? 'success' : 
                                 ($row['condition'] == 'Good' ? 'info' : 
                                 ($row['condition'] == 'Fair' ? 'warning' : 'danger')); 
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
                        <a href="edit_device.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="delete_device.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
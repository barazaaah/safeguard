<?php
require_once '../config/auth.php';
requireAdmin();

require_once '../config/db.php';

// Read filters
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($from !== '') {
    $where[] = "DATE(a.created_at) >= ?";
    $params[] = $from;
}

if ($to !== '') {
    $where[] = "DATE(a.created_at) <= ?";
    $params[] = $to;
}

$allowedStatuses = ['Pending', 'Responding', 'Resolved'];

if (in_array($status, $allowedStatuses, true)) {
    $where[] = "a.status = ?";
    $params[] = $status;
}

$sql = "SELECT
            a.id,
            a.incident_type,
            a.description,
            a.location,
            a.latitude,
            a.longitude,
            a.status,
            a.created_at,
            u.fullname,
            u.email,
            u.phone
        FROM alerts a
        JOIN users u ON a.user_id = u.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export filtered records to CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=safeguard_emergency_report.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Alert ID',
        'User',
        'Email',
        'Phone',
        'Incident Type',
        'Description',
        'Location',
        'Latitude',
        'Longitude',
        'Status',
        'Date Reported'
    ]);

    foreach ($alerts as $alert) {
        $row = [
            $alert['id'],
            $alert['fullname'],
            $alert['email'],
            $alert['phone'],
            $alert['incident_type'],
            $alert['description'],
            $alert['location'],
            $alert['latitude'],
            $alert['longitude'],
            $alert['status'],
            $alert['created_at']
        ];

        // Reduce the risk of spreadsheet formula execution
        foreach ($row as &$value) {
            $value = (string)($value ?? '');
            if (preg_match('/^[\s]*[=+\-@]/', $value)) {
                $value = "'" . $value;
            }
        }
        unset($value);

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Reports | SafeGuard Admin</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Emergency Reports</h1>
    <p>View, filter and export emergency reports submitted through SafeGuard.</p>

    <p>
        <a href="admin_dashboard.php">Admin Dashboard</a>
        <a href="admin_alerts.php">All Alerts</a>
        <a href="admin_users.php">Manage Users</a>
        <a href="logout.php">Logout</a>
    </p>

    <hr>

    <h2>Filter Emergency Records</h2>

    <form method="GET" action="reports.php">

        <label>From Date:</label><br>
        <input type="date" name="from" value="<?= e($from) ?>">

        <br><br>

        <label>To Date:</label><br>
        <input type="date" name="to" value="<?= e($to) ?>">

        <br><br>

        <label>Alert Status:</label><br>
        <select name="status">
            <option value="">All Statuses</option>
            <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Responding" <?= $status === 'Responding' ? 'selected' : '' ?>>Responding</option>
            <option value="Resolved" <?= $status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
        </select>

        <br><br>

        <button type="submit">Filter Records</button>

        <a href="reports.php">Clear Filters</a>

    </form>

    <br>

    <form method="GET" action="reports.php">
        <input type="hidden" name="from" value="<?= e($from) ?>">
        <input type="hidden" name="to" value="<?= e($to) ?>">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <button type="submit" name="export" value="1">
            Download CSV Report
        </button>
    </form>

    <hr>

    <h2>Emergency Records</h2>

    <p><strong>Total records found:</strong> <?= count($alerts) ?></p>

    <?php if (count($alerts) > 0): ?>

    <div style="overflow-x:auto;">
        <table border="1" cellpadding="10" cellspacing="0" width="100%">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Contact</th>
                    <th>Incident</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Map</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($alerts as $alert): ?>

                <tr>
                    <td><?= e($alert['id']) ?></td>

                    <td><?= e($alert['fullname']) ?></td>

                    <td>
                        <?= e($alert['email']) ?><br>
                        <?= e($alert['phone']) ?>
                    </td>

                    <td>
                        <?= e($alert['incident_type']) ?><br>
                        <?= e($alert['description']) ?>
                    </td>

                    <td><?= e($alert['location']) ?></td>

                    <td><?= e($alert['status']) ?></td>

                    <td><?= e($alert['created_at']) ?></td>

                    <td>
                        <?php if (
                            $alert['latitude'] !== null &&
                            $alert['longitude'] !== null &&
                            is_numeric($alert['latitude']) &&
                            is_numeric($alert['longitude'])
                        ): ?>
                            <a target="_blank" rel="noopener"
                               href="https://www.google.com/maps?q=<?= rawurlencode($alert['latitude'] . ',' . $alert['longitude']) ?>">
                                View Map
                            </a>
                        <?php else: ?>
                            No coordinates
                        <?php endif; ?>
                    </td>
                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>
    </div>

    <?php else: ?>

        <p>No emergency records match your filters.</p>

    <?php endif; ?>

</div>

</body>
</html>

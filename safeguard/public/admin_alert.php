
<?php
require_once '../config/auth.php';
requireAdmin();
require_once '../config/db.php';

$stmt = $conn->query(
    "SELECT alerts.*, users.fullname, users.email, users.phone
     FROM alerts
     JOIN users ON alerts.user_id = users.id
     ORDER BY alerts.created_at DESC"
);

$alerts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Monitoring - SafeGuard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h2>SAFEGUARD ADMIN</h2>

    <div>
        <a href="admin_dashboard.php">DASHBOARD</a>
        <a href="admin_users.php">USERS</a>
        <a href="admin_alert.php">ALERTS</a>
        <a href="logout.php">LOGOUT</a>
    </div>
</nav>

<main class="dashboard">

    <h1>EMERGENCY MONITOR CENTER</h1>

    <p>All emergency reports submitted through SafeGuard.</p>

    <div class="table-container">
    <table>

        <tr>
            <th>ALERT ID</th>
            <th>USER</th>
            <th>PHONE</th>
            <th>EMERGENCY</th>
            <th>LOCATION</th>
            <th>STATUS</th>
            <th>DATE</th>
            <th>ACTION</th>
        </tr>

        <?php foreach ($alerts as $alert): ?>

        <tr>
            <td><?php echo (int)$alert['id']; ?></td>

            <td>
                <?php echo htmlspecialchars($alert['fullname']); ?>
                <br>
                <small><?php echo htmlspecialchars($alert['email']); ?></small>
            </td>

            <td>
                <?php echo htmlspecialchars($alert['phone']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($alert['incident_type']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($alert['location'] ?? 'Not provided'); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($alert['status']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($alert['created_at']); ?>
            </td>

            <td>
                <a href="admin_alert_details.php?id=<?php echo (int)$alert['id']; ?>">
                    MANAGE
                </a>
            </td>
        </tr>

        <?php endforeach; ?>

    </table>
    </div>

    <?php if (count($alerts) === 0): ?>
        <p>No emergency alerts have been submitted yet.</p>
    <?php endif; ?>

</main>

</body>
</html>
